-- Migration: Création tables tags pour étiquetage personnalisé
-- Date: 2025-11-16
-- Version: v2.2.0-dev
-- Description: Permet d'ajouter des tags personnalisés aux transactions (complément aux catégories)

-- Table principale des tags
CREATE TABLE IF NOT EXISTS tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(50) NOT NULL COMMENT 'Nom du tag (ex: "Urgent", "Personnel", "Bureau")',
    color VARCHAR(20) NOT NULL DEFAULT 'secondary' COMMENT 'Couleur Bootstrap (primary, success, danger, etc.)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Index et contraintes
    INDEX idx_user_id (user_id),
    INDEX idx_name (name),
    UNIQUE KEY unique_user_tag (user_id, name),
    
    -- Foreign key
    CONSTRAINT fk_tags_user 
        FOREIGN KEY (user_id) 
        REFERENCES users(id) 
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table pivot many-to-many (transactions <-> tags)
CREATE TABLE IF NOT EXISTS transaction_tags (
    transaction_id INT NOT NULL,
    tag_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Clé primaire composite
    PRIMARY KEY (transaction_id, tag_id),
    
    -- Index pour recherche inverse
    INDEX idx_tag_id (tag_id),
    
    -- Foreign keys avec CASCADE DELETE
    CONSTRAINT fk_transaction_tags_transaction 
        FOREIGN KEY (transaction_id) 
        REFERENCES transactions(id) 
        ON DELETE CASCADE,
    
    CONSTRAINT fk_transaction_tags_tag 
        FOREIGN KEY (tag_id) 
        REFERENCES tags(id) 
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Commentaires
ALTER TABLE tags COMMENT = 'Tags personnalisés pour étiquetage flexible des transactions';
ALTER TABLE transaction_tags COMMENT = 'Table pivot many-to-many entre transactions et tags';

-- Exemple de données (optionnel)
-- INSERT INTO tags (user_id, name, color) VALUES 
-- (1, 'Urgent', 'danger'),
-- (1, 'Personnel', 'info'),
-- (1, 'Professionnel', 'primary'),
-- (1, 'Récurrent', 'warning'),
-- (1, 'À vérifier', 'secondary');
