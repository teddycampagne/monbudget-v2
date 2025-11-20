<?php

namespace MonBudget\Controllers;

use MonBudget\Core\Database;
use MonBudget\Services\RecurrenceService;
use MonBudget\Services\PasswordPolicyService;
use MonBudget\Services\AuditLogService;

/**
 * Contrôleur d'authentification
 * 
 * Gère toutes les opérations liées à l'authentification des utilisateurs :
 * connexion, déconnexion, inscription, récupération de mot de passe.
 * Implémente la validation CSRF et la gestion sécurisée des sessions.
 * Exécute automatiquement les récurrences échues lors du login.
 * 
 * @package MonBudget\Controllers
 * @author MonBudget
 * @version 2.2.0
 */
class AuthController extends BaseController
{
    /**
     * Afficher le formulaire de connexion
     * 
     * Redirige vers le dashboard si l'utilisateur est déjà connecté.
     * 
     * @return void
     */
    public function showLogin(): void
    {
        if ($this->isAuthenticated()) {
            $this->redirect('dashboard');
        }
        
        $this->view('auth.login');
    }
    
    /**
     * Traiter la soumission du formulaire de connexion
     * 
     * Vérifie les identifiants, crée la session utilisateur et gère
     * le cookie "Remember me" si demandé. Régénère l'ID de session
     * pour la sécurité après connexion réussie.
     * 
     * ⚠️ PCI DSS: Vérifie verrouillage compte et expiration mot de passe.
     * 
     * @return void
     */
    public function login(): void
    {
        if (!$this->validateCsrfOrFail('login')) return;
        
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);
        
        if (empty($email) || empty($password)) {
            flash('error', 'Email et mot de passe requis');
            $this->redirect('login');
        }
        
        // Récupérer l'utilisateur
        $user = Database::selectOne(
            "SELECT * FROM users WHERE email = ? LIMIT 1",
            [$email]
        );
        
        // Initialiser services PCI DSS
        $passwordPolicy = new PasswordPolicyService();
        $audit = new AuditLogService();
        
        // Vérifier si utilisateur existe
        if (!$user) {
            // Log tentative avec email inconnu
            $audit->logLogin($email, false, null, 'Unknown email');
            flash('error', 'Identifiants incorrects');
            $this->redirect('login');
        }
        
        // Vérifier verrouillage compte (PCI DSS 8.3)
        $lockoutStatus = PasswordPolicyService::checkLockout($email);
        if ($lockoutStatus['locked']) {
            $audit->logLogin($email, false, $user['id'], 'Account locked');
            $remainingMinutes = ceil($lockoutStatus['remaining_time'] / 60);
            flash('error', "Compte verrouillé suite à trop de tentatives échouées. Réessayez dans {$remainingMinutes} minute(s).");
            $this->redirect('login');
        }
        
        // Vérifier mot de passe
        if (!password_verify($password, $user['password'])) {
            // Enregistrer tentative échouée
            PasswordPolicyService::recordFailedAttempt($email);
            $audit->logLogin($email, false, $user['id'], 'Invalid password');
            
            // Vérifier si compte doit être verrouillé
            $lockoutStatus = PasswordPolicyService::checkLockout($email);
            if ($lockoutStatus['locked']) {
                flash('error', 'Compte verrouillé suite à trop de tentatives échouées');
            } else {
                $remainingAttempts = PasswordPolicyService::getRemainingAttempts($email);
                flash('error', "Identifiants incorrects. {$remainingAttempts} tentative(s) restante(s).");
            }
            
            $this->redirect('login');
        }
        
        // Réinitialiser compteur tentatives échouées
        Database::update(
            "UPDATE users SET failed_login_attempts = 0, locked_until = NULL WHERE id = ?",
            [$user['id']]
        );
        PasswordPolicyService::resetAttempts($email);
        
        // Vérifier expiration mot de passe (PCI DSS 8.2.4)
        if (PasswordPolicyService::isExpired($user['id'])) {
            $_SESSION['user_id_temp'] = $user['id'];
            $_SESSION['must_change_password'] = true;
            flash('warning', 'Votre mot de passe a expiré. Vous devez le changer.');
            $this->redirect('change-password');
        }
        
        // Régénérer l'ID de session pour la sécurité
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user'] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role'] ?? 'user'
        ];
        
        // Cookie "Remember me"
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            setcookie('remember_token', $token, time() + (86400 * 30), '/'); // 30 jours
            
            // Stocker le token en BDD (à implémenter)
        }
        
        // Log succès connexion (PCI DSS 10.2.5)
        $audit->logLogin($email, true, $user['id']);
        
        // ✨ NOUVEAU : Exécuter automatiquement les récurrences échues
        // Appel silencieux pour tous les utilisateurs (pas seulement le user connecté)
        // Protection anti-doublons intégrée dans le service
        try {
            $recurrenceService = new RecurrenceService();
            $stats = $recurrenceService->executeAllPendingRecurrences();
            
            // Afficher un message uniquement s'il y a des exécutions
            if ($stats['total_executed'] > 0) {
                flash('info', sprintf(
                    '%d récurrence(s) automatique(s) exécutée(s) avec succès',
                    $stats['total_executed']
                ));
            }
        } catch (\Exception $e) {
            // Log l'erreur mais ne bloque pas la connexion
            error_log("Erreur exécution récurrences auto: " . $e->getMessage());
        }
        
        $this->redirect('dashboard');
    }
    
    /**
     * Déconnexion de l'utilisateur
     * 
     * Nettoie complètement la session, supprime les cookies (session et "Remember me"),
     * et détruit la session. Redirige ensuite vers la page de connexion.
     * 
     * ⚠️ PCI DSS: Log de déconnexion pour audit.
     * 
     * @return void
     */
    public function logout(): void
    {
        // Log déconnexion avant destruction session (PCI DSS 10.2.3)
        if (isset($_SESSION['user_id'])) {
            $audit = new AuditLogService();
            $audit->logLogout($_SESSION['user_id']);
        }
        
        // Nettoyer la session
        $_SESSION = [];
        
        // Détruire le cookie de session
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        // Détruire le cookie "Remember me"
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/');
        }
        
        session_destroy();
        
        $this->redirect('login');
    }
    
    /**
     * Afficher le formulaire d'inscription
     * 
     * Redirige vers le dashboard si l'utilisateur est déjà connecté.
     * 
     * @return void
     */
    public function showRegister(): void
    {
        if ($this->isAuthenticated()) {
            $this->redirect('dashboard');
        }
        
        $this->view('auth.register');
    }
    
    /**
     * Traiter la soumission du formulaire d'inscription
     * 
     * Valide les données (username, email, password), vérifie que l'email
     * n'est pas déjà utilisé, crée le compte avec un mot de passe haché,
     * et redirige vers la page de connexion.
     * 
     * ⚠️ PCI DSS: Validation stricte du mot de passe (longueur, complexité).
     * 
     * @return void
     */
    public function register(): void
    {
        if (!$this->validateCsrfOrFail('register')) return;
        
        $data = $this->validate([
            'username' => 'required|min:3|max:50',
            'email' => 'required|email',
            'password' => 'required|min:8',
            'password_confirm' => 'required'
        ]);
        
        if ($data['password'] !== $data['password_confirm']) {
            flash('error', 'Les mots de passe ne correspondent pas');
            $this->redirect('register');
        }
        
        // Initialiser services PCI DSS
        $passwordPolicy = new PasswordPolicyService();
        $audit = new AuditLogService();
        
        // Valider mot de passe (PCI DSS 8.2.3)
        $validationErrors = $passwordPolicy->validatePassword($data['password']);
        if (!empty($validationErrors)) {
            foreach ($validationErrors as $error) {
                flash('error', $error);
            }
            $this->redirect('register');
        }
        
        // Vérifier si l'email existe déjà
        $existing = Database::selectOne(
            "SELECT id FROM users WHERE email = ? LIMIT 1",
            [$data['email']]
        );
        
        if ($existing) {
            flash('error', 'Cet email est déjà utilisé');
            $this->redirect('register');
        }
        
        // Créer l'utilisateur
        $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
        $userId = Database::insert(
            "INSERT INTO users (username, email, password, role, created_at, last_password_change, password_expires_at) 
             VALUES (?, ?, ?, 'user', NOW(), NOW(), DATE_ADD(NOW(), INTERVAL 90 DAY))",
            [$data['username'], $data['email'], $passwordHash]
        );
        
        if ($userId) {
            // Enregistrer dans historique des mots de passe
            $passwordPolicy->savePasswordHistory($userId, $passwordHash);
            
            // Log création compte (PCI DSS 10.2.1)
            $audit->logCreate('users', $userId, [
                'username' => $data['username'],
                'email' => $data['email']
            ]);
            
            flash('success', 'Compte créé avec succès. Vous pouvez maintenant vous connecter.');
            $this->redirect('login');
        } else {
            flash('error', 'Erreur lors de la création du compte');
            $this->redirect('register');
        }
    }
    
    /**
     * Afficher le formulaire de récupération de mot de passe
     * 
     * @return void
     */
    public function showForgotPassword(): void
    {
        $this->view('auth.forgot-password');
    }
    
    /**
     * Traiter la demande de réinitialisation de mot de passe
     * 
     * Vérifie l'existence de l'utilisateur et génère un token de réinitialisation.
     * Utilise un message générique pour éviter l'énumération d'adresses email.
     * Note : L'envoi d'email et le stockage du token sont à implémenter.
     * 
     * @return void
     * @todo Implémenter la table password_resets et l'envoi d'email
     */
    public function forgotPassword(): void
    {
        if (!$this->verifyCsrf()) {
            flash('error', 'Token CSRF invalide');
            $this->redirect('forgot-password');
        }
        
        $email = $_POST['email'] ?? '';
        
        if (empty($email)) {
            flash('error', 'Email requis');
            $this->redirect('forgot-password');
        }
        
        // Vérifier si l'utilisateur existe
        $user = Database::selectOne(
            "SELECT id, email FROM users WHERE email = ? LIMIT 1",
            [$email]
        );
        
        if ($user) {
            // Générer un token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 3600); // 1 heure
            
            // Stocker le token (à implémenter avec une table password_resets)
            // Pour l'instant, message de succès générique
        }
        
        // Message générique pour éviter l'énumération d'emails
        flash('success', 'Si cet email existe, vous recevrez un lien de réinitialisation');
        $this->redirect('login');
    }
}
