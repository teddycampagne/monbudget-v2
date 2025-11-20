-- Migration: Supprimer la colonne chemin_fichier de la table imports
-- Raison: Les fichiers CSV/OFX sont maintenant supprimés automatiquement après import (sécurité PCI DSS)
--         Cette colonne pointe vers des fichiers qui n'existent plus et stocke des chemins inutiles
-- Date: 2024-11-20
-- Version: v2.3.0

-- Vérifier que la colonne existe avant de la supprimer
SELECT COUNT(*) 
INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'imports' 
  AND COLUMN_NAME = 'chemin_fichier';

-- Supprimer la colonne si elle existe
SET @sql = IF(@col_exists > 0, 
    'ALTER TABLE imports DROP COLUMN chemin_fichier',
    'SELECT "La colonne chemin_fichier n\'existe pas" AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Résultat attendu: colonne supprimée (fichiers auto-supprimés, chemins obsolètes)
