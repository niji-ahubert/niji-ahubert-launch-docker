import {Controller} from '@hotwired/stimulus';

/**
 * Stimulus controller pour le formulaire d'ajout/édition de projet.
 *
 * Utilise le service global FieldNormalizer pour la normalisation automatique du nom de projet.
 *
 * Usage dans Twig:
 * <div data-controller="project-form"
 *      data-project-form-field-selector-value="#project_project"
 *      data-project-form-badge-text-value="✓ Nom de projet normalisé appliqué">
 *   <!-- Formulaire -->
 * </div>
 */
export default class extends Controller {
    static values = {
        fieldSelector: String,
        badgeText: String,
        debounceDelay: Number,
        minLength: Number
    };

    /**
     * Valeurs par défaut pour les paramètres
     */
    static get defaultValues() {
        return {
            fieldSelector: '#project_project',
            badgeText: '✓ Nom de projet normalisé appliqué',
            debounceDelay: 300,
            minLength: 2
        };
    }

    /**
     * Initialise le contrôleur
     */
    connect() {
        // Attendre que le service global soit disponible
        if (window.FieldNormalizer) {
            this.initializeNormalizer();
        } else {
            // Retry après un court délai si le service n'est pas encore chargé
            setTimeout(() => this.initializeNormalizer(), 100);
        }
    }

    /**
     * Nettoie les ressources lors de la déconnexion
     */
    disconnect() {
        if (this.normalizer) {
            this.normalizer.destroy();
            this.normalizer = null;
        }
    }

    /**
     * Initialise le normalisateur de champ
     * @private
     */
    initializeNormalizer() {
        if (!window.FieldNormalizer) {
            console.error('FieldNormalizer service not available');
            return;
        }

        const options = {
            badgeText: this.badgeTextValue,
            debounceDelay: this.debounceDelayValue,
            minLength: this.minLengthValue
        };

        try {
            this.normalizer = new window.FieldNormalizer(this.fieldSelectorValue, options);
        } catch (error) {
            console.error('Erreur lors de l\'initialisation du normalisateur:', error);
        }
    }

    /**
     * Méthode appelée quand les valeurs changent
     */
    fieldSelectorValueChanged() {
        // Réinitialiser le normalisateur si le sélecteur change
        if (this.normalizer) {
            this.normalizer.destroy();
        }
        this.initializeNormalizer();
    }

    /**
     * Méthode publique pour déclencher manuellement la normalisation
     * Peut être appelée depuis d'autres contrôleurs ou scripts
     */
    normalizeField() {
        if (this.normalizer && this.normalizer.input) {
            this.normalizer.normalize(this.normalizer.input.value);
        }
    }

    /**
     * Méthode publique pour réinitialiser le champ
     */
    resetField() {
        if (this.normalizer && this.normalizer.input) {
            this.normalizer.input.value = '';
            this.normalizer.hideBadge();
        }
    }
}
