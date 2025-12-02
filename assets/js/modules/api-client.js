/**
 * Client API pour les appels vers le serveur
 */
export class ApiClient {
    constructor() {
        this.baseUrl = '';
    }

    /**
     * Effectue une requête GET
     */
    async get(url) {
        try {
            const response = await fetch(url);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error('Erreur lors de la requête:', error);
            throw error;
        }
    }

    /**
     * Récupère les frameworks pour un langage donné
     */
    async getFrameworks(language) {
        return this.get(`/api/frameworks/${language}`);
    }

    /**
     * Récupère les versions d'un service pour un langage donné
     */
    async getServiceVersions(language) {
        return this.get(`/api/versions/${language}`);
    }

    /**
     * Récupère les versions d'un framework pour un langage et un framework donnés
     */
    async getFrameworkVersions(language, framework) {
        const frameworkValue = framework.toLowerCase();
        return this.get(`/api/versions/${language}/${frameworkValue}`);
    }

    /**
     * Récupère les extensions disponibles pour un langage donné
     */
    async getExtensions(language) {
        return this.get(`/api/extensions/${language}`);
    }

    /**
     * Récupère les webservers disponibles pour un projet donné
     */
    async getWebServers(client, project) {
        return this.get(`/api/webservers?client=${client}&project=${project}`);
    }

    /**
     * Récupère les storages disponibles pour un projet donné
     */
    async getDataStorages(client, project) {
        return this.get(`/api/data-storages?client=${client}&project=${project}`);
    }
}