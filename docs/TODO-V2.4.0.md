# TODO - MonBudget V2.4.0 - Système d'Alertes & Notifications

## 📋 Vue d'ensemble
Version V2.4.0 - Système d'alertes budgétaires et notifications email

**Date de début** : 21 novembre 2025  
**Estimation** : 1 semaine (5-7 jours)  
**Priorité** : Haute

---

## 🎯 Objectifs V2.4.0

### Fonctionnalités principales
1. **🔔 Alertes budgétaires** - Notifications dépassements/seuils
2. **📧 Système email** - Infrastructure SMTP configurable
3. **🔑 Récupération mot de passe** - Reset via email + fallback admin
4. **📊 Centre de notifications** - Widget dans l'application
5. **⚙️ Configuration utilisateur** - Préférences notifications

---

## 📦 Phase 1 - Infrastructure Email (Jour 1-2)

### 1.1 Configuration SMTP

**Fichiers à créer** :
- `app/Services/MailService.php` (300 lignes)
- `app/Services/EmailTemplateService.php` (200 lignes)
- `config/mail.php` (configuration SMTP)

**Fonctionnalités** :
```php
class MailService {
    // Configuration
    - configure(array $config) : void
    - testConnection() : bool
    
    // Envoi
    - send(string $to, string $subject, string $body) : bool
    - sendTemplate(string $template, array $data) : bool
    - sendBatch(array $recipients) : array
    
    // Queue (futur)
    - queue(Email $email) : void
    - processPendingEmails() : void
}
```

**Configuration .env** :
```env
MAIL_DRIVER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@monbudget.app
MAIL_FROM_NAME=MonBudget
```

**Tables BDD** :
```sql
-- Table emails_log (tracking envois)
CREATE TABLE emails_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    recipient VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    template VARCHAR(100) NULL,
    status ENUM('pending', 'sent', 'failed', 'bounced') DEFAULT 'pending',
    error_message TEXT NULL,
    sent_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Table email_templates
CREATE TABLE email_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    subject VARCHAR(255) NOT NULL,
    body_html TEXT NOT NULL,
    body_text TEXT NOT NULL,
    variables JSON NULL COMMENT 'Variables disponibles: {{username}}, {{amount}}, etc.',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name)
) ENGINE=InnoDB;
```

**Templates email initiaux** :
1. `welcome.html` - Email bienvenue nouveau compte
2. `password_reset.html` - Lien réinitialisation mot de passe
3. `budget_alert.html` - Alerte dépassement budget
4. `budget_threshold.html` - Approche seuil (80%, 90%)
5. `monthly_summary.html` - Récapitulatif mensuel
6. `admin_password_request.html` - Demande assistance admin

### 1.2 Templates HTML Email

**Fichiers à créer** :
- `app/Views/emails/layouts/base.html` (layout principal)
- `app/Views/emails/welcome.html`
- `app/Views/emails/password_reset.html`
- `app/Views/emails/budget_alert.html`
- `app/Views/emails/budget_threshold.html`
- `app/Views/emails/monthly_summary.html`
- `app/Views/emails/admin_password_request.html`

**Design email** :
- Responsive (mobile-first)
- Compatible tous clients (Gmail, Outlook, Apple Mail)
- Logo MonBudget en header
- Footer avec lien désabonnement
- Variables dynamiques : `{{username}}`, `{{amount}}`, `{{date}}`, etc.
- Inline CSS (compatibilité maximale)

**Exemple template** :
```html
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{subject}}</title>
</head>
<body style="margin:0; padding:0; font-family:Arial,sans-serif; background:#f4f4f4;">
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center" style="padding:20px;">
                <table width="600" cellpadding="0" cellspacing="0" style="background:#fff; border-radius:8px;">
                    <!-- Header -->
                    <tr>
                        <td style="background:#6f42c1; padding:20px; text-align:center; border-radius:8px 8px 0 0;">
                            <h1 style="color:#fff; margin:0;">MonBudget</h1>
                        </td>
                    </tr>
                    <!-- Body -->
                    <tr>
                        <td style="padding:40px;">
                            {{content}}
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="background:#f8f9fa; padding:20px; text-align:center; font-size:12px; color:#666; border-radius:0 0 8px 8px;">
                            <p>MonBudget - Gestion de budget personnel</p>
                            <a href="{{unsubscribe_url}}" style="color:#6f42c1;">Se désabonner</a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
```

---

## 🔑 Phase 2 - Récupération Mot de Passe (Jour 2-3)

### 2.1 Flow complet

**Étapes** :
1. **Demande reset** : Utilisateur entre son email
2. **Génération token** : Token unique 64 chars + expiration 1h
3. **Email envoi** : Lien avec token vers formulaire reset
4. **Validation token** : Vérification validité + expiration
5. **Nouveau mot de passe** : Formulaire avec validation PCI DSS
6. **Confirmation** : Email confirmation changement réussi
7. **Fallback admin** : Si échec, bouton "Contacter un administrateur"

**Fichiers à créer/modifier** :
- `app/Controllers/PasswordResetController.php` (nouveau, 350 lignes)
- `app/Views/auth/forgot-password.php` (formulaire email)
- `app/Views/auth/reset-password.php` (formulaire nouveau MDP)
- `app/Views/auth/reset-success.php` (confirmation)
- `app/Views/auth/reset-admin-request.php` (demande assistance)

**Routes** :
```php
// Formulaire demande reset
$router->get('/forgot-password', [PasswordResetController::class, 'showForgotForm']);
$router->post('/forgot-password', [PasswordResetController::class, 'sendResetLink']);

// Formulaire nouveau mot de passe
$router->get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm']);
$router->post('/reset-password', [PasswordResetController::class, 'resetPassword']);

// Demande assistance admin
$router->get('/reset-admin-request', [PasswordResetController::class, 'showAdminRequest']);
$router->post('/reset-admin-request', [PasswordResetController::class, 'sendAdminRequest']);
```

**Table password_resets** :
```sql
CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    INDEX idx_email (email),
    INDEX idx_token (token),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB;
```

**Sécurité** :
- Token cryptographiquement sécurisé (`random_bytes(32)` + `bin2hex()`)
- Expiration 1 heure (configurable)
- Token à usage unique (marqué `used_at` après utilisation)
- Rate limiting : 3 tentatives / 15 min par IP
- Log IP + User-Agent pour audit
- Validation email existence avant envoi
- Email confirmation après changement réussi

### 2.2 Assistance admin

**Workflow** :
1. Utilisateur clique "Contacter un administrateur"
2. Formulaire : Email + Raison (optionnel)
3. Email envoyé à tous les admins actifs
4. Admin accède à interface dédiée
5. Admin peut :
   - Voir détails utilisateur
   - Réinitialiser mot de passe (temporaire généré)
   - Envoyer nouveau mot de passe par email
   - Forcer changement mot de passe à prochaine connexion

**Fichiers à créer** :
- `app/Views/admin/password_requests.php` (liste demandes)
- `app/Controllers/AdminController::passwordRequests()` (méthode)
- `app/Controllers/AdminController::resetUserPassword()` (méthode)

**Table admin_password_requests** :
```sql
CREATE TABLE admin_password_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    reason TEXT NULL,
    status ENUM('pending', 'resolved', 'rejected') DEFAULT 'pending',
    resolved_by INT NULL COMMENT 'Admin user_id',
    resolved_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;
```

---

## 🔔 Phase 3 - Alertes Budgétaires (Jour 3-4)

### 3.1 Configuration alertes

**Table notifications_settings** :
```sql
CREATE TABLE notifications_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    
    -- Alertes budgets
    budget_alert_enabled TINYINT(1) DEFAULT 1,
    budget_threshold_80 TINYINT(1) DEFAULT 1 COMMENT 'Alerte à 80% du budget',
    budget_threshold_90 TINYINT(1) DEFAULT 1 COMMENT 'Alerte à 90% du budget',
    budget_exceeded TINYINT(1) DEFAULT 1 COMMENT 'Alerte dépassement',
    
    -- Récapitulatifs
    weekly_summary TINYINT(1) DEFAULT 0,
    monthly_summary TINYINT(1) DEFAULT 1,
    
    -- Méthodes notification
    notify_email TINYINT(1) DEFAULT 1,
    notify_web TINYINT(1) DEFAULT 1 COMMENT 'Notifications dans app',
    
    -- Fréquence
    max_emails_per_day INT DEFAULT 5 COMMENT 'Limite anti-spam',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
```

**Fichiers à créer** :
- `app/Views/profile/notifications.php` (page paramètres)
- `app/Controllers/ProfileController::notifications()` (GET/POST)

**Interface** :
```
┌─────────────────────────────────────────┐
│ Paramètres de notification              │
├─────────────────────────────────────────┤
│                                          │
│ 🔔 Alertes budgétaires                  │
│ ☑ Activer les alertes budgets           │
│ ☑ Alerte à 80% du budget                │
│ ☑ Alerte à 90% du budget                │
│ ☑ Alerte en cas de dépassement          │
│                                          │
│ 📊 Récapitulatifs                        │
│ ☐ Résumé hebdomadaire                   │
│ ☑ Résumé mensuel                         │
│                                          │
│ 📧 Méthodes de notification              │
│ ☑ Par email                              │
│ ☑ Dans l'application                     │
│                                          │
│ ⚙️ Limites                                │
│ Maximum 5 emails par jour                │
│                                          │
│ [Enregistrer]                            │
└─────────────────────────────────────────┘
```

### 3.2 Détection dépassements

**Fichiers à créer** :
- `app/Services/BudgetAlertService.php` (400 lignes)

**Méthodes** :
```php
class BudgetAlertService {
    // Vérification automatique
    - checkBudgetStatus(int $budgetId) : array
    - checkAllUserBudgets(int $userId) : void
    
    // Calculs
    - calculateBudgetUsage(int $budgetId) : float // Pourcentage
    - getBudgetRemaining(int $budgetId) : float
    
    // Alertes
    - sendThresholdAlert(int $budgetId, int $threshold) : bool
    - sendExceededAlert(int $budgetId, float $overspent) : bool
    
    // Historique
    - getAlertHistory(int $userId, int $days = 30) : array
    - markAlertSent(int $budgetId, string $type) : void
}
```

**Table budget_alerts** :
```sql
CREATE TABLE budget_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    budget_id INT NOT NULL,
    user_id INT NOT NULL,
    alert_type ENUM('threshold_80', 'threshold_90', 'exceeded') NOT NULL,
    budget_amount DECIMAL(10,2) NOT NULL,
    spent_amount DECIMAL(10,2) NOT NULL,
    percentage DECIMAL(5,2) NOT NULL,
    email_sent TINYINT(1) DEFAULT 0,
    web_notified TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_budget_id (budget_id),
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at),
    
    FOREIGN KEY (budget_id) REFERENCES budgets(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_alert (budget_id, alert_type, created_at)
) ENGINE=InnoDB;
```

**Déclencheurs** :
1. **Après ajout transaction** : Vérification budgets impactés
2. **Cron quotidien** : Vérification tous budgets actifs
3. **Changement budget** : Recalcul seuils

**Logique anti-spam** :
- 1 seule alerte par type par budget par jour
- Respect `max_emails_per_day` utilisateur
- Pas d'alerte si budget déjà notifié dans les 24h

### 3.3 Cron job alertes

**Fichier à créer** :
- `cli/check_budget_alerts.php` (script CLI)

**Commande cron** :
```bash
# Vérification quotidienne à 8h00
0 8 * * * php /path/to/monbudget/cli/check_budget_alerts.php
```

**Script** :
```php
<?php
// cli/check_budget_alerts.php
require_once __DIR__ . '/../vendor/autoload.php';

use MonBudget\Core\Database;
use MonBudget\Services\BudgetAlertService;
use MonBudget\Services\MailService;

// Configuration
$config = require __DIR__ . '/../config/database.php';
Database::configure($config);

$alertService = new BudgetAlertService();
$mailService = new MailService();

// Récupérer tous utilisateurs actifs avec alertes activées
$users = $alertService->getUsersWithAlertsEnabled();

foreach ($users as $user) {
    echo "Vérification budgets utilisateur {$user['username']}...\n";
    $alertService->checkAllUserBudgets($user['id']);
}

echo "Vérification terminée.\n";
```

---

## 📊 Phase 4 - Centre de Notifications (Jour 4-5)

### 4.1 Notifications in-app

**Table web_notifications** :
```sql
CREATE TABLE web_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL COMMENT 'budget_alert, password_reset, monthly_summary, etc.',
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(500) NULL COMMENT 'URL action (ex: /budgets/123)',
    icon VARCHAR(50) NULL COMMENT 'bi-exclamation-triangle, bi-info-circle, etc.',
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    is_read TINYINT(1) DEFAULT 0,
    read_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_user_id (user_id),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
```

**Fichiers à créer** :
- `app/Services/NotificationService.php` (250 lignes)
- `app/Controllers/NotificationController.php` (200 lignes)
- `app/Views/notifications/index.php` (liste notifications)
- `app/Views/components/notification-badge.php` (badge compteur)
- `assets/js/notifications.js` (polling/WebSocket futur)

**Widget header** :
```html
<!-- Dans header.php -->
<div class="dropdown">
    <a href="#" class="position-relative" id="notificationDropdown" data-bs-toggle="dropdown">
        <i class="bi bi-bell fs-4"></i>
        <?php if ($unread_count > 0): ?>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                <?= $unread_count ?>
            </span>
        <?php endif; ?>
    </a>
    <ul class="dropdown-menu dropdown-menu-end" style="width: 350px; max-height: 400px; overflow-y: auto;">
        <li class="dropdown-header">
            Notifications
            <?php if ($unread_count > 0): ?>
                <a href="#" class="float-end text-primary" onclick="markAllAsRead()">
                    <small>Tout marquer comme lu</small>
                </a>
            <?php endif; ?>
        </li>
        <li><hr class="dropdown-divider"></li>
        
        <?php foreach ($notifications as $notif): ?>
            <li>
                <a class="dropdown-item <?= $notif['is_read'] ? '' : 'bg-light' ?>" 
                   href="<?= url($notif['link']) ?>"
                   onclick="markAsRead(<?= $notif['id'] ?>)">
                    <div class="d-flex align-items-start">
                        <i class="bi <?= $notif['icon'] ?> fs-4 me-2"></i>
                        <div class="flex-grow-1">
                            <strong><?= htmlspecialchars($notif['title']) ?></strong>
                            <p class="mb-1 small"><?= htmlspecialchars($notif['message']) ?></p>
                            <small class="text-muted"><?= formatDate($notif['created_at']) ?></small>
                        </div>
                    </div>
                </a>
            </li>
            <li><hr class="dropdown-divider"></li>
        <?php endforeach; ?>
        
        <li class="text-center">
            <a href="<?= url('notifications') ?>" class="dropdown-item text-primary">
                Voir toutes les notifications
            </a>
        </li>
    </ul>
</div>
```

**Routes API** :
```php
// Récupérer notifications
$router->get('/api/notifications', [NotificationController::class, 'index']);
$router->get('/api/notifications/unread-count', [NotificationController::class, 'unreadCount']);

// Marquer comme lu
$router->post('/api/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
$router->post('/api/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);

// Supprimer
$router->delete('/api/notifications/{id}', [NotificationController::class, 'delete']);
```

**Polling JavaScript** (15s) :
```javascript
// assets/js/notifications.js
let lastNotificationId = 0;

async function checkNewNotifications() {
    const response = await fetch(url('api/notifications/unread-count'));
    const data = await response.json();
    
    if (data.count > 0) {
        updateBadge(data.count);
        
        // Toast si nouvelles notifications
        if (data.new_since_last_check) {
            showToast('Nouvelle notification', data.latest_title);
        }
    }
}

// Polling toutes les 15 secondes
setInterval(checkNewNotifications, 15000);
```

### 4.2 Types de notifications

**Notifications créées automatiquement** :
1. **Budget à 80%** : "Attention : Vous avez atteint 80% de votre budget [Nom]"
2. **Budget à 90%** : "Alerte : Vous avez atteint 90% de votre budget [Nom]"
3. **Budget dépassé** : "⚠️ Budget [Nom] dépassé de [X]€"
4. **Reset mot de passe** : "Votre mot de passe a été réinitialisé"
5. **Nouveau compte créé** : "Bienvenue sur MonBudget !"
6. **Demande admin traitée** : "Un administrateur a réinitialisé votre mot de passe"
7. **Import réussi** : "[X] transactions importées avec succès"
8. **Récurrence exécutée** : "Transaction récurrente [Nom] exécutée"

---

## 🧪 Phase 5 - Tests & Validation (Jour 5-6)

### 5.1 Tests unitaires

**Fichiers à créer** :
- `tests/Unit/Services/MailServiceTest.php`
- `tests/Unit/Services/BudgetAlertServiceTest.php`
- `tests/Unit/Services/NotificationServiceTest.php`

**Tests MailService** :
- ✅ Configuration SMTP valide
- ✅ Test connexion
- ✅ Envoi email simple
- ✅ Envoi avec template
- ✅ Variables remplacées correctement
- ✅ Gestion erreurs (mauvaises credentials, destinataire invalide)
- ✅ Log emails dans `emails_log`

**Tests BudgetAlertService** :
- ✅ Calcul pourcentage budget correct
- ✅ Détection seuils 80%, 90%, 100%+
- ✅ Pas de doublons alertes même jour
- ✅ Respect `max_emails_per_day`
- ✅ Alertes désactivées si settings utilisateur = 0

**Tests NotificationService** :
- ✅ Création notification web
- ✅ Marquage comme lu
- ✅ Compteur non-lus correct
- ✅ Suppression notifications > 30 jours (cleanup)

### 5.2 Tests manuels

**Scénarios à tester** :

**Reset mot de passe** :
1. ✅ Demande reset avec email valide → Email reçu
2. ✅ Clic lien email → Formulaire reset affiché
3. ✅ Token expiré (>1h) → Message erreur
4. ✅ Token déjà utilisé → Message erreur
5. ✅ Nouveau mot de passe faible → Validation échoue
6. ✅ Nouveau mot de passe valide → Succès + email confirmation
7. ✅ Demande assistance admin → Email envoyé à admins
8. ✅ Admin reset mot de passe → Email nouveau mot de passe temporaire

**Alertes budgets** :
1. ✅ Créer budget 100€
2. ✅ Ajouter transaction 80€ → Alerte 80% (email + notif web)
3. ✅ Ajouter transaction 10€ → Alerte 90%
4. ✅ Ajouter transaction 15€ → Alerte dépassement (105€ / 100€)
5. ✅ Vérifier aucun doublon alertes même jour
6. ✅ Désactiver alertes dans profil → Plus d'emails

**Notifications in-app** :
1. ✅ Badge compteur correct (nombre non-lus)
2. ✅ Dropdown affiche 5 dernières
3. ✅ Clic notification → Marquée comme lue + redirection
4. ✅ "Tout marquer comme lu" → Badge disparaît
5. ✅ Page liste notifications → Pagination + filtres

---

## 📚 Phase 6 - Documentation (Jour 6-7)

### 6.1 Documentation utilisateur

**Fichiers à créer/modifier** :
- `docs/user/NOTIFICATIONS.md` (nouveau, guide notifications)
- `docs/user/PASSWORD_RESET.md` (nouveau, guide reset mot de passe)
- `docs/user/GUIDE.md` (mise à jour section alertes)

**Contenu NOTIFICATIONS.md** :
```markdown
# Guide des Notifications

## Types de notifications

### Alertes budgétaires
- **Seuil 80%** : Vous recevez une alerte lorsque vous atteignez 80% d'un budget
- **Seuil 90%** : Alerte à 90% du budget
- **Dépassement** : Notification immédiate en cas de dépassement

### Récapitulatifs
- **Hebdomadaire** : Résumé de vos dépenses chaque dimanche
- **Mensuel** : Bilan complet le 1er de chaque mois

## Configuration

Accédez à **Profil > Notifications** pour personnaliser vos préférences :

1. Activez/désactivez les alertes budgétaires
2. Choisissez les méthodes de notification (email et/ou in-app)
3. Configurez la fréquence des récapitulatifs

## Centre de notifications

Cliquez sur l'icône 🔔 en haut à droite pour :
- Voir vos dernières notifications
- Marquer comme lu
- Accéder aux détails (lien vers budget, transaction, etc.)
```

### 6.2 Documentation technique

**Fichiers à créer** :
- `docs/MAIL_CONFIGURATION.md` (config SMTP serveurs populaires)
- `docs/CRON_JOBS.md` (liste tâches planifiées)

**Contenu MAIL_CONFIGURATION.md** :
```markdown
# Configuration Email SMTP

## Gmail

```env
MAIL_DRIVER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=votre-email@gmail.com
MAIL_PASSWORD=mot-de-passe-application
MAIL_ENCRYPTION=tls
```

⚠️ **Important** : Activez "Mots de passe d'application" dans Google Account

## OVH

```env
MAIL_HOST=ssl0.ovh.net
MAIL_PORT=587
MAIL_USERNAME=votre-email@votredomaine.com
MAIL_PASSWORD=***
MAIL_ENCRYPTION=tls
```

## Sendinblue (Brevo)

```env
MAIL_HOST=smtp-relay.sendinblue.com
MAIL_PORT=587
MAIL_USERNAME=votre-email@exemple.com
MAIL_PASSWORD=cle-api-smtp
MAIL_ENCRYPTION=tls
```
```

---

## 📊 Récapitulatif Fichiers

### Nouveaux fichiers (30)

**Services** (3) :
- `app/Services/MailService.php`
- `app/Services/EmailTemplateService.php`
- `app/Services/BudgetAlertService.php`
- `app/Services/NotificationService.php`

**Controllers** (2) :
- `app/Controllers/PasswordResetController.php`
- `app/Controllers/NotificationController.php`

**Models** (3) :
- `app/Models/EmailLog.php`
- `app/Models/PasswordReset.php`
- `app/Models/WebNotification.php`

**Views** (12) :
- `app/Views/emails/layouts/base.html`
- `app/Views/emails/welcome.html`
- `app/Views/emails/password_reset.html`
- `app/Views/emails/budget_alert.html`
- `app/Views/emails/budget_threshold.html`
- `app/Views/emails/monthly_summary.html`
- `app/Views/emails/admin_password_request.html`
- `app/Views/auth/forgot-password.php`
- `app/Views/auth/reset-password.php`
- `app/Views/auth/reset-success.php`
- `app/Views/profile/notifications.php`
- `app/Views/notifications/index.php`

**Migrations** (5) :
- `database/migrations/006_create_emails_log.sql`
- `database/migrations/007_create_email_templates.sql`
- `database/migrations/008_create_password_resets.sql`
- `database/migrations/009_create_notifications_settings.sql`
- `database/migrations/010_create_budget_alerts.sql`
- `database/migrations/011_create_web_notifications.sql`
- `database/migrations/012_create_admin_password_requests.sql`

**Scripts** (1) :
- `cli/check_budget_alerts.php`

**JavaScript** (1) :
- `assets/js/notifications.js`

**Tests** (3) :
- `tests/Unit/Services/MailServiceTest.php`
- `tests/Unit/Services/BudgetAlertServiceTest.php`
- `tests/Unit/Services/NotificationServiceTest.php`

**Documentation** (4) :
- `docs/user/NOTIFICATIONS.md`
- `docs/user/PASSWORD_RESET.md`
- `docs/MAIL_CONFIGURATION.md`
- `docs/CRON_JOBS.md`

**Configuration** (1) :
- `config/mail.php`

### Fichiers modifiés (6)

- `index.php` (+15 routes)
- `app/Views/layouts/header.php` (widget notifications)
- `app/Controllers/ProfileController.php` (méthode notifications)
- `app/Controllers/AdminController.php` (gestion demandes reset)
- `app/Controllers/TransactionController.php` (trigger alertes)
- `docs/user/GUIDE.md` (section alertes)

---

## 🎯 Fonctionnalités Livrables

### Must-Have (Priorité 1)
- ✅ Infrastructure email SMTP fonctionnelle
- ✅ Reset mot de passe par email avec token sécurisé
- ✅ Alertes budgétaires (80%, 90%, dépassement)
- ✅ Centre de notifications in-app
- ✅ Configuration utilisateur notifications

### Should-Have (Priorité 2)
- ✅ Demande assistance admin pour reset MDP
- ✅ Templates email HTML responsive
- ✅ Cron job vérification budgets
- ✅ Récapitulatifs mensuels
- ✅ Log emails envoyés

### Nice-to-Have (Priorité 3)
- ⏳ Récapitulatifs hebdomadaires
- ⏳ WebSocket temps réel (remplace polling)
- ⏳ Templates email personnalisables par admin
- ⏳ Push notifications navigateur (PWA)
- ⏳ Export historique notifications CSV

---

## ⚡ Optimisations Futures (V2.5+)

1. **Queue emails** : RabbitMQ/Redis pour envois asynchrones
2. **WebSocket** : Notifications temps réel sans polling
3. **Service externe** : Sendinblue/Mailgun pour meilleure délivrabilité
4. **A/B Testing** : Templates email avec analytics ouvertures/clics
5. **Digest intelligent** : Regrouper alertes similaires (1 email au lieu de 5)
6. **Push notifications** : Via PWA Service Worker
7. **SMS** : Twilio pour alertes critiques (optionnel payant)

---

## 📈 Statistiques Estimées

**Lignes de code** : ~3,500 lignes
- Backend (Services/Controllers/Models) : ~2,000 lignes
- Frontend (Views/JS) : ~1,000 lignes
- Tests : ~300 lignes
- Documentation : ~200 lignes

**Tables BDD** : 7 nouvelles tables

**Routes** : +15 routes

**Emails templates** : 7 templates

---

## 🚀 Prêt pour démarrage !

**Prochaine étape** : Commencer Phase 1 - Infrastructure Email

**Commande Git** :
```bash
git checkout -b feature/v2.4.0-notifications-emails
```

**Premier commit** :
```bash
git commit -m "feat(v2.4.0): Infrastructure email - MailService + config SMTP"
```
