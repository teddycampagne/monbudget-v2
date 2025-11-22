-- Migration: Créer la table password_resets
-- Date: 2025-11-22
-- Description: Table pour stocker les tokens de réinitialisation de mot de passe

CREATE TABLE IF NOT EXISTS `password_resets` (
    `id` int NOT NULL AUTO_INCREMENT,
    `user_id` int NOT NULL,
    `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Token hashé pour sécurité',
    `expires_at` datetime NOT NULL COMMENT 'Date d\'expiration du token',
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `used_at` timestamp NULL DEFAULT NULL COMMENT 'Date d\'utilisation du token',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_token` (`token`(50)),
    KEY `idx_expires_at` (`expires_at`),
    CONSTRAINT `fk_password_resets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tokens de réinitialisation de mot de passe';