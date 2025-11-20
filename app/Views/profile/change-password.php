<?php
/**
 * Vue: Changement de Mot de Passe
 * 
 * Formulaire sécurisé de changement de mot de passe avec validation PCI DSS
 */
?>

<?php require_once BASE_PATH . '/app/Views/layouts/header.php'; ?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-lg-6 offset-lg-3">
            
            <!-- En-tête -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>
                    <i class="bi bi-key-fill"></i>
                    Changer le mot de passe
                </h1>
                <a href="<?= url('profile') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Retour
                </a>
            </div>

            <!-- Alerte si changement forcé -->
            <?php if (isset($forced) && $forced): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <strong>Changement obligatoire</strong> - Votre mot de passe a expiré. 
                    Vous devez le changer pour continuer.
                </div>
            <?php endif; ?>

            <!-- Formulaire -->
            <div class="card">
                <div class="card-header bg-warning">
                    <h5 class="mb-0">
                        <i class="bi bi-shield-lock"></i>
                        Nouveau mot de passe
                    </h5>
                </div>
                <div class="card-body">
                    
                    <!-- Exigences de sécurité -->
                    <div class="alert alert-info mb-4">
                        <h6 class="alert-heading">
                            <i class="bi bi-info-circle"></i>
                            Exigences de sécurité (PCI DSS)
                        </h6>
                        <ul class="mb-0 small">
                            <li><strong>12 caractères minimum</strong></li>
                            <li>Au moins <strong>1 majuscule</strong> (A-Z)</li>
                            <li>Au moins <strong>1 minuscule</strong> (a-z)</li>
                            <li>Au moins <strong>1 chiffre</strong> (0-9)</li>
                            <li>Au moins <strong>1 caractère spécial</strong> (!@#$%^&*...)</li>
                            <li>Différent des <strong>5 derniers mots de passe</strong></li>
                        </ul>
                    </div>

                    <form method="POST" action="<?= url('change-password') ?>" id="changePasswordForm">
                        <?= csrf_field() ?>
                        
                        <!-- Mot de passe actuel -->
                        <div class="mb-3">
                            <label for="current_password" class="form-label">
                                <i class="bi bi-lock"></i>
                                Mot de passe actuel
                                <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input 
                                    type="password" 
                                    class="form-control" 
                                    id="current_password" 
                                    name="current_password" 
                                    required
                                    autocomplete="current-password"
                                >
                                <button 
                                    class="btn btn-outline-secondary" 
                                    type="button" 
                                    onclick="togglePassword('current_password')"
                                >
                                    <i class="bi bi-eye" id="current_password_icon"></i>
                                </button>
                            </div>
                        </div>

                        <hr>

                        <!-- Nouveau mot de passe -->
                        <div class="mb-3">
                            <label for="new_password" class="form-label">
                                <i class="bi bi-key"></i>
                                Nouveau mot de passe
                                <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input 
                                    type="password" 
                                    class="form-control" 
                                    id="new_password" 
                                    name="new_password" 
                                    required
                                    minlength="12"
                                    autocomplete="new-password"
                                >
                                <button 
                                    class="btn btn-outline-secondary" 
                                    type="button" 
                                    onclick="togglePassword('new_password')"
                                >
                                    <i class="bi bi-eye" id="new_password_icon"></i>
                                </button>
                            </div>
                            
                            <!-- Indicateur de force -->
                            <div class="mt-2">
                                <div class="progress" style="height: 5px;">
                                    <div 
                                        class="progress-bar" 
                                        id="password-strength-bar" 
                                        role="progressbar" 
                                        style="width: 0%"
                                    ></div>
                                </div>
                                <small class="text-muted" id="password-strength-text">
                                    Entrez un mot de passe
                                </small>
                            </div>
                        </div>

                        <!-- Confirmation -->
                        <div class="mb-4">
                            <label for="confirm_password" class="form-label">
                                <i class="bi bi-key-fill"></i>
                                Confirmer le nouveau mot de passe
                                <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input 
                                    type="password" 
                                    class="form-control" 
                                    id="confirm_password" 
                                    name="confirm_password" 
                                    required
                                    minlength="12"
                                    autocomplete="new-password"
                                >
                                <button 
                                    class="btn btn-outline-secondary" 
                                    type="button" 
                                    onclick="togglePassword('confirm_password')"
                                >
                                    <i class="bi bi-eye" id="confirm_password_icon"></i>
                                </button>
                            </div>
                            <small class="text-muted" id="password-match-text"></small>
                        </div>

                        <!-- Boutons -->
                        <div class="d-flex justify-content-end gap-2">
                            <?php if (!isset($forced) || !$forced): ?>
                                <a href="<?= url('profile') ?>" class="btn btn-secondary">
                                    <i class="bi bi-x-circle"></i> Annuler
                                </a>
                            <?php endif; ?>
                            <button type="submit" class="btn btn-warning" id="submitBtn" disabled>
                                <i class="bi bi-check-circle"></i> Changer le mot de passe
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- JavaScript -->
<script>
// Toggle password visibility
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = document.getElementById(fieldId + '_icon');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
}

// Validation mot de passe
const newPassword = document.getElementById('new_password');
const confirmPassword = document.getElementById('confirm_password');
const submitBtn = document.getElementById('submitBtn');
const strengthBar = document.getElementById('password-strength-bar');
const strengthText = document.getElementById('password-strength-text');
const matchText = document.getElementById('password-match-text');

// Vérifier force mot de passe
function checkPasswordStrength(password) {
    let strength = 0;
    const checks = {
        length: password.length >= 12,
        uppercase: /[A-Z]/.test(password),
        lowercase: /[a-z]/.test(password),
        number: /[0-9]/.test(password),
        special: /[!@#$%^&*(),.?":{}|<>]/.test(password)
    };
    
    strength = Object.values(checks).filter(v => v).length;
    
    let color, text, percent;
    if (strength === 0) {
        color = 'bg-secondary'; text = 'Très faible'; percent = 0;
    } else if (strength <= 2) {
        color = 'bg-danger'; text = 'Faible'; percent = 40;
    } else if (strength === 3) {
        color = 'bg-warning'; text = 'Moyen'; percent = 60;
    } else if (strength === 4) {
        color = 'bg-info'; text = 'Bon'; percent = 80;
    } else {
        color = 'bg-success'; text = 'Excellent'; percent = 100;
    }
    
    strengthBar.className = `progress-bar ${color}`;
    strengthBar.style.width = percent + '%';
    strengthText.textContent = text;
    strengthText.className = `text-${color.replace('bg-', '')}`;
    
    return strength >= 5;
}

// Vérifier correspondance
function checkPasswordMatch() {
    if (confirmPassword.value === '') {
        matchText.textContent = '';
        return false;
    }
    
    if (newPassword.value === confirmPassword.value) {
        matchText.textContent = '✓ Les mots de passe correspondent';
        matchText.className = 'text-success';
        return true;
    } else {
        matchText.textContent = '✗ Les mots de passe ne correspondent pas';
        matchText.className = 'text-danger';
        return false;
    }
}

// Activer/désactiver bouton submit
function updateSubmitButton() {
    const isStrong = checkPasswordStrength(newPassword.value);
    const isMatch = checkPasswordMatch();
    submitBtn.disabled = !(isStrong && isMatch && newPassword.value.length >= 12);
}

newPassword.addEventListener('input', updateSubmitButton);
confirmPassword.addEventListener('input', updateSubmitButton);

// Validation formulaire
document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
    if (newPassword.value !== confirmPassword.value) {
        e.preventDefault();
        alert('Les mots de passe ne correspondent pas');
        return false;
    }
    
    if (newPassword.value.length < 12) {
        e.preventDefault();
        alert('Le mot de passe doit contenir au moins 12 caractères');
        return false;
    }
});
</script>

<?php require_once BASE_PATH . '/app/Views/layouts/footer.php'; ?>
