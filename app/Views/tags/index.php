<?php
/**
 * Vue Liste des Tags
 * Affiche tous les tags de l'utilisateur avec statistiques d'utilisation
 */

use MonBudget\Models\Tag;

// Récupérer les données depuis le contrôleur
$tags = $tags ?? [];
$stats = $stats ?? null;
$orderBy = $orderBy ?? 'name';

require_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <!-- En-tête avec titre et bouton -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="bi bi-tags-fill text-primary"></i>
                    Gestion des Tags
                </h1>
                <a href="<?= url('/tags/create') ?>" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Nouveau Tag
                </a>
            </div>

            <!-- Statistiques -->
            <?php if ($stats): ?>
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h6 class="mb-0">Total Tags</h6>
                                    <h2 class="mb-0"><?= $stats['total_tags'] ?></h2>
                                </div>
                                <div class="fs-1 opacity-50">
                                    <i class="bi bi-tags"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h6 class="mb-0">Transactions Taguées</h6>
                                    <h2 class="mb-0"><?= $stats['tagged_transactions'] ?></h2>
                                </div>
                                <div class="fs-1 opacity-50">
                                    <i class="bi bi-check2-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h6 class="mb-0">Moyenne Tags/Transaction</h6>
                                    <h2 class="mb-0"><?= number_format($stats['avg_tags_per_transaction'] ?? 0, 1) ?></h2>
                                </div>
                                <div class="fs-1 opacity-50">
                                    <i class="bi bi-graph-up"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Messages flash -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle"></i> <?= $_SESSION['success'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle"></i> <?= $_SESSION['error'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <!-- Tableau des tags -->
            <div class="card">
                <div class="card-header bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-list-ul"></i> Liste des Tags
                        </h5>
                        <div class="btn-group btn-group-sm" role="group">
                            <a href="<?= url('/tags?order_by=name') ?>" 
                               class="btn btn-outline-secondary <?= $orderBy === 'name' ? 'active' : '' ?>">
                                <i class="bi bi-sort-alpha-down"></i> Nom
                            </a>
                            <a href="<?= url('/tags?order_by=usage_count') ?>" 
                               class="btn btn-outline-secondary <?= $orderBy === 'usage_count' ? 'active' : '' ?>">
                                <i class="bi bi-sort-numeric-down"></i> Utilisation
                            </a>
                            <a href="<?= url('/tags?order_by=created_at') ?>" 
                               class="btn btn-outline-secondary <?= $orderBy === 'created_at' ? 'active' : '' ?>">
                                <i class="bi bi-calendar"></i> Date
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($tags)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-tags display-1 text-muted"></i>
                            <p class="text-muted mt-3 mb-0">Aucun tag créé.</p>
                            <a href="<?= url('/tags/create') ?>" class="btn btn-primary mt-3">
                                <i class="bi bi-plus-circle"></i> Créer votre premier tag
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 40%">Nom</th>
                                        <th style="width: 15%">Couleur</th>
                                        <th style="width: 15%" class="text-center">Utilisation</th>
                                        <th style="width: 15%">Créé le</th>
                                        <th style="width: 15%" class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tags as $tag): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <i class="bi bi-tag-fill text-<?= htmlspecialchars($tag['color']) ?> me-2"></i>
                                                    <strong><?= htmlspecialchars($tag['name']) ?></strong>
                                                </div>
                                            </td>
                                            <td>
                                                <?= Tag::renderBadge($tag, false) ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($tag['usage_count'] > 0): ?>
                                                    <a href="<?= url('tags/' . $tag['id']) ?>" 
                                                       class="badge bg-secondary text-decoration-none">
                                                        <?= $tag['usage_count'] ?> 
                                                        <i class="bi bi-arrow-right-circle"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">0</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?= date('d/m/Y', strtotime($tag['created_at'])) ?>
                                                </small>
                                            </td>
                                            <td class="text-end">
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="<?= url('/tags/' . $tag['id'] . '/edit') ?>" 
                                                       class="btn btn-outline-primary" 
                                                       title="Modifier">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <button type="button" 
                                                            class="btn btn-outline-danger" 
                                                            onclick="confirmDelete(<?= $tag['id'] ?>, '<?= htmlspecialchars($tag['name'], ENT_QUOTES) ?>')"
                                                            title="Supprimer">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Formulaire de suppression caché -->
<form id="deleteForm" method="POST" style="display: none;">
    <input type="hidden" name="confirm" value="1">
</form>

<script>
function confirmDelete(tagId, tagName) {
    if (confirm(`Voulez-vous vraiment supprimer le tag "${tagName}" ?\n\nCette action supprimera également toutes les associations avec les transactions.`)) {
        const form = document.getElementById('deleteForm');
        form.action = '<?= url('/tags') ?>/' + tagId + '/delete';
        form.submit();
    }
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
