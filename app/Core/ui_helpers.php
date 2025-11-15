<?php

/**
 * UI Helpers pour les vues
 * 
 * Fonctions réutilisables pour générer des composants UI cohérents
 * dans toutes les vues de l'application.
 * 
 * @package MonBudget
 * @author MonBudget Team
 * @version 2.0
 */

// ============================================================================
// CARTES (CARDS)
// ============================================================================

/**
 * Génère l'ouverture d'une carte Bootstrap
 * 
 * @param string $headerTitle Titre de la carte (optionnel)
 * @param string $headerClass Classes CSS supplémentaires pour le header
 * @param string $bodyClass Classes CSS supplémentaires pour le body
 * @return string HTML de début de carte
 * 
 * @example
 * echo cardStart('Liste des comptes', 'bg-primary text-white');
 * echo '<p>Contenu de la carte</p>';
 * echo cardEnd();
 */
function cardStart(string $headerTitle = '', string $headerClass = '', string $bodyClass = ''): string
{
    $html = '<div class="card shadow-sm mb-4">';
    
    if ($headerTitle) {
        $headerClasses = 'card-header ' . $headerClass;
        $html .= sprintf('<div class="%s"><h5 class="mb-0">%s</h5></div>', 
            $headerClasses, 
            htmlspecialchars($headerTitle)
        );
    }
    
    $bodyClasses = 'card-body ' . $bodyClass;
    $html .= sprintf('<div class="%s">', $bodyClasses);
    
    return $html;
}

/**
 * Génère la fermeture d'une carte Bootstrap
 * 
 * @return string HTML de fin de carte
 */
function cardEnd(): string
{
    return '</div></div>';
}

/**
 * Génère une carte de statistiques
 * 
 * @param string $title Titre de la statistique
 * @param string $value Valeur à afficher
 * @param string $icon Classe d'icône Bootstrap Icons
 * @param string $bgColor Couleur de fond (primary, success, danger, etc.)
 * @param string|null $description Description optionnelle
 * @return string HTML de la carte statistique
 * 
 * @example
 * echo statsCard('Solde total', '1 234,56 €', 'bi-wallet2', 'success', '+12% ce mois');
 */
function statsCard(string $title, string $value, string $icon = 'bi-graph-up', string $bgColor = 'primary', ?string $description = null): string
{
    $html = sprintf('<div class="card text-white bg-%s shadow-sm">', $bgColor);
    $html .= '<div class="card-body">';
    $html .= sprintf('<div class="d-flex justify-content-between align-items-center">');
    $html .= sprintf('<div><h6 class="text-white-50 mb-1">%s</h6>', htmlspecialchars($title));
    $html .= sprintf('<h3 class="mb-0">%s</h3>', htmlspecialchars($value));
    if ($description) {
        $html .= sprintf('<small class="text-white-50">%s</small>', htmlspecialchars($description));
    }
    $html .= '</div>';
    $html .= sprintf('<div><i class="%s" style="font-size: 2.5rem; opacity: 0.5;"></i></div>', $icon);
    $html .= '</div></div></div>';
    
    return $html;
}

// ============================================================================
// FORMULAIRES
// ============================================================================

/**
 * Génère un champ input de formulaire
 * 
 * @param string $name Nom du champ
 * @param string $label Label du champ
 * @param string $type Type d'input (text, email, password, etc.)
 * @param string $value Valeur par défaut
 * @param bool $required Champ requis ou non
 * @param string $placeholder Placeholder
 * @param array $attributes Attributs HTML supplémentaires
 * @return string HTML du champ input
 * 
 * @example
 * echo formInput('email', 'Adresse email', 'email', '', true, 'votre@email.com');
 */
function formInput(string $name, string $label, string $type = 'text', string $value = '', bool $required = false, string $placeholder = '', array $attributes = []): string
{
    $id = $attributes['id'] ?? $name;
    $class = $attributes['class'] ?? 'form-control';
    $requiredAttr = $required ? 'required' : '';
    $requiredLabel = $required ? '<span class="text-danger">*</span>' : '';
    
    $extraAttrs = '';
    foreach ($attributes as $key => $val) {
        if (!in_array($key, ['id', 'class'])) {
            $extraAttrs .= sprintf(' %s="%s"', $key, htmlspecialchars($val));
        }
    }
    
    $html = '<div class="mb-3">';
    $html .= sprintf('<label for="%s" class="form-label">%s %s</label>', 
        $id, 
        htmlspecialchars($label), 
        $requiredLabel
    );
    $html .= sprintf(
        '<input type="%s" class="%s" id="%s" name="%s" value="%s" placeholder="%s" %s%s>',
        $type,
        $class,
        $id,
        $name,
        htmlspecialchars($value),
        htmlspecialchars($placeholder),
        $requiredAttr,
        $extraAttrs
    );
    $html .= '</div>';
    
    return $html;
}

/**
 * Génère un select de formulaire
 * 
 * @param string $name Nom du champ
 * @param string $label Label du champ
 * @param array $options Options [value => label]
 * @param string $selected Valeur sélectionnée
 * @param bool $required Champ requis ou non
 * @param string $emptyOption Texte de l'option vide
 * @return string HTML du select
 * 
 * @example
 * echo formSelect('type', 'Type', ['debit' => 'Débit', 'credit' => 'Crédit'], 'debit', true);
 */
function formSelect(string $name, string $label, array $options, string $selected = '', bool $required = false, string $emptyOption = '-- Sélectionner --'): string
{
    $requiredAttr = $required ? 'required' : '';
    $requiredLabel = $required ? '<span class="text-danger">*</span>' : '';
    
    $html = '<div class="mb-3">';
    $html .= sprintf('<label for="%s" class="form-label">%s %s</label>', 
        $name, 
        htmlspecialchars($label), 
        $requiredLabel
    );
    $html .= sprintf('<select class="form-select" id="%s" name="%s" %s>', 
        $name, 
        $name, 
        $requiredAttr
    );
    
    if ($emptyOption) {
        $html .= sprintf('<option value="">%s</option>', htmlspecialchars($emptyOption));
    }
    
    foreach ($options as $value => $label) {
        $selectedAttr = ($value == $selected) ? 'selected' : '';
        $html .= sprintf('<option value="%s" %s>%s</option>', 
            htmlspecialchars($value), 
            $selectedAttr, 
            htmlspecialchars($label)
        );
    }
    
    $html .= '</select></div>';
    
    return $html;
}

/**
 * Génère un textarea de formulaire
 * 
 * @param string $name Nom du champ
 * @param string $label Label du champ
 * @param string $value Valeur par défaut
 * @param int $rows Nombre de lignes
 * @param bool $required Champ requis ou non
 * @return string HTML du textarea
 * 
 * @example
 * echo formTextarea('description', 'Description', '', 4, false);
 */
function formTextarea(string $name, string $label, string $value = '', int $rows = 3, bool $required = false): string
{
    $requiredAttr = $required ? 'required' : '';
    $requiredLabel = $required ? '<span class="text-danger">*</span>' : '';
    
    $html = '<div class="mb-3">';
    $html .= sprintf('<label for="%s" class="form-label">%s %s</label>', 
        $name, 
        htmlspecialchars($label), 
        $requiredLabel
    );
    $html .= sprintf('<textarea class="form-control" id="%s" name="%s" rows="%d" %s>%s</textarea>', 
        $name, 
        $name, 
        $rows,
        $requiredAttr,
        htmlspecialchars($value)
    );
    $html .= '</div>';
    
    return $html;
}

// ============================================================================
// BOUTONS
// ============================================================================

/**
 * Génère un bouton de soumission de formulaire
 * 
 * @param string $text Texte du bouton
 * @param string $icon Classe d'icône Bootstrap Icons (optionnel)
 * @param string $class Classes CSS supplémentaires
 * @return string HTML du bouton
 * 
 * @example
 * echo submitButton('Enregistrer', 'bi-check-lg', 'btn-lg');
 */
function submitButton(string $text = 'Enregistrer', string $icon = '', string $class = ''): string
{
    $iconHtml = $icon ? sprintf('<i class="%s"></i> ', $icon) : '';
    $classes = 'btn btn-primary ' . $class;
    
    return sprintf('<button type="submit" class="%s">%s%s</button>', 
        $classes, 
        $iconHtml, 
        htmlspecialchars($text)
    );
}

/**
 * Génère un bouton d'annulation
 * 
 * @param string $url URL de retour
 * @param string $text Texte du bouton
 * @param string $icon Classe d'icône Bootstrap Icons
 * @return string HTML du bouton
 * 
 * @example
 * echo cancelButton('/comptes', 'Retour');
 */
function cancelButton(string $url, string $text = 'Annuler', string $icon = 'bi-x-lg'): string
{
    $iconHtml = $icon ? sprintf('<i class="%s"></i> ', $icon) : '';
    
    return sprintf('<a href="%s" class="btn btn-secondary">%s%s</a>', 
        url($url), 
        $iconHtml, 
        htmlspecialchars($text)
    );
}

/**
 * Génère un bouton avec confirmation
 * 
 * @param string $text Texte du bouton
 * @param string $confirmMessage Message de confirmation
 * @param string $icon Classe d'icône
 * @param string $btnClass Classes du bouton
 * @return string HTML du bouton avec confirmation
 * 
 * @example
 * echo confirmButton('Supprimer', 'Êtes-vous sûr ?', 'bi-trash', 'btn-danger');
 */
function confirmButton(string $text, string $confirmMessage, string $icon = '', string $btnClass = 'btn-danger'): string
{
    $iconHtml = $icon ? sprintf('<i class="%s"></i> ', $icon) : '';
    
    return sprintf(
        '<button type="submit" class="btn %s" onclick="return confirm(\'%s\')">%s%s</button>',
        $btnClass,
        htmlspecialchars($confirmMessage),
        $iconHtml,
        htmlspecialchars($text)
    );
}

// ============================================================================
// BADGES
// ============================================================================

/**
 * Génère un badge Bootstrap
 * 
 * @param string $text Texte du badge
 * @param string $color Couleur (primary, success, danger, warning, etc.)
 * @param string $icon Classe d'icône (optionnel)
 * @return string HTML du badge
 * 
 * @example
 * echo badge('Actif', 'success', 'bi-check-circle');
 */
function badge(string $text, string $color = 'secondary', string $icon = ''): string
{
    $iconHtml = $icon ? sprintf('<i class="%s"></i> ', $icon) : '';
    
    return sprintf('<span class="badge bg-%s">%s%s</span>', 
        $color, 
        $iconHtml, 
        htmlspecialchars($text)
    );
}

/**
 * Génère un badge de statut
 * 
 * @param bool $isActive Statut actif ou non
 * @param string $activeText Texte si actif
 * @param string $inactiveText Texte si inactif
 * @return string HTML du badge
 * 
 * @example
 * echo statusBadge($compte['actif'], 'Actif', 'Inactif');
 */
function statusBadge(bool $isActive, string $activeText = 'Actif', string $inactiveText = 'Inactif'): string
{
    if ($isActive) {
        return badge($activeText, 'success', 'bi-check-circle');
    } else {
        return badge($inactiveText, 'secondary', 'bi-x-circle');
    }
}

/**
 * Génère un badge de type
 * 
 * @param string $type Type (debit, credit, etc.)
 * @return string HTML du badge
 * 
 * @example
 * echo typeBadge('debit');
 */
function typeBadge(string $type): string
{
    $badges = [
        'debit' => ['Débit', 'danger', 'bi-arrow-down'],
        'credit' => ['Crédit', 'success', 'bi-arrow-up'],
        'virement' => ['Virement', 'primary', 'bi-arrow-left-right'],
    ];
    
    $config = $badges[$type] ?? ['Inconnu', 'secondary', ''];
    
    return badge($config[0], $config[1], $config[2]);
}

// ============================================================================
// ALERTES
// ============================================================================

/**
 * Génère une alerte Bootstrap
 * 
 * @param string $message Message de l'alerte
 * @param string $type Type (success, danger, warning, info)
 * @param bool $dismissible Alerte fermable ou non
 * @return string HTML de l'alerte
 * 
 * @example
 * echo alert('Opération réussie !', 'success', true);
 */
function alert(string $message, string $type = 'info', bool $dismissible = true): string
{
    $dismissClass = $dismissible ? ' alert-dismissible fade show' : '';
    $dismissBtn = $dismissible ? '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' : '';
    
    $icons = [
        'success' => 'bi-check-circle',
        'danger' => 'bi-exclamation-triangle',
        'warning' => 'bi-exclamation-circle',
        'info' => 'bi-info-circle',
    ];
    
    $icon = $icons[$type] ?? 'bi-info-circle';
    
    return sprintf(
        '<div class="alert alert-%s%s" role="alert"><i class="%s"></i> %s%s</div>',
        $type,
        $dismissClass,
        $icon,
        htmlspecialchars($message),
        $dismissBtn
    );
}

// ============================================================================
// TABLES
// ============================================================================

/**
 * Génère l'en-tête d'un tableau
 * 
 * @param array $columns Colonnes [label => options]
 * @return string HTML du thead
 * 
 * @example
 * echo tableHeader(['Nom', 'Email', ['text' => 'Actions', 'class' => 'text-end']]);
 */
function tableHeader(array $columns): string
{
    $html = '<thead class="table-light"><tr>';
    
    foreach ($columns as $column) {
        if (is_array($column)) {
            $text = $column['text'] ?? '';
            $class = $column['class'] ?? '';
            $html .= sprintf('<th class="%s">%s</th>', $class, htmlspecialchars($text));
        } else {
            $html .= sprintf('<th>%s</th>', htmlspecialchars($column));
        }
    }
    
    $html .= '</tr></thead>';
    
    return $html;
}

/**
 * Génère des boutons d'action pour un tableau
 * 
 * @param array $actions Actions [type => url]
 * @param int|string $id ID de l'élément
 * @return string HTML des boutons d'action
 * 
 * @example
 * echo actionButtons(['edit' => '/comptes/1/edit', 'delete' => '/comptes/1/delete'], 1);
 */
function actionButtons(array $actions, $id = null): string
{
    $html = '<div class="btn-group btn-group-sm">';
    
    $configs = [
        'view' => ['bi-eye', 'btn-outline-info', 'Voir'],
        'edit' => ['bi-pencil', 'btn-outline-primary', 'Modifier'],
        'delete' => ['bi-trash', 'btn-outline-danger', 'Supprimer'],
        'download' => ['bi-file-pdf', 'btn-outline-success', 'Télécharger le RIB'],
    ];
    
    foreach ($actions as $type => $url) {
        $config = $configs[$type] ?? ['bi-gear', 'btn-outline-secondary', 'Action'];
        
        if ($type === 'delete') {
            $html .= sprintf(
                '<form method="POST" action="%s" class="d-inline" onsubmit="return confirm(\'Confirmer la suppression ?\')">%s<button type="submit" class="btn %s" title="%s"><i class="%s"></i></button></form>',
                url($url),
                csrf_field(),
                $config[1],
                $config[2],
                $config[0]
            );
        } else {
            $html .= sprintf(
                '<a href="%s" class="btn %s" title="%s"><i class="%s"></i></a>',
                url($url),
                $config[1],
                $config[2],
                $config[0]
            );
        }
    }
    
    $html .= '</div>';
    
    return $html;
}
