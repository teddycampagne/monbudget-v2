/**
 * Dark Mode Charts.js Adapter
 * Auteur: Session 12
 * Date: 15 novembre 2025
 * Description: Adaptation dynamique des couleurs Charts.js selon le thème
 */

/**
 * Obtenir les couleurs adaptées au thème actuel
 */
function getChartColors() {
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    
    return {
        // Couleurs de grille et axes
        gridColor: isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)',
        textColor: isDark ? '#e9ecef' : '#212529',
        
        // Couleurs des graphiques (palette harmonieuse dark/light)
        palette: {
            primary: isDark ? '#4dabf7' : '#0d6efd',
            success: isDark ? '#51cf66' : '#198754',
            danger: isDark ? '#ff6b6b' : '#dc3545',
            warning: isDark ? '#ffd43b' : '#ffc107',
            info: isDark ? '#22b8cf' : '#0dcaf0',
            purple: isDark ? '#cc5de8' : '#6f42c1',
            pink: isDark ? '#ff6b9d' : '#d63384',
            orange: isDark ? '#ff922b' : '#fd7e14',
            teal: isDark ? '#20c997' : '#20c997',
            cyan: isDark ? '#3bc9db' : '#0dcaf0'
        }
    };
}

/**
 * Configuration globale Charts.js pour dark mode
 */
function applyChartDefaultsForTheme() {
    const colors = getChartColors();
    
    if (typeof Chart !== 'undefined') {
        Chart.defaults.color = colors.textColor;
        Chart.defaults.borderColor = colors.gridColor;
        
        // Grilles
        Chart.defaults.scale.grid.color = colors.gridColor;
        Chart.defaults.scale.ticks.color = colors.textColor;
        
        // Legends
        Chart.defaults.plugins.legend.labels.color = colors.textColor;
        
        // Tooltips - fond adapté au thème
        if (document.documentElement.getAttribute('data-theme') === 'dark') {
            Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(45, 50, 61, 0.95)';
            Chart.defaults.plugins.tooltip.titleColor = '#e9ecef';
            Chart.defaults.plugins.tooltip.bodyColor = '#e9ecef';
            Chart.defaults.plugins.tooltip.borderColor = '#3d424d';
            Chart.defaults.plugins.tooltip.borderWidth = 1;
        } else {
            Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(0, 0, 0, 0.8)';
            Chart.defaults.plugins.tooltip.titleColor = '#fff';
            Chart.defaults.plugins.tooltip.bodyColor = '#fff';
            Chart.defaults.plugins.tooltip.borderColor = 'transparent';
            Chart.defaults.plugins.tooltip.borderWidth = 0;
        }
    }
}

/**
 * Rafraîchir tous les graphiques lors du changement de thème
 */
function refreshAllCharts() {
    if (typeof Chart !== 'undefined') {
        // Réappliquer les defaults d'abord
        applyChartDefaultsForTheme();
        
        const colors = getChartColors();
        
        // Rafraîchir tous les graphiques existants
        if (Chart.instances) {
            const instances = Object.values(Chart.instances);
            
            instances.forEach(function(chart) {
                if (chart && typeof chart.update === 'function') {
                    // Mettre à jour les options du graphique individuellement
                    if (chart.options) {
                        // Couleurs des axes et grilles
                        if (chart.options.scales) {
                            Object.keys(chart.options.scales).forEach(function(scaleKey) {
                                const scale = chart.options.scales[scaleKey];
                                if (scale.grid) {
                                    scale.grid.color = colors.gridColor;
                                }
                                if (scale.ticks) {
                                    scale.ticks.color = colors.textColor;
                                }
                            });
                        }
                        
                        // Couleurs de la légende
                        if (chart.options.plugins && chart.options.plugins.legend && chart.options.plugins.legend.labels) {
                            chart.options.plugins.legend.labels.color = colors.textColor;
                        }
                    }
                    
                    // Forcer la mise à jour
                    chart.update('none');
                }
            });
        }
    }
}

/**
 * Obtenir une palette de couleurs pour graphiques multi-datasets
 * @param {number} count - Nombre de couleurs nécessaires
 * @returns {Array} - Tableau de couleurs rgba
 */
function getChartPalette(count = 10) {
    const colors = getChartColors();
    const palette = [
        colors.palette.primary,
        colors.palette.success,
        colors.palette.danger,
        colors.palette.warning,
        colors.palette.info,
        colors.palette.purple,
        colors.palette.pink,
        colors.palette.orange,
        colors.palette.teal,
        colors.palette.cyan
    ];
    
    // Si besoin de plus de couleurs, on réutilise en variant l'opacité
    if (count > palette.length) {
        const extended = [...palette];
        for (let i = palette.length; i < count; i++) {
            extended.push(palette[i % palette.length]);
        }
        return extended.slice(0, count);
    }
    
    return palette.slice(0, count);
}

/**
 * Générer des couleurs de fond avec transparence pour datasets
 * @param {string} color - Couleur de base (hex ou rgb)
 * @param {number} alpha - Opacité (0-1)
 * @returns {string} - Couleur rgba
 */
function getChartBackgroundColor(color, alpha = 0.2) {
    // Si déjà rgba, retourner tel quel
    if (color.startsWith('rgba')) return color;
    
    // Convertir hex vers rgb
    if (color.startsWith('#')) {
        const r = parseInt(color.slice(1, 3), 16);
        const g = parseInt(color.slice(3, 5), 16);
        const b = parseInt(color.slice(5, 7), 16);
        return `rgba(${r}, ${g}, ${b}, ${alpha})`;
    }
    
    // Si rgb, convertir en rgba
    if (color.startsWith('rgb')) {
        return color.replace('rgb', 'rgba').replace(')', `, ${alpha})`);
    }
    
    return color;
}

// Initialiser au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    applyChartDefaultsForTheme();
});

// Écouter l'événement personnalisé de changement de thème
window.addEventListener('themeChanged', function(e) {
    applyChartDefaultsForTheme();
    refreshAllCharts();
});
