<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Éditer un utilisateur') ?> - MonBudget</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body>
    <?php require __DIR__ . '/../../layouts/header.php'; ?>

    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col">
                <h1 class="h3 mb-0">
                    <i class="bi bi-pencil"></i> Éditer l'utilisateur : <?= htmlspecialchars($user['username']) ?>
                </h1>
            </div>
        </div>

        <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="POST" action="<?= url('admin/users/' . $user['id'] . '/update') ?>">
                        <?= csrf_field() ?>
                        
                        <div class="mb-3">
                            <label for="username" class="form-label">Nom d'utilisateur</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="username" 
                                   value="<?= htmlspecialchars($user['username']) ?>" 
                                   disabled>
                            <div class="form-text">Le nom d'utilisateur ne peut pas être modifié</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" 
                                   class="form-control" 
                                   id="email" 
                                   name="email" 
                                   value="<?= old('email', $user['email']) ?>" 
                                   required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="role" class="form-label">Rôle <span class="text-danger">*</span></label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>Utilisateur</option>
                                <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Administrateur</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="is_active" 
                                       name="is_active" 
                                       <?= $user['is_active'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_active">
                                    Compte actif
                                </label>
                                <div class="form-text">Un compte inactif ne peut pas se connecter</div>
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        
                        <h5 class="mb-3">Changer le mot de passe</h5>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">Nouveau mot de passe</label>
                            <input type="password" 
                                   class="form-control" 
                                   id="new_password" 
                                   name="new_password">
                            <div class="form-text">Laisser vide pour conserver le mot de passe actuel. Au moins 8 caractères si renseigné.</div>
                        </div>
                        
                        <div class="border-top pt-3 mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Enregistrer les modifications
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
                    <dl class="mb-0">
                        <dt>ID</dt>
                        <dd><?= htmlspecialchars($user['id']) ?></dd>
                        
                        <dt>Créé le</dt>
                        <dd><?= date('d/m/Y à H:i', strtotime($user['created_at'])) ?></dd>
                        
                        <dt>Dernière connexion</dt>
                        <dd>
                            <?php if (!empty($user['updated_at'])): ?>
                                <?= date('d/m/Y à H:i', strtotime($user['updated_at'])) ?>
                            <?php else: ?>
                                <em>Jamais connecté</em>
                            <?php endif; ?>
                        </dd>
                    </dl>
                </div>
            </div>
            
            <div class="card shadow-sm mt-3">
                <div class="card-header bg-danger text-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-exclamation-triangle"></i> Zone de danger
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" 
                          action="<?= url('admin/users/' . $user['id'] . '/delete') ?>"
                          onsubmit="return confirm('Êtes-vous VRAIMENT sûr de vouloir supprimer cet utilisateur ?\n\nCette action est IRRÉVERSIBLE et supprimera également :\n- Tous ses comptes\n- Toutes ses transactions\n- Tous ses budgets\n- Toutes ses données\n\nTapez OUI pour confirmer');">
                        <?= csrf_field() ?>
                        <p class="small mb-2">
                            Suppression définitive de l'utilisateur et de toutes ses données.
                        </p>
                        <button type="submit" class="btn btn-danger btn-sm">
                            <i class="bi bi-trash"></i> Supprimer cet utilisateur
                        </button>
                    </form>
                </div>
            </div>
        </div>
        </div>
    </div>

    <?php require __DIR__ . '/../../layouts/footer.php'; ?>
</body>
</html>
