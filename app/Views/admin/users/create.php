<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Créer un utilisateur') ?> - MonBudget</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body>
    <?php require __DIR__ . '/../../layouts/header.php'; ?>

    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col">
                <h1 class="h3 mb-0">
                    <i class="bi bi-person-plus"></i> Créer un nouvel utilisateur
                </h1>
            </div>
        </div>

        <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="POST" action="<?= url('admin/users/store') ?>">
                        <?= csrf_field() ?>
                        
                        <div class="mb-3">
                            <label for="username" class="form-label">Nom d'utilisateur <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control" 
                                   id="username" 
                                   name="username" 
                                   value="<?= old('username') ?>" 
                                   required>
                            <div class="form-text">Sera utilisé pour la connexion</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" 
                                   class="form-control" 
                                   id="email" 
                                   name="email" 
                                   value="<?= old('email') ?>" 
                                   required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Mot de passe <span class="text-danger">*</span></label>
                            <input type="password" 
                                   class="form-control" 
                                   id="password" 
                                   name="password" 
                                   required>
                            <div class="form-text">Au moins 8 caractères</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="role" class="form-label">Rôle <span class="text-danger">*</span></label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="user" <?= old('role') === 'user' ? 'selected' : '' ?>>Utilisateur</option>
                                <option value="admin" <?= old('role') === 'admin' ? 'selected' : '' ?>>Administrateur</option>
                            </select>
                            <div class="form-text">
                                <strong>Utilisateur :</strong> Accès aux fonctionnalités de base<br>
                                <strong>Administrateur :</strong> Accès à la gestion des utilisateurs et à la maintenance
                            </div>
                        </div>
                        
                        <div class="border-top pt-3 mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Créer l'utilisateur
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
                    <h6>Rôles disponibles</h6>
                    <ul class="small">
                        <li><strong>Utilisateur :</strong> Peut gérer ses comptes, transactions, budgets</li>
                        <li><strong>Administrateur :</strong> Peut gérer les utilisateurs et effectuer la maintenance</li>
                    </ul>
                    
                    <hr>
                    
                    <h6>Sécurité</h6>
                    <p class="small mb-0">
                        Le mot de passe sera chiffré avec ARGON2ID, l'algorithme de hachage le plus sécurisé.
                    </p>
                </div>
            </div>
        </div>
        </div>
    </div>

    <?php require __DIR__ . '/../../layouts/footer.php'; ?>
</body>
</html>
