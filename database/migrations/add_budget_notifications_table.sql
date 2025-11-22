-- Migration: Add budget notifications table
-- Date: 2025-11-22
-- Description: Table pour stocker les notifications de dépassement de budget

CREATE TABLE IF NOT EXISTS `budget_notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `budget_id` int NOT NULL,
  `type` enum('warning','alert','critical') NOT NULL DEFAULT 'warning',
  `message` text NOT NULL,
  `pourcentage_depasse` decimal(5,2) NOT NULL,
  `montant_depasse` decimal(15,2) NOT NULL,
  `notification_envoyee` tinyint(1) NOT NULL DEFAULT 0,
  `email_envoye` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `sent_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_budget_id` (`budget_id`),
  KEY `idx_type` (`type`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_notification_envoyee` (`notification_envoyee`),
  KEY `idx_email_envoye` (`email_envoye`),
  CONSTRAINT `fk_budget_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_budget_notifications_budget` FOREIGN KEY (`budget_id`) REFERENCES `budgets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Notifications de dépassement de budget';