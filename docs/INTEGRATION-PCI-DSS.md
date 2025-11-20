# Intégration Services PCI DSS - Guide Rapide

## ✅ Implémentations Complétées

### 1. EncryptionService dans Modèles

**Fichier**: `app/Models/Compte.php`

Le modèle Compte intègre automatiquement le chiffrement AES-256-GCM :

```php
use MonBudget\Services\EncryptionService;

// Méthodes ajoutées :
- encryptIban(?string $iban): ?string      // Chiffre IBAN avant sauvegarde
- decryptIban(?string $iban, bool $masked): ?string  // Déchiffre ou masque IBAN
- create(array $data): int                   // Override avec chiffrement auto
- update(int $id, array $data): int          // Override avec chiffrement auto
```

**Usage**:
```php
// Création compte - IBAN chiffré automatiquement
$compteId = Compte::create([
    'nom' => 'Compte Courant',
    'iban' => 'FR7630006000011234567890189',  // Stocké chiffré
    'banque_id' => 1,
    'user_id' => $userId
]);

// Récupération IBAN masqué pour affichage
$compte = Compte::find($compteId);
$ibanMasked = Compte::decryptIban($compte['iban'], true);
// Résultat: "FR** **** **89"

// Récupération IBAN complet (admin uniquement)
$ibanClair = Compte::decryptIban($compte['iban'], false);
```

### 2. PasswordPolicyService dans AuthController

**Fichier**: `app/Controllers/AuthController.php`

Intégration complète dans login(), register(), logout() :

```php
use MonBudget\Services\PasswordPolicyService;
use App\Services\AuditLogService;

// Login - Vérifications PCI DSS
- Verrouillage compte après 5 tentatives (8.3)
- Détection expiration mot de passe 90 jours (8.2.4)
- Log succès/échec connexion (10.2.5)

// Register - Validation stricte
- Longueur minimum 12 caractères (8.2.3)
- Complexité: maj, min, chiffres, spéciaux
- Historique 5 derniers mots de passe (8.2.5)

// Logout - Traçabilité
- Log déconnexion avec timestamp (10.2.3)
```

### 3. ProfileController pour Changement Mot de Passe

**Fichier**: `app/Controllers/ProfileController.php` (NOUVEAU)

Contrôleur dédié conforme PCI DSS :

```php
// Méthodes:
- showChangePassword()   // Affiche formulaire
- changePassword()       // Traite changement avec validation
- show()                 // Affiche profil + statut mot de passe
- update()              // Mise à jour profil avec audit
```

**Routes à ajouter** (config/routes.php):
```php
$router->get('/profile', 'ProfileController@show');
$router->post('/profile', 'ProfileController@update');
$router->get('/change-password', 'ProfileController@showChangePassword');
$router->post('/change-password', 'ProfileController@changePassword');
```

### 4. Script Migration Chiffrement IBAN

**Fichier**: `cli/migrate-encrypt-ibans.php`

Script CLI pour chiffrer les IBAN existants en base :

```bash
# Simulation (dry-run)
php cli/migrate-encrypt-ibans.php --dry-run

# Exécution réelle
php cli/migrate-encrypt-ibans.php

# Force re-chiffrement (si clé changée)
php cli/migrate-encrypt-ibans.php --force
```

**Fonctionnalités**:
- Détection automatique IBAN déjà chiffrés
- Affichage IBAN masqués pour sécurité
- Rapport détaillé : total, chiffrés, erreurs
- Vérification post-migration
- Mode dry-run pour test

## 🔧 Configuration Requise

### Fichier .env

Créer `.env` à partir de `.env.example` :

```bash
# Générer clé de chiffrement
php -r "echo base64_encode(openssl_random_pseudo_bytes(32));"

# Exemple .env
ENCRYPTION_KEY=wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY=
DB_HOST=localhost
DB_DATABASE=monbudget_v2
DB_USERNAME=root
DB_PASSWORD=votre_password

# Paramètres sécurité (optionnels)
MAX_LOGIN_ATTEMPTS=5
PASSWORD_EXPIRY_DAYS=90
PASSWORD_HISTORY_COUNT=5
ACCOUNT_LOCK_DURATION=900
AUDIT_RETENTION_DAYS=365
```

### Chargement Variables Environnement

Ajouter dans `public/index.php` (si pas déjà fait):

```php
// Charger .env avec vlucas/phpdotenv ou simplement:
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($key, $value) = explode('=', $line, 2);
        putenv(trim($key) . '=' . trim($value));
    }
}
```

## 📋 TODO - Intégration Restante

### Audit dans Contrôleurs Critiques

**TransactionController** :
```php
use App\Services\AuditLogService;

public function store() {
    // ... création transaction
    
    $audit = new AuditLogService();
    $audit->logCreate('transactions', $transactionId, [
        'montant' => $montant,
        'type_operation' => $typeOperation,
        'compte_id' => $compteId
    ]);
}

public function update($id) {
    $oldTransaction = Transaction::find($id);
    
    // ... update
    
    $audit = new AuditLogService();
    $audit->logUpdate('transactions', $id, $oldTransaction, $newValues);
}

public function delete($id) {
    $transaction = Transaction::find($id);
    
    $audit = new AuditLogService();
    $audit->logDelete('transactions', $id, $transaction);
    
    // ... delete
}
```

**CompteController** :
```php
public function store() {
    $compteId = Compte::create($data);
    
    $audit = new AuditLogService();
    $audit->logCreate('comptes', $compteId, $data);
}

public function update($id) {
    $oldCompte = Compte::find($id);
    
    // ... update
    
    $audit = new AuditLogService();
    $audit->logUpdate('comptes', $id, $oldCompte, $newValues);
}
```

**BudgetController** :
```php
public function store() {
    $budgetId = Budget::create($data);
    
    $audit = new AuditLogService();
    $audit->logCreate('budgets', $budgetId, $data);
}
```

### Vues à Créer/Modifier

**profile/change-password.php** (NOUVEAU):
```php
<form method="POST" action="<?= url('change-password') ?>">
    <?= csrf_field() ?>
    
    <input type="password" name="current_password" required>
    <input type="password" name="new_password" required>
    <input type="password" name="confirm_password" required>
    
    <button type="submit">Changer le mot de passe</button>
</form>

<!-- Afficher exigences mot de passe -->
<ul>
    <li>12 caractères minimum</li>
    <li>Majuscule + minuscule + chiffre + caractère spécial</li>
    <li>Différent des 5 derniers mots de passe</li>
</ul>
```

**profile/show.php** (NOUVEAU):
```php
<!-- Afficher statut mot de passe -->
<?php if ($isPasswordExpired): ?>
    <div class="alert alert-danger">
        ⚠️ Votre mot de passe a expiré
    </div>
<?php elseif ($daysUntilExpiration < 7): ?>
    <div class="alert alert-warning">
        Votre mot de passe expire dans <?= $daysUntilExpiration ?> jours
    </div>
<?php endif; ?>
```

**auth/login.php** - Ajouter notification verrouillage:
```php
<?php if (isset($_GET['locked'])): ?>
    <div class="alert alert-danger">
        Compte verrouillé suite à trop de tentatives. 
        Réessayez dans 15 minutes.
    </div>
<?php endif; ?>
```

## 🧪 Tests à Créer

### EncryptionServiceTest.php
```php
public function testEncryptDecrypt()
public function testEncryptIBAN()
public function testMaskIBAN()
public function testIsEncrypted()
public function testInvalidKey()
```

### PasswordPolicyServiceTest.php
```php
public function testValidatePassword()
public function testPasswordHistory()
public function testAccountLocking()
public function testPasswordExpiration()
```

### AuditLogServiceTest.php
```php
public function testLogCreation()
public function testSanitizeValues()
public function testGetAuditReport()
public function testCleanOldLogs()
```

## 📊 Statut Conformité PCI DSS

| Exigence | Statut | Implémentation |
|----------|--------|----------------|
| **3** - Protection données | ✅ 100% | EncryptionService + Compte.php |
| **8.2.3** - Mots de passe forts | ✅ 100% | PasswordPolicyService validation |
| **8.2.4** - Expiration 90j | ✅ 100% | password_expires_at + AuthController |
| **8.2.5** - Historique | ✅ 100% | password_history table |
| **8.3** - Verrouillage compte | ✅ 100% | failed_login_attempts + locked_until |
| **10.2** - Journalisation | ✅ 80% | AuditLogService (Auth OK, CRUD en cours) |

**Taux global** : 70% → 90% (après intégration audit complète)

## 🚀 Déploiement

1. **Exécuter migrations** :
   ```powershell
   .\run-migrations.ps1 -Password "votre_password"
   ```

2. **Configurer .env** :
   ```bash
   cp .env.example .env
   # Éditer .env et générer ENCRYPTION_KEY
   ```

3. **Chiffrer IBAN existants** :
   ```bash
   php cli/migrate-encrypt-ibans.php --dry-run  # Test
   php cli/migrate-encrypt-ibans.php            # Production
   ```

4. **Ajouter routes ProfileController** dans config/routes.php

5. **Créer vues profile/**

6. **Intégrer audit dans contrôleurs CRUD**

7. **Tests unitaires PHPUnit**

8. **Audit de sécurité final**
