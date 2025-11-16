<?php

namespace MonBudget\Models;

use MonBudget\Core\Database;
use PDO;

/**
 * Modèle Tag - Étiquettes personnalisées pour transactions
 * 
 * @package MonBudget\Models
 * @version 2.2.0
 */
class Tag
{
    private PDO $db;

    /**
     * Couleurs prédéfinies pour les tags avec labels en français
     */
    public const COLORS = [
        'primary' => ['label' => 'Bleu', 'hex' => '#0d6efd'],
        'secondary' => ['label' => 'Gris', 'hex' => '#6c757d'],
        'success' => ['label' => 'Vert', 'hex' => '#198754'],
        'danger' => ['label' => 'Rouge', 'hex' => '#dc3545'],
        'warning' => ['label' => 'Jaune', 'hex' => '#ffc107'],
        'info' => ['label' => 'Cyan', 'hex' => '#0dcaf0'],
        'dark' => ['label' => 'Noir', 'hex' => '#212529']
    ];

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Récupérer tous les tags d'un utilisateur
     * 
     * @param int $userId ID de l'utilisateur
     * @param string $orderBy Tri (name, created_at, usage_count)
     * @return array
     */
    public function getAllByUser(int $userId, string $orderBy = 'name'): array
    {
        $orderColumn = match($orderBy) {
            'created_at' => 't.created_at DESC',
            'usage_count' => 'usage_count DESC, t.name ASC',
            default => 't.name ASC'
        };

        $sql = "SELECT t.*, 
                       COUNT(DISTINCT tt.transaction_id) as usage_count
                FROM tags t
                LEFT JOIN transaction_tags tt ON t.id = tt.tag_id
                WHERE t.user_id = ?
                GROUP BY t.id
                ORDER BY {$orderColumn}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupérer un tag par ID
     * 
     * @param int $id
     * @param int $userId Pour vérifier ownership
     * @return array|null
     */
    public function findById(int $id, int $userId): ?array
    {
        $sql = "SELECT t.*, 
                       COUNT(DISTINCT tt.transaction_id) as usage_count
                FROM tags t
                LEFT JOIN transaction_tags tt ON t.id = tt.tag_id
                WHERE t.id = ? AND t.user_id = ?
                GROUP BY t.id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id, $userId]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Créer un nouveau tag
     * 
     * @param int $userId
     * @param string $name
     * @param string $color Couleur Bootstrap (primary, success, etc.)
     * @return int|false ID du tag créé ou false
     */
    public function create(int $userId, string $name, string $color = 'secondary'): int|false
    {
        // Validation
        $errors = $this->validate($name, $color);
        if (!empty($errors)) {
            return false;
        }

        // Vérifier unicité user/name
        if ($this->existsByName($userId, $name)) {
            return false;
        }

        $sql = "INSERT INTO tags (user_id, name, color, created_at) 
                VALUES (?, ?, ?, NOW())";

        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute([$userId, trim($name), $color])) {
            return (int) $this->db->lastInsertId();
        }

        return false;
    }

    /**
     * Mettre à jour un tag
     * 
     * @param int $id
     * @param int $userId
     * @param string $name
     * @param string $color
     * @return bool
     */
    public function update(int $id, int $userId, string $name, string $color): bool
    {
        // Validation
        $errors = $this->validate($name, $color);
        if (!empty($errors)) {
            return false;
        }

        // Vérifier unicité (sauf pour le tag actuel)
        if ($this->existsByName($userId, $name, $id)) {
            return false;
        }

        $sql = "UPDATE tags 
                SET name = ?, color = ?, updated_at = NOW()
                WHERE id = ? AND user_id = ?";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([trim($name), $color, $id, $userId]);
    }

    /**
     * Supprimer un tag
     * CASCADE DELETE supprimera automatiquement les entrées dans transaction_tags
     * 
     * @param int $id
     * @param int $userId
     * @return bool
     */
    public function delete(int $id, int $userId): bool
    {
        $sql = "DELETE FROM tags WHERE id = ? AND user_id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id, $userId]);
    }

    /**
     * Rechercher des tags par nom (autocomplete)
     * 
     * @param int $userId
     * @param string $search Terme de recherche
     * @param int $limit Nombre de résultats max
     * @return array
     */
    public function search(int $userId, string $search, int $limit = 10): array
    {
        $sql = "SELECT t.*, 
                       COUNT(DISTINCT tt.transaction_id) as usage_count
                FROM tags t
                LEFT JOIN transaction_tags tt ON t.id = tt.tag_id
                WHERE t.user_id = ? AND t.name LIKE ?
                GROUP BY t.id
                ORDER BY usage_count DESC, t.name ASC
                LIMIT ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, '%' . $search . '%', $limit]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Vérifier si un tag existe par nom
     * 
     * @param int $userId
     * @param string $name
     * @param int|null $excludeId Pour exclure un tag lors de l'édition
     * @return bool
     */
    public function existsByName(int $userId, string $name, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM tags 
                WHERE user_id = ? AND name = ?" . 
                ($excludeId ? " AND id != ?" : "");

        $stmt = $this->db->prepare($sql);
        
        $params = [$userId, trim($name)];
        if ($excludeId) {
            $params[] = $excludeId;
        }
        
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Récupérer les tags d'une transaction
     * 
     * @param int $transactionId
     * @return array
     */
    public function getByTransaction(int $transactionId): array
    {
        $sql = "SELECT t.* 
                FROM tags t
                INNER JOIN transaction_tags tt ON t.id = tt.tag_id
                WHERE tt.transaction_id = ?
                ORDER BY t.name ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$transactionId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupérer les tags les plus utilisés (pour cloud de tags)
     * 
     * @param int $userId
     * @param int $limit
     * @return array
     */
    public function getTopTags(int $userId, int $limit = 20): array
    {
        $sql = "SELECT t.*, 
                       COUNT(DISTINCT tt.transaction_id) as usage_count
                FROM tags t
                INNER JOIN transaction_tags tt ON t.id = tt.tag_id
                WHERE t.user_id = ?
                GROUP BY t.id
                HAVING usage_count > 0
                ORDER BY usage_count DESC, t.name ASC
                LIMIT ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $limit]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Valider les données d'un tag
     * 
     * @param string $name
     * @param string $color
     * @return array Tableau d'erreurs
     */
    public function validate(string $name, string $color): array
    {
        $errors = [];

        // Validation nom
        $name = trim($name);
        if (empty($name)) {
            $errors['name'] = 'Le nom du tag est obligatoire';
        } elseif (strlen($name) > 50) {
            $errors['name'] = 'Le nom ne peut pas dépasser 50 caractères';
        } elseif (!preg_match('/^[\p{L}\p{N}\s\-_]+$/u', $name)) {
            $errors['name'] = 'Le nom contient des caractères non autorisés';
        }

        // Validation couleur
        if (!isset(self::COLORS[$color])) {
            $errors['color'] = 'Couleur invalide';
        }

        return $errors;
    }

    /**
     * Obtenir le code hexadécimal d'une couleur
     * 
     * @param string $colorName
     * @return string
     */
    public static function getColorHex(string $colorName): string
    {
        return self::COLORS[$colorName]['hex'] ?? self::COLORS['secondary']['hex'];
    }

    /**
     * Obtenir le label français d'une couleur
     * 
     * @param string $colorName
     * @return string
     */
    public static function getColorLabel(string $colorName): string
    {
        return self::COLORS[$colorName]['label'] ?? 'Gris';
    }

    /**
     * Formater un tag pour affichage badge HTML
     * 
     * @param array $tag
     * @param bool $removable Afficher bouton de suppression
     * @return string
     */
    public static function renderBadge(array $tag, bool $removable = false): string
    {
        $color = htmlspecialchars($tag['color']);
        $name = htmlspecialchars($tag['name']);
        $id = (int) $tag['id'];
        
        $removeBtn = $removable 
            ? '<i class="bi bi-x-circle ms-1" style="cursor:pointer;" data-tag-remove="' . $id . '"></i>' 
            : '';

        return sprintf(
            '<span class="badge bg-%s me-1 mb-1" data-tag-id="%d">%s%s</span>',
            $color,
            $id,
            $name,
            $removeBtn
        );
    }

    /**
     * Obtenir les statistiques d'utilisation des tags
     * 
     * @param int $userId
     * @return array
     */
    public function getStats(int $userId): array
    {
        $sql = "SELECT 
                    COUNT(DISTINCT t.id) as total_tags,
                    COUNT(DISTINCT tt.transaction_id) as tagged_transactions,
                    AVG(tag_counts.tag_count) as avg_tags_per_transaction
                FROM tags t
                LEFT JOIN transaction_tags tt ON t.id = tt.tag_id
                LEFT JOIN (
                    SELECT transaction_id, COUNT(*) as tag_count
                    FROM transaction_tags
                    GROUP BY transaction_id
                ) tag_counts ON tt.transaction_id = tag_counts.transaction_id
                WHERE t.user_id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [
            'total_tags' => 0,
            'tagged_transactions' => 0,
            'avg_tags_per_transaction' => 0
        ];
    }
}
