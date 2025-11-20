# Conformit\u00e9 PCI DSS - MonBudget v2

## \ud83d\udee1\ufe0f Vue d'ensemble

Ce document d\u00e9taille les mesures de s\u00e9curit\u00e9 impl\u00e9ment\u00e9es pour se conformer aux normes **PCI DSS (Payment Card Industry Data Security Standard)**, m\u00eame si l'application ne traite pas directement de paiements par carte.

Les principes PCI DSS garantissent un niveau de s\u00e9curit\u00e9 \u00e9lev\u00e9 pour toutes les donn\u00e9es financi\u00e8res sensibles.

**Version PCI DSS cibl\u00e9e** : 4.0 (mars 2022)  
**Date derni\u00e8re mise \u00e0 jour** : 18 novembre 2025

---

## \ud83c\udfaf Les 12 Exigences PCI DSS

### 1\ufe0f\u20e3 Exigence 1 : Installer et maintenir des contr\u00f4les de s\u00e9curit\u00e9 r\u00e9seau

**Statut** : \ud83d\udfe1 PARTIELLEMENT CONFORME

**Mesures impl\u00e9ment\u00e9es** :
- \u2705 `.htaccess` avec protection r\u00e9pertoires sensibles
- \u2705 Configuration Apache `AllowOverride All`
- \u2705 Firewall serveur (configuration serveur externe)

**\u00c0 impl\u00e9menter** :
- \u26a0\ufe0f Configuration explicite pare-feu applicatif (WAF recommand\u00e9)
- \u26a0\ufe0f Segmentation r\u00e9seau (DMZ pour serveur web)
- \u26a0\ufe0f Restrictions IP pour acc\u00e8s administrateur

**Fichiers concern\u00e9s** :
- `.htaccess`
- `.htaccess.production`
- `docs/DEPLOIEMENT.md`

---

### 2\ufe0f\u20e3 Exigence 2 : Appliquer des configurations s\u00e9curis\u00e9es

**Statut** : \ud83d\udfe1 PARTIELLEMENT CONFORME

**Mesures impl\u00e9ment\u00e9es** :
- \u2705 PHP 8.4+ avec configurations s\u00e9curis\u00e9es
- \u2705 MySQL/MariaDB avec utilisateur d\u00e9di\u00e9 (non root en production)
- \u2705 Suppression fichiers de test en production
- \u2705 Mots de passe BDD dans `.env` (git ignor\u00e9)

**\u00c0 impl\u00e9menter** :
- \u26a0\ufe0f Durcissement PHP (`disable_functions`, `open_basedir`)
- \u26a0\ufe0f Suppression modules/extensions inutiles
- \u26a0\ufe0f Chiffrement connexions BDD (TLS/SSL)
- \u26a0\ufe0f Configuration stricte permissions fichiers (644/755)

**Fichiers concern\u00e9s** :
- `config/database.php`
- `.env.example`
- `php.ini` (serveur)

---

### 3\ufe0f\u20e3 Exigence 3 : Prot\u00e9ger les donn\u00e9es stock\u00e9es

**Statut** : \ud83d\udd34 NON CONFORME - **PRIORIT\u00c9 CRITIQUE**

**Donn\u00e9es sensibles d\u00e9tect\u00e9es (non chiffr\u00e9es)** :
- \u274c IBAN (table `comptes.iban`)
- \u274c Num\u00e9ros de compte (table `comptes.numero_compte`)
- \u274c RIB complets (code banque, code guichet, cl\u00e9)
- \u274c Emails (tables `users`, `titulaires`)
- \u274c T\u00e9l\u00e9phones (tables `banques`, `titulaires`)

**Actions OBLIGATOIRES** :

#### \ud83d\udd11 A. Impl\u00e9menter chiffrement AES-256-GCM

```php
// app/Services/EncryptionService.php (nouveau fichier)
class EncryptionService {
    private string $key;
    private string $cipher = 'aes-256-gcm';
    
    public function __construct() {
        // Cl\u00e9 stock\u00e9e en variable environnement
        $this->key = getenv('ENCRYPTION_KEY');
        if (!$this->key || strlen($this->key) !== 32) {
            throw new Exception('Cl\u00e9 de chiffrement invalide');
        }
    }
    
    public function encrypt(string $data): string {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($this->cipher));
        $tag = '';
        
        $encrypted = openssl_encrypt(
            $data,
            $this->cipher,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
        
        return base64_encode($iv . $tag . $encrypted);
    }
    
    public function decrypt(string $data): string {
        $data = base64_decode($data);
        $ivLength = openssl_cipher_iv_length($this->cipher);
        
        $iv = substr($data, 0, $ivLength);
        $tag = substr($data, $ivLength, 16);
        $encrypted = substr($data, $ivLength + 16);
        
        return openssl_decrypt(
            $encrypted,
            $this->cipher,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
    }
}
```

#### \ud83d\udcbe B. Migration base de donn\u00e9es

```sql
-- database/migrations/003_encrypt_sensitive_data.sql

-- Ajouter colonnes chiffr\u00e9es
ALTER TABLE comptes 
    ADD COLUMN iban_encrypted TEXT AFTER iban,
    ADD COLUMN numero_compte_encrypted TEXT AFTER numero_compte;

-- Proc\u00e9dure migration (PHP)
-- 1. Lire toutes les donn\u00e9es sensibles
-- 2. Chiffrer avec EncryptionService
-- 3. Stocker dans colonnes _encrypted
-- 4. V\u00e9rifier int\u00e9grit\u00e9
-- 5. Supprimer colonnes en clair
-- 6. Renommer colonnes _encrypted

ALTER TABLE comptes 
    DROP COLUMN iban,
    DROP COLUMN numero_compte,
    CHANGE iban_encrypted iban TEXT,
    CHANGE numero_compte_encrypted numero_compte TEXT;
```

#### \ud83d\udd10 C. Gestion des cl\u00e9s de chiffrement

**Options recommand\u00e9es** (par ordre de s\u00e9curit\u00e9) :

1. **Hardware Security Module (HSM)** - Production entreprise
2. **Key Management Service (KMS)** - AWS KMS, Azure Key Vault
3. **Variables d'environnement serveur** - Minimum viable
4. **Fichier s\u00e9curis\u00e9 hors webroot** - Permissions 600

**Impl\u00e9mentation recommand\u00e9e** :
```bash
# .env (JAMAIS commit\u00e9 sur Git)
ENCRYPTION_KEY=<32 octets g\u00e9n\u00e9r\u00e9s avec openssl_random_pseudo_bytes>

# G\u00e9n\u00e9ration cl\u00e9
php -r "echo base64_encode(openssl_random_pseudo_bytes(32));"
```

**Rotation des cl\u00e9s** :
- \ud83d\udd04 Rotation tous les 12 mois
- \ud83d\udcc5 Calendrier : 1er janvier de chaque ann\u00e9e
- \ud83d\udee0\ufe0f Script `cli/rotate-encryption-keys.php`

---

### 4\ufe0f\u20e3 Exigence 4 : Prot\u00e9ger les donn\u00e9es en transit

**Statut** : \ud83d\udfe1 PARTIELLEMENT CONFORME

**Mesures impl\u00e9ment\u00e9es** :
- \u2705 HTTPS obligatoire en production (certificat SSL/TLS)
- \u2705 Redirection HTTP \u2192 HTTPS (`.htaccess`)

**\u00c0 impl\u00e9menter** :
- \u26a0\ufe0f TLS 1.2+ minimum (d\u00e9sactiver TLS 1.0/1.1)
- \u26a0\ufe0f HSTS (HTTP Strict Transport Security)
- \u26a0\ufe0f Chiffrement connexions BDD (MySQL SSL)
- \u26a0\ufe0f Certificat SSL valide (pas auto-sign\u00e9)

**Configuration Apache** :
```apache
# .htaccess
<IfModule mod_headers.c>
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"
</IfModule>

# Forcer HTTPS
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}/$1 [R=301,L]
```

---

### 5\ufe0f\u20e3 Exigence 5 : Prot\u00e9ger contre les malwares

**Statut** : \ud83d\udfe1 PARTIELLEMENT CONFORME

**Mesures impl\u00e9ment\u00e9es** :
- \u2705 Validation MIME types fichiers upload\u00e9s
- \u2705 Limite taille fichiers (10 MB)
- \u2705 Stockage fichiers hors webroot (`uploads/`)

**\u00c0 impl\u00e9menter** :
- \u26a0\ufe0f Scan antivirus fichiers upload\u00e9s (ClamAV)
- \u26a0\ufe0f Quarantaine fichiers suspects
- \u26a0\ufe0f Whitelist extensions autoris\u00e9es stricte
- \u26a0\ufe0f R\u00e9\u00e9criture noms fichiers (hash)

**Exemple impl\u00e9mentation** :
```php
// app/Services/FileUploadService.php
public function scanFile(string $filePath): bool {
    // ClamAV scan
    exec("clamscan --infected --remove=no " . escapeshellarg($filePath), $output, $returnCode);
    return $returnCode === 0; // 0 = clean, 1 = infected
}
```

---

### 6\ufe0f\u20e3 Exigence 6 : D\u00e9velopper des syst\u00e8mes s\u00e9curis\u00e9s

**Statut** : \ud83d\udfe1 PARTIELLEMENT CONFORME

**Mesures impl\u00e9ment\u00e9es** :
- \u2705 Requ\u00eates pr\u00e9par\u00e9es PDO (anti SQL Injection)
- \u2705 Tokens CSRF sur formulaires
- \u2705 `htmlspecialchars()` sur affichages (anti XSS)
- \u2705 Validation inputs c\u00f4t\u00e9 serveur

**\u00c0 impl\u00e9menter** :
- \u26a0\ufe0f Content Security Policy (CSP)
- \u26a0\ufe0f Protection SSRF (Server-Side Request Forgery)
- \u26a0\ufe0f Protection Path Traversal sur fichiers
- \u26a0\ufe0f Rate limiting API/formulaires
- \u26a0\ufe0f Tests s\u00e9curit\u00e9 automatis\u00e9s (OWASP ZAP)

**Headers s\u00e9curit\u00e9 recommand\u00e9s** :
```php
// app/Core/Security.php
public static function setSecurityHeaders(): void {
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' cdn.jsdelivr.net;");
    header("X-Frame-Options: DENY");
    header("X-Content-Type-Options: nosniff");
    header("Referrer-Policy: strict-origin-when-cross-origin");
    header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
}
```

---

### 7\ufe0f\u20e3 Exigence 7 : Restreindre l'acc\u00e8s aux donn\u00e9es

**Statut** : \ud83d\udfe1 PARTIELLEMENT CONFORME

**Mesures impl\u00e9ment\u00e9es** :
- \u2705 Contr\u00f4le acc\u00e8s basique (session user_id)
- \u2705 R\u00f4les admin/user
- \u2705 Isolation donn\u00e9es par utilisateur (WHERE user_id)

**\u00c0 impl\u00e9menter** :
- \u26a0\ufe0f RBAC (Role-Based Access Control) complet
- \u26a0\ufe0f Principe du moindre privil\u00e8ge
- \u26a0\ufe0f Logs acc\u00e8s donn\u00e9es sensibles
- \u26a0\ufe0f Permissions granulaires (lecture/\u00e9criture/suppression)
- \u26a0\ufe0f S\u00e9paration duties (admin ne peut pas modifier transactions)

**Exemple RBAC** :
```php
// app/Core/Authorization.php
class Authorization {
    public static function can(string $action, string $resource): bool {
        $role = $_SESSION['user']['role'] ?? 'guest';
        $permissions = [
            'admin' => ['*'], // Tous acc\u00e8s
            'user' => ['read_transactions', 'write_own_transactions'],
            'viewer' => ['read_transactions']
        ];
        
        return in_array($action, $permissions[$role] ?? []) 
            || in_array('*', $permissions[$role] ?? []);
    }
}
```

---

### 8\ufe0f\u20e3 Exigence 8 : Identifier les utilisateurs et authentifier l'acc\u00e8s

**Statut** : \ud83d\udfe1 PARTIELLEMENT CONFORME

**Mesures impl\u00e9ment\u00e9es** :
- \u2705 Mots de passe hash\u00e9s avec `password_hash()` (bcrypt)
- \u2705 Sessions s\u00e9curis\u00e9es avec r\u00e9g\u00e9n\u00e9ration ID
- \u2705 Remember me token (basique)

**\u00c0 impl\u00e9menter** - **PRIORIT\u00c9 HAUTE** :

#### \ud83d\udd11 A. Politique mots de passe forts

```php
// app/Services/PasswordPolicy.php
class PasswordPolicy {
    const MIN_LENGTH = 12;
    const REQUIRE_UPPERCASE = true;
    const REQUIRE_LOWERCASE = true;
    const REQUIRE_NUMBERS = true;
    const REQUIRE_SPECIAL = true;
    const HISTORY_COUNT = 5; // Ne pas r\u00e9utiliser 5 derniers mots de passe
    const MAX_AGE_DAYS = 90; // Expiration tous les 90 jours
    
    public static function validate(string $password): array {
        $errors = [];
        
        if (strlen($password) < self::MIN_LENGTH) {
            $errors[] = "Minimum " . self::MIN_LENGTH . " caract\u00e8res";
        }
        
        if (self::REQUIRE_UPPERCASE && !preg_match('/[A-Z]/', $password)) {
            $errors[] = "Au moins une majuscule requise";
        }
        
        if (self::REQUIRE_LOWERCASE && !preg_match('/[a-z]/', $password)) {
            $errors[] = "Au moins une minuscule requise";
        }
        
        if (self::REQUIRE_NUMBERS && !preg_match('/[0-9]/', $password)) {
            $errors[] = "Au moins un chiffre requis";
        }
        
        if (self::REQUIRE_SPECIAL && !preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = "Au moins un caract\u00e8re sp\u00e9cial requis";
        }
        
        return $errors;
    }
}
```

#### \ud83d\udd12 B. Verrouillage de compte

```php
// app/Services/LoginAttemptService.php
class LoginAttemptService {
    const MAX_ATTEMPTS = 5;
    const LOCKOUT_DURATION = 900; // 15 minutes en secondes
    
    public static function recordFailedAttempt(string $email): void {
        $key = 'login_attempts_' . md5($email);
        $_SESSION[$key] = ($_SESSION[$key] ?? 0) + 1;
        $_SESSION[$key . '_time'] = time();
    }
    
    public static function isLocked(string $email): bool {
        $key = 'login_attempts_' . md5($email);
        $attempts = $_SESSION[$key] ?? 0;
        $lastAttempt = $_SESSION[$key . '_time'] ?? 0;
        
        if ($attempts >= self::MAX_ATTEMPTS) {
            if (time() - $lastAttempt < self::LOCKOUT_DURATION) {
                return true; // Encore verrouill\u00e9
            } else {
                // D\u00e9verrouillage automatique
                unset($_SESSION[$key], $_SESSION[$key . '_time']);
                return false;
            }
        }
        
        return false;
    }
    
    public static function resetAttempts(string $email): void {
        $key = 'login_attempts_' . md5($email);
        unset($_SESSION[$key], $_SESSION[$key . '_time']);
    }
}
```

#### \ud83d\udd50 C. Timeout session automatique

```php
// app/Core/SessionManager.php
class SessionManager {
    const SESSION_TIMEOUT = 1800; // 30 minutes
    const SESSION_ABSOLUTE_TIMEOUT = 28800; // 8 heures
    
    public static function checkTimeout(): void {
        if (!isset($_SESSION['last_activity'])) {
            $_SESSION['last_activity'] = time();
            $_SESSION['created_at'] = time();
            return;
        }
        
        // Timeout inactivit\u00e9
        if (time() - $_SESSION['last_activity'] > self::SESSION_TIMEOUT) {
            session_destroy();
            header('Location: /login?timeout=inactivity');
            exit;
        }
        
        // Timeout absolu
        if (time() - $_SESSION['created_at'] > self::SESSION_ABSOLUTE_TIMEOUT) {
            session_destroy();
            header('Location: /login?timeout=absolute');
            exit;
        }
        
        $_SESSION['last_activity'] = time();
    }
}
```

#### \ud83d\udd10 D. Authentification 2 facteurs (2FA) - OPTIONNEL

```php
// app/Services/TwoFactorService.php (avec Google Authenticator)
// Utiliser library: pragmarx/google2fa
class TwoFactorService {
    public static function generateSecret(): string {
        $google2fa = new \PragmaRx\Google2FA\Google2FA();
        return $google2fa->generateSecretKey();
    }
    
    public static function verify(string $secret, string $code): bool {
        $google2fa = new \PragmaRx\Google2FA\Google2FA();
        return $google2fa->verifyKey($secret, $code);
    }
}
```

---

### 9\ufe0f\u20e3 Exigence 9 : Restreindre l'acc\u00e8s physique

**Statut** : \u26aa NON APPLICABLE (h\u00e9bergement mutualis\u00e9)

**Mesures d\u00e9l\u00e9gu\u00e9es \u00e0 l'h\u00e9bergeur** :
- Acc\u00e8s physique datacenter
- Contr\u00f4le badges/biom\u00e9trie
- Vid\u00e9osurveillance
- D\u00e9tection intrusion physique

**Responsabilit\u00e9 applicative** :
- \u2705 Aucune donn\u00e9e sensible en fichiers locaux (tout en BDD chiffr\u00e9e)
- \u2705 Logs rotate et archiv\u00e9s (pas de retention infinie)

---

### \ud83d\udd1f Exigence 10 : Journaliser et surveiller tous les acc\u00e8s

**Statut** : \ud83d\udd34 NON CONFORME - **PRIORIT\u00c9 HAUTE**

**\u00c0 impl\u00e9menter** :

#### \ud83d\udcc4 A. Syst\u00e8me de logging complet

```php
// app/Services/AuditLogger.php
class AuditLogger {
    private static $logFile = __DIR__ . '/../../storage/logs/audit.log';
    
    public static function log(string $action, array $context = []): void {
        $entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'user_id' => $_SESSION['user_id'] ?? null,
            'username' => $_SESSION['user']['username'] ?? 'anonymous',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'action' => $action,
            'context' => $context,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ];
        
        file_put_contents(
            self::$logFile,
            json_encode($entry) . PHP_EOL,
            FILE_APPEND
        );
    }
    
    // \u00c9v\u00e9nements \u00e0 logger
    public static function logLogin(int $userId, bool $success): void {
        self::log('LOGIN', ['user_id' => $userId, 'success' => $success]);
    }
    
    public static function logDataAccess(string $table, int $recordId): void {
        self::log('DATA_ACCESS', ['table' => $table, 'record_id' => $recordId]);
    }
    
    public static function logDataModification(string $table, int $recordId, array $changes): void {
        self::log('DATA_MODIFY', ['table' => $table, 'record_id' => $recordId, 'changes' => $changes]);
    }
    
    public static function logFailedAuth(string $email): void {
        self::log('AUTH_FAILED', ['email' => $email]);
    }
}
```

#### \ud83d\udea8 B. Alertes s\u00e9curit\u00e9

```php
// app/Services/SecurityAlertService.php
class SecurityAlertService {
    public static function detectSuspiciousActivity(): void {
        // D\u00e9tecter tentatives brute force
        $recentFailures = self::countRecentLoginFailures();
        if ($recentFailures > 10) {
            self::sendAlert('Tentatives brute force d\u00e9tect\u00e9es', [
                'failures' => $recentFailures,
                'ip' => $_SERVER['REMOTE_ADDR']
            ]);
        }
        
        // D\u00e9tecter acc\u00e8s anormaux (heures inhabituelles)
        if (self::isUnusualAccessTime()) {
            self::sendAlert('Acc\u00e8s hors heures normales', [
                'user' => $_SESSION['user']['username'],
                'time' => date('H:i:s')
            ]);
        }
    }
    
    private static function sendAlert(string $message, array $context): void {
        // Email admin
        mail(
            'admin@monbudget.com',
            '[ALERTE S\u00c9CURIT\u00c9] ' . $message,
            json_encode($context, JSON_PRETTY_PRINT)
        );
        
        // Log critique
        AuditLogger::log('SECURITY_ALERT', ['message' => $message, 'context' => $context]);
    }
}
```

#### \ud83d\udd04 C. Rotation et archivage logs

```php
// cli/rotate-logs.php
// Ex\u00e9cuter quotidiennement via cron
$logFile = __DIR__ . '/../storage/logs/audit.log';
$archiveDir = __DIR__ . '/../storage/logs/archive/';

if (file_exists($logFile) && filesize($logFile) > 10 * 1024 * 1024) { // 10 MB
    $archiveName = 'audit_' . date('Y-m-d_His') . '.log';
    rename($logFile, $archiveDir . $archiveName);
    
    // Compresser
    exec("gzip " . escapeshellarg($archiveDir . $archiveName));
    
    // Supprimer archives > 1 an
    $oldArchives = glob($archiveDir . 'audit_*');
    foreach ($oldArchives as $archive) {
        if (filemtime($archive) < strtotime('-1 year')) {
            unlink($archive);
        }
    }
}
```

**\u00c9v\u00e9nements \u00e0 logger obligatoirement** :
- \u2705 Tentatives de connexion (success/fail)
- \u2705 Acc\u00e8s donn\u00e9es sensibles (IBAN, comptes)
- \u2705 Modifications donn\u00e9es critiques
- \u2705 Cr\u00e9ation/suppression utilisateurs
- \u2705 Changements permissions/r\u00f4les
- \u2705 Exports de donn\u00e9es
- \u2705 Erreurs s\u00e9curit\u00e9 (CSRF fail, injection tentative)

---

### 1\ufe0f\u20e31\ufe0f\u20e3 Exigence 11 : Tester r\u00e9guli\u00e8rement la s\u00e9curit\u00e9

**Statut** : \ud83d\udd34 NON CONFORME

**\u00c0 impl\u00e9menter** :

#### \ud83e\uddea A. Tests s\u00e9curit\u00e9 automatis\u00e9s

```php
// tests/Security/SecurityTest.php
class SecurityTest extends TestCase {
    public function testSqlInjectionProtection() {
        // Tenter injection SQL
        $maliciousInput = "' OR '1'='1";
        $result = Database::selectOne(
            "SELECT * FROM users WHERE email = ?",
            [$maliciousInput]
        );
        $this->assertNull($result);
    }
    
    public function testXssProtection() {
        $maliciousScript = '<script>alert("XSS")</script>';
        $sanitized = htmlspecialchars($maliciousScript);
        $this->assertStringNotContainsString('<script>', $sanitized);
    }
    
    public function testCsrfProtection() {
        $_POST['csrf_token'] = 'invalid_token';
        $result = validateCsrf();
        $this->assertFalse($result);
    }
}
```

#### \ud83d\udd75\ufe0f B. Scan vuln\u00e9rabilit\u00e9s (OWASP ZAP)

```bash
# cli/security-scan.sh
#!/bin/bash

# Lancer OWASP ZAP en mode headless
docker run -v $(pwd):/zap/wrk/:rw \
    -t owasp/zap2docker-stable \
    zap-baseline.py \
    -t https://www.tedcampa.org/monbudget \
    -r zap_report.html

# Analyser rapport
if grep -q "High\|Critical" zap_report.html; then
    echo "VULN\u00c9RABILIT\u00c9S CRITIQUES D\u00c9TECT\u00c9ES"
    exit 1
fi
```

#### \ud83d\udce6 C. Audit d\u00e9pendances

```bash
# V\u00e9rifier vuln\u00e9rabilit\u00e9s d\u00e9pendances Composer
composer audit

# Mise \u00e0 jour automatique (hebdomadaire)
composer update --with-dependencies
```

**Fr\u00e9quence tests** :
- Tests unitaires s\u00e9curit\u00e9 : \u00e0 chaque commit
- Scan OWASP ZAP : hebdomadaire
- Audit d\u00e9pendances : hebdomadaire
- Pentest manuel : annuel (par expert externe)

---

### 1\ufe0f\u20e32\ufe0f\u20e3 Exigence 12 : Politique de s\u00e9curit\u00e9 de l'information

**Statut** : \ud83d\udfe1 PARTIELLEMENT CONFORME

**Documents existants** :
- \u2705 `SECURITY.md` - Consignes s\u00e9curit\u00e9 d\u00e9veloppeurs
- \u2705 `.ai-instructions` - R\u00e8gles s\u00e9curit\u00e9 IA
- \u2705 `security-audit.ps1` - Script audit avant push

**\u00c0 cr\u00e9er** :
- \u26a0\ufe0f Politique s\u00e9curit\u00e9 formelle (ce document)
- \u26a0\ufe0f Proc\u00e9dure r\u00e9ponse incidents
- \u26a0\ufe0f Formation utilisateurs/d\u00e9veloppeurs
- \u26a0\ufe0f Revue s\u00e9curit\u00e9 annuelle

---

## \ud83d\udcc5 Plan d'action prioritaire

### Phase 1 : CRITIQUE (1-2 semaines)

1. \u2705 **Impl\u00e9menter EncryptionService** (AES-256-GCM)
2. \u2705 **Migrer IBAN/comptes vers colonnes chiffr\u00e9es**
3. \u2705 **Politique mots de passe forts** (12 caract\u00e8res minimum)
4. \u2705 **Verrouillage compte apr\u00e8s 5 \u00e9checs**
5. \u2705 **AuditLogger complet** (login, acc\u00e8s donn\u00e9es, modifications)

### Phase 2 : HAUTE (2-4 semaines)

6. \u2705 **Headers s\u00e9curit\u00e9 HTTP** (CSP, HSTS, X-Frame-Options)
7. \u2705 **Timeout session** (30 min inactivit\u00e9, 8h absolu)
8. \u2705 **RBAC granulaire** (permissions lecture/\u00e9criture par ressource)
9. \u2705 **Rotation logs automatique**
10. \u2705 **Tests s\u00e9curit\u00e9 PHPUnit**

### Phase 3 : MOYENNE (1-2 mois)

11. \u2705 **2FA optionnel** (Google Authenticator)
12. \u2705 **Scan antivirus uploads** (ClamAV)
13. \u2705 **Rate limiting** (anti brute force)
14. \u2705 **Alertes s\u00e9curit\u00e9 email**
15. \u2705 **OWASP ZAP scan hebdomadaire**

### Phase 4 : BASSE (maintenance continue)

16. \u2705 **TLS 1.3** (serveur Apache)
17. \u2705 **Chiffrement connexions BDD** (MySQL SSL)
18. \u2705 **WAF** (Web Application Firewall)
19. \u2705 **Pentest annuel externe**
20. \u2705 **Formation s\u00e9curit\u00e9 \u00e9quipe**

---

## \ud83d\udcca Indicateurs de conformit\u00e9

| Exigence | Statut | Conformit\u00e9 | Priorit\u00e9 |
|----------|--------|---------------|-----------|
| 1. Contr\u00f4les r\u00e9seau | \ud83d\udfe1 Partiel | 50% | Moyenne |
| 2. Configurations s\u00e9curis\u00e9es | \ud83d\udfe1 Partiel | 60% | Moyenne |
| **3. Donn\u00e9es stock\u00e9es** | \ud83d\udd34 **Non conforme** | **0%** | **CRITIQUE** |
| 4. Donn\u00e9es en transit | \ud83d\udfe1 Partiel | 70% | Haute |
| 5. Protection malwares | \ud83d\udfe1 Partiel | 40% | Moyenne |
| 6. Syst\u00e8mes s\u00e9curis\u00e9s | \ud83d\udfe1 Partiel | 65% | Haute |
| 7. Contr\u00f4le d'acc\u00e8s | \ud83d\udfe1 Partiel | 50% | Haute |
| **8. Authentification** | \ud83d\udfe1 **Partiel** | **40%** | **HAUTE** |
| 9. Acc\u00e8s physique | \u26aa N/A | N/A | N/A |
| **10. Journalisation** | \ud83d\udd34 **Non conforme** | **10%** | **HAUTE** |
| 11. Tests s\u00e9curit\u00e9 | \ud83d\udd34 Non conforme | 20% | Haute |
| 12. Politique s\u00e9curit\u00e9 | \ud83d\udfe1 Partiel | 50% | Moyenne |

**Score global** : 42% \ud83d\udd34  
**Objectif** : 90%+ \ud83d\udfe2

---

## \ud83d\udee0\ufe0f Checklist de validation

### Chiffrement
- [ ] EncryptionService impl\u00e9ment\u00e9
- [ ] IBAN chiffr\u00e9s en BDD
- [ ] Num\u00e9ros compte chiffr\u00e9s
- [ ] Cl\u00e9 de chiffrement dans .env
- [ ] Rotation cl\u00e9s proc\u00e9dure d\u00e9finie

### Authentification
- [ ] Politique mots de passe 12+ caract\u00e8res
- [ ] Verrouillage compte 5 \u00e9checs
- [ ] Timeout session 30 min
- [ ] R\u00e9g\u00e9n\u00e9ration ID session
- [ ] 2FA impl\u00e9ment\u00e9 (optionnel)

### Journalisation
- [ ] AuditLogger impl\u00e9ment\u00e9
- [ ] Logs connexion
- [ ] Logs acc\u00e8s donn\u00e9es sensibles
- [ ] Logs modifications
- [ ] Rotation logs automatique
- [ ] Alertes email configur\u00e9es

### S\u00e9curit\u00e9 applicative
- [ ] Headers s\u00e9curit\u00e9 HTTP
- [ ] CSP configur\u00e9
- [ ] HSTS activ\u00e9
- [ ] Protection XSS
- [ ] Protection CSRF
- [ ] Rate limiting

### Tests
- [ ] Tests s\u00e9curit\u00e9 PHPUnit
- [ ] OWASP ZAP scan
- [ ] Composer audit
- [ ] Pentest externe annuel

---

## \ud83d\udcde Contacts s\u00e9curit\u00e9

**Responsable s\u00e9curit\u00e9** : [Nom]  
**Email** : security@monbudget.com  
**T\u00e9l\u00e9phone urgence** : [Num\u00e9ro]

**Incident de s\u00e9curit\u00e9** : security-incident@monbudget.com  
**Vuln\u00e9rabilit\u00e9 d\u00e9couverte** : vulnerability@monbudget.com

---

## \ud83d\udcda R\u00e9f\u00e9rences

- [PCI DSS v4.0 Official Documentation](https://www.pcisecuritystandards.org/)
- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [PHP Security Best Practices](https://www.php.net/manual/en/security.php)
- [NIST Cybersecurity Framework](https://www.nist.gov/cyberframework)

---

**Derni\u00e8re r\u00e9vision** : 18 novembre 2025  
**Prochaine revue** : 18 f\u00e9vrier 2026 (trimestrielle)
