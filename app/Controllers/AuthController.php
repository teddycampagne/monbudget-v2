<?php

namespace MonBudget\Controllers;

use MonBudget\Core\Database;
use MonBudget\Services\RecurrenceService;
use MonBudget\Services\PasswordPolicyService;
use MonBudget\Services\AuditLogService;
use MonBudget\Services\EmailService;

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
            // Vérifier que l'utilisateur existe encore en base avant de logger
            $userExists = Database::selectOne(
                "SELECT id FROM users WHERE id = ?",
                [$_SESSION['user_id']]
            );

            if ($userExists) {
                $audit = new AuditLogService();
                $audit->logLogout($_SESSION['user_id']);
            }
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
     * Vérifie l'existence de l'utilisateur, génère un token de réinitialisation,
     * stocke le token et envoie l'email de réinitialisation.
     * Utilise un message générique pour éviter l'énumération d'adresses email.
     * 
     * @return void
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
            "SELECT id, username, email FROM users WHERE email = ? LIMIT 1",
            [$email]
        );
        
        if ($user) {
            // Générer un token sécurisé
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 3600); // 1 heure

            // Stocker le token dans la base de données
            Database::insert(
                "INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)",
                [$user['id'], password_hash($token, PASSWORD_DEFAULT), $expires]
            );

            // Envoyer l'email de réinitialisation
            $emailService = new EmailService();
            $emailSent = $emailService->sendPasswordReset(
                $user['email'],
                $token,
                $user['username']
            );

            if ($emailSent) {
                // Log succès seulement en mode debug
                if (config('app.debug', false)) {
                    error_log("Email de réinitialisation envoyé à: " . $user['email']);
                }
            } else {
                // Log échec seulement en mode debug
                if (config('app.debug', false)) {
                    error_log("Échec envoi email de réinitialisation à: " . $user['email']);
                }
                // Créer un ticket admin pour signaler l'échec d'envoi
                $this->createEmailFailureTicket($user['email'], $user['username']);
            }
        }
        
        // Message générique pour éviter l'énumération d'emails
        flash('success', 'Si cet email existe, vous recevrez un lien de réinitialisation');
        $this->redirect('login');
    }    /**
     * Afficher le formulaire de réinitialisation de mot de passe
     *
     * @return void
     */
    public function showResetPassword(): void
    {
        $token = $_GET['token'] ?? '';

        if (empty($token)) {
            flash('error', 'Token de réinitialisation manquant');
            $this->redirect('login');
        }

        // Vérifier si le token existe et n'est pas expiré
        $reset = Database::selectOne(
            "SELECT pr.*, u.username FROM password_resets pr
             JOIN users u ON pr.user_id = u.id
             WHERE pr.token IS NOT NULL AND pr.used_at IS NULL AND pr.expires_at > NOW()
             ORDER BY pr.created_at DESC LIMIT 1",
            []
        );

        if (!$reset) {
            flash('error', 'Token de réinitialisation invalide ou expiré');
            $this->redirect('login');
        }

        // Vérifier le token
        if (!password_verify($token, $reset['token'])) {
            flash('error', 'Token de réinitialisation invalide');
            $this->redirect('login');
        }

        $this->view('auth.reset-password', [
            'token' => $token,
            'username' => $reset['username']
        ]);
    }

    /**
     * Traiter la réinitialisation de mot de passe
     *
     * @return void
     */
    public function resetPassword(): void
    {
        if (!$this->verifyCsrf()) {
            flash('error', 'Token CSRF invalide');
            $this->redirect('login');
        }

        $token = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        if (empty($token) || empty($password) || empty($passwordConfirm)) {
            flash('error', 'Tous les champs sont requis');
            $this->redirect('reset-password?token=' . urlencode($token));
        }

        if ($password !== $passwordConfirm) {
            flash('error', 'Les mots de passe ne correspondent pas');
            $this->redirect('reset-password?token=' . urlencode($token));
        }

        // Vérifier la politique de mot de passe
        $passwordPolicy = new PasswordPolicyService();
        $policyCheck = $passwordPolicy->validatePassword($password);

        if (!$policyCheck['valid']) {
            flash('error', 'Mot de passe invalide: ' . implode(', ', $policyCheck['errors']));
            $this->redirect('reset-password?token=' . urlencode($token));
        }

        // Vérifier le token et récupérer l'utilisateur
        $reset = Database::selectOne(
            "SELECT pr.*, u.username FROM password_resets pr
             JOIN users u ON pr.user_id = u.id
             WHERE pr.token IS NOT NULL AND pr.used_at IS NULL AND pr.expires_at > NOW()
             ORDER BY pr.created_at DESC LIMIT 1",
            []
        );

        if (!$reset || !password_verify($token, $reset['token'])) {
            flash('error', 'Token de réinitialisation invalide ou expiré');
            $this->redirect('login');
        }

        // Hasher le nouveau mot de passe
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Mettre à jour le mot de passe
        Database::update(
            "UPDATE users SET password = ?, password_changed_at = NOW() WHERE id = ?",
            [$hashedPassword, $reset['user_id']]
        );

        // Marquer le token comme utilisé
        Database::update(
            "UPDATE password_resets SET used_at = NOW() WHERE id = ?",
            [$reset['id']]
        );

        // Nettoyer les anciens tokens expirés
        Database::execute(
            "DELETE FROM password_resets WHERE expires_at < NOW() OR used_at IS NOT NULL"
        );

        // Logger l'action
        $audit = new AuditLogService();
        $audit->logPasswordChange($reset['user_id'], 'password_reset', 'Mot de passe réinitialisé via email');

        flash('success', 'Mot de passe réinitialisé avec succès. Vous pouvez maintenant vous connecter.');
        $this->redirect('login');
    }

    /**
     * Créer un ticket admin en cas d'échec d'envoi d'email de réinitialisation
     *
     * @param string $userEmail Email de l'utilisateur
     * @param string $username Nom d'utilisateur
     * @return void
     */
    private function createEmailFailureTicket(string $userEmail, string $username): void
    {
        try {
            // Récupérer l'ID de l'utilisateur
            $user = Database::selectOne(
                "SELECT id FROM users WHERE email = ? LIMIT 1",
                [$userEmail]
            );

            if (!$user) {
                error_log("Utilisateur non trouvé pour créer ticket d'échec email: $userEmail");
                return;
            }

            // Créer le ticket dans la base de données
            $ticketId = Database::insert(
                "INSERT INTO admin_tickets (user_id, subject, message, category, priority, status) VALUES (?, ?, ?, ?, ?, ?)",
                [
                    $user['id'], // Utiliser l'ID de l'utilisateur réel
                    "Échec envoi email de réinitialisation de mot de passe",
                    "L'utilisateur $username ($userEmail) a demandé une réinitialisation de mot de passe, mais l'envoi de l'email a échoué.\n\n" .
                    "Veuillez vérifier la configuration email et contacter l'utilisateur manuellement si nécessaire.\n\n" .
                    "Email: $userEmail\n" .
                    "Utilisateur: $username\n" .
                    "Date: " . date('Y-m-d H:i:s'),
                    "system",
                    "high",
                    "open"
                ]
            );

            // Notifier les administrateurs
            $this->notifyAdminsOfEmailFailure($ticketId, $userEmail, $username);

            error_log("Ticket admin #$ticketId créé pour échec envoi email à: $userEmail");
        } catch (\Exception $e) {
            error_log("Erreur lors de la création du ticket admin pour échec email: " . $e->getMessage());
        }
    }

    /**
     * Notifier les administrateurs d'un échec d'envoi d'email
     *
     * @param int $ticketId ID du ticket créé
     * @param string $userEmail Email de l'utilisateur concerné
     * @param string $username Nom d'utilisateur
     * @return void
     */
    private function notifyAdminsOfEmailFailure(int $ticketId, string $userEmail, string $username): void
    {
        // Récupérer tous les administrateurs
        $admins = Database::select(
            "SELECT id, username, email FROM users WHERE role IN ('admin', 'super_admin')"
        );

        if (empty($admins)) {
            error_log("Aucun administrateur trouvé pour notification d'échec email");
            return;
        }

        $emailService = new EmailService();

        foreach ($admins as $admin) {
            $emailService->sendAdminTicket(
                $admin['email'],
                $ticketId,
                "Échec envoi email de réinitialisation - $username",
                "Un utilisateur a demandé une réinitialisation de mot de passe, mais l'envoi de l'email a échoué.\n\n" .
                "Détails :\n" .
                "- Utilisateur: $username\n" .
                "- Email: $userEmail\n" .
                "- Date: " . date('Y-m-d H:i:s') . "\n\n" .
                "Veuillez vérifier la configuration email et contacter l'utilisateur si nécessaire.",
                $admin['username']
            );
        }
    }
}
