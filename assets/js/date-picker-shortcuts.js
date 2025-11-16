/**
 * Date Picker Shortcuts
 * 
 * Ajoute des boutons raccourcis pour faciliter la sélection de dates
 * dans les formulaires (Aujourd'hui, Hier, Début mois, etc.)
 * 
 * @version 1.0.0
 * @author MonBudget
 */

(function() {
    'use strict';

    /**
     * Formate une date au format YYYY-MM-DD pour les inputs type="date"
     * @param {Date} date - Date à formater
     * @returns {string} Date formatée
     */
    function formatDateForInput(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    /**
     * Obtient la date selon le raccourci demandé
     * @param {string} shortcut - Type de raccourci
     * @returns {string} Date formatée YYYY-MM-DD
     */
    function getDateFromShortcut(shortcut) {
        const today = new Date();
        let targetDate = new Date();

        switch (shortcut) {
            case 'today':
                targetDate = today;
                break;

            case 'yesterday':
                targetDate.setDate(today.getDate() - 1);
                break;

            case 'week-ago':
                targetDate.setDate(today.getDate() - 7);
                break;

            case 'month-ago':
                targetDate.setDate(today.getDate() - 30);
                break;

            case 'month-start':
                targetDate = new Date(today.getFullYear(), today.getMonth(), 1);
                break;

            case 'month-end':
                targetDate = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                break;

            case 'year-start':
                targetDate = new Date(today.getFullYear(), 0, 1);
                break;

            case 'year-end':
                targetDate = new Date(today.getFullYear(), 11, 31);
                break;

            default:
                targetDate = today;
        }

        return formatDateForInput(targetDate);
    }

    /**
     * Ajoute les raccourcis de date à un champ input[type="date"]
     * @param {HTMLElement} dateInput - Input de type date
     * @param {Object} options - Options de configuration
     */
    function addDateShortcuts(dateInput, options = {}) {
        // Configuration par défaut
        const config = {
            shortcuts: ['today', 'yesterday', 'week-ago', 'month-start', 'month-end'],
            buttonClass: 'btn btn-outline-secondary btn-sm',
            containerClass: 'btn-group btn-group-sm mt-1',
            labels: {
                today: "Aujourd'hui",
                yesterday: 'Hier',
                'week-ago': 'Il y a 7j',
                'month-ago': 'Il y a 30j',
                'month-start': 'Début mois',
                'month-end': 'Fin mois',
                'year-start': 'Début année',
                'year-end': 'Fin année'
            },
            ...options
        };

        // Vérifier si les raccourcis n'ont pas déjà été ajoutés
        if (dateInput.dataset.shortcutsAdded === 'true') {
            return;
        }

        // Créer le conteneur des boutons
        const buttonGroup = document.createElement('div');
        buttonGroup.className = config.containerClass;
        buttonGroup.setAttribute('role', 'group');
        buttonGroup.setAttribute('aria-label', 'Raccourcis de date');

        // Créer les boutons de raccourci
        config.shortcuts.forEach(shortcut => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = config.buttonClass;
            button.dataset.shortcut = shortcut;
            button.textContent = config.labels[shortcut] || shortcut;
            button.title = `Sélectionner ${config.labels[shortcut]}`;

            // Gestionnaire de clic
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const dateValue = getDateFromShortcut(shortcut);
                dateInput.value = dateValue;
                
                // Déclencher l'événement 'change' pour les écouteurs
                const changeEvent = new Event('change', { bubbles: true });
                dateInput.dispatchEvent(changeEvent);

                // Feedback visuel
                button.classList.add('active');
                setTimeout(() => button.classList.remove('active'), 200);
            });

            buttonGroup.appendChild(button);
        });

        // Insérer les boutons après l'input
        dateInput.parentNode.insertBefore(buttonGroup, dateInput.nextSibling);

        // Marquer comme initialisé
        dateInput.dataset.shortcutsAdded = 'true';
    }

    /**
     * Initialise les raccourcis pour tous les champs date avec attribut data-shortcuts
     */
    function initDateShortcuts() {
        // Sélectionner tous les inputs date avec data-shortcuts
        const dateInputs = document.querySelectorAll('input[type="date"][data-shortcuts]');

        dateInputs.forEach(input => {
            // Récupérer les options depuis data-attributes
            const shortcuts = input.dataset.shortcuts ? input.dataset.shortcuts.split(',') : undefined;
            const options = {};

            if (shortcuts) {
                options.shortcuts = shortcuts;
            }

            addDateShortcuts(input, options);
        });
    }

    // Initialiser au chargement du DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDateShortcuts);
    } else {
        initDateShortcuts();
    }

    // Exposer les fonctions publiques
    window.DatePickerShortcuts = {
        init: initDateShortcuts,
        add: addDateShortcuts,
        getDate: getDateFromShortcut
    };

})();
