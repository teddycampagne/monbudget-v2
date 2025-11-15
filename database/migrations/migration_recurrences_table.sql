-- ============================================================================
-- MIGRATION : Séparation modèles récurrences et transactions
-- Date : 15 novembre 2025
-- Objectif : Créer table recurrences + lier occurrences via recurrence_id
-- Fix Bug 7 : Suppression doublons récurrences identiques
-- ============================================================================

-- ============================================================================
-- ÉTAPE 1 : Création table recurrences
-- ============================================================================

CREATE TABLE IF NOT EXISTS `recurrences` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `compte_id` int NOT NULL,
  `compte_destination_id` int DEFAULT NULL COMMENT 'Pour virements récurrents',
  
  -- Informations de base de la transaction modèle
  `libelle` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `montant` decimal(15,2) NOT NULL,
  `type_operation` enum('debit','credit','virement') COLLATE utf8mb4_unicode_ci NOT NULL,
  `moyen_paiement` enum('virement','virement_interne','prelevement','carte','cheque','especes','autre') COLLATE utf8mb4_unicode_ci DEFAULT 'autre',
  `beneficiaire` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  
  -- Catégorisation
  `categorie_id` int DEFAULT NULL,
  `sous_categorie_id` int DEFAULT NULL,
  `tiers_id` int DEFAULT NULL,
  
  -- Paramètres de récurrence
  `frequence` enum('quotidien','hebdomadaire','mensuel','trimestriel','semestriel','annuel') COLLATE utf8mb4_unicode_ci NOT NULL,
  `intervalle` int DEFAULT '1' COMMENT 'Ex: 2 pour "tous les 2 mois"',
  `jour_execution` int DEFAULT NULL COMMENT 'Jour du mois (1-31)',
  `jour_semaine` int DEFAULT NULL COMMENT 'Jour semaine (1=Lundi, 7=Dimanche)',
  
  -- Dates et limites
  `date_debut` date NOT NULL,
  `date_fin` date DEFAULT NULL COMMENT 'Optionnel: date de fin',
  `prochaine_execution` date NOT NULL,
  `derniere_execution` date DEFAULT NULL,
  
  -- Compteurs et limites
  `nb_executions` int DEFAULT '0',
  `nb_executions_max` int DEFAULT NULL COMMENT 'Optionnel: limite exécutions',
  
  -- Options
  `auto_validation` tinyint(1) DEFAULT '1' COMMENT 'Valider auto les occurrences',
  `tolerance_weekend` enum('aucune','jour_ouvre_suivant','jour_ouvre_precedent') COLLATE utf8mb4_unicode_ci DEFAULT 'jour_ouvre_suivant',
  `recurrence_active` tinyint(1) DEFAULT '1',
  
  -- Audit
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_compte_id` (`compte_id`),
  KEY `idx_prochaine_execution` (`prochaine_execution`),
  KEY `idx_recurrence_active` (`recurrence_active`),
  KEY `categorie_id` (`categorie_id`),
  KEY `tiers_id` (`tiers_id`),
  KEY `compte_destination_id` (`compte_destination_id`),
  
  CONSTRAINT `recurrences_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `recurrences_ibfk_2` FOREIGN KEY (`compte_id`) REFERENCES `comptes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `recurrences_ibfk_3` FOREIGN KEY (`compte_destination_id`) REFERENCES `comptes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `recurrences_ibfk_4` FOREIGN KEY (`categorie_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `recurrences_ibfk_5` FOREIGN KEY (`tiers_id`) REFERENCES `tiers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- ÉTAPE 2 : Ajouter recurrence_id à transactions
-- ============================================================================

ALTER TABLE `transactions` 
ADD COLUMN `recurrence_id` int DEFAULT NULL COMMENT 'FK vers recurrences si généré depuis récurrence'
AFTER `est_recurrente`;

ALTER TABLE `transactions`
ADD KEY `idx_recurrence_id` (`recurrence_id`);

ALTER TABLE `transactions`
ADD CONSTRAINT `transactions_ibfk_5` FOREIGN KEY (`recurrence_id`) 
REFERENCES `recurrences` (`id`) ON DELETE SET NULL;


-- ============================================================================
-- ÉTAPE 3 : Migration des données existantes
-- ============================================================================

-- 3.1 - Copier les modèles (est_recurrente = 1) vers table recurrences
INSERT INTO `recurrences` (
    user_id, compte_id, compte_destination_id,
    libelle, description, montant, type_operation, moyen_paiement, beneficiaire,
    categorie_id, sous_categorie_id, tiers_id,
    frequence, intervalle, jour_execution, jour_semaine,
    date_debut, date_fin, prochaine_execution, derniere_execution,
    nb_executions, nb_executions_max,
    auto_validation, tolerance_weekend, recurrence_active,
    created_at, updated_at
)
SELECT 
    user_id, compte_id, compte_destination_id,
    libelle, description, montant, type_operation, moyen_paiement, beneficiaire,
    categorie_id, sous_categorie_id, tiers_id,
    frequence, intervalle, jour_execution, jour_semaine,
    date_debut, date_fin, prochaine_execution, derniere_execution,
    nb_executions, nb_executions_max,
    auto_validation, tolerance_weekend, recurrence_active,
    created_at, updated_at
FROM `transactions`
WHERE est_recurrente = 1;

-- 3.2 - Lier les occurrences aux modèles par heuristique
-- Pour chaque récurrence créée, on identifie ses occurrences par :
-- - Même user_id, compte_id, montant, libellé
-- - est_recurrente = 0
-- - date_transaction >= date_debut du modèle

UPDATE `transactions` t
INNER JOIN `recurrences` r ON (
    t.user_id = r.user_id
    AND t.compte_id = r.compte_id
    AND t.est_recurrente = 0
    AND ABS(t.montant - r.montant) < 0.01
    AND t.libelle = r.libelle
    AND t.date_transaction >= r.date_debut
    AND (r.date_fin IS NULL OR t.date_transaction <= r.date_fin)
)
SET t.recurrence_id = r.id
WHERE t.est_recurrente = 0;

-- 3.3 - Supprimer les modèles de la table transactions
DELETE FROM `transactions` WHERE est_recurrente = 1;


-- ============================================================================
-- ÉTAPE 4 : Nettoyage - Suppression colonnes récurrence de transactions
-- ============================================================================
-- NOTE: On garde est_recurrente pour distinguer anciennes données
-- Les nouvelles colonnes recurrence_* ne seront plus utilisées
-- On les supprimera dans une migration ultérieure si tout fonctionne

-- ALTER TABLE `transactions` DROP COLUMN `frequence`;
-- ALTER TABLE `transactions` DROP COLUMN `intervalle`;
-- ALTER TABLE `transactions` DROP COLUMN `jour_execution`;
-- ALTER TABLE `transactions` DROP COLUMN `jour_semaine`;
-- ALTER TABLE `transactions` DROP COLUMN `date_debut`;
-- ALTER TABLE `transactions` DROP COLUMN `date_fin`;
-- ALTER TABLE `transactions` DROP COLUMN `prochaine_execution`;
-- ALTER TABLE `transactions` DROP COLUMN `derniere_execution`;
-- ALTER TABLE `transactions` DROP COLUMN `nb_executions`;
-- ALTER TABLE `transactions` DROP COLUMN `nb_executions_max`;
-- ALTER TABLE `transactions` DROP COLUMN `auto_validation`;
-- ALTER TABLE `transactions` DROP COLUMN `tolerance_weekend`;
-- ALTER TABLE `transactions` DROP COLUMN `recurrence_active`;

-- Pour l'instant on les garde en commentaire pour rollback facile


-- ============================================================================
-- ÉTAPE 5 : Vérifications post-migration
-- ============================================================================

-- Vérifier nombre de récurrences migrées
SELECT COUNT(*) as nb_recurrences_migrees FROM `recurrences`;

-- Vérifier nombre d'occurrences liées
SELECT COUNT(*) as nb_occurrences_liees 
FROM `transactions` 
WHERE recurrence_id IS NOT NULL;

-- Vérifier récurrences sans occurrences (nouvelles récurrences non exécutées)
SELECT r.id, r.libelle, r.nb_executions, 
       (SELECT COUNT(*) FROM transactions WHERE recurrence_id = r.id) as nb_occ_liees
FROM `recurrences` r
WHERE r.nb_executions > 0
HAVING nb_occ_liees = 0;

-- Vérifier que les modèles ont bien été supprimés
SELECT COUNT(*) as nb_modeles_restants FROM `transactions` WHERE est_recurrente = 1;
-- Doit retourner 0


-- ============================================================================
-- ROLLBACK (si nécessaire)
-- ============================================================================

/*
-- Pour annuler la migration :

-- 1. Restaurer les modèles dans transactions
INSERT INTO `transactions` (
    user_id, compte_id, compte_destination_id,
    date_transaction, libelle, description, montant, type_operation, moyen_paiement, beneficiaire,
    categorie_id, sous_categorie_id, tiers_id,
    est_recurrente, frequence, intervalle, jour_execution, jour_semaine,
    date_debut, date_fin, prochaine_execution, derniere_execution,
    nb_executions, nb_executions_max,
    auto_validation, tolerance_weekend, recurrence_active,
    created_at, updated_at
)
SELECT 
    user_id, compte_id, compte_destination_id,
    prochaine_execution, libelle, description, montant, type_operation, moyen_paiement, beneficiaire,
    categorie_id, sous_categorie_id, tiers_id,
    1, frequence, intervalle, jour_execution, jour_semaine,
    date_debut, date_fin, prochaine_execution, derniere_execution,
    nb_executions, nb_executions_max,
    auto_validation, tolerance_weekend, recurrence_active,
    created_at, updated_at
FROM `recurrences`;

-- 2. Supprimer la contrainte FK
ALTER TABLE `transactions` DROP FOREIGN KEY `transactions_ibfk_5`;
ALTER TABLE `transactions` DROP KEY `idx_recurrence_id`;

-- 3. Supprimer la colonne recurrence_id
ALTER TABLE `transactions` DROP COLUMN `recurrence_id`;

-- 4. Vider la table recurrences
TRUNCATE TABLE `recurrences`;

-- 5. Supprimer la table recurrences
DROP TABLE `recurrences`;
*/
