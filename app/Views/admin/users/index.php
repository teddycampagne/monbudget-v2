<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Utilisateurs') ?> - MonBudget</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body>
    <?php require __DIR__ . '/../../layouts/header.php'; ?>

    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col">
                <h1 class="h3 mb-0">
                    <i class="bi bi-people"></i> Gestion des utilisateurs
                </h1>
            </div>
            <div class="col-auto">
                <a href="<?= url('admin/users/create') ?>" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Créer un utilisateur
                </a>
            </div>
        </div>

        <?php if (!empty($users)): ?>
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nom d'utilisateur</th>
                                <th>Email</th>
                                <th>Rôle</th>
                                <th>Statut</th>
                                <th>Créé le</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= htmlspecialchars($user['id']) ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($user['username']) ?></strong>
                                        <?php if ($user['username'] === 'UserFirst'): ?>
                                            <span class="badge bg-danger ms-1">Super-Admin</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td>
                                        <?php if ($user['role'] === 'admin'): ?>
                                            <span class="badge bg-primary">Admin</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Utilisateur</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($user['is_active']): ?>
                                            <span class="badge bg-success">Actif</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">Inactif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($user['created_at'])) ?></td>
                                    <td class="text-end">
                                        <?php if ($user['username'] !== 'UserFirst'): ?>
                                            <a href="<?= url('admin/users/' . $user['id'] . '/edit') ?>" 
                                               class="btn btn-sm btn-outline-primary" 
                                               title="Éditer">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            
                                            <form method="POST" 
                                                  action="<?= url('admin/users/' . $user['id'] . '/delete') ?>" 
                                                  class="d-inline"
                                                  onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ? Cette action est irréversible.');">
                                                <?= csrf_field() ?>
                                                <button type="submit" 
                                                        class="btn btn-sm btn-outline-danger" 
                                                        title="Supprimer">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted small">
                                                <i class="bi bi-lock"></i> Protégé
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> Aucun utilisateur trouvé.
            </div>
        <?php endif; ?>

        <div class="mt-3">
            <a href="<?= url('admin') ?>" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Retour au tableau de bord
            </a>
        </div>
    </div>

    <?php require __DIR__ . '/../../layouts/footer.php'; ?>
</body>
</html>
