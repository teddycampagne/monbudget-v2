<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialiser votre mot de passe - MonBudget</title>
    <link rel="stylesheet" href="/assets/css/auth.css">
    <style>
        .reset-password-container {
            max-width: 450px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .reset-password-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .reset-password-header h1 {
            color: #333;
            font-size: 24px;
            margin-bottom: 10px;
        }
        .reset-password-header p {
            color: #666;
            font-size: 14px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 500;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        .form-group input:focus {
            outline: none;
            border-color: #4CAF50;
        }
        .password-strength {
            margin-top: 5px;
            font-size: 12px;
        }
        .password-requirements {
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
            font-size: 13px;
        }
        .password-requirements h4 {
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 14px;
            color: #333;
        }
        .password-requirements ul {
            margin: 0;
            padding-left: 20px;
        }
        .password-requirements li {
            margin-bottom: 5px;
            color: #666;
        }
        .password-requirements li.valid {
            color: #28a745;
        }
        .password-requirements li.invalid {
            color: #dc3545;
        }
        .btn-primary {
            width: 100%;
            padding: 12px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn-primary:hover {
            background: #45a049;
        }
        .btn-primary:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        .alert {
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .password-toggle {
            position: relative;
        }
        .password-toggle button {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="reset-password-container">
        <div class="reset-password-header">
            <h1>🔐 Nouveau mot de passe</h1>
            <p>Choisissez un mot de passe sécurisé pour votre compte</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($success) ?>
                <p style="margin-top: 10px;">
                    <a href="/login">Se connecter maintenant →</a>
                </p>
            </div>
        <?php else: ?>

        <div class="password-requirements">
            <h4>Exigences du mot de passe :</h4>
            <ul id="passwordRequirements">
                <li id="req-length">Au moins 12 caractères</li>
                <li id="req-uppercase">Au moins une majuscule (A-Z)</li>
                <li id="req-lowercase">Au moins une minuscule (a-z)</li>
                <li id="req-number">Au moins un chiffre (0-9)</li>
                <li id="req-special">Au moins un caractère spécial (!@#$%^&*)</li>
            </ul>
        </div>

        <form id="resetPasswordForm" method="POST" action="/password/reset">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token ?? '') ?>">

            <div class="form-group">
                <label for="password">Nouveau mot de passe</label>
                <div class="password-toggle">
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required
                        minlength="12"
                        placeholder="Entrez votre nouveau mot de passe"
                    >
                    <button type="button" onclick="togglePassword('password')">👁️</button>
                </div>
                <div class="password-strength" id="passwordStrength"></div>
            </div>

            <div class="form-group">
                <label for="password_confirm">Confirmer le mot de passe</label>
                <div class="password-toggle">
                    <input 
                        type="password" 
                        id="password_confirm" 
                        name="password_confirm" 
                        required
                        minlength="12"
                        placeholder="Confirmez votre nouveau mot de passe"
                    >
                    <button type="button" onclick="togglePassword('password_confirm')">👁️</button>
                </div>
                <div id="passwordMatch" style="font-size: 12px; margin-top: 5px;"></div>
            </div>

            <button type="submit" class="btn-primary" id="submitBtn" disabled>
                Réinitialiser le mot de passe
            </button>
        </form>

        <?php endif; ?>
    </div>

    <script>
        const passwordInput = document.getElementById('password');
        const passwordConfirmInput = document.getElementById('password_confirm');
        const submitBtn = document.getElementById('submitBtn');

        // Validation en temps réel
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            validatePassword(password);
        });

        passwordConfirmInput.addEventListener('input', function() {
            checkPasswordMatch();
        });

        function validatePassword(password) {
            const requirements = {
                length: password.length >= 12,
                uppercase: /[A-Z]/.test(password),
                lowercase: /[a-z]/.test(password),
                number: /[0-9]/.test(password),
                special: /[^A-Za-z0-9]/.test(password)
            };

            // Mise à jour visuelle des exigences
            document.getElementById('req-length').className = requirements.length ? 'valid' : 'invalid';
            document.getElementById('req-uppercase').className = requirements.uppercase ? 'valid' : 'invalid';
            document.getElementById('req-lowercase').className = requirements.lowercase ? 'valid' : 'invalid';
            document.getElementById('req-number').className = requirements.number ? 'valid' : 'invalid';
            document.getElementById('req-special').className = requirements.special ? 'valid' : 'invalid';

            // Force du mot de passe
            const strength = Object.values(requirements).filter(v => v).length;
            const strengthText = document.getElementById('passwordStrength');
            
            if (password.length === 0) {
                strengthText.textContent = '';
            } else if (strength < 3) {
                strengthText.textContent = '❌ Faible';
                strengthText.style.color = '#dc3545';
            } else if (strength < 5) {
                strengthText.textContent = '⚠️ Moyen';
                strengthText.style.color = '#ffc107';
            } else {
                strengthText.textContent = '✅ Fort';
                strengthText.style.color = '#28a745';
            }

            checkPasswordMatch();
            updateSubmitButton();
        }

        function checkPasswordMatch() {
            const password = passwordInput.value;
            const confirm = passwordConfirmInput.value;
            const matchDiv = document.getElementById('passwordMatch');

            if (confirm.length === 0) {
                matchDiv.textContent = '';
                return;
            }

            if (password === confirm) {
                matchDiv.textContent = '✅ Les mots de passe correspondent';
                matchDiv.style.color = '#28a745';
            } else {
                matchDiv.textContent = '❌ Les mots de passe ne correspondent pas';
                matchDiv.style.color = '#dc3545';
            }

            updateSubmitButton();
        }

        function updateSubmitButton() {
            const password = passwordInput.value;
            const confirm = passwordConfirmInput.value;

            const requirements = {
                length: password.length >= 12,
                uppercase: /[A-Z]/.test(password),
                lowercase: /[a-z]/.test(password),
                number: /[0-9]/.test(password),
                special: /[^A-Za-z0-9]/.test(password)
            };

            const allValid = Object.values(requirements).every(v => v) && password === confirm;
            submitBtn.disabled = !allValid;
        }

        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            field.type = field.type === 'password' ? 'text' : 'password';
        }

        // Validation avant soumission
        document.getElementById('resetPasswordForm').addEventListener('submit', function(e) {
            const password = passwordInput.value;
            const confirm = passwordConfirmInput.value;

            if (password !== confirm) {
                e.preventDefault();
                alert('Les mots de passe ne correspondent pas.');
                return false;
            }

            if (password.length < 12) {
                e.preventDefault();
                alert('Le mot de passe doit contenir au moins 12 caractères.');
                return false;
            }
        });
    </script>
</body>
</html>
