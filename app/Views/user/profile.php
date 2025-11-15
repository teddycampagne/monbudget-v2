<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid px-4 py-4">
    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0"><i class="bi bi-person-circle"></i> Mon Profil</h1>
            <p class="text-muted mb-0">Gérez vos informations personnelles et vos préférences</p>
        </div>
    </div>

    <div class="row">
        <!-- Onglets -->
        <div class="col-lg-8">
            <ul class="nav nav-tabs mb-3" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="infos-tab" data-bs-toggle="tab" data-bs-target="#infos" type="button" role="tab">
                        <i class="bi bi-person"></i> Informations personnelles
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab">
                        <i class="bi bi-shield-lock"></i> Sécurité
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="preferences-tab" data-bs-toggle="tab" data-bs-target="#preferences" type="button" role="tab">
                        <i class="bi bi-sliders"></i> Préférences
                    </button>
                </li>
            </ul>

            <div class="tab-content">
                <!-- Onglet Informations personnelles -->
                <div class="tab-pane fade show active" id="infos" role="tabpanel">
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Informations personnelles</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="<?= url('profile/update') ?>">
                                <?= csrf_field() ?>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="prenom" class="form-label">
                                            Prénom <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="prenom" 
                                               name="prenom" 
                                               value="<?= htmlspecialchars($user['prenom'] ?? '') ?>"
                                               required>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="nom" class="form-label">
                                            Nom <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="nom" 
                                               name="nom" 
                                               value="<?= htmlspecialchars($user['nom'] ?? '') ?>"
                                               required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">
                                        Email <span class="text-danger">*</span>
                                    </label>
                                    <input type="email" 
                                           class="form-control" 
                                           id="email" 
                                           name="email" 
                                           value="<?= htmlspecialchars($user['email'] ?? '') ?>"
                                           required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="username" class="form-label">
                                        Nom d'utilisateur
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="username" 
                                           value="<?= htmlspecialchars($user['username'] ?? '') ?>"
                                           disabled
                                           readonly>
                                    <small class="text-muted">Le nom d'utilisateur ne peut pas être modifié</small>
                                </div>
                                
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="<?= url('/') ?>" class="btn btn-secondary">
                                        <i class="bi bi-x-lg"></i> Annuler
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-check-lg"></i> Enregistrer
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Onglet Sécurité -->
                <div class="tab-pane fade" id="security" role="tabpanel">
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Changer le mot de passe</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="<?= url('profile/password') ?>" id="passwordForm">
                                <?= csrf_field() ?>
                                
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i>
                                    Utilisez un mot de passe fort avec au moins 8 caractères, incluant majuscules, minuscules et chiffres.
                                </div>
                                
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">
                                        Mot de passe actuel <span class="text-danger">*</span>
                                    </label>
                                    <input type="password" 
                                           class="form-control" 
                                           id="current_password" 
                                           name="current_password"
                                           required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">
                                        Nouveau mot de passe <span class="text-danger">*</span>
                                    </label>
                                    <input type="password" 
                                           class="form-control" 
                                           id="new_password" 
                                           name="new_password"
                                           minlength="8"
                                           required>
                                    <small class="text-muted">Minimum 8 caractères</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">
                                        Confirmer le nouveau mot de passe <span class="text-danger">*</span>
                                    </label>
                                    <input type="password" 
                                           class="form-control" 
                                           id="confirm_password" 
                                           name="confirm_password"
                                           minlength="8"
                                           required>
                                </div>
                                
                                <div id="password-error" class="alert alert-danger d-none">
                                    Les mots de passe ne correspondent pas.
                                </div>
                                
                                <div class="d-flex justify-content-end gap-2">
                                    <button type="reset" class="btn btn-secondary">
                                        <i class="bi bi-x-lg"></i> Réinitialiser
                                    </button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-shield-check"></i> Changer le mot de passe
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Onglet Préférences -->
                <div class="tab-pane fade" id="preferences" role="tabpanel">
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Préférences de l'application</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="<?= url('profile/preferences') ?>">
                                <?= csrf_field() ?>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="langue" class="form-label">
                                            <i class="bi bi-translate"></i> Langue
                                        </label>
                                        <select class="form-select" id="langue" name="langue">
                                            <option value="fr" selected>Français</option>
                                            <option value="en">English</option>
                                            <option value="es">Español</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="devise" class="form-label">
                                            <i class="bi bi-currency-euro"></i> Devise par défaut
                                        </label>
                                        <select class="form-select" id="devise" name="devise">
                                            <option value="EUR" selected>Euro (€)</option>
                                            <option value="USD">Dollar ($)</option>
                                            <option value="GBP">Livre (£)</option>
                                            <option value="CHF">Franc Suisse (CHF)</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="format_date" class="form-label">
                                            <i class="bi bi-calendar"></i> Format de date
                                        </label>
                                        <select class="form-select" id="format_date" name="format_date">
                                            <option value="d/m/Y" selected>JJ/MM/AAAA</option>
                                            <option value="Y-m-d">AAAA-MM-JJ</option>
                                            <option value="m/d/Y">MM/JJ/AAAA</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="theme" class="form-label">
                                            <i class="bi bi-palette"></i> Thème
                                        </label>
                                        <select class="form-select" id="theme" name="theme">
                                            <option value="light" selected>Clair</option>
                                            <option value="dark">Sombre</option>
                                            <option value="auto">Automatique</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="bi bi-bell"></i> Notifications
                                    </label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="notif_transactions" name="notif_transactions" checked>
                                        <label class="form-check-label" for="notif_transactions">
                                            Nouvelles transactions
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="notif_budgets" name="notif_budgets" checked>
                                        <label class="form-check-label" for="notif_budgets">
                                            Dépassements de budget
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="notif_recurrences" name="notif_recurrences">
                                        <label class="form-check-label" for="notif_recurrences">
                                            Rappels des transactions récurrentes
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-end gap-2">
                                    <button type="reset" class="btn btn-secondary">
                                        <i class="bi bi-x-lg"></i> Réinitialiser
                                    </button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-check-lg"></i> Enregistrer
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Informations compte -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Informations du compte</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-6">Statut :</dt>
                        <dd class="col-sm-6">
                            <?php if ($user['is_active']): ?>
                                <span class="badge bg-success">
                                    <i class="bi bi-check-circle"></i> Actif
                                </span>
                            <?php else: ?>
                                <span class="badge bg-danger">
                                    <i class="bi bi-x-circle"></i> Inactif
                                </span>
                            <?php endif; ?>
                        </dd>
                        
                        <dt class="col-sm-6">Rôle :</dt>
                        <dd class="col-sm-6">
                            <span class="badge bg-<?= $user['role'] === 'admin' ? 'danger' : 'info' ?>">
                                <?= $user['role'] === 'admin' ? 'Administrateur' : 'Utilisateur' ?>
                            </span>
                        </dd>
                        
                        <dt class="col-sm-6">Membre depuis :</dt>
                        <dd class="col-sm-6">
                            <?= date('d/m/Y', strtotime($user['created_at'])) ?>
                        </dd>
                    </dl>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-gear"></i> Informations système</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-6">Version :</dt>
                        <dd class="col-sm-6">
                            <span class="badge bg-primary">v2.0.0</span>
                        </dd>
                        
                        <dt class="col-sm-6">PHP :</dt>
                        <dd class="col-sm-6"><?= phpversion() ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Validation du mot de passe
document.getElementById('passwordForm')?.addEventListener('submit', function(e) {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const errorDiv = document.getElementById('password-error');
    
    if (newPassword !== confirmPassword) {
        e.preventDefault();
        errorDiv.classList.remove('d-none');
        return false;
    }
    
    errorDiv.classList.add('d-none');
    return true;
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
