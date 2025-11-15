<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation - Base de données</title>
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
                    <div class="step active">
                        <div class="step-circle">2</div>
                        <div class="step-label">Base de données</div>
                    </div>
                    <div class="step">
                        <div class="step-circle">3</div>
                        <div class="step-label">Administrateur</div>
                    </div>
                    <div class="step">
                        <div class="step-circle">4</div>
                        <div class="step-label">Terminé</div>
                    </div>
                </div>

                <h2>Configuration de la base de données</h2>
                <p class="lead">Veuillez entrer les informations de connexion à votre base de données MySQL.</p>

                <div id="alerts"></div>

                <form id="dbForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="db_host" class="form-label">Hôte <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="db_host" name="db_host" value="<?= $defaultConfig['host'] ?? 'localhost' ?>" required>
                                <div class="form-text">Généralement "localhost"</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="db_port" class="form-label">Port</label>
                                <input type="text" class="form-control" id="db_port" name="db_port" value="<?= $defaultConfig['port'] ?? '3306' ?>">
                                <div class="form-text">Port MySQL (défaut: 3306)</div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="db_name" class="form-label">Nom de la base de données <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="db_name" name="db_name" value="<?= $defaultConfig['database'] ?? 'monbudget_v2' ?>" required>
                        <div class="form-text">La base de données sera créée si elle n'existe pas</div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="db_username" class="form-label">Nom d'utilisateur <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="db_username" name="db_username" value="<?= $defaultConfig['username'] ?? 'root' ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="db_password" class="form-label">Mot de passe</label>
                                <input type="password" class="form-control" id="db_password" name="db_password" value="<?= $defaultConfig['password'] ?? '' ?>">
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="db_driver" value="mysql">
                </form>
            </div>

            <div class="setup-footer">
                <div class="d-flex justify-content-between align-items-center gap-2">
                    <a href="<?= url('setup/welcome') ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Retour
                    </a>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-primary" id="testBtn">
                            <i class="bi bi-plug"></i> Tester la connexion
                        </button>
                        <button type="button" class="btn btn-primary" id="installBtn" disabled>
                            <i class="bi bi-database-add"></i> Installer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const testBtn = document.getElementById('testBtn');
        const installBtn = document.getElementById('installBtn');
        const dbForm = document.getElementById('dbForm');
        const alertsDiv = document.getElementById('alerts');

        testBtn.addEventListener('click', async () => {
            testBtn.disabled = true;
            testBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Test en cours...';
            alertsDiv.innerHTML = '';

            const formData = new FormData(dbForm);

            try {
                const response = await fetch('<?= url('setup/test-database') ?>', {
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
                    installBtn.disabled = false;
                } else {
                    alertsDiv.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle-fill"></i> ${result.message}
                            ${result.errors ? '<br><small>' + result.errors.join('<br>') + '</small>' : ''}
                        </div>
                    `;
                    installBtn.disabled = true;
                }
            } catch (error) {
                alertsDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle-fill"></i> Erreur de communication avec le serveur
                    </div>
                `;
                installBtn.disabled = true;
            }

            testBtn.disabled = false;
            testBtn.innerHTML = '<i class="bi bi-plug"></i> Tester la connexion';
        });

        installBtn.addEventListener('click', async () => {
            if (!confirm('Êtes-vous sûr de vouloir installer la base de données ?')) {
                return;
            }

            installBtn.disabled = true;
            installBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Installation...';
            alertsDiv.innerHTML = '';

            const formData = new FormData(dbForm);

            try {
                const response = await fetch('<?= url('setup/install-database') ?>', {
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
                        window.location.href = '<?= url('setup/admin') ?>';
                    }, 1500);
                } else {
                    alertsDiv.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle-fill"></i> ${result.message}
                            ${result.errors ? '<br><small>' + result.errors.join('<br>') + '</small>' : ''}
                        </div>
                    `;
                    installBtn.disabled = false;
                    installBtn.innerHTML = '<i class="bi bi-database-add"></i> Installer';
                }
            } catch (error) {
                alertsDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle-fill"></i> Erreur de communication avec le serveur
                    </div>
                `;
                installBtn.disabled = false;
                installBtn.innerHTML = '<i class="bi bi-database-add"></i> Installer';
            }
        });
    </script>
</body>
</html>
