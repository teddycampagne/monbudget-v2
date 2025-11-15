/**
 * MonBudget v2.0 - JavaScript principal
 * 
 * Gestion des interactions utilisateur et utilitaires généraux
 * @version 2.0.0
 */

/**
 * Initialisation au chargement de la page
 * - Auto-hide des alertes après 5 secondes
 * - Confirmation pour les actions de suppression
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('MonBudget v2.0 chargé');
    
    // Auto-hide alerts après 5 secondes
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
    
    // Confirmation pour les actions de suppression
    const deleteButtons = document.querySelectorAll('[data-confirm-delete]');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Êtes-vous sûr de vouloir supprimer cet élément ?')) {
                e.preventDefault();
            }
        });
    });
});

/**
 * Namespace principal pour les utilitaires MonBudget
 * @namespace MonBudget
 */
const MonBudget = {
    /**
     * Formate un nombre en devise française (EUR)
     * @param {number} amount - Montant à formater
     * @returns {string} Montant formaté (ex: "1 234,56 €")
     * @example
     * MonBudget.formatCurrency(1234.56); // "1 234,56 €"
     */
    formatCurrency: function(amount) {
        return new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency: 'EUR'
        }).format(amount);
    },
    
    /**
     * Formate une date au format français (jj/mm/aaaa)
     * @param {string|Date} date - Date à formater
     * @returns {string} Date formatée (ex: "25/12/2024")
     * @example
     * MonBudget.formatDate('2024-12-25'); // "25/12/2024"
     */
    formatDate: function(date) {
        return new Intl.DateFormat('fr-FR').format(new Date(date));
    },
    
    /**
     * Affiche un message toast temporaire (3 secondes)
     * @param {string} message - Message à afficher
     * @param {string} [type='info'] - Type d'alerte (info, success, warning, danger)
     * @example
     * MonBudget.toast('Opération réussie', 'success');
     */
    toast: function(message, type = 'info') {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3`;
        alertDiv.style.zIndex = '9999';
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(alertDiv);
        
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alertDiv);
            bsAlert.close();
        }, 3000);
    },
    
    /**
     * Requête AJAX simplifiée utilisant Fetch API
     * @param {string} url - URL de la requête
     * @param {Object} [options={}] - Options de la requête
     * @param {string} [options.method='GET'] - Méthode HTTP
     * @param {Object} [options.headers] - En-têtes supplémentaires
     * @param {Object} [options.body] - Corps de la requête (sera converti en JSON)
     * @returns {Promise<Object>} Réponse JSON parsée
     * @throws {Error} Si la requête échoue
     * @example
     * const data = await MonBudget.ajax('/api/comptes', {
     *     method: 'POST',
     *     body: { nom: 'Mon compte' }
     * });
     */
    ajax: async function(url, options = {}) {
        try {
            const response = await fetch(url, {
                method: options.method || 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    ...options.headers
                },
                body: options.body ? JSON.stringify(options.body) : null
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            return await response.json();
        } catch (error) {
            console.error('AJAX Error:', error);
            throw error;
        }
    }
};

// Exposer globalement
window.MonBudget = MonBudget;
