# Tests PHPUnit - MonBudget V2

## Structure des Tests

```
tests/
├── TestCase.php              # Classe de base pour tous les tests
├── Unit/                     # Tests unitaires
├── Models/                   # Tests des modèles
│   ├── CompteTest.php
│   ├── TransactionTest.php
│   └── CategorieTest.php
└── Controllers/              # Tests des contrôleurs (à venir)
```

## Installation

PHPUnit est déjà installé via Composer :

```bash
composer install
```

## Configuration

Le fichier `phpunit.xml` configure :
- Les suites de tests (Unit, Models, Controllers)
- Les variables d'environnement pour les tests
- La couverture de code

### Base de données de test

**Important** : Les tests utilisent une base de données séparée `monbudget_test`.

**Fichier `.env.testing`** : Déjà créé à la racine avec la config de test (DB=monbudget_test, APP_ENV=testing)

1. Créer la base via **phpMyAdmin** :
   - Ouvrir phpMyAdmin (http://localhost/phpmyadmin)
   - Nouvelle base de données : `monbudget_test`
   - Interclassement : `utf8mb4_unicode_ci`

2. Importer la structure :
   - Dans phpMyAdmin, sélectionner `monbudget_test`
   - Onglet "Importer"
   - Choisir le fichier `database.sql` à la racine du projet
   - Cliquer "Exécuter"

Alternativement, si MySQL en ligne de commande fonctionne :
```bash
mysql -u root -p monbudget_test < database.sql
```

## Lancer les tests

### Tous les tests
```bash
vendor/bin/phpunit
```

### Une suite spécifique
```bash
# Tests des modèles uniquement
vendor/bin/phpunit --testsuite "Models Tests"

# Tests unitaires uniquement
vendor/bin/phpunit --testsuite "Unit Tests"
```

### Un fichier spécifique
```bash
vendor/bin/phpunit tests/Models/CompteTest.php
```

### Une méthode spécifique
```bash
vendor/bin/phpunit --filter testGetByUser tests/Models/CompteTest.php
```

### Avec couverture de code
```bash
vendor/bin/phpunit --coverage-html coverage/
```

## Tests Disponibles

### CompteTest
- ✅ `testGetByUser()` - Récupérer les comptes d'un utilisateur
- ✅ `testGetById()` - Récupérer un compte par ID
- ✅ `testCreate()` - Créer un nouveau compte
- ✅ `testUpdate()` - Mettre à jour un compte
- ✅ `testDelete()` - Supprimer un compte

### TransactionTest
- ✅ `testGetByCompte()` - Récupérer les transactions d'un compte
- ✅ `testCreateDebit()` - Créer une transaction de débit
- ✅ `testCreateCredit()` - Créer une transaction de crédit
- ✅ `testTypeOperationValidation()` - Valider le type d'opération

### CategorieTest
- ✅ `testGetHierarchie()` - Récupérer la hiérarchie des catégories
- ✅ `testGetCategoriesPrincipales()` - Récupérer les catégories principales
- ✅ `testParentEnfantRelation()` - Tester la relation parent-enfant
- ✅ `testCreate()` - Créer une catégorie
- ✅ `testFilterByType()` - Filtrer par type (revenu/dépense)

## Bonnes Pratiques

### Setup et Teardown
Chaque test crée et nettoie ses propres données :
```php
protected function setUp(): void {
    parent::setUp();
    $this->testUserId = $this->createTestUser();
}

protected function tearDown(): void {
    $this->deleteTestUser($this->testUserId);
    parent::tearDown();
}
```

### Assertions Personnalisées
Utilisez les helpers du TestCase :
```php
$this->assertArrayHasKeys(['id', 'nom', 'email'], $user);
```

### Isolation des Tests
- Chaque test doit être indépendant
- Utiliser des données de test uniques (emails différents, etc.)
- Nettoyer toutes les données créées dans `tearDown()`

## Ajouter de Nouveaux Tests

### 1. Créer le fichier de test
```php
<?php
namespace Tests\Models;

use Tests\TestCase;
use MonBudget\Models\VotreModele;

class VotreModeleTest extends TestCase {
    public function testUneMethode(): void {
        // Arrange
        $data = ['key' => 'value'];
        
        // Act
        $result = VotreModele::method($data);
        
        // Assert
        $this->assertEquals('expected', $result);
    }
}
```

### 2. Lancer le nouveau test
```bash
vendor/bin/phpunit tests/Models/VotreModeleTest.php
```

## Debugging

### Mode verbeux
```bash
vendor/bin/phpunit --verbose
```

### Afficher les erreurs complètes
```bash
vendor/bin/phpunit --debug
```

### Tester avec données de sortie
```bash
vendor/bin/phpunit --testdox
```

## Intégration Continue

Pour CI/CD, ajouter dans `.github/workflows/tests.yml` :
```yaml
- name: Run tests
  run: vendor/bin/phpunit --coverage-text
```

## Prochaines Étapes

- [ ] Ajouter tests pour les contrôleurs
- [ ] Ajouter tests pour les services
- [ ] Augmenter la couverture de code à 80%+
- [ ] Tests d'intégration pour les workflows complets
- [ ] Tests de performance pour les requêtes lourdes
