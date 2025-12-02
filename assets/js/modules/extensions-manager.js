/**
 * Gestionnaire moderne pour les extensions (remplace Select2)
 */
export class ExtensionsManager {
    constructor(extensionsElement) {
        this.extensionsElement = extensionsElement;
        this.selectedExtensions = new Set();
        this.availableExtensions = [];
        this.isInitialized = false;
    }

    /**
     * Initialise le gestionnaire d'extensions
     */
    initialize(extensions = []) {
        this.availableExtensions = extensions;
        this.createMultiSelectInterface();
        this.isInitialized = true;
    }

    /**
     * Crée une interface multi-select moderne
     */
    createMultiSelectInterface() {
        if (!this.extensionsElement) return;

        // Container principal
        const container = document.createElement('div');
        container.className = 'extensions-multi-select relative';

        // Input pour la recherche et l'affichage des sélections
        const input = document.createElement('input');
        input.type = 'text';
        input.className = 'form-control extensions-input';
        input.placeholder = 'Rechercher et sélectionner des extensions...';

        // Container pour les tags sélectionnés
        const tagsContainer = document.createElement('div');
        tagsContainer.className = 'extensions-tags mt-2';

        // Dropdown pour les options
        const dropdown = document.createElement('div');
        dropdown.className = 'extensions-dropdown absolute z-10 w-full bg-white border border-gray-300 rounded-md shadow-lg hidden max-h-60 overflow-y-auto';

        // Assemblage
        container.appendChild(input);
        container.appendChild(tagsContainer);
        container.appendChild(dropdown);

        // Remplacer l'élément original
        this.extensionsElement.style.display = 'none';
        this.extensionsElement.parentNode.insertBefore(container, this.extensionsElement.nextSibling);

        this.setupEventListeners(input, dropdown, tagsContainer);
        this.updateDropdown(dropdown);
        
        // Charger les valeurs pré-sélectionnées depuis le select original
        this.loadPreSelectedValues(tagsContainer);
    }

    /**
     * Configure les event listeners
     */
    setupEventListeners(input, dropdown, tagsContainer) {
        // Focus sur l'input
        input.addEventListener('focus', () => {
            dropdown.classList.remove('hidden');
            this.updateDropdown(dropdown);
        });

        // Recherche en temps réel
        input.addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase();
            this.updateDropdown(dropdown, searchTerm);
        });

        // Fermeture du dropdown
        document.addEventListener('click', (e) => {
            if (!input.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.add('hidden');
            }
        });

        // Gestion des touches
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                const value = input.value.trim();
                if (value) {
                    this.addExtension(value, value, tagsContainer);
                    input.value = '';
                    this.updateDropdown(dropdown);
                }
            }
        });
    }

    /**
     * Met à jour le dropdown avec les options filtrées
     */
    updateDropdown(dropdown, searchTerm = '') {
        dropdown.innerHTML = '';

        const filteredExtensions = this.availableExtensions.filter(ext =>
            ext.label.toLowerCase().includes(searchTerm) &&
            !this.selectedExtensions.has(ext.value)
        );

        filteredExtensions.forEach(ext => {
            const option = document.createElement('div');
            option.className = 'px-3 py-2 hover:bg-gray-100 cursor-pointer';
            option.textContent = ext.label;
            option.addEventListener('click', () => {
                this.addExtension(ext.value, ext.label);
                dropdown.classList.add('hidden');
            });
            dropdown.appendChild(option);
        });

        if (filteredExtensions.length === 0) {
            const noResults = document.createElement('div');
            noResults.className = 'px-3 py-2 text-gray-500 italic';
            noResults.textContent = 'Aucune extension trouvée';
            dropdown.appendChild(noResults);
        }
    }

    /**
     * Ajoute une extension à la sélection
     */
    addExtension(value, label, tagsContainer = null) {
        if (this.selectedExtensions.has(value)) return;

        this.selectedExtensions.add(value);

        if (!tagsContainer) {
            tagsContainer = document.querySelector('.extensions-tags');
        }

        const tag = document.createElement('span');
        tag.className = 'inline-flex items-center px-2 py-1 text-sm bg-blue-100 text-blue-800 rounded mr-2 mb-2';
        tag.innerHTML = `
            ${label}
            <button type="button" class="ml-1 text-blue-600 hover:text-blue-800">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
            </button>
        `;

        tag.querySelector('button').addEventListener('click', () => {
            this.removeExtension(value, tag);
        });

        tagsContainer.appendChild(tag);
        this.updateHiddenInput();
    }

    /**
     * Supprime une extension de la sélection
     */
    removeExtension(value, tagElement) {
        this.selectedExtensions.delete(value);
        tagElement.remove();
        this.updateHiddenInput();
    }

    /**
     * Met à jour l'input caché pour Symfony
     */
    updateHiddenInput() {
        if (!this.extensionsElement) return;

        // Créer les options dans le select original pour Symfony
        this.extensionsElement.innerHTML = '';

        this.selectedExtensions.forEach(value => {
            const option = document.createElement('option');
            option.value = value;
            option.selected = true;
            this.extensionsElement.appendChild(option);
        });
    }

    /**
     * Réinitialise les extensions
     */
    reset() {
        this.selectedExtensions.clear();
        const tagsContainer = document.querySelector('.extensions-tags');
        if (tagsContainer) {
            tagsContainer.innerHTML = '';
        }
        this.updateHiddenInput();
    }

    /**
     * Charge les valeurs pré-sélectionnées depuis le select original
     */
    loadPreSelectedValues(tagsContainer) {
        // Méthode 1: Chercher les options avec l'attribut selected
        const selectedOptions = this.extensionsElement.querySelectorAll('option[selected]');
        
        selectedOptions.forEach(option => {
            const value = option.value;
            const label = option.textContent.trim();
            this.addExtension(value, label, tagsContainer);
        });
        
        // Méthode 2: Chercher les options qui ont la propriété selected = true
        const allOptions = this.extensionsElement.querySelectorAll('option');
        allOptions.forEach(option => {
            if (option.selected && option.value && !this.selectedExtensions.has(option.value)) {
                const value = option.value;
                const label = option.textContent.trim();
                this.addExtension(value, label, tagsContainer);
            }
        });
    }
}
