<?php
/**
 * Vue détail d'un tag - Affiche les transactions associées
 * Variables disponibles: $tag, $transactions, $stats
 */

use MonBudget\Models\Tag;

$title = 'Tag : ' . htmlspecialchars($tag['name']);
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid mt-4">
    <!-- En-tête -->
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= url('/') ?>">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="<?= url('tags') ?>">Tags</a></li>
                    <li class="breadcrumb-item active"><?= htmlspecialchars($tag['name']) ?></li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-8">
            <h2>
                <span class="badge bg-<?= htmlspecialchars($tag['color']) ?> me-2" style="font-size: 1.2rem;">
                    <i class="bi bi-tag-fill"></i> <?= htmlspecialchars($tag['name']) ?>
                </span>
            </h2>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="<?= url('tags/' . $tag['id'] . '/edit') ?>" class="btn btn-primary">
                <i class="bi bi-pencil"></i> Modifier
            </a>
            <a href="<?= url('tags') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
        </div>
    </div>

    <!-- Statistiques -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Transactions</h6>
                    <h3 class="mb-0"><?= $stats['nb_transactions'] ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Total Débits</h6>
                    <h3 class="mb-0 text-danger">-<?= number_format($stats['total_debits'], 2, ',', ' ') ?> €</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Total Crédits</h6>
                    <h3 class="mb-0 text-success">+<?= number_format($stats['total_credits'], 2, ',', ' ') ?> €</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Balance</h6>
                    <h3 class="mb-0 <?= $stats['balance'] >= 0 ? 'text-success' : 'text-danger' ?>">
                        <?= $stats['balance'] >= 0 ? '+' : '' ?><?= number_format($stats['balance'], 2, ',', ' ') ?> €
                    </h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Liste des transactions -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0">
                <i class="bi bi-list-ul"></i> Transactions avec ce tag
            </h5>
        </div>
        <div class="card-body p-0">
            <?php if (empty($transactions)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.3;"></i>
                    <p class="mt-3 mb-0">Aucune transaction avec ce tag</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Libellé</th>
                                <th>Compte</th>
                                <th>Catégorie</th>
                                <th class="text-end">Montant</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $trans): ?>
                                <tr>
                                    <td>
                                        <small><?= date('d/m/Y', strtotime($trans['date_transaction'])) ?></small>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars(substr($trans['libelle'], 0, 60)) ?>
                                        <?= strlen($trans['libelle']) > 60 ? '...' : '' ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?= htmlspecialchars($trans['compte_nom']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($trans['categorie_nom'])): ?>
                                            <span class="badge" style="background-color: <?= $trans['categorie_couleur'] ?? '#6c757d' ?>">
                                                <?php if (!empty($trans['categorie_icone'])): ?>
                                                    <i class="bi bi-<?= $trans['categorie_icone'] ?>"></i>
                                                <?php endif; ?>
                                                <?= htmlspecialchars($trans['categorie_nom']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <strong class="<?= $trans['type_operation'] === 'credit' ? 'text-success' : 'text-danger' ?>">
                                            <?= $trans['type_operation'] === 'credit' ? '+' : '-' ?>
                                            <?= number_format($trans['montant'], 2, ',', ' ') ?> €
                                        </strong>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?= url('comptes/' . $trans['compte_id'] . '/transactions/' . $trans['id'] . '/edit') ?>" 
                                           class="btn btn-sm btn-outline-primary"
                                           title="Modifier">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="4" class="text-end">Total :</th>
                                <th class="text-end">
                                    <span class="<?= $stats['balance'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                        <?= $stats['balance'] >= 0 ? '+' : '' ?><?= number_format($stats['balance'], 2, ',', ' ') ?> €
                                    </span>
                                </th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Zone de danger -->
    <div class="card border-danger mt-4">
        <div class="card-header bg-danger text-white">
            <h6 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Zone de danger</h6>
        </div>
        <div class="card-body">
            <p class="mb-2">
                La suppression de ce tag supprimera toutes les associations avec les transactions.
                Les transactions ne seront pas supprimées, seulement le lien avec ce tag.
            </p>
            <form method="POST" action="<?= url('tags/' . $tag['id'] . '/delete') ?>" 
                  onsubmit="return confirm('Voulez-vous vraiment supprimer le tag &quot;<?= htmlspecialchars($tag['name']) ?>&quot; ?\n\nCette action supprimera également toutes les associations avec les transactions.');">
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-trash"></i> Supprimer ce tag
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
