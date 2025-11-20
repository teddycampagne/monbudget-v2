<?php
/**
 * Vue: Utilisateurs verrouillés (Admin)
 * 
 * Affiche la liste des utilisateurs avec tentatives de connexion échouées
 * ou comptes verrouillés, avec possibilité de déverrouillage.
 */
?>

<?php require_once BASE_PATH . '/app/Views/layouts/header.php'; ?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            
            <!-- En-tête -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>
                    <i class="bi bi-shield-lock-fill text-danger"></i>
                    Utilisateurs verrouillés
                </h1>
                <div>
                    <a href="<?= url('admin/users') ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-people"></i> Tous les utilisateurs
                    </a>
                    <a href="<?= url('admin') ?>" class="btn btn-outline-primary">
                        <i class="bi bi-arrow-left"></i> Retour Admin
                    </a>
                </div>
            </div>

            <!-- Alerte Info PCI DSS -->
            <div class="alert alert-info">
                <h5 class="alert-heading">
                    <i class="bi bi-info-circle"></i> Politique de sécurité (PCI DSS 8.1.6)
                </h5>
                <ul class="mb-0">
                    <li>Les comptes sont <strong>verrouillés après 5 tentatives échouées</strong></li>
                    <li>Le verrouillage dure <strong>15 minutes</strong> (automatique) ou jusqu'au déverrouillage manuel</li>
                    <li>Les tentatives échouées sont réinitialisées après une connexion réussie</li>
                    <li>Tous les événements sont enregistrés dans les logs d'audit</li>
                </ul>
            </div>

            <?php if (empty($users)): ?>
                <!-- Aucun utilisateur verrouillé -->
                <div class="alert alert-success">
                    <i class="bi bi-check-circle-fill"></i>
                    <strong>Aucun utilisateur verrouillé</strong> - Tous les comptes sont en bon état
                </div>
            <?php else: ?>
                <!-- Tableau des utilisateurs -->
                <div class="card">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            Utilisateurs avec problèmes de sécurité
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Statut</th>
                                        <th>Utilisateur</th>
                                        <th>Email</th>
                                        <th>Rôle</th>
                                        <th>Tentatives</th>
                                        <th>Verrouillé jusqu'à</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <?php
                                            $isLocked = $user['status'] === 'locked';
                                            $isSuspicious = $user['status'] === 'suspicious';
                                            $badgeClass = $isLocked ? 'danger' : ($isSuspicious ? 'warning' : 'secondary');
                                            $badgeIcon = $isLocked ? 'lock-fill' : ($isSuspicious ? 'exclamation-triangle-fill' : 'check-circle');
                                            $badgeText = $isLocked ? 'Verrouillé' : ($isSuspicious ? 'Suspect' : 'OK');
                                        ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-<?= $badgeClass ?>">
                                                    <i class="bi bi-<?= $badgeIcon ?>"></i>
                                                    <?= $badgeText ?>
                                                </span>
                                            </td>
                                            <td>
                                                <strong><?= htmlspecialchars($user['username']) ?></strong>
                                            </td>
                                            <td><?= htmlspecialchars($user['email']) ?></td>
                                            <td>
                                                <span class="badge bg-<?= $user['role'] === 'admin' ? 'primary' : 'secondary' ?>">
                                                    <?= htmlspecialchars(ucfirst($user['role'])) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $user['failed_login_attempts'] >= 5 ? 'danger' : 'warning' ?>">
                                                    <?= $user['failed_login_attempts'] ?> / 5
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($user['locked_until']): ?>
                                                    <?php
                                                        $lockedUntil = new DateTime($user['locked_until']);
                                                        $now = new DateTime();
                                                        $isStillLocked = $lockedUntil > $now;
                                                    ?>
                                                    <span class="<?= $isStillLocked ? 'text-danger fw-bold' : 'text-muted' ?>">
                                                        <?= $lockedUntil->format('d/m/Y H:i') ?>
                                                        <?php if ($isStillLocked): ?>
                                                            <br><small>(<?= $now->diff($lockedUntil)->format('%i min') ?> restantes)</small>
                                                        <?php else: ?>
                                                            <br><small>(expiré)</small>
                                                        <?php endif; ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <?php if ($user['failed_login_attempts'] > 0 || $user['locked_until']): ?>
                                                    <form method="POST" action="<?= url('admin/users/unlock') ?>" style="display:inline;">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                        <button 
                                                            type="submit" 
                                                            class="btn btn-sm btn-success"
                                                            onclick="return confirm('Déverrouiller le compte de <?= htmlspecialchars($user['username']) ?> ?')"
                                                        >
                                                            <i class="bi bi-unlock-fill"></i>
                                                            Déverrouiller
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Statistiques -->
            <div class="row mt-4">
                <div class="col-md-4">
                    <div class="card border-danger">
                        <div class="card-body text-center">
                            <i class="bi bi-lock-fill text-danger fs-1"></i>
                            <h3 class="mt-2">
                                <?= count(array_filter($users, fn($u) => $u['status'] === 'locked')) ?>
                            </h3>
                            <p class="text-muted mb-0">Comptes verrouillés</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-warning">
                        <div class="card-body text-center">
                            <i class="bi bi-exclamation-triangle-fill text-warning fs-1"></i>
                            <h3 class="mt-2">
                                <?= count(array_filter($users, fn($u) => $u['status'] === 'suspicious')) ?>
                            </h3>
                            <p class="text-muted mb-0">Comptes suspects</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-info">
                        <div class="card-body text-center">
                            <i class="bi bi-people-fill text-info fs-1"></i>
                            <h3 class="mt-2"><?= count($users) ?></h3>
                            <p class="text-muted mb-0">Total problèmes</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once BASE_PATH . '/app/Views/layouts/footer.php'; ?>
