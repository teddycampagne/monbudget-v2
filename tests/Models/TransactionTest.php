<?php

namespace MonBudget\Tests\Models;

use MonBudget\Tests\TestCase;
use MonBudget\Models\Transaction;

/**
 * Tests pour le modèle Transaction
 */
class TransactionTest extends TestCase
{
    private int $testUserId;
    private int $testBanqueId;
    private int $testCompteId;
    private int $testCategorieId;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->testUserId = $this->createTestUser();
        $this->testBanqueId = $this->createTestBanque();
        $this->testCompteId = $this->createTestCompte();
        $this->testCategorieId = $this->createTestCategorie();
        
        // Initialiser la session pour les tests
        $_SESSION['user']['id'] = $this->testUserId;
    }

    protected function tearDown(): void
    {
        $this->deleteTestCategorie($this->testCategorieId);
        $this->deleteTestCompte($this->testCompteId);
        $this->deleteTestBanque($this->testBanqueId);
        $this->deleteTestUser($this->testUserId);
        
        parent::tearDown();
    }

    /**
     * Test : récupérer les transactions par compte
     */
    public function testGetByCompte(): void
    {
        // Créer une transaction de test
        $transactionId = $this->createTestTransaction();

        // Récupérer les transactions
        $transactions = Transaction::getByCompte($this->testCompteId);

        // Assertions
        $this->assertIsArray($transactions);
        $this->assertNotEmpty($transactions);
        $this->assertArrayHasKeys(['id', 'libelle', 'montant', 'type_operation'], $transactions[0]);

        // Nettoyer
        $this->deleteTestTransaction($transactionId);
    }

    /**
     * Test : créer une transaction de type débit
     */
    public function testCreateDebit(): void
    {
        $data = [
            'user_id' => $this->testUserId,
            'compte_id' => $this->testCompteId,
            'libelle' => 'Test Débit',
            'montant' => 50.00,
            'type_operation' => 'debit',
            'date_transaction' => date('Y-m-d'),
            'categorie_id' => $this->testCategorieId,
            'validee' => 1
        ];

        $transactionId = Transaction::create($data);

        // Assertions
        $this->assertIsInt($transactionId);
        $this->assertGreaterThan(0, $transactionId);

        $transaction = Transaction::getById($transactionId);
        $this->assertEquals('debit', $transaction['type_operation']);
        $this->assertEquals(50.00, $transaction['montant']);

        // Nettoyer
        $this->deleteTestTransaction($transactionId);
    }

    /**
     * Test : créer une transaction de type crédit
     */
    public function testCreateCredit(): void
    {
        $data = [
            'user_id' => $this->testUserId,
            'compte_id' => $this->testCompteId,
            'libelle' => 'Test Crédit',
            'montant' => 100.00,
            'type_operation' => 'credit',
            'date_transaction' => date('Y-m-d'),
            'categorie_id' => $this->testCategorieId,
            'validee' => 1
        ];

        $transactionId = Transaction::create($data);

        $transaction = Transaction::getById($transactionId);
        $this->assertEquals('credit', $transaction['type_operation']);

        $this->deleteTestTransaction($transactionId);
    }

    /**
     * Test : validation du type d'opération
     */
    public function testTypeOperationValidation(): void
    {
        $data = [
            'user_id' => $this->testUserId,
            'compte_id' => $this->testCompteId,
            'libelle' => 'Test Invalid',
            'montant' => 50.00,
            'type_operation' => 'invalid', // Type invalide
            'date_transaction' => date('Y-m-d'),
            'validee' => 1
        ];

        // MySQL ENUM va rejeter la valeur invalide
        // Le comportement peut être une erreur SQL ou une valeur vide insérée
        try {
            $transactionId = Transaction::create($data);
            
            // Si l'insertion réussit, vérifier que la valeur est vide/NULL
            if ($transactionId) {
                $transaction = Transaction::getById($transactionId);
                $this->assertEmpty($transaction['type_operation'] ?? '');
                $this->deleteTestTransaction($transactionId);
            }
        } catch (\Exception $e) {
            // C'est acceptable - l'ENUM a rejeté la valeur
            $this->assertStringContainsString('type_operation', $e->getMessage());
        }
    }

    /**
     * Helpers
     */
    private function createTestBanque(): int
    {
        $sql = "INSERT INTO banques (nom, code_banque, bic) VALUES (?, ?, ?)";
        \MonBudget\Core\Database::execute($sql, ['Banque Test Trans', '54321', 'TESTTRBICX']);
        return (int) \MonBudget\Core\Database::lastInsertId();
    }

    private function deleteTestBanque(int $id): void
    {
        \MonBudget\Core\Database::execute("DELETE FROM banques WHERE id = ?", [$id]);
    }

    private function createTestCompte(): int
    {
        $sql = "INSERT INTO comptes (user_id, banque_id, nom, type_compte, devise, solde_initial, solde_actuel, actif) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        \MonBudget\Core\Database::execute($sql, [
            $this->testUserId,
            $this->testBanqueId,
            'Compte Test Trans',
            'Compte Courant',
            'EUR',
            1000.00,
            1000.00,
            1
        ]);
        return (int) \MonBudget\Core\Database::lastInsertId();
    }

    private function deleteTestCompte(int $id): void
    {
        \MonBudget\Core\Database::execute("DELETE FROM comptes WHERE id = ?", [$id]);
    }

    private function createTestCategorie(): int
    {
        $sql = "INSERT INTO categories (user_id, nom, type, couleur) VALUES (?, ?, ?, ?)";
        \MonBudget\Core\Database::execute($sql, [$this->testUserId, 'Catégorie Test', 'depense', '#ff0000']);
        return (int) \MonBudget\Core\Database::lastInsertId();
    }

    private function deleteTestCategorie(int $id): void
    {
        \MonBudget\Core\Database::execute("DELETE FROM categories WHERE id = ?", [$id]);
    }

    private function createTestTransaction(): int
    {
        $data = [
            'user_id' => $this->testUserId,
            'compte_id' => $this->testCompteId,
            'libelle' => 'Transaction Test',
            'montant' => 25.00,
            'type_operation' => 'debit',
            'date_transaction' => date('Y-m-d'),
            'categorie_id' => $this->testCategorieId,
            'validee' => 1
        ];
        return Transaction::create($data);
    }

    private function deleteTestTransaction(int $id): void
    {
        \MonBudget\Core\Database::execute("DELETE FROM transactions WHERE id = ?", [$id]);
    }
}
