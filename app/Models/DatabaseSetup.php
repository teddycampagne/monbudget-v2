<?php

namespace MonBudget\Models;

use MonBudget\Core\Database;
use PDO;
use PDOException;

/**
 * DatabaseSetup - Gestion de la configuration et installation BDD
 */
class DatabaseSetup
{
    /**
     * Tester la connexion à la base de données
     */
    public static function testConnection(array $config): array
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
            
            return [
                'success' => true,
                'message' => 'Connexion réussie au serveur MySQL'
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Échec de la connexion',
                'error' => $e->getMessage()
            ];
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
    public static function createDatabase(array $config, string $database): array
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
            
            return [
                'success' => true,
                'message' => "Base de données '$database' créée avec succès"
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Échec de la création de la base de données',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Importer un fichier SQL
     */
    public static function importSQLFile(array $config, string $sqlFile): array
    {
        if (!file_exists($sqlFile)) {
            return [
                'success' => false,
                'message' => 'Fichier SQL introuvable',
                'error' => "Le fichier $sqlFile n'existe pas"
            ];
        }
        
        try {
            $dsn = sprintf(
                "%s:host=%s;port=%s;dbname=%s;charset=%s",
                $config['driver'] ?? 'mysql',
                $config['host'] ?? 'localhost',
                $config['port'] ?? '3306',
                $config['database'] ?? 'monbudget_v2',
                $config['charset'] ?? 'utf8mb4'
            );
            
            $pdo = new PDO(
                $dsn,
                $config['username'] ?? 'root',
                $config['password'] ?? '',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            
            $sql = file_get_contents($sqlFile);
            
            // Séparer les requêtes
            $statements = array_filter(
                array_map('trim', explode(';', $sql)),
                fn($stmt) => !empty($stmt)
            );
            
            $executed = 0;
            foreach ($statements as $statement) {
                if (!empty($statement)) {
                    $pdo->exec($statement);
                    $executed++;
                }
            }
            
            return [
                'success' => true,
                'message' => "Fichier SQL importé avec succès ($executed requêtes exécutées)"
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Échec de l\'importation du fichier SQL',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtenir la liste des tables
     */
    public static function getTables(array $config): array
    {
        try {
            Database::configure($config);
            
            $tables = Database::select("SHOW TABLES");
            
            return [
                'success' => true,
                'tables' => array_map('current', $tables)
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Impossible de récupérer la liste des tables',
                'error' => $e->getMessage()
            ];
        }
    }
}
