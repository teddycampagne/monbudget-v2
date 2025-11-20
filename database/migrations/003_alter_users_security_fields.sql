-- Migration: Ajout champs sécurité à table users pour conformité PCI DSS
-- Date: 2025-11-20
-- Description: Gestion expiration mots de passe, verrouillage compte, historique

-- Vérifier si les colonnes existent déjà avant de les ajouter
SET @dbname = DATABASE();
SET @tablename = 'users';

-- Ajouter password_expires_at si n'existe pas
SET @column_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'password_expires_at');
SET @sql = IF(@column_exists = 0, 
    'ALTER TABLE `users` ADD COLUMN `password_expires_at` datetime DEFAULT NULL COMMENT ''Date d\'\'expiration du mot de passe (90 jours)''',
    'SELECT ''Column password_expires_at already exists'' AS msg');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ajouter failed_login_attempts si n'existe pas
SET @column_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'failed_login_attempts');
SET @sql = IF(@column_exists = 0,
    'ALTER TABLE `users` ADD COLUMN `failed_login_attempts` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT ''Nombre de tentatives échouées consécutives''',
    'SELECT ''Column failed_login_attempts already exists'' AS msg');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ajouter locked_until si n'existe pas
SET @column_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'locked_until');
SET @sql = IF(@column_exists = 0,
    'ALTER TABLE `users` ADD COLUMN `locked_until` datetime DEFAULT NULL COMMENT ''Compte verrouillé jusqu\'\'à cette date''',
    'SELECT ''Column locked_until already exists'' AS msg');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ajouter last_password_change si n'existe pas
SET @column_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'last_password_change');
SET @sql = IF(@column_exists = 0,
    'ALTER TABLE `users` ADD COLUMN `last_password_change` datetime DEFAULT NULL COMMENT ''Date du dernier changement de mot de passe''',
    'SELECT ''Column last_password_change already exists'' AS msg');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ajouter must_change_password si n'existe pas
SET @column_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'must_change_password');
SET @sql = IF(@column_exists = 0,
    'ALTER TABLE `users` ADD COLUMN `must_change_password` tinyint(1) NOT NULL DEFAULT 0 COMMENT ''Forcer le changement de mot de passe à la prochaine connexion''',
    'SELECT ''Column must_change_password already exists'' AS msg');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ajouter index sur locked_until pour recherche rapide des comptes verrouillés
SET @index_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND INDEX_NAME = 'idx_locked_until');
SET @sql = IF(@index_exists = 0,
    'CREATE INDEX `idx_locked_until` ON `users` (`locked_until`)',
    'SELECT ''Index idx_locked_until already exists'' AS msg');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ajouter index sur password_expires_at
SET @index_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND INDEX_NAME = 'idx_password_expires_at');
SET @sql = IF(@index_exists = 0,
    'CREATE INDEX `idx_password_expires_at` ON `users` (`password_expires_at`)',
    'SELECT ''Index idx_password_expires_at already exists'' AS msg');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Mettre à jour les utilisateurs existants
-- Définir la date de dernier changement de mot de passe à la date de création
UPDATE `users` 
SET `last_password_change` = `created_at`,
    `password_expires_at` = DATE_ADD(`created_at`, INTERVAL 90 DAY)
WHERE `last_password_change` IS NULL;
