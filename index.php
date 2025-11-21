<?php

/**
 * Point d'entrée de l'application MonBudget v2.0
 */

// Démarrage de la session
session_start();

// Définir le chemin de base
define('BASE_PATH', __DIR__);

// Charger l'autoloader de Composer
if (file_exists(BASE_PATH . '/vendor/autoload.php')) {
    require_once BASE_PATH . '/vendor/autoload.php';
}

// Détecter le base URL (pour fonctionner en sous-dossier)
$scriptName = dirname($_SERVER['SCRIPT_NAME']);
define('BASE_URL', $scriptName === '/' ? '' : $scriptName);

// Autoloader simple (en attendant Composer)
spl_autoload_register(function ($class) {
    // Convertir namespace en chemin
    $prefix = 'MonBudget\\';
    $baseDir = BASE_PATH . '/app/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// Charger les helpers
require_once BASE_PATH . '/app/Core/helpers.php';
require_once BASE_PATH . '/app/Core/ui_helpers.php';

// Charger les variables d'environnement depuis .env
use MonBudget\Core\Environment;
Environment::loadEnv();

// Gestion des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

use MonBudget\Core\Router;
use MonBudget\Core\Installer;
use MonBudget\Core\Database;
use MonBudget\Controllers\SetupController;
use MonBudget\Controllers\AuthController;
use MonBudget\Controllers\HomeController;

// Vérifier si l'application est installée
$requestUri = $_SERVER['REQUEST_URI'];
$basePath = BASE_URL;
$relativePath = str_replace($basePath, '', parse_url($requestUri, PHP_URL_PATH));

if (!Installer::isInstalled() && !str_starts_with($relativePath, '/setup')) {
    header('Location: ' . BASE_URL . '/setup/welcome');
    exit;
}

// Charger la configuration de la base de données si installé
if (Installer::isInstalled()) {
    $dbConfig = require BASE_PATH . '/config/database.php';
    Database::configure($dbConfig);
}

// Créer le routeur
$router = new Router();

// Routes d'installation
$router->get('/setup/welcome', [SetupController::class, 'welcome']);
$router->get('/setup/database', [SetupController::class, 'database']);
$router->post('/setup/test-database', [SetupController::class, 'testDatabase']);
$router->post('/setup/install-database', [SetupController::class, 'installDatabase']);
$router->get('/setup/admin', [SetupController::class, 'admin']);
$router->post('/setup/create-admin', [SetupController::class, 'createAdmin']);
$router->get('/setup/sample-data', [SetupController::class, 'sampleData']);
$router->post('/setup/load-sample-data', [SetupController::class, 'loadSampleData']);
$router->get('/setup/complete', [SetupController::class, 'complete']);

// Routes d'authentification
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/logout', [AuthController::class, 'logout']);
$router->get('/register', [AuthController::class, 'showRegister']);
$router->post('/register', [AuthController::class, 'register']);
$router->get('/forgot-password', [AuthController::class, 'showForgotPassword']);
$router->post('/forgot-password', [AuthController::class, 'forgotPassword']);
$router->post('/admin-password-request', [AuthController::class, 'adminPasswordRequest']);

// Routes principales
$router->get('/', [HomeController::class, 'index']);
$router->get('/dashboard', [HomeController::class, 'dashboard']);

// Routes Banques
use MonBudget\Controllers\BanqueController;
$router->get('/banques', [BanqueController::class, 'index']);
$router->get('/banques/create', [BanqueController::class, 'create']);
$router->post('/banques/store', [BanqueController::class, 'store']);
$router->get('/banques/{id}', [BanqueController::class, 'show']);
$router->get('/banques/{id}/edit', [BanqueController::class, 'edit']);
$router->post('/banques/{id}/update', [BanqueController::class, 'update']);
$router->post('/banques/{id}/delete', [BanqueController::class, 'destroy']);
$router->get('/banques/search', [BanqueController::class, 'search']);

// Routes Titulaires
use MonBudget\Controllers\TitulaireController;
$router->get('/titulaires', [TitulaireController::class, 'index']);
$router->get('/titulaires/create', [TitulaireController::class, 'create']);
$router->post('/titulaires/store', [TitulaireController::class, 'store']);
$router->get('/titulaires/{id}/edit', [TitulaireController::class, 'edit']);
$router->post('/titulaires/{id}/update', [TitulaireController::class, 'update']);
$router->post('/titulaires/{id}/delete', [TitulaireController::class, 'destroy']);
$router->get('/titulaires/search', [TitulaireController::class, 'search']);

// Routes Comptes
use MonBudget\Controllers\CompteController;
$router->get('/comptes', [CompteController::class, 'index']);
$router->get('/comptes/create', [CompteController::class, 'create']);
$router->post('/comptes/store', [CompteController::class, 'store']);
$router->get('/comptes/{id}/edit', [CompteController::class, 'edit']);
$router->post('/comptes/{id}/update', [CompteController::class, 'update']);
$router->post('/comptes/{id}/delete', [CompteController::class, 'destroy']);
$router->get('/comptes/search', [CompteController::class, 'search']);
$router->get('/comptes/{id}/rib/download', [CompteController::class, 'downloadRib']);
// Routes Transactions (via comptes uniquement)
$router->get('/comptes/{compteId}/transactions', [CompteController::class, 'transactions']);

// Routes Transactions
use MonBudget\Controllers\TransactionController;
$router->get('/comptes/{compteId}/transactions/create', [TransactionController::class, 'create']);
$router->post('/comptes/{compteId}/transactions/store', [TransactionController::class, 'store']);
$router->get('/comptes/{compteId}/transactions/{id}/duplicate', [TransactionController::class, 'duplicate']);
$router->get('/comptes/{compteId}/transactions/{id}/edit', [TransactionController::class, 'edit']);
$router->post('/comptes/{compteId}/transactions/{id}/update', [TransactionController::class, 'update']);
$router->post('/comptes/{compteId}/transactions/{id}/delete', [TransactionController::class, 'delete']);

// Routes Pièces jointes (Session 15 - v2.1.0 Phase 2)
$router->post('/comptes/{compteId}/transactions/{transactionId}/attachments/upload', [TransactionController::class, 'uploadAttachment']);
$router->delete('/comptes/{compteId}/transactions/{transactionId}/attachments/{attachmentId}', [TransactionController::class, 'deleteAttachment']);
$router->get('/comptes/{compteId}/transactions/{transactionId}/attachments/{attachmentId}/download', [TransactionController::class, 'downloadAttachment']);

// Routes Récurrences (Session 11 - Table séparée)
use MonBudget\Controllers\RecurrenceController;
$router->get('/recurrences', [RecurrenceController::class, 'index']); // Toutes les récurrences
$router->get('/recurrences/admin', [RecurrenceController::class, 'admin']); // Administration (stats)
$router->get('/comptes/{compteId}/recurrences', [RecurrenceController::class, 'index']); // Récurrences d'un compte
$router->get('/comptes/{compteId}/transactions/recurrentes', [RecurrenceController::class, 'index']); // Alias pour compatibilité
$router->get('/comptes/{compteId}/recurrences/create', [RecurrenceController::class, 'create']);
$router->post('/recurrences/store', [RecurrenceController::class, 'store']);
$router->get('/recurrences/{id}/edit', [RecurrenceController::class, 'edit']);
$router->post('/recurrences/{id}/update', [RecurrenceController::class, 'update']);
$router->post('/recurrences/{id}/delete', [RecurrenceController::class, 'destroy']);
$router->post('/recurrences/{id}/execute', [RecurrenceController::class, 'execute']); // Exécution manuelle
// API Récurrences
$router->get('/api/recurrences/{id}/count-occurrences', [RecurrenceController::class, 'apiCountOccurrences']);

// Routes Catégories
use MonBudget\Controllers\CategorieController;
$router->get('/categories', [CategorieController::class, 'index']);
$router->get('/categories/create', [CategorieController::class, 'create']);
$router->post('/categories/store', [CategorieController::class, 'store']);
// Routes sous-catégories (AVANT les routes génériques pour éviter les conflits)
$router->get('/categories/sous/{id}/edit', [CategorieController::class, 'editSous']);
$router->post('/categories/sous/{id}/update', [CategorieController::class, 'updateSous']);
$router->post('/categories/sous/{id}/delete', [CategorieController::class, 'destroySous']);
// Routes catégories génériques
$router->get('/categories/{id}/edit', [CategorieController::class, 'edit']);
$router->post('/categories/{id}/update', [CategorieController::class, 'update']);
$router->post('/categories/{id}/delete', [CategorieController::class, 'destroy']);

// API - Sous-catégories (pour AJAX)
$router->get('/api/categories/{id}/sous-categories', [CategorieController::class, 'apiGetSousCategories']);

// Routes Tags
use MonBudget\Controllers\TagController;
$router->get('/tags', [TagController::class, 'index']);
$router->get('/tags/create', [TagController::class, 'create']);
$router->get('/tags/{id}', [TagController::class, 'show']);
$router->post('/tags/store', [TagController::class, 'store']);
$router->get('/tags/{id}/edit', [TagController::class, 'edit']);
$router->post('/tags/{id}/update', [TagController::class, 'update']);
$router->post('/tags/{id}/delete', [TagController::class, 'destroy']);
// API Tags (pour AJAX)
$router->get('/api/tags/autocomplete', [TagController::class, 'autocomplete']);
$router->post('/api/tags/quick-create', [TagController::class, 'quickCreate']);
$router->get('/api/tags/all', [TagController::class, 'getAllTags']);

// Routes Tiers
use MonBudget\Controllers\TiersController;
$router->get('/tiers', [TiersController::class, 'index']);
$router->get('/tiers/create', [TiersController::class, 'create']);
$router->post('/tiers/store', [TiersController::class, 'store']);
$router->get('/tiers/{id}/edit', [TiersController::class, 'edit']);
$router->post('/tiers/{id}/update', [TiersController::class, 'update']);
$router->post('/tiers/{id}/delete', [TiersController::class, 'destroy']);

// Routes Utilisateur
use MonBudget\Controllers\UserController;
use MonBudget\Controllers\ProfileController;
$router->get('/profile', [ProfileController::class, 'show']);
$router->post('/profile', [ProfileController::class, 'update']);
$router->get('/change-password', [ProfileController::class, 'showChangePassword']);
$router->post('/change-password', [ProfileController::class, 'changePassword']);
// Routes legacy UserController (à migrer vers ProfileController)
$router->get('/profile/legacy', [UserController::class, 'profile']);
$router->post('/profile/update', [UserController::class, 'updateProfile']);
$router->post('/profile/password', [UserController::class, 'updatePassword']);
$router->post('/profile/preferences', [UserController::class, 'updatePreferences']);

// Routes Automatisation
use MonBudget\Controllers\AutomatisationController;
$router->get('/automatisation', [AutomatisationController::class, 'index']);
$router->get('/automatisation/create', [AutomatisationController::class, 'create']);
$router->post('/automatisation/store', [AutomatisationController::class, 'store']);
$router->get('/automatisation/{id}/edit', [AutomatisationController::class, 'edit']);
$router->post('/automatisation/{id}/update', [AutomatisationController::class, 'update']);
$router->post('/automatisation/{id}/delete', [AutomatisationController::class, 'destroy']);
$router->post('/automatisation/test', [AutomatisationController::class, 'test']);
$router->post('/automatisation/apply-all', [AutomatisationController::class, 'applyToAll']);
$router->post('/automatisation/{id}/toggle', [AutomatisationController::class, 'toggle']);

// Routes Budgets
use MonBudget\Controllers\BudgetController;
$router->get('/budgets', [BudgetController::class, 'index']);
$router->get('/budgets/create', [BudgetController::class, 'create']);
$router->post('/budgets/store', [BudgetController::class, 'store']);
$router->get('/budgets/generate', [BudgetController::class, 'generate']);
$router->post('/budgets/preview', [BudgetController::class, 'preview']);
$router->post('/budgets/create-from-projection', [BudgetController::class, 'createFromProjection']);
$router->get('/budgets/{id}/edit', [BudgetController::class, 'edit']);
$router->post('/budgets/{id}/update', [BudgetController::class, 'update']);
$router->get('/budgets/{id}/delete', [BudgetController::class, 'delete']);
$router->get('/budgets/delete-annual', [BudgetController::class, 'deleteAnnual']);

// Routes Rapports
use MonBudget\Controllers\RapportController;
$router->get('/rapports', [RapportController::class, 'index']);
$router->get('/rapports/graphiques', [RapportController::class, 'graphiques']);
$router->get('/rapports/releve', [RapportController::class, 'releve']);
// API Rapports
$router->get('/api/rapports/evolution-solde', [RapportController::class, 'apiEvolutionSolde']);
$router->get('/api/rapports/repartition-categories', [RapportController::class, 'apiRepartitionCategories']);
$router->get('/api/rapports/detail-categorie', [RapportController::class, 'apiDetailCategorie']);
$router->get('/api/rapports/balances', [RapportController::class, 'apiBalances']);
$router->get('/api/rapports/budgetaire', [RapportController::class, 'apiBudgetaire']);
$router->get('/api/rapports/tendance-epargne', [RapportController::class, 'apiTendanceEpargne']);
$router->get('/api/rapports/tags', [RapportController::class, 'apiRapportTags']);

// Routes Projections
use MonBudget\Controllers\ProjectionController;
$router->get('/projections', [ProjectionController::class, 'index']);
$router->get('/projections/export-pdf', [ProjectionController::class, 'exportPdf']);

// Routes Recherche
use MonBudget\Controllers\RechercheController;
$router->get('/recherche', [RechercheController::class, 'index']);
// API Recherche
$router->get('/api/recherche', [RechercheController::class, 'apiRecherche']);
$router->get('/api/recherche/export', [RechercheController::class, 'apiExport']);
$router->get('/api/recherche/sous-categories', [RechercheController::class, 'apiSousCategories']);

// Imports
use MonBudget\Controllers\ImportController;
$router->get('/imports', [ImportController::class, 'index']);
$router->get('/imports/upload', [ImportController::class, 'upload']);
$router->post('/imports/preview', [ImportController::class, 'preview']);
$router->post('/imports/process', [ImportController::class, 'process']);
$router->get('/imports/cancel', [ImportController::class, 'cancel']);

// Routes Administration (Super-Admin seulement)
use MonBudget\Controllers\AdminController;
$router->get('/admin', [AdminController::class, 'index']);
$router->post('/admin/reset-database', [AdminController::class, 'resetDatabase']);

// Routes Gestion Utilisateurs (Admin + UserFirst)
$router->get('/admin/users', [AdminController::class, 'users']);
$router->get('/admin/users/create', [AdminController::class, 'createUser']);
$router->post('/admin/users/store', [AdminController::class, 'storeUser']);
$router->get('/admin/users/{id}/edit', [AdminController::class, 'editUser']);
$router->post('/admin/users/{id}/update', [AdminController::class, 'updateUser']);
$router->post('/admin/users/{id}/delete', [AdminController::class, 'deleteUser']);
$router->get('/admin/users/roles', [AdminController::class, 'manageRoles']);
$router->post('/admin/users/roles/update', [AdminController::class, 'updateRoles']);

// Routes Maintenance BDD (Admin + UserFirst)
$router->post('/admin/maintenance/recalcul-soldes', [AdminController::class, 'recalculSoldes']);
$router->post('/admin/maintenance/clean-logs', [AdminController::class, 'cleanLogs']);
$router->post('/admin/maintenance/clean-sessions', [AdminController::class, 'cleanSessions']);
$router->post('/admin/maintenance/optimize-db', [AdminController::class, 'optimizeDatabase']);
$router->post('/admin/maintenance/apply-indexes', [AdminController::class, 'applyPerformanceIndexes']);

// Routes Backup & Restore (Admin + UserFirst)
$router->get('/admin/backup', [AdminController::class, 'backup']);
$router->get('/admin/restore', [AdminController::class, 'restorePage']);
$router->post('/admin/restore/upload', [AdminController::class, 'restoreUpload']);

// Routes Sécurité (Admin + UserFirst)
$router->get('/admin/users/reset-passwords', [AdminController::class, 'resetPasswords']);
$router->post('/admin/users/reset-passwords/process', [AdminController::class, 'processResetPasswords']);
$router->get('/admin/locked-users', [AdminController::class, 'lockedUsers']);
$router->post('/admin/users/unlock', [AdminController::class, 'unlockUser']);
$router->post('/admin/users/{id}/reset-password', [AdminController::class, 'resetUserPassword']);

// Routes Gestion des Icônes (Admin)
$router->get('/admin/icons', [AdminController::class, 'icons']);
$router->post('/admin/icons/add', [AdminController::class, 'addIcon']);
$router->post('/admin/icons/delete', [AdminController::class, 'deleteIcon']);

// Routes API (création rapide)
use MonBudget\Controllers\ApiController;
$router->post('/api/categories', [ApiController::class, 'createCategorie']);
$router->post('/api/tiers', [ApiController::class, 'createTiers']);
$router->get('/api/bootstrap-icons', [ApiController::class, 'getBootstrapIcons']);

// Routes Version Manager
use MonBudget\Controllers\VersionController;
$router->get('/version/check-update', [VersionController::class, 'checkUpdate']);
$router->post('/version/deploy', [VersionController::class, 'deploy']);
$router->post('/version/rollback', [VersionController::class, 'rollback']);
$router->get('/version/info', [VersionController::class, 'info']);

// Routes Documentation
use MonBudget\Controllers\DocumentationController;
$router->get('/documentation', [DocumentationController::class, 'index']);
$router->get('/documentation/search', [DocumentationController::class, 'search']);
$router->get('/documentation/help/{context}', [DocumentationController::class, 'contextHelp']);
$router->get('/documentation/{document}', [DocumentationController::class, 'show']);
$router->get('/documentation/{document}/pdf', [DocumentationController::class, 'downloadPdf']);
$router->post('/documentation/feedback', [DocumentationController::class, 'feedback']);

// === Route gestion demandes d'aide admin (tickets) ===
$router->post('/admin/admin-requests/{id}/close', function ($id) {
    if (!csrf_check()) {
        http_response_code(403);
        exit('CSRF invalide');
    }
    $pdo = MonBudget\Core\Database::getConnection();
    $stmt = $pdo->prepare('UPDATE admin_password_requests SET status = :status, processed_at = NOW() WHERE id = :id');
    $stmt->execute([
        'status' => 'approved',
        'id' => $id
    ]);
    header('Location: ' . url('admin/admin-requests'));
    exit;
});
$router->get('/admin/admin-requests', function () {
    require __DIR__ . '/app/Views/admin/admin_requests.php';
});

// Dispatcher la requête
// Debug temporaire : afficher l'URI vue par le Router
if (isset($_GET['debug_router_uri'])) {
    $reflection = new ReflectionClass($router);
    $method = $reflection->getMethod('getUri');
    $method->setAccessible(true);
    $uri = $method->invoke($router);
    echo '<div style="background:#222;color:#fff;padding:10px;">Router URI: <b>' . htmlspecialchars($uri) . '</b></div>';
}

try {
    $router->dispatch();
} catch (Exception $e) {
    // Gestion des erreurs
    http_response_code(500);
    
    if (class_exists('MonBudget\\Core\\Environment') && MonBudget\Core\Environment::isDevelopment()) {
        echo '<h1>Erreur</h1>';
        echo '<p>' . $e->getMessage() . '</p>';
        echo '<pre>' . $e->getTraceAsString() . '</pre>';
    } else {
        echo '<h1>Une erreur est survenue</h1>';
        echo '<p>Veuillez réessayer plus tard.</p>';
    }
}
