<?php

namespace MonBudget\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use MonBudget\Services\PasswordPolicyService;
use MonBudget\Core\Database;

/**
 * Tests unitaires pour PasswordPolicyService
 * 
 * Teste les politiques de mots de passe PCI DSS :
 * - Validation complexité (12+ chars, upper, lower, digit, special)
 * - Historique (5 derniers mots de passe)
 * - Expiration (90 jours)
 * - Verrouillage compte (5 tentatives, 15 min)
 */
class PasswordPolicyServiceTest extends TestCase
{
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
    }

    /**
     * Test validation mot de passe trop court
     */
    public function testValidatePasswordTooShort(): void
    {
        $errors = PasswordPolicyService::validate('Short1!');
        
        $this->assertNotEmpty($errors);
        $this->assertContains('Le mot de passe doit contenir au moins 12 caractères', $errors);
    }

    /**
     * Test validation mot de passe sans majuscule
     */
    public function testValidatePasswordNoUppercase(): void
    {
        $errors = PasswordPolicyService::validate('lowercase123!');
        
        $this->assertNotEmpty($errors);
        $this->assertContains('Le mot de passe doit contenir au moins une majuscule', $errors);
    }

    /**
     * Test validation mot de passe sans minuscule
     */
    public function testValidatePasswordNoLowercase(): void
    {
        $errors = PasswordPolicyService::validate('UPPERCASE123!');
        
        $this->assertNotEmpty($errors);
        $this->assertContains('Le mot de passe doit contenir au moins une minuscule', $errors);
    }

    /**
     * Test validation mot de passe sans chiffre
     */
    public function testValidatePasswordNoDigit(): void
    {
        $errors = PasswordPolicyService::validate('NoDigitHere!');
        
        $this->assertNotEmpty($errors);
        $this->assertContains('Le mot de passe doit contenir au moins un chiffre', $errors);
    }

    /**
     * Test validation mot de passe sans caractère spécial
     */
    public function testValidatePasswordNoSpecialChar(): void
    {
        $errors = PasswordPolicyService::validate('NoSpecial123');
        
        $this->assertNotEmpty($errors);
        $this->assertContains('Le mot de passe doit contenir au moins un caractère spécial', $errors);
    }

    /**
     * Test validation mot de passe valide
     */
    public function testValidatePasswordValid(): void
    {
        $errors = PasswordPolicyService::validate('ValidPass123!');
        
        $this->assertEmpty($errors);
    }

    /**
     * Test validation mot de passe complexe valide
     */
    public function testValidatePasswordComplexValid(): void
    {
        $passwords = [
            'MyP@ssw0rd123!',
            'Str0ng&Secure#2024',
            'C0mpl3x!P@ssw0rd',
            'Test123!@#ABC'
        ];
        
        foreach ($passwords as $password) {
            $errors = PasswordPolicyService::validate($password);
            $this->assertEmpty($errors, "Password '$password' should be valid");
        }
    }

    /**
     * Test validation mot de passe avec multiples erreurs
     */
    public function testValidatePasswordMultipleErrors(): void
    {
        $errors = PasswordPolicyService::validate('short');
        
        // Doit avoir au moins 4 erreurs (longueur, majuscule, chiffre, spécial)
        $this->assertGreaterThanOrEqual(4, count($errors));
    }

    /**
     * Test longueur minimum (constante MIN_LENGTH)
     */
    public function testMinLengthConstant(): void
    {
        $this->assertEquals(12, PasswordPolicyService::MIN_LENGTH);
    }

    /**
     * Test durée maximale mot de passe (constante MAX_AGE_DAYS)
     */
    public function testMaxAgeDaysConstant(): void
    {
        $this->assertEquals(90, PasswordPolicyService::MAX_AGE_DAYS);
    }

    /**
     * Test nombre maximum tentatives login (constante MAX_LOGIN_ATTEMPTS)
     */
    public function testMaxLoginAttemptsConstant(): void
    {
        $this->assertEquals(5, PasswordPolicyService::MAX_LOGIN_ATTEMPTS);
    }

    /**
     * Test durée verrouillage (constante LOCKOUT_DURATION)
     */
    public function testLockoutDurationConstant(): void
    {
        $this->assertEquals(900, PasswordPolicyService::LOCKOUT_DURATION); // 15 minutes
    }

    /**
     * Test mots de passe avec caractères spéciaux variés
     */
    public function testValidatePasswordWithVariousSpecialChars(): void
    {
        $passwords = [
            'Test123!Password',
            'Test123@Password',
            'Test123#Password',
            'Test123$Password',
            'Test123%Password',
            'Test123^Password',
            'Test123&Password',
            'Test123*Password',
            'Test123(Password)',
            'Test123_Password-'
        ];
        
        foreach ($passwords as $password) {
            $errors = PasswordPolicyService::validate($password);
            $this->assertEmpty($errors, "Password '$password' with special char should be valid");
        }
    }

    /**
     * Test mot de passe exactement 12 caractères (minimum)
     */
    public function testValidatePasswordExactly12Chars(): void
    {
        $password = 'Valid123!Pwd'; // Exactement 12 chars
        
        $errors = PasswordPolicyService::validate($password);
        
        $this->assertEmpty($errors);
    }

    /**
     * Test mot de passe très long (doit être accepté)
     */
    public function testValidatePasswordVeryLong(): void
    {
        $password = 'ThisIsAVeryLongAndComplexPassword123!@#WithManyCharacters';
        
        $errors = PasswordPolicyService::validate($password);
        
        $this->assertEmpty($errors);
    }

    /**
     * Test validation avec espaces (doit être refusé si pas d'autres caractères spéciaux)
     */
    public function testValidatePasswordWithSpaces(): void
    {
        $password = 'Test 123 Password'; // Espaces mais pas de spécial
        
        $errors = PasswordPolicyService::validate($password);
        
        // Doit contenir erreur caractère spécial (espace ne compte pas)
        $this->assertContains('Le mot de passe doit contenir au moins un caractère spécial', $errors);
    }

    /**
     * Test mot de passe avec accents (doit être accepté si critères remplis)
     */
    public function testValidatePasswordWithAccents(): void
    {
        $password = 'Très123!Secret'; // 14 chars, avec accents
        
        $errors = PasswordPolicyService::validate($password);
        
        $this->assertEmpty($errors);
    }

    /**
     * Test validation null
     */
    public function testValidatePasswordNull(): void
    {
        $errors = PasswordPolicyService::validate(null);
        
        $this->assertNotEmpty($errors);
        $this->assertContains('Le mot de passe doit contenir au moins 12 caractères', $errors);
    }

    /**
     * Test validation chaîne vide
     */
    public function testValidatePasswordEmptyString(): void
    {
        $errors = PasswordPolicyService::validate('');
        
        $this->assertNotEmpty($errors);
        $this->assertContains('Le mot de passe doit contenir au moins 12 caractères', $errors);
    }

    /**
     * Test format retour validation (doit être un tableau)
     */
    public function testValidateReturnsArray(): void
    {
        $errors = PasswordPolicyService::validate('short');
        
        $this->assertIsArray($errors);
    }

    /**
     * Test validation retourne tableau vide pour mot de passe valide
     */
    public function testValidateReturnsEmptyArrayForValidPassword(): void
    {
        $errors = PasswordPolicyService::validate('ValidPass123!');
        
        $this->assertIsArray($errors);
        $this->assertEmpty($errors);
    }

    /**
     * Test caractères Unicode comme caractères spéciaux
     */
    public function testValidatePasswordWithUnicodeSpecialChars(): void
    {
        $password = 'Test123€Password'; // € comme caractère spécial
        
        $errors = PasswordPolicyService::validate($password);
        
        // Dépend de l'implémentation, mais devrait accepter Unicode spécial
        $this->assertEmpty($errors);
    }

    /**
     * Test mot de passe commun (devrait être validé par critères techniques uniquement)
     */
    public function testValidatePasswordCommonButTechnicallyValid(): void
    {
        // Mots de passe techniquement valides selon critères PCI DSS
        $passwords = [
            'Password123!',
            'Welcome123!@',
            'Admin123!@#'
        ];
        
        foreach ($passwords as $password) {
            $errors = PasswordPolicyService::validate($password);
            // Techniquement valides (même si communs)
            $this->assertEmpty($errors);
        }
    }

    /**
     * Test performance validation (doit être rapide)
     */
    public function testValidationPerformance(): void
    {
        $password = 'ValidPass123!';
        
        $startTime = microtime(true);
        
        // Valider 1000 fois
        for ($i = 0; $i < 1000; $i++) {
            PasswordPolicyService::validate($password);
        }
        
        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        
        // Doit prendre moins de 0.5 seconde pour 1000 validations
        $this->assertLessThan(0.5, $duration, 'Validation is too slow');
    }

    /**
     * Test que tous les messages d'erreur sont en français
     */
    public function testErrorMessagesInFrench(): void
    {
        $errors = PasswordPolicyService::validate('short');
        
        foreach ($errors as $error) {
            $this->assertIsString($error);
            // Vérifier que c'est du français (contient des mots français)
            $this->assertMatchesRegularExpression('/mot de passe|caractère|majuscule|minuscule|chiffre|spécial/', $error);
        }
    }
}
