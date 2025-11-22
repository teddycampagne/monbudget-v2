<?php
$title = 'Dashboard';
require_once __DIR__ . '/../layouts/header.php';

// Tableau des mois en français
$mois_fr = ['', 'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
$mois_actuel = $mois_fr[date('n')] . ' ' . date('Y');
?>

<div class="container-fluid mt-4">
    <!-- Message de bienvenue si aucune donnée -->
    <?php if (empty($stats['comptes']) || $stats['comptes'] == 0): ?>
    <div class="row justify-content-center mt-5">
        <div class="col-md-8 text-center">
            <div class="card border-0 shadow-sm p-5">
                <div class="card-body">
                    <i class="bi bi-rocket-takeoff text-primary" style="font-size: 4rem;"></i>
                    <h1 class="mt-4 mb-3">Bienvenue sur MonBudget, <?= htmlspecialchars($user['username']) ?> !</h1>
                    <p class="lead text-muted mb-4">Commencez dès maintenant à gérer vos finances personnelles</p>
                    
                    <div class="row g-3 mt-4 mb-4">
                        <div class="col-md-4">
                            <div class="p-3 border rounded">
                                <i class="bi bi-bank text-primary" style="font-size: 2rem;"></i>
                                <h5 class="mt-2">1. Ajoutez un compte</h5>
                                <p class="small text-muted">Compte courant, livret, carte...</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 border rounded">
                                <i class="bi bi-upload text-success" style="font-size: 2rem;"></i>
                                <h5 class="mt-2">2. Importez vos données</h5>
                                <p class="small text-muted">Fichiers OFX, QIF, CSV...</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 border rounded">
                                <i class="bi bi-graph-up text-info" style="font-size: 2rem;"></i>
                                <h5 class="mt-2">3. Analysez</h5>
                                <p class="small text-muted">Rapports, budgets, projections...</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-3 justify-content-center mt-4">
                        <a href="<?= url('comptes') ?>" class="btn btn-primary btn-lg">
                            <i class="bi bi-plus-circle"></i> Créer mon premier compte
                        </a>
                        <a href="<?= url('imports') ?>" class="btn btn-outline-secondary btn-lg">
                            <i class="bi bi-upload"></i> Importer des transactions
                        </a>
                        <button type="button" class="btn btn-outline-info btn-lg" data-bs-toggle="modal" data-bs-target="#supportModal">
                            <i class="bi bi-question-circle"></i> Besoin d'aide ?
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    
    <!-- En-tête -->
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-2"><i class="bi bi-speedometer2 text-primary"></i> Tableau de bord</h2>
            <p class="text-muted mb-0">Vue d'ensemble de vos finances</p>
        </div>
    </div>

    <!-- Alertes -->
    <?php if (isset($stats['transactions_non_categorisees']) && $stats['transactions_non_categorisees'] > 0): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle"></i>
        <strong>Attention !</strong> Vous avez <?= $stats['transactions_non_categorisees'] ?> transaction(s) non catégorisée(s).
        <a href="<?= url('recherche') ?>?categorie_id=-1" class="alert-link">Les catégoriser maintenant</a>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Statistiques principales -->
    <div class="row g-3 mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="card stat-card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1 small text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px;">Solde total</p>
                            <h4 class="mb-1 fw-bold"><?= number_format($stats['solde_total'], 2, ',', ' ') ?> €</h4>
                            <small class="text-muted">
                                <i class="bi bi-bank"></i> <?= $stats['comptes'] ?> compte(s)
                            </small>
                        </div>
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                            <i class="bi bi-wallet2"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6">
            <div class="card stat-card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1 small text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px;">Revenus</p>
                            <h4 class="mb-1 fw-bold text-success">+<?= number_format($stats['revenus_mois'], 2, ',', ' ') ?> €</h4>
                            <small class="text-muted"><?= $mois_actuel ?></small>
                        </div>
                        <div class="stat-icon bg-success bg-opacity-10 text-success">
                            <i class="bi bi-arrow-up-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6">
            <div class="card stat-card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1 small text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px;">Dépenses</p>
                            <h4 class="mb-1 fw-bold text-danger">-<?= number_format($stats['depenses_mois'], 2, ',', ' ') ?> €</h4>
                            <small class="text-muted">
                                <?php if (isset($stats['evolution_depenses']) && $stats['evolution_depenses'] != 0): ?>
                                    <?= $stats['evolution_depenses'] > 0 ? '↗' : '↘' ?>
                                    <?= abs(round($stats['evolution_depenses'], 1)) ?>% vs dernier
                                <?php else: ?>
                                    Stable
                                <?php endif; ?>
                            </small>
                        </div>
                        <div class="stat-icon bg-danger bg-opacity-10 text-danger">
                            <i class="bi bi-arrow-down-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6">
            <div class="card stat-card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1 small text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px;">Balance</p>
                            <h4 class="mb-1 fw-bold <?= $stats['balance_mois'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                <?= $stats['balance_mois'] >= 0 ? '+' : '' ?><?= number_format($stats['balance_mois'], 2, ',', ' ') ?> €
                            </h4>
                            <small class="text-muted">
                                <?= $stats['transactions_mois'] ?> transaction(s)
                            </small>
                        </div>
                        <div class="stat-icon bg-info bg-opacity-10 text-info">
                            <i class="bi bi-graph-up"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Widget Nuage de Tags -->
    <?php if (!empty($stats['top_tags'])): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-2 px-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold">
                            <i class="bi bi-tags-fill text-primary"></i> Tags les plus utilisés
                        </h6>
                        <small class="text-muted">3 derniers mois</small>
                    </div>
                </div>
                <div class="card-body p-3">
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($stats['top_tags'] as $tag): ?>
                            <?php 
                            $usage = (int)$tag['usage_count'];
                            $debits = (float)$tag['total_debits'];
                            $credits = (float)$tag['total_credits'];
                            $balance = $credits - $debits;
                            
                            // Taille du badge basée sur l'utilisation (min: 0.85rem, max: 1.3rem)
                            $fontSize = 0.85 + (min($usage, 20) / 20) * 0.45;
                            ?>
                            <a href="<?= url('tags/' . $tag['id']) ?>" 
                               class="text-decoration-none"
                               title="<?= $usage ?> transaction(s) - Débits: <?= number_format($debits, 2, ',', ' ') ?> € - Crédits: <?= number_format($credits, 2, ',', ' ') ?> € - Balance: <?= number_format($balance, 2, ',', ' ') ?> €">
                                <span class="badge bg-<?= htmlspecialchars($tag['color']) ?> position-relative" 
                                      style="font-size: <?= $fontSize ?>rem; padding: 0.4em 0.7em;">
                                    <i class="bi bi-tag-fill"></i> <?= htmlspecialchars($tag['name']) ?>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-dark" 
                                          style="font-size: 0.65rem; padding: 0.25em 0.4em;">
                                        <?= $usage ?>
                                    </span>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-3 pt-2 border-top">
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                <i class="bi bi-info-circle"></i> Cliquez sur un tag pour voir ses détails et transactions
                            </small>
                            <a href="<?= url('tags') ?>" class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size: 0.75rem;">
                                Gérer les tags
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <!-- Top catégories -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-2 px-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold">
                            <i class="bi bi-pie-chart text-primary"></i> Top 5 Catégories
                        </h6>
                        <small class="text-muted"><?= $mois_actuel ?></small>
                    </div>
                </div>
                <div class="card-body p-3">
                    <?php if (empty($stats['top_categories'])): ?>
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-inbox" style="font-size: 2.5rem; opacity: 0.3;"></i>
                            <p class="mt-2 mb-0 small">Aucune dépense ce mois-ci</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($stats['top_categories'] as $cat): ?>
                                <div class="list-group-item px-0 py-2 border-0">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <div>
                                            <span class="badge badge-sm" style="background-color: <?= $cat['couleur'] ?? '#6c757d' ?>">
                                                <?php if (!empty($cat['icone'])): ?>
                                                    <i class="bi bi-<?= $cat['icone'] ?>"></i>
                                                <?php endif; ?>
                                                <?= htmlspecialchars($cat['nom']) ?>
                                            </span>
                                            <small class="text-muted ms-1"><?= $cat['nb_transactions'] ?> op.</small>
                                        </div>
                                        <strong class="text-danger" style="font-size: 0.95rem;">
                                            <?= number_format($cat['total'], 2, ',', ' ') ?> €
                                        </strong>
                                    </div>
                                    <div class="progress" style="height: 4px;">
                                        <?php 
                                        $maxTotal = $stats['top_categories'][0]['total'];
                                        $percentage = ($cat['total'] / $maxTotal) * 100;
                                        ?>
                                        <div class="progress-bar" 
                                             style="width: <?= $percentage ?>%; background-color: <?= $cat['couleur'] ?? '#6c757d' ?>"
                                             role="progressbar"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Budgets -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-2 px-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold">
                            <i class="bi bi-cash-stack text-success"></i> Suivi des budgets
                        </h6>
                        <a href="<?= url('budgets') ?>" class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size: 0.75rem;">
                            Gérer
                        </a>
                    </div>
                </div>
                <div class="card-body p-3">
                    <?php if (empty($stats['budgets'])): ?>
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-cash-stack" style="font-size: 2.5rem; opacity: 0.3;"></i>
                            <p class="mt-2 mb-2 small">Aucun budget défini</p>
                            <a href="<?= url('budgets') ?>" class="btn btn-primary btn-sm">
                                Créer un budget
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($stats['budgets'] as $budget): ?>
                                <?php 
                                $pourcentage = ($budget['depense_reelle'] / $budget['montant_prevu']) * 100;
                                $reste = $budget['montant_prevu'] - $budget['depense_reelle'];
                                $classe = $pourcentage < 80 ? 'success' : ($pourcentage < 100 ? 'warning' : 'danger');
                                ?>
                                <div class="list-group-item px-0 py-2 border-0">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="badge badge-sm" style="background-color: <?= $budget['categorie_couleur'] ?? '#6c757d' ?>">
                                            <?php if (!empty($budget['categorie_icone'])): ?>
                                                <i class="bi bi-<?= $budget['categorie_icone'] ?>"></i>
                                            <?php endif; ?>
                                            <?= htmlspecialchars($budget['categorie_nom']) ?>
                                        </span>
                                        <small class="text-muted" style="font-size: 0.8rem;">
                                            <?= number_format($budget['depense_reelle'], 0, ',', ' ') ?> / 
                                            <?= number_format($budget['montant_prevu'], 0, ',', ' ') ?> €
                                        </small>
                                    </div>
                                    <div class="progress mb-1" style="height: 6px;">
                                        <div class="progress-bar bg-<?= $classe ?>" 
                                             style="width: <?= min($pourcentage, 100) ?>%"
                                             role="progressbar"></div>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <small class="text-<?= $classe ?>" style="font-size: 0.75rem;">
                                            <?= round($pourcentage, 0) ?>%
                                        </small>
                                        <small class="text-muted" style="font-size: 0.75rem;">
                                            Reste: <?= number_format($reste, 0, ',', ' ') ?> €
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <!-- Dernières transactions -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-2 px-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold">
                            <i class="bi bi-clock-history text-info"></i> Dernières transactions
                        </h6>
                        <a href="<?= url('comptes') ?>" class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size: 0.75rem;">
                            Voir tout
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($stats['dernieres_transactions'])): ?>
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-inbox" style="font-size: 2.5rem; opacity: 0.3;"></i>
                            <p class="mt-2 mb-0 small">Aucune transaction</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-sm mb-0">
                                <thead class="table-light">
                                    <tr style="font-size: 0.8rem;">
                                        <th class="py-2">Date</th>
                                        <th class="py-2">Libellé</th>
                                        <th class="py-2">Catégorie</th>
                                        <th class="text-end py-2">Montant</th>
                                    </tr>
                                </thead>
                                <tbody style="font-size: 0.85rem;">
                                    <?php foreach ($stats['dernieres_transactions'] as $trans): ?>
                                        <tr>
                                            <td class="py-2">
                                                <small class="text-muted"><?= date('d/m', strtotime($trans['date_transaction'])) ?></small>
                                            </td>
                                            <td class="py-2">
                                                <div><?= htmlspecialchars(substr($trans['libelle'], 0, 35)) ?><?= strlen($trans['libelle']) > 35 ? '...' : '' ?></div>
                                                <?php if (!empty($trans['tiers_nom'])): ?>
                                                    <small class="text-muted">
                                                        <i class="bi bi-person"></i> <?= htmlspecialchars($trans['tiers_nom']) ?>
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-2">
                                                <?php if (!empty($trans['categorie_nom'])): ?>
                                                    <span class="badge badge-sm" style="background-color: <?= $trans['categorie_couleur'] ?? '#6c757d' ?>; font-size: 0.7rem;">
                                                        <?php if (!empty($trans['categorie_icone'])): ?>
                                                            <i class="bi bi-<?= $trans['categorie_icone'] ?>"></i>
                                                        <?php endif; ?>
                                                        <?= htmlspecialchars($trans['categorie_nom']) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted" style="font-size: 0.75rem;">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end py-2">
                                                <strong class="<?= $trans['type_operation'] === 'credit' ? 'text-success' : 'text-danger' ?>">
                                                    <?= $trans['type_operation'] === 'credit' ? '+' : '-' ?>
                                                    <?= number_format($trans['montant'], 2, ',', ' ') ?> €
                                                </strong>
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

        <!-- Colonne de droite -->
        <div class="col-lg-4">
            <!-- Prochaines récurrences -->
            <?php if (!empty($stats['prochaines_recurrentes'])): ?>
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white border-bottom py-2 px-3">
                    <h6 class="mb-0 fw-bold">
                        <i class="bi bi-calendar-event text-warning"></i> Prochaines échéances
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php foreach ($stats['prochaines_recurrentes'] as $rec): ?>
                            <div class="list-group-item px-3 py-2">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <small class="text-muted d-block" style="font-size: 0.7rem;">
                                            <?= !empty($rec['prochaine_execution']) ? date('d/m/Y', strtotime($rec['prochaine_execution'])) : 'À venir' ?>
                                        </small>
                                        <div class="fw-bold" style="font-size: 0.85rem;"><?= htmlspecialchars(substr($rec['libelle'], 0, 20)) ?><?= strlen($rec['libelle']) > 20 ? '...' : '' ?></div>
                                        <?php if (!empty($rec['categorie_nom'])): ?>
                                            <small class="text-muted" style="font-size: 0.7rem;"><?= htmlspecialchars($rec['categorie_nom']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <strong class="<?= $rec['type_operation'] === 'credit' ? 'text-success' : 'text-danger' ?>" style="font-size: 0.85rem;">
                                        <?= $rec['type_operation'] === 'credit' ? '+' : '-' ?>
                                        <?= number_format($rec['montant'], 0, ',', ' ') ?> €
                                    </strong>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Actions rapides -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-2 px-3">
                    <h6 class="mb-0 fw-bold">
                        <i class="bi bi-lightning-fill text-danger"></i> Actions rapides
                    </h6>
                </div>
                <div class="card-body p-2">
                    <div class="d-grid gap-2">
                        <a href="<?= url('comptes') ?>" class="btn btn-sm btn-outline-primary text-start">
                            <i class="bi bi-plus-circle"></i> Nouvelle transaction
                        </a>
                        <a href="<?= url('imports') ?>" class="btn btn-sm btn-outline-secondary text-start">
                            <i class="bi bi-upload"></i> Importer des données
                        </a>
                        <a href="<?= url('categories') ?>" class="btn btn-sm btn-outline-info text-start">
                            <i class="bi bi-tags"></i> Gérer les catégories
                        </a>
                        <a href="<?= url('budgets') ?>" class="btn btn-sm btn-outline-success text-start">
                            <i class="bi bi-cash-stack"></i> Créer un budget
                        </a>
                        <a href="<?= url('rapports') ?>" class="btn btn-sm btn-outline-warning text-start">
                            <i class="bi bi-graph-up"></i> Voir les rapports
                        </a>
                        <a href="<?= url('recherche') ?>" class="btn btn-sm btn-outline-dark text-start">
                            <i class="bi bi-search"></i> Recherche avancée
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.stat-card {
    transition: transform 0.2s;
}
.stat-card:hover {
    transform: translateY(-5px);
}
.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}
</style>

<!-- Modal Support -->
<div class="modal fade" id="supportModal" tabindex="-1" aria-labelledby="supportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="supportModalLabel">
                    <i class="bi bi-question-circle text-info"></i> Support MonBudget
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Ressources d'aide</h6>
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <a href="/docs" class="text-decoration-none">
                                    <i class="bi bi-book"></i> Documentation
                                </a>
                            </li>
                            <li class="mb-2">
                                <a href="https://github.com/teddycampagne/monbudget-v2" target="_blank" class="text-decoration-none">
                                    <i class="bi bi-github"></i> GitHub
                                </a>
                            </li>
                            <li class="mb-2">
                                <a href="mailto:support@monbudget.local" class="text-decoration-none">
                                    <i class="bi bi-envelope"></i> Email support
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6>Créer un ticket de support</h6>
                        <p class="text-muted small">Pour des problèmes techniques ou des demandes spécifiques</p>

                        <form id="supportForm" method="POST" action="/support/create-ticket">
                            <?php echo csrf_field(); ?>
                            <div class="mb-3">
                                <label for="ticketSubject" class="form-label">Sujet</label>
                                <input type="text" class="form-control" id="ticketSubject" name="subject" required>
                            </div>
                            <div class="mb-3">
                                <label for="ticketCategory" class="form-label">Catégorie</label>
                                <select class="form-select" id="ticketCategory" name="category">
                                    <option value="general">Général</option>
                                    <option value="technical">Technique</option>
                                    <option value="import">Import de données</option>
                                    <option value="budget">Budgets</option>
                                    <option value="reports">Rapports</option>
                                    <option value="other">Autre</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="ticketMessage" class="form-label">Description</label>
                                <textarea class="form-control" id="ticketMessage" name="message" rows="4" placeholder="Décrivez votre problème ou votre demande..." required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-send"></i> Créer le ticket
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>