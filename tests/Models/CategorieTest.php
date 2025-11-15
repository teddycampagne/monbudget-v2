<?php

namespace MonBudget\Tests\Models;

use MonBudget\Tests\TestCase;
use MonBudget\Models\Categorie;

/**
 * Tests pour le modèle Categorie
 */
class CategorieTest extends TestCase
{
    private int $testUserId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testUserId = $this->createTestUser();
    }

    protected function tearDown(): void
    {
        $this->deleteTestUser($this->testUserId);
        parent::tearDown();
    }

    /**
     * Test : récupérer la hiérarchie des catégories
     */
    public function testGetHierarchie(): void
    {
        // Créer une catégorie parente
        $parentId = $this->createTestCategorie('Catégorie Parent', null);
        
        // Créer une sous-catégorie
        $enfantId = $this->createTestCategorie('Sous-catégorie', $parentId);

        // Récupérer la hiérarchie
        $hierarchie = Categorie::getHierarchie($this->testUserId);

        // Assertions
        $this->assertIsArray($hierarchie);
        $this->assertNotEmpty($hierarchie);

        // Vérifier la structure parent-enfant
        $found = false;
        foreach ($hierarchie as $cat) {
            if ($cat['id'] === $parentId) {
                $found = true;
                $this->assertArrayHasKey('sous_categories', $cat);
                $this->assertNotEmpty($cat['sous_categories']);
            }
        }
        $this->assertTrue($found, 'Catégorie parente non trouvée dans la hiérarchie');

        // Nettoyer
        $this->deleteTestCategorie($enfantId);
        $this->deleteTestCategorie($parentId);
    }

    /**
     * Test : récupérer les catégories principales (sans parent)
     */
    public function testGetCategoriesPrincipales(): void
    {
        // Créer une catégorie principale
        $categorieId = $this->createTestCategorie('Catégorie Principale', null);

        // Récupérer les catégories principales
        $categories = Categorie::getCategoriesPrincipales($this->testUserId);

        // Assertions
        $this->assertIsArray($categories);
        $this->assertNotEmpty($categories);

        // Vérifier qu'il n'y a pas de parent_id
        foreach ($categories as $cat) {
            $this->assertNull($cat['parent_id']);
        }

        // Nettoyer
        $this->deleteTestCategorie($categorieId);
    }

    /**
     * Test : relation parent-enfant
     */
    public function testParentEnfantRelation(): void
    {
        // Créer parent
        $parentId = $this->createTestCategorie('Parent Test', null);
        
        // Créer enfant
        $enfantId = $this->createTestCategorie('Enfant Test', $parentId);

        // Récupérer l'enfant
        $enfant = Categorie::getById($enfantId);

        // Assertions
        $this->assertEquals($parentId, $enfant['parent_id']);

        // Nettoyer
        $this->deleteTestCategorie($enfantId);
        $this->deleteTestCategorie($parentId);
    }

    /**
     * Test : créer une catégorie
     */
    public function testCreate(): void
    {
        $data = [
            'user_id' => $this->testUserId,
            'nom' => 'Nouvelle Catégorie',
            'type' => 'depense',
            'couleur' => '#00ff00',
            'icone' => 'cart'
        ];

        $categorieId = Categorie::create($data);

        // Assertions
        $this->assertIsInt($categorieId);
        $this->assertGreaterThan(0, $categorieId);

        $categorie = Categorie::getById($categorieId);
        $this->assertEquals('Nouvelle Catégorie', $categorie['nom']);
        $this->assertEquals('depense', $categorie['type']);

        // Nettoyer
        $this->deleteTestCategorie($categorieId);
    }

    /**
     * Test : filtrer par type (revenu/depense)
     */
    public function testFilterByType(): void
    {
        // Créer une catégorie de type revenu
        $revenuId = $this->createTestCategorie('Salaire', null, 'revenu');
        
        // Créer une catégorie de type dépense
        $depenseId = $this->createTestCategorie('Courses', null, 'depense');

        // Récupérer uniquement les revenus
        $revenus = Categorie::getCategoriesPrincipales($this->testUserId, 'revenu');
        
        // Vérifier que seules les catégories de type revenu sont retournées
        foreach ($revenus as $cat) {
            $this->assertEquals('revenu', $cat['type']);
        }

        // Nettoyer
        $this->deleteTestCategorie($revenuId);
        $this->deleteTestCategorie($depenseId);
    }

    /**
     * Helpers
     */
    private function createTestCategorie(string $nom, ?int $parentId = null, string $type = 'depense'): int
    {
        $sql = "INSERT INTO categories (user_id, nom, type, couleur, parent_id) VALUES (?, ?, ?, ?, ?)";
        \MonBudget\Core\Database::execute($sql, [
            $this->testUserId,
            $nom,
            $type,
            '#cccccc',
            $parentId
        ]);
        return (int) \MonBudget\Core\Database::lastInsertId();
    }

    private function deleteTestCategorie(int $id): void
    {
        \MonBudget\Core\Database::execute("DELETE FROM categories WHERE id = ?", [$id]);
    }
}
