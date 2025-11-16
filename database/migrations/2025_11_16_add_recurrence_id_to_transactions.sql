-- Migration: Ajout colonne recurrence_id pour lier transactions générées aux récurrences
-- Date: 2025-11-16
-- Version: v2.2.0
-- Description: Permet de tracer quelle récurrence a généré quelle transaction (anti-doublon)

-- Ajouter la colonne recurrence_id (nullable car transactions existantes n'ont pas de récurrence)
ALTER TABLE transactions 
ADD COLUMN recurrence_id INT NULL 
COMMENT 'ID de la récurrence parente (NULL si transaction manuelle)' 
AFTER est_recurrente;

-- Ajouter un index pour recherche rapide des transactions générées par une récurrence
ALTER TABLE transactions 
ADD INDEX idx_recurrence_id (recurrence_id);

-- Ajouter une contrainte de clé étrangère (optionnel mais recommandé)
-- Note: La FK pointe vers la table recurrences (table séparée pour les modèles de récurrence)
ALTER TABLE transactions
ADD CONSTRAINT fk_transaction_recurrence
    FOREIGN KEY (recurrence_id) 
    REFERENCES recurrences(id) 
    ON DELETE SET NULL; -- Si la récurrence est supprimée, les transactions générées restent mais perdent le lien

-- Commentaire
ALTER TABLE transactions COMMENT = 'Transactions bancaires et récurrences avec traçabilité anti-doublon';
