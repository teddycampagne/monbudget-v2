<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-key"></i> Réinitialiser votre mot de passe
                    </h4>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-4">
                        Bonjour <strong><?php echo htmlspecialchars($username ?? 'utilisateur'); ?></strong>,<br>
                        Entrez votre nouveau mot de passe.
                    </p>

                    <form method="POST" action="/reset-password">
                        <?php echo csrf_field(); ?>

                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token ?? ''); ?>">

                        <div class="mb-3">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock"></i> Nouveau mot de passe
                            </label>
                            <input type="password"
                                   class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>"
                                   id="password"
                                   name="password"
                                   required
                                   minlength="8"
                                   autofocus>
                            <?php if (isset($errors['password'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $errors['password']; ?>
                                </div>
                            <?php endif; ?>
                            <div class="form-text">
                                Le mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule et un chiffre.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="password_confirm" class="form-label">
                                <i class="fas fa-lock"></i> Confirmer le mot de passe
                            </label>
                            <input type="password"
                                   class="form-control <?php echo isset($errors['password_confirm']) ? 'is-invalid' : ''; ?>"
                                   id="password_confirm"
                                   name="password_confirm"
                                   required
                                   minlength="8">
                            <?php if (isset($errors['password_confirm'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $errors['password_confirm']; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save"></i> Réinitialiser le mot de passe
                            </button>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-center">
                    <a href="/login" class="text-decoration-none">
                        <i class="fas fa-arrow-left"></i> Retour à la connexion
                    </a>
                </div>
            </div>

            <div class="text-center mt-3">
                <small class="text-muted">
                    Ce lien de réinitialisation expire dans 1 heure.
                </small>
            </div>
        </div>
    </div>
</div>

<script>
// Validation côté client pour l'expérience utilisateur
document.addEventListener('DOMContentLoaded', function() {
    const password = document.getElementById('password');
    const confirm = document.getElementById('password_confirm');
    const form = document.querySelector('form');

    function validatePasswords() {
        if (password.value !== confirm.value) {
            confirm.setCustomValidity('Les mots de passe ne correspondent pas');
        } else {
            confirm.setCustomValidity('');
        }
    }

    password.addEventListener('input', validatePasswords);
    confirm.addEventListener('input', validatePasswords);

    form.addEventListener('submit', function(e) {
        if (password.value !== confirm.value) {
            e.preventDefault();
            alert('Les mots de passe ne correspondent pas');
        }
    });
});
</script>