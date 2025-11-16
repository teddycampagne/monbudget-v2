<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-graph-up"></i> Rapports et Analyses</h2>
            <p class="text-muted">Sélectionnez un compte et une période pour afficher vos rapports et graphiques</p>
        </div>
    </div>
    
    <!-- Formulaire de sélection -->
    <div class="card mb-4">
        <div class="card-body">
            <form id="formSelectionRapport" class="row g-3">
                <div class="col-md-4">
                    <label for="compte_selection" class="form-label">Compte <span class="text-danger">*</span></label>
                    <select name="compte_id" id="compte_selection" class="form-select" required>
                        <option value="">-- Sélectionner un compte --</option>
                        <?php foreach ($comptes as $compte): ?>
                            <option value="<?= $compte['id'] ?>">
                                <?= htmlspecialchars($compte['nom']) ?> 
                                (<?= number_format($compte['solde_actuel'] ?? 0, 2, ',', ' ') ?> €)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="mois_selection" class="form-label">Mois</label>
                    <select name="mois" 
                            id="mois_selection" 
                            class="form-select"
                            data-month-year-shortcuts="month"
                            data-year-select="annee_selection">
                        <option value="">Année complète</option>
                        <?php 
                        $moisNoms = ['', 'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 
                                     'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
                        $moisActuel = (int) date('n');
                        for ($m = 1; $m <= 12; $m++): 
                        ?>
                            <option value="<?= $m ?>" <?= $m === $moisActuel ? 'selected' : '' ?>>
                                <?= $moisNoms[$m] ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="annee_selection" class="form-label">Année <span class="text-danger">*</span></label>
                    <select name="annee" id="annee_selection" class="form-select" required>
                        <?php 
                        $anneeActuelle = (int) date('Y');
                        for ($a = $anneeActuelle; $a >= $anneeActuelle - 3; $a--): 
                        ?>
                            <option value="<?= $a ?>" <?= $a === $anneeActuelle ? 'selected' : '' ?>>
                                <?= $a ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Afficher
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Placeholder / Résultats -->
    <div id="placeholder" class="text-center py-5">
        <i class="bi bi-file-earmark-bar-graph" style="font-size: 5rem; color: #dee2e6;"></i>
        <h4 class="text-muted mt-3">Aucune sélection</h4>
        <p class="text-muted">Veuillez sélectionner un compte et une période pour afficher les rapports</p>
    </div>
    
    <div id="resultats" style="display: none;">
        <!-- Informations sélection -->
        <div class="alert alert-info mb-4">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <strong><i class="bi bi-info-circle"></i> Sélection :</strong>
                    <span id="info_compte"></span> - 
                    <span id="info_periode"></span>
                </div>
                <div class="col-md-4 text-end">
                    <button class="btn btn-sm btn-outline-primary" onclick="reinitialiser()">
                        <i class="bi bi-arrow-counterclockwise"></i> Modifier
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Relevé de compte PDF -->
        <div class="card mb-4 border-primary">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-file-earmark-text"></i> Relevé de Compte PDF</h5>
            </div>
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <p class="mb-0">
                            <i class="bi bi-info-circle text-primary"></i>
                            Exportez toutes les opérations du compte sélectionné au format PDF imprimable.
                        </p>
                    </div>
                    <div class="col-md-4 text-end">
                        <a href="#" id="lien_releve" class="btn btn-primary" target="_blank">
                            <i class="bi bi-file-pdf"></i> Générer le PDF
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Évolution du solde -->
        <div class="card mb-4">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-graph-up"></i> Évolution du Solde avec Projection</h5>
                    <select id="nb_mois_evolution" class="form-select w-auto">
                        <option value="3">3 derniers mois</option>
                        <option value="6" selected>6 derniers mois</option>
                        <option value="12">12 derniers mois</option>
                        <option value="24">24 derniers mois</option>
                    </select>
                </div>
            </div>
            <div class="card-body">
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
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="<?= url('assets/js/dark-mode-charts.js') ?>?v=7"></script>
<script>
// Variables globales
let compteNoms = {};
<?php foreach ($comptes as $compte): ?>
compteNoms[<?= $compte['id'] ?>] = "<?= htmlspecialchars($compte['nom']) ?>";
<?php endforeach; ?>

const moisNoms = ['', 'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 
                  'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];

// Charts
let chartEvolution = null;
let chartDepenses = null;
let chartRevenus = null;
let chartBalances = null;
let chartTendanceEpargne = null;
let chartBudgetaire = null;
let chartSousCategories = null;

// Paramètres de sélection
let params = {
    compte_id: null,
    annee: null,
    mois: null
};

// Gestion du formulaire
document.getElementById('formSelectionRapport').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const compteId = document.getElementById('compte_selection').value;
    const mois = document.getElementById('mois_selection').value;
    const annee = document.getElementById('annee_selection').value;
    
    if (!compteId || !annee) {
        alert('Veuillez sélectionner un compte et une année');
        return;
    }
    
    params.compte_id = parseInt(compteId);
    params.annee = parseInt(annee);
    params.mois = mois ? parseInt(mois) : null;
    
    afficherResultats();
});

// Détecter les changements dans le formulaire pour rafraîchir automatiquement
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('nb_mois_evolution').addEventListener('change', function() {
        if (params.compte_id) {
            chargerEvolutionSolde();
        }
    });
    
    // Rafraîchir automatiquement si déjà affiché
    ['compte_selection', 'mois_selection', 'annee_selection'].forEach(function(id) {
        document.getElementById(id).addEventListener('change', function() {
            // Si les résultats sont déjà affichés, les rafraîchir
            if (document.getElementById('resultats').style.display === 'block') {
                const compteId = document.getElementById('compte_selection').value;
                const mois = document.getElementById('mois_selection').value;
                const annee = document.getElementById('annee_selection').value;
                
                if (compteId && annee) {
                    params.compte_id = parseInt(compteId);
                    params.annee = parseInt(annee);
                    params.mois = mois ? parseInt(mois) : null;
                    
                    afficherResultats();
                }
            }
        });
    });
});

function afficherResultats() {
    // Masquer le placeholder
    document.getElementById('placeholder').style.display = 'none';
    
    // Afficher les résultats
    const resultatsDiv = document.getElementById('resultats');
    resultatsDiv.style.display = 'block';
    
    // Attendre que le DOM soit mis à jour avant d'accéder aux éléments
    setTimeout(function() {
        // Mettre à jour les infos
        const nomCompte = compteNoms[params.compte_id];
        const periode = params.mois ? `${moisNoms[params.mois]} ${params.annee}` : `Année ${params.annee}`;
        
        const infoCompte = document.getElementById('info_compte');
        const infoPeriode = document.getElementById('info_periode');
        
        if (infoCompte) infoCompte.textContent = nomCompte;
        if (infoPeriode) infoPeriode.textContent = periode;
        
        // Construire l'URL du relevé
        let urlReleve = `rapports/releve?compte_id=${params.compte_id}&annee=${params.annee}`;
        if (params.mois) {
            urlReleve += `&mois=${params.mois}`;
        } else {
            urlReleve += `&mois=${new Date().getMonth() + 1}`;
        }
        const lienReleve = document.getElementById('lien_releve');
        if (lienReleve) lienReleve.href = urlReleve;
        
        // Charger tous les graphiques
        chargerTousGraphiques();
        
        // Scroll vers les résultats
        resultatsDiv.scrollIntoView({ behavior: 'smooth' });
    }, 10);
}

function chargerTousGraphiques() {
    chargerEvolutionSolde();
    chargerRepartitionCategories();
    chargerBalances();
    chargerTendanceEpargne();
    chargerBudgetaire();
}

function reinitialiser() {
    document.getElementById('resultats').style.display = 'none';
    document.getElementById('placeholder').style.display = 'block';
    
    // Détruire les charts
    if (chartEvolution) chartEvolution.destroy();
    if (chartDepenses) chartDepenses.destroy();
    if (chartRevenus) chartRevenus.destroy();
    if (chartBalances) chartBalances.destroy();
    if (chartTendanceEpargne) chartTendanceEpargne.destroy();
    if (chartBudgetaire) chartBudgetaire.destroy();
    
    // Scroll vers le formulaire
    document.getElementById('formSelectionRapport').scrollIntoView({ behavior: 'smooth' });
}

// Évolution du solde
async function chargerEvolutionSolde() {
    if (!params.compte_id) return;
    
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
    await chargerRepartition('debit', 'chartRepartitionDepenses', 'chartDepenses');
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
    
    const moisNomsChart = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 
                           'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc', 'Année'];
    
    // Séparer les données mensuelles et annuelles
    const dataMensuelles = data.slice(0, 12);
    const dataAnnuelle = data[12];
    
    // Calculer les balances pour chaque mois
    const balancesMensuelles = dataMensuelles.map(m => m.credit - Math.abs(m.debit));
    const balanceAnnuelle = dataAnnuelle.credit - Math.abs(dataAnnuelle.debit);
    
    // Couleurs dynamiques selon la balance (rouge si négatif, vert si positif)
    const couleursBalance = balancesMensuelles.map(b => 
        b < 0 ? 'rgba(220, 53, 69, 0.8)' : 'rgba(40, 167, 69, 0.8)'
    );
    const couleurBalanceAnnuelle = balanceAnnuelle < 0 ? 'rgba(220, 53, 69, 0.95)' : 'rgba(40, 167, 69, 0.95)';
    
    chartBalances = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: moisNomsChart,
            datasets: [{
                label: 'Dépenses (mensuelles)',
                // Utiliser Math.abs() pour afficher en positif (facilite la comparaison visuelle)
                data: [...dataMensuelles.map(m => Math.abs(m.debit)), null],
                backgroundColor: 'rgba(255, 99, 132, 0.7)',
                borderColor: 'rgb(255, 99, 132)',
                borderWidth: 1,
                yAxisID: 'y',
                order: 2
            }, {
                label: 'Revenus (mensuels)',
                data: [...dataMensuelles.map(m => m.credit), null],
                backgroundColor: 'rgba(75, 192, 192, 0.7)',
                borderColor: 'rgb(75, 192, 192)',
                borderWidth: 1,
                yAxisID: 'y',
                order: 2
            }, {
                label: 'Balance (mensuelles)',
                data: [...balancesMensuelles, null],
                backgroundColor: couleursBalance,
                borderColor: couleursBalance.map(c => c.replace('0.8', '1')),
                borderWidth: 2,
                yAxisID: 'y',
                order: 1,
                // Type ligne pour mieux visualiser la tendance
                type: 'line',
                fill: false,
                tension: 0.3,
                pointRadius: 5,
                pointHoverRadius: 7
            }, {
                label: 'Dépenses (année)',
                // Utiliser Math.abs() pour afficher en positif
                data: [null, null, null, null, null, null, null, null, null, null, null, null, Math.abs(dataAnnuelle.debit)],
                backgroundColor: 'rgba(255, 99, 132, 0.95)',
                borderColor: 'rgb(255, 99, 132)',
                borderWidth: 2,
                yAxisID: 'y1',
                order: 2
            }, {
                label: 'Revenus (année)',
                data: [null, null, null, null, null, null, null, null, null, null, null, null, dataAnnuelle.credit],
                backgroundColor: 'rgba(75, 192, 192, 0.95)',
                borderColor: 'rgb(75, 192, 192)',
                borderWidth: 2,
                yAxisID: 'y1',
                order: 2
            }, {
                label: 'Balance (année)',
                data: [null, null, null, null, null, null, null, null, null, null, null, null, balanceAnnuelle],
                backgroundColor: couleurBalanceAnnuelle,
                borderColor: couleurBalanceAnnuelle.replace('0.95', '1'),
                borderWidth: 3,
                yAxisID: 'y1',
                order: 1,
                type: 'line',
                pointRadius: 8,
                pointHoverRadius: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            interaction: {
                mode: 'index',
                intersect: false
            },
            plugins: {
                legend: {
                    display: true,
                    labels: {
                        filter: function(item) {
                            // Afficher toutes les légendes sauf les annuelles séparément
                            return !item.text.includes('(année)');
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            if (context.parsed.y === null) return null;
                            const label = context.dataset.label
                                .replace(' (mensuelles)', '')
                                .replace(' (mensuels)', '')
                                .replace(' (année)', '');
                            return label + ': ' + context.parsed.y.toFixed(2) + ' €';
                        },
                        footer: function(items) {
                            const validItems = items.filter(i => i.parsed.y !== null);
                            if (validItems.length === 0) return null;
                            
                            // Si c'est un mois
                            if (validItems[0].dataIndex < 12) {
                                const debits = validItems.find(i => i.dataset.label.includes('Dépenses'));
                                const credits = validItems.find(i => i.dataset.label.includes('Revenus'));
                                if (debits && credits) {
                                    const balance = credits.parsed.y - debits.parsed.y;
                                    const status = balance < 0 ? '⚠️ ATTENTION - Déficit' : '✅ Épargne positive';
                                    return 'Balance: ' + balance.toFixed(2) + ' €\n' + status;
                                }
                            } else {
                                // Si c'est l'année
                                const debits = validItems.find(i => i.dataset.label.includes('Dépenses'));
                                const credits = validItems.find(i => i.dataset.label.includes('Revenus'));
                                if (debits && credits) {
                                    const balance = credits.parsed.y - debits.parsed.y;
                                    const status = balance < 0 ? '⚠️ ATTENTION - Déficit annuel' : '✅ Épargne annuelle positive';
                                    return 'Balance annuelle: ' + balance.toFixed(2) + ' €\n' + status;
                                }
                            }
                        }
                    },
                    // Couleur de fond du tooltip selon la balance
                    backgroundColor: function(context) {
                        const dataIndex = context.tooltip.dataPoints[0].dataIndex;
                        if (dataIndex < 12) {
                            const balance = balancesMensuelles[dataIndex];
                            return balance < 0 ? 'rgba(220, 53, 69, 0.9)' : 'rgba(52, 58, 64, 0.9)';
                        } else {
                            return balanceAnnuelle < 0 ? 'rgba(220, 53, 69, 0.9)' : 'rgba(52, 58, 64, 0.9)';
                        }
                    }
                },
                // Plugin pour ajouter une zone d'alerte visuelle
                annotation: {
                    annotations: {
                        line1: {
                            type: 'line',
                            yMin: 0,
                            yMax: 0,
                            borderColor: 'rgba(0, 0, 0, 0.3)',
                            borderWidth: 2,
                            borderDash: [5, 5],
                            label: {
                                display: true,
                                content: 'Seuil d\'équilibre',
                                position: 'end'
                            }
                        }
                    }
                }
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Montants mensuels (€)'
                    },
                    // Inclure 0 et les valeurs négatives pour la balance
                    grace: '5%',
                    ticks: {
                        callback: function(value) {
                            return value.toFixed(0) + ' €';
                        }
                    },
                    // Ligne de grille plus visible sur le zéro
                    grid: {
                        color: function(context) {
                            if (context.tick.value === 0) {
                                return 'rgba(0, 0, 0, 0.5)';
                            }
                            return 'rgba(0, 0, 0, 0.1)';
                        },
                        lineWidth: function(context) {
                            if (context.tick.value === 0) {
                                return 2;
                            }
                            return 1;
                        }
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Total annuel (€)'
                    },
                    grid: {
                        drawOnChartArea: false
                    },
                    ticks: {
                        callback: function(value) {
                            return (value / 1000).toFixed(1) + 'k €';
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
    
    const moisNomsChart = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 
                           'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'];
    
    chartTendanceEpargne = new Chart(ctx, {
        type: 'line',
        data: {
            labels: moisNomsChart,
            datasets: [{
                label: 'Revenus',
                data: data.map(m => m.revenus),
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                tension: 0.3,
                fill: false
            }, {
                label: 'Dépenses',
                // Utiliser Math.abs() pour afficher en positif (cohérence avec graphique balances)
                data: data.map(m => Math.abs(m.depenses)),
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
async function chargerBudgetaire() {
    let url = `api/rapports/budgetaire?annee=${params.annee}`;
    if (params.compte_id) url += `&compte_id=${params.compte_id}`;
    if (params.mois) url += `&mois=${params.mois}`;
    
    const response = await fetch(url);
    const data = await response.json();
    
    const ctx = document.getElementById('chartBudgetaire').getContext('2d');
    
    if (chartBudgetaire) {
        chartBudgetaire.destroy();
        chartBudgetaire = null;
    }
    
    if (data.length === 0) {
        ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);
        ctx.font = '16px Arial';
        ctx.fillStyle = '#6c757d';
        ctx.textAlign = 'center';
        ctx.fillText('Aucun budget défini pour cette période', ctx.canvas.width / 2, ctx.canvas.height / 2);
        return;
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
    
    document.getElementById('modalDetailCategorieTitle').textContent = `Détail: ${categorieNom}`;
    
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
    
    new bootstrap.Modal(document.getElementById('modalDetailCategorie')).show();
}

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
