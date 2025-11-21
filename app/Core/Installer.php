<?php

namespace MonBudget\Core;

use PDO;
use PDOException;

/**
 * Installer - Gestionnaire d'installation automatique
 * 
 * Gère le processus d'installation de l'application
 */
class Installer
{
    private array $config = [];
    private array $errors = [];
    private array $steps = [];
    
    /**
     * Vérifier si l'application est déjà installée
     */
    public static function isInstalled(): bool
    {
        $configFile = dirname(__DIR__, 2) . '/config/installed.json';
        
        if (!file_exists($configFile)) {
            // Créer le fichier depuis l'exemple si disponible
            $exampleFile = $configFile . '.example';
            if (file_exists($exampleFile)) {
                copy($exampleFile, $configFile);
            } else {
                // Créer un fichier vide par défaut
                $configDir = dirname($configFile);
                if (!file_exists($configDir)) {
                    mkdir($configDir, 0755, true);
                }
                file_put_contents($configFile, json_encode([
                    'installed' => false,
                    'installation_date' => null,
                    'version' => null,
                    'database_initialized' => false
                ], JSON_PRETTY_PRINT));
            }
            return false;
        }
        
        $config = json_decode(file_get_contents($configFile), true);
        
        return isset($config['installed']) && $config['installed'] === true;
    }
    
    /**
     * Marquer l'application comme installée
     */
    public static function markAsInstalled(): bool
    {
        $configFile = dirname(__DIR__, 2) . '/config/installed.json';
        $configDir = dirname($configFile);
        
        if (!file_exists($configDir)) {
            mkdir($configDir, 0755, true);
        }
        
        $config = [
            'installed' => true,
            'installed_at' => date('Y-m-d H:i:s'),
            'version' => '2.2.0'
        ];
        
        return file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT)) !== false;
    }
    
    /**
     * Vérifier la connexion à la base de données
     */
    public function testDatabaseConnection(array $config): bool
    {
        try {
            $dsn = sprintf(
                "%s:host=%s;port=%s;charset=%s",
                $config['driver'] ?? 'mysql',
                $config['host'] ?? 'localhost',
                $config['port'] ?? '3306',
                $config['charset'] ?? 'utf8mb4'
            );
            
            $pdo = new PDO(
                $dsn,
                $config['username'] ?? 'root',
                $config['password'] ?? '',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            
            return true;
        } catch (PDOException $e) {
            $this->errors[] = "Database connection failed: " . $e->getMessage();
            return false;
        }
    }
    
    /**
     * Créer la base de données si elle n'existe pas
     */
    public function createDatabase(array $config): bool
    {
        try {
            $dsn = sprintf(
                "%s:host=%s;port=%s",
                $config['driver'] ?? 'mysql',
                $config['host'] ?? 'localhost',
                $config['port'] ?? '3306'
            );
            
            $pdo = new PDO(
                $dsn,
                $config['username'] ?? 'root',
                $config['password'] ?? '',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            
            $database = $config['database'] ?? 'monbudget_v2';
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            
            $this->steps[] = "Database '$database' created successfully";
            return true;
        } catch (PDOException $e) {
            $this->errors[] = "Failed to create database: " . $e->getMessage();
            return false;
        }
    }
    
    /**
     * Importer le fichier SQL
     */
    public function importSQLFile(array $config, string $sqlFile): bool
    {
        if (!file_exists($sqlFile)) {
            $this->errors[] = "SQL file not found: $sqlFile";
            return false;
        }
        
        try {
            $dsn = sprintf(
                "%s:host=%s;port=%s;dbname=%s;charset=%s",
                $config['driver'] ?? 'mysql',
                $config['host'] ?? 'localhost',
                $config['port'] ?? '3306',
                $config['database'] ?? 'monbudget_v2',
                $config['charset'] ?? 'utf8mb4'
            );
            
            $pdo = new PDO(
                $dsn,
                $config['username'] ?? 'root',
                $config['password'] ?? '',
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                ]
            );
            
            // Lire le fichier SQL en UTF-8
            $sql = file_get_contents($sqlFile);
            if ($sql === false) {
                throw new Exception("Impossible de lire le fichier SQL");
            }
            
            // Convertir en UTF-8 si nécessaire (détection automatique)
            if (!mb_check_encoding($sql, 'UTF-8')) {
                $sql = mb_convert_encoding($sql, 'UTF-8', mb_detect_encoding($sql));
            }
            
            // Supprimer seulement les commentaires standard (pas les directives MySQL /*! */)
            $sql = preg_replace('/--.*$/m', '', $sql);
            // Ne pas supprimer les directives MySQL (/*!... */)
            $sql = preg_replace('/\/\*(?!\!).*?\*\//s', '', $sql);
            
            // Séparer les requêtes par point-virgule
            $queries = array_filter(
                array_map('trim', explode(';', $sql)),
                fn($query) => !empty($query)
            );
            
            // Exécuter chaque requête séparément
            foreach ($queries as $query) {
                if (!empty($query)) {
                    $pdo->exec($query);
                }
            }
            
            $this->steps[] = "SQL file imported successfully (" . count($queries) . " queries)";
            return true;
        } catch (PDOException $e) {
            $this->errors[] = "Failed to import SQL: " . $e->getMessage();
            return false;
        }
    }
    
    /**
     * Créer l'utilisateur administrateur
     */
    public function createAdminUser(array $config, array $userData): bool
    {
        try {
            $dsn = sprintf(
                "%s:host=%s;port=%s;dbname=%s;charset=%s",
                $config['driver'] ?? 'mysql',
                $config['host'] ?? 'localhost',
                $config['port'] ?? '3306',
                $config['database'] ?? 'monbudget_v2',
                $config['charset'] ?? 'utf8mb4'
            );
            
            $pdo = new PDO(
                $dsn,
                $config['username'] ?? 'root',
                $config['password'] ?? '',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            
            // ÉTAPE 1 : Créer le super-admin UserFirst avec mot de passe fort généré
            $userFirstPassword = $this->generateStrongPassword();
            
            $stmt = $pdo->prepare("
                INSERT INTO users (username, email, password, role, created_at)
                VALUES (:username, :email, :password, :role, NOW())
            ");
            
            $stmt->execute([
                'username' => 'UserFirst',
                'email' => 'userfirst@monbudget.local',
                'password' => password_hash($userFirstPassword, PASSWORD_ARGON2ID, [
                    'memory_cost' => 65536,
                    'time_cost' => 4,
                    'threads' => 3
                ]),
                'role' => 'admin'
            ]);
            
            $this->steps[] = "Super-admin UserFirst created with strong password";
            
            // Sauvegarder le mot de passe UserFirst pour l'affichage final
            $_SESSION['userfirst_credentials'] = [
                'username' => 'UserFirst',
                'password' => $userFirstPassword,
                'email' => 'userfirst@monbudget.local'
            ];
            
            // ÉTAPE 2 : Créer l'utilisateur admin principal
            $stmt->execute([
                'username' => $userData['username'],
                'email' => $userData['email'],
                'password' => password_hash($userData['password'], PASSWORD_ARGON2ID, [
                    'memory_cost' => 65536,
                    'time_cost' => 4,
                    'threads' => 3
                ]),
                'role' => 'admin'
            ]);
            
            $this->steps[] = "Admin user '" . $userData['username'] . "' created successfully";
            return true;
        } catch (PDOException $e) {
            $this->errors[] = "Failed to create admin user: " . $e->getMessage();
            return false;
        }
    }
    
    /**
     * Générer un mot de passe fort aléatoire
     * 
     * @param int $length Longueur du mot de passe (défaut: 32)
     * @return string Mot de passe généré
     */
    private function generateStrongPassword(int $length = 32): string
    {
        // Caractères disponibles pour un mot de passe fort
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $symbols = '!@#$%^&*()-_=+[]{}|;:,.<>?';
        
        $allChars = $uppercase . $lowercase . $numbers . $symbols;
        
        // S'assurer qu'on a au moins un caractère de chaque type
        $password = '';
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $symbols[random_int(0, strlen($symbols) - 1)];
        
        // Compléter avec des caractères aléatoires
        for ($i = 4; $i < $length; $i++) {
            $password .= $allChars[random_int(0, strlen($allChars) - 1)];
        }
        
        // Mélanger les caractères pour éviter un pattern prévisible
        $passwordArray = str_split($password);
        shuffle($passwordArray);
        
        return implode('', $passwordArray);
    }
    
    /**
     * Sauvegarder la configuration
     */
    public function saveConfiguration(array $config): bool
    {
        $configFile = dirname(__DIR__, 2) . '/config/database.php';
        $configDir = dirname($configFile);
        
        if (!file_exists($configDir)) {
            mkdir($configDir, 0755, true);
        }
        
        $content = "<?php\n\nreturn [\n";
        foreach ($config as $key => $value) {
            if (is_string($value)) {
                $content .= "    '$key' => '$value',\n";
            } elseif (is_bool($value)) {
                $content .= "    '$key' => " . ($value ? 'true' : 'false') . ",\n";
            } elseif (is_numeric($value)) {
                $content .= "    '$key' => $value,\n";
            }
        }
        $content .= "];\n";
        
        return file_put_contents($configFile, $content) !== false;
    }
    
    /**
     * Créer les dossiers nécessaires
     */
    public function createDirectories(): bool
    {
        $basePath = dirname(__DIR__, 2);
        
        $directories = [
            '/storage',
            '/storage/logs',
            '/storage/cache',
            '/storage/sessions',
            '/uploads',
            '/config'
        ];
        
        foreach ($directories as $dir) {
            $path = $basePath . $dir;
            if (!file_exists($path)) {
                if (!mkdir($path, 0755, true)) {
                    $this->errors[] = "Failed to create directory: $path";
                    return false;
                }
            }
        }
        
        $this->steps[] = "Required directories created";
        return true;
    }
    
    /**
     * Obtenir les erreurs
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
    
    /**
     * Obtenir les étapes complétées
     */
    public function getSteps(): array
    {
        return $this->steps;
    }
    
    /**
     * Charger les données d'exemple
     * 
     * @param array $config Configuration base de données
     * @param int $userId ID de l'utilisateur admin créé
     * @return bool Succès ou échec
     */
    public function loadSampleData(array $config, int $userId): bool
    {
        try {
            $dsn = sprintf(
                "%s:host=%s;port=%s;dbname=%s;charset=%s",
                $config['driver'] ?? 'mysql',
                $config['host'] ?? 'localhost',
                $config['port'] ?? '3306',
                $config['database'] ?? 'monbudget_v2',
                $config['charset'] ?? 'utf8mb4'
            );
            
            $pdo = new PDO(
                $dsn,
                $config['username'] ?? 'root',
                $config['password'] ?? '',
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                ]
            );
            
            $pdo->beginTransaction();
            
            // 1. Charger le fichier SQL statique (banques, catégories, tiers)
            $sqlFile = dirname(__DIR__, 2) . '/database_sample_data.sql';
            if (file_exists($sqlFile)) {
                $sql = file_get_contents($sqlFile);
                
                // Convertir en UTF-8 si nécessaire
                if (!mb_check_encoding($sql, 'UTF-8')) {
                    $sql = mb_convert_encoding($sql, 'UTF-8', mb_detect_encoding($sql));
                }
                
                $pdo->exec($sql);
                $this->steps[] = "Sample data: Static data loaded (banks, categories, suppliers)";
            }
            
            // 2. Créer des comptes d'exemple
            $comptes = [
                [
                    'nom' => 'Compte Courant',
                    'banque_id' => 1,
                    'numero_compte' => 'FR76 1390 6000 0123 4567 8901 234',
                    'type' => 'courant',
                    'solde_initial' => 2500.00
                ],
                [
                    'nom' => 'Compte Épargne',
                    'banque_id' => 1,
                    'numero_compte' => 'FR76 1390 6000 0987 6543 2109 876',
                    'type' => 'epargne',
                    'solde_initial' => 5000.00
                ],
                [
                    'nom' => 'Livret A',
                    'banque_id' => 2,
                    'numero_compte' => 'FR76 3000 3000 0111 2222 3333 444',
                    'type' => 'livret',
                    'solde_initial' => 3000.00
                ]
            ];
            
            $compteIds = [];
            foreach ($comptes as $compte) {
                $stmt = $pdo->prepare("
                    INSERT INTO comptes (user_id, banque_id, nom, numero_compte, type_compte, solde_initial, solde_actuel, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $userId,
                    $compte['banque_id'],
                    $compte['nom'],
                    $compte['numero_compte'],
                    $compte['type'],
                    $compte['solde_initial'],
                    $compte['solde_initial']
                ]);
                $compteIds[$compte['nom']] = $pdo->lastInsertId();
            }
            $this->steps[] = "Sample data: " . count($comptes) . " accounts created";
            
            // 2b. Créer des tiers (fournisseurs récurrents)
            $tiersList = [
                ['nom' => 'CARREFOUR', 'type' => 'debiteur'],
                ['nom' => 'AUCHAN', 'type' => 'debiteur'],
                ['nom' => 'TOTAL ENERGIES', 'type' => 'debiteur'],
                ['nom' => 'EDF', 'type' => 'debiteur'],
                ['nom' => 'FREE MOBILE', 'type' => 'debiteur'],
                ['nom' => 'SNCF', 'type' => 'debiteur'],
                ['nom' => 'PHARMACIE CENTRALE', 'type' => 'debiteur'],
                ['nom' => 'DECATHLON', 'type' => 'debiteur'],
                ['nom' => 'AMAZON', 'type' => 'debiteur'],
                ['nom' => 'NETFLIX', 'type' => 'debiteur']
            ];
            
            $tiersIds = [];
            foreach ($tiersList as $tiers) {
                $stmt = $pdo->prepare("
                    INSERT INTO tiers (user_id, nom, type, created_at)
                    VALUES (?, ?, ?, NOW())
                ");
                $stmt->execute([$userId, $tiers['nom'], $tiers['type']]);
                $tiersIds[$tiers['nom']] = $pdo->lastInsertId();
            }
            $this->steps[] = "Sample data: " . count($tiersList) . " suppliers created";
            
            // 3. Créer des budgets mensuels
            $moisActuel = (int)date('n');
            $anneeActuelle = (int)date('Y');
            
            $budgets = [
                ['categorie_id' => 20, 'montant' => 400.00, 'nom' => 'Alimentation'],
                ['categorie_id' => 30, 'montant' => 200.00, 'nom' => 'Transports'],
                ['categorie_id' => 40, 'montant' => 150.00, 'nom' => 'Loisirs']
            ];
            
            foreach ($budgets as $budget) {
                $stmt = $pdo->prepare("
                    INSERT INTO budgets (user_id, categorie_id, montant, periode, mois, annee, created_at)
                    VALUES (?, ?, ?, 'mensuel', ?, ?, NOW())
                ");
                $stmt->execute([
                    $userId,
                    $budget['categorie_id'],
                    $budget['montant'],
                    $moisActuel,
                    $anneeActuelle
                ]);
            }
            $this->steps[] = "Sample data: " . count($budgets) . " budgets created";
            
            // 4. Créer des règles d'automatisation
            $regles = [
                [
                    'nom' => 'Courses Carrefour',
                    'pattern' => 'CARREFOUR',
                    'type_pattern' => 'contient',
                    'action_categorie' => 21,
                    'action_tiers' => $tiersIds['CARREFOUR'] ?? null
                ],
                [
                    'nom' => 'Essence Total',
                    'pattern' => 'TOTAL',
                    'type_pattern' => 'contient',
                    'action_categorie' => 31,
                    'action_tiers' => $tiersIds['TOTAL ENERGIES'] ?? null
                ],
                [
                    'nom' => 'Facture EDF',
                    'pattern' => 'EDF',
                    'type_pattern' => 'contient',
                    'action_categorie' => 12,
                    'action_tiers' => $tiersIds['EDF'] ?? null
                ]
            ];
            
            foreach ($regles as $regle) {
                $stmt = $pdo->prepare("
                    INSERT INTO regles_automatisation 
                    (user_id, nom, pattern, type_pattern, action_categorie, action_tiers, actif, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, 1, NOW())
                ");
                $stmt->execute([
                    $userId,
                    $regle['nom'],
                    $regle['pattern'],
                    $regle['type_pattern'],
                    $regle['action_categorie'],
                    $regle['action_tiers']
                ]);
            }
            $this->steps[] = "Sample data: " . count($regles) . " automation rules created";
            
            // 5. Créer des transactions sur 3 mois
            $transactions = $this->generateSampleTransactions($userId, $compteIds, $tiersIds);
            $stmt = $pdo->prepare("
                INSERT INTO transactions 
                (user_id, compte_id, date_transaction, libelle, montant, type_operation, categorie_id, tiers_id, moyen_paiement, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            foreach ($transactions as $transaction) {
                $stmt->execute([
                    $userId,
                    $transaction['compte_id'],
                    $transaction['date'],
                    $transaction['libelle'],
                    $transaction['montant'],
                    $transaction['type'],
                    $transaction['categorie_id'],
                    $transaction['tiers_id'],
                    $transaction['moyen_paiement']
                ]);
            }
            $this->steps[] = "Sample data: " . count($transactions) . " transactions created";
            
            $pdo->commit();
            $this->steps[] = "Sample data loaded successfully";
            return true;
            
        } catch (PDOException $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->errors[] = "Failed to load sample data: " . $e->getMessage();
            return false;
        }
    }
    
    /**
     * Générer des transactions d'exemple réalistes
     * 
     * @param int $userId ID utilisateur
     * @param array $compteIds IDs des comptes créés
     * @param array $tiersIds IDs des tiers créés
     * @return array Tableau de transactions
     */
    private function generateSampleTransactions(int $userId, array $compteIds, array $tiersIds): array
    {
        $transactions = [];
        $compteCourant = $compteIds['Compte Courant'];
        
        // Générer 3 mois de transactions
        for ($mois = 0; $mois < 3; $mois++) {
            $dateDebut = strtotime("-$mois months", strtotime('first day of this month'));
            
            // Salaire (début de mois)
            $transactions[] = [
                'compte_id' => $compteCourant,
                'date' => date('Y-m-05', $dateDebut),
                'libelle' => 'VIREMENT SALAIRE NOVEMBRE',
                'montant' => 2800.00,
                'type' => 'credit',
                'categorie_id' => 1,
                'tiers_id' => null,
                'moyen_paiement' => 'virement'
            ];
            
            // Loyer (début de mois)
            $transactions[] = [
                'compte_id' => $compteCourant,
                'date' => date('Y-m-01', $dateDebut),
                'libelle' => 'PRELEVEMENT LOYER',
                'montant' => -850.00,
                'type' => 'debit',
                'categorie_id' => 11,
                'tiers_id' => null,
                'moyen_paiement' => 'prelevement'
            ];
            
            // EDF (milieu de mois)
            $transactions[] = [
                'compte_id' => $compteCourant,
                'date' => date('Y-m-15', $dateDebut),
                'libelle' => 'PRELEVEMENT EDF',
                'montant' => -65.00,
                'type' => 'debit',
                'categorie_id' => 12,
                'tiers_id' => $tiersIds['EDF'] ?? null,
                'moyen_paiement' => 'prelevement'
            ];
            
            // Free Mobile
            $transactions[] = [
                'compte_id' => $compteCourant,
                'date' => date('Y-m-10', $dateDebut),
                'libelle' => 'PRELEVEMENT FREE MOBILE',
                'montant' => -19.99,
                'type' => 'debit',
                'categorie_id' => 14,
                'tiers_id' => $tiersIds['FREE MOBILE'] ?? null,
                'moyen_paiement' => 'prelevement'
            ];
            
            // Courses aléatoires (5-8 par mois)
            $nbCourses = rand(5, 8);
            for ($i = 0; $i < $nbCourses; $i++) {
                $jour = rand(1, 28);
                $magasin = rand(0, 1) ? 'CARREFOUR' : 'AUCHAN';
                $tiersId = $magasin === 'CARREFOUR' ? ($tiersIds['CARREFOUR'] ?? null) : ($tiersIds['AUCHAN'] ?? null);
                
                $transactions[] = [
                    'compte_id' => $compteCourant,
                    'date' => date('Y-m-' . str_pad($jour, 2, '0', STR_PAD_LEFT), $dateDebut),
                    'libelle' => "CB $magasin",
                    'montant' => -rand(30, 120) - (rand(0, 99) / 100),
                    'type' => 'debit',
                    'categorie_id' => 21,
                    'tiers_id' => $tiersId,
                    'moyen_paiement' => 'carte'
                ];
            }
            
            // Essence (2-3 par mois)
            $nbEssence = rand(2, 3);
            for ($i = 0; $i < $nbEssence; $i++) {
                $jour = rand(1, 28);
                $transactions[] = [
                    'compte_id' => $compteCourant,
                    'date' => date('Y-m-' . str_pad($jour, 2, '0', STR_PAD_LEFT), $dateDebut),
                    'libelle' => 'CB TOTAL ENERGIES',
                    'montant' => -rand(40, 70) - (rand(0, 99) / 100),
                    'type' => 'debit',
                    'categorie_id' => 31,
                    'tiers_id' => $tiersIds['TOTAL ENERGIES'] ?? null,
                    'moyen_paiement' => 'carte'
                ];
            }
            
            // Restaurant (1-2 par mois)
            $nbRestau = rand(1, 2);
            for ($i = 0; $i < $nbRestau; $i++) {
                $jour = rand(1, 28);
                $transactions[] = [
                    'compte_id' => $compteCourant,
                    'date' => date('Y-m-' . str_pad($jour, 2, '0', STR_PAD_LEFT), $dateDebut),
                    'libelle' => 'CB RESTAURANT',
                    'montant' => -rand(20, 60) - (rand(0, 99) / 100),
                    'type' => 'debit',
                    'categorie_id' => 22,
                    'tiers_id' => null,
                    'moyen_paiement' => 'carte'
                ];
            }
            
            // Pharmacie (occasionnel)
            if (rand(0, 1)) {
                $transactions[] = [
                    'compte_id' => $compteCourant,
                    'date' => date('Y-m-' . str_pad(rand(1, 28), 2, '0', STR_PAD_LEFT), $dateDebut),
                    'libelle' => 'CB PHARMACIE CENTRALE',
                    'montant' => -rand(10, 40) - (rand(0, 99) / 100),
                    'type' => 'debit',
                    'categorie_id' => 52,
                    'tiers_id' => 7,
                    'moyen_paiement' => 'carte'
                ];
            }
            
            // Netflix
            $transactions[] = [
                'compte_id' => $compteCourant,
                'date' => date('Y-m-20', $dateDebut),
                'libelle' => 'PRELEVEMENT NETFLIX',
                'montant' => -13.49,
                'type' => 'debit',
                'categorie_id' => 43,
                'tiers_id' => 10,
                'moyen_paiement' => 'prelevement'
            ];
            
            // Virement épargne
            if ($mois < 2) {
                $transactions[] = [
                    'compte_id' => $compteCourant,
                    'date' => date('Y-m-25', $dateDebut),
                    'libelle' => 'VIREMENT EPARGNE',
                    'montant' => -300.00,
                    'type' => 'debit',
                    'categorie_id' => 70,
                    'tiers_id' => null,
                    'moyen_paiement' => 'virement'
                ];
            }
        }
        
        return $transactions;
    }
}

