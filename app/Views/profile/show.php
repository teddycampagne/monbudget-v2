<?php
/**
 * Vue: Profil Utilisateur
 * 
 * Affiche les informations du profil utilisateur avec statut du mot de passe
 */
?>

<?php require_once BASE_PATH . '/app/Views/layouts/header.php'; ?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-lg-8 offset-lg-2">
            
            <!-- En-tête -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>
                    <i class="bi bi-person-circle"></i>
                    Mon Profil
                </h1>
                <a href="<?= url('dashboard') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Retour
                </a>
            </div>

            <!-- Alertes Sécurité -->
            <?php if (isset($isPasswordExpired) && $isPasswordExpired): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <strong>Mot de passe expiré</strong> - Votre mot de passe a expiré. 
                    Vous devez le changer immédiatement.
                    <a href="<?= url('change-password') ?>" class="alert-link">Changer maintenant</a>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php elseif (isset($daysUntilExpiration) && $daysUntilExpiration !== null && $daysUntilExpiration < 7): ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="bi bi-clock-history"></i>
                    <strong>Attention</strong> - Votre mot de passe expire dans 
                    <strong><?= $daysUntilExpiration ?> jour<?= $daysUntilExpiration > 1 ? 's' : '' ?></strong>.
                    <a href="<?= url('change-password') ?>" class="alert-link">Changer maintenant</a>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Informations Utilisateur -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-person-badge"></i>
                        Informations Personnelles
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= url('profile') ?>" id="profileForm">
                        <?= csrf_field() ?>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="username" class="form-label">
                                    <i class="bi bi-person"></i> Nom d'utilisateur
                                </label>
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    id="username" 
                                    name="username" 
                                    value="<?= htmlspecialchars($user['username'] ?? '') ?>"
                                    required
                                    minlength="3"
                                    maxlength="50"
                                >
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">
                                    <i class="bi bi-envelope"></i> Email
                                </label>
                                <input 
                                    type="email" 
                                    class="form-control" 
                                    id="email" 
                                    name="email" 
                                    value="<?= htmlspecialchars($user['email'] ?? '') ?>"
                                    required
                                >
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="bi bi-shield-check"></i> Rôle
                                </label>
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    value="<?= htmlspecialchars(ucfirst($user['role'] ?? 'user')) ?>"
                                    disabled
                                >
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="bi bi-calendar-check"></i> Membre depuis
                                </label>
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    value="<?= date('d/m/Y', strtotime($user['created_at'] ?? '')) ?>"
                                    disabled
                                >
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <button type="reset" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Annuler
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Enregistrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Sécurité -->
            <div class="card mb-4">
                <div class="card-header bg-warning">
                    <h5 class="mb-0">
                        <i class="bi bi-shield-lock"></i>
                        Sécurité
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <h6><i class="bi bi-key"></i> Mot de passe</h6>
                            <p class="text-muted mb-2">
                                Dernière modification : 
                                <?php if (isset($user['last_password_change']) && $user['last_password_change']): ?>
                                    <strong><?= date('d/m/Y à H:i', strtotime($user['last_password_change'])) ?></strong>
                                <?php else: ?>
                                    <strong class="text-warning">Jamais</strong>
                                <?php endif; ?>
                            </p>
                            
                            <?php if (isset($daysUntilExpiration) && $daysUntilExpiration !== null): ?>
                                <?php if ($daysUntilExpiration > 0): ?>
                                    <p class="mb-0">
                                        <i class="bi bi-hourglass-split text-<?= $daysUntilExpiration < 7 ? 'warning' : 'success' ?>"></i>
                                        Expire dans <strong><?= $daysUntilExpiration ?> jours</strong>
                                    </p>
                                <?php else: ?>
                                    <p class="mb-0 text-danger">
                                        <i class="bi bi-exclamation-triangle-fill"></i>
                                        <strong>Expiré</strong> - Changement requis
                                    </p>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-4 text-end">
                            <a href="<?= url('change-password') ?>" class="btn btn-warning">
                                <i class="bi bi-key-fill"></i>
                                Changer le mot de passe
                            </a>
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-12">
                            <h6><i class="bi bi-info-circle"></i> Exigences de sécurité (PCI DSS)</h6>
                            <ul class="text-muted small mb-0">
                                <li>Longueur minimum : <strong>12 caractères</strong></li>
                                <li>Complexité : majuscule + minuscule + chiffre + caractère spécial</li>
                                <li>Historique : les 5 derniers mots de passe ne peuvent pas être réutilisés</li>
                                <li>Expiration : tous les <strong>90 jours</strong></li>
                                <li>Verrouillage automatique après 5 tentatives échouées</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Activité -->
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-clock-history"></i>
                        Activité du Compte
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-4">
                            <div class="p-3 border rounded">
                                <i class="bi bi-calendar-check fs-3 text-primary"></i>
                                <h6 class="mt-2">Membre depuis</h6>
                                <p class="mb-0">
                                    <?= date('d/m/Y', strtotime($user['created_at'] ?? '')) ?>
                                </p>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="p-3 border rounded">
                                <i class="bi bi-shield-check fs-3 text-success"></i>
                                <h6 class="mt-2">Dernier changement MDP</h6>
                                <p class="mb-0">
                                    <?php if (isset($user['last_password_change']) && $user['last_password_change']): ?>
                                        <?= date('d/m/Y', strtotime($user['last_password_change'])) ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="p-3 border rounded">
                                <i class="bi bi-hourglass-split fs-3 text-warning"></i>
                                <h6 class="mt-2">Expiration MDP</h6>
                                <p class="mb-0">
                                    <?php if (isset($daysUntilExpiration) && $daysUntilExpiration !== null): ?>
                                        <?php if ($daysUntilExpiration > 0): ?>
                                            Dans <?= $daysUntilExpiration ?> jours
                                        <?php else: ?>
                                            <span class="text-danger">Expiré</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once BASE_PATH . '/app/Views/layouts/footer.php'; ?>
