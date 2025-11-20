-- Migration: Ajout table audit_logs pour conformité PCI DSS
-- Date: 2025-11-20
-- Description: Journalisation complète des événements de sécurité (rétention 1 an minimum)

CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL COMMENT 'ID utilisateur (NULL si non authentifié)',
  `action` varchar(50) NOT NULL COMMENT 'Type d''action (login, update, delete, etc.)',
  `table_name` varchar(64) DEFAULT NULL COMMENT 'Table concernée',
  `record_id` int(11) DEFAULT NULL COMMENT 'ID de l''enregistrement concerné',
  `old_values` text DEFAULT NULL COMMENT 'Valeurs avant modification (JSON)',
  `new_values` text DEFAULT NULL COMMENT 'Valeurs après modification (JSON)',
  `ip_address` varchar(45) NOT NULL COMMENT 'Adresse IP (IPv4 ou IPv6)',
  `user_agent` varchar(255) DEFAULT NULL COMMENT 'User-Agent du navigateur',
  `request_uri` varchar(255) DEFAULT NULL COMMENT 'URI de la requête',
  `request_method` varchar(10) DEFAULT NULL COMMENT 'Méthode HTTP (GET, POST, etc.)',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_table_record` (`table_name`, `record_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_ip_address` (`ip_address`),
  CONSTRAINT `fk_audit_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Journalisation d''audit pour conformité PCI DSS (Exigence 10)';

-- Index composite pour recherches fréquentes
CREATE INDEX `idx_user_action_date` ON `audit_logs` (`user_id`, `action`, `created_at` DESC);

-- Partition par année pour performances (optionnel, à activer manuellement si MySQL >= 5.7)
-- ALTER TABLE `audit_logs` PARTITION BY RANGE (YEAR(created_at)) (
--   PARTITION p2024 VALUES LESS THAN (2025),
--   PARTITION p2025 VALUES LESS THAN (2026),
--   PARTITION p2026 VALUES LESS THAN (2027),
--   PARTITION p_future VALUES LESS THAN MAXVALUE
-- );
