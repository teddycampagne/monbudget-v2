<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation - Données d'exemple</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <?php require __DIR__ . '/../layouts/setup-styles.php'; ?>
</head>
<body>
    <div class="setup-container">
        <div class="setup-card">
            <div class="setup-header">
                <h1><i class="bi bi-database-fill-add"></i> MonBudget</h1>
                <p>Assistant d'installation</p>
            </div>
            
            <div class="setup-body">
                <!-- Step Indicator -->
                <div class="step-indicator">
                    <div class="step completed">
                        <div class="step-circle"><i class="bi bi-check"></i></div>
                        <div class="step-label">Vérifications</div>
                    </div>
                    <div class="step completed">
                        <div class="step-circle"><i class="bi bi-check"></i></div>
                        <div class="step-label">Base de données</div>
                    </div>
                    <div class="step completed">
                        <div class="step-circle"><i class="bi bi-check"></i></div>
                        <div class="step-label">Administrateur</div>
                    </div>
                    <div class="step active">
                        <div class="step-circle">4</div>
                        <div class="step-label">Données</div>
                    </div>
                    <div class="step">
                        <div class="step-circle">5</div>
                        <div class="step-label">Terminé</div>
                    </div>
                </div>

                <div class="text-center mb-4">
                    <h2><i class="bi bi-database-fill-add text-primary"></i> Données d'exemple</h2>
                    <p class="lead mb-4">
                        Souhaitez-vous charger des données d'exemple pour découvrir l'application ?
                    </p>
                </div>

                <!-- Options -->
                <div class="row g-4 mb-4">
                    <!-- Option 1 : Base vierge -->
                    <div class="col-md-6">
                        <div class="card h-100 border-2" id="card-vierge" style="cursor: pointer;">
                            <div class="card-body text-center p-4">
                                <div class="mb-3">
                                    <i class="bi bi-database text-secondary" style="font-size: 3rem;"></i>
                                </div>
                                <h4 class="card-title">Base vierge</h4>
                                <p class="card-text text-muted mb-3">
                                    Commencer avec une base de données vide, prête pour vos propres données.
                                </p>
                                
                                <div class="alert alert-light text-start mb-0">
                                    <strong>Idéal pour :</strong>
                                    <ul class="mb-0 mt-2">
                                        <li>Commencer immédiatement avec vos vraies données</li>
                                        <li>Importer vos fichiers bancaires</li>
                                        <li>Configuration personnalisée complète</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="card-footer bg-light">
                                <button type="button" class="btn btn-outline-secondary w-100" onclick="selectOption('vierge')">
                                    <i class="bi bi-circle"></i> Sélectionner
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Option 2 : Avec données d'exemple -->
                    <div class="col-md-6">
                        <div class="card h-100 border-2 border-primary" id="card-exemple" style="cursor: pointer;">
                            <div class="card-body text-center p-4">
                                <div class="mb-3">
                                    <i class="bi bi-stars text-primary" style="font-size: 3rem;"></i>
                                </div>
                                <h4 class="card-title text-primary">Données d'exemple</h4>
                                <p class="card-text text-muted mb-3">
                                    Découvrir l'application avec des données fictives déjà en place.
                                </p>
                                
                                <div class="alert alert-primary text-start mb-0">
                                    <strong>Inclut :</strong>
                                    <ul class="mb-0 mt-2">
                                        <li>2 banques (Crédit Agricole, Société Générale)</li>
                                        <li>3 comptes bancaires</li>
                                        <li>15+ catégories pré-configurées</li>
                                        <li>~50 transactions sur 3 mois</li>
                                        <li>3 budgets mensuels</li>
                                        <li>5 règles d'automatisation</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="card-footer bg-primary bg-opacity-10">
                                <button type="button" class="btn btn-primary w-100" onclick="selectOption('exemple')">
                                    <i class="bi bi-check-circle-fill"></i> Sélectionner (recommandé)
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info">
                    <i class="bi bi-info-circle-fill"></i> <strong>Note :</strong> 
                    Les données d'exemple sont totalement fictives et peuvent être supprimées à tout moment. 
                    Elles vous permettent de tester toutes les fonctionnalités avant d'ajouter vos vraies données.
                </div>

                <!-- Formulaire caché -->
                <form id="sampleDataForm" method="POST" action="<?= url('setup/load-sample-data') ?>">
                    <input type="hidden" name="load_sample_data" id="load_sample_data" value="yes">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                </form>

                <!-- Actions -->
                <div class="d-flex justify-content-between">
                    <a href="<?= url('setup/admin') ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Retour
                    </a>
                    <button type="button" class="btn btn-primary" id="btnContinuer" onclick="continuer()">
                        Continuer <i class="bi bi-arrow-right"></i>
                    </button>
                </div>
            </div>

            <div class="setup-footer">
                <div class="text-center text-muted">
                    <small>MonBudget v2.0 - Étape 4/5</small>
                </div>
            </div>
        </div>
    </div>

    <script>
    let selectedOption = 'exemple'; // Par défaut : avec exemples

    function selectOption(option) {
        selectedOption = option;
        
        // Mettre à jour l'input hidden
        document.getElementById('load_sample_data').value = option === 'exemple' ? 'yes' : 'no';
        
        // Mettre à jour les styles des cartes
        const cardVierge = document.getElementById('card-vierge');
        const cardExemple = document.getElementById('card-exemple');
        
        if (option === 'vierge') {
            // Card vierge sélectionnée
            cardVierge.classList.add('border-primary', 'border-2');
            cardVierge.classList.remove('border-2');
            cardVierge.querySelector('button').innerHTML = '<i class="bi bi-check-circle-fill"></i> Sélectionné';
            cardVierge.querySelector('button').classList.remove('btn-outline-secondary');
            cardVierge.querySelector('button').classList.add('btn-secondary');
            
            // Card exemple désélectionnée
            cardExemple.classList.remove('border-primary', 'border-2');
            cardExemple.classList.add('border-2');
            cardExemple.querySelector('button').innerHTML = '<i class="bi bi-circle"></i> Sélectionner';
            cardExemple.querySelector('button').classList.remove('btn-primary');
            cardExemple.querySelector('button').classList.add('btn-outline-primary');
        } else {
            // Card exemple sélectionnée
            cardExemple.classList.add('border-primary', 'border-2');
            cardExemple.querySelector('button').innerHTML = '<i class="bi bi-check-circle-fill"></i> Sélectionné';
            cardExemple.querySelector('button').classList.remove('btn-outline-primary');
            cardExemple.querySelector('button').classList.add('btn-primary');
            
            // Card vierge désélectionnée
            cardVierge.classList.remove('border-primary', 'border-2');
            cardVierge.classList.add('border-2');
            cardVierge.querySelector('button').innerHTML = '<i class="bi bi-circle"></i> Sélectionner';
            cardVierge.querySelector('button').classList.remove('btn-secondary');
            cardVierge.querySelector('button').classList.add('btn-outline-secondary');
        }
    }

    function continuer() {
        const btn = document.getElementById('btnContinuer');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Chargement...';
        
        document.getElementById('sampleDataForm').submit();
    }

    // Clic sur les cartes
    document.getElementById('card-vierge').addEventListener('click', function() {
        selectOption('vierge');
    });

    document.getElementById('card-exemple').addEventListener('click', function() {
        selectOption('exemple');
    });
    </script>
</body>
</html>
