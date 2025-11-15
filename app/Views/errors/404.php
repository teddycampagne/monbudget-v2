<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page non trouvée</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .error-container {
            text-align: center;
            color: white;
            padding: 2rem;
        }
        
        .banker-image {
            width: 400px;
            height: 400px;
            margin: 0 auto 2rem;
            position: relative;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            animation: float 3s ease-in-out infinite;
        }
        
        .banker-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 20px;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        .calculator-animation {
            position: absolute;
            bottom: 20px;
            right: 20px;
            background: #2c3e50;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            animation: shake 0.5s infinite;
        }
        
        .calculator-screen {
            background: #34495e;
            color: #e74c3c;
            padding: 10px 15px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 1.2rem;
            font-weight: bold;
            text-align: center;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0) rotate(0deg); }
            25% { transform: translateX(-2px) rotate(-1deg); }
            75% { transform: translateX(2px) rotate(1deg); }
        }
        
        .error-code {
            font-size: 6rem;
            font-weight: bold;
            color: #fff;
            text-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            margin: 1rem 0;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .error-message {
            font-size: 1.5rem;
            margin: 2rem 0;
            color: rgba(255, 255, 255, 0.95);
        }
        
        .error-subtitle {
            font-size: 1.1rem;
            margin: 1rem 0;
            color: rgba(255, 255, 255, 0.8);
        }
        
        .humor-box {
            background: rgba(255, 255, 255, 0.2);
            border-left: 4px solid #f39c12;
            padding: 1.5rem;
            margin: 2rem auto;
            max-width: 600px;
            border-radius: 10px;
            backdrop-filter: blur(10px);
        }
        
        .humor-box p {
            margin: 0.5rem 0;
            color: rgba(255, 255, 255, 0.9);
        }
        
        .btn-home {
            background: white;
            color: #667eea;
            padding: 1rem 3rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: bold;
            display: inline-block;
            margin-top: 2rem;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .btn-home:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
            color: #764ba2;
        }
        
        .quick-links {
            margin-top: 2rem;
        }
        
        .quick-links a {
            color: white;
            text-decoration: none;
            margin: 0 1rem;
            padding: 0.5rem 1rem;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            transition: all 0.3s ease;
            display: inline-block;
            margin-bottom: 0.5rem;
        }
        
        .quick-links a:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }
        
        @media (max-width: 768px) {
            .banker-image {
                width: 300px;
                height: 300px;
            }
            
            .error-code {
                font-size: 4rem;
            }
            
            .error-message {
                font-size: 1.2rem;
            }
            
            .calculator-animation {
                bottom: 10px;
                right: 10px;
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="banker-image">
            <img src="<?= defined('BASE_URL') ? BASE_URL : '' ?>/assets/404-banker.png" alt="Banquier paniqué">
            
            <!-- Calculatrice animée -->
            <div class="calculator-animation">
                <div class="calculator-screen">
                    ERROR 404
                </div>
            </div>
        </div>
        
        <h1 class="error-code">404</h1>
        
        <div class="error-message">
            <i class="bi bi-exclamation-triangle-fill"></i>
            Notre banquier a vérifié tous ses comptes...
        </div>
        
        <div class="error-subtitle">
            mais cette page reste introuvable !
        </div>
        
        <div class="humor-box">
            <p><strong>🧾 Alerte budgétaire !</strong></p>
            <p>Cette page a été dépensée dans un budget inexistant.</p>
            <p>Nos calculs ne parviennent pas à la localiser dans notre système comptable.</p>
            <p><em>Erreur de caisse : Le solde de cette URL est de 0€</em></p>
        </div>
        
        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/" class="btn-home">
            <i class="bi bi-house-fill me-2"></i>
            Retour à l'accueil
        </a>
        
        <div class="quick-links">
            <strong>Ou essayez ces sections :</strong><br>
            <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/comptes">
                <i class="bi bi-wallet2"></i> Mes comptes
            </a>
            <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/transactions">
                <i class="bi bi-arrow-left-right"></i> Transactions
            </a>
            <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/categories">
                <i class="bi bi-tags"></i> Catégories
            </a>
            <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/budgets">
                <i class="bi bi-piggy-bank"></i> Budgets
            </a>
            <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/rapports">
                <i class="bi bi-graph-up"></i> Rapports
            </a>
        </div>
    </div>
</body>
</html>
