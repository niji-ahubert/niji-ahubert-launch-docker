import {Controller} from '@hotwired/stimulus';

/**
 * Stimulus controller pour le formulaire d'ajout/édition de client.
 *
 * Fournit une validation en temps réel et des suggestions de noms
 * en utilisant les endpoints AJAX du contrôleur.
 *
 * Usage dans Twig:
 * <div data-controller="client-form"
 *      data-client-form-original-name-value="{{ client_name|default('') }}">
 *   <!-- Formulaire -->
 * </div>
 */
export default class extends Controller {
    static values = {
        originalName: String
    };

    static targets = ['clientInput', 'suggestions', 'availabilityMessage'];

    /**
     * Initialise le contrôleur
     */
    connect() {
        this.debounceTimeout = null;

        // Initialiser le normalisateur pour la normalisation automatique
        if (window.FieldNormalizer) {
            this.initializeFieldNormalizer();
        } else {
            setTimeout(() => this.initializeFieldNormalizer(), 100);
        }

        this.setupEventListeners();
    }

    /**
     * Nettoie les ressources lors de la déconnexion
     */
    disconnect() {
        if (this.normalizer) {
            this.normalizer.destroy();
            this.normalizer = null;
        }

        if (this.debounceTimeout) {
            clearTimeout(this.debounceTimeout);
        }
    }

    /**
     * Initialise le normalisateur de champ
     * @private
     */
    initializeFieldNormalizer() {
        if (!window.FieldNormalizer || !this.hasClientInputTarget) {
            console.error('FieldNormalizer service not available or client input not found');
            return;
        }

        try {
            this.normalizer = new window.FieldNormalizer('#client_client', {
                badgeText: '✓ Nom de client normalisé appliqué'
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
        if (!this.hasClientInputTarget) {
            return;
        }

        // Validation en temps réel
        this.clientInputTarget.addEventListener('input', (e) => {
            this.debounceValidation(e.target.value);
        });

        // Suggestions au focus
        this.clientInputTarget.addEventListener('focus', (e) => {
            if (e.target.value.length >= 2) {
                this.showSuggestions(e.target.value);
            }
        });

        // Masquer les suggestions au clic extérieur
        document.addEventListener('click', (e) => {
            if (!this.element.contains(e.target)) {
                this.hideSuggestions();
            }
        });
    }

    /**
     * Débounce la validation pour éviter trop d'appels AJAX
     * @private
     */
    debounceValidation(value) {
        clearTimeout(this.debounceTimeout);

        this.debounceTimeout = setTimeout(() => {
            this.validateClientName(value);
        }, 300);
    }

    /**
     * Valide le nom du client via AJAX
     * @private
     */
    async validateClientName(clientName) {
        if (!clientName || clientName.length < 2) {
            this.hideAvailabilityMessage();
            return;
        }

        try {
            const response = await fetch(`/client/check-availability?name=${encodeURIComponent(clientName)}`);
            const data = await response.json();

            this.showAvailabilityMessage(data.message, data.available);
        } catch (error) {
            this.showAvailabilityMessage('Erreur de validation', false);
        }
    }

    /**
     * Affiche les suggestions de noms alternatifs
     * @private
     */
    async showSuggestions(clientName) {
        if (!this.hasSuggestionsTarget) {
            return;
        }

        try {
            const response = await fetch(`/client/suggest?name=${encodeURIComponent(clientName)}`);
            const data = await response.json();

            if (data.suggestions && data.suggestions.length > 0) {
                this.renderSuggestions(data.suggestions);
            } else {
                this.hideSuggestions();
            }
        } catch (error) {
            this.hideSuggestions();
        }
    }

    /**
     * Affiche la liste des suggestions
     * @private
     */
    renderSuggestions(suggestions) {
        if (!this.hasSuggestionsTarget) {
            return;
        }

        this.suggestionsTarget.innerHTML = '';

        suggestions.forEach(suggestion => {
            const suggestionItem = document.createElement('div');
            suggestionItem.className = 'suggestion-item cursor-pointer p-2 hover:bg-gray-100 dark:hover:bg-gray-700';
            suggestionItem.textContent = suggestion;

            suggestionItem.addEventListener('click', () => {
                this.selectSuggestion(suggestion);
            });

            this.suggestionsTarget.appendChild(suggestionItem);
        });

        this.suggestionsTarget.style.display = 'block';
    }

    /**
     * Sélectionne une suggestion
     * @private
     */
    selectSuggestion(suggestion) {
        if (this.hasClientInputTarget) {
            this.clientInputTarget.value = suggestion;
            this.hideSuggestions();
            this.validateClientName(suggestion);
        }
    }

    /**
     * Masque les suggestions
     */
    hideSuggestions() {
        if (this.hasSuggestionsTarget) {
            this.suggestionsTarget.style.display = 'none';
        }
    }

    /**
     * Affiche le message de disponibilité
     * @private
     */
    showAvailabilityMessage(message, available) {
        if (!this.hasAvailabilityMessageTarget) {
            return;
        }

        this.availabilityMessageTarget.textContent = message;
        this.availabilityMessageTarget.className = available
            ? 'text-sm font-medium text-gray-900 dark:text-white'
            : 'text-sm text-red-600 dark:text-red-400';
        this.availabilityMessageTarget.style.display = 'block';
    }

    /**
     * Masque le message de disponibilité
     * @private
     */
    hideAvailabilityMessage() {
        if (this.hasAvailabilityMessageTarget) {
            this.availabilityMessageTarget.style.display = 'none';
        }
    }
}