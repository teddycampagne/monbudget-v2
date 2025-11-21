-- Migration 009: Création de la table admin_password_requests
-- Stocke les demandes de réinitialisation de mot de passe via admin (système de secours)

CREATE TABLE IF NOT EXISTS `admin_password_requests` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL COMMENT 'ID utilisateur demandeur',
    `requester_email` VARCHAR(255) NOT NULL COMMENT 'Email de l\'utilisateur',
    `reason` TEXT NULL COMMENT 'Raison de la demande',
    `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending' COMMENT 'Statut de la demande',
    `admin_id` INT NULL COMMENT 'ID admin qui a traité la demande',
    `admin_notes` TEXT NULL COMMENT 'Notes de l\'admin',
    `processed_at` DATETIME NULL COMMENT 'Date de traitement par l\'admin',
    `new_password_sent_at` DATETIME NULL COMMENT 'Date d\'envoi du nouveau mot de passe',
    `ip_address` VARCHAR(45) NULL COMMENT 'Adresse IP de la demande',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_user_id (`user_id`),
    INDEX idx_status (`status`),
    INDEX idx_admin_id (`admin_id`),
    INDEX idx_created_at (`created_at`),
    
    CONSTRAINT fk_admin_pwd_requests_user
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
        
    CONSTRAINT fk_admin_pwd_requests_admin
        FOREIGN KEY (`admin_id`) REFERENCES `users`(`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE
        
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Demandes de réinitialisation de mot de passe via admin (fallback)';
