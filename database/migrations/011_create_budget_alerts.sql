-- Migration: Create budget_alerts table
-- Phase 3: Budget Alerts - Alert history table

CREATE TABLE budget_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    budget_id INT NOT NULL,
    user_id INT NOT NULL,
    alert_type ENUM('threshold_80', 'threshold_90', 'exceeded') NOT NULL,
    budget_amount DECIMAL(10,2) NOT NULL,
    spent_amount DECIMAL(10,2) NOT NULL,
    percentage DECIMAL(5,2) NOT NULL,
    email_sent TINYINT(1) DEFAULT 0,
    web_notified TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_budget_id (budget_id),
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at),

    FOREIGN KEY (budget_id) REFERENCES budgets(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,

    UNIQUE KEY unique_alert (budget_id, alert_type, DATE(created_at))
) ENGINE=InnoDB;

-- Index pour optimiser les requêtes
CREATE INDEX idx_alert_type ON budget_alerts(alert_type);
CREATE INDEX idx_email_sent ON budget_alerts(email_sent);
CREATE INDEX idx_web_notified ON budget_alerts(web_notified);