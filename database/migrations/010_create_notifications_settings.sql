-- Migration: Create notifications_settings table
-- Phase 3: Budget Alerts - Configuration table

CREATE TABLE notifications_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,

    -- Alertes budgets
    budget_alert_enabled TINYINT(1) DEFAULT 1,
    budget_threshold_80 TINYINT(1) DEFAULT 1 COMMENT 'Alerte à 80% du budget',
    budget_threshold_90 TINYINT(1) DEFAULT 1 COMMENT 'Alerte à 90% du budget',
    budget_exceeded TINYINT(1) DEFAULT 1 COMMENT 'Alerte dépassement',

    -- Récapitulatifs
    weekly_summary TINYINT(1) DEFAULT 0,
    monthly_summary TINYINT(1) DEFAULT 1,

    -- Méthodes notification
    notify_email TINYINT(1) DEFAULT 1,
    notify_web TINYINT(1) DEFAULT 1 COMMENT 'Notifications dans app',

    -- Fréquence
    max_emails_per_day INT DEFAULT 5 COMMENT 'Limite anti-spam',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Index pour optimiser les requêtes
CREATE INDEX idx_user_id ON notifications_settings(user_id);
CREATE INDEX idx_budget_alert_enabled ON notifications_settings(budget_alert_enabled);