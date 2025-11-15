<?php

namespace MonBudget\Models;

use MonBudget\Core\Database;

/**
 * BaseModel - Classe parent pour tous les models
 * 
 * Fournit les méthodes CRUD de base et des helpers pour les requêtes courantes.
 * Tous les models doivent étendre cette classe et définir la propriété $table.
 * 
 * @package MonBudget\Models
 * @abstract
 */
abstract class BaseModel
{
    /** @var string Nom de la table en base de données */
    protected static string $table = '';
    
    /** @var string Nom de la clé primaire (par défaut 'id') */
    protected static string $primaryKey = 'id';
    
    /**
     * Récupère tous les enregistrements de la table
     * 
     * @return array Liste de tous les enregistrements
     */
    public static function all(): array
    {
        $query = "SELECT * FROM " . static::$table;
        return Database::select($query);
    }
    
    /**
     * Récupère un enregistrement par son ID
     * 
     * @param int $id Identifiant de l'enregistrement
     * @return array|null L'enregistrement trouvé ou null
     */
    public static function find(int $id): ?array
    {
        $query = "SELECT * FROM " . static::$table . " WHERE " . static::$primaryKey . " = ? LIMIT 1";
        return Database::selectOne($query, [$id]);
    }
    
    /**
     * Récupère les enregistrements selon des conditions
     * 
     * @param array $conditions Tableau associatif [champ => valeur]
     * @return array Liste des enregistrements correspondants
     * 
     * @example where(['user_id' => 1, 'actif' => 1])
     */
    public static function where(array $conditions): array
    {
        $where = [];
        $params = [];
        
        foreach ($conditions as $field => $value) {
            $where[] = "$field = ?";
            $params[] = $value;
        }
        
        $query = "SELECT * FROM " . static::$table . " WHERE " . implode(' AND ', $where);
        return Database::select($query, $params);
    }
    
    /**
     * Crée un nouvel enregistrement
     * 
     * @param array $data Données à insérer [champ => valeur]
     * @return int ID de l'enregistrement créé
     * 
     * @example create(['nom' => 'Test', 'user_id' => 1])
     */
    public static function create(array $data): int
    {
        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');
        
        $query = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            static::$table,
            implode(', ', $fields),
            implode(', ', $placeholders)
        );
        
        return Database::insert($query, array_values($data));
    }
    
    /**
     * Met à jour un enregistrement existant
     * 
     * @param int $id Identifiant de l'enregistrement à modifier
     * @param array $data Données à mettre à jour [champ => valeur]
     * @return int Nombre de lignes affectées
     * 
     * @example update(5, ['nom' => 'Nouveau nom', 'actif' => 1])
     */
    public static function update(int $id, array $data): int
    {
        $set = [];
        $params = [];
        
        foreach ($data as $field => $value) {
            $set[] = "$field = ?";
            $params[] = $value;
        }
        
        $params[] = $id;
        
        $query = sprintf(
            "UPDATE %s SET %s WHERE %s = ?",
            static::$table,
            implode(', ', $set),
            static::$primaryKey
        );
        
        return Database::update($query, $params);
    }
    
    /**
     * Supprime un enregistrement de la base
     * 
     * @param int $id Identifiant de l'enregistrement à supprimer
     * @return int Nombre de lignes supprimées (1 si succès, 0 sinon)
     */
    public static function delete(int $id): int
    {
        $query = "DELETE FROM " . static::$table . " WHERE " . static::$primaryKey . " = ?";
        return Database::delete($query, [$id]);
    }
    
    /**
     * Supprime des enregistrements selon des conditions
     * 
     * @param array $conditions Conditions [champ => valeur]
     * @return int Nombre de lignes supprimées
     * 
     * @example deleteWhere(['user_id' => 1, 'actif' => 0])
     */
    public static function deleteWhere(array $conditions): int
    {
        if (empty($conditions)) {
            return 0;
        }
        
        $where = [];
        $params = [];
        
        foreach ($conditions as $field => $value) {
            $where[] = "$field = ?";
            $params[] = $value;
        }
        
        $query = "DELETE FROM " . static::$table . " WHERE " . implode(' AND ', $where);
        return Database::delete($query, $params);
    }
    
    /**
     * Compte le nombre d'enregistrements
     * 
     * @param array $conditions Conditions optionnelles [champ => valeur]
     * @return int Nombre d'enregistrements
     * 
     * @example count() // Tous les enregistrements
     * @example count(['user_id' => 1]) // Avec filtre
     */
    public static function count(array $conditions = []): int
    {
        if (empty($conditions)) {
            $query = "SELECT COUNT(*) as count FROM " . static::$table;
            $result = Database::selectOne($query);
        } else {
            $where = [];
            $params = [];
            
            foreach ($conditions as $field => $value) {
                $where[] = "$field = ?";
                $params[] = $value;
            }
            
            $query = "SELECT COUNT(*) as count FROM " . static::$table . " WHERE " . implode(' AND ', $where);
            $result = Database::selectOne($query, $params);
        }
        
        return (int) ($result['count'] ?? 0);
    }
    
    /**
     * Récupère les enregistrements d'un utilisateur
     * 
     * @param int $userId ID de l'utilisateur
     * @param string $orderBy Champ de tri (par défaut 'created_at DESC')
     * @return array Liste des enregistrements
     */
    public static function getByUser(int $userId, string $orderBy = 'created_at DESC'): array
    {
        $query = "SELECT * FROM " . static::$table . " WHERE user_id = ? ORDER BY " . $orderBy;
        return Database::select($query, [$userId]);
    }
    
    /**
     * Vérifie si un enregistrement existe
     * 
     * @param int $id Identifiant à vérifier
     * @return bool True si existe, false sinon
     */
    public static function exists(int $id): bool
    {
        return self::find($id) !== null;
    }
    
    /**
     * Récupère le premier enregistrement selon des conditions
     * 
     * @param array $conditions Conditions [champ => valeur]
     * @return array|null Premier enregistrement ou null
     */
    public static function first(array $conditions): ?array
    {
        $where = [];
        $params = [];
        
        foreach ($conditions as $field => $value) {
            $where[] = "$field = ?";
            $params[] = $value;
        }
        
        $query = "SELECT * FROM " . static::$table . " WHERE " . implode(' AND ', $where) . " LIMIT 1";
        return Database::selectOne($query, $params);
    }
    
    /**
     * Exécute une requête SQL personnalisée
     * 
     * @param string $sql Requête SQL
     * @param array $params Paramètres de la requête
     * @return array Résultats de la requête
     */
    protected static function query(string $sql, array $params = []): array
    {
        return Database::select($sql, $params);
    }
}
