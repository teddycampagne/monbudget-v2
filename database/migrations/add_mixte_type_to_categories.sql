-- Migration: Ajouter le type 'mixte' aux catégories
-- Date: 2025-11-13
-- Description: Permet de créer des catégories utilisables en débit ET en crédit (ex: Mutuelle)

ALTER TABLE `categories` 
MODIFY COLUMN `type` ENUM('depense','revenu','mixte') COLLATE utf8mb4_unicode_ci DEFAULT 'depense';

-- Exemples de catégories mixtes typiques :
-- - Mutuelle (cotisation = dépense, remboursement = revenu)
-- - Impôts (paiement = dépense, remboursement = revenu)
-- - Assurance (prime = dépense, indemnité = revenu)
