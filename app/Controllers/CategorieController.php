<?php

namespace MonBudget\Controllers;

use MonBudget\Models\Categorie;
use MonBudget\Models\SousCategorie;

/**
 * Contrôleur de gestion des catégories et sous-catégories
 * 
 * Gère toutes les opérations CRUD sur les catégories (dépenses, revenus)
 * et leurs sous-catégories. Permet l'organisation hiérarchique des transactions.
 * 
 * @package MonBudget\Controllers
 * @author MonBudget
 * @version 1.0.0
 */
class CategorieController extends BaseController
{
    /**
     * Instance du modèle SousCategorie
     * 
     * @var SousCategorie
     */
    private SousCategorie $sousCategorieModel;

    /**
     * Constructeur - Initialise le modèle SousCategorie
     */
    public function __construct()
    {
        parent::__construct();
        $this->sousCategorieModel = new SousCategorie();
    }

    /**
     * Lister toutes les catégories avec hiérarchie
     * 
     * Affiche les catégories principales et leurs sous-catégories.
     * Filtrage optionnel par type (dépense/revenu).
     * 
     * @return void
     */
    public function index(): void
    {
        $this->requireAuth();
        
        $type = $_GET['type'] ?? null;
        $categories = Categorie::getHierarchie($this->userId, $type);
        
        $this->view('categories/index', [
            'categories' => $categories,
            'type_filtre' => $type,
            'isAdmin' => $this->isAdmin()
        ]);
    }

    /**
     * Afficher le formulaire de création de catégorie
     * 
     * Permet de créer une catégorie principale ou une sous-catégorie.
     * 
     * @return void
     */
    public function create(): void
    {
        $this->requireAuth();
        
        $type = $_GET['type'] ?? 'depense';
        $categoriesPrincipales = Categorie::getCategoriesPrincipales($this->userId);
        
        $this->view('categories/create', [
            'type' => $type,
            'categoriesPrincipales' => $categoriesPrincipales,
            'isAdmin' => $this->isAdmin()
        ]);
    }

    /**
     * Enregistre une nouvelle catégorie
     */
    public function store(): void
    {
        $this->requireAuth();
        if (!$this->validateCsrfOrFail('categories/create')) return;

        // Si admin coche "catégorie système", user_id = NULL, sinon = userId actuel
        $isSystemCategory = $this->isAdmin() && isset($_POST['is_system']) && $_POST['is_system'] == '1';

        $data = [
            'user_id' => $isSystemCategory ? null : $this->userId,
            'nom' => trim($_POST['nom'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'type' => $_POST['type'] ?? 'depense',
            'couleur' => $_POST['couleur'] ?? '#6c757d',
            'icone' => $_POST['icone'] ?? 'bi-tag',
            'parent_id' => !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null
        ];

        // Validation
        $errors = [];
        
        if (empty($data['nom'])) {
            $errors[] = "Le nom est obligatoire";
        }
        
        if (!in_array($data['type'], ['depense', 'revenu', 'mixte'])) {
            $errors[] = "Le type doit être 'depense', 'revenu' ou 'mixte'";
        }

        if (!empty($errors)) {
            flash('error', implode('<br>', $errors));
            header('Location: ' . url('categories/create'));
            exit;
        }

        $id = Categorie::create($data);

        if ($id) {
            flash('success', "Catégorie créée avec succès");
        } else {
            flash('error', "Erreur lors de la création de la catégorie");
        }

        header('Location: ' . url('categories'));
        exit;
    }

    /**
     * Affiche le formulaire d'édition
     */
    public function edit(int $id): void
    {
        $this->requireAuth();
        
        $categorie = Categorie::findWithSousCategories($id);

        // Autoriser l'édition si c'est la catégorie de l'utilisateur OU si l'utilisateur est admin
        if (!$categorie || ($categorie['user_id'] != $this->userId && !$this->isAdmin())) {
            flash('error', "Catégorie non trouvée ou accès refusé");
            header('Location: ' . url('categories'));
            exit;
        }

        $categoriesPrincipales = Categorie::getCategoriesPrincipales($this->userId);
        
        // Exclure la catégorie elle-même de la liste des parents possibles
        $categoriesPrincipales = array_filter($categoriesPrincipales, function($cat) use ($id) {
            return $cat['id'] != $id;
        });

        $this->view('categories/edit', [
            'categorie' => $categorie,
            'categoriesPrincipales' => $categoriesPrincipales,
            'isAdmin' => $this->isAdmin()
        ]);
    }

    /**
     * Met à jour une catégorie
     */
    public function update(int $id): void
    {
        $this->requireAuth();
        if (!$this->validateCsrfOrFail('categories')) return;

        $categorie = Categorie::find($id);

        // Autoriser la modification si c'est la catégorie de l'utilisateur OU si l'utilisateur est admin
        if (!$categorie || ($categorie['user_id'] != $this->userId && !$this->isAdmin())) {
            flash('error', "Catégorie non trouvée ou accès refusé");
            header('Location: ' . url('categories'));
            exit;
        }

        // Si admin coche/décoche "catégorie système", gérer user_id
        $isSystemCategory = $this->isAdmin() && isset($_POST['is_system']) && $_POST['is_system'] == '1';
        
        $data = [
            'nom' => trim($_POST['nom'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'type' => $_POST['type'] ?? 'depense',
            'couleur' => $_POST['couleur'] ?? '#6c757d',
            'icone' => $_POST['icone'] ?? 'bi-tag',
            'parent_id' => !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null
        ];

        // Si admin, gérer le changement de statut système
        if ($this->isAdmin()) {
            $data['user_id'] = $isSystemCategory ? null : $this->userId;
        }

        // Validation
        $errors = [];
        
        if (empty($data['nom'])) {
            $errors[] = "Le nom est obligatoire";
        }
        
        // Empêcher qu'une catégorie soit son propre parent
        if ($data['parent_id'] == $id) {
            $errors[] = "Une catégorie ne peut pas être son propre parent";
        }

        if (!empty($errors)) {
            flash('error', implode('<br>', $errors));
            header('Location: ' . url("categories/{$id}/edit"));
            exit;
        }

        if (Categorie::update($id, $data)) {
            flash('success', "Catégorie modifiée avec succès");
        } else {
            flash('error', "Erreur lors de la modification");
        }

        header('Location: ' . url('categories'));
        exit;
    }

    /**
     * Supprime une catégorie
     */
    public function destroy(int $id): void
    {
        $this->requireAuth();
        if (!$this->validateCsrfOrFail('categories')) return;

        $categorie = Categorie::find($id);

        // Autoriser la suppression si c'est la catégorie de l'utilisateur OU si l'utilisateur est admin
        if (!$categorie || ($categorie['user_id'] != $this->userId && !$this->isAdmin())) {
            flash('error', "Catégorie non trouvée ou accès refusé");
            header('Location: ' . url('categories'));
            exit;
        }

        // Vérifier s'il y a des transactions
        $nbTransactions = Categorie::countTransactions($id);
        if ($nbTransactions > 0) {
            flash('error', "Impossible de supprimer cette catégorie : elle est utilisée par {$nbTransactions} transaction(s)");
            header('Location: ' . url('categories'));
            exit;
        }

        // Vérifier s'il y a des sous-catégories
        $sousCategories = Categorie::getSousCategories($id);
        if (!empty($sousCategories)) {
            $nbSous = count($sousCategories);
            flash('error', "Impossible de supprimer cette catégorie : elle contient {$nbSous} sous-catégorie(s)");
            header('Location: ' . url('categories'));
            exit;
        }

        if (Categorie::delete($id)) {
            flash('success', "Catégorie supprimée avec succès");
        } else {
            flash('error', "Erreur lors de la suppression de la catégorie");
        }

        header('Location: ' . url('categories'));
        exit;
    }

    /**
     * Créer une sous-catégorie
     */
    public function createSous(int $categorieId): void
    {
        $this->requireAuth();
        
        $categorie = Categorie::find($categorieId);

        if (!$categorie || $categorie['user_id'] != $this->userId) {
            $_SESSION['error'] = "Catégorie parente non trouvée";
            header('Location: ' . url('categories'));
            exit;
        }

        $this->view('categories/create_sous', [
            'categorie' => $categorie
        ]);
    }

    /**
     * Enregistrer une sous-catégorie
     */
    public function storeSous(): void
    {
        $this->requireAuth();
        if (!$this->validateCsrfOrFail('categories')) return;

        $categorieId = (int)($_POST['categorie_id'] ?? 0);
        $categorie = Categorie::find($categorieId);

        if (!$categorie || $categorie['user_id'] != $this->userId) {
            $_SESSION['error'] = "Catégorie parente non trouvée";
            header('Location: ' . url('categories'));
            exit;
        }

        $data = [
            'categorie_id' => $categorieId,
            'nom' => trim($_POST['nom'] ?? ''),
            'description' => trim($_POST['description'] ?? '')
        ];

        if (empty($data['nom'])) {
            $_SESSION['error'] = "Le nom est obligatoire";
            header('Location: ' . url("categories/{$categorieId}/create-sous"));
            exit;
        }

        $id = $this->sousCategorieModel->create($data);

        if ($id) {
            $_SESSION['success'] = "Sous-catégorie créée avec succès";
        } else {
            $_SESSION['error'] = "Erreur lors de la création";
        }

        header('Location: ' . url('categories'));
        exit;
    }

    /**
     * Affiche le formulaire d'édition d'une sous-catégorie
     */
    public function editSous(int $id): void
    {
        $this->requireAuth();
        
        $sousCategorie = SousCategorie::findWithCategorie($id);

        if (!$sousCategorie || $sousCategorie['categorie_user_id'] != $this->userId) {
            $_SESSION['error'] = "Sous-catégorie non trouvée";
            header('Location: ' . url('categories'));
            exit;
        }

        $this->view('categories/edit_sous', [
            'sousCategorie' => $sousCategorie
        ]);
    }

    /**
     * Met à jour une sous-catégorie
     */
    public function updateSous(int $id): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $sousCategorie = SousCategorie::findWithCategorie($id);

        if (!$sousCategorie || $sousCategorie['categorie_user_id'] != $this->userId) {
            $_SESSION['error'] = "Sous-catégorie non trouvée";
            header('Location: ' . url('categories'));
            exit;
        }

        $data = [
            'nom' => trim($_POST['nom'] ?? ''),
            'description' => trim($_POST['description'] ?? '')
        ];

        if (empty($data['nom'])) {
            $_SESSION['error'] = "Le nom est obligatoire";
            header('Location: ' . url("categories/sous/{$id}/edit"));
            exit;
        }

        if (SousCategorie::update($id, $data)) {
            $_SESSION['success'] = "Sous-catégorie modifiée avec succès";
        } else {
            $_SESSION['error'] = "Erreur lors de la modification";
        }

        header('Location: ' . url('categories'));
        exit;
    }

    /**
     * Supprime une sous-catégorie
     */
    public function destroySous(int $id): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $sousCategorie = SousCategorie::findWithCategorie($id);

        if (!$sousCategorie || $sousCategorie['categorie_user_id'] != $this->userId) {
            $_SESSION['error'] = "Sous-catégorie non trouvée";
            header('Location: ' . url('categories'));
            exit;
        }

        if (SousCategorie::deleteSousCategorie($id)) {
            $_SESSION['success'] = "Sous-catégorie supprimée avec succès";
        } else {
            $_SESSION['error'] = "Impossible de supprimer cette sous-catégorie (transactions liées)";
        }

        header('Location: ' . url('categories'));
        exit;
    }
    
    /**
     * API - Récupérer les sous-catégories d'une catégorie (pour AJAX)
     * 
     * ✅ FIX BUG : Accepter les catégories système (user_id = NULL) + catégories user
     */
    public function apiGetSousCategories(int $id): void
    {
        $this->requireAuth();
        
        // Vérifier que la catégorie existe
        $categorie = Categorie::find($id);
        if (!$categorie) {
            $this->json(['error' => 'Catégorie non trouvée'], 404);
            return;
        }
        
        // ✅ FIX : Accepter les catégories système (user_id IS NULL) ET les catégories de l'utilisateur
        if ($categorie['user_id'] !== null && $categorie['user_id'] != $this->userId) {
            $this->json(['error' => 'Accès refusé'], 403);
            return;
        }
        
        // Récupérer les sous-catégories
        $sousCategories = Categorie::getSousCategories($id);
        
        $this->json($sousCategories);
    }
}
