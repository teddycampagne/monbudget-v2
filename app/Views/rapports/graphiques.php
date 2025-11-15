<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2><i class="bi bi-bar-chart"></i> Graphiques d'Analyse</h2>
                <a href="rapports" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Retour
                </a>
            </div>
        </div>
    </div>
    
    <!-- Filtres -->
    <div class="card mb-4">
        <div class="card-body">
            <form id="filtresGraphiques" class="row g-3">
                <div class="col-md-3">
                    <label for="compte_graph" class="form-label">Compte</label>
                    <select name="compte_id" id="compte_graph" class="form-select">
                        <option value="">Tous les comptes</option>
                        <?php foreach ($comptes as $compte): ?>
                            <option value="<?= $compte['id'] ?>" <?= $compte_id == $compte['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($compte['nom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label for="annee_graph" class="form-label">Année</label>
                    <select name="annee" id="annee_graph" class="form-select">
                        <?php 
                        $anneeActuelle = (int) date('Y');
                        for ($a = $anneeActuelle; $a >= $anneeActuelle - 3; $a--): 
                        ?>
                            <option value="<?= $a ?>" <?= $annee == $a ? 'selected' : '' ?>><?= $a ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label for="mois_graph" class="form-label">Mois</label>
                    <select name="mois" id="mois_graph" class="form-select">
                        <option value="">Année complète</option>
                        <?php 
                        $moisNoms = ['', 'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 
                                     'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
                        for ($m = 1; $m <= 12; $m++): 
                        ?>
                            <option value="<?= $m ?>" <?= $mois == $m ? 'selected' : '' ?>>
                                <?= $moisNoms[$m] ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-filter"></i> Filtrer
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Évolution du solde -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-graph-up"></i> Évolution du Solde avec Projection</h5>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="nb_mois_evolution" class="form-label">Période d'affichage</label>
                <select id="nb_mois_evolution" class="form-select w-auto">
                    <option value="3">3 derniers mois</option>
                    <option value="6" selected>6 derniers mois</option>
                    <option value="12">12 derniers mois</option>
                    <option value="24">24 derniers mois</option>
                </select>
            </div>
            <canvas id="chartEvolutionSolde" height="80"></canvas>
        </div>
    </div>
    
    <div class="row g-4 mb-4">
        <!-- Répartition par catégories -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-pie-chart"></i> Répartition des Dépenses</h5>
                </div>
                <div class="card-body">
                    <canvas id="chartRepartitionDepenses"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-pie-chart"></i> Répartition des Revenus</h5>
                </div>
                <div class="card-body">
                    <canvas id="chartRepartitionRevenus"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Balances mensuelles -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-bar-chart"></i> Balances Mensuelles</h5>
        </div>
        <div class="card-body">
            <canvas id="chartBalances" height="80"></canvas>
        </div>
    </div>
    
    <!-- Tendance d'épargne -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-graph-up-arrow"></i> Tendance d'Épargne (Revenus - Dépenses)</h5>
        </div>
        <div class="card-body">
            <canvas id="chartTendanceEpargne" height="80"></canvas>
        </div>
    </div>
    
    <!-- Suivi budgétaire -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-cash-stack"></i> Suivi Budgétaire (Prévu vs Réalisé)</h5>
        </div>
        <div class="card-body">
            <canvas id="chartBudgetaire" height="80"></canvas>
        </div>
    </div>
    
    <!-- Modal détail catégorie -->
    <div class="modal fade" id="modalDetailCategorie" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalDetailCategorieTitle">Détail Catégorie</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Répartition par sous-catégories</h6>
                            <canvas id="chartSousCategories"></canvas>
                        </div>
                        <div class="col-md-6">
                            <h6>Transactions</h6>
                            <div id="listeTransactions" style="max-height: 400px; overflow-y: auto;">
                                <!-- Rempli par JS -->
                            </div>
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
// Variables globales
let chartEvolution = null;
let chartDepenses = null;
let chartRevenus = null;
let chartBalances = null;
let chartTendanceEpargne = null;
let chartBudgetaire = null;
let chartSousCategories = null;

// Paramètres actuels
let params = {
    compte_id: <?= $compte_id ?? 'null' ?>,
    annee: <?= $annee ?>,
    mois: <?= $mois ?? 'null' ?>
};

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    chargerTousGraphiques();
    
    // Gestionnaire de filtres
    document.getElementById('filtresGraphiques').addEventListener('submit', function(e) {
        e.preventDefault();
        params.compte_id = document.getElementById('compte_graph').value || null;
        params.annee = parseInt(document.getElementById('annee_graph').value);
        params.mois = document.getElementById('mois_graph').value || null;
        chargerTousGraphiques();
    });
    
    // Changement période évolution
    document.getElementById('nb_mois_evolution').addEventListener('change', function() {
        chargerEvolutionSolde();
    });
});

function chargerTousGraphiques() {
    chargerEvolutionSolde();
    chargerRepartitionCategories();
    chargerBalances();
    chargerTendanceEpargne();
    chargerSuiviBudgetaire();
}

// Évolution du solde
async function chargerEvolutionSolde() {
    if (!params.compte_id) {
        if (chartEvolution) {
            chartEvolution.destroy();
            chartEvolution = null;
        }
        document.getElementById('chartEvolutionSolde').getContext('2d').clearRect(0, 0, 
            document.getElementById('chartEvolutionSolde').width, 
            document.getElementById('chartEvolutionSolde').height);
        return;
    }
    
    const nbMois = document.getElementById('nb_mois_evolution').value;
    const url = `api/rapports/evolution-solde?compte_id=${params.compte_id}&nb_mois=${nbMois}`;
    
    const response = await fetch(url);
    const data = await response.json();
    
    const ctx = document.getElementById('chartEvolutionSolde').getContext('2d');
    
    if (chartEvolution) {
        chartEvolution.destroy();
    }
    
    const datasets = [{
        label: 'Solde historique',
        data: data.historique.map(d => d.solde),
        borderColor: 'rgb(75, 192, 192)',
        backgroundColor: 'rgba(75, 192, 192, 0.1)',
        tension: 0.1,
        fill: true
    }];
    
    // Ajouter la projection si disponible
    if (data.projection && data.projection.length > 0) {
        datasets.push({
            label: 'Projection (récurrences)',
            data: new Array(data.historique.length - 1).fill(null).concat([
                data.historique[data.historique.length - 1].solde,
                ...data.projection.map(d => d.solde)
            ]),
            borderColor: 'rgb(255, 159, 64)',
            backgroundColor: 'rgba(255, 159, 64, 0.1)',
            borderDash: [5, 5],
            tension: 0.1,
            fill: false
        });
    }
    
    const allLabels = [
        ...data.historique.map(d => new Date(d.date).toLocaleDateString('fr-FR')),
        ...(data.projection || []).map(d => new Date(d.date).toLocaleDateString('fr-FR'))
    ];
    
    chartEvolution = new Chart(ctx, {
        type: 'line',
        data: {
            labels: allLabels,
            datasets: datasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: true
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + 
                                   context.parsed.y.toFixed(2) + ' €';
                        }
                    }
                }
            },
            scales: {
                y: {
                    ticks: {
                        callback: function(value) {
                            return value.toFixed(0) + ' €';
                        }
                    }
                }
            }
        }
    });
}

// Répartition par catégories
async function chargerRepartitionCategories() {
    // Dépenses
    await chargerRepartition('debit', 'chartRepartitionDepenses', 'chartDepenses');
    
    // Revenus
    await chargerRepartition('credit', 'chartRepartitionRevenus', 'chartRevenus');
}

async function chargerRepartition(type, canvasId, chartVar) {
    let url = `api/rapports/repartition-categories?type=${type}&annee=${params.annee}`;
    if (params.compte_id) url += `&compte_id=${params.compte_id}`;
    if (params.mois) url += `&mois=${params.mois}`;
    
    const response = await fetch(url);
    const data = await response.json();
    
    const ctx = document.getElementById(canvasId).getContext('2d');
    
    if (type === 'debit' && chartDepenses) {
        chartDepenses.destroy();
    } else if (type === 'credit' && chartRevenus) {
        chartRevenus.destroy();
    }
    
    const chart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: data.map(c => c.nom),
            datasets: [{
                data: data.map(c => c.total),
                backgroundColor: data.map(c => c.couleur || getRandomColor())
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'right'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percent = ((context.parsed / total) * 100).toFixed(1);
                            return context.label + ': ' + 
                                   context.parsed.toFixed(2) + ' € (' + percent + '%)';
                        }
                    }
                }
            },
            onClick: (event, elements) => {
                if (elements.length > 0) {
                    const index = elements[0].index;
                    const categorie = data[index];
                    afficherDetailCategorie(categorie.id, categorie.nom);
                }
            }
        }
    });
    
    if (type === 'debit') {
        chartDepenses = chart;
    } else {
        chartRevenus = chart;
    }
}

// Balances mensuelles
async function chargerBalances() {
    let url = `api/rapports/balances?annee=${params.annee}`;
    if (params.compte_id) url += `&compte_id=${params.compte_id}`;
    
    const response = await fetch(url);
    const data = await response.json();
    
    const ctx = document.getElementById('chartBalances').getContext('2d');
    
    if (chartBalances) {
        chartBalances.destroy();
    }
    
    const moisNoms = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 
                      'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'];
    
    chartBalances = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: moisNoms,
            datasets: [{
                label: 'Dépenses',
                data: data.map(m => m.debit),
                backgroundColor: 'rgba(255, 99, 132, 0.7)',
                borderColor: 'rgb(255, 99, 132)',
                borderWidth: 1
            }, {
                label: 'Revenus',
                data: data.map(m => m.credit),
                backgroundColor: 'rgba(75, 192, 192, 0.7)',
                borderColor: 'rgb(75, 192, 192)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: true
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + 
                                   context.parsed.y.toFixed(2) + ' €';
                        },
                        footer: function(items) {
                            const debits = items.find(i => i.dataset.label === 'Dépenses');
                            const credits = items.find(i => i.dataset.label === 'Revenus');
                            if (debits && credits) {
                                const balance = credits.parsed.y - debits.parsed.y;
                                return 'Balance: ' + balance.toFixed(2) + ' €';
                            }
                        }
                    }
                }
            },
            scales: {
                y: {
                    ticks: {
                        callback: function(value) {
                            return value.toFixed(0) + ' €';
                        }
                    }
                }
            }
        }
    });
}

// Tendance d'épargne
async function chargerTendanceEpargne() {
    let url = `api/rapports/tendance-epargne?annee=${params.annee}`;
    if (params.compte_id) url += `&compte_id=${params.compte_id}`;
    
    const response = await fetch(url);
    const data = await response.json();
    
    const ctx = document.getElementById('chartTendanceEpargne').getContext('2d');
    
    if (chartTendanceEpargne) {
        chartTendanceEpargne.destroy();
    }
    
    const moisNoms = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 
                      'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'];
    
    chartTendanceEpargne = new Chart(ctx, {
        type: 'line',
        data: {
            labels: moisNoms,
            datasets: [{
                label: 'Revenus',
                data: data.map(m => m.revenus),
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                tension: 0.3,
                fill: false
            }, {
                label: 'Dépenses',
                data: data.map(m => m.depenses),
                borderColor: 'rgb(255, 99, 132)',
                backgroundColor: 'rgba(255, 99, 132, 0.1)',
                tension: 0.3,
                fill: false
            }, {
                label: 'Épargne',
                data: data.map(m => m.epargne),
                borderColor: 'rgb(54, 162, 235)',
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: true
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + 
                                   context.parsed.y.toFixed(2) + ' €';
                        }
                    }
                }
            },
            scales: {
                y: {
                    ticks: {
                        callback: function(value) {
                            return value.toFixed(0) + ' €';
                        }
                    }
                }
            }
        }
    });
}

// Suivi budgétaire
async function chargerSuiviBudgetaire() {
    let url = `api/rapports/budgetaire?annee=${params.annee}`;
    if (params.mois) url += `&mois=${params.mois}`;
    
    const response = await fetch(url);
    const data = await response.json();
    
    if (data.length === 0) {
        // Pas de budgets définis
        const ctx = document.getElementById('chartBudgetaire').getContext('2d');
        if (chartBudgetaire) {
            chartBudgetaire.destroy();
            chartBudgetaire = null;
        }
        ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);
        ctx.font = '16px Arial';
        ctx.fillStyle = '#6c757d';
        ctx.textAlign = 'center';
        ctx.fillText('Aucun budget défini pour cette période', ctx.canvas.width / 2, ctx.canvas.height / 2);
        return;
    }
    
    const ctx = document.getElementById('chartBudgetaire').getContext('2d');
    
    if (chartBudgetaire) {
        chartBudgetaire.destroy();
    }
    
    chartBudgetaire = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.map(d => d.categorie),
            datasets: [{
                label: 'Budget prévu',
                data: data.map(d => d.prevu),
                backgroundColor: 'rgba(54, 162, 235, 0.7)',
                borderColor: 'rgb(54, 162, 235)',
                borderWidth: 1
            }, {
                label: 'Réalisé',
                data: data.map(d => d.realise),
                backgroundColor: data.map(d => {
                    return d.realise > d.prevu ? 'rgba(255, 99, 132, 0.7)' : 'rgba(75, 192, 192, 0.7)';
                }),
                borderColor: data.map(d => {
                    return d.realise > d.prevu ? 'rgb(255, 99, 132)' : 'rgb(75, 192, 192)';
                }),
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: true
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + 
                                   context.parsed.y.toFixed(2) + ' €';
                        },
                        footer: function(items) {
                            if (items.length === 2) {
                                const prevu = items[0].parsed.y;
                                const realise = items[1].parsed.y;
                                const diff = realise - prevu;
                                const percent = prevu > 0 ? ((diff / prevu) * 100).toFixed(1) : 0;
                                return `Écart: ${diff.toFixed(2)} € (${percent}%)`;
                            }
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toFixed(0) + ' €';
                        }
                    }
                }
            }
        }
    });
}

// Afficher détail catégorie (drill-down)
async function afficherDetailCategorie(categorieId, categorieNom) {
    let url = `api/rapports/detail-categorie?categorie_id=${categorieId}&annee=${params.annee}`;
    if (params.compte_id) url += `&compte_id=${params.compte_id}`;
    if (params.mois) url += `&mois=${params.mois}`;
    
    const response = await fetch(url);
    const data = await response.json();
    
    // Mettre à jour le titre
    document.getElementById('modalDetailCategorieTitle').textContent = 
        `Détail: ${categorieNom}`;
    
    // Graphique sous-catégories
    const ctx = document.getElementById('chartSousCategories').getContext('2d');
    if (chartSousCategories) {
        chartSousCategories.destroy();
    }
    
    chartSousCategories = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: data.sous_categories.map(sc => sc.nom),
            datasets: [{
                data: data.sous_categories.map(sc => sc.total),
                backgroundColor: data.sous_categories.map(() => getRandomColor())
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
    
    // Liste des transactions
    const listeHtml = data.transactions.map(t => `
        <div class="border-bottom pb-2 mb-2">
            <div class="d-flex justify-content-between">
                <strong>${new Date(t.date_transaction).toLocaleDateString('fr-FR')}</strong>
                <strong>${parseFloat(t.montant).toFixed(2)} €</strong>
            </div>
            <small class="text-muted">${t.libelle || 'Sans libellé'}</small>
            ${t.sous_categorie_nom ? `<br><small class="badge bg-secondary">${t.sous_categorie_nom}</small>` : ''}
        </div>
    `).join('');
    
    document.getElementById('listeTransactions').innerHTML = listeHtml || 
        '<p class="text-muted">Aucune transaction</p>';
    
    // Afficher le modal
    new bootstrap.Modal(document.getElementById('modalDetailCategorie')).show();
}

// Utilitaire couleur aléatoire
function getRandomColor() {
    const letters = '0123456789ABCDEF';
    let color = '#';
    for (let i = 0; i < 6; i++) {
        color += letters[Math.floor(Math.random() * 16)];
    }
    return color;
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
