-- Migration: Création table attachments pour pièces jointes transactions
-- Date: 2025-11-16
-- Version: v2.1.0-dev
-- Description: Permet d'attacher des fichiers (factures, reçus, justificatifs) aux transactions

CREATE TABLE IF NOT EXISTS attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL COMMENT 'Nom unique du fichier stocké (hash)',
    original_name VARCHAR(255) NOT NULL COMMENT 'Nom original du fichier uploadé',
    path VARCHAR(500) NOT NULL COMMENT 'Chemin relatif depuis uploads/',
    mimetype VARCHAR(100) NOT NULL COMMENT 'Type MIME du fichier',
    size INT NOT NULL COMMENT 'Taille en octets',
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Index et contraintes
    INDEX idx_transaction_id (transaction_id),
    INDEX idx_uploaded_at (uploaded_at),
    
    -- Foreign key
    CONSTRAINT fk_attachments_transaction 
        FOREIGN KEY (transaction_id) 
        REFERENCES transactions(id) 
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Commentaires table
ALTER TABLE attachments COMMENT = 'Pièces jointes attachées aux transactions (factures, reçus, justificatifs)';
