<?php

namespace MonBudget\Controllers;

use MonBudget\Models\Tag;
use MonBudget\Core\Session;

/**
 * Contrôleur Tags - Gestion des étiquettes personnalisées
 * 
 * @package MonBudget\Controllers
 * @version 2.2.0
 */
class TagController extends BaseController
{
    private Tag $tagModel;

    public function __construct()
    {
        parent::__construct();
        $this->tagModel = new Tag();
    }

    /**
     * Liste tous les tags de l'utilisateur
     */
    public function index(): void
    {
        $orderBy = $_GET['order_by'] ?? 'name';
        $tags = $this->tagModel->getAllByUser($this->userId, $orderBy);
        $stats = $this->tagModel->getStats($this->userId);

        require __DIR__ . '/../Views/tags/index.php';
    }

    /**
     * Afficher les détails d'un tag et ses transactions
     */
    public function show(int $id): void
    {
        $tag = $this->tagModel->findById($id, $this->userId);

        if (!$tag) {
            Session::setFlash('error', 'Tag introuvable');
            header('Location: ' . url('tags'));
            exit;
        }

        // Récupérer les transactions associées à ce tag
        $db = \MonBudget\Core\Database::getConnection();
        $sql = "SELECT 
                    t.id,
                    t.compte_id,
                    t.date_transaction,
                    t.libelle,
                    t.montant,
                    t.type_operation,
                    c.nom as compte_nom,
                    cat.nom as categorie_nom,
                    cat.couleur as categorie_couleur,
                    cat.icone as categorie_icone
                FROM transactions t
                INNER JOIN transaction_tags tt ON t.id = tt.transaction_id
                INNER JOIN comptes c ON t.compte_id = c.id
                LEFT JOIN categories cat ON t.categorie_id = cat.id
                WHERE tt.tag_id = ? AND t.user_id = ?
                ORDER BY t.date_transaction DESC, t.created_at DESC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$id, $this->userId]);
        $transactions = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Calculer les statistiques
        $totalDebits = 0;
        $totalCredits = 0;
        foreach ($transactions as $trans) {
            if ($trans['type_operation'] === 'debit') {
                $totalDebits += (float) $trans['montant'];
            } else {
                $totalCredits += (float) $trans['montant'];
            }
        }
        $balance = $totalCredits - $totalDebits;

        $stats = [
            'nb_transactions' => count($transactions),
            'total_debits' => $totalDebits,
            'total_credits' => $totalCredits,
            'balance' => $balance
        ];

        require __DIR__ . '/../Views/tags/show.php';
    }

    /**
     * Formulaire de création d'un tag
     */
    public function create(): void
    {
        $colors = Tag::COLORS;
        require __DIR__ . '/../Views/tags/create.php';
    }

    /**
     * Traiter la création d'un tag
     */
    public function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('tags'));
            exit;
        }

        $name = $_POST['name'] ?? '';
        $color = $_POST['color'] ?? 'secondary';

        // Validation
        $errors = $this->tagModel->validate($name, $color);
        
        if ($this->tagModel->existsByName($this->userId, $name)) {
            $errors['name'] = 'Un tag avec ce nom existe déjà';
        }

        if (!empty($errors)) {
            Session::setFlash('error', 'Erreurs de validation');
            Session::set('errors', $errors);
            Session::set('old', $_POST);
            header('Location: ' . url('tags/create'));
            exit;
        }

        // Création
        $tagId = $this->tagModel->create($this->userId, $name, $color);

        if ($tagId) {
            Session::setFlash('success', "Tag \"{$name}\" créé avec succès");
            header('Location: ' . url('tags'));
        } else {
            Session::setFlash('error', 'Erreur lors de la création du tag');
            header('Location: ' . url('tags/create'));
        }
        exit;
    }

    /**
     * Formulaire d'édition d'un tag
     */
    public function edit(int $id): void
    {
        $tag = $this->tagModel->findById($id, $this->userId);

        if (!$tag) {
            Session::setFlash('error', 'Tag introuvable');
            header('Location: ' . url('tags'));
            exit;
        }

        $colors = Tag::COLORS;
        require __DIR__ . '/../Views/tags/edit.php';
    }

    /**
     * Traiter la mise à jour d'un tag
     */
    public function update(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('tags'));
            exit;
        }

        $tag = $this->tagModel->findById($id, $this->userId);

        if (!$tag) {
            Session::setFlash('error', 'Tag introuvable');
            header('Location: ' . url('tags'));
            exit;
        }

        $name = $_POST['name'] ?? '';
        $color = $_POST['color'] ?? 'secondary';

        // Validation
        $errors = $this->tagModel->validate($name, $color);
        
        if ($this->tagModel->existsByName($this->userId, $name, $id)) {
            $errors['name'] = 'Un tag avec ce nom existe déjà';
        }

        if (!empty($errors)) {
            Session::setFlash('error', 'Erreurs de validation');
            Session::set('errors', $errors);
            Session::set('old', $_POST);
            header('Location: ' . url("tags/{$id}/edit"));
            exit;
        }

        // Mise à jour
        if ($this->tagModel->update($id, $this->userId, $name, $color)) {
            Session::setFlash('success', "Tag \"{$name}\" modifié avec succès");
            header('Location: ' . url('tags'));
        } else {
            Session::setFlash('error', 'Erreur lors de la modification du tag');
            header('Location: ' . url("tags/{$id}/edit"));
        }
        exit;
    }

    /**
     * Supprimer un tag
     */
    public function destroy(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('tags'));
            exit;
        }

        $tag = $this->tagModel->findById($id, $this->userId);

        if (!$tag) {
            Session::setFlash('error', 'Tag introuvable');
            header('Location: ' . url('tags'));
            exit;
        }

        if ($this->tagModel->delete($id, $this->userId)) {
            Session::setFlash('success', "Tag \"{$tag['name']}\" supprimé avec succès");
        } else {
            Session::setFlash('error', 'Erreur lors de la suppression du tag');
        }

        header('Location: ' . url('tags'));
        exit;
    }

    /**
     * API - Autocomplete pour recherche de tags
     * Retourne JSON
     */
    public function autocomplete(): void
    {
        header('Content-Type: application/json');

        $search = $_GET['q'] ?? '';
        $limit = (int) ($_GET['limit'] ?? 10);

        if (empty($search)) {
            echo json_encode([]);
            exit;
        }

        $tags = $this->tagModel->search($this->userId, $search, $limit);

        // Formater pour autocomplete
        $results = array_map(function($tag) {
            return [
                'id' => (int) $tag['id'],
                'name' => $tag['name'],
                'color' => $tag['color'],
                'usage_count' => (int) $tag['usage_count']
            ];
        }, $tags);

        echo json_encode($results);
        exit;
    }

    /**
     * API - Créer un tag rapidement (pour modal AJAX)
     * Retourne JSON
     */
    public function quickCreate(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Méthode non autorisée']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $name = $data['name'] ?? '';
        $color = $data['color'] ?? 'secondary';

        // Validation
        $errors = $this->tagModel->validate($name, $color);
        
        if ($this->tagModel->existsByName($this->userId, $name)) {
            $errors['name'] = 'Un tag avec ce nom existe déjà';
        }

        if (!empty($errors)) {
            http_response_code(400);
            echo json_encode(['errors' => $errors]);
            exit;
        }

        // Création
        $tagId = $this->tagModel->create($this->userId, $name, $color);

        if ($tagId) {
            $tag = $this->tagModel->findById($tagId, $this->userId);
            echo json_encode([
                'success' => true,
                'tag' => [
                    'id' => (int) $tag['id'],
                    'name' => $tag['name'],
                    'color' => $tag['color']
                ]
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Erreur lors de la création']);
        }
        exit;
    }

    /**
     * API - Récupérer tous les tags de l'utilisateur (pour select)
     * Retourne JSON
     */
    public function getAllTags(): void
    {
        header('Content-Type: application/json');

        $tags = $this->tagModel->getAllByUser($this->userId, 'name');

        $results = array_map(function($tag) {
            return [
                'id' => (int) $tag['id'],
                'name' => $tag['name'],
                'color' => $tag['color'],
                'usage_count' => (int) $tag['usage_count']
            ];
        }, $tags);

        echo json_encode($results);
        exit;
    }
}
