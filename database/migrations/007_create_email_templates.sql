-- Migration 007: Création de la table email_templates
-- Stocke les templates d'emails réutilisables

CREATE TABLE IF NOT EXISTS `email_templates` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE COMMENT 'Identifiant unique du template',
    `description` VARCHAR(500) NULL COMMENT 'Description du template',
    `subject` VARCHAR(500) NOT NULL COMMENT 'Sujet de l\'email (support variables {{var}})',
    `body_html` TEXT NOT NULL COMMENT 'Corps HTML de l\'email',
    `body_text` TEXT NULL COMMENT 'Version texte alternative',
    `category` VARCHAR(50) NOT NULL COMMENT 'Catégorie (system, user, budget, security)',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Template actif',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_name (`name`),
    INDEX idx_category (`category`),
    INDEX idx_active (`is_active`)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Templates d\'emails réutilisables';

-- Insertion des templates par défaut
INSERT INTO `email_templates` (`name`, `description`, `subject`, `body_html`, `body_text`, `category`) VALUES

-- Template: Bienvenue
('welcome', 
 'Email de bienvenue pour les nouveaux utilisateurs',
 'Bienvenue sur MonBudget {{app_name}}',
 '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #4CAF50; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f9f9f9; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        .button { display: inline-block; padding: 10px 20px; background: #4CAF50; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Bienvenue sur MonBudget</h1>
        </div>
        <div class="content">
            <h2>Bonjour {{username}},</h2>
            <p>Votre compte a été créé avec succès sur MonBudget.</p>
            <p><strong>Nom d\'utilisateur :</strong> {{username}}</p>
            <p>Vous pouvez maintenant vous connecter et commencer à gérer votre budget.</p>
            <p style="text-align: center; margin: 30px 0;">
                <a href="{{app_url}}" class="button">Accéder à MonBudget</a>
            </p>
            <p><strong>Note importante :</strong> Pour des raisons de sécurité, vous devrez changer votre mot de passe lors de votre première connexion.</p>
        </div>
        <div class="footer">
            <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
            <p>© {{year}} MonBudget - Gestion de budget personnel</p>
        </div>
    </div>
</body>
</html>',
 'Bienvenue sur MonBudget

Bonjour {{username}},

Votre compte a été créé avec succès.
Nom d\'utilisateur : {{username}}

Vous pouvez vous connecter à {{app_url}}

Note : Vous devrez changer votre mot de passe lors de votre première connexion.

---
Cet email a été envoyé automatiquement.
© {{year}} MonBudget',
 'user'),

-- Template: Réinitialisation mot de passe
('password_reset',
 'Email de réinitialisation du mot de passe',
 'Réinitialisation de votre mot de passe MonBudget',
 '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #2196F3; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f9f9f9; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        .button { display: inline-block; padding: 10px 20px; background: #2196F3; color: white; text-decoration: none; border-radius: 5px; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 10px; margin: 15px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Réinitialisation mot de passe</h1>
        </div>
        <div class="content">
            <h2>Bonjour {{username}},</h2>
            <p>Vous avez demandé à réinitialiser votre mot de passe MonBudget.</p>
            <p style="text-align: center; margin: 30px 0;">
                <a href="{{reset_url}}" class="button">Réinitialiser mon mot de passe</a>
            </p>
            <p>Ce lien est valide pendant <strong>1 heure</strong>.</p>
            <div class="warning">
                <strong>⚠️ Attention :</strong> Si vous n\'avez pas demandé cette réinitialisation, ignorez cet email. Votre mot de passe reste inchangé.
            </div>
            <p style="font-size: 12px; color: #666;">
                Si le bouton ne fonctionne pas, copiez ce lien dans votre navigateur :<br>
                {{reset_url}}
            </p>
        </div>
        <div class="footer">
            <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
            <p>© {{year}} MonBudget</p>
        </div>
    </div>
</body>
</html>',
 'Réinitialisation mot de passe MonBudget

Bonjour {{username}},

Vous avez demandé à réinitialiser votre mot de passe.

Cliquez sur ce lien pour réinitialiser votre mot de passe :
{{reset_url}}

Ce lien est valide pendant 1 heure.

⚠️ Si vous n\'avez pas demandé cette réinitialisation, ignorez cet email.

---
© {{year}} MonBudget',
 'security'),

-- Template: Alerte budget (seuil 80%)
('budget_alert_80',
 'Alerte budget à 80% de consommation',
 '⚠️ Budget "{{budget_name}}" à {{percentage}}%',
 '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #FF9800; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f9f9f9; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        .stats { background: white; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .progress-bar { background: #e0e0e0; height: 30px; border-radius: 15px; overflow: hidden; }
        .progress-fill { background: #FF9800; height: 100%; text-align: center; line-height: 30px; color: white; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚠️ Alerte Budget</h1>
        </div>
        <div class="content">
            <h2>Bonjour {{username}},</h2>
            <p>Votre budget <strong>{{budget_name}}</strong> atteint <strong>{{percentage}}%</strong> de consommation.</p>
            <div class="stats">
                <p><strong>Montant dépensé :</strong> {{spent}} €</p>
                <p><strong>Budget total :</strong> {{total}} €</p>
                <p><strong>Reste disponible :</strong> {{remaining}} €</p>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: {{percentage}}%;">{{percentage}}%</div>
                </div>
            </div>
            <p>💡 <strong>Conseil :</strong> Surveillez vos dépenses pour ne pas dépasser votre budget.</p>
        </div>
        <div class="footer">
            <p>© {{year}} MonBudget</p>
        </div>
    </div>
</body>
</html>',
 'Alerte Budget - MonBudget

Bonjour {{username}},

Votre budget "{{budget_name}}" atteint {{percentage}}% de consommation.

Montant dépensé : {{spent}} €
Budget total : {{total}} €
Reste disponible : {{remaining}} €

Surveillez vos dépenses pour ne pas dépasser votre budget.

---
© {{year}} MonBudget',
 'budget'),

-- Template: Alerte budget (seuil 90%)
('budget_alert_90',
 'Alerte budget à 90% de consommation',
 '🚨 Budget "{{budget_name}}" à {{percentage}}% - Attention!',
 '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #F44336; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f9f9f9; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        .stats { background: white; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .progress-bar { background: #e0e0e0; height: 30px; border-radius: 15px; overflow: hidden; }
        .progress-fill { background: #F44336; height: 100%; text-align: center; line-height: 30px; color: white; font-weight: bold; }
        .warning { background: #ffebee; border-left: 4px solid #F44336; padding: 10px; margin: 15px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚨 Alerte Budget Critique</h1>
        </div>
        <div class="content">
            <h2>Bonjour {{username}},</h2>
            <div class="warning">
                <strong>⚠️ ATTENTION :</strong> Votre budget <strong>{{budget_name}}</strong> atteint <strong>{{percentage}}%</strong> de consommation!
            </div>
            <div class="stats">
                <p><strong>Montant dépensé :</strong> {{spent}} €</p>
                <p><strong>Budget total :</strong> {{total}} €</p>
                <p><strong>Reste disponible :</strong> {{remaining}} €</p>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: {{percentage}}%;">{{percentage}}%</div>
                </div>
            </div>
            <p>🛑 <strong>Action recommandée :</strong> Limitez vos dépenses dans cette catégorie pour éviter de dépasser votre budget.</p>
        </div>
        <div class="footer">
            <p>© {{year}} MonBudget</p>
        </div>
    </div>
</body>
</html>',
 'ALERTE Budget Critique - MonBudget

Bonjour {{username}},

⚠️ ATTENTION : Votre budget "{{budget_name}}" atteint {{percentage}}% de consommation!

Montant dépensé : {{spent}} €
Budget total : {{total}} €
Reste disponible : {{remaining}} €

🛑 Limitez vos dépenses pour éviter de dépasser votre budget.

---
© {{year}} MonBudget',
 'budget'),

-- Template: Budget dépassé
('budget_exceeded',
 'Notification de dépassement de budget',
 '❌ Budget "{{budget_name}}" dépassé',
 '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #D32F2F; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f9f9f9; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        .stats { background: white; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .alert { background: #ffcdd2; border-left: 4px solid #D32F2F; padding: 15px; margin: 15px 0; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>❌ Budget Dépassé</h1>
        </div>
        <div class="content">
            <h2>Bonjour {{username}},</h2>
            <div class="alert">
                ❌ Votre budget <strong>{{budget_name}}</strong> a été dépassé!
            </div>
            <div class="stats">
                <p><strong>Montant dépensé :</strong> {{spent}} €</p>
                <p><strong>Budget alloué :</strong> {{total}} €</p>
                <p><strong>Dépassement :</strong> <span style="color: #D32F2F;">{{exceeded}} €</span></p>
            </div>
            <p>📊 Consultez vos transactions pour identifier les dépenses importantes et ajustez votre budget si nécessaire.</p>
        </div>
        <div class="footer">
            <p>© {{year}} MonBudget</p>
        </div>
    </div>
</body>
</html>',
 'Budget Dépassé - MonBudget

Bonjour {{username}},

❌ Votre budget "{{budget_name}}" a été dépassé!

Montant dépensé : {{spent}} €
Budget alloué : {{total}} €
Dépassement : {{exceeded}} €

Consultez vos transactions et ajustez votre budget si nécessaire.

---
© {{year}} MonBudget',
 'budget'),

-- Template: Récapitulatif mensuel
('monthly_summary',
 'Récapitulatif mensuel des dépenses',
 '📊 Récapitulatif {{month}} {{year}} - MonBudget',
 '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #673AB7; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f9f9f9; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        .stats { background: white; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .stat-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Récapitulatif Mensuel</h1>
        </div>
        <div class="content">
            <h2>Bonjour {{username}},</h2>
            <p>Voici votre récapitulatif pour <strong>{{month}} {{year}}</strong> :</p>
            <div class="stats">
                <div class="stat-row">
                    <span>💰 Revenus :</span>
                    <span><strong>{{income}} €</strong></span>
                </div>
                <div class="stat-row">
                    <span>💸 Dépenses :</span>
                    <span><strong>{{expenses}} €</strong></span>
                </div>
                <div class="stat-row">
                    <span>📈 Solde :</span>
                    <span><strong style="color: {{balance_color}};">{{balance}} €</strong></span>
                </div>
                <div class="stat-row">
                    <span>📊 Nombre de transactions :</span>
                    <span>{{transaction_count}}</span>
                </div>
            </div>
            <p><strong>Top 3 catégories de dépenses :</strong></p>
            <div class="stats">
                {{top_categories}}
            </div>
        </div>
        <div class="footer">
            <p>© {{year}} MonBudget</p>
        </div>
    </div>
</body>
</html>',
 'Récapitulatif {{month}} {{year}} - MonBudget

Bonjour {{username}},

Récapitulatif pour {{month}} {{year}} :

💰 Revenus : {{income}} €
💸 Dépenses : {{expenses}} €
📈 Solde : {{balance}} €
📊 Transactions : {{transaction_count}}

Top 3 catégories de dépenses :
{{top_categories}}

---
© {{year}} MonBudget',
 'system'),

-- Template: Demande admin mot de passe
('admin_password_request',
 'Notification admin pour demande de réinitialisation',
 '🔐 Demande de réinitialisation mot de passe - {{username}}',
 '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #607D8B; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f9f9f9; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        .info { background: white; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .button { display: inline-block; padding: 10px 20px; background: #607D8B; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 Demande Admin</h1>
        </div>
        <div class="content">
            <h2>Bonjour Admin,</h2>
            <p>L\'utilisateur <strong>{{username}}</strong> a demandé une réinitialisation de mot de passe.</p>
            <div class="info">
                <p><strong>Utilisateur :</strong> {{username}}</p>
                <p><strong>Email :</strong> {{user_email}}</p>
                <p><strong>Date demande :</strong> {{request_date}}</p>
                <p><strong>Raison :</strong> {{reason}}</p>
            </div>
            <p style="text-align: center; margin: 30px 0;">
                <a href="{{admin_url}}" class="button">Gérer la demande</a>
            </p>
        </div>
        <div class="footer">
            <p>Notification automatique</p>
            <p>© {{year}} MonBudget</p>
        </div>
    </div>
</body>
</html>',
 'Demande de réinitialisation mot de passe

Bonjour Admin,

L\'utilisateur {{username}} a demandé une réinitialisation de mot de passe.

Utilisateur : {{username}}
Email : {{user_email}}
Date : {{request_date}}
Raison : {{reason}}

Gérer la demande : {{admin_url}}

---
© {{year}} MonBudget',
 'security');
