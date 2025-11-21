-- Migration 008: Création de la table password_resets
-- Stocke les tokens de réinitialisation de mot de passe

CREATE TABLE IF NOT EXISTS `password_resets` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL COMMENT 'ID utilisateur',
    `email` VARCHAR(255) NOT NULL COMMENT 'Email de l\'utilisateur',
    `token` VARCHAR(255) NOT NULL UNIQUE COMMENT 'Token de réinitialisation (64 caractères hashés)',
    `expires_at` DATETIME NOT NULL COMMENT 'Date d\'expiration du token (1 heure)',
    `used_at` DATETIME NULL COMMENT 'Date d\'utilisation du token (NULL si non utilisé)',
    `ip_address` VARCHAR(45) NULL COMMENT 'Adresse IP de la demande',
    `user_agent` VARCHAR(500) NULL COMMENT 'User-Agent du navigateur',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_token (`token`),
    INDEX idx_email (`email`),
    INDEX idx_user_id (`user_id`),
    INDEX idx_expires_at (`expires_at`),
    
    CONSTRAINT fk_password_resets_user
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE
        
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Tokens de réinitialisation de mot de passe';

-- Créer un événement pour nettoyer les tokens expirés (tous les jours à 3h du matin)
CREATE EVENT IF NOT EXISTS `cleanup_expired_password_resets`
ON SCHEDULE EVERY 1 DAY
STARTS (TIMESTAMP(CURRENT_DATE) + INTERVAL 3 HOUR)
DO
  DELETE FROM `password_resets` 
  WHERE `expires_at` < NOW() 
  OR `used_at` IS NOT NULL;
