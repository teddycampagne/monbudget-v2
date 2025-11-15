<?php

namespace MonBudget\Tests\Models;

use MonBudget\Tests\TestCase;
use MonBudget\Models\Compte;

/**
 * Tests pour le modèle Compte
 */
class CompteTest extends TestCase
{
    private int $testUserId;
    private int $testBanqueId;

    /**
     * Setup avant chaque test
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Créer un utilisateur de test
        $this->testUserId = $this->createTestUser();

        // Créer une banque de test
        $this->testBanqueId = $this->createTestBanque();
    }

    /**
     * Teardown après chaque test
     */
    protected function tearDown(): void
    {
        // Nettoyer les données de test
        $this->deleteTestBanque($this->testBanqueId);
        $this->deleteTestUser($this->testUserId);
        
        parent::tearDown();
    }

    /**
     * Test : récupérer les comptes d'un utilisateur
     */
    public function testGetByUser(): void
    {
        // Créer un compte de test
        $compteId = $this->createTestCompte();

        // Récupérer les comptes
        $comptes = Compte::getByUser($this->testUserId);

        // Assertions
        $this->assertIsArray($comptes);
        $this->assertNotEmpty($comptes);
        $this->assertArrayHasKeys(['id', 'nom', 'banque_id', 'solde_actuel'], $comptes[0]);

        // Nettoyer
        $this->deleteTestCompte($compteId);
    }

    /**
     * Test : récupérer un compte par ID
     */
    public function testGetById(): void
    {
        // Créer un compte de test
        $compteId = $this->createTestCompte();

        // Récupérer le compte
        $compte = Compte::getById($compteId);

        // Assertions
        $this->assertIsArray($compte);
        $this->assertEquals($compteId, $compte['id']);
        $this->assertEquals('Compte Test', $compte['nom']);

        // Nettoyer
        $this->deleteTestCompte($compteId);
    }

    /**
     * Test : créer un compte
     */
    public function testCreate(): void
    {
        $data = [
            'user_id' => $this->testUserId,
            'banque_id' => $this->testBanqueId,
            'nom' => 'Nouveau Compte',
            'type_compte' => 'Compte Courant',
            'devise' => 'EUR',
            'solde_initial' => 1000.00,
            'actif' => 1
        ];

        // Créer le compte
        $compteId = Compte::create($data);

        // Assertions
        $this->assertIsInt($compteId);
        $this->assertGreaterThan(0, $compteId);

        // Vérifier que le compte existe
        $compte = Compte::getById($compteId);
        $this->assertEquals('Nouveau Compte', $compte['nom']);
        $this->assertEquals(1000.00, $compte['solde_initial']);

        // Nettoyer
        $this->deleteTestCompte($compteId);
    }

    /**
     * Test : mettre à jour un compte
     */
    public function testUpdate(): void
    {
        // Créer un compte de test
        $compteId = $this->createTestCompte();

        // Mettre à jour
        $data = ['nom' => 'Compte Modifié'];
        $result = Compte::update($compteId, $data);

        // Assertions
        $this->assertEquals(1, $result);

        $compte = Compte::getById($compteId);
        $this->assertEquals('Compte Modifié', $compte['nom']);

        // Nettoyer
        $this->deleteTestCompte($compteId);
    }

    /**
     * Test : supprimer un compte
     */
    public function testDelete(): void
    {
        // Créer un compte de test
        $compteId = $this->createTestCompte();

        // Supprimer
        $result = Compte::delete($compteId);

        // Assertions
        $this->assertEquals(1, $result);

        $compte = Compte::getById($compteId);
        $this->assertEmpty($compte);
    }

    /**
     * Helpers pour créer/supprimer des données de test
     */
    private function createTestBanque(): int
    {
        $sql = "INSERT INTO banques (nom, code_banque, bic) VALUES (?, ?, ?)";
        \MonBudget\Core\Database::execute($sql, ['Banque Test', '12345', 'TESTBICXXX']);
        return (int) \MonBudget\Core\Database::lastInsertId();
    }

    private function deleteTestBanque(int $id): void
    {
        \MonBudget\Core\Database::execute("DELETE FROM banques WHERE id = ?", [$id]);
    }

    private function createTestCompte(): int
    {
        $data = [
            'user_id' => $this->testUserId,
            'banque_id' => $this->testBanqueId,
            'nom' => 'Compte Test',
            'type_compte' => 'Compte Courant',
            'devise' => 'EUR',
            'solde_initial' => 500.00,
            'actif' => 1
        ];
        return Compte::create($data);
    }

    private function deleteTestCompte(int $id): void
    {
        Compte::delete($id);
    }
}
