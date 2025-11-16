-- Migration: Nettoyage des champs obsolètes
-- Date: 2025-11-16
-- Version: v2.2.0
-- Description: Suppression des champs de récurrence obsolètes dans transactions (maintenant dans recurrences)
--              et suppression de la table beneficiaires (doublon avec tiers)

-- ============================================================================
-- PARTIE 1: Nettoyage de la table transactions
-- ============================================================================

-- Supprimer les champs de récurrence (maintenant uniquement dans la table recurrences)
-- Note: Les index seront automatiquement supprimés avec les colonnes
ALTER TABLE transactions DROP COLUMN est_recurrente;
ALTER TABLE transactions DROP COLUMN frequence;
ALTER TABLE transactions DROP COLUMN intervalle;
ALTER TABLE transactions DROP COLUMN jour_execution;
ALTER TABLE transactions DROP COLUMN jour_semaine;
ALTER TABLE transactions DROP COLUMN date_debut;
ALTER TABLE transactions DROP COLUMN date_fin;
ALTER TABLE transactions DROP COLUMN prochaine_execution;
ALTER TABLE transactions DROP COLUMN derniere_execution;
ALTER TABLE transactions DROP COLUMN nb_executions;
ALTER TABLE transactions DROP COLUMN nb_executions_max;
ALTER TABLE transactions DROP COLUMN auto_validation;
ALTER TABLE transactions DROP COLUMN tolerance_weekend;
ALTER TABLE transactions DROP COLUMN recurrence_active;

-- Note: On garde recurrence_id qui sert à lier une transaction générée à sa récurrence parente

-- ============================================================================
-- PARTIE 2: Suppression de la table beneficiaires (doublon avec tiers)
-- ============================================================================

DROP TABLE beneficiaires;

-- ============================================================================
-- RÉSUMÉ DES OPTIMISATIONS
-- ============================================================================

-- Champs supprimés dans transactions: 14
-- Tables supprimées: 1 (beneficiaires)
-- Gain estimé: ~50 Ko + clarification du modèle de données
-- Impact: Aucun (champs obsolètes, table vide)
