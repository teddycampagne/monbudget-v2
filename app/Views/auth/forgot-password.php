<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié - MonBudget v2.1</title>

    <!-- PWA Manifest -->
    <link rel="manifest" href="<?= url('manifest.json') ?>">

    <!-- Favicons -->
    <link rel="icon" type="image/x-icon" href="<?= url('favicon.ico?v=2.1') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= url('favicon-16x16.png?v=2.1') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= url('favicon-32x32.png?v=2.1') ?>">
    <link rel="icon" type="image/png" sizes="48x48" href="<?= url('favicon-48x48.png?v=2.1') ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= url('apple-touch-icon.png?v=2.1') ?>">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }

        .auth-container {
            max-width: 500px;
            margin: 0 auto;
        }

        .auth-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .auth-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .auth-header h1 {
            margin: 0;
            font-size: 2rem;
            font-weight: 600;
        }

        .auth-body {
            padding: 40px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 30px;
            font-weight: 600;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .text-link {
            color: #667eea;
            text-decoration: none;
        }

        .text-link:hover {
            color: #5a6fd8;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1><i class="bi bi-key-fill"></i> Mot de passe oublié</h1>
            </div>
            <div class="auth-body">
                <p class="text-muted mb-4 text-center">
                    Entrez votre adresse email pour recevoir un lien de réinitialisation de mot de passe.
                </p>

                <form method="POST" action="<?= url('forgot-password') ?>">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                    <div class="mb-3">
                        <label for="email" class="form-label">
                            <i class="bi bi-envelope-fill"></i> Adresse email
                        </label>
                        <input type="email"
                               class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>"
                               id="email"
                               name="email"
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                               required
                               autofocus
                               placeholder="votre.email@exemple.com">
                        <?php if (isset($errors['email'])): ?>
                            <div class="invalid-feedback">
                                <?php echo $errors['email']; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send-fill"></i> Envoyer le lien de réinitialisation
                        </button>
                    </div>
                </form>

                <div class="text-center">
                    <a href="<?= url('login') ?>" class="text-link">
                        <i class="bi bi-arrow-left"></i> Retour à la connexion
                    </a>
                </div>

                <div class="text-center mt-3">
                    <small class="text-muted">
                        Si vous ne recevez pas d'email, vérifiez votre dossier spam.
                    </small>
                </div>
            </div>
        </div>

        <div class="text-center mt-3 text-white">
            <small>MonBudget v2.1 © <?= date('Y') ?></small>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>