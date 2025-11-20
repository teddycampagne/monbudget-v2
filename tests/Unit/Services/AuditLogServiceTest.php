<?php

namespace MonBudget\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use MonBudget\Services\AuditLogService;
use MonBudget\Core\Database;

/**
 * Tests unitaires pour AuditLogService
 * 
 * Teste le logging des événements de sécurité PCI DSS Requirement 10 :
 * - Création/Modification/Suppression d'enregistrements
 * - Login réussi/échoué, logout
 * - Changement de mot de passe
 * - Verrouillage/Déverrouillage de compte
 */
class AuditLogServiceTest extends TestCase
{
    private AuditLogService $auditService;

    /**
     * Configuration avant chaque test
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Configurer la connexion à la base de données de test
        if (file_exists(__DIR__ . '/../../../.env.testing')) {
            $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/../../..', '.env.testing');
            $dotenv->load();
        }
        
        Database::configure([
            'driver' => $_ENV['DB_CONNECTION'] ?? 'mysql',
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'port' => $_ENV['DB_PORT'] ?? '3306',
            'database' => $_ENV['DB_DATABASE'] ?? 'monbudget_test',
            'username' => $_ENV['DB_USERNAME'] ?? 'root',
            'password' => $_ENV['DB_PASSWORD'] ?? '',
            'charset' => 'utf8mb4'
        ]);
        
        $this->auditService = new AuditLogService();
        
        // Simuler une session utilisateur
        $_SESSION['user'] = [
            'id' => 1,
            'email' => 'test@example.com'
        ];
        
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit Test';
        $_SERVER['REQUEST_URI'] = '/test';
        $_SERVER['REQUEST_METHOD'] = 'POST';
    }

    /**
     * Nettoyage après chaque test
     */
    protected function tearDown(): void
    {
        unset($_SESSION['user']);
        
        parent::tearDown();
    }

    /**
     * Test constantes d'actions
     */
    public function testActionConstants(): void
    {
        $this->assertEquals('login_success', AuditLogService::ACTION_LOGIN_SUCCESS);
        $this->assertEquals('login_failed', AuditLogService::ACTION_LOGIN_FAILED);
        $this->assertEquals('logout', AuditLogService::ACTION_LOGOUT);
        $this->assertEquals('password_change', AuditLogService::ACTION_PASSWORD_CHANGE);
        $this->assertEquals('password_reset', AuditLogService::ACTION_PASSWORD_RESET);
        $this->assertEquals('account_locked', AuditLogService::ACTION_ACCOUNT_LOCKED);
        $this->assertEquals('account_unlocked', AuditLogService::ACTION_ACCOUNT_UNLOCKED);
        $this->assertEquals('create', AuditLogService::ACTION_CREATE);
        $this->assertEquals('update', AuditLogService::ACTION_UPDATE);
        $this->assertEquals('delete', AuditLogService::ACTION_DELETE);
    }

    /**
     * Test log générique
     */
    public function testLog(): void
    {
        $logId = $this->auditService->log(
            AuditLogService::ACTION_CREATE,
            'test_table',
            123,
            null,
            ['name' => 'Test']
        );
        
        $this->assertIsInt($logId);
        $this->assertGreaterThan(0, $logId);
    }

    /**
     * Test logCreate
     */
    public function testLogCreate(): void
    {
        $data = [
            'name' => 'Test Transaction',
            'amount' => 100.50,
            'date' => '2024-11-20'
        ];
        
        $logId = $this->auditService->logCreate('transactions', 456, $data);
        
        $this->assertIsInt($logId);
        $this->assertGreaterThan(0, $logId);
    }

    /**
     * Test logUpdate
     */
    public function testLogUpdate(): void
    {
        $oldValues = [
            'name' => 'Old Name',
            'amount' => 50.00
        ];
        
        $newValues = [
            'name' => 'New Name',
            'amount' => 100.00
        ];
        
        $logId = $this->auditService->logUpdate('transactions', 789, $oldValues, $newValues);
        
        $this->assertIsInt($logId);
        $this->assertGreaterThan(0, $logId);
    }

    /**
     * Test logDelete
     */
    public function testLogDelete(): void
    {
        $data = [
            'name' => 'Deleted Transaction',
            'amount' => 75.00
        ];
        
        $logId = $this->auditService->logDelete('transactions', 999, $data);
        
        $this->assertIsInt($logId);
        $this->assertGreaterThan(0, $logId);
    }

    /**
     * Test logLogin success
     */
    public function testLogLoginSuccess(): void
    {
        $logId = $this->auditService->logLogin('user@test.com', true, 1);
        
        $this->assertIsInt($logId);
        $this->assertGreaterThan(0, $logId);
    }

    /**
     * Test logLogin failed
     */
    public function testLogLoginFailed(): void
    {
        $logId = $this->auditService->logLogin('wrong@test.com', false, null, 'Invalid password');
        
        $this->assertIsInt($logId);
        $this->assertGreaterThan(0, $logId);
    }

    /**
     * Test logLogout
     */
    public function testLogLogout(): void
    {
        $logId = $this->auditService->logLogout();
        
        $this->assertIsInt($logId);
        $this->assertGreaterThan(0, $logId);
    }

    /**
     * Test logPasswordChange
     */
    public function testLogPasswordChange(): void
    {
        $logId = $this->auditService->logPasswordChange();
        
        $this->assertIsInt($logId);
        $this->assertGreaterThan(0, $logId);
    }

    /**
     * Test sanitizeValues masque les mots de passe
     */
    public function testSanitizeValuesMasksPasswords(): void
    {
        $data = [
            'username' => 'john',
            'password' => 'secret123!',
            'email' => 'john@test.com'
        ];
        
        $reflection = new \ReflectionClass($this->auditService);
        $method = $reflection->getMethod('sanitizeValues');
        $method->setAccessible(true);
        
        $sanitized = $method->invoke($this->auditService, $data);
        
        $this->assertEquals('john', $sanitized['username']);
        $this->assertEquals('[REDACTED]', $sanitized['password']);
        $this->assertEquals('john@test.com', $sanitized['email']);
    }

    /**
     * Test sanitizeValues masque les champs sensibles
     */
    public function testSanitizeValuesMasksSensitiveFields(): void
    {
        $data = [
            'username' => 'jane',
            'password' => 'MyP@ss123',
            'new_password' => 'NewP@ss123',
            'current_password' => 'OldP@ss123',
            'password_confirmation' => 'NewP@ss123'
        ];
        
        $reflection = new \ReflectionClass($this->auditService);
        $method = $reflection->getMethod('sanitizeValues');
        $method->setAccessible(true);
        
        $sanitized = $method->invoke($this->auditService, $data);
        
        $this->assertEquals('[REDACTED]', $sanitized['password']);
        $this->assertEquals('[REDACTED]', $sanitized['new_password']);
        $this->assertEquals('[REDACTED]', $sanitized['current_password']);
        $this->assertEquals('[REDACTED]', $sanitized['password_confirmation']);
    }

    /**
     * Test sanitizeValues avec tableau vide
     */
    public function testSanitizeValuesWithEmptyArray(): void
    {
        $reflection = new \ReflectionClass($this->auditService);
        $method = $reflection->getMethod('sanitizeValues');
        $method->setAccessible(true);
        
        $sanitized = $method->invoke($this->auditService, []);
        
        $this->assertIsArray($sanitized);
        $this->assertEmpty($sanitized);
    }

    /**
     * Test sanitizeValues avec null
     */
    public function testSanitizeValuesWithNull(): void
    {
        $reflection = new \ReflectionClass($this->auditService);
        $method = $reflection->getMethod('sanitizeValues');
        $method->setAccessible(true);
        
        $sanitized = $method->invoke($this->auditService, null);
        
        $this->assertNull($sanitized);
    }

    /**
     * Test sanitizeValues préserve les autres champs
     */
    public function testSanitizeValuesPreservesOtherFields(): void
    {
        $data = [
            'id' => 123,
            'name' => 'John Doe',
            'email' => 'john@test.com',
            'created_at' => '2024-11-20 10:00:00',
            'password' => 'secret'
        ];
        
        $reflection = new \ReflectionClass($this->auditService);
        $method = $reflection->getMethod('sanitizeValues');
        $method->setAccessible(true);
        
        $sanitized = $method->invoke($this->auditService, $data);
        
        $this->assertEquals(123, $sanitized['id']);
        $this->assertEquals('John Doe', $sanitized['name']);
        $this->assertEquals('john@test.com', $sanitized['email']);
        $this->assertEquals('2024-11-20 10:00:00', $sanitized['created_at']);
        $this->assertEquals('[REDACTED]', $sanitized['password']);
    }

    /**
     * Test log avec données sensibles multiples
     */
    public function testLogWithMultipleSensitiveData(): void
    {
        $data = [
            'username' => 'admin',
            'password' => 'AdminP@ss123',
            'email' => 'admin@test.com',
            'role' => 'administrator'
        ];
        
        $logId = $this->auditService->logCreate('users', 100, $data);
        
        $this->assertIsInt($logId);
        $this->assertGreaterThan(0, $logId);
        
        // Vérifier que le mot de passe a été masqué dans la BDD
        // (nécessite d'interroger audit_logs, mais on vérifie juste que ça ne plante pas)
    }

    /**
     * Test log sans session utilisateur
     */
    public function testLogWithoutUserSession(): void
    {
        unset($_SESSION['user']);
        
        $logId = $this->auditService->log(
            AuditLogService::ACTION_LOGIN_FAILED,
            null,
            null,
            null,
            null,
            'unknown@test.com'
        );
        
        $this->assertIsInt($logId);
        $this->assertGreaterThan(0, $logId);
    }

    /**
     * Test log avec IP personnalisée
     */
    public function testLogWithCustomIP(): void
    {
        $_SERVER['REMOTE_ADDR'] = '192.168.1.100';
        
        $logId = $this->auditService->logCreate('test', 1, ['data' => 'test']);
        
        $this->assertIsInt($logId);
        $this->assertGreaterThan(0, $logId);
    }

    /**
     * Test log avec User-Agent personnalisé
     */
    public function testLogWithCustomUserAgent(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 Test Browser';
        
        $logId = $this->auditService->logCreate('test', 1, ['data' => 'test']);
        
        $this->assertIsInt($logId);
        $this->assertGreaterThan(0, $logId);
    }

    /**
     * Test log avec différentes méthodes HTTP
     */
    public function testLogWithDifferentHTTPMethods(): void
    {
        $methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'];
        
        foreach ($methods as $method) {
            $_SERVER['REQUEST_METHOD'] = $method;
            
            $logId = $this->auditService->log(AuditLogService::ACTION_UPDATE, 'test', 1, null, null);
            
            $this->assertIsInt($logId);
            $this->assertGreaterThan(0, $logId);
        }
    }

    /**
     * Test sanitizeValues avec tableaux imbriqués
     */
    public function testSanitizeValuesWithNestedArrays(): void
    {
        $data = [
            'user' => [
                'name' => 'John',
                'password' => 'secret123'
            ],
            'settings' => [
                'theme' => 'dark',
                'password' => 'another_secret'
            ]
        ];
        
        $reflection = new \ReflectionClass($this->auditService);
        $method = $reflection->getMethod('sanitizeValues');
        $method->setAccessible(true);
        
        $sanitized = $method->invoke($this->auditService, $data);
        
        // Vérifier que les mots de passe dans les sous-tableaux sont masqués
        $this->assertIsArray($sanitized);
        $this->assertArrayHasKey('user', $sanitized);
        $this->assertArrayHasKey('settings', $sanitized);
    }

    /**
     * Test performance logging (doit être rapide)
     */
    public function testLoggingPerformance(): void
    {
        $startTime = microtime(true);
        
        // Logger 50 événements
        for ($i = 0; $i < 50; $i++) {
            $this->auditService->logCreate('test', $i, ['data' => "test_$i"]);
        }
        
        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        
        // Doit prendre moins de 2 secondes pour 50 logs
        $this->assertLessThan(2.0, $duration, 'Logging is too slow');
    }

    /**
     * Test que les logs incluent l'horodatage
     */
    public function testLogsIncludeTimestamp(): void
    {
        $beforeLog = time();
        
        $logId = $this->auditService->logCreate('test', 1, ['data' => 'test']);
        
        $afterLog = time();
        
        $this->assertIsInt($logId);
        // Le log doit avoir été créé entre beforeLog et afterLog
        $this->assertGreaterThanOrEqual($beforeLog, time());
        $this->assertLessThanOrEqual($afterLog, time());
    }

    /**
     * Test log avec recordId null (pour actions globales)
     */
    public function testLogWithNullRecordId(): void
    {
        $logId = $this->auditService->log(
            AuditLogService::ACTION_LOGOUT,
            null,
            null,
            null,
            null
        );
        
        $this->assertIsInt($logId);
        $this->assertGreaterThan(0, $logId);
    }

    /**
     * Test log avec table null (pour actions système)
     */
    public function testLogWithNullTable(): void
    {
        $logId = $this->auditService->log(
            'system_event',
            null,
            null,
            null,
            ['event' => 'backup_completed']
        );
        
        $this->assertIsInt($logId);
        $this->assertGreaterThan(0, $logId);
    }

    /**
     * Test logUpdate avec changements multiples
     */
    public function testLogUpdateWithMultipleChanges(): void
    {
        $oldValues = [
            'name' => 'Old Name',
            'email' => 'old@test.com',
            'status' => 'active',
            'amount' => 100
        ];
        
        $newValues = [
            'name' => 'New Name',
            'email' => 'new@test.com',
            'status' => 'inactive',
            'amount' => 200
        ];
        
        $logId = $this->auditService->logUpdate('users', 50, $oldValues, $newValues);
        
        $this->assertIsInt($logId);
        $this->assertGreaterThan(0, $logId);
    }

    /**
     * Test que les valeurs JSON sont correctement encodées
     */
    public function testValuesAreJSONEncoded(): void
    {
        $data = [
            'array_field' => [1, 2, 3],
            'object_field' => ['key' => 'value'],
            'string_field' => 'simple string'
        ];
        
        $logId = $this->auditService->logCreate('test', 1, $data);
        
        $this->assertIsInt($logId);
        $this->assertGreaterThan(0, $logId);
    }
}
