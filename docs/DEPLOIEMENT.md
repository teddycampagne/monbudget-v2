# Guide de Déploiement - MonBudget v2

## 📋 Prérequis Serveur

- **OS** : Linux (Ubuntu 20.04+, Debian 10+, CentOS 8+)
- **Serveur Web** : Apache 2.4+ avec `mod_rewrite` activé
- **PHP** : 8.0+ avec extensions :
  - `pdo_mysql`
  - `mbstring`
  - `json`
  - `curl`
  - `zip`
  - `xml`
- **Base de données** : MySQL 8.0+ ou MariaDB 10.5+
- **Composer** : 2.0+
- **Git** : Pour cloner le dépôt

---

## 🚀 Installation sur Serveur de Production

### 1. Cloner le Dépôt

```bash
cd /var/www  # ou votre dossier web
git clone https://github.com/teddycampagne/monbudget-v2.git monbudget
cd monbudget
git checkout main  # ou develop selon votre branche
```

### 2. Installer les Dépendances

```bash
composer install --no-dev --optimize-autoloader
```

### 3. Configuration Apache

#### a) Activer mod_rewrite

```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

#### b) Configurer VirtualHost

Créer `/etc/apache2/sites-available/monbudget.conf` :

```apache
<VirtualHost *:80>
    ServerName monbudget.votredomaine.com
    DocumentRoot /var/www/monbudget
    
    <Directory /var/www/monbudget>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    # Logs
    ErrorLog ${APACHE_LOG_DIR}/monbudget-error.log
    CustomLog ${APACHE_LOG_DIR}/monbudget-access.log combined
</VirtualHost>
```

Activer le site :

```bash
sudo a2ensite monbudget.conf
sudo systemctl reload apache2
```

### 4. Ajuster .htaccess

**IMPORTANT** : Le fichier `.htaccess` est maintenant configuré pour détecter automatiquement le chemin de base.

Si l'application est à la **racine** du domaine (ex: `https://budget.com/`) :
- ✅ Aucune modification nécessaire

Si l'application est dans un **sous-dossier** (ex: `https://monsite.com/budget/`) :
- Décommenter et ajuster la ligne dans `.htaccess` :
  ```apache
  RewriteBase /budget
  ```

### 5. Configurer l'Application

#### a) Copier le fichier de configuration exemple

```bash
cp config/installed.json.example config/installed.json
```

#### b) Éditer `config/app.php`

```php
return [
    'app' => [
        'name' => 'MonBudget',
        'version' => '2.2.10',
        'env' => 'production',  // ⚠️ IMPORTANT
        'debug' => false,        // ⚠️ IMPORTANT
        'url' => 'https://monbudget.votredomaine.com',
        'timezone' => 'Europe/Paris'
    ],
    
    'database' => [
        'driver' => 'mysql',
        'host' => 'localhost',  // ou IP de votre serveur MySQL
        'port' => 3306,
        'name' => 'monbudget_prod',
        'username' => 'monbudget_user',
        'password' => 'VOTRE_MOT_DE_PASSE_SECURISE',
        'charset' => 'utf8mb4',
        // ...
    ],
    // ...
];
```

### 6. Créer la Base de Données

```bash
mysql -u root -p
```

```sql
CREATE DATABASE monbudget_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'monbudget_user'@'localhost' IDENTIFIED BY 'VOTRE_MOT_DE_PASSE_SECURISE';
GRANT ALL PRIVILEGES ON monbudget_prod.* TO 'monbudget_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

Importer le schéma :

```bash
mysql -u monbudget_user -p monbudget_prod < database/database.sql
```

### 7. Permissions Fichiers

```bash
# Propriétaire Apache (www-data sur Ubuntu/Debian, apache sur CentOS)
sudo chown -R www-data:www-data /var/www/monbudget

# Permissions
sudo find /var/www/monbudget -type d -exec chmod 755 {} \;
sudo find /var/www/monbudget -type f -exec chmod 644 {} \;

# Dossiers en écriture
sudo chmod -R 775 storage/
sudo chmod -R 775 uploads/
```

### 8. Sécurité Supplémentaire

#### a) Bloquer accès .git en production

Vérifier que `.htaccess` racine contient :

```apache
<FilesMatch "^(\.env|\.git|composer\.json|composer\.lock|\.gitignore)$">
    Require all denied
</FilesMatch>
```

#### b) SSL/HTTPS (Recommandé avec Let's Encrypt)

```bash
sudo apt install certbot python3-certbot-apache
sudo certbot --apache -d monbudget.votredomaine.com
```

---

## 🔧 Problèmes Courants

### ❌ Erreur 404 sur toutes les routes

**Cause** : `mod_rewrite` non activé ou `.htaccess` ignoré

**Solution** :
```bash
# Vérifier mod_rewrite
sudo apache2ctl -M | grep rewrite
# Si absent :
sudo a2enmod rewrite
sudo systemctl restart apache2

# Vérifier AllowOverride dans VirtualHost
# Doit être : AllowOverride All
```

### ❌ Routes 404 avec sous-dossier

**Cause** : `RewriteBase` non défini

**Solution** : Dans `.htaccess`, décommenter et ajuster :
```apache
RewriteBase /votre-sous-dossier
```

### ❌ Fichier `installed.json` manquant

**Cause** : Fichier ignoré par Git (normal)

**Solution** :
```bash
cp config/installed.json.example config/installed.json
# Puis lancer l'assistant d'installation via navigateur
```

### ❌ Erreurs de permissions

**Cause** : Apache ne peut pas écrire dans `storage/` ou `uploads/`

**Solution** :
```bash
sudo chown -R www-data:www-data storage/ uploads/
sudo chmod -R 775 storage/ uploads/
```

### ❌ Page blanche (erreur 500)

**Cause** : Erreur PHP non affichée en production

**Solution** : Consulter les logs
```bash
tail -f /var/log/apache2/monbudget-error.log
# ou
tail -f storage/logs/app.log
```

---

## 📝 Checklist de Déploiement

- [ ] Git clone effectué
- [ ] Composer install exécuté
- [ ] `mod_rewrite` activé
- [ ] VirtualHost configuré avec `AllowOverride All`
- [ ] `.htaccess` ajusté si sous-dossier
- [ ] `config/app.php` configuré (env=production, debug=false, url correcte)
- [ ] Base de données créée et importée
- [ ] Permissions fichiers correctes (775 sur storage/ et uploads/)
- [ ] `config/installed.json` copié depuis .example
- [ ] SSL/HTTPS configuré (recommandé)
- [ ] Test accès : page de connexion s'affiche
- [ ] Test connexion : authentification fonctionne
- [ ] Test routes : /dashboard, /comptes, /transactions accessibles

---

## 🔄 Mise à Jour

```bash
cd /var/www/monbudget
git pull origin main
composer install --no-dev --optimize-autoloader
# Si migrations nécessaires :
# php cli/migrate.php
sudo systemctl reload apache2
```

---

## 📞 Support

- **Documentation** : `/docs`
- **Issues GitHub** : https://github.com/teddycampagne/monbudget-v2/issues
- **Version actuelle** : 2.2.10
