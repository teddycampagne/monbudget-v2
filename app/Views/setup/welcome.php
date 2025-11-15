<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation - Bienvenue</title>
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
                    <div class="step active">
                        <div class="step-circle">1</div>
                        <div class="step-label">Vérifications</div>
                    </div>
                    <div class="step">
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

                <h2>Bienvenue dans MonBudget v2.0</h2>
                <p class="lead">Cet assistant va vous guider dans l'installation de votre application de gestion budgétaire.</p>

                <div class="alert alert-info">
                    <i class="bi bi-info-circle-fill"></i>
                    L'installation ne prendra que quelques minutes. Assurez-vous d'avoir vos informations de base de données à portée de main.
                </div>

                <h3>Vérification des prérequis</h3>

                <div class="requirements">
                    <h5>Configuration PHP</h5>
                    <?php foreach ($requirements as $req): ?>
                        <div class="requirement-item <?= $req['status'] ? 'success' : 'error' ?>">
                            <i class="bi <?= $req['status'] ? 'bi-check-circle-fill' : 'bi-x-circle-fill' ?> requirement-icon <?= $req['status'] ? 'success' : 'error' ?>"></i>
                            <div class="flex-grow-1">
                                <strong><?= htmlspecialchars($req['name']) ?></strong>
                                <div class="small text-muted"><?= htmlspecialchars($req['current']) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="requirements">
                    <h5>Permissions des dossiers</h5>
                    <?php foreach ($permissions as $perm): ?>
                        <div class="requirement-item <?= $perm['status'] ? 'success' : 'error' ?>">
                            <i class="bi <?= $perm['status'] ? 'bi-check-circle-fill' : 'bi-x-circle-fill' ?> requirement-icon <?= $perm['status'] ? 'success' : 'error' ?>"></i>
                            <div class="flex-grow-1">
                                <strong><?= htmlspecialchars($perm['path']) ?></strong>
                                <div class="small text-muted">Doit être accessible en écriture</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if (!$allRequirementsMet): ?>
                    <div class="alert alert-danger mt-4">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        Certains prérequis ne sont pas satisfaits. Veuillez les corriger avant de continuer.
                    </div>
                <?php endif; ?>
            </div>

            <div class="setup-footer">
                <div class="d-flex justify-content-between align-items-center">
                    <div></div>
                    <?php if ($allRequirementsMet): ?>
                        <a href="<?= url('setup/database') ?>" class="btn btn-primary">
                            Continuer <i class="bi bi-arrow-right"></i>
                        </a>
                    <?php else: ?>
                        <button class="btn btn-primary" disabled>
                            Continuer <i class="bi bi-arrow-right"></i>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
