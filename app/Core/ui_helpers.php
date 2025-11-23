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
    function statsCard(string $title, $value, string $icon, string $color, ?string $subtitle = null): string
    {
        $subtitleHtml = $subtitle ? '<small class="text-muted">' . htmlspecialchars($subtitle) . '</small>' : '';
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
                ' . $subtitleHtml . '
            </div>
        </div>';
    }
}

if (!function_exists('formInput')) {
    /**
     * Génère un champ de formulaire Bootstrap
     *
     * @param string $name Nom du champ
     * @param string $label Label du champ
     * @param string $type Type du champ (text, email, etc.)
     * @param string $value Valeur par défaut
     * @param bool $required Si le champ est requis
     * @param string $placeholder Placeholder
     * @return string HTML du champ
     */
    function formInput(string $name, string $label, string $type = 'text', string $value = '', bool $required = false, string $placeholder = ''): string
    {
        $requiredAttr = $required ? 'required' : '';
        $requiredStar = $required ? ' *' : '';
        return "
        <div class=\"mb-3\">
            <label for=\"$name\" class=\"form-label\">$label$requiredStar</label>
            <input type=\"$type\" class=\"form-control\" id=\"$name\" name=\"$name\" value=\"" . htmlspecialchars($value) . "\" placeholder=\"$placeholder\" $requiredAttr>
        </div>
        ";
    }
}

if (!function_exists('formSelect')) {
    /**
     * Génère un champ select Bootstrap
     *
     * @param string $name Nom du champ
     * @param string $label Label du champ
     * @param array $options Tableau des options [value => label]
     * @param string $selected Valeur sélectionnée
     * @param bool $required Si le champ est requis
     * @param string $placeholder Placeholder (option vide)
     * @return string HTML du select
     */
    function formSelect(string $name, string $label, array $options, string $selected = '', bool $required = false, string $placeholder = ''): string
    {
        $requiredAttr = $required ? 'required' : '';
        $requiredStar = $required ? ' *' : '';
        $optionsHtml = '';
        
        if ($placeholder) {
            $optionsHtml .= "<option value=\"\">$placeholder</option>";
        }
        
        foreach ($options as $value => $text) {
            $sel = ($selected == $value) ? 'selected' : '';
            $optionsHtml .= "<option value=\"" . htmlspecialchars($value) . "\" $sel>" . htmlspecialchars($text) . "</option>";
        }
        
        return "
        <div class=\"mb-3\">
            <label for=\"$name\" class=\"form-label\">$label$requiredStar</label>
            <select class=\"form-select\" id=\"$name\" name=\"$name\" $requiredAttr>
                $optionsHtml
            </select>
        </div>
        ";
    }
}
