-- Migration: Créer la table admin_tickets
-- Date: 2025-11-22
-- Description: Table pour le système de tickets d'administration

CREATE TABLE IF NOT EXISTS `admin_tickets` (
    `id` int NOT NULL AUTO_INCREMENT,
    `user_id` int NOT NULL COMMENT 'Utilisateur qui a créé le ticket',
    `admin_id` int DEFAULT NULL COMMENT 'Administrateur assigné (NULL = non assigné)',
    `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Sujet du ticket',
    `message` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Message détaillé',
    `status` enum('open','in_progress','waiting_user','resolved','closed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open' COMMENT 'Statut du ticket',
    `priority` enum('low','normal','high','urgent') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal' COMMENT 'Priorité du ticket',
    `category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Catégorie du ticket',
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `resolved_at` timestamp NULL DEFAULT NULL COMMENT 'Date de résolution',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_admin_id` (`admin_id`),
    KEY `idx_status` (`status`),
    KEY `idx_priority` (`priority`),
    KEY `idx_created_at` (`created_at`),
    CONSTRAINT `fk_admin_tickets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_admin_tickets_admin` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Système de tickets d\'administration';

-- Table pour les réponses aux tickets
CREATE TABLE IF NOT EXISTS `admin_ticket_replies` (
    `id` int NOT NULL AUTO_INCREMENT,
    `ticket_id` int NOT NULL,
    `user_id` int NOT NULL COMMENT 'Utilisateur qui a répondu',
    `message` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Contenu de la réponse',
    `is_internal` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Réponse interne (visible seulement par les admins)',
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_ticket_id` (`ticket_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_created_at` (`created_at`),
    CONSTRAINT `fk_ticket_replies_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `admin_tickets` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ticket_replies_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Réponses aux tickets d\'administration';