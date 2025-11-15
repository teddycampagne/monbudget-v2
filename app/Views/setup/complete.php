<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation - Terminée</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <?php require __DIR__ . '/../layouts/setup-styles.php'; ?>
</head>
<body>
    <div class="setup-container">
        <div class="setup-card">
            <div class="setup-header">
                <h1><i class="bi bi-rocket-takeoff"></i> MonBudget</h1>
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
                        <div class="step-circle"><i class="bi bi-check-lg"></i></div>
                        <div class="step-label">Terminé</div>
                    </div>
                </div>

                <div class="text-center py-5">
                    <div class="mb-4">
                        <i class="bi bi-check-circle-fill text-success" style="font-size: 5rem;"></i>
                    </div>
                    
                    <h2 class="mb-3">Installation terminée !</h2>
                    
                    <p class="lead mb-4">
                        MonBudget v2.0 est maintenant prêt à l'emploi.
                    </p>

                    <!-- IDENTIFIANTS SUPER-ADMIN USERFIRST -->
                    <?php if (isset($_SESSION['userfirst_credentials'])): ?>
                    <div class="alert alert-danger mb-4" style="max-width: 700px; margin: 0 auto;">
                        <h5 class="alert-heading">
                            <i class="bi bi-shield-lock-fill"></i> SUPER-ADMIN : UserFirst
                        </h5>
                        <hr>
                        <p class="mb-3">
                            Un compte super-administrateur a été créé automatiquement avec un mot de passe fort généré aléatoirement.
                            <strong>Ces identifiants ne seront affichés qu'une seule fois.</strong>
                        </p>
                        <div class="bg-dark text-white p-3 rounded mb-3 text-start">
                            <div class="mb-2">
                                <strong>Login :</strong> 
                                <code class="text-warning fs-5"><?= htmlspecialchars($_SESSION['userfirst_credentials']['username']) ?></code>
                            </div>
                            <div class="mb-2">
                                <strong>Email :</strong> 
                                <code class="text-info"><?= htmlspecialchars($_SESSION['userfirst_credentials']['email']) ?></code>
                            </div>
                            <div>
                                <strong>Password :</strong> 
                                <code class="text-danger fs-5 user-select-all"><?= htmlspecialchars($_SESSION['userfirst_credentials']['password']) ?></code>
                                <button class="btn btn-sm btn-outline-light ms-2" onclick="copyToClipboard('<?= htmlspecialchars($_SESSION['userfirst_credentials']['password'], ENT_QUOTES) ?>', this)">
                                    <i class="bi bi-clipboard"></i> Copier
                                </button>
                            </div>
                        </div>
                        <div class="alert alert-warning mb-0">
                            <i class="bi bi-exclamation-triangle-fill"></i> <strong>ATTENTION CRITIQUE :</strong>
                            <ul class="mb-0 mt-2 text-start">
                                <li>Ce compte a des <strong>privilèges absolus</strong> (accès Administration, RAZ complète)</li>
                                <li><strong>Notez ces identifiants IMMÉDIATEMENT</strong> dans un gestionnaire de mots de passe sécurisé</li>
                                <li>En cas de <strong>perte de ces identifiants</strong>, la seule solution sera de <strong>supprimer et réinstaller complètement l'application</strong></li>
                                <li>Ne partagez <strong>JAMAIS</strong> ces identifiants</li>
                            </ul>
                        </div>
                    </div>
                    <?php 
                    // Nettoyer les identifiants de la session après affichage
                    unset($_SESSION['userfirst_credentials']);
                    endif; 
                    ?>

                    <!-- IDENTIFIANTS ADMIN PRINCIPAL -->
                    <?php if (isset($_SESSION['admin_credentials'])): ?>
                    <div class="alert alert-info mb-4" style="max-width: 700px; margin: 0 auto;">
                        <h5 class="alert-heading">
                            <i class="bi bi-person-badge-fill"></i> Administrateur Principal
                        </h5>
                        <hr>
                        <p class="mb-3">Utilisez ce compte pour votre utilisation quotidienne :</p>
                        <div class="bg-light p-3 rounded text-start">
                            <div class="mb-2">
                                <strong>Login :</strong> 
                                <code class="text-primary fs-5"><?= htmlspecialchars($_SESSION['admin_credentials']['username']) ?></code>
                            </div>
                            <div>
                                <strong>Email :</strong> 
                                <code class="text-secondary"><?= htmlspecialchars($_SESSION['admin_credentials']['email']) ?></code>
                            </div>
                        </div>
                        <p class="mb-0 mt-2">
                            <small class="text-muted">
                                <i class="bi bi-info-circle"></i> Le mot de passe est celui que vous avez choisi lors de l'étape précédente
                            </small>
                        </p>
                    </div>
                    <?php 
                    // Nettoyer les identifiants admin de la session
                    unset($_SESSION['admin_credentials']);
                    endif; 
                    ?>

                    <!-- Message si données d'exemple chargées -->
                    <?php if (isset($_SESSION['sample_data_loaded']) && $_SESSION['sample_data_loaded']): ?>
                    <div class="alert alert-info mb-4">
                        <i class="bi bi-database-fill-check"></i> <strong>Données d'exemple chargées !</strong>
                        <p class="mb-0 mt-2">
                            L'application contient maintenant des données fictives pour vous aider à découvrir toutes les fonctionnalités.
                            Vous pouvez les supprimer à tout moment via le panneau d'administration.
                        </p>
                    </div>
                    <?php 
                    unset($_SESSION['sample_data_loaded']);
                    endif; 
                    ?>

                    <div class="alert alert-success mb-4">
                        <i class="bi bi-check-circle-fill"></i>
                        Votre application est maintenant configurée et sécurisée. 
                        <strong>Connectez-vous avec votre compte administrateur principal pour commencer.</strong>
                    </div>

                    <div class="d-flex gap-3 justify-content-center mt-4">
                        <a href="<?= url('login') ?>" class="btn btn-primary btn-lg">
                            <i class="bi bi-box-arrow-in-right"></i> Se connecter
                        </a>
                    </div>

                    <div class="mt-5 text-muted">
                        <h5>Prochaines étapes :</h5>
                        <ul class="text-start" style="max-width: 500px; margin: 20px auto;">
                            <li class="mb-2">Connectez-vous avec votre compte administrateur principal</li>
                            <li class="mb-2">Configurez vos banques et comptes bancaires</li>
                            <li class="mb-2">Importez vos premières transactions (CSV/OFX)</li>
                            <li class="mb-2">Définissez vos catégories et budgets</li>
                            <li class="mb-2">Configurez les règles d'automatisation</li>
                        </ul>
                        
                        <div class="alert alert-light mt-4">
                            <i class="bi bi-lightbulb"></i> <strong>Conseil :</strong> 
                            Le compte <code>UserFirst</code> est réservé aux opérations critiques d'administration. 
                            Utilisez votre compte administrateur principal pour l'utilisation quotidienne.
                        </div>
                    </div>
                </div>
            </div>

            <div class="setup-footer">
                <div class="text-center text-muted">
                    <small>MonBudget v2.0 - Gestion budgétaire simplifiée</small>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Fonction pour copier le mot de passe dans le presse-papier
    function copyToClipboard(text, btn) {
        navigator.clipboard.writeText(text).then(function() {
            // Afficher une confirmation
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-check"></i> Copié !';
            btn.classList.add('btn-success');
            btn.classList.remove('btn-outline-light');
            
            setTimeout(function() {
                btn.innerHTML = originalHTML;
                btn.classList.remove('btn-success');
                btn.classList.add('btn-outline-light');
            }, 2000);
        }).catch(function(err) {
            alert('Erreur lors de la copie : ' + err);
        });
    }
    </script>
</body>
</html>
