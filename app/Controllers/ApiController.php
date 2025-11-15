<?php

namespace MonBudget\Controllers;

use MonBudget\Core\Database;
use MonBudget\Models\Categorie;
use MonBudget\Models\Tiers;

/**
 * Contrôleur API pour création rapide
 */
class ApiController extends BaseController
{
    /**
     * Créer une catégorie rapidement (AJAX)
     */
    public function createCategorie(): void
    {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Méthode non autorisée']);
            exit;
        }
        
        $this->requireAuth();
        
        try {
            // Récupérer les données JSON
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['nom']) || !isset($input['type'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Données manquantes']);
                exit;
            }
            
            $db = Database::getConnection();
            $categorieModel = new Categorie($db);
            
            // Créer la catégorie
            $data = [
                'nom' => trim($input['nom']),
                'type' => $input['type'],
                'couleur' => $input['couleur'] ?? '#0d6efd',
                'user_id' => $_SESSION['user']['id']
            ];
            
            $id = $categorieModel->create($data);
            
            if ($id) {
                $categorie = $categorieModel->getById($id);
                http_response_code(201);
                echo json_encode($categorie);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Erreur lors de la création']);
            }
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        
        exit;
    }
    
    /**
     * Créer un tiers rapidement (AJAX)
     */
    public function createTiers(): void
    {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Méthode non autorisée']);
            exit;
        }
        
        $this->requireAuth();
        
        try {
            // Récupérer les données JSON
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['nom']) || !isset($input['type'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Données manquantes']);
                exit;
            }
            
            $db = Database::getConnection();
            $tiersModel = new Tiers($db);
            
            // Créer le tiers
            $data = [
                'nom' => trim($input['nom']),
                'type' => $input['type'],
                'groupe' => $input['groupe'] ?? null,
                'user_id' => $_SESSION['user']['id']
            ];
            
            $id = $tiersModel->create($data);
            
            if ($id) {
                $tiers = $tiersModel->find($id);
                http_response_code(201);
                echo json_encode($tiers);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Erreur lors de la création']);
            }
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        
        exit;
    }
    
    /**
     * Retourner la liste des icônes Bootstrap disponibles
     */
    public function getBootstrapIcons(): void
    {
        header('Content-Type: application/json');
        
        $configFile = __DIR__ . '/../../storage/config/bootstrap-icons.json';
        
        if (file_exists($configFile)) {
            echo file_get_contents($configFile);
        } else {
            // Fallback
            echo json_encode(['bi-tag', 'bi-star', 'bi-heart', 'bi-cash-coin']);
        }
        
        exit;
    }
}
