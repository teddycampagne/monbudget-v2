<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid px-4 py-4">
    <!-- En-tête avec filtres -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1><i class="bi bi-calculator"></i> Budgets</h1>
            <p class="text-muted">Gérez vos budgets par catégorie</p>
        </div>
        <div class="col-md-4 text-end">
            <div class="btn-group">
                <a href="<?= url('budgets/create?' . http_build_query(['annee' => $annee, 'mois' => $mois ?? ''])) ?>" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Nouveau budget
                </a>
                <a href="<?= url('budgets/generate') ?>" class="btn btn-outline-primary">
                    <i class="bi bi-magic"></i> Générer automatiquement
                </a>
            </div>
        </div>
    </div>

    <!-- Filtres de période -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="<?= url('budgets') ?>" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Année</label>
                    <select name="annee" class="form-select" onchange="this.form.submit()">
                        <?php for($y = date('Y') - 2; $y <= date('Y') + 1; $y++): ?>
                            <option value="<?= $y ?>" <?= $annee == $y ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Mois</label>
                    <select name="mois" class="form-select" onchange="this.form.submit()">
                        <option value="">Année complète</option>
                        <?php 
                        $moisNoms = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
                        for($m = 1; $m <= 12; $m++): 
                        ?>
                            <option value="<?= $m ?>" <?= $mois == $m ? 'selected' : '' ?>>
                                <?= $moisNoms[$m - 1] ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <div>
                        <span class="badge bg-info">Période: <?= $periode_label ?></span>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Statistiques globales -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="text-muted">Budgets actifs</h6>
                    <h3><?= $stats['nb_budgets'] ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="text-muted">Total prévu</h6>
                    <h3 class="text-primary"><?= number_format($stats['total_prevu'], 2, ',', ' ') ?> €</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="text-muted">Total réalisé</h6>
                    <h3 class="<?= $stats['total_realise'] > $stats['total_prevu'] ? 'text-danger' : 'text-success' ?>">
                        <?= number_format($stats['total_realise'], 2, ',', ' ') ?> €
                    </h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="text-muted">Dépassements</h6>
                    <h3 class="<?= $stats['nb_depassements'] > 0 ? 'text-danger' : 'text-success' ?>">
                        <?= $stats['nb_depassements'] ?>
                    </h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Alertes de dépassement -->
    <?php if (count($depassements) > 0): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <h5 class="alert-heading"><i class="bi bi-exclamation-triangle"></i> Budgets dépassés</h5>
            <ul class="mb-0">
                <?php foreach ($depassements as $dep): ?>
                    <li>
                        <strong><?= htmlspecialchars($dep['categorie_nom']) ?></strong> : 
                        <?= number_format($dep['montant_realise'], 2, ',', ' ') ?> € / <?= number_format($dep['montant'], 2, ',', ' ') ?> € 
                        (<?= number_format($dep['montant_realise'] - $dep['montant'], 2, ',', ' ') ?> € de dépassement)
                    </li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Liste des budgets -->
    <?php if (count($budgets) > 0): ?>
        <div class="row g-3">
            <?php foreach ($budgets as $budget): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 <?= $budget['depasse'] ? 'border-danger' : '' ?>">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <?php if (!empty($budget['categorie_icone'])): ?>
                                    <i class="bi bi-<?= htmlspecialchars($budget['categorie_icone']) ?>"></i>
                                <?php endif; ?>
                                <strong><?= htmlspecialchars($budget['categorie_nom']) ?></strong>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-link" data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="<?= url('budgets/' . $budget['id'] . '/edit') ?>">
                                            <i class="bi bi-pencil"></i> Modifier
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <?php if ($mois === null): ?>
                                            <!-- Vue annuelle : supprimer tous les budgets mensuels de cette catégorie -->
                                            <a class="dropdown-item text-danger" 
                                               href="<?= url('budgets/delete-annual?' . http_build_query(['categorie_id' => $budget['categorie_id'], 'annee' => $annee])) ?>"
                                               onclick="return confirm('Supprimer TOUS les budgets mensuels de cette catégorie pour l\'année <?= $annee ?> ?')">
                                                <i class="bi bi-trash"></i> Supprimer le budget annuel
                                            </a>
                                        <?php else: ?>
                                            <!-- Vue mensuelle : supprimer seulement ce budget -->
                                            <a class="dropdown-item text-danger" 
                                               href="<?= url('budgets/' . $budget['id'] . '/delete') ?>"
                                               onclick="return confirm('Supprimer ce budget mensuel ?')">
                                                <i class="bi bi-trash"></i> Supprimer
                                            </a>
                                        <?php endif; ?>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Montants -->
                            <div class="row text-center mb-3">
                                <div class="col-6">
                                    <small class="text-muted">Prévu</small>
                                    <div class="fw-bold">
                                        <?= number_format($budget['montant'], 2, ',', ' ') ?> €
                                    </div>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Réalisé</small>
                                    <div class="fw-bold <?= $budget['depasse'] ? 'text-danger' : 'text-success' ?>">
                                        <?= number_format($budget['montant_realise'], 2, ',', ' ') ?> €
                                    </div>
                                </div>
                            </div>

                            <!-- Barre de progression -->
                            <div class="mb-2">
                                <div class="progress" style="height: 25px;">
                                    <div class="progress-bar <?= $budget['depasse'] ? 'bg-danger' : 'bg-success' ?>" 
                                         role="progressbar" 
                                         style="width: <?= min($budget['pourcentage'], 100) ?>%;"
                                         aria-valuenow="<?= $budget['pourcentage'] ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                        <?= $budget['pourcentage'] ?> %
                                    </div>
                                </div>
                            </div>

                            <!-- Restant -->
                            <div class="text-center">
                                <?php if ($budget['depasse']): ?>
                                    <span class="badge bg-danger">
                                        Dépassé de <?= number_format(abs($budget['restant']), 2, ',', ' ') ?> €
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-success">
                                        Reste <?= number_format($budget['restant'], 2, ',', ' ') ?> €
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-footer text-muted small">
                            <?php if ($mois === null): ?>
                                Budget annuel (total sur 12 mois)
                            <?php else: ?>
                                Budget mensuel
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> Aucun budget défini pour cette période.
            <a href="<?= url('budgets/create?' . http_build_query(['annee' => $annee, 'mois' => $mois ?? ''])) ?>" class="alert-link">
                Créer un budget
            </a>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
