<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Réinitialiser les mots de passe') ?> - MonBudget</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body>
    <?php require __DIR__ . '/../layouts/header.php'; ?>

    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col">
                <h1 class="h3 mb-0">
                    <i class="bi bi-key"></i> Réinitialiser les mots de passe
                </h1>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <h5 class="alert-heading">
                                <i class="bi bi-exclamation-triangle"></i> Attention !
                            </h5>
                            <p class="mb-0">
                                Cette action réinitialisera le mot de passe des utilisateurs sélectionnés.
                                Ils devront utiliser le nouveau mot de passe pour se connecter.
                            </p>
                        </div>

                        <form method="POST" action="<?= url('admin/users/reset-passwords/process') ?>" id="resetPasswordsForm">
                            <?= csrf_field() ?>
                            
                            <div class="mb-4">
                                <label class="form-label fw-bold">
                                    Sélectionner les utilisateurs <span class="text-danger">*</span>
                                </label>
                                
                                <?php if (!empty($users)): ?>
                                    <div class="mb-2">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="selectAll()">
                                            <i class="bi bi-check-square"></i> Tout sélectionner
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deselectAll()">
                                            <i class="bi bi-square"></i> Tout désélectionner
                                        </button>
                                    </div>
                                    
                                    <div class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                                        <?php foreach ($users as $user): ?>
                                            <div class="form-check">
                                                <input class="form-check-input user-checkbox" 
                                                       type="checkbox" 
                                                       name="user_ids[]" 
                                                       value="<?= $user['id'] ?>" 
                                                       id="user_<?= $user['id'] ?>"
                                                       <?= !$user['is_active'] ? 'disabled' : '' ?>>
                                                <label class="form-check-label" for="user_<?= $user['id'] ?>">
                                                    <strong><?= htmlspecialchars($user['username']) ?></strong>
                                                    <small class="text-muted">
                                                        (<?= htmlspecialchars($user['email']) ?>)
                                                    </small>
                                                    <?php if ($user['role'] === 'admin'): ?>
                                                        <span class="badge bg-primary">Admin</span>
                                                    <?php endif; ?>
                                                    <?php if (!$user['is_active']): ?>
                                                        <span class="badge bg-warning">Inactif</span>
                                                    <?php endif; ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <small class="text-muted">
                                        Les comptes inactifs ne peuvent pas être sélectionnés
                                    </small>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle"></i> Aucun utilisateur disponible
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mb-4">
                                <label for="new_password" class="form-label fw-bold">
                                    Nouveau mot de passe <span class="text-danger">*</span>
                                </label>
                                <input type="password" 
                                       class="form-control" 
                                       id="new_password" 
                                       name="new_password" 
                                       required
                                       minlength="8">
                                <div class="form-text">
                                    Au moins 8 caractères. Ce mot de passe sera appliqué à tous les utilisateurs sélectionnés.
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="confirm_password" class="form-label fw-bold">
                                    Confirmer le mot de passe <span class="text-danger">*</span>
                                </label>
                                <input type="password" 
                                       class="form-control" 
                                       id="confirm_password" 
                                       name="confirm_password" 
                                       required
                                       minlength="8">
                            </div>
                            
                            <div class="form-check mb-4">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="confirm_reset" 
                                       required>
                                <label class="form-check-label text-danger fw-bold" for="confirm_reset">
                                    Je confirme vouloir réinitialiser les mots de passe des utilisateurs sélectionnés
                                </label>
                            </div>
                            
                            <div class="border-top pt-3">
                                <button type="submit" class="btn btn-danger">
                                    <i class="bi bi-key"></i> Réinitialiser les mots de passe
                                </button>
                                <a href="<?= url('admin/users') ?>" class="btn btn-secondary ms-2">
                                    <i class="bi bi-x-circle"></i> Annuler
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-info-circle"></i> Informations
                        </h5>
                    </div>
                    <div class="card-body">
                        <h6>Sécurité</h6>
                        <ul class="small mb-3">
                            <li>Le mot de passe sera chiffré avec ARGON2ID</li>
                            <li>Le compte UserFirst est protégé</li>
                            <li>Les comptes inactifs ne peuvent pas être modifiés</li>
                        </ul>
                        
                        <h6>Recommandations</h6>
                        <ul class="small mb-0">
                            <li>Utilisez un mot de passe fort (8+ caractères)</li>
                            <li>Communiquez le nouveau mot de passe aux utilisateurs de manière sécurisée</li>
                            <li>Demandez aux utilisateurs de changer leur mot de passe dès la première connexion</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function selectAll() {
        document.querySelectorAll('.user-checkbox:not(:disabled)').forEach(cb => cb.checked = true);
    }
    
    function deselectAll() {
        document.querySelectorAll('.user-checkbox').forEach(cb => cb.checked = false);
    }
    
    document.getElementById('resetPasswordsForm')?.addEventListener('submit', function(e) {
        const password = document.getElementById('new_password').value;
        const confirm = document.getElementById('confirm_password').value;
        
        if (password !== confirm) {
            e.preventDefault();
            alert('Les mots de passe ne correspondent pas !');
            return false;
        }
        
        const checked = document.querySelectorAll('.user-checkbox:checked').length;
        if (checked === 0) {
            e.preventDefault();
            alert('Veuillez sélectionner au moins un utilisateur !');
            return false;
        }
        
        if (!confirm(`Êtes-vous sûr de vouloir réinitialiser le mot de passe de ${checked} utilisateur(s) ?`)) {
            e.preventDefault();
            return false;
        }
    });
    </script>

    <?php require __DIR__ . '/../layouts/footer.php'; ?>
</body>
</html>
