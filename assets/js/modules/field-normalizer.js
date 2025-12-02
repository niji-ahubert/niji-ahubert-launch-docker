/**
 * Module réutilisable pour la normalisation automatique des champs de formulaire
 */
export class FieldNormalizer {
    constructor(selector, options = {}) {
        this.input = document.querySelector(selector);
        this.debounceTimeout = null;
        this.options = {
            debounceDelay: 300,
            badgeClass: 'text-sm text-green-600 dark:text-green-400',
            badgeText: '✓ Nom normalisé appliqué',
            minLength: 2,
            ...options
        };
        
        this.init();
    }

    /**
     * Initialise la normalisation
     */
    init() {
        if (!this.input) {
            return;
        }

        // Normalisation en temps réel
        this.input.addEventListener('input', (e) => {
            this.debounceNormalization(e.target.value);
        });
    }

    /**
     * Débounce la normalisation
     * @param {string} value 
     */
    debounceNormalization(value) {
        clearTimeout(this.debounceTimeout);
        
        this.debounceTimeout = setTimeout(() => {
            this.normalize(value);
        }, this.options.debounceDelay);
    }

    /**
     * Normalise la valeur et met à jour le champ
     * @param {string} value 
     */
    normalize(value) {
        if (!value || value.length < this.options.minLength) {
            this.hideBadge();
            return;
        }

        const normalizedValue = this.slugify(value);
        this.updateField(normalizedValue);
    }

    /**
     * Met à jour le champ et affiche le badge
     * @param {string} normalizedValue 
     */
    updateField(normalizedValue) {
        // Mettre à jour la valeur du champ
        if (this.input.value !== normalizedValue) {
            this.input.value = normalizedValue;
        }
        
        this.showBadge();
    }

    /**
     * Affiche le badge de confirmation
     */
    showBadge() {
        const badgeId = `normalized-badge-${this.input.id || 'field'}`;
        let badge = document.querySelector(`#${badgeId}`);
        
        if (!badge) {
            badge = document.createElement('small');
            badge.id = badgeId;
            badge.className = this.options.badgeClass;
            this.input.parentNode.appendChild(badge);
        }

        badge.textContent = this.options.badgeText;
        badge.style.display = 'inline';
    }

    /**
     * Masque le badge
     */
    hideBadge() {
        const badgeId = `normalized-badge-${this.input.id || 'field'}`;
        const badge = document.querySelector(`#${badgeId}`);
        if (badge) {
            badge.style.display = 'none';
        }
    }

    /**
     * Convertit une chaîne en "slug"
     * @param {string} text 
     * @returns {string}
     */
    slugify(text) {
        return text
            .toString()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .trim()
            .replace(/\s+/g, '-')
            .replace(/[^\w-]+/g, '')
            .replace(/--+/g, '-');
    }

    /**
     * Nettoie les ressources
     */
    destroy() {
        if (this.debounceTimeout) {
            clearTimeout(this.debounceTimeout);
        }
    }
}
