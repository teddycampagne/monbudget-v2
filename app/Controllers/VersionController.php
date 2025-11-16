<?php

namespace MonBudget\Controllers;

use MonBudget\Core\Controller;
use MonBudget\Services\VersionChecker;

/**
 * Contrôleur de gestion des versions
 * 
 * @version 2.2.0
 */
class VersionController extends Controller
{
    private VersionChecker $versionChecker;
    
    public function __construct()
    {
        // Ne pas appeler parent::__construct() pour éviter la vérification d'auth
        // car on veut permettre l'accès à certaines routes sans authentification
        $this->versionChecker = new VersionChecker();
    }
    
    /**
     * Vérifier si l'utilisateur est authentifié
     */
    private function requireAuth(): void
    {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Authentification requise'
            ]);
            exit;
        }
    }
    
    /**
     * API: Vérifier les mises à jour disponibles
     */
    public function checkUpdate(): void
    {
        $this->requireAuth();
        header('Content-Type: application/json');
        
        try {
            $update = $this->versionChecker->checkForUpdates();
            
            echo json_encode([
                'success' => true,
                'update_available' => $update !== null,
                'update' => $update,
                'current_version' => $this->versionChecker->getVersionInfo()
            ]);
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors de la vérification: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * API: Déployer une version
     */
    public function deploy(): void
    {
        $this->requireAuth();
        header('Content-Type: application/json');
        
        // Vérifier que l'utilisateur est admin
        if (!$this->isAdmin()) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Accès refusé. Seuls les administrateurs peuvent déployer des mises à jour.'
            ]);
            return;
        }
        
        try {
            $version = $_POST['version'] ?? '';
            
            if (empty($version)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Version non spécifiée'
                ]);
                return;
            }
            
            // Déployer
            $result = $this->versionChecker->deployVersion($version);
            
            if (!$result['success']) {
                http_response_code(500);
            }
            
            echo json_encode($result);
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors du déploiement: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * API: Rollback vers une version précédente
     */
    public function rollback(): void
    {
        $this->requireAuth();
        header('Content-Type: application/json');
        
        // Vérifier que l'utilisateur est admin
        if (!$this->isAdmin()) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Accès refusé'
            ]);
            return;
        }
        
        try {
            $commit = $_POST['commit'] ?? '';
            
            if (empty($commit)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Commit non spécifié'
                ]);
                return;
            }
            
            $result = $this->versionChecker->rollback($commit);
            
            if (!$result['success']) {
                http_response_code(500);
            }
            
            echo json_encode($result);
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors du rollback: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * API: Informations de version
     */
    public function info(): void
    {
        header('Content-Type: application/json');
        
        try {
            $info = $this->versionChecker->getVersionInfo();
            
            echo json_encode([
                'success' => true,
                'info' => $info
            ]);
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Vérifier si l'utilisateur est admin
     */
    private function isAdmin(): bool
    {
        // Pour l'instant, on vérifie juste si l'utilisateur est connecté
        return isset($_SESSION['user_id']);
    }
}
