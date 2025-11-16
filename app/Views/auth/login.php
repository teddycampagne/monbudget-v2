<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - MonBudget v2.1</title>
    
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
        
        .login-container {
            max-width: 450px;
            margin: 0 auto;
        }
        
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .login-header h1 {
            font-size: 2rem;
            margin: 0;
            font-weight: 700;
        }
        
        .login-body {
            padding: 40px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="login-card">
                <div class="login-header">
                    <h1><i class="bi bi-piggy-bank-fill"></i> MonBudget</h1>
                    <p class="mb-0">Connexion à votre compte</p>
                </div>
                
                <div class="login-body">
                    <?php if (isset($_SESSION['flash']['error'])): ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            <?= htmlspecialchars($_SESSION['flash']['error']) ?>
                        </div>
                        <?php unset($_SESSION['flash']['error']); ?>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['flash']['success'])): ?>
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle-fill"></i>
                            <?= htmlspecialchars($_SESSION['flash']['success']) ?>
                        </div>
                        <?php unset($_SESSION['flash']['success']); ?>
                    <?php endif; ?>
                    
                    <form method="POST" action="<?= url('login') ?>">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" required autofocus>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Mot de passe</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                            <label class="form-check-label" for="remember">
                                Se souvenir de moi
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-box-arrow-in-right"></i> Se connecter
                        </button>
                    </form>
                    
                    <div class="text-center mt-3">
                        <a href="<?= url('forgot-password') ?>" class="text-decoration-none">
                            Mot de passe oublié ?
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-3 text-white">
                <small>MonBudget v2.0 © <?= date('Y') ?></small>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
