<?php

namespace MonBudget\Controllers;

use MonBudget\Core\Environment;
use MonBudget\Core\Installer;
use MonBudget\Core\Database;

/**
 * Contrôleur d'installation de l'application
 * 
 * Gère le processus d'installation guidée en plusieurs étapes :
 * vérification des prérequis, configuration de la base de données,
 * création du super-administrateur, et configuration initiale.
 * 
 * @package MonBudget\Controllers
 * @author MonBudget
 * @version 1.0.0
 */
class SetupController extends BaseController
{
    /**
     * Instance de l'installeur
     * 
     * @var Installer
     */
    private Installer $installer;
    
    /**
     * Constructeur - Initialise l'installeur et vérifie l'état
     * 
     * Redirige vers la page d'accueil si l'application est déjà installée,
     * sauf si le paramètre force est présent.
     */
    public function __construct()
    {
        $this->installer = new Installer();
        
        // Rediriger si déjà installé
        if (Installer::isInstalled() && !isset($_GET['force'])) {
            $this->redirect('/');
        }
    }
    
    /**
     * Afficher la page de bienvenue de l'installation
     * 
     * Première étape du processus d'installation.
     * 
     * @return void
     */
    public function welcome(): void
    {
        $requirements = Environment::checkRequirements();
        $permissions = Environment::checkPermissions();
        $allRequirementsMet = Environment::checkAllRequirements();
        
        $this->view('setup.welcome', [
            'requirements' => $requirements,
            'permissions' => $permissions,
            'allRequirementsMet' => $allRequirementsMet
        ]);
    }
    
    /**
     * Configuration de la base de données
     */
    public function database(): void
    {
        $defaultConfig = Environment::getDefaultConfig();
        
        $this->view('setup.database', [
            'defaultConfig' => $defaultConfig
        ]);
    }
    
    /**
     * Tester la connexion à la base de données
     */
    public function testDatabase(): void
    {
        $config = [
            'driver' => $_POST['db_driver'] ?? 'mysql',
            'host' => $_POST['db_host'] ?? 'localhost',
            'port' => $_POST['db_port'] ?? '3306',
            'database' => $_POST['db_name'] ?? 'monbudget_v2',
            'username' => $_POST['db_username'] ?? 'root',
            'password' => $_POST['db_password'] ?? '',
            'charset' => 'utf8mb4'
        ];
        
        if ($this->installer->testDatabaseConnection($config)) {
            $this->json([
                'success' => true,
                'message' => 'Connexion réussie à la base de données'
            ]);
        } else {
            $this->json([
                'success' => false,
                'message' => 'Échec de la connexion',
                'errors' => $this->installer->getErrors()
            ], 400);
        }
    }
    
    /**
     * Créer et importer la base de données
     */
    public function installDatabase(): void
    {
        try {
            $config = [
                'driver' => $_POST['db_driver'] ?? 'mysql',
                'host' => $_POST['db_host'] ?? 'localhost',
                'port' => $_POST['db_port'] ?? '3306',
                'database' => $_POST['db_name'] ?? 'monbudget_v2',
                'username' => $_POST['db_username'] ?? 'root',
                'password' => $_POST['db_password'] ?? '',
                'charset' => 'utf8mb4'
            ];
            
            // Créer les dossiers nécessaires
            if (!$this->installer->createDirectories()) {
                $this->json([
                    'success' => false,
                    'message' => 'Échec de la création des dossiers',
                    'errors' => $this->installer->getErrors()
                ], 500);
                return;
            }
            
            // Créer la base de données
            if (!$this->installer->createDatabase($config)) {
                $this->json([
                    'success' => false,
                    'message' => 'Échec de la création de la base de données',
                    'errors' => $this->installer->getErrors()
                ], 500);
                return;
            }
            
            // Importer le fichier SQL
            $sqlFile = dirname(__DIR__, 2) . '/database.sql';
            if (file_exists($sqlFile)) {
                if (!$this->installer->importSQLFile($config, $sqlFile)) {
                    $this->json([
                        'success' => false,
                        'message' => 'Échec de l\'importation du fichier SQL',
                        'errors' => $this->installer->getErrors()
                    ], 500);
                    return;
                }
            } else {
                $this->json([
                    'success' => false,
                    'message' => 'Fichier SQL introuvable: ' . $sqlFile,
                    'errors' => ['Le fichier database.sql n\'existe pas']
                ], 500);
                return;
            }
            
            // Sauvegarder la configuration
            if (!$this->installer->saveConfiguration($config)) {
                $this->json([
                    'success' => false,
                    'message' => 'Échec de la sauvegarde de la configuration'
                ], 500);
                return;
            }
            
            // Sauvegarder dans la session pour l'étape suivante
            $_SESSION['db_config'] = $config;
            
            $this->json([
                'success' => true,
                'message' => 'Base de données installée avec succès',
                'steps' => $this->installer->getSteps()
            ]);
        } catch (\Exception $e) {
            error_log("Installation error: " . $e->getMessage());
            $this->json([
                'success' => false,
                'message' => 'Erreur lors de l\'installation',
                'errors' => [$e->getMessage()]
            ], 500);
        }
    }
    
    /**
     * Création de l'administrateur
     */
    public function admin(): void
    {
        if (!isset($_SESSION['db_config'])) {
            $this->redirect('/setup/database');
        }
        
        $this->view('setup.admin');
    }
    
    /**
     * Créer l'utilisateur administrateur
     */
    public function createAdmin(): void
    {
        if (!isset($_SESSION['db_config'])) {
            $this->json([
                'success' => false,
                'message' => 'Configuration de la base de données manquante'
            ], 400);
            return;
        }
        
        $data = $this->validate([
            'username' => 'required|min:3|max:50',
            'email' => 'required|email',
            'password' => 'required|min:8',
            'password_confirm' => 'required'
        ]);
        
        if ($data['password'] !== $data['password_confirm']) {
            $this->json([
                'success' => false,
                'message' => 'Les mots de passe ne correspondent pas'
            ], 400);
            return;
        }
        
        $config = $_SESSION['db_config'];
        Database::configure($config);
        
        if (!$this->installer->createAdminUser($config, $data)) {
            $this->json([
                'success' => false,
                'message' => 'Échec de la création de l\'administrateur',
                'errors' => $this->installer->getErrors()
            ], 500);
            return;
        }
        
        // Sauvegarder les identifiants admin pour affichage final
        $_SESSION['admin_credentials'] = [
            'username' => $data['username'],
            'email' => $data['email']
        ];
        
        $this->json([
            'success' => true,
            'message' => 'Administrateur créé avec succès'
        ]);
    }
    
    /**
     * Page choix données d'exemple
     */
    public function sampleData(): void
    {
        if (!isset($_SESSION['db_config'])) {
            $this->redirect('/setup/database');
        }
        
        $this->view('setup.sample-data');
    }
    
    /**
     * Charger les données d'exemple (ou pas)
     */
    public function loadSampleData(): void
    {
        if (!isset($_SESSION['db_config'])) {
            $this->json([
                'success' => false,
                'message' => 'Configuration de la base de données manquante'
            ], 400);
            return;
        }
        
        $loadData = $_POST['load_sample_data'] ?? 'no';
        
        if ($loadData === 'yes') {
            // Charger les données d'exemple
            $config = $_SESSION['db_config'];
            Database::configure($config);
            
            // Récupérer l'ID du dernier utilisateur créé (admin principal)
            $db = Database::getConnection();
            $stmt = $db->query("SELECT id FROM users WHERE username != 'UserFirst' ORDER BY id DESC LIMIT 1");
            $user = $stmt->fetch();
            
            if (!$user) {
                flash('error', 'Aucun utilisateur admin trouvé');
                $this->redirect('/setup/complete');
                return;
            }
            
            if (!$this->installer->loadSampleData($config, $user['id'])) {
                flash('warning', 'Les données d\'exemple n\'ont pas pu être chargées, mais l\'installation est terminée.');
            } else {
                $_SESSION['sample_data_loaded'] = true;
            }
        }
        
        $this->redirect('/setup/complete');
    }
    
    /**
     * Finalisation de l'installation
     */
    public function complete(): void
    {
        if (!isset($_SESSION['db_config'])) {
            $this->redirect('/setup/welcome');
        }
        
        // Marquer comme installé
        Installer::markAsInstalled();
        
        // Nettoyer la session
        unset($_SESSION['db_config']);
        
        $this->view('setup.complete');
    }
}
