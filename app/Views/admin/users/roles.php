<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Gestion des rôles') ?> - MonBudget</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body>
    <?php require __DIR__ . '/../../layouts/header.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <!-- En-tête -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h3">
                            <i class="bi bi-shield-lock"></i> Gestion des rôles
                        </h1>
                        <p class="text-muted mb-0">
                            Modifier les rôles des utilisateurs du système
                        </p>
                    </div>
                    <a href="<?= url('admin/users') ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Retour
                    </a>
                </div>

                <?php if (isset($_SESSION['flash'])): ?>
                    <?php foreach ($_SESSION['flash'] as $type => $message): ?>
                        <div class="alert alert-<?= $type === 'error' ? 'danger' : $type ?> alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($message) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endforeach; ?>
                    <?php unset($_SESSION['flash']); ?>
                <?php endif; ?>

                <div class="row">
                    <!-- Formulaire principal -->
                    <div class="col-lg-8">
                        <div class="card shadow-sm">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">
                                    <i class="bi bi-people"></i> Attribution des rôles
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="<?= url('admin/users/roles/update') ?>" id="rolesForm">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                    
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Utilisateur</th>
                                                    <th>Email</th>
                                                    <th>Rôle actuel</th>
                                                    <th>Nouveau rôle</th>
                                                    <th>Statut</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($users as $user): ?>
                                                    <tr class="<?= $user['username'] === 'UserFirst' ? 'table-warning' : '' ?>">
                                                        <td>
                                                            <strong><?= htmlspecialchars($user['username']) ?></strong>
                                                            <?php if ($user['username'] === 'UserFirst'): ?>
                                                                <span class="badge bg-warning text-dark ms-2">
                                                                    <i class="bi bi-shield-fill-exclamation"></i> Protégé
                                                                </span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?= htmlspecialchars($user['email']) ?></td>
                                                        <td>
                                                            <span class="badge bg-<?= $availableRoles[$user['role']]['color'] ?? 'secondary' ?>">
                                                                <?= htmlspecialchars($availableRoles[$user['role']]['label'] ?? $user['role']) ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <?php if ($user['username'] === 'UserFirst'): ?>
                                                                <select class="form-select form-select-sm" disabled>
                                                                    <option><?= htmlspecialchars($availableRoles[$user['role']]['label']) ?></option>
                                                                </select>
                                                                <small class="text-muted">Non modifiable</small>
                                                            <?php else: ?>
                                                                <select 
                                                                    name="roles[<?= $user['id'] ?>]" 
                                                                    class="form-select form-select-sm role-select"
                                                                    data-original="<?= htmlspecialchars($user['role']) ?>"
                                                                >
                                                                    <?php foreach ($availableRoles as $roleKey => $roleData): ?>
                                                                        <option 
                                                                            value="<?= htmlspecialchars($roleKey) ?>"
                                                                            <?= $user['role'] === $roleKey ? 'selected' : '' ?>
                                                                        >
                                                                            <?= htmlspecialchars($roleData['label']) ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if ($user['is_active']): ?>
                                                                <span class="badge bg-success">
                                                                    <i class="bi bi-check-circle"></i> Actif
                                                                </span>
                                                            <?php else: ?>
                                                                <span class="badge bg-danger">
                                                                    <i class="bi bi-x-circle"></i> Inactif
                                                                </span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="alert alert-info mt-3">
                                        <i class="bi bi-info-circle"></i>
                                        <strong>Information :</strong> Seuls les rôles modifiés seront mis à jour. 
                                        Le compte <strong>UserFirst</strong> ne peut pas être modifié.
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center mt-4">
                                        <button type="button" class="btn btn-secondary" id="resetBtn">
                                            <i class="bi bi-arrow-counterclockwise"></i> Réinitialiser
                                        </button>
                                        <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                                            <i class="bi bi-check-lg"></i> Enregistrer les modifications
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Panneau d'information -->
                    <div class="col-lg-4">
                        <div class="card shadow-sm mb-3">
                            <div class="card-header bg-info text-white">
                                <h6 class="mb-0">
                                    <i class="bi bi-info-circle"></i> Rôles disponibles
                                </h6>
                            </div>
                            <div class="card-body">
                                <?php foreach ($availableRoles as $roleKey => $roleData): ?>
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center mb-1">
                                            <span class="badge bg-<?= $roleData['color'] ?> me-2">
                                                <?= htmlspecialchars($roleData['label']) ?>
                                            </span>
                                        </div>
                                        <p class="text-muted small mb-0">
                                            <?= htmlspecialchars($roleData['description']) ?>
                                        </p>
                                    </div>
                                    <?php if ($roleKey !== array_key_last($availableRoles)): ?>
                                        <hr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="card shadow-sm border-warning">
                            <div class="card-header bg-warning">
                                <h6 class="mb-0">
                                    <i class="bi bi-exclamation-triangle"></i> Attention
                                </h6>
                            </div>
                            <div class="card-body">
                                <ul class="small mb-0">
                                    <li>Le changement de rôle est <strong>immédiat</strong></li>
                                    <li>Les administrateurs ont accès à toutes les fonctionnalités</li>
                                    <li>Les utilisateurs simples ne peuvent pas accéder à l'administration</li>
                                    <li>Le compte <strong>UserFirst</strong> ne peut jamais être modifié</li>
                                </ul>
                            </div>
                        </div>

                        <div class="card shadow-sm border-success mt-3">
                            <div class="card-header bg-success text-white">
                                <h6 class="mb-0">
                                    <i class="bi bi-shield-check"></i> Sécurité
                                </h6>
                            </div>
                            <div class="card-body">
                                <p class="small mb-2">
                                    <i class="bi bi-check2"></i> Protection du compte système
                                </p>
                                <p class="small mb-2">
                                    <i class="bi bi-check2"></i> Validation CSRF
                                </p>
                                <p class="small mb-0">
                                    <i class="bi bi-check2"></i> Journalisation des modifications
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php require __DIR__ . '/../../layouts/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Gestion du formulaire de rôles
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('rolesForm');
            const submitBtn = document.getElementById('submitBtn');
            const resetBtn = document.getElementById('resetBtn');
            const selects = document.querySelectorAll('.role-select');
            
            // Vérifier s'il y a des modifications
            function checkChanges() {
                let hasChanges = false;
                
                selects.forEach(select => {
                    if (select.value !== select.dataset.original) {
                        hasChanges = true;
                    }
                });
                
                submitBtn.disabled = !hasChanges;
                
                // Compter les modifications
                if (hasChanges) {
                    let count = 0;
                    selects.forEach(select => {
                        if (select.value !== select.dataset.original) count++;
                    });
                    submitBtn.innerHTML = `<i class="bi bi-check-lg"></i> Enregistrer ${count} modification(s)`;
                } else {
                    submitBtn.innerHTML = `<i class="bi bi-check-lg"></i> Enregistrer les modifications`;
                }
            }
            
            // Écouter les changements
            selects.forEach(select => {
                select.addEventListener('change', checkChanges);
            });
            
            // Bouton de réinitialisation
            resetBtn.addEventListener('click', function() {
                selects.forEach(select => {
                    select.value = select.dataset.original;
                });
                checkChanges();
            });
            
            // Confirmation avant envoi
            form.addEventListener('submit', function(e) {
                let count = 0;
                selects.forEach(select => {
                    if (select.value !== select.dataset.original) count++;
                });
                
                if (count === 0) {
                    e.preventDefault();
                    return;
                }
                
                if (!confirm(`Êtes-vous sûr de vouloir modifier ${count} rôle(s) ?\n\nCette action prendra effet immédiatement.`)) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>
