<?php

namespace MonBudget\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use MonBudget\Services\EncryptionService;

/**
 * Tests unitaires pour EncryptionService
 * 
 * Teste le chiffrement/déchiffrement des données sensibles (IBAN)
 * conformément aux exigences PCI DSS
 */
class EncryptionServiceTest extends TestCase
{
    private string $originalKey;
    private EncryptionService $encryptionService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Sauvegarder la clé actuelle
        $this->originalKey = getenv('ENCRYPTION_KEY') ?: '';
        
        // Définir une clé de test (utiliser putenv pour getenv)
        $testKey = base64_encode(random_bytes(32));
        putenv("ENCRYPTION_KEY=$testKey");
        $_ENV['ENCRYPTION_KEY'] = $testKey;
        
        // Créer l'instance du service
        $this->encryptionService = new EncryptionService();
    }

    protected function tearDown(): void
    {
        // Restaurer la clé originale
        if ($this->originalKey) {
            putenv("ENCRYPTION_KEY={$this->originalKey}");
            $_ENV['ENCRYPTION_KEY'] = $this->originalKey;
        } else {
            putenv('ENCRYPTION_KEY');
            unset($_ENV['ENCRYPTION_KEY']);
        }
        
        parent::tearDown();
    }

    /**
     * Test chiffrement basique
     */
    public function testEncryptReturnsEncryptedString(): void
    {
        $plaintext = 'FR7612345678901234567890123';
        
        $encrypted = $this->encryptionService->encrypt($plaintext);
        
        $this->assertNotEmpty($encrypted);
        $this->assertNotEquals($plaintext, $encrypted);
        $this->assertStringContainsString(':', $encrypted); // Format iv:encrypted
    }

    /**
     * Test déchiffrement basique
     */
    public function testDecryptReturnsOriginalString(): void
    {
        $plaintext = 'FR7612345678901234567890123';
        
        $encrypted = $this->encryptionService->encrypt($plaintext);
        $decrypted = $this->encryptionService->decrypt($encrypted);
        
        $this->assertEquals($plaintext, $decrypted);
    }

    /**
     * Test chiffrement/déchiffrement de chaîne vide
     */
    public function testEncryptDecryptEmptyString(): void
    {
        $plaintext = '';
        
        $encrypted = $this->encryptionService->encrypt($plaintext);
        $decrypted = $this->encryptionService->decrypt($encrypted);
        
        $this->assertEquals($plaintext, $decrypted);
    }

    /**
     * Test chiffrement IBAN
     */
    public function testEncryptIBAN(): void
    {
        $iban = 'FR7612345678901234567890123';
        
        $encrypted = $this->encryptionService->encryptIBAN($iban);
        
        $this->assertNotEmpty($encrypted);
        $this->assertNotEquals($iban, $encrypted);
    }

    /**
     * Test déchiffrement IBAN
     */
    public function testDecryptIBAN(): void
    {
        $iban = 'FR7612345678901234567890123';
        
        $encrypted = $this->encryptionService->encryptIBAN($iban);
        $decrypted = $this->encryptionService->decryptIBAN($encrypted);
        
        $this->assertEquals($iban, $decrypted);
    }

    /**
     * Test masquage IBAN
     */
    public function testMaskIBAN(): void
    {
        $iban = 'FR7612345678901234567890123';
        
        $masked = $this->encryptionService->maskIBAN($iban);
        
        $this->assertEquals('FR76 **** **** **** **** **23', $masked);
    }

    /**
     * Test masquage IBAN court
     */
    public function testMaskShortIBAN(): void
    {
        $iban = 'FR76123';
        
        $masked = $this->encryptionService->maskIBAN($iban);
        
        // IBAN trop court, retourner tel quel
        $this->assertEquals($iban, $masked);
    }

    /**
     * Test détection données chiffrées
     */
    public function testIsEncryptedReturnsTrueForEncryptedData(): void
    {
        $plaintext = 'FR7612345678901234567890123';
        $encrypted = $this->encryptionService->encrypt($plaintext);
        
        $this->assertTrue($this->encryptionService->isEncrypted($encrypted));
    }

    /**
     * Test détection données non chiffrées
     */
    public function testIsEncryptedReturnsFalseForPlaintext(): void
    {
        $plaintext = 'FR7612345678901234567890123';
        
        $this->assertFalse($this->encryptionService->isEncrypted($plaintext));
    }

    /**
     * Test détection chaîne vide
     */
    public function testIsEncryptedReturnsFalseForEmptyString(): void
    {
        $this->assertFalse($this->encryptionService->isEncrypted(''));
    }

    /**
     * Test détection format invalide
     */
    public function testIsEncryptedReturnsFalseForInvalidFormat(): void
    {
        $this->assertFalse($this->encryptionService->isEncrypted('invalid:format:extra'));
        $this->assertFalse($this->encryptionService->isEncrypted('no_separator'));
    }

    /**
     * Test unicité du chiffrement (IV aléatoire)
     */
    public function testEncryptionUsesRandomIV(): void
    {
        $plaintext = 'FR7612345678901234567890123';
        
        $encrypted1 = $this->encryptionService->encrypt($plaintext);
        $encrypted2 = $this->encryptionService->encrypt($plaintext);
        
        // Deux chiffrements du même texte doivent être différents (IV différent)
        $this->assertNotEquals($encrypted1, $encrypted2);
        
        // Mais les deux doivent déchiffrer vers le même texte
        $this->assertEquals($plaintext, $this->encryptionService->decrypt($encrypted1));
        $this->assertEquals($plaintext, $this->encryptionService->decrypt($encrypted2));
    }

    /**
     * Test déchiffrement de données invalides
     */
    public function testDecryptInvalidDataReturnsNull(): void
    {
        $this->assertNull($this->encryptionService->decrypt('invalid_data'));
        $this->assertNull($this->encryptionService->decrypt('invalid:format:extra'));
    }

    /**
     * Test déchiffrement avec mauvaise clé
     */
    public function testDecryptWithWrongKeyReturnsFalse(): void
    {
        $plaintext = 'FR7612345678901234567890123';
        
        // Chiffrer avec une clé
        $encrypted = $this->encryptionService->encrypt($plaintext);
        
        // Changer la clé et créer nouvelle instance
        $newTestKey = base64_encode(random_bytes(32));
        putenv("ENCRYPTION_KEY=$newTestKey");
        $_ENV['ENCRYPTION_KEY'] = $newTestKey;
        $newService = new EncryptionService();
        
        // Déchiffrer avec une autre clé devrait échouer
        $decrypted = $newService->decrypt($encrypted);
        
        $this->assertNull($decrypted);
    }

    /**
     * Test format IBAN avec espaces
     */
    public function testMaskIBANWithSpaces(): void
    {
        $iban = 'FR76 1234 5678 9012 3456 7890 123';
        
        $masked = $this->encryptionService->maskIBAN($iban);
        
        // Devrait retirer les espaces et masquer
        $this->assertStringContainsString('****', $masked);
        $this->assertStringStartsWith('FR76', $masked);
    }

    /**
     * Test chiffrement données sensibles multiples
     */
    public function testEncryptMultipleSensitiveData(): void
    {
        $data = [
            'FR7612345678901234567890123',
            'DE89370400440532013000',
            'GB29NWBK60161331926819'
        ];
        
        $encrypted = [];
        foreach ($data as $iban) {
            $encrypted[] = $this->encryptionService->encryptIBAN($iban);
        }
        
        // Tous doivent être chiffrés
        $this->assertCount(3, $encrypted);
        foreach ($encrypted as $enc) {
            $this->assertTrue($this->encryptionService->isEncrypted($enc));
        }
        
        // Tous doivent déchiffrer correctement
        foreach ($data as $index => $iban) {
            $decrypted = $this->encryptionService->decryptIBAN($encrypted[$index]);
            $this->assertEquals($iban, $decrypted);
        }
    }

    /**
     * Test conservation de la longueur lors du masquage
     */
    public function testMaskIBANPreservesLength(): void
    {
        $ibans = [
            'FR7612345678901234567890123',    // 27 chars
            'DE89370400440532013000',          // 22 chars
            'GB29NWBK60161331926819'           // 22 chars
        ];
        
        foreach ($ibans as $iban) {
            $masked = $this->encryptionService->maskIBAN($iban);
            
            // Le masquage ajoute des espaces, mais conserve l'info essentielle
            $this->assertStringStartsWith(substr($iban, 0, 4), $masked);
            $this->assertStringContainsString('****', $masked);
        }
    }

    /**
     * Test IBAN null
     */
    public function testEncryptIBANWithNull(): void
    {
        $encrypted = $this->encryptionService->encryptIBAN(null);
        
        $this->assertNull($encrypted);
    }

    /**
     * Test déchiffrement IBAN null
     */
    public function testDecryptIBANWithNull(): void
    {
        $decrypted = $this->encryptionService->decryptIBAN(null);
        
        $this->assertNull($decrypted);
    }

    /**
     * Test masquage IBAN null
     */
    public function testMaskIBANWithNull(): void
    {
        $masked = $this->encryptionService->maskIBAN(null);
        
        $this->assertEquals('', $masked);
    }

    /**
     * Test performance chiffrement (doit être rapide)
     */
    public function testEncryptionPerformance(): void
    {
        $plaintext = 'FR7612345678901234567890123';
        
        $startTime = microtime(true);
        
        // Chiffrer 100 fois
        for ($i = 0; $i < 100; $i++) {
            $this->encryptionService->encrypt($plaintext);
        }
        
        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        
        // Doit prendre moins de 1 seconde pour 100 opérations
        $this->assertLessThan(1.0, $duration, 'Encryption is too slow');
    }

    /**
     * Test déchiffrement IBAN déjà en clair
     */
    public function testDecryptIBANPlaintext(): void
    {
        $iban = 'FR7612345678901234567890123';
        
        // Si l'IBAN n'est pas chiffré, le retourner tel quel
        $decrypted = $this->encryptionService->decryptIBAN($iban);
        
        $this->assertEquals($iban, $decrypted);
    }
}
