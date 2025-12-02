import {Controller} from '@hotwired/stimulus';
import {SelectManager} from '../js/modules/select-manager.js';
import {ApiClient} from '../js/modules/api-client.js';
import {ExtensionsManager} from '../js/modules/extensions-manager.js';

/**
 * Stimulus controller pour le formulaire de service projet.
 *
 * Gère les interactions dynamiques :
 * - Chargement AJAX des frameworks, versions et extensions selon le langage
 * - Normalisation automatique du champ folderName
 * - Calcul automatique de l'URL du service
 * - Gestion du mode édition vs création
 *
 * Usage dans Twig:
 * <div data-controller="service-project-form"
 *      data-service-project-form-client-value="{{ project.client }}"
 *      data-service-project-form-project-value="{{ project.project }}"
 *      data-service-project-form-is-edit-value="{{ isEdit ? 'true' : 'false' }}">
 *   <!-- Formulaire -->
 * </div>
 */
export default class extends Controller {
    static values = {
        client: String,
        project: String,
        isEdit: String
    };

    /**
     * Initialise le contrôleur
     */
    connect() {
        this.selectManager = new SelectManager();
        this.apiClient = new ApiClient();
        this.extensionsManager = null;
        this.dataStoragesContainer = document.querySelector('.data-storages-checkboxes');

        // Initialiser le normalisateur pour folderName
        if (window.FieldNormalizer) {
            this.initializeFieldNormalizer();
        } else {
            setTimeout(() => this.initializeFieldNormalizer(), 100);
        }

        // Récupérer les éléments du formulaire
        this.folderNameInput = document.querySelector('.folder-name-input');
        this.urlServiceInput = document.querySelector('.url-service-input');

        // Initialiser le gestionnaire d'extensions
        if (this.selectManager.elements.extensions) {
            this.extensionsManager = new ExtensionsManager(this.selectManager.elements.extensions);
        }

        // Vérifier si nous sommes en mode édition
        const isEditMode = this.isEditValue === 'true';

        if (isEditMode) {
            this.setupEventListeners();

            // En mode édition, initialiser les extensions si un langage est déjà sélectionné
            const currentLanguage = this.selectManager.elements.language?.value;
            if (currentLanguage && this.extensionsManager) {
                this.initializeExtensionsForEditMode(currentLanguage);
            }
            this.setupUrlServiceCalculation();

            // En mode édition, DÉSACTIVÉ temporairement pour tester si Symfony pré-remplit bien
            this.loadWebServers();
            this.loadDataStorages();
        } else {
            // En mode création
            this.setupEventListeners();
            this.selectManager.disableDependentFields();
            this.setupUrlServiceCalculation();

            // Initialiser les champs selon la sélection actuelle
            const currentLanguage = this.selectManager.elements.language?.value;
            if (currentLanguage) {
                this.handleLanguageChange(false);
            }

            // Charger les webservers disponibles pour le projet
            this.loadWebServers();
            this.loadDataStorages();
        }
    }

    /**
     * Nettoie les ressources lors de la déconnexion
     */
    disconnect() {
        if (this.folderNameNormalizer) {
            this.folderNameNormalizer.destroy();
            this.folderNameNormalizer = null;
        }
    }

    /**
     * Initialise le normalisateur de champ
     * @private
     */
    initializeFieldNormalizer() {
        if (!window.FieldNormalizer) {
            return;
        }

        try {
            this.folderNameNormalizer = new window.FieldNormalizer('.folder-name-input', {
                badgeText: '✓ Nom de dossier normalisé appliqué'
            });
        } catch (error) {
            console.error('Erreur lors de l\'initialisation du normalisateur:', error);
        }
    }

    /**
     * Configure les event listeners
     * @private
     */
    setupEventListeners() {
        // Changement de langage
        if (this.selectManager.elements.language) {
            this.selectManager.elements.language.addEventListener('change', () => {
                this.handleLanguageChange(true);
            });
        }

        // Changement de framework
        if (this.selectManager.elements.framework) {
            this.selectManager.elements.framework.addEventListener('change', () => {
                this.handleFrameworkChange();
            });
        }
    }

    /**
     * Configure le calcul automatique de l'URL du service
     * @private
     */
    setupUrlServiceCalculation() {
        if (!this.folderNameInput || !this.urlServiceInput) {
            return;
        }

        // Calculer l'URL initiale si folderName a une valeur
        if (this.folderNameInput.value) {
            this.calculateUrlService();
        }

        // Écouter les changements du champ folderName
        this.folderNameInput.addEventListener('input', () => {
            this.calculateUrlService();
        });
    }

    /**
     * Calcule et met à jour l'URL du service
     * @private
     */
    calculateUrlService() {
        if (!this.folderNameInput || !this.urlServiceInput || !this.clientValue || !this.projectValue) {
            return;
        }

        const folderName = this.folderNameInput.value.trim();

        if (!folderName) {
            this.urlServiceInput.value = '';
            return;
        }

        // Calculer l'URL au format: client.project.folderName.docker.localhost
        this.urlServiceInput.value = `${this.clientValue}-${this.projectValue}-${folderName}.docker.localhost`;
    }

    /**
     * Gère le changement de langage
     * @private
     */
    async handleLanguageChange() {
        const language = this.selectManager.elements.language?.value;

        // Réinitialiser tous les champs dépendants
        this.resetDependentFields();

        if (!language) {
            return;
        }

        try {
            // Charger les données en parallèle
            const [frameworkData, versionData, extensionsData] = await Promise.all([
                this.apiClient.getFrameworks(language),
                this.apiClient.getServiceVersions(language),
                this.apiClient.getExtensions(language)
            ]);

            // Traiter les frameworks
            this.populateFrameworks(frameworkData);

            // Traiter les versions de service
            this.populateServiceVersions(versionData);

            // Traiter les extensions
            this.populateExtensions(extensionsData);

        } catch (error) {
            this.showError('Erreur lors du chargement des données');
        }
    }

    /**
     * Gère le changement de framework
     * @private
     */
    async handleFrameworkChange() {
        const language = this.selectManager.elements.language?.value;
        const framework = this.selectManager.elements.framework?.value;

        // Réinitialiser les versions du framework
        this.selectManager.resetSelect(
            this.selectManager.elements.versionFramework,
            'Choisir une version de framework'
        );
        this.selectManager.elements.versionFramework.disabled = true;

        if (!language || !framework) {
            return;
        }

        try {
            // Récupérer la valeur de l'énumération
            const selectedOption = this.selectManager.elements.framework.options[
                this.selectManager.elements.framework.selectedIndex
                ];
            const frameworkValue = selectedOption.getAttribute('data-enum-value') || framework;

            const versionData = await this.apiClient.getFrameworkVersions(language, frameworkValue);
            this.populateFrameworkVersions(versionData);

        } catch (error) {
            this.showError('Erreur lors du chargement des versions du framework');
        }
    }

    /**
     * Réinitialise tous les champs dépendants
     * @private
     */
    resetDependentFields() {
        this.selectManager.resetSelect(this.selectManager.elements.framework, 'Choisir un framework');
        this.selectManager.resetSelect(this.selectManager.elements.versionService, 'Choisir une version de service');
        this.selectManager.resetSelect(this.selectManager.elements.versionFramework, 'Choisir une version de framework');
        this.selectManager.resetSelect(this.selectManager.elements.webServer, 'Choisir un webserver');

        this.selectManager.disableDependentFields();

        // Réinitialiser les extensions
        if (this.extensionsManager) {
            this.extensionsManager.reset();
        }

        // Recharger les webservers après réinitialisation
        this.loadWebServers();
        this.loadDataStorages();
    }

    /**
     * Remplit le select des frameworks
     * @private
     */
    populateFrameworks(frameworkData) {
        if (frameworkData.frameworks && frameworkData.frameworks.length > 0) {
            this.selectManager.resetSelect(this.selectManager.elements.framework, 'Choisir un framework');

            // Stocker la classe d'énumération
            if (frameworkData.frameworks[0].enum_class) {
                this.selectManager.setEnumClass(
                    this.selectManager.elements.framework,
                    frameworkData.frameworks[0].enum_class
                );
            }

            // Ajouter les options
            frameworkData.frameworks.forEach(framework => {
                this.selectManager.addEnumOptionToSelect(
                    this.selectManager.elements.framework,
                    framework.value,
                    framework.label
                );
            });

            this.selectManager.enableField('framework');
        }
    }

    /**
     * Remplit le select des versions de service
     * @private
     */
    populateServiceVersions(versionData) {
        if (versionData.versions && versionData.versions.length > 0) {
            this.selectManager.resetSelect(this.selectManager.elements.versionService, 'Choisir une version de service');

            // Stocker la classe d'énumération
            if (versionData.versions[0].enum_class) {
                this.selectManager.setEnumClass(
                    this.selectManager.elements.versionService,
                    versionData.versions[0].enum_class
                );
            }

            // Ajouter les options
            versionData.versions.forEach(version => {
                this.selectManager.addEnumOptionToSelect(
                    this.selectManager.elements.versionService,
                    version.value,
                    version.label
                );
            });

            this.selectManager.enableField('versionService');
        }
    }

    /**
     * Remplit le select des versions de framework
     * @private
     */
    populateFrameworkVersions(versionData) {
        if (versionData.versions && versionData.versions.length > 0) {
            this.selectManager.resetSelect(this.selectManager.elements.versionFramework, 'Choisir une version de framework');

            // Stocker la classe d'énumération
            if (versionData.versions[0].enum_class) {
                this.selectManager.setEnumClass(
                    this.selectManager.elements.versionFramework,
                    versionData.versions[0].enum_class
                );
            }

            // Ajouter les options
            versionData.versions.forEach(version => {
                this.selectManager.addEnumOptionToSelect(
                    this.selectManager.elements.versionFramework,
                    version.value,
                    version.label
                );
            });

            this.selectManager.enableField('versionFramework');
        }
    }

    /**
     * Initialise les extensions
     * @private
     */
    populateExtensions(extensionsData) {
        if (this.extensionsManager && extensionsData.extensions && extensionsData.extensions.length > 0) {
            if (extensionsData.extensions[0].enum_class) {
                this.selectManager.setEnumClass(
                    this.selectManager.elements.extensions,
                    extensionsData.extensions[0].enum_class
                );
            }

            this.extensionsManager.initialize(extensionsData.extensions);
        }
    }

    /**
     * Initialise les extensions en mode édition
     * @private
     */
    async initializeExtensionsForEditMode(language) {
        try {
            const extensionsData = await this.apiClient.getExtensions(language);
            this.populateExtensions(extensionsData);
        } catch (error) {
            this.showError('Erreur lors du chargement des extensions en mode édition');
        }
    }

    /**
     * Charge les webservers disponibles pour le projet
     * @private
     */
    async loadWebServers() {
        if (!this.clientValue || !this.projectValue) {
            return;
        }

        try {
            const webServersData = await this.apiClient.getWebServers(this.clientValue, this.projectValue);
            this.populateWebServers(webServersData);
        } catch (error) {
            this.showError('Erreur lors du chargement des webservers disponibles');
        }
    }

    /**
     * Remplit le select des webservers
     * @private
     */
    populateWebServers(webServersData) {
        if (!this.selectManager.elements.webServer) {
            return;
        }

        // Stocker la valeur actuelle avant de réinitialiser
        const currentValue = this.selectManager.elements.webServer.value;
        if (webServersData.webservers && webServersData.webservers.length > 0) {
            this.selectManager.resetSelect(this.selectManager.elements.webServer, 'Choisir un webserver');

            // Stocker la classe d'énumération
            if (webServersData.webservers[0].enum_class) {
                this.selectManager.setEnumClass(
                    this.selectManager.elements.webServer,
                    webServersData.webservers[0].enum_class
                );
            }

            // Ajouter les options
            webServersData.webservers.forEach(webServer => {
                this.selectManager.addEnumOptionToSelect(
                    this.selectManager.elements.webServer,
                    webServer.value,
                    webServer.label
                );
            });

            // Restaurer la valeur précédente si elle existe dans les nouvelles options
            if (currentValue && webServersData.webservers.some(ws => ws.value === currentValue)) {
                this.selectManager.elements.webServer.value = currentValue;
            }

            this.selectManager.enableField('webServer');
        }
    }

    /**
     * Charge les storages disponibles pour le projet
     * @private
     */
    async loadDataStorages() {
        if (!this.clientValue || !this.projectValue || !this.dataStoragesContainer) {
            return;
        }

        try {
            const storagesData = await this.apiClient.getDataStorages(this.clientValue, this.projectValue);
            this.populateDataStorages(storagesData);
        } catch (error) {
            this.showError('Erreur lors du chargement des storages disponibles');
        }
    }

    /**
     * Remplit les checkboxes des storages
     * @private
     */
    populateDataStorages(storagesData) {
        if (!this.dataStoragesContainer || !storagesData.storages) {
            return;
        }

        // Sauvegarder les valeurs actuellement cochées
        const currentValues = [];
        const currentCheckboxes = this.dataStoragesContainer.querySelectorAll('input[type="checkbox"]:checked');
        currentCheckboxes.forEach(checkbox => {
            currentValues.push(checkbox.value);
        });

        // Vider le conteneur
        this.dataStoragesContainer.innerHTML = '';

        if (storagesData.storages.length === 0) {
            const noStorageMessage = document.createElement('p');
            noStorageMessage.className = 'text-gray-500 text-sm';
            noStorageMessage.textContent = 'Aucun storage disponible dans ce projet';
            this.dataStoragesContainer.appendChild(noStorageMessage);
            return;
        }

        // Créer les checkboxes pour chaque storage disponible
        storagesData.storages.forEach((storage, index) => {
            const wrapper = document.createElement('div');
            wrapper.className = 'form-check';

            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.className = 'form-check-input';
            checkbox.id = `service_project_dataStorages_${index}`;
            checkbox.name = 'service_project[dataStorages][]';
            checkbox.value = storage.value;

            // Restaurer l'état coché si la valeur était précédemment sélectionnée
            if (currentValues.includes(storage.value)) {
                checkbox.checked = true;
            }

            const label = document.createElement('label');
            label.className = 'form-check-label';
            label.htmlFor = checkbox.id;
            label.textContent = storage.label;

            wrapper.appendChild(checkbox);
            wrapper.appendChild(label);
            this.dataStoragesContainer.appendChild(wrapper);
        });
    }

    /**
     * Affiche un message d'erreur
     * @private
     */
    showError(message) {
        const alert = document.createElement('div');
        alert.className = 'alert alert-danger position-fixed top-0 end-0 m-3';
        alert.style.zIndex = '9999';
        alert.textContent = message;

        document.body.appendChild(alert);

        setTimeout(() => {
            alert.remove();
        }, 5000);
    }
}