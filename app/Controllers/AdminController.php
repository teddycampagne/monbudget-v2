<?php

namespace MonBudget\Controllers;

use MonBudget\Core\Database;
use MonBudget\Services\PasswordPolicyService;
use MonBudget\Services\AuditLogService;

/**
 * Contrôleur d'administration
 * 
 * Gère toutes les fonctionnalités d'administration réservées aux administrateurs
 * et super-administrateurs : gestion des utilisateurs, configuration système,
 * statistiques globales, maintenance, logs, etc.
 * 
 * @package MonBudget\Controllers
 * @author MonBudget
 * @version 1.0.0
 */
class AdminController extends BaseController
{
    /**
     * Page principale du panneau d'administration
     * 
     * Affiche le tableau de bord admin avec les statistiques globales
     * et les liens vers les différentes sections d'administration.
     * 
     * @return void
     */
    public function index()
    {
        if (!$this->requireAdminAccess()) return;
        
        // Récupérer les statistiques générales
        $stats = $this->getGlobalStats();
        
        $this->view('admin.index', [
            'user' => $_SESSION['user'],
            'stats' => $stats
        ]);
    }
    
    /**
     * Réinitialisation complète de la base de données
     */
    public function resetDatabase()
    {
        if (!$this->requireSuperAdminAccess()) return;
        
        // Vérification CSRF
        $this->verifyCsrf();
        
        // Vérification du code de confirmation
        $confirmCode = $_POST['confirm_code'] ?? '';
        if ($confirmCode !== 'RESET-ALL-DATA') {
            flash('error', 'Code de confirmation incorrect. Opération annulée.');
            header('Location: ' . url('admin'));
            exit;
        }
        
        try {
            $db = Database::getConnection();
            $db->beginTransaction();
            
            // Liste des tables à vider (dans l'ordre pour respecter les contraintes FK)
            $tables = [
                // Tables de liaison et dépendantes
                'compte_titulaires',
                'transactions',
                'imports',
                'import_logs',
                'regles_automatisation',
                'budgets',
                
                // Tables principales (sauf users pour garder UserFirst)
                'comptes',
                'categories',
                'tiers',
                'titulaires',
                'banques',
                'moyens_paiement',
                
                // Logs et cache
                'logs',
                'sessions'
            ];
            
            // Désactiver temporairement les contraintes FK
            $db->exec('SET FOREIGN_KEY_CHECKS = 0');
            
            $deletedCounts = [];
            foreach ($tables as $table) {
                // Vérifier que la table existe
                $exists = $db->query("SHOW TABLES LIKE '$table'")->fetch();
                if ($exists) {
                    $count = $db->query("SELECT COUNT(*) as count FROM $table")->fetch()['count'];
                    $db->exec("TRUNCATE TABLE $table");
                    $deletedCounts[$table] = $count;
                }
            }
            
            // Supprimer tous les utilisateurs SAUF UserFirst
            $stmt = $db->prepare("DELETE FROM users WHERE username != 'UserFirst'");
            $stmt->execute();
            $deletedUsers = $stmt->rowCount();
            
            // Réactiver les contraintes FK
            $db->exec('SET FOREIGN_KEY_CHECKS = 1');
            
            // Réinitialiser les auto-increment
            foreach ($tables as $table) {
                $exists = $db->query("SHOW TABLES LIKE '$table'")->fetch();
                if ($exists) {
                    $db->exec("ALTER TABLE $table AUTO_INCREMENT = 1");
                }
            }
            
            $db->commit();
            
            // Préparer le message de succès
            $totalDeleted = array_sum($deletedCounts) + $deletedUsers;
            $message = "✅ Base de données réinitialisée avec succès !<br>";
            $message .= "📊 Total supprimé : " . number_format($totalDeleted, 0, ',', ' ') . " enregistrements<br><br>";
            $message .= "<strong>Détails :</strong><br>";
            
            if ($deletedUsers > 0) {
                $message .= "👥 Utilisateurs supprimés : $deletedUsers<br>";
            }
            
            foreach ($deletedCounts as $table => $count) {
                if ($count > 0) {
                    $message .= "📋 $table : " . number_format($count, 0, ',', ' ') . "<br>";
                }
            }
            
            flash('success', $message);
            
        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            
            // Réactiver les contraintes FK en cas d'erreur
            try {
                $db->exec('SET FOREIGN_KEY_CHECKS = 1');
            } catch (\Exception $e2) {
                // Ignorer
            }
            
            flash('error', 'Erreur lors de la réinitialisation : ' . $e->getMessage());
        }
        
        header('Location: ' . url('admin'));
        exit;
    }
    
    /**
     * Liste des utilisateurs
     */
    public function users()
    {
        if (!$this->requireAdminAccess()) return;
        
        $db = Database::getConnection();
        
        // Récupérer tous les utilisateurs
        $stmt = $db->query("
            SELECT id, username, email, role, is_active, created_at
            FROM users
            ORDER BY created_at DESC
        ");
        $users = $stmt->fetchAll();
        
        $this->view('admin.users.index', [
            'users' => $users,
            'title' => 'Gestion des utilisateurs'
        ]);
    }
    
    /**
     * Formulaire création utilisateur
     */
    public function createUser()
    {
        if (!$this->requireAdminAccess()) return;
        
        $this->view('admin.users.create', [
            'title' => 'Créer un utilisateur'
        ]);
    }
    
    /**
     * Enregistrer un nouvel utilisateur
     */
    public function storeUser()
    {
        if (!$this->requireAdminAccess()) return;
        if (!$this->validateCsrfOrFail('admin/users')) return;
        
        // Validation
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'user';
        
        if (empty($username) || empty($email) || empty($password)) {
            flash('error', 'Tous les champs sont obligatoires');
            $this->redirect('admin/users/create');
            return;
        }
        
        if (strlen($password) < 8) {
            flash('error', 'Le mot de passe doit contenir au moins 8 caractères');
            $this->redirect('admin/users/create');
            return;
        }
        
        // Vérifier si username ou email existe déjà
        $db = Database::getConnection();
        $check = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $check->execute([$username, $email]);
        
        if ($check->fetch()) {
            flash('error', 'Ce nom d\'utilisateur ou cet email existe déjà');
            $this->redirect('admin/users/create');
            return;
        }
        
        // Créer l'utilisateur
        $hashedPassword = password_hash($password, PASSWORD_ARGON2ID);
        
        $stmt = $db->prepare("
            INSERT INTO users (username, password, email, role, is_active, created_at)
            VALUES (?, ?, ?, ?, 1, NOW())
        ");
        
        if ($stmt->execute([$username, $hashedPassword, $email, $role])) {
            flash('success', 'Utilisateur créé avec succès');
        } else {
            flash('error', 'Erreur lors de la création de l\'utilisateur');
        }
        
        $this->redirect('admin/users');
    }
    
    /**
     * Formulaire édition utilisateur
     */
    public function editUser($id)
    {
        if (!$this->requireAdminAccess()) return;
        
        // Protection UserFirst
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            flash('error', 'Utilisateur introuvable');
            $this->redirect('admin/users');
            return;
        }
        
        if ($user['username'] === 'UserFirst') {
            flash('error', 'Le compte UserFirst ne peut pas être modifié');
            $this->redirect('admin/users');
            return;
        }
        
        $this->view('admin.users.edit', [
            'user' => $user,
            'title' => 'Éditer l\'utilisateur'
        ]);
    }
    
    /**
     * Mettre à jour un utilisateur
     */
    public function updateUser($id)
    {
        if (!$this->requireAdminAccess()) return;
        if (!$this->validateCsrfOrFail('admin/users')) return;
        
        $db = Database::getConnection();
        
        // Vérifier que ce n'est pas UserFirst
        $stmt = $db->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        
        if ($user['username'] === 'UserFirst') {
            flash('error', 'Le compte UserFirst ne peut pas être modifié');
            $this->redirect('admin/users');
            return;
        }
        
        // Récupérer les données
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'user';
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $new_password = $_POST['new_password'] ?? '';
        
        // Construire la requête
        if (!empty($new_password)) {
            // Validation PCI DSS
            $validationErrors = PasswordPolicyService::validate($new_password);
            if (!empty($validationErrors)) {
                foreach ($validationErrors as $error) {
                    flash('error', $error);
                }
                $this->redirect("admin/users/{$id}/edit");
                return;
            }
            
            $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Mise à jour complète avec PCI DSS
            $stmt = $db->prepare("
                UPDATE users 
                SET email = ?, 
                    role = ?, 
                    is_active = ?, 
                    password = ?,
                    last_password_change = NOW(), 
                    password_expires_at = DATE_ADD(NOW(), INTERVAL 90 DAY),
                    must_change_password = 1,
                    failed_login_attempts = 0,
                    locked_until = NULL
                WHERE id = ?
            ");
            $stmt->execute([$email, $role, $is_active, $hashedPassword, $id]);
            
            // Ajouter à l'historique
            PasswordPolicyService::addToHistory($id, $hashedPassword);
            
            // Log audit
            $audit = new AuditLogService();
            $audit->log(
                AuditLogService::ACTION_PASSWORD_RESET,
                'users',
                $id,
                null,
                ['reset_by_admin' => $_SESSION['user_id'], 'username' => $user['username']],
                $_SESSION['user_id']
            );
            
            flash('success', 'Utilisateur mis à jour. Il devra changer son mot de passe à la prochaine connexion.');
        } else {
            $stmt = $db->prepare("
                UPDATE users 
                SET email = ?, role = ?, is_active = ?
                WHERE id = ?
            ");
            $stmt->execute([$email, $role, $is_active, $id]);
            flash('success', 'Utilisateur mis à jour avec succès');
        }
        $this->redirect('admin/users');
    }
    
    /**
     * Supprimer un utilisateur
     */
    public function deleteUser($id)
    {
        if (!$this->requireAdminAccess()) return;
        if (!$this->validateCsrfOrFail('admin/users')) return;
        
        $db = Database::getConnection();
        
        // Vérifier que ce n'est pas UserFirst
        $stmt = $db->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            flash('error', 'Utilisateur introuvable');
            $this->redirect('admin/users');
            return;
        }
        
        if ($user['username'] === 'UserFirst') {
            flash('error', 'Le compte UserFirst ne peut pas être supprimé');
            $this->redirect('admin/users');
            return;
        }
        
        // Supprimer l'utilisateur (les données associées seront supprimées en cascade)
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        
        if ($stmt->execute([$id])) {
            flash('success', 'Utilisateur supprimé avec succès');
        } else {
            flash('error', 'Erreur lors de la suppression');
        }
        
        $this->redirect('admin/users');
    }
    
    /**
     * Gestion des rôles - Page principale
     */
    public function manageRoles()
    {
        if (!$this->requireAdminAccess()) return;
        
        $db = Database::getConnection();
        
        // Récupérer tous les utilisateurs avec leurs rôles
        $users = $db->query("
            SELECT id, username, email, role, is_active, created_at
            FROM users
            ORDER BY username
        ")->fetchAll();
        
        // Définir les rôles disponibles
        $availableRoles = [
            'user' => [
                'label' => 'Utilisateur',
                'description' => 'Accès aux fonctionnalités de base',
                'color' => 'secondary'
            ],
            'admin' => [
                'label' => 'Administrateur',
                'description' => 'Gestion des utilisateurs et maintenance',
                'color' => 'primary'
            ]
        ];
        
        $this->view('admin.users.roles', [
            'users' => $users,
            'availableRoles' => $availableRoles,
            'title' => 'Gestion des rôles'
        ]);
    }
    
    /**
     * Mise à jour des rôles
     */
    public function updateRoles()
    {
        if (!$this->requireAdminAccess()) return;
        if (!$this->validateCsrfOrFail('admin/users/roles')) return;
        
        $roles = $_POST['roles'] ?? [];
        
        if (empty($roles)) {
            flash('error', 'Aucune modification à appliquer');
            $this->redirect('admin/users/roles');
            return;
        }
        
        $db = Database::getConnection();
        $updated = 0;
        
        try {
            foreach ($roles as $userId => $newRole) {
                // Vérifier que ce n'est pas UserFirst
                $stmt = $db->prepare("SELECT username FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch();
                
                if ($user && $user['username'] !== 'UserFirst') {
                    // Valider le rôle
                    if (in_array($newRole, ['user', 'admin'])) {
                        $update = $db->prepare("UPDATE users SET role = ? WHERE id = ?");
                        $update->execute([$newRole, $userId]);
                        $updated++;
                    }
                }
            }
            
            flash('success', "{$updated} rôle(s) mis à jour");
        } catch (\Exception $e) {
            flash('error', 'Erreur lors de la mise à jour : ' . $e->getMessage());
        }
        
        $this->redirect('admin/users/roles');
    }
    
    /**
     * Recalculer tous les soldes des comptes
     */
    public function recalculSoldes()
    {
        if (!$this->requireAdminAccess()) return;
        
        $db = Database::getConnection();
        
        try {
            // Récupérer tous les comptes
            $comptes = $db->query("SELECT id, solde_initial FROM comptes")->fetchAll();
            
            $updated = 0;
            foreach ($comptes as $compte) {
                // Calculer le solde
                $stmt = $db->prepare("
                    SELECT 
                        COALESCE(SUM(CASE WHEN type_operation = 'credit' THEN montant ELSE 0 END), 0) as total_credits,
                        COALESCE(SUM(CASE WHEN type_operation = 'debit' THEN montant ELSE 0 END), 0) as total_debits
                    FROM transactions
                    WHERE compte_id = ?
                ");
                $stmt->execute([$compte['id']]);
                $totaux = $stmt->fetch();
                
                $solde_calcule = $compte['solde_initial'] + $totaux['total_credits'] - $totaux['total_debits'];
                
                // Mettre à jour
                $update = $db->prepare("UPDATE comptes SET solde_actuel = ? WHERE id = ?");
                $update->execute([$solde_calcule, $compte['id']]);
                $updated++;
            }
            
            flash('success', "Soldes recalculés pour {$updated} compte(s)");
        } catch (\Exception $e) {
            flash('error', 'Erreur lors du recalcul : ' . $e->getMessage());
        }
        
        $this->redirect('admin');
    }
    
    /**
     * Nettoyer les logs anciens
     */
    public function cleanLogs()
    {
        if (!$this->requireAdminAccess()) return;
        
        $logsDir = __DIR__ . '/../../storage/logs';
        $deleted = 0;
        $limit = strtotime('-90 days');
        
        if (is_dir($logsDir)) {
            $files = glob($logsDir . '/*.log');
            foreach ($files as $file) {
                if (filemtime($file) < $limit) {
                    if (unlink($file)) {
                        $deleted++;
                    }
                }
            }
        }
        
        flash('success', "{$deleted} fichier(s) log supprimé(s) (> 90 jours)");
        $this->redirect('admin');
    }
    
    /**
     * Nettoyer les sessions expirées
     */
    public function cleanSessions()
    {
        if (!$this->requireAdminAccess()) return;
        
        $sessionsDir = __DIR__ . '/../../storage/sessions';
        $deleted = 0;
        
        if (is_dir($sessionsDir)) {
            $files = glob($sessionsDir . '/sess_*');
            $now = time();
            
            foreach ($files as $file) {
                // Supprimer les fichiers non modifiés depuis plus de 24h
                if (($now - filemtime($file)) > 86400) {
                    if (unlink($file)) {
                        $deleted++;
                    }
                }
            }
        }
        
        flash('success', "{$deleted} session(s) expirée(s) supprimée(s)");
        $this->redirect('admin');
    }
    
    /**
     * Optimiser les tables de la base de données
     */
    public function optimizeDatabase()
    {
        if (!$this->requireAdminAccess()) return;
        
        $db = Database::getConnection();
        
        try {
            // Activer le buffering pour éviter les erreurs unbuffered queries
            $db->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
            
            // Récupérer toutes les tables directement
            $tables = $db->query("SHOW TABLES")->fetchAll(\PDO::FETCH_COLUMN);
            
            $optimized = 0;
            foreach ($tables as $table) {
                // Utiliser query() au lieu de exec() pour OPTIMIZE TABLE
                $stmt = $db->query("OPTIMIZE TABLE `$table`");
                $stmt->fetchAll(); // Consommer tous les résultats
                $stmt->closeCursor();
                $optimized++;
            }
            
            flash('success', "{$optimized} table(s) optimisée(s)");
        } catch (\Exception $e) {
            flash('error', 'Erreur lors de l\'optimisation : ' . $e->getMessage());
        }
        
        $this->redirect('admin');
    }
    
    /**
     * Appliquer les index de performance
     */
    public function applyPerformanceIndexes()
    {
        if (!$this->requireAdminAccess()) return;
        
        try {
            // Charger le script d'optimisation
            require_once __DIR__ . '/../../database/migrations/optimize_database.php';
            
            $optimizer = new \DatabaseOptimizer();
            // Mode silencieux pour éviter les echo qui cassent le redirect
            $optimizer->applyOptimizations(true);
            $results = $optimizer->getResults();
            
            if ($results['success']) {
                flash('success', sprintf(
                    "Optimisation réussie : %d index créés, %d déjà existants",
                    $results['created'],
                    $results['exists']
                ));
            } else {
                flash('warning', sprintf(
                    "Optimisation terminée avec %d erreur(s) : %d index créés, %d déjà existants",
                    $results['errors'],
                    $results['created'],
                    $results['exists']
                ));
            }
            
        } catch (\Exception $e) {
            flash('error', 'Erreur lors de l\'optimisation : ' . $e->getMessage());
        }
        
        $this->redirect('admin');
    }
    
    /**
     * Télécharger un dump SQL de la base
     */
    public function backup()
    {
        if (!$this->requireAdminAccess()) return;
        
        $db = Database::getConnection();
        $dbName = $db->query("SELECT DATABASE() as db")->fetch()['db'];
        
        // Générer le nom du fichier
        $filename = 'backup_' . $dbName . '_' . date('Y-m-d_H-i-s') . '.sql';
        
        // Headers pour le téléchargement
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        
        // Générer le dump SQL
        echo "-- Backup de $dbName le " . date('Y-m-d H:i:s') . "\n\n";
        echo "SET FOREIGN_KEY_CHECKS=0;\n\n";
        
        // Récupérer toutes les tables
        $tables = $db->query("SHOW TABLES")->fetchAll(\PDO::FETCH_COLUMN);
        
        foreach ($tables as $table) {
            // Structure de la table
            $createTable = $db->query("SHOW CREATE TABLE `$table`")->fetch();
            echo "-- Structure de la table `$table`\n";
            echo "DROP TABLE IF EXISTS `$table`;\n";
            echo $createTable['Create Table'] . ";\n\n";
            
            // Données de la table
            $rows = $db->query("SELECT * FROM `$table`")->fetchAll(\PDO::FETCH_ASSOC);
            if (count($rows) > 0) {
                echo "-- Données de la table `$table`\n";
                foreach ($rows as $row) {
                    $values = array_map(function($value) use ($db) {
                        return $value === null ? 'NULL' : $db->quote($value);
                    }, array_values($row));
                    
                    echo "INSERT INTO `$table` VALUES (" . implode(', ', $values) . ");\n";
                }
                echo "\n";
            }
        }
        
        echo "SET FOREIGN_KEY_CHECKS=1;\n";
        exit;
    }
    
    /**
     * Page de restauration
     */
    public function restorePage()
    {
        if (!$this->requireAdminAccess()) return;
        
        $this->view('admin.restore', [
            'title' => 'Restaurer une sauvegarde'
        ]);
    }
    
    /**
     * Traiter l'upload et restauration
     */
    public function restoreUpload()
    {
        if (!$this->requireAdminAccess()) return;
        if (!$this->validateCsrfOrFail('admin/restore')) return;
        
        if (!isset($_FILES['sql_file']) || $_FILES['sql_file']['error'] !== UPLOAD_ERR_OK) {
            flash('error', 'Erreur lors de l\'upload du fichier');
            $this->redirect('admin/restore');
            return;
        }
        
        $file = $_FILES['sql_file'];
        
        // Vérifier l'extension
        if (!str_ends_with($file['name'], '.sql')) {
            flash('error', 'Le fichier doit être au format .sql');
            $this->redirect('admin/restore');
            return;
        }
        
        // Lire le contenu
        $sql = file_get_contents($file['tmp_name']);
        
        if (empty($sql)) {
            flash('error', 'Le fichier SQL est vide');
            $this->redirect('admin/restore');
            return;
        }
        
        $db = Database::getConnection();
        
        try {
            // Exécuter le SQL
            $db->exec($sql);
            
            flash('success', 'Base de données restaurée avec succès. Veuillez vous reconnecter.');
            
            // Déconnecter l'utilisateur
            session_destroy();
            $this->redirect('login');
        } catch (\Exception $e) {
            flash('error', 'Erreur lors de la restauration : ' . $e->getMessage());
            $this->redirect('admin/restore');
        }
    }
    
    /**
     * Page de réinitialisation des mots de passe
     */
    public function resetPasswords()
    {
        if (!$this->requireAdminAccess()) return;
        
        // Récupérer tous les utilisateurs sauf UserFirst
        $db = Database::getConnection();
        $users = $db->query("
            SELECT id, username, email, role, is_active
            FROM users
            WHERE username != 'UserFirst'
            ORDER BY username
        ")->fetchAll();
        
        $this->view('admin.reset_passwords', [
            'users' => $users,
            'title' => 'Réinitialiser les mots de passe'
        ]);
    }
    
    /**
     * Traiter la réinitialisation des mots de passe (PCI DSS)
     */
    public function processResetPasswords()
    {
        if (!$this->requireAdminAccess()) return;
        if (!$this->validateCsrfOrFail('admin/users/reset-passwords')) return;
        
        $userIds = $_POST['user_ids'] ?? [];
        $newPassword = $_POST['new_password'] ?? '';
        
        if (empty($userIds) || empty($newPassword)) {
            flash('error', 'Veuillez sélectionner au moins un utilisateur et saisir un mot de passe');
            $this->redirect('admin/users/reset-passwords');
            return;
        }
        
        // Validation PCI DSS du mot de passe
        $validationErrors = PasswordPolicyService::validate($newPassword);
        if (!empty($validationErrors)) {
            foreach ($validationErrors as $error) {
                flash('error', $error);
            }
            $this->redirect('admin/users/reset-passwords');
            return;
        }
        
        $db = Database::getConnection();
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $audit = new AuditLogService();
        
        try {
            $updated = 0;
            
            foreach ($userIds as $userId) {
                // Vérifier que ce n'est pas UserFirst
                $stmt = $db->prepare("SELECT username FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch();
                
                if ($user && $user['username'] !== 'UserFirst') {
                    // Mise à jour avec PCI DSS
                    $update = $db->prepare("
                        UPDATE users 
                        SET password = ?, 
                            last_password_change = NOW(), 
                            password_expires_at = DATE_ADD(NOW(), INTERVAL 90 DAY),
                            must_change_password = 1
                        WHERE id = ?
                    ");
                    $update->execute([$hashedPassword, $userId]);
                    
                    // Log audit
                    $audit->log(
                        AuditLogService::ACTION_PASSWORD_RESET,
                        'users',
                        $userId,
                        null,
                        ['reset_by_admin' => $_SESSION['user_id'], 'username' => $user['username']],
                        $_SESSION['user_id']
                    );
                    
                    $updated++;
                }
            }
            
            flash('success', "Mot de passe réinitialisé pour {$updated} utilisateur(s). Ils devront le changer à la prochaine connexion.");
        } catch (\Exception $e) {
            flash('error', 'Erreur lors de la réinitialisation : ' . $e->getMessage());
        }
        
        $this->redirect('admin/users/reset-passwords');
    }
    
    /**
     * Récupérer les statistiques globales
     */
    private function getGlobalStats(): array
    {
        $db = Database::getConnection();
        
        $stats = [];
        
        try {
            // Utilisateurs
            $stats['users_total'] = $db->query("SELECT COUNT(*) as count FROM users")->fetch()['count'] ?? 0;
            
            // Comptes
            $stats['comptes_total'] = $db->query("SELECT COUNT(*) as count FROM comptes")->fetch()['count'] ?? 0;
            
            // Transactions
            $stats['transactions_total'] = $db->query("SELECT COUNT(*) as count FROM transactions")->fetch()['count'] ?? 0;
            $stats['transactions_importees'] = $db->query("SELECT COUNT(*) as count FROM transactions WHERE importee = 1")->fetch()['count'] ?? 0;
            
            // Imports
            $stats['imports_total'] = $db->query("SELECT COUNT(*) as count FROM imports")->fetch()['count'] ?? 0;
            
            // Catégories
            $stats['categories_total'] = $db->query("SELECT COUNT(*) as count FROM categories")->fetch()['count'] ?? 0;
            
            // Tiers
            $stats['tiers_total'] = $db->query("SELECT COUNT(*) as count FROM tiers")->fetch()['count'] ?? 0;
            
            // Règles
            $stats['regles_total'] = $db->query("SELECT COUNT(*) as count FROM regles_automatisation")->fetch()['count'] ?? 0;
            
            // Budgets
            $stats['budgets_total'] = $db->query("SELECT COUNT(*) as count FROM budgets")->fetch()['count'] ?? 0;
            
            // Tickets admin
            $stats['tickets_total'] = $db->query("SELECT COUNT(*) as count FROM admin_tickets")->fetch()['count'] ?? 0;
            $stats['tickets_open'] = $db->query("SELECT COUNT(*) as count FROM admin_tickets WHERE status = 'open'")->fetch()['count'] ?? 0;
            $stats['tickets_in_progress'] = $db->query("SELECT COUNT(*) as count FROM admin_tickets WHERE status = 'in_progress'")->fetch()['count'] ?? 0;
            $stats['tickets_high_priority'] = $db->query("SELECT COUNT(*) as count FROM admin_tickets WHERE priority = 'high' OR priority = 'urgent'")->fetch()['count'] ?? 0;
            
            // Taille de la BDD
            $dbName = $db->query("SELECT DATABASE() as db")->fetch()['db'];
            $sizeQuery = $db->query("
                SELECT 
                    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
                FROM information_schema.TABLES 
                WHERE table_schema = '$dbName'
            ")->fetch();
            $stats['db_size_mb'] = $sizeQuery['size_mb'] ?? 0;
            
            // Version MySQL
            $stats['mysql_version'] = $db->query("SELECT VERSION() as version")->fetch()['version'] ?? 'N/A';
            
            // Version PHP
            $stats['php_version'] = phpversion();
            
        } catch (\Exception $e) {
            // En cas d'erreur, retourner des stats vides
            error_log("Erreur lors de la récupération des stats admin : " . $e->getMessage());
        }
        
        return $stats;
    }

    /**
     * Gestion des icônes Bootstrap disponibles dans le sélecteur
     */
    public function icons(): void
    {
        if (!$this->requireAdminAccess()) return;

        $configFile = __DIR__ . '/../../storage/config/bootstrap-icons.json';
        $icons = [];

        if (file_exists($configFile)) {
            $icons = json_decode(file_get_contents($configFile), true) ?? [];
        }

        $this->view('admin.icons', [
            'icons' => $icons,
            'title' => 'Gestion des icônes'
        ]);
    }

    /**
     * Ajouter une icône à la liste
     */
    public function addIcon(): void
    {
        if (!$this->requireAdminAccess()) return;
        if (!$this->validateCsrfOrFail('admin/icons')) return;

        $iconClass = trim($_POST['icon_class'] ?? '');

        if (empty($iconClass)) {
            flash('error', "Le nom de l'icône est obligatoire");
            header('Location: ' . url('admin/icons'));
            exit;
        }

        // Valider le format bi-*
        if (!preg_match('/^bi-[a-z0-9-]+$/', $iconClass)) {
            flash('error', "Format invalide. Utilisez le format 'bi-nom-icone'");
            header('Location: ' . url('admin/icons'));
            exit;
        }

        $configFile = __DIR__ . '/../../storage/config/bootstrap-icons.json';
        $icons = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) ?? [] : [];

        if (in_array($iconClass, $icons)) {
            flash('error', "Cette icône existe déjà");
            header('Location: ' . url('admin/icons'));
            exit;
        }

        $icons[] = $iconClass;
        sort($icons); // Trier alphabétiquement

        if (file_put_contents($configFile, json_encode($icons, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
            flash('success', "Icône '{$iconClass}' ajoutée avec succès");
        } else {
            flash('error', "Erreur lors de l'enregistrement");
        }

        header('Location: ' . url('admin/icons'));
        exit;
    }

    /**
     * Supprimer une icône de la liste
     */
    public function deleteIcon(): void
    {
        if (!$this->requireAdminAccess()) return;
        if (!$this->validateCsrfOrFail('admin/icons')) return;

        $iconClass = trim($_POST['icon_class'] ?? '');

        if (empty($iconClass)) {
            flash('error', "Icône non spécifiée");
            header('Location: ' . url('admin/icons'));
            exit;
        }

        $configFile = __DIR__ . '/../../storage/config/bootstrap-icons.json';
        $icons = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) ?? [] : [];

        $icons = array_values(array_filter($icons, fn($icon) => $icon !== $iconClass));

        if (file_put_contents($configFile, json_encode($icons, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
            flash('success', "Icône '{$iconClass}' supprimée avec succès");
        } else {
            flash('error', "Erreur lors de l'enregistrement");
        }

        header('Location: ' . url('admin/icons'));
        exit;
    }
    
    /**
     * Liste des utilisateurs verrouillés
     */
    public function lockedUsers(): void
    {
        if (!$this->requireAdminAccess()) return;
        
        $db = Database::getConnection();
        
        // Récupérer utilisateurs avec tentatives échouées ou verrouillés
        $users = $db->query("
            SELECT 
                id, username, email, role, 
                failed_login_attempts, 
                locked_until,
                CASE 
                    WHEN locked_until IS NOT NULL AND locked_until > NOW() THEN 'locked'
                    WHEN failed_login_attempts >= 5 THEN 'suspicious'
                    ELSE 'ok'
                END as status
            FROM users
            WHERE (failed_login_attempts > 0 OR locked_until IS NOT NULL)
            AND username != 'UserFirst'
            ORDER BY failed_login_attempts DESC, locked_until DESC
        ")->fetchAll();
        
        $this->view('admin.locked_users', [
            'users' => $users,
            'title' => 'Utilisateurs verrouillés'
        ]);
    }
    
    /**
     * Déverrouiller un utilisateur (PCI DSS)
     */
    public function unlockUser(): void
    {
        if (!$this->requireAdminAccess()) return;
        if (!$this->validateCsrfOrFail('admin/users/unlock')) return;
        
        $userId = $_POST['user_id'] ?? null;
        
        if (!$userId) {
            flash('error', 'Utilisateur non spécifié');
            $this->redirect('admin/locked-users');
            return;
        }
        
        $db = Database::getConnection();
        
        // Vérifier que ce n'est pas UserFirst
        $stmt = $db->prepare("SELECT username, email FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            flash('error', 'Utilisateur introuvable');
            $this->redirect('admin/locked-users');
            return;
        }
        
        if ($user['username'] === 'UserFirst') {
            flash('error', 'Le compte UserFirst ne peut pas être déverrouillé');
            $this->redirect('admin/locked-users');
            return;
        }
        
        try {
            // Déverrouiller le compte
            $update = $db->prepare("
                UPDATE users 
                SET failed_login_attempts = 0, 
                    locked_until = NULL
                WHERE id = ?
            ");
            $update->execute([$userId]);
            
            // Réinitialiser les tentatives en session
            $email = $user['email'];
            PasswordPolicyService::resetAttempts($email);
            
            // Log audit
            $audit = new AuditLogService();
            $audit->log(
                AuditLogService::ACTION_ACCOUNT_UNLOCKED,
                'users',
                $userId,
                null,
                ['unlocked_by_admin' => $_SESSION['user_id'], 'username' => $user['username']],
                $_SESSION['user_id']
            );
            
            flash('success', "Compte '{$user['username']}' déverrouillé avec succès");
        } catch (\Exception $e) {
            flash('error', 'Erreur lors du déverrouillage : ' . $e->getMessage());
        }
        
        $this->redirect('admin/locked-users');
    }
    
    /**
     * Réinitialiser un mot de passe utilisateur (PCI DSS)
     */
    public function resetUserPassword(): void
    {
        if (!$this->requireAdminAccess()) return;
        if (!$this->validateCsrfOrFail('admin/users/reset-password')) return;
        
        $userId = $_POST['user_id'] ?? null;
        $newPassword = $_POST['new_password'] ?? '';
        
        if (!$userId || empty($newPassword)) {
            flash('error', 'Données manquantes');
            $this->redirect('admin/users');
            return;
        }
        
        // Validation PCI DSS
        $validationErrors = PasswordPolicyService::validate($newPassword);
        if (!empty($validationErrors)) {
            foreach ($validationErrors as $error) {
                flash('error', $error);
            }
            $this->redirect("admin/users/{$userId}/edit");
            return;
        }
        
        $db = Database::getConnection();
        
        // Vérifier que ce n'est pas UserFirst
        $stmt = $db->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            flash('error', 'Utilisateur introuvable');
            $this->redirect('admin/users');
            return;
        }
        
        if ($user['username'] === 'UserFirst') {
            flash('error', 'Le mot de passe de UserFirst ne peut pas être réinitialisé');
            $this->redirect('admin/users');
            return;
        }
        
        try {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Mise à jour avec PCI DSS
            $update = $db->prepare("
                UPDATE users 
                SET password = ?, 
                    last_password_change = NOW(), 
                    password_expires_at = DATE_ADD(NOW(), INTERVAL 90 DAY),
                    must_change_password = 1,
                    failed_login_attempts = 0,
                    locked_until = NULL
                WHERE id = ?
            ");
            $update->execute([$hashedPassword, $userId]);
            
            // Ajouter à l'historique
            PasswordPolicyService::addToHistory($userId, $hashedPassword);
            
            // Log audit
            $audit = new AuditLogService();
            $audit->log(
                AuditLogService::ACTION_PASSWORD_RESET,
                'users',
                $userId,
                null,
                ['reset_by_admin' => $_SESSION['user_id'], 'username' => $user['username']],
                $_SESSION['user_id']
            );
            
            flash('success', "Mot de passe réinitialisé. L'utilisateur devra le changer à la prochaine connexion.");
        } catch (\Exception $e) {
            flash('error', 'Erreur lors de la réinitialisation : ' . $e->getMessage());
        }
        
        $this->redirect("admin/users/{$userId}/edit");
    }

    /**
     * Gestion des tickets d'administration
     */
    public function tickets()
    {
        if (!$this->requireAdminAccess()) return;

        $status = $_GET['status'] ?? 'open';
        $page = (int)($_GET['page'] ?? 1);
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        // Récupérer les tickets
        $tickets = Database::select(
            "SELECT t.*, u.username as user_name, u.email as user_email,
                    a.username as admin_name
             FROM admin_tickets t
             JOIN users u ON t.user_id = u.id
             LEFT JOIN users a ON t.admin_id = a.id
             WHERE t.status = ?
             ORDER BY
                 CASE t.priority
                     WHEN 'urgent' THEN 1
                     WHEN 'high' THEN 2
                     WHEN 'normal' THEN 3
                     WHEN 'low' THEN 4
                 END,
                 t.created_at DESC
             LIMIT ? OFFSET ?",
            [$status, $perPage, $offset]
        );

        // Compter le total
        $total = Database::selectOne(
            "SELECT COUNT(*) as count FROM admin_tickets WHERE status = ?",
            [$status]
        )['count'];

        $totalPages = ceil($total / $perPage);

        // Statistiques
        $stats = Database::selectOne(
            "SELECT
                COUNT(CASE WHEN status = 'open' THEN 1 END) as open_count,
                COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress_count,
                COUNT(CASE WHEN status = 'waiting_user' THEN 1 END) as waiting_count,
                COUNT(CASE WHEN status = 'resolved' THEN 1 END) as resolved_count,
                COUNT(CASE WHEN status = 'urgent' THEN 1 END) as urgent_count
             FROM admin_tickets"
        );

        $this->view('admin.tickets.index', [
            'user' => $_SESSION['user'],
            'tickets' => $tickets,
            'status' => $status,
            'page' => $page,
            'totalPages' => $totalPages,
            'stats' => $stats
        ]);
    }

    /**
     * Voir un ticket spécifique
     */
    public function showTicket($id)
    {
        if (!$this->requireAdminAccess()) return;

        $ticket = Database::selectOne(
            "SELECT t.*, u.username as user_name, u.email as user_email,
                    a.username as admin_name
             FROM admin_tickets t
             JOIN users u ON t.user_id = u.id
             LEFT JOIN users a ON t.admin_id = a.id
             WHERE t.id = ?",
            [$id]
        );

        if (!$ticket) {
            flash('error', 'Ticket non trouvé');
            $this->redirect('admin/tickets');
        }

        // Récupérer les réponses
        $replies = Database::select(
            "SELECT r.*, u.username, u.email
             FROM admin_ticket_replies r
             JOIN users u ON r.user_id = u.id
             WHERE r.ticket_id = ?
             ORDER BY r.created_at ASC",
            [$id]
        );

        $this->view('admin.tickets.show', [
            'user' => $_SESSION['user'],
            'ticket' => $ticket,
            'replies' => $replies
        ]);
    }

    /**
     * Mettre à jour le statut d'un ticket
     */
    public function updateTicketStatus($id)
    {
        if (!$this->requireAdminAccess()) return;

        if (!$this->verifyCsrf()) {
            flash('error', 'Token CSRF invalide');
            $this->redirect("admin/tickets/{$id}");
        }

        $status = $_POST['status'] ?? '';
        $adminId = $_POST['admin_id'] ?? null;
        $priority = $_POST['priority'] ?? null;

        $validStatuses = ['open', 'in_progress', 'waiting_user', 'resolved', 'closed'];
        if (!in_array($status, $validStatuses)) {
            flash('error', 'Statut invalide');
            $this->redirect("admin/tickets/{$id}");
        }

        // Mettre à jour le ticket
        $updateData = ['status' => $status];
        if ($adminId) $updateData['admin_id'] = $adminId;
        if ($priority) $updateData['priority'] = $priority;

        if ($status === 'resolved' || $status === 'closed') {
            $updateData['resolved_at'] = date('Y-m-d H:i:s');
        }

        Database::update(
            "UPDATE admin_tickets SET " . implode(' = ?, ', array_keys($updateData)) . " = ? WHERE id = ?",
            array_merge(array_values($updateData), [$id])
        );

        flash('success', 'Ticket mis à jour avec succès');
        $this->redirect("admin/tickets/{$id}");
    }

    /**
     * Répondre à un ticket
     */
    public function replyToTicket($id)
    {
        if (!$this->requireAdminAccess()) return;

        if (!$this->verifyCsrf()) {
            flash('error', 'Token CSRF invalide');
            $this->redirect("admin/tickets/{$id}");
        }

        $message = trim($_POST['message'] ?? '');
        $isInternal = isset($_POST['is_internal']) ? 1 : 0;

        if (empty($message)) {
            flash('error', 'Le message ne peut pas être vide');
            $this->redirect("admin/tickets/{$id}");
        }

        // Ajouter la réponse
        Database::insert(
            "INSERT INTO admin_ticket_replies (ticket_id, user_id, message, is_internal) VALUES (?, ?, ?, ?)",
            [$id, $_SESSION['user']['id'], $message, $isInternal]
        );

        // Mettre à jour la date du ticket
        Database::update(
            "UPDATE admin_tickets SET updated_at = NOW() WHERE id = ?",
            [$id]
        );

        flash('success', 'Réponse ajoutée avec succès');
        $this->redirect("admin/tickets/{$id}");
    }

    /**
     * Créer un ticket (pour les utilisateurs)
     */
    public function createTicket()
    {
        if (!$this->isAuthenticated()) {
            $this->redirect('login');
        }

        if (!$this->verifyCsrf()) {
            flash('error', 'Token CSRF invalide');
            $this->redirect('dashboard');
        }

        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');
        $category = $_POST['category'] ?? 'general';

        if (empty($subject) || empty($message)) {
            flash('error', 'Sujet et message sont requis');
            $this->redirect('dashboard');
        }

        // Créer le ticket
        $ticketId = Database::insert(
            "INSERT INTO admin_tickets (user_id, subject, message, category) VALUES (?, ?, ?, ?)",
            [$_SESSION['user']['id'], $subject, $message, $category]
        );

        // Envoyer un email aux administrateurs
        $this->notifyAdminsOfNewTicket($ticketId);

        flash('success', 'Votre ticket a été créé. Un administrateur vous répondra bientôt.');
        $this->redirect('dashboard');
    }

    /**
     * Notifier les administrateurs d'un nouveau ticket
     */
    private function notifyAdminsOfNewTicket($ticketId)
    {
        $ticket = Database::selectOne(
            "SELECT t.*, u.username, u.email FROM admin_tickets t
             JOIN users u ON t.user_id = u.id
             WHERE t.id = ?",
            [$ticketId]
        );

        if (!$ticket) return;

        // Récupérer tous les administrateurs
        $admins = Database::select(
            "SELECT id, username, email FROM users WHERE role IN ('admin', 'super_admin')"
        );

        $emailService = new \MonBudget\Services\EmailService();

        foreach ($admins as $admin) {
            $emailService->sendAdminTicket(
                $admin['email'],
                $ticketId,
                $ticket['subject'],
                $ticket['message'],
                $admin['username']
            );
        }
    }
}
