<?php
/**
 * Page de test/démo du Version Manager
 * 
 * Permet de tester le système sans modifier la version réelle
 */

session_start();

// Vérifier authentification
if (!isset($_SESSION['user_id'])) {
    header('Location: /monbudgetV2/login');
    exit;
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Version Manager - MonBudget</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body>
    <div class="container py-5">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="bi bi-wrench-adjustable-circle"></i>
                            Test Version Manager
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            <strong>Mode démo :</strong> Cette page permet de tester le système de mise à jour sans modifier la version réelle.
                        </div>

                        <h5 class="mb-3">🔍 Informations Version Actuelle</h5>
                        <div id="current-version-info" class="mb-4">
                            <div class="text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Chargement...</span>
                                </div>
                            </div>
                        </div>

                        <h5 class="mb-3">🧪 Actions de Test</h5>
                        
                        <div class="list-group mb-4">
                            <button class="list-group-item list-group-item-action" id="btn-check-update">
                                <i class="bi bi-cloud-arrow-down"></i>
                                <strong>Vérifier mises à jour manuellement</strong>
                                <p class="mb-0 small text-muted">Interroge l'API GitHub pour chercher nouvelle version</p>
                            </button>
                            
                            <button class="list-group-item list-group-item-action" id="btn-show-modal-demo">
                                <i class="bi bi-window"></i>
                                <strong>Afficher modal démo</strong>
                                <p class="mb-0 small text-muted">Simule une notification de mise à jour avec données fictives</p>
                            </button>
                            
                            <button class="list-group-item list-group-item-action" id="btn-clear-cache">
                                <i class="bi bi-trash"></i>
                                <strong>Vider le cache</strong>
                                <p class="mb-0 small text-muted">Force une nouvelle vérification à la prochaine requête</p>
                            </button>
                            
                            <button class="list-group-item list-group-item-action" id="btn-test-notification">
                                <i class="bi bi-bell"></i>
                                <strong>Tester notification badge</strong>
                                <p class="mb-0 small text-muted">Affiche le badge dans le header (reload page pour voir)</p>
                            </button>
                        </div>

                        <h5 class="mb-3">📊 Résultats Tests</h5>
                        <div id="test-results" class="border rounded p-3 bg-light" style="min-height: 200px; max-height: 400px; overflow-y: auto;">
                            <p class="text-muted mb-0">Aucun test exécuté pour le moment...</p>
                        </div>

                        <div class="mt-3">
                            <a href="/monbudgetV2" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Retour au dashboard
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mt-4">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-code-square"></i>
                            API Endpoints
                        </h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Méthode</th>
                                    <th>Endpoint</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><span class="badge bg-success">GET</span></td>
                                    <td><code>/version/info</code></td>
                                    <td>Version locale + Git info</td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-success">GET</span></td>
                                    <td><code>/version/check-update</code></td>
                                    <td>Vérifier GitHub pour nouvelles versions</td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-warning">POST</span></td>
                                    <td><code>/version/deploy</code></td>
                                    <td>Déployer version (admin)</td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-warning">POST</span></td>
                                    <td><code>/version/rollback</code></td>
                                    <td>Rollback commit précédent</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Fonction pour logger dans la zone de résultats
        function log(message, type = 'info') {
            const resultsDiv = document.getElementById('test-results');
            const timestamp = new Date().toLocaleTimeString();
            const colors = {
                'info': 'text-info',
                'success': 'text-success',
                'error': 'text-danger',
                'warning': 'text-warning'
            };
            
            const entry = document.createElement('div');
            entry.className = `mb-2 ${colors[type] || 'text-dark'}`;
            entry.innerHTML = `<strong>[${timestamp}]</strong> ${message}`;
            
            // Si c'est le premier log, vider le placeholder
            if (resultsDiv.querySelector('.text-muted')) {
                resultsDiv.innerHTML = '';
            }
            
            resultsDiv.appendChild(entry);
            resultsDiv.scrollTop = resultsDiv.scrollHeight;
        }

        // Charger infos version actuelle
        async function loadVersionInfo() {
            try {
                const response = await fetch('/monbudgetV2/version/info');
                const data = await response.json();
                
                if (data.success) {
                    const html = `
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <h6 class="card-subtitle mb-2 text-muted">Version Locale</h6>
                                        <h3 class="card-title text-primary">${data.info.local_version}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <h6 class="card-subtitle mb-2 text-muted">Version Git</h6>
                                        <h3 class="card-title text-secondary">${data.info.git_version}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-subtitle mb-2 text-muted">Branche</h6>
                                        <p class="mb-0"><code>${data.info.branch}</code></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-subtitle mb-2 text-muted">Commit</h6>
                                        <p class="mb-0"><code>${data.info.commit}</code></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    document.getElementById('current-version-info').innerHTML = html;
                    log('✓ Informations version chargées', 'success');
                } else {
                    throw new Error(data.message || 'Erreur inconnue');
                }
            } catch (error) {
                document.getElementById('current-version-info').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle"></i>
                        Erreur: ${error.message}
                    </div>
                `;
                log('✗ Erreur chargement version: ' + error.message, 'error');
            }
        }

        // Vérifier mises à jour
        document.getElementById('btn-check-update').addEventListener('click', async function() {
            log('🔍 Vérification des mises à jour...', 'info');
            this.disabled = true;
            
            try {
                const response = await fetch('/monbudgetV2/version/check-update');
                const data = await response.json();
                
                if (data.success) {
                    if (data.update_available) {
                        log(`✓ Nouvelle version disponible: ${data.update.version}`, 'success');
                        log(`  Changelog: ${data.update.changelog.substring(0, 100)}...`, 'info');
                    } else {
                        log('✓ Aucune mise à jour disponible (version à jour)', 'success');
                    }
                    log(`  Version actuelle: ${data.current_version.local_version}`, 'info');
                } else {
                    log('✗ Erreur: ' + data.message, 'error');
                }
            } catch (error) {
                log('✗ Erreur réseau: ' + error.message, 'error');
            } finally {
                this.disabled = false;
            }
        });

        // Afficher modal démo
        document.getElementById('btn-show-modal-demo').addEventListener('click', function() {
            log('🎭 Affichage modal démo...', 'info');
            
            // Créer données factices
            window.versionManager = window.versionManager || {};
            window.versionManager.updateData = {
                version: '2.3.0',
                tag_name: 'v2.3.0',
                changelog: `## [2.3.0] - 2025-12-01

### ✨ Ajouté
- Nouveaux graphiques interactifs
- Export PDF amélioré
- Mode sombre optimisé

### 🐛 Corrigé
- Correction calcul soldes
- Fix export CSV
- Amélioration performances`,
                published_at: '2025-12-01T10:00:00Z',
                html_url: 'https://github.com/teddycampagne/monbudget-v2/releases/tag/v2.3.0',
                current_version: '2.2.0'
            };
            
            // Charger le script si pas déjà fait
            if (!document.querySelector('script[src*="version-manager.js"]')) {
                const script = document.createElement('script');
                script.src = '/monbudgetV2/assets/js/version-manager.js';
                document.body.appendChild(script);
                
                setTimeout(() => {
                    if (window.versionManager && window.versionManager.showUpdateModal) {
                        window.versionManager.showUpdateModal();
                        log('✓ Modal affichée (données fictives)', 'success');
                    }
                }, 1000);
            } else if (window.versionManager && window.versionManager.showUpdateModal) {
                window.versionManager.showUpdateModal();
                log('✓ Modal affichée (données fictives)', 'success');
            }
        });

        // Vider cache
        document.getElementById('btn-clear-cache').addEventListener('click', function() {
            log('🗑️ Vidage du cache localStorage...', 'warning');
            localStorage.removeItem('last_version_notification');
            log('✓ Cache vidé', 'success');
            log('  La prochaine vérification sera forcée', 'info');
        });

        // Tester notification
        document.getElementById('btn-test-notification').addEventListener('click', function() {
            log('🔔 Test notification badge...', 'info');
            log('  Pour voir le badge, retournez au dashboard', 'warning');
            log('  (Cette page de test n\'a pas de header)', 'info');
            
            // Stocker dans localStorage pour simulation
            localStorage.setItem('test_update_available', 'true');
            log('✓ Flag test activé', 'success');
        });

        // Charger au démarrage
        loadVersionInfo();
        log('🚀 Page de test chargée', 'success');
    </script>
</body>
</html>
