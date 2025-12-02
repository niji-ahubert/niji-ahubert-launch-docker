import {ApiClient} from './api-client.js';

/**
 * Gestionnaire pour les éléments select du formulaire
 */
export class SelectManager {
    constructor() {
        this.selectors = {
            language: '#service_project_language',
            framework: '#service_project_framework',
            versionService: '#service_project_versionService',
            versionFramework: '#service_project_versionFramework',
            extensions: '#service_project_extensionsRequired',
            webServer: '#service_project_webServer'
        };

        this.elements = this.initializeElements();
    }

    /**
     * Initialise tous les éléments select
     */
    initializeElements() {
        const elements = {};

        Object.entries(this.selectors).forEach(([key, selector]) => {
            elements[key] = document.querySelector(selector);
        });

        return elements;
    }

    /**
     * Réinitialise un select avec seulement l'option placeholder
     */
    resetSelect(selectElement, placeholderText) {
        if (!selectElement) return;

        selectElement.innerHTML = '';
        const placeholderOption = document.createElement('option');
        placeholderOption.value = '';
        placeholderOption.textContent = placeholderText;
        placeholderOption.selected = true;
        selectElement.appendChild(placeholderOption);
    }

    /**
     * Ajoute une option d'énumération à un select
     */
    addEnumOptionToSelect(select, value, label) {
        if (!select) return;

        const option = document.createElement('option');
        option.value = value;
        option.textContent = label;
        option.setAttribute('data-enum-value', value);
        select.appendChild(option);
    }

    /**
     * Désactive les champs dépendants
     */
    disableDependentFields() {
        const fieldsToDisable = ['versionService', 'framework', 'versionFramework', 'webServer'];

        fieldsToDisable.forEach(field => {
            if (this.elements[field]) {
                this.elements[field].disabled = true;
            }
        });
    }

    /**
     * Active un champ
     */
    enableField(fieldName) {
        if (this.elements[fieldName]) {
            this.elements[fieldName].disabled = false;
        }
    }

    /**
     * Stocke la classe d'énumération sur un élément
     */
    setEnumClass(element, enumClass) {
        if (element && enumClass) {
            element.setAttribute('data-enum-class', enumClass);
        }
    }
}

export default SelectManager;
