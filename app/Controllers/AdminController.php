<?php

namespace MonBudget\Controllers;

use MonBudget\Core\Database;

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
            if (strlen($new_password) < 8) {
                flash('error', 'Le mot de passe doit contenir au moins 8 caractères');
                $this->redirect("admin/users/{$id}/edit");
                return;
            }
            
            $hashedPassword = password_hash($new_password, PASSWORD_ARGON2ID);
            $stmt = $db->prepare("
                UPDATE users 
                SET email = ?, role = ?, is_active = ?, password = ?
                WHERE id = ?
            ");
            $stmt->execute([$email, $role, $is_active, $hashedPassword, $id]);
        } else {
            $stmt = $db->prepare("
                UPDATE users 
                SET email = ?, role = ?, is_active = ?
                WHERE id = ?
            ");
            $stmt->execute([$email, $role, $is_active, $id]);
        }
        
        flash('success', 'Utilisateur mis à jour avec succès');
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
     * Traiter la réinitialisation des mots de passe
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
        
        if (strlen($newPassword) < 8) {
            flash('error', 'Le mot de passe doit contenir au moins 8 caractères');
            $this->redirect('admin/users/reset-passwords');
            return;
        }
        
        $db = Database::getConnection();
        $hashedPassword = password_hash($newPassword, PASSWORD_ARGON2ID);
        
        try {
            $updated = 0;
            
            foreach ($userIds as $userId) {
                // Vérifier que ce n'est pas UserFirst
                $stmt = $db->prepare("SELECT username FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch();
                
                if ($user && $user['username'] !== 'UserFirst') {
                    $update = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $update->execute([$hashedPassword, $userId]);
                    $updated++;
                }
            }
            
            flash('success', "Mot de passe réinitialisé pour {$updated} utilisateur(s)");
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
}
