<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid px-4 py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= url('comptes') ?>">Comptes</a></li>
            <li class="breadcrumb-item"><a href="<?= url('recurrences') ?>">Récurrences</a></li>
            <li class="breadcrumb-item active" aria-current="page">Administration</li>
        </ol>
    </nav>

    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-gear-fill"></i> Administration des Récurrences
            </h1>
            <p class="text-muted mb-0">Statistiques et monitoring du système automatique</p>
        </div>
        <div class="btn-group">
            <a href="<?= url('recurrences') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
        </div>
    </div>

    <!-- Statistiques globales -->
    <div class="row g-3 mb-4">
        <div class="col-md-2">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="bi bi-arrow-repeat fs-3 text-primary"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small">Total</div>
                            <div class="fs-4 fw-bold"><?= $stats['total_recurrences'] ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-2">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="bi bi-check-circle-fill fs-3 text-success"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small">Actives</div>
                            <div class="fs-4 fw-bold"><?= $stats['actives'] ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-2">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="bi bi-pause-circle-fill fs-3 text-secondary"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small">Inactives</div>
                            <div class="fs-4 fw-bold"><?= $stats['inactives'] ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-2">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="bi bi-clock-fill fs-3 text-warning"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small">Échues</div>
                            <div class="fs-4 fw-bold"><?= $stats['echues'] ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="bi bi-receipt fs-3 text-info"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small">Transactions générées</div>
                            <div class="fs-4 fw-bold"><?= $stats['total_transactions_generees'] ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Colonne gauche -->
        <div class="col-lg-6">
            <!-- Dernière exécution automatique -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0">
                        <i class="bi bi-clock-history"></i> Dernière Exécution Automatique
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($lastExecution): ?>
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="text-muted small">Date</div>
                                <div class="fw-bold"><?= htmlspecialchars($lastExecution['timestamp']) ?></div>
                            </div>
                            <div class="col-6">
                                <div class="text-muted small">Vérifiées</div>
                                <div class="fw-bold"><?= $lastExecution['checked'] ?></div>
                            </div>
                            <div class="col-4">
                                <div class="text-muted small">Exécutées</div>
                                <div class="text-success fw-bold">
                                    <i class="bi bi-check-circle"></i> <?= $lastExecution['executed'] ?>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="text-muted small">Ignorées</div>
                                <div class="text-warning fw-bold">
                                    <i class="bi bi-skip-forward-circle"></i> <?= $lastExecution['skipped'] ?>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="text-muted small">Erreurs</div>
                                <div class="<?= $lastExecution['errors'] > 0 ? 'text-danger' : 'text-muted' ?> fw-bold">
                                    <i class="bi bi-x-circle"></i> <?= $lastExecution['errors'] ?>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info mb-0">
                            <i class="bi bi-info-circle"></i> Aucune exécution automatique ce mois-ci
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Prochaines exécutions -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0">
                        <i class="bi bi-calendar-event"></i> Prochaines Exécutions (7 jours)
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($prochainesExecutions)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Récurrence</th>
                                        <th>Compte</th>
                                        <th class="text-end">Montant</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($prochainesExecutions as $rec): ?>
                                        <tr>
                                            <td>
                                                <?php 
                                                $date = new DateTime($rec['prochaine_execution']);
                                                $isToday = $date->format('Y-m-d') === date('Y-m-d');
                                                $isTomorrow = $date->format('Y-m-d') === date('Y-m-d', strtotime('+1 day'));
                                                ?>
                                                <span class="badge bg-<?= $isToday ? 'danger' : ($isTomorrow ? 'warning' : 'secondary') ?>">
                                                    <?= $isToday ? 'Aujourd\'hui' : ($isTomorrow ? 'Demain' : $date->format('d/m/Y')) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="fw-medium"><?= htmlspecialchars($rec['libelle']) ?></div>
                                                <small class="text-muted">
                                                    <i class="bi bi-arrow-repeat"></i> <?= ucfirst($rec['frequence']) ?>
                                                </small>
                                            </td>
                                            <td>
                                                <small><?= htmlspecialchars($rec['compte_nom']) ?></small>
                                            </td>
                                            <td class="text-end">
                                                <span class="badge bg-<?= $rec['type_operation'] === 'credit' ? 'success' : 'danger' ?>">
                                                    <?= $rec['type_operation'] === 'credit' ? '+' : '-' ?>
                                                    <?= number_format($rec['montant'], 2, ',', ' ') ?> €
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info m-3 mb-0">
                            <i class="bi bi-info-circle"></i> Aucune exécution prévue dans les 7 prochains jours
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Colonne droite -->
        <div class="col-lg-6">
            <!-- Top récurrences -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0">
                        <i class="bi bi-trophy-fill"></i> Récurrences les Plus Actives
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($topRecurrences)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Récurrence</th>
                                        <th>Compte</th>
                                        <th class="text-center">Transactions</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($topRecurrences as $idx => $rec): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span class="badge bg-<?= $idx < 3 ? 'warning' : 'secondary' ?> me-2">
                                                        #<?= $idx + 1 ?>
                                                    </span>
                                                    <div>
                                                        <div class="fw-medium"><?= htmlspecialchars($rec['libelle']) ?></div>
                                                        <small class="text-muted"><?= ucfirst($rec['frequence']) ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <small><?= htmlspecialchars($rec['compte_nom']) ?></small>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-info"><?= $rec['nb_transactions'] ?></span>
                                            </td>
                                            <td class="text-end">
                                                <span class="badge bg-<?= $rec['type_operation'] === 'credit' ? 'success' : 'danger' ?>">
                                                    <?= $rec['type_operation'] === 'credit' ? '+' : '-' ?>
                                                    <?= number_format(abs($rec['total_montant']), 2, ',', ' ') ?> €
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info m-3 mb-0">
                            <i class="bi bi-info-circle"></i> Aucune transaction générée pour le moment
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Logs récents -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0">
                        <i class="bi bi-file-text"></i> Logs Récents
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($recentLogs)): ?>
                        <div class="p-3 rounded" style="background-color: #1e1e1e; color: #d4d4d4; font-family: 'Courier New', monospace; font-size: 0.85rem; max-height: 300px; overflow-y: auto;">
                            <?php foreach ($recentLogs as $log): ?>
                                <div class="mb-1" style="color: #d4d4d4;"><?= htmlspecialchars($log) ?></div>
                            <?php endforeach; ?>
                        </div>
                        <div class="text-end mt-2">
                            <small class="text-muted">
                                <i class="bi bi-folder"></i> 
                                storage/logs/recurrence_auto_<?= date('Y-m') ?>.log
                            </small>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info mb-0">
                            <i class="bi bi-info-circle"></i> Aucun log disponible ce mois-ci
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
