<?php

<?php
/**
 * UI Helpers
 *
 * Ce fichier contient des helpers UI pour l'interface d'administration.
 * Fournit des fonctions pour générer des composants Bootstrap modernes.
 */

if (!function_exists('statsCard')) {
    /**
     * Génère une carte de statistiques Bootstrap
     *
     * @param string $title Titre de la carte
     * @param string|int $value Valeur principale
     * @param string $icon Icône Bootstrap (ex: 'bi-people')
     * @param string $color Couleur Bootstrap (primary, success, danger, etc.)
     * @param string $subtitle Sous-titre descriptif
     * @return string HTML de la carte
     */
    function statsCard(string $title, $value, string $icon, string $color, string $subtitle): string
    {
        return '
        <div class="card border-' . htmlspecialchars($color) . ' h-100">
            <div class="card-body text-center">
                <div class="d-flex align-items-center justify-content-center mb-2">
                    <i class="' . htmlspecialchars($icon) . ' fs-1 text-' . htmlspecialchars($color) . ' me-2"></i>
                    <div class="text-start">
                        <h6 class="card-title mb-0 text-muted small">' . htmlspecialchars($title) . '</h6>
                        <h3 class="mb-0 text-' . htmlspecialchars($color) . '">' . htmlspecialchars($value) . '</h3>
                    </div>
                </div>
                <small class="text-muted">' . htmlspecialchars($subtitle) . '</small>
            </div>
        </div>';
    }
}
