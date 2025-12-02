import {Controller} from '@hotwired/stimulus';

/**
 * Stimulus controller pour le formulaire de service externe.
 *
 * Gère le chargement dynamique des versions selon le service sélectionné.
 *
 * Usage dans Twig:
 * <div data-controller="service-external-form"
 *      data-service-external-form-is-edit-value="{{ isEdit ? 'true' : 'false' }}">
 *   <!-- Formulaire -->
 * </div>
 */
export default class extends Controller {
    static values = {
        isEdit: String
    };

    static targets = ['serviceName', 'version'];

    /**
     * Initialise le contrôleur
     */
    connect() {
        // Le champ version est disabled par défaut jusqu'à ce qu'on sélectionne un service
        if (this.hasVersionTarget) {
            this.versionTarget.disabled = true;
        }

        // Event listener pour le changement de service
        if (this.hasServiceNameTarget && !this.serviceNameTarget.disabled) {
            this.serviceNameTarget.addEventListener('change', (e) => {
                this.handleServiceChange(e.target.value);
            });
        }

        // Si un service est déjà sélectionné, charger ses versions
        if (this.hasServiceNameTarget && this.serviceNameTarget.value) {
            this.handleServiceChange(this.serviceNameTarget.value);
        }
    }

    /**
     * Gère le changement de service
     * @private
     */
    async handleServiceChange(serviceName) {
        if (!serviceName || !this.hasVersionTarget) return;

        // Sauvegarder la valeur actuelle du champ version avant de le mettre à jour
        const currentVersionValue = this.versionTarget.value;

        // Désactiver le champ version pendant l'appel AJAX
        this.versionTarget.disabled = true;

        try {
            // Appel API pour récupérer les versions du service
            const response = await fetch(`/project/external-service/versions?service=${encodeURIComponent(serviceName)}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            // Mettre à jour le select des versions
            if (data.versions) {
                this.updateVersionSelect(data.versions, currentVersionValue);
            }

        } catch (error) {
            console.error('Erreur lors du chargement des versions:', error);
            // En cas d'erreur, on réactive quand même le champ version s'il avait une valeur
            if (currentVersionValue) {
                this.versionTarget.disabled = false;
            }
        }
    }

    /**
     * Met à jour le select des versions
     * @private
     */
    updateVersionSelect(versions, currentVersionValue) {
        // Vider le select
        this.versionTarget.innerHTML = '';

        // Ajouter l'option par défaut
        const defaultOption = document.createElement('option');
        defaultOption.value = '';
        defaultOption.textContent = 'Choisir une version';
        this.versionTarget.appendChild(defaultOption);

        // Ajouter les versions disponibles
        versions.forEach(version => {
            const option = document.createElement('option');
            option.value = version;
            option.textContent = version;

            // Restaurer la sélection si c'était la valeur précédente
            if (version === currentVersionValue) {
                option.selected = true;
            }

            this.versionTarget.appendChild(option);
        });

        // Le champ version est toujours actif quand on a des versions
        this.versionTarget.disabled = false;
    }
}