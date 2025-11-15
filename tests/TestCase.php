<?php

namespace MonBudget\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use MonBudget\Core\Database;

/**
 * Classe TestCase de base pour tous les tests
 * 
 * Fournit des méthodes utilitaires communes pour les tests
 */
abstract class TestCase extends BaseTestCase
{
    /**
     * Configuration avant chaque test
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Charger les variables d'environnement de test si besoin
        if (file_exists(__DIR__ . '/../.env.testing')) {
            $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/..', '.env.testing');
            $dotenv->load();
        }
        
        // Configurer la connexion à la base de données de test
        Database::configure([
            'driver' => $_ENV['DB_CONNECTION'] ?? 'mysql',
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'port' => $_ENV['DB_PORT'] ?? '3306',
            'database' => $_ENV['DB_DATABASE'] ?? 'monbudget_test',
            'username' => $_ENV['DB_USERNAME'] ?? 'root',
            'password' => $_ENV['DB_PASSWORD'] ?? '',
            'charset' => 'utf8mb4'
        ]);
    }

    /**
     * Nettoyage après chaque test
     */
    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * Helper pour créer un utilisateur de test
     * 
     * @param array $data Données de l'utilisateur
     * @return int ID de l'utilisateur créé
     */
    protected function createTestUser(array $data = []): int
    {
        // Utiliser microtime pour garantir l'unicité même avec tests parallèles
        $unique = str_replace('.', '', microtime(true));
        
        $defaults = [
            'username' => 'testuser_' . $unique,
            'email' => 'test_' . $unique . '@example.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'role' => 'user',
            'is_active' => 1
        ];

        $userData = array_merge($defaults, $data);
        
        $sql = "INSERT INTO users (username, email, password, role, is_active) 
                VALUES (?, ?, ?, ?, ?)";
        
        Database::execute($sql, [
            $userData['username'],
            $userData['email'],
            $userData['password'],
            $userData['role'],
            $userData['is_active']
        ]);

        return (int) Database::lastInsertId();
    }

    /**
     * Helper pour supprimer un utilisateur de test
     * 
     * @param int $userId ID de l'utilisateur
     */
    protected function deleteTestUser(int $userId): void
    {
        Database::execute("DELETE FROM users WHERE id = ?", [$userId]);
    }

    /**
     * Assert qu'un tableau contient les clés attendues
     * 
     * @param array $expectedKeys Clés attendues
     * @param array $array Tableau à vérifier
     */
    protected function assertArrayHasKeys(array $expectedKeys, array $array): void
    {
        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $array, "La clé '{$key}' est manquante");
        }
    }
}
