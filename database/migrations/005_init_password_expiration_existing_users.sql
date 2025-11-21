-- Migration: Initialiser les dates d'expiration pour les utilisateurs existants
-- Date: 2025-11-21
-- Description: Définir password_expires_at et last_password_change pour les utilisateurs créés sans ces champs

UPDATE `users` 
SET 
    `password_expires_at` = DATE_ADD(NOW(), INTERVAL 90 DAY),
    `last_password_change` = NOW()
WHERE 
    `password_expires_at` IS NULL 
    AND `last_password_change` IS NULL;
