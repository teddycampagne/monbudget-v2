-- Migration 006: Création de la table emails_log
-- Enregistre l'historique des emails envoyés

CREATE TABLE IF NOT EXISTS `emails_log` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL COMMENT 'ID utilisateur si applicable',
    `recipient` VARCHAR(255) NOT NULL COMMENT 'Adresse email destinataire',
    `subject` VARCHAR(500) NOT NULL COMMENT 'Sujet de l\'email',
    `template_name` VARCHAR(100) NULL COMMENT 'Nom du template utilisé',
    `status` ENUM('sent', 'failed', 'pending') NOT NULL DEFAULT 'pending' COMMENT 'Statut d\'envoi',
    `error_message` TEXT NULL COMMENT 'Message d\'erreur si échec',
    `sent_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Date d\'envoi',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_user_id (`user_id`),
    INDEX idx_recipient (`recipient`),
    INDEX idx_status (`status`),
    INDEX idx_sent_at (`sent_at`),
    INDEX idx_template (`template_name`),
    
    CONSTRAINT fk_emails_log_user
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE
        
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Historique des emails envoyés';
