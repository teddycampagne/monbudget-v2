-- Migration: Ajout table password_history pour conformité PCI DSS
-- Date: 2025-11-20
-- Description: Stocke l'historique des 5 derniers mots de passe pour éviter la réutilisation

CREATE TABLE IF NOT EXISTS `password_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `password_hash` varchar(255) NOT NULL COMMENT 'Hash du mot de passe (bcrypt)',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_password_history_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Historique des mots de passe utilisateurs (PCI DSS 8.2.5)';

-- Index pour recherche rapide des N derniers mots de passe d'un utilisateur
CREATE INDEX `idx_user_created` ON `password_history` (`user_id`, `created_at` DESC);
