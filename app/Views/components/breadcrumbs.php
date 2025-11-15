<?php
/**
 * Composant Breadcrumbs
 * 
 * Usage: 
 * <?php renderBreadcrumbs([
 *     ['label' => 'Accueil', 'url' => '/'],
 *     ['label' => 'Comptes', 'url' => '/comptes'],
 *     ['label' => 'Détails'] // Pas d'URL = page actuelle
 * ]); ?>
 */

if (!function_exists('renderBreadcrumbs')) {
    function renderBreadcrumbs(array $items): void {
        if (empty($items)) {
            return;
        }
        ?>
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <?php foreach ($items as $index => $item): ?>
                    <?php $isLast = ($index === count($items) - 1); ?>
                    
                    <li class="breadcrumb-item <?= $isLast ? 'active' : '' ?>" 
                        <?= $isLast ? 'aria-current="page"' : '' ?>>
                        
                        <?php if (!$isLast && isset($item['url'])): ?>
                            <a href="<?= htmlspecialchars($item['url']) ?>">
                                <?php if (isset($item['icon'])): ?>
                                    <i class="bi bi-<?= htmlspecialchars($item['icon']) ?>"></i>
                                <?php endif; ?>
                                <?= htmlspecialchars($item['label']) ?>
                            </a>
                        <?php else: ?>
                            <?php if (isset($item['icon'])): ?>
                                <i class="bi bi-<?= htmlspecialchars($item['icon']) ?>"></i>
                            <?php endif; ?>
                            <?= htmlspecialchars($item['label']) ?>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ol>
        </nav>
        <?php
    }
}
