-- Migration: 013_add_budget_exceeded_notification_method
-- Created: 2025-11-22
-- Description: Add specific notification method configuration for budget exceeded alerts

ALTER TABLE notifications_settings
ADD COLUMN budget_exceeded_method ENUM('none', 'web_only', 'email_only', 'both') DEFAULT 'both'
COMMENT 'Méthode de notification pour les alertes de dépassement de budget';

-- Mettre à jour les enregistrements existants pour utiliser la valeur par défaut
UPDATE notifications_settings SET budget_exceeded_method = 'both' WHERE budget_exceeded_method IS NULL;