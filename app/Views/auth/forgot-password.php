<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié - MonBudget</title>
    <link rel="stylesheet" href="/assets/css/auth.css">
    <style>
        .forgot-password-container {
            max-width: 450px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .forgot-password-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .forgot-password-header h1 {
            color: #333;
            font-size: 24px;
            margin-bottom: 10px;
        }
        .forgot-password-header p {
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
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .back-to-login {
            text-align: center;
            margin-top: 20px;
        }
        .back-to-login a {
            color: #4CAF50;
            text-decoration: none;
        }
        .back-to-login a:hover {
            text-decoration: underline;
        }
        .fallback-section {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
        .fallback-section h3 {
            font-size: 16px;
            color: #666;
            margin-bottom: 10px;
        }
        .btn-secondary {
            width: 100%;
            padding: 10px;
            background: #f0f0f0;
            color: #333;
            border: 1px solid #ddd;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn-secondary:hover {
            background: #e0e0e0;
        }
        .help-text {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="forgot-password-container">
        <div class="forgot-password-header">
            <h1>🔑 Mot de passe oublié</h1>
            <p>Entrez votre adresse email pour recevoir un lien de réinitialisation</p>
        </div>

        <?php if (isset($message)): ?>
            <div class="alert alert-<?= $message_type ?? 'info' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form id="forgotPasswordForm" method="POST" action="/password/forgot">
            <div class="form-group">
                <label for="email">Adresse email</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    required 
                    placeholder="votre-email@example.com"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                >
                <div class="help-text">
                    Le lien de réinitialisation sera valide pendant 1 heure
                </div>
            </div>

            <button type="submit" class="btn-primary">
                Envoyer le lien de réinitialisation
            </button>
        </form>

        <div class="fallback-section">
            <h3>Vous ne recevez pas l'email ?</h3>
            <p style="font-size: 14px; color: #666; margin-bottom: 10px;">
                Si vous rencontrez des problèmes pour recevoir l'email, vous pouvez demander une réinitialisation manuelle par un administrateur.
            </p>
            <button type="button" class="btn-secondary" onclick="showAdminRequestForm()">
                Demander l'aide d'un administrateur
            </button>
        </div>

        <div class="back-to-login">
            <a href="/login">← Retour à la connexion</a>
        </div>
    </div>

    <!-- Modal demande admin -->
    <div id="adminRequestModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="max-width: 500px; margin: 100px auto; background: white; padding: 30px; border-radius: 10px;">
            <h2>Demande de réinitialisation admin</h2>
            <form method="POST" action="/password/admin-request">
                <div class="form-group">
                    <label for="admin_email">Votre email</label>
                    <input type="email" id="admin_email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="reason">Raison de la demande (optionnel)</label>
                    <textarea id="reason" name="reason" rows="3" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;"></textarea>
                </div>
                <button type="submit" class="btn-primary">Envoyer la demande</button>
                <button type="button" class="btn-secondary" onclick="hideAdminRequestForm()" style="margin-top: 10px;">Annuler</button>
            </form>
        </div>
    </div>

    <script>
        function showAdminRequestForm() {
            document.getElementById('adminRequestModal').style.display = 'block';
            document.getElementById('admin_email').value = document.getElementById('email').value;
        }

        function hideAdminRequestForm() {
            document.getElementById('adminRequestModal').style.display = 'none';
        }

        // Fermer le modal en cliquant en dehors
        document.getElementById('adminRequestModal').addEventListener('click', function(e) {
            if (e.target === this) {
                hideAdminRequestForm();
            }
        });
    </script>
</body>
</html>
