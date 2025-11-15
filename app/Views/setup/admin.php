<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation - Administrateur</title>
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
                    <div class="step active">
                        <div class="step-circle">3</div>
                        <div class="step-label">Administrateur</div>
                    </div>
                    <div class="step">
                        <div class="step-circle">4</div>
                        <div class="step-label">Terminé</div>
                    </div>
                </div>

                <h2>Création du compte administrateur</h2>
                <p class="lead">Créez votre compte administrateur pour accéder à l'application.</p>

                <div id="alerts"></div>

                <form id="adminForm" method="POST" action="<?= url('setup/create-admin') ?>">
                    <div class="mb-3">
                        <label for="username" class="form-label">Nom d'utilisateur <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="username" name="username" required minlength="3">
                        <div class="form-text">Au moins 3 caractères</div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" required>
                        <div class="form-text">Utilisé pour la récupération du mot de passe</div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="password" class="form-label">Mot de passe <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="password" name="password" required minlength="6">
                                <div class="form-text">Au moins 6 caractères</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="password_confirm" class="form-label">Confirmer le mot de passe <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="password_confirm" name="password_confirm" required minlength="6">
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info mt-4">
                        <i class="bi bi-info-circle-fill"></i>
                        Ces informations seront utilisées pour votre première connexion à l'application.
                    </div>
                </form>
            </div>

            <div class="setup-footer">
                <div class="d-flex justify-content-between align-items-center">
                    <a href="<?= url('setup/database') ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Retour
                    </a>
                    <button type="submit" form="adminForm" class="btn btn-primary" id="createBtn">
                        Créer le compte <i class="bi bi-arrow-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const adminForm = document.getElementById('adminForm');
        const createBtn = document.getElementById('createBtn');
        const alertsDiv = document.getElementById('alerts');
        const password = document.getElementById('password');
        const passwordConfirm = document.getElementById('password_confirm');

        // Vérification des mots de passe
        passwordConfirm.addEventListener('input', () => {
            if (password.value !== passwordConfirm.value) {
                passwordConfirm.setCustomValidity('Les mots de passe ne correspondent pas');
            } else {
                passwordConfirm.setCustomValidity('');
            }
        });

        adminForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            if (password.value !== passwordConfirm.value) {
                alertsDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle-fill"></i> Les mots de passe ne correspondent pas
                    </div>
                `;
                return;
            }

            createBtn.disabled = true;
            createBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Création...';
            alertsDiv.innerHTML = '';

            const formData = new FormData(adminForm);

            try {
                const response = await fetch('<?= url('setup/create-admin') ?>', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    alertsDiv.innerHTML = `
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle-fill"></i> ${result.message}
                        </div>
                    `;
                    setTimeout(() => {
                        window.location.href = '<?= url('setup/sample-data') ?>';
                    }, 1500);
                } else {
                    alertsDiv.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle-fill"></i> ${result.message}
                            ${result.errors ? '<br><small>' + result.errors.join('<br>') + '</small>' : ''}
                        </div>
                    `;
                    createBtn.disabled = false;
                    createBtn.innerHTML = 'Créer le compte <i class="bi bi-arrow-right"></i>';
                }
            } catch (error) {
                alertsDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle-fill"></i> Erreur de communication avec le serveur
                    </div>
                `;
                createBtn.disabled = false;
                createBtn.innerHTML = 'Créer le compte <i class="bi bi-arrow-right"></i>';
            }
        });
    </script>
</body>
</html>
