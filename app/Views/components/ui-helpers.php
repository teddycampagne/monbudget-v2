<?php
/**
 * UI Helpers - Fonctions utilitaires pour générer des composants HTML réutilisables
 * 
 * Réduit la duplication de code dans les vues en centralisant
 * la génération des composants Bootstrap courants (cards, forms, tables, etc.)
 * 
 * @package MonBudget\Views\Components
 * @version 2.0.0
 */

/**
 * Génère l'ouverture d'une card Bootstrap
 * 
 * @param string $class Classes CSS additionnelles (ex: 'shadow-sm', 'border-primary')
 * @param string $headerClass Classes CSS pour le header (ex: 'bg-light', 'bg-danger text-white')
 * @param string|null $title Titre du header (null = pas de header)
 * @return string HTML de l'ouverture de card
 * @example
 * echo cardStart('shadow-sm', 'bg-light', 'Mon titre');
 * // <div class="card shadow-sm"><div class="card-header bg-light">Mon titre</div><div class="card-body">
 */
function cardStart(string $class = '', string $headerClass = '', ?string $title = null): string {
    $html = '<div class="card ' . $class . '">';
    if ($title !== null) {
        $html .= '<div class="card-header ' . $headerClass . '">' . htmlspecialchars($title) . '</div>';
    }
    $html .= '<div class="card-body">';
    return $html;
}

/**
 * Génère la fermeture d'une card Bootstrap
 * 
 * @return string HTML de fermeture
 */
function cardEnd(): string {
    return '</div></div>';
}

/**
 * Génère une card complète avec contenu
 * 
 * @param string $content Contenu HTML de la card
 * @param string $class Classes CSS additionnelles
 * @param string $headerClass Classes CSS pour le header
 * @param string|null $title Titre du header
 * @return string HTML complet de la card
 * @example
 * echo card('<p>Contenu</p>', 'shadow-sm', 'bg-light', 'Titre');
 */
function card(string $content, string $class = '', string $headerClass = '', ?string $title = null): string {
    return cardStart($class, $headerClass, $title) . $content . cardEnd();
}

/**
 * Génère l'ouverture d'un formulaire POST avec CSRF token
 * 
 * @param string $action URL d'action du formulaire
 * @param string $class Classes CSS additionnelles
 * @param string $id ID du formulaire
 * @return string HTML de l'ouverture du form
 * @example
 * echo formStart(url('comptes/store'), 'needs-validation', 'compteForm');
 */
function formStart(string $action, string $class = '', string $id = ''): string {
    $idAttr = $id ? ' id="' . $id . '"' : '';
    $classAttr = $class ? ' class="' . $class . '"' : '';
    return '<form method="POST" action="' . $action . '"' . $classAttr . $idAttr . '>' . csrf();
}

/**
 * Génère la fermeture d'un formulaire
 * 
 * @return string HTML de fermeture
 */
function formEnd(): string {
    return '</form>';
}

/**
 * Génère un bouton de soumission
 * 
 * @param string $label Libellé du bouton
 * @param string $class Classes CSS (ex: 'btn-primary', 'btn-danger btn-sm')
 * @param string $icon Classe d'icône Bootstrap (ex: 'bi-save')
 * @return string HTML du bouton
 * @example
 * echo submitButton('Enregistrer', 'btn-primary', 'bi-save');
 */
function submitButton(string $label, string $class = 'btn-primary', string $icon = ''): string {
    $iconHtml = $icon ? '<i class="bi ' . $icon . ' me-1"></i>' : '';
    return '<button type="submit" class="btn ' . $class . '">' . $iconHtml . htmlspecialchars($label) . '</button>';
}

/**
 * Génère un bouton lien (pour annulation, retour, etc.)
 * 
 * @param string $label Libellé du bouton
 * @param string $url URL de destination
 * @param string $class Classes CSS
 * @param string $icon Classe d'icône Bootstrap
 * @return string HTML du lien bouton
 * @example
 * echo linkButton('Annuler', url('comptes'), 'btn-secondary', 'bi-x-circle');
 */
function linkButton(string $label, string $url, string $class = 'btn-secondary', string $icon = ''): string {
    $iconHtml = $icon ? '<i class="bi ' . $icon . ' me-1"></i>' : '';
    return '<a href="' . $url . '" class="btn ' . $class . '">' . $iconHtml . htmlspecialchars($label) . '</a>';
}

/**
 * Génère l'ouverture d'une table responsive Bootstrap
 * 
 * @param string $class Classes CSS additionnelles pour la table
 * @return string HTML de l'ouverture
 * @example
 * echo tableStart('table-hover table-striped');
 */
function tableStart(string $class = 'table-hover'): string {
    return '<div class="table-responsive"><table class="table ' . $class . '">';
}

/**
 * Génère la fermeture d'une table responsive
 * 
 * @return string HTML de fermeture
 */
function tableEnd(): string {
    return '</table></div>';
}

/**
 * Génère un champ de formulaire input avec label
 * 
 * @param string $name Nom du champ
 * @param string $label Libellé du champ
 * @param string $type Type d'input (text, email, number, etc.)
 * @param string $value Valeur par défaut
 * @param bool $required Champ obligatoire ?
 * @param string $placeholder Placeholder
 * @param string $help Texte d'aide
 * @return string HTML complet du champ
 * @example
 * echo formInput('nom', 'Nom du compte', 'text', old('nom'), true, 'Mon compte courant');
 */
function formInput(
    string $name, 
    string $label, 
    string $type = 'text', 
    string $value = '', 
    bool $required = false, 
    string $placeholder = '',
    string $help = ''
): string {
    $requiredAttr = $required ? ' required' : '';
    $placeholderAttr = $placeholder ? ' placeholder="' . htmlspecialchars($placeholder) . '"' : '';
    
    $html = '<div class="mb-3">';
    $html .= '<label for="' . $name . '" class="form-label">' . htmlspecialchars($label);
    if ($required) {
        $html .= ' <span class="text-danger">*</span>';
    }
    $html .= '</label>';
    $html .= '<input type="' . $type . '" class="form-control" id="' . $name . '" name="' . $name . '" value="' . htmlspecialchars($value) . '"' . $requiredAttr . $placeholderAttr . '>';
    
    if ($help) {
        $html .= '<small class="form-text text-muted">' . htmlspecialchars($help) . '</small>';
    }
    
    $html .= '</div>';
    return $html;
}

/**
 * Génère un champ select avec options
 * 
 * @param string $name Nom du champ
 * @param string $label Libellé
 * @param array $options Tableau [value => label]
 * @param string $selected Valeur sélectionnée
 * @param bool $required Champ obligatoire ?
 * @param string $emptyOption Libellé de l'option vide
 * @return string HTML complet du select
 * @example
 * echo formSelect('compte_id', 'Compte', ['1' => 'Compte 1', '2' => 'Compte 2'], old('compte_id'), true);
 */
function formSelect(
    string $name,
    string $label,
    array $options,
    string $selected = '',
    bool $required = false,
    string $emptyOption = '-- Sélectionner --'
): string {
    $requiredAttr = $required ? ' required' : '';
    
    $html = '<div class="mb-3">';
    $html .= '<label for="' . $name . '" class="form-label">' . htmlspecialchars($label);
    if ($required) {
        $html .= ' <span class="text-danger">*</span>';
    }
    $html .= '</label>';
    $html .= '<select class="form-select" id="' . $name . '" name="' . $name . '"' . $requiredAttr . '>';
    
    if ($emptyOption) {
        $html .= '<option value="">' . htmlspecialchars($emptyOption) . '</option>';
    }
    
    foreach ($options as $value => $label) {
        $selectedAttr = ($value == $selected) ? ' selected' : '';
        $html .= '<option value="' . htmlspecialchars($value) . '"' . $selectedAttr . '>' . htmlspecialchars($label) . '</option>';
    }
    
    $html .= '</select></div>';
    return $html;
}

/**
 * Génère un badge Bootstrap
 * 
 * @param string $text Texte du badge
 * @param string $type Type (primary, success, danger, warning, info, etc.)
 * @return string HTML du badge
 * @example
 * echo badge('Actif', 'success');
 */
function badge(string $text, string $type = 'primary'): string {
    return '<span class="badge bg-' . $type . '">' . htmlspecialchars($text) . '</span>';
}

/**
 * Génère une icône Bootstrap Icons
 * 
 * @param string $icon Nom de l'icône (sans préfixe 'bi-')
 * @param string $class Classes CSS additionnelles
 * @return string HTML de l'icône
 * @example
 * echo icon('trash', 'text-danger');
 */
function icon(string $icon, string $class = ''): string {
    return '<i class="bi bi-' . $icon . ' ' . $class . '"></i>';
}

/**
 * Génère un bouton d'action avec confirmation
 * 
 * @param string $url URL d'action
 * @param string $label Libellé du bouton
 * @param string $icon Icône
 * @param string $class Classes CSS
 * @param string $confirmMessage Message de confirmation
 * @return string HTML du bouton avec formulaire
 * @example
 * echo actionButton(url('comptes/1/delete'), 'Supprimer', 'trash', 'btn-danger btn-sm', 'Confirmer la suppression ?');
 */
function actionButton(
    string $url,
    string $label,
    string $icon = '',
    string $class = 'btn-primary',
    string $confirmMessage = ''
): string {
    $iconHtml = $icon ? '<i class="bi bi-' . $icon . ' me-1"></i>' : '';
    $dataConfirm = $confirmMessage ? ' data-confirm-delete' : '';
    
    $html = '<form method="POST" action="' . $url . '" class="d-inline">';
    $html .= csrf();
    $html .= '<button type="submit" class="btn ' . $class . '"' . $dataConfirm . '>';
    $html .= $iconHtml . htmlspecialchars($label);
    $html .= '</button>';
    $html .= '</form>';
    
    return $html;
}
