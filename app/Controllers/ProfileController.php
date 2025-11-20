<?php

namespace MonBudget\Controllers;

use MonBudget\Core\Database;
use MonBudget\Services\PasswordPolicyService;
use App\Services\AuditLogService;

/**
 * Contrôleur de gestion du profil utilisateur
 * 
 * Gère le changement de mot de passe avec validation PCI DSS.
 * 
 * @package MonBudget\Controllers
 * @author MonBudget Security Team
 * @version 2.3.0
 */
class ProfileController extends BaseController
{
    /**
     * Afficher le formulaire de changement de mot de passe
     * 
     * @return void
     */
    public function showChangePassword(): void
    {
        if (!$this->isAuthenticated()) {
            $this->redirect('login');
        }
        
        // Vérifier si changement forcé (expiration)
        $forced = $_SESSION['must_change_password'] ?? false;
        
        $this->view('profile.change-password', [
            'forced' => $forced
        ]);
    }
    
    /**
     * Traiter le changement de mot de passe
     * 
     * Valide le mot de passe actuel, vérifie la conformité PCI DSS
     * du nouveau mot de passe, et met à jour la base de données.
     * 
     * @return void
     */
    public function changePassword(): void
    {
        if (!$this->isAuthenticated()) {
            $this->redirect('login');
        }
        
        if (!$this->validateCsrfOrFail('change-password')) return;
        
        $userId = $_SESSION['user_id'];
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $forced = $_SESSION['must_change_password'] ?? false;
        
        // Validation basique
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            flash('error', 'Tous les champs sont requis');
            $this->redirect('change-password');
        }
        
        if ($newPassword !== $confirmPassword) {
            flash('error', 'Les nouveaux mots de passe ne correspondent pas');
            $this->redirect('change-password');
        }
        
        // Récupérer utilisateur
        $user = Database::selectOne(
            "SELECT * FROM users WHERE id = ? LIMIT 1",
            [$userId]
        );
        
        if (!$user) {
            flash('error', 'Utilisateur non trouvé');
            $this->redirect('login');
        }
        
        // Vérifier mot de passe actuel
        if (!password_verify($currentPassword, $user['password'])) {
            flash('error', 'Mot de passe actuel incorrect');
            $this->redirect('change-password');
        }
        
        // Initialiser services PCI DSS
        $passwordPolicy = new PasswordPolicyService();
        $audit = new AuditLogService();
        
        // Valider nouveau mot de passe (PCI DSS 8.2.3)
        $validationErrors = $passwordPolicy->validatePassword($newPassword, $userId);
        if (!empty($validationErrors)) {
            foreach ($validationErrors as $error) {
                flash('error', $error);
            }
            $this->redirect('change-password');
        }
        
        // Vérifier historique (PCI DSS 8.2.5)
        if ($passwordPolicy->checkPasswordHistory($userId, $newPassword)) {
            flash('error', 'Vous ne pouvez pas réutiliser un de vos 5 derniers mots de passe');
            $this->redirect('change-password');
        }
        
        // Mettre à jour mot de passe
        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $updated = Database::update(
            "UPDATE users 
             SET password = ?, 
                 last_password_change = NOW(), 
                 password_expires_at = DATE_ADD(NOW(), INTERVAL 90 DAY),
                 must_change_password = 0
             WHERE id = ?",
            [$newPasswordHash, $userId]
        );
        
        if ($updated > 0) {
            // Enregistrer dans historique
            $passwordPolicy->savePasswordHistory($userId, $newPasswordHash);
            
            // Log changement (PCI DSS 10.2.5)
            $audit->logPasswordChange($userId, $forced);
            
            // Nettoyer flag session
            unset($_SESSION['must_change_password']);
            
            flash('success', 'Mot de passe modifié avec succès');
            $this->redirect('dashboard');
        } else {
            flash('error', 'Erreur lors de la modification du mot de passe');
            $this->redirect('change-password');
        }
    }
    
    /**
     * Afficher le profil utilisateur
     * 
     * @return void
     */
    public function show(): void
    {
        if (!$this->isAuthenticated()) {
            $this->redirect('login');
        }
        
        $userId = $_SESSION['user_id'];
        
        // Récupérer informations utilisateur
        $user = Database::selectOne(
            "SELECT id, username, email, role, created_at, last_password_change, password_expires_at 
             FROM users WHERE id = ? LIMIT 1",
            [$userId]
        );
        
        if (!$user) {
            flash('error', 'Utilisateur non trouvé');
            $this->redirect('login');
        }
        
        // Calculer jours avant expiration mot de passe
        $passwordPolicy = new PasswordPolicyService();
        $isExpired = $passwordPolicy->isPasswordExpired($userId);
        
        $daysUntilExpiration = null;
        if ($user['password_expires_at']) {
            $expirationDate = new \DateTime($user['password_expires_at']);
            $now = new \DateTime();
            $interval = $now->diff($expirationDate);
            $daysUntilExpiration = $interval->invert ? 0 : $interval->days;
        }
        
        $this->view('profile.show', [
            'user' => $user,
            'isPasswordExpired' => $isExpired,
            'daysUntilExpiration' => $daysUntilExpiration
        ]);
    }
    
    /**
     * Mettre à jour le profil utilisateur
     * 
     * @return void
     */
    public function update(): void
    {
        if (!$this->isAuthenticated()) {
            $this->redirect('login');
        }
        
        if (!$this->validateCsrfOrFail('update-profile')) return;
        
        $userId = $_SESSION['user_id'];
        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        
        // Validation
        if (empty($username) || empty($email)) {
            flash('error', 'Nom d\'utilisateur et email requis');
            $this->redirect('profile');
        }
        
        // Vérifier email unique
        $existing = Database::selectOne(
            "SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1",
            [$email, $userId]
        );
        
        if ($existing) {
            flash('error', 'Cet email est déjà utilisé par un autre utilisateur');
            $this->redirect('profile');
        }
        
        // Récupérer anciennes valeurs pour audit
        $oldUser = Database::selectOne(
            "SELECT username, email FROM users WHERE id = ? LIMIT 1",
            [$userId]
        );
        
        // Mettre à jour
        $updated = Database::update(
            "UPDATE users SET username = ?, email = ? WHERE id = ?",
            [$username, $email, $userId]
        );
        
        if ($updated > 0) {
            // Log modification (PCI DSS 10.2.5)
            $audit = new AuditLogService();
            $audit->logUpdate('users', $userId, 
                ['username' => $oldUser['username'], 'email' => $oldUser['email']],
                ['username' => $username, 'email' => $email]
            );
            
            // Mettre à jour session
            $_SESSION['user']['username'] = $username;
            $_SESSION['user']['email'] = $email;
            
            flash('success', 'Profil mis à jour avec succès');
        } else {
            flash('info', 'Aucune modification détectée');
        }
        
        $this->redirect('profile');
    }
}
