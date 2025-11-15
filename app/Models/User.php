<?php

namespace MonBudget\Models;

use MonBudget\Core\Database;

/**
 * Class User
 * 
 * Modèle de gestion des utilisateurs de l'application.
 * 
 * Gère l'authentification, les rôles, les permissions et les profils utilisateurs.
 * Hérite de BaseModel pour les opérations CRUD génériques.
 * 
 * @package MonBudget\Models
 * @extends BaseModel
 * 
 * @property int $id Identifiant unique de l'utilisateur
 * @property string $username Nom d'utilisateur unique
 * @property string $email Email unique de l'utilisateur
 * @property string $password Mot de passe hashé (bcrypt)
 * @property string $role Rôle de l'utilisateur ('user' ou 'admin')
 * @property bool $active Statut actif/inactif du compte
 * @property string $created_at Date de création du compte
 * @property string|null $last_login Date de dernière connexion
 */
class User extends BaseModel
{
    /** @var string Table de la base de données */
    protected static string $table = 'users';
    
    /** @var string Clé primaire de la table */
    protected static string $primaryKey = 'id';
    
    /**
     * Recherche un utilisateur par son adresse email
     * 
     * Utilisé principalement lors de l'authentification et de la vérification
     * de l'unicité des emails.
     * 
     * @param string $email Adresse email de l'utilisateur à rechercher
     * @return array|null Données de l'utilisateur ou null si non trouvé
     * 
     * @example
     * $user = User::findByEmail('john.doe@example.com');
     * if ($user) {
     *     echo "Utilisateur trouvé : " . $user['username'];
     * }
     */
    public static function findByEmail(string $email): ?array
    {
        $query = "SELECT * FROM " . self::$table . " WHERE email = ? LIMIT 1";
        return Database::selectOne($query, [$email]);
    }
    
    /**
     * Recherche un utilisateur par son nom d'utilisateur
     * 
     * Utilisé pour l'authentification par username et la vérification
     * de l'unicité des noms d'utilisateur.
     * 
     * @param string $username Nom d'utilisateur à rechercher
     * @return array|null Données de l'utilisateur ou null si non trouvé
     * 
     * @example
     * $user = User::findByUsername('johndoe');
     * if ($user && User::verifyPassword($password, $user['password'])) {
     *     // Authentification réussie
     * }
     */
    public static function findByUsername(string $username): ?array
    {
        $query = "SELECT * FROM " . self::$table . " WHERE username = ? LIMIT 1";
        return Database::selectOne($query, [$username]);
    }
    
    /**
     * Crée un nouvel utilisateur avec hashage automatique du mot de passe
     * 
     * Le mot de passe est automatiquement hashé avec PASSWORD_DEFAULT (bcrypt).
     * La date de création est définie automatiquement.
     * Le rôle par défaut 'user' est attribué si non spécifié.
     * 
     * @param array $data Données de l'utilisateur à créer
     *                    - string $data['username'] Nom d'utilisateur (requis)
     *                    - string $data['email'] Email (requis)
     *                    - string $data['password'] Mot de passe en clair (requis)
     *                    - string $data['role'] Rôle ('user' ou 'admin', défaut: 'user')
     * @return int ID de l'utilisateur créé
     * 
     * @throws \PDOException Si l'insertion échoue (email/username déjà existant)
     * 
     * @example
     * $userId = User::createUser([
     *     'username' => 'johndoe',
     *     'email' => 'john@example.com',
     *     'password' => 'SecurePass123!',
     *     'role' => 'user'
     * ]);
     */
    public static function createUser(array $data): int
    {
        // Hasher le mot de passe
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        // Définir la date de création
        $data['created_at'] = date('Y-m-d H:i:s');
        
        // Définir le rôle par défaut si non spécifié
        if (!isset($data['role'])) {
            $data['role'] = 'user';
        }
        
        return self::create($data);
    }
    
    /**
     * Vérifie la correspondance entre un mot de passe en clair et son hash
     * 
     * Utilise password_verify pour comparer le mot de passe fourni
     * avec le hash stocké en base de données.
     * 
     * @param string $password Mot de passe en clair à vérifier
     * @param string $hash Hash du mot de passe stocké en BDD
     * @return bool True si le mot de passe correspond, false sinon
     * 
     * @example
     * $user = User::findByEmail($email);
     * if ($user && User::verifyPassword($_POST['password'], $user['password'])) {
     *     // Connexion réussie
     *     $_SESSION['user'] = $user;
     * }
     */
    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
    
    /**
     * Met à jour le mot de passe d'un utilisateur
     * 
     * Le nouveau mot de passe est automatiquement hashé avec PASSWORD_DEFAULT.
     * Utile pour la fonctionnalité de changement/réinitialisation de mot de passe.
     * 
     * @param int $userId ID de l'utilisateur dont on veut changer le mot de passe
     * @param string $newPassword Nouveau mot de passe en clair
     * @return bool True si la mise à jour a réussi, false sinon
     * 
     * @example
     * if (User::verifyPassword($oldPassword, $user['password'])) {
     *     User::updatePassword($user['id'], $newPassword);
     *     echo "Mot de passe changé avec succès";
     * }
     */
    public static function updatePassword(int $userId, string $newPassword): bool
    {
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $query = "UPDATE " . self::$table . " SET password = ? WHERE id = ?";
        return Database::update($query, [$hash, $userId]) > 0;
    }
    
    /**
     * Vérifie si un email existe déjà en base de données
     * 
     * Utilisé lors de l'inscription pour garantir l'unicité des emails.
     * 
     * @param string $email Email à vérifier
     * @return bool True si l'email existe déjà, false sinon
     * 
     * @example
     * if (User::emailExists($_POST['email'])) {
     *     throw new \Exception("Cet email est déjà utilisé");
     * }
     */
    public static function emailExists(string $email): bool
    {
        return self::findByEmail($email) !== null;
    }
    
    /**
     * Vérifie si un nom d'utilisateur existe déjà en base de données
     * 
     * Utilisé lors de l'inscription pour garantir l'unicité des usernames.
     * 
     * @param string $username Nom d'utilisateur à vérifier
     * @return bool True si le username existe déjà, false sinon
     * 
     * @example
     * if (User::usernameExists($_POST['username'])) {
     *     throw new \Exception("Ce nom d'utilisateur est déjà pris");
     * }
     */
    public static function usernameExists(string $username): bool
    {
        return self::findByUsername($username) !== null;
    }
    
    /**
     * Récupère tous les utilisateurs actifs
     * 
     * Retourne uniquement les utilisateurs dont le champ 'active' vaut 1.
     * Résultats triés par nom d'utilisateur par ordre alphabétique.
     * 
     * @return array Liste des utilisateurs actifs
     * 
     * @example
     * $activeUsers = User::getActiveUsers();
     * foreach ($activeUsers as $user) {
     *     echo "{$user['username']} - {$user['email']}\n";
     * }
     */
    public static function getActiveUsers(): array
    {
        $query = "SELECT * FROM " . self::$table . " WHERE active = 1 ORDER BY username ASC";
        return Database::select($query);
    }
    
    /**
     * Récupère tous les utilisateurs ayant le rôle d'administrateur
     * 
     * Retourne les utilisateurs dont le champ 'role' vaut 'admin'.
     * Résultats triés par nom d'utilisateur par ordre alphabétique.
     * 
     * @return array Liste des administrateurs
     * 
     * @example
     * $admins = User::getAdmins();
     * echo "Nombre d'administrateurs : " . count($admins);
     */
    public static function getAdmins(): array
    {
        $query = "SELECT * FROM " . self::$table . " WHERE role = 'admin' ORDER BY username ASC";
        return Database::select($query);
    }
}
