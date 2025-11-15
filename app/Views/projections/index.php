<?php
$title = 'Projections Budgétaires';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid mt-4">
    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-graph-up-arrow text-primary"></i> Projections Budgétaires
            </h1>
            <p class="text-muted small mb-0">Prévisions basées sur récurrences et tendances historiques</p>
        </div>
        <div>
            <a href="<?= url('projections/export-pdf?' . http_build_query($filtres)) ?>" class="btn btn-outline-danger">
                <i class="bi bi-file-pdf"></i> Export PDF
            </a>
        </div>
    </div>

    <!-- Filtres -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="<?= url('projections') ?>" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Période de projection</label>
                    <select name="periode" class="form-select">
                        <option value="3" <?= $filtres['periode'] == 3 ? 'selected' : '' ?>>3 mois</option>
                        <option value="6" <?= $filtres['periode'] == 6 ? 'selected' : '' ?>>6 mois</option>
                        <option value="12" <?= $filtres['periode'] == 12 ? 'selected' : '' ?>>12 mois</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Compte</label>
                    <select name="compte" class="form-select">
                        <option value="">Tous les comptes</option>
                        <?php foreach ($comptes as $compte): ?>
                            <option value="<?= $compte['id'] ?>" <?= $filtres['compte_id'] == $compte['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($compte['nom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Catégorie</label>
                    <select name="categorie" class="form-select">
                        <option value="">Toutes les catégories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $filtres['categorie_id'] == $cat['id'] ? 'selected' : '' ?>>
                                <?php if (!empty($cat['icone'])): ?>
                                    <i class="bi bi-<?= $cat['icone'] ?>"></i>
                                <?php endif; ?>
                                <?= htmlspecialchars($cat['nom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-funnel"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Résumé -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted small mb-1">Total Crédits Prévus</p>
                            <h4 class="mb-0 text-success">+<?= number_format($resume['total_credits'], 2, ',', ' ') ?> €</h4>
                        </div>
                        <div class="bg-success bg-opacity-10 p-3 rounded">
                            <i class="bi bi-arrow-down-circle text-success" style="font-size: 1.5rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted small mb-1">Total Débits Prévus</p>
                            <h4 class="mb-0 text-danger">-<?= number_format($resume['total_debits'], 2, ',', ' ') ?> €</h4>
                        </div>
                        <div class="bg-danger bg-opacity-10 p-3 rounded">
                            <i class="bi bi-arrow-up-circle text-danger" style="font-size: 1.5rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted small mb-1">Solde Cumulé Prévu</p>
                            <h4 class="mb-0 <?= $resume['solde_cumule'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                <?= $resume['solde_cumule'] >= 0 ? '+' : '' ?><?= number_format($resume['solde_cumule'], 2, ',', ' ') ?> €
                            </h4>
                        </div>
                        <div class="bg-primary bg-opacity-10 p-3 rounded">
                            <i class="bi bi-wallet2 text-primary" style="font-size: 1.5rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted small mb-1">Moyenne Mensuelle</p>
                            <h4 class="mb-0 <?= $resume['moyenne_mensuelle'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                <?= $resume['moyenne_mensuelle'] >= 0 ? '+' : '' ?><?= number_format($resume['moyenne_mensuelle'], 2, ',', ' ') ?> €
                            </h4>
                        </div>
                        <div class="bg-info bg-opacity-10 p-3 rounded">
                            <i class="bi bi-graph-up text-info" style="font-size: 1.5rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Graphique -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-3">
            <h5 class="mb-0">
                <i class="bi bi-graph-up"></i> Évolution et Projections
            </h5>
            <small class="text-muted">Historique 12 mois + Projections <?= $filtres['periode'] ?> mois</small>
        </div>
        <div class="card-body">
            <canvas id="chartProjections" style="max-height: 400px;"></canvas>
        </div>
    </div>

    <div class="row g-3">
        <!-- Tableau détaillé des projections -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="mb-0">
                        <i class="bi bi-table"></i> Détail des Projections
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Mois</th>
                                    <th class="text-end">Crédits</th>
                                    <th class="text-end">Débits</th>
                                    <th class="text-end">Solde</th>
                                    <th class="text-end">Conf. Min/Max</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($projections as $proj): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($proj['mois']) ?></strong></td>
                                        <td class="text-end text-success">+<?= number_format($proj['credits_prevus'], 2, ',', ' ') ?> €</td>
                                        <td class="text-end text-danger">-<?= number_format($proj['debits_prevus'], 2, ',', ' ') ?> €</td>
                                        <td class="text-end">
                                            <strong class="<?= $proj['solde_previsionnel'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                                <?= $proj['solde_previsionnel'] >= 0 ? '+' : '' ?><?= number_format($proj['solde_previsionnel'], 2, ',', ' ') ?> €
                                            </strong>
                                        </td>
                                        <td class="text-end">
                                            <small class="text-muted">
                                                <?= number_format($proj['confiance_min'], 0, ',', ' ') ?> / <?= number_format($proj['confiance_max'], 0, ',', ' ') ?> €
                                            </small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Récurrences actives -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="mb-0">
                        <i class="bi bi-arrow-repeat"></i> Récurrences Actives
                    </h5>
                    <small class="text-muted"><?= count($recurrences) ?> récurrence(s)</small>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($recurrences)): ?>
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-inbox" style="font-size: 2rem; opacity: 0.3;"></i>
                            <p class="small mb-0 mt-2">Aucune récurrence active</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recurrences as $rec): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <div class="fw-bold small"><?= htmlspecialchars($rec['libelle']) ?></div>
                                            <small class="text-muted">
                                                <?= ucfirst($rec['frequence']) ?>
                                                <?php if (!empty($rec['categorie_nom'])): ?>
                                                    • <?= htmlspecialchars($rec['categorie_nom']) ?>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                        <div class="text-end ms-2">
                                            <div class="fw-bold <?= $rec['type_operation'] === 'credit' ? 'text-success' : 'text-danger' ?>">
                                                <?= $rec['type_operation'] === 'credit' ? '+' : '-' ?><?= number_format($rec['montant'], 2, ',', ' ') ?> €
                                            </div>
                                            <small class="text-muted">~<?= number_format($rec['montant_mensuel'], 2, ',', ' ') ?> €/mois</small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tendances -->
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="mb-0">
                        <i class="bi bi-bar-chart-line"></i> Tendances Historiques
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <small class="text-muted">Solde moyen 3 mois</small>
                            <small class="fw-bold <?= $tendances['solde']['3mois'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                <?= number_format($tendances['solde']['3mois'], 2, ',', ' ') ?> €
                            </small>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <small class="text-muted">Solde moyen 6 mois</small>
                            <small class="fw-bold <?= $tendances['solde']['6mois'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                <?= number_format($tendances['solde']['6mois'], 2, ',', ' ') ?> €
                            </small>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">Solde moyen 12 mois</small>
                            <small class="fw-bold <?= $tendances['solde']['12mois'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                <?= number_format($tendances['solde']['12mois'], 2, ',', ' ') ?> €
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="<?= url('assets/js/dark-mode-charts.js') ?>?v=7"></script>
<script>
// Données historiques
const historique = <?= json_encode($historique) ?>;
const projections = <?= json_encode($projections) ?>;

// Combiner labels (mois historiques + mois projetés)
const labelsHistorique = historique.map(h => {
    const date = new Date(h.date);
    return date.toLocaleDateString('fr-FR', { month: 'short', year: 'numeric' });
});

const labelsProjections = projections.map(p => {
    const date = new Date(p.date);
    return date.toLocaleDateString('fr-FR', { month: 'short', year: 'numeric' });
});

const allLabels = [...labelsHistorique, ...labelsProjections];

// Données historiques (solde cumulé)
const dataHistorique = historique.map(h => h.solde_cumule);
const dataProjections = projections.map(p => p.solde_cumule);

// Intervalles de confiance
const dataConfianceMin = projections.map(p => p.confiance_min);
const dataConfianceMax = projections.map(p => p.confiance_max);

// Remplir avec null pour aligner les données
const historiqueComplete = [...dataHistorique, ...Array(projections.length).fill(null)];
const projectionsComplete = [...Array(historique.length).fill(null), ...dataProjections];
const confianceMinComplete = [...Array(historique.length).fill(null), ...dataConfianceMin];
const confianceMaxComplete = [...Array(historique.length).fill(null), ...dataConfianceMax];

// Créer le graphique
const ctx = document.getElementById('chartProjections').getContext('2d');
const chartProjections = new Chart(ctx, {
    type: 'line',
    data: {
        labels: allLabels,
        datasets: [
            {
                label: 'Historique',
                data: historiqueComplete,
                borderColor: 'rgb(13, 110, 253)',
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                borderWidth: 3,
                fill: false,
                tension: 0.3,
                pointRadius: 4,
                pointHoverRadius: 6
            },
            {
                label: 'Projection',
                data: projectionsComplete,
                borderColor: 'rgb(255, 193, 7)',
                backgroundColor: 'rgba(255, 193, 7, 0.1)',
                borderWidth: 3,
                borderDash: [5, 5],
                fill: false,
                tension: 0.3,
                pointRadius: 4,
                pointHoverRadius: 6
            },
            {
                label: 'Confiance Min',
                data: confianceMinComplete,
                borderColor: 'rgba(108, 117, 125, 0.3)',
                backgroundColor: 'rgba(108, 117, 125, 0.05)',
                borderWidth: 1,
                borderDash: [2, 2],
                fill: '+1',
                tension: 0.3,
                pointRadius: 0
            },
            {
                label: 'Confiance Max',
                data: confianceMaxComplete,
                borderColor: 'rgba(108, 117, 125, 0.3)',
                backgroundColor: 'rgba(108, 117, 125, 0.05)',
                borderWidth: 1,
                borderDash: [2, 2],
                fill: false,
                tension: 0.3,
                pointRadius: 0
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        interaction: {
            intersect: false,
            mode: 'index'
        },
        plugins: {
            legend: {
                display: true,
                position: 'top'
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) {
                            label += ': ';
                        }
                        if (context.parsed.y !== null) {
                            label += new Intl.NumberFormat('fr-FR', { 
                                style: 'currency', 
                                currency: 'EUR' 
                            }).format(context.parsed.y);
                        }
                        return label;
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: false,
                ticks: {
                    callback: function(value) {
                        return new Intl.NumberFormat('fr-FR', { 
                            style: 'currency', 
                            currency: 'EUR',
                            minimumFractionDigits: 0
                        }).format(value);
                    }
                },
                grid: {
                    drawBorder: true
                }
            },
            x: {
                grid: {
                    display: false
                }
            }
        }
    }
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
