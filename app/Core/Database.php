<?php

namespace MonBudget\Core;

use PDO;
use PDOException;

/**
 * Gestionnaire de connexion et d'opérations sur la base de données
 * 
 * Classe Singleton fournissant une connexion PDO unique et des méthodes
 * utilitaires pour les opérations CRUD : select, insert, update, delete.
 * Gère automatiquement les requêtes préparées pour la sécurité.
 * 
 * @package MonBudget\Core
 * @author MonBudget
 * @version 1.0.0
 */
class Database
{
    /**
     * Instance PDO unique (pattern Singleton)
     * 
     * @var PDO|null
     */
    private static ?PDO $connection = null;
    
    /**
     * Configuration de la base de données
     * 
     * @var array
     */
    private static array $config = [];
    
    /**
     * Initialiser la configuration de la base de données
     * 
     * Doit être appelée avant toute utilisation de la classe.
     * 
     * @param array $config Configuration (driver, host, port, database, username, password, charset)
     * @return void
     */
    public static function configure(array $config): void
    {
        self::$config = $config;
    }
    
    /**
     * Obtenir la connexion PDO (pattern Singleton)
     * 
     * Crée la connexion à la première utilisation, puis la réutilise.
     * 
     * @return PDO Instance PDO configurée
     * @throws \Exception Si la configuration n'est pas définie
     */
    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            self::connect();
        }
        
        return self::$connection;
    }
    
    /**
     * Établir la connexion à la base de données
     * 
     * Méthode privée appelée automatiquement par getConnection().
     * Configure PDO avec les options de sécurité et d'encodage UTF-8.
     * 
     * @return void
     * @throws \Exception Si la connexion échoue ou si la configuration est manquante
     */
    private static function connect(): void
    {
        $config = self::$config;
        
        if (empty($config)) {
            throw new \Exception("Database configuration not set");
        }
        
        try {
            $dsn = sprintf(
                "%s:host=%s;port=%s;dbname=%s;charset=%s",
                $config['driver'] ?? 'mysql',
                $config['host'] ?? 'localhost',
                $config['port'] ?? '3306',
                $config['database'] ?? '',
                $config['charset'] ?? 'utf8mb4'
            );
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];
            
            self::$connection = new PDO(
                $dsn,
                $config['username'] ?? 'root',
                $config['password'] ?? '',
                $options
            );
            
        } catch (PDOException $e) {
            throw new \Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    /**
     * Exécuter une requête SELECT et retourner toutes les lignes
     * 
     * @param string $query Requête SQL SELECT avec placeholders (?)
     * @param array $params Paramètres pour les placeholders (échappés automatiquement)
     * @return array Tableau de résultats (tableaux associatifs)
     * @throws \PDOException Si l'exécution échoue
     */
    public static function select(string $query, array $params = []): array
    {
        $stmt = self::getConnection()->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Exécuter une requête SELECT et retourner une seule ligne
     * 
     * @param string $query Requête SQL SELECT avec placeholders (?)
     * @param array $params Paramètres pour les placeholders
     * @return array|null Tableau associatif de la première ligne ou null si aucun résultat
     * @throws \PDOException Si l'exécution échoue
     */
    public static function selectOne(string $query, array $params = []): ?array
    {
        $stmt = self::getConnection()->prepare($query);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    /**
     * Exécuter une requête INSERT
     * 
     * @param string $query Requête SQL INSERT avec placeholders (?)
     * @param array $params Paramètres pour les placeholders
     * @return int ID de la dernière ligne insérée (auto-increment)
     * @throws \PDOException Si l'exécution échoue
     */
    public static function insert(string $query, array $params = []): int
    {
        $stmt = self::getConnection()->prepare($query);
        $stmt->execute($params);
        return (int) self::getConnection()->lastInsertId();
    }
    
    /**
     * Exécuter une requête UPDATE
     * 
     * @param string $query Requête SQL UPDATE avec placeholders (?)
     * @param array $params Paramètres pour les placeholders
     * @return int Nombre de lignes affectées
     * @throws \PDOException Si l'exécution échoue
     */
    public static function update(string $query, array $params = []): int
    {
        $stmt = self::getConnection()->prepare($query);
        $stmt->execute($params);
        return $stmt->rowCount();
    }
    
    /**
     * Exécuter une requête DELETE
     * 
     * @param string $query Requête SQL DELETE avec placeholders (?)
     * @param array $params Paramètres pour les placeholders
     * @return int Nombre de lignes supprimées
     * @throws \PDOException Si l'exécution échoue
     */
    public static function delete(string $query, array $params = []): int
    {
        return self::update($query, $params);
    }
    
    /**
     * Exécuter une requête SQL quelconque
     * 
     * Pour les requêtes qui ne retournent pas de résultats (CREATE, DROP, etc.)
     * 
     * @param string $query Requête SQL avec placeholders (?)
     * @param array $params Paramètres pour les placeholders
     * @return bool true si succès, false sinon
     * @throws \PDOException Si l'exécution échoue
     */
    public static function execute(string $query, array $params = []): bool
    {
        $stmt = self::getConnection()->prepare($query);
        return $stmt->execute($params);
    }
    
    /**
     * Obtenir l'ID de la dernière insertion
     * 
     * @return int ID de la dernière ligne insérée
     */
    public static function lastInsertId(): int
    {
        return (int) self::getConnection()->lastInsertId();
    }
    
    /**
     * Démarrer une transaction SQL
     * 
     * Permet de grouper plusieurs requêtes dans une transaction atomique.
     * Doit être suivie de commit() ou rollback().
     * 
     * @return bool true si succès
     */
    public static function beginTransaction(): bool
    {
        return self::getConnection()->beginTransaction();
    }
    
    /**
     * Valider une transaction SQL
     * 
     * Confirme toutes les modifications effectuées depuis beginTransaction().
     * 
     * @return bool true si succès
     */
    public static function commit(): bool
    {
        return self::getConnection()->commit();
    }
    
    /**
     * Annuler une transaction SQL (rollback)
     * 
     * Annule toutes les modifications effectuées depuis beginTransaction().
     * Utilisé en cas d'erreur pour maintenir la cohérence des données.
     * 
     * @return bool true si succès
     */
    public static function rollback(): bool
    {
        return self::getConnection()->rollBack();
    }
    
    /**
     * Tester la connexion à la base de données
     * 
     * Vérifie si les paramètres de connexion sont valides sans établir
     * de connexion permanente. Utilisé lors de l'installation.
     * 
     * @param array $config Configuration de connexion à tester
     * @return bool true si la connexion réussit, false sinon
     */
    public static function testConnection(array $config): bool
    {
        try {
            $dsn = sprintf(
                "%s:host=%s;port=%s;charset=%s",
                $config['driver'] ?? 'mysql',
                $config['host'] ?? 'localhost',
                $config['port'] ?? '3306',
                $config['charset'] ?? 'utf8mb4'
            );
            
            $pdo = new PDO(
                $dsn,
                $config['username'] ?? 'root',
                $config['password'] ?? '',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Vérifier si une base de données existe
     */
    public static function databaseExists(array $config, string $database): bool
    {
        try {
            $dsn = sprintf(
                "%s:host=%s;port=%s",
                $config['driver'] ?? 'mysql',
                $config['host'] ?? 'localhost',
                $config['port'] ?? '3306'
            );
            
            $pdo = new PDO(
                $dsn,
                $config['username'] ?? 'root',
                $config['password'] ?? '',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            
            $stmt = $pdo->prepare("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?");
            $stmt->execute([$database]);
            
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Créer une base de données
     */
    public static function createDatabase(array $config, string $database): bool
    {
        try {
            $dsn = sprintf(
                "%s:host=%s;port=%s",
                $config['driver'] ?? 'mysql',
                $config['host'] ?? 'localhost',
                $config['port'] ?? '3306'
            );
            
            $pdo = new PDO(
                $dsn,
                $config['username'] ?? 'root',
                $config['password'] ?? '',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            
            return true;
        } catch (PDOException $e) {
            throw new \Exception("Failed to create database: " . $e->getMessage());
        }
    }
}
