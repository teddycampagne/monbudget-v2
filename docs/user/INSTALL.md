# Guide d'installation MonBudget

## Prérequis

### Logiciels requis

- **Serveur Web** : Apache 2.4+ ou Nginx 1.18+
- **PHP** : Version 8.1 ou supérieure
- **Base de données** : MySQL 8.0+ ou MariaDB 10.5+
- **Composer** : Gestionnaire de dépendances PHP

### Extensions PHP requises

- `pdo_mysql` : Connexion à la base de données
- `mbstring` : Manipulation de chaînes multibytes
- `json` : Manipulation JSON
- `session` : Gestion des sessions
- `gd` ou `imagick` : Manipulation d'images (pour les logos)
- `zip` : Extraction d'archives
- `xml` : Manipulation XML (pour import OFX)

Vérifiez les extensions installées :
```bash
php -m
```

## Installation

### 1. Téléchargement

#### Option A : Clone Git
```bash
git clone https://github.com/votre-repo/monbudget.git
cd monbudget
```

#### Option B : Archive ZIP
1. Téléchargez l'archive depuis GitHub
2. Extrayez dans votre dossier web (`/var/www/html` ou `C:\wamp64\www`)
3. Renommez le dossier en `monbudget` ou `monbudgetV2`

### 2. Installation des dépendances

```bash
composer install
```

Si vous n'avez pas Composer :
```bash
# Linux/Mac
curl -sS https://getcomposer.org/installer | php
php composer.phar install

# Windows
# Téléchargez et installez depuis https://getcomposer.org/
```

### 3. Configuration de la base de données

#### Création de la base

```sql
CREATE DATABASE monbudget CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'monbudget_user'@'localhost' IDENTIFIED BY 'votre_mot_de_passe_securise';
GRANT ALL PRIVILEGES ON monbudget.* TO 'monbudget_user'@'localhost';
FLUSH PRIVILEGES;
```

#### Configuration de la connexion

Créez le fichier `config/database.php` (copiez depuis `database.php.example`) :

```php
<?php
return [
    'host' => 'localhost',
    'database' => 'monbudget',
    'username' => 'monbudget_user',
    'password' => 'votre_mot_de_passe_securise',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
];
```

#### Import du schéma

```bash
# Import du schéma complet
mysql -u monbudget_user -p monbudget < database/database.sql

# (Optionnel) Import des données de démonstration
mysql -u monbudget_user -p monbudget < database/database_sample_data.sql
```

### 4. Configuration des permissions

#### Linux/Mac
```bash
# Propriétaire des fichiers
sudo chown -R www-data:www-data /var/www/html/monbudget

# Permissions des dossiers
sudo chmod -R 755 /var/www/html/monbudget

# Permissions d'écriture pour storage et uploads
sudo chmod -R 775 storage/ uploads/
sudo chown -R www-data:www-data storage/ uploads/
```

#### Windows (WAMP/XAMPP)
Les permissions sont généralement correctes par défaut. Vérifiez que les dossiers suivants sont accessibles en écriture :
- `storage/logs/`
- `storage/cache/`
- `storage/sessions/`
- `uploads/imports/`
- `uploads/logos/`

### 5. Configuration du serveur web

#### Apache

Créez un VirtualHost (`/etc/apache2/sites-available/monbudget.conf`) :

```apache
<VirtualHost *:80>
    ServerName monbudget.local
    DocumentRoot /var/www/html/monbudget
    
    <Directory /var/www/html/monbudget>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
        
        # Redirection vers index.php
        RewriteEngine On
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule ^ index.php [L]
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/monbudget_error.log
    CustomLog ${APACHE_LOG_DIR}/monbudget_access.log combined
</VirtualHost>
```

Activez le site :
```bash
sudo a2ensite monbudget
sudo a2enmod rewrite
sudo systemctl reload apache2
```

Ajoutez à `/etc/hosts` :
```
127.0.0.1 monbudget.local
```

#### Nginx

Créez un bloc serveur (`/etc/nginx/sites-available/monbudget`) :

```nginx
server {
    listen 80;
    server_name monbudget.local;
    root /var/www/html/monbudget;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

Activez le site :
```bash
sudo ln -s /etc/nginx/sites-available/monbudget /etc/nginx/sites-enabled/
sudo systemctl reload nginx
```

#### WAMP/XAMPP (Windows)

L'application fonctionne directement dans un sous-dossier :
- WAMP : `http://localhost/monbudgetV2/`
- XAMPP : `http://localhost/monbudgetV2/`

Pas de configuration spécifique requise.

### 6. Assistant d'installation web

Accédez à l'assistant d'installation :
```
http://monbudget.local/setup/
```
ou
```
http://localhost/monbudgetV2/setup/
```

L'assistant va :
1. Vérifier les prérequis PHP et extensions
2. Tester la connexion à la base de données
3. Créer les tables si nécessaire
4. Créer le compte administrateur
5. Générer les fichiers de configuration

### 7. Création du compte administrateur

Si vous n'utilisez pas l'assistant, créez le compte manuellement :

```sql
INSERT INTO utilisateurs (nom, email, password, role, actif, created_at)
VALUES (
    'Administrateur',
    'admin@monbudget.local',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: password
    'admin',
    1,
    NOW()
);
```

**Important** : Changez ce mot de passe après la première connexion !

### 8. Configuration finale

#### Variables d'environnement

Créez `.env` (optionnel, pour la production) :

```ini
APP_ENV=production
APP_DEBUG=false
APP_URL=https://monbudget.votredomaine.com

DB_HOST=localhost
DB_NAME=monbudget
DB_USER=monbudget_user
DB_PASS=votre_mot_de_passe_securise

SESSION_LIFETIME=120
```

#### Sécurité

1. **Changez le mot de passe admin** immédiatement
2. **Supprimez le dossier setup/** après installation :
   ```bash
   rm -rf setup/
   ```
3. **Configurez HTTPS** en production
4. **Activez les backups automatiques** de la base de données

## Vérification de l'installation

### Checklist

- [ ] Page d'accueil accessible sans erreur
- [ ] Connexion avec le compte admin fonctionne
- [ ] Upload de logo fonctionne
- [ ] Import CSV/OFX fonctionne
- [ ] Génération de rapports PDF fonctionne
- [ ] Aucune erreur PHP dans les logs

### Tests

```bash
# Vérifier les logs d'erreurs
tail -f storage/logs/app-*.log

# Tester la connexion DB
php -r "require 'app/Core/Database.php'; var_dump(MonBudget\Core\Database::getInstance());"

# Vérifier les permissions
ls -la storage/ uploads/
```

### Problèmes courants

#### Erreur "Class not found"
```bash
composer dump-autoload
```

#### Erreur de connexion DB
- Vérifiez les identifiants dans `config/database.php`
- Testez la connexion : `mysql -u monbudget_user -p`
- Vérifiez que le serveur MySQL est démarré

#### Erreur 500 sans message
- Activez l'affichage des erreurs dans `index.php` :
  ```php
  ini_set('display_errors', 1);
  error_reporting(E_ALL);
  ```
- Consultez les logs Apache/Nginx

#### Upload échoue
- Vérifiez `php.ini` :
  ```ini
  upload_max_filesize = 10M
  post_max_size = 10M
  ```
- Vérifiez les permissions sur `uploads/`

## Mise à jour

### Depuis une version antérieure

```bash
# Sauvegardez la base de données
mysqldump -u monbudget_user -p monbudget > backup-$(date +%Y%m%d).sql

# Mettez à jour les fichiers
git pull origin main

# Installez les nouvelles dépendances
composer install --no-dev

# Exécutez les migrations
php database/migrate.php

# Videz le cache
rm -rf storage/cache/*
```

### Migrations de base de données

Les fichiers de migration se trouvent dans `database/migrations/`. Appliquez-les dans l'ordre :

```bash
mysql -u monbudget_user -p monbudget < database/migrations/001_add_recurring_transactions.sql
mysql -u monbudget_user -p monbudget < database/migrations/002_add_budgets_table.sql
# etc.
```

## Configuration avancée

### Sauvegarde automatique

Créez un script cron (`/etc/cron.daily/monbudget-backup`) :

```bash
#!/bin/bash
BACKUP_DIR="/var/backups/monbudget"
DATE=$(date +%Y%m%d_%H%M%S)

mkdir -p $BACKUP_DIR

# Backup DB
mysqldump -u monbudget_user -p'password' monbudget | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# Backup fichiers
tar -czf $BACKUP_DIR/files_$DATE.tar.gz /var/www/html/monbudget/uploads

# Suppression des backups > 30 jours
find $BACKUP_DIR -name "*.gz" -mtime +30 -delete

echo "Backup completed: $DATE" >> /var/log/monbudget-backup.log
```

Rendez-le exécutable :
```bash
chmod +x /etc/cron.daily/monbudget-backup
```

### Performance

#### Activation du cache d'opcache

Dans `php.ini` :
```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
```

#### Optimisation Composer

```bash
composer install --optimize-autoloader --no-dev
```

## Support

- **Documentation utilisateur** : `/documentation/guide`
- **FAQ** : `/documentation/faq`
- **Issues GitHub** : https://github.com/votre-repo/monbudget/issues
- **Email support** : support@monbudget.local

## Licence

MonBudget est un logiciel open-source sous licence MIT.
