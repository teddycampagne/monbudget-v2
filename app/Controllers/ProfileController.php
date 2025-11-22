<?php

namespace MonBudget\Controllers;

use MonBudget\Core\Database;
use MonBudget\Services\PasswordPolicyService;
use MonBudget\Services\AuditLogService;

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
        
        // Initialiser service audit PCI DSS
        $audit = new AuditLogService();
        
        // Valider nouveau mot de passe (PCI DSS 8.2.3)
        $validationErrors = PasswordPolicyService::validate($newPassword);
        if (!empty($validationErrors)) {
            foreach ($validationErrors as $error) {
                flash('error', $error);
            }
            $this->redirect('change-password');
        }
        
        // Vérifier historique (PCI DSS 8.2.5)
        if (PasswordPolicyService::isInHistory($userId, $newPassword)) {
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
            PasswordPolicyService::addToHistory($userId, $newPasswordHash);
            
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
            "SELECT id, username, email, role, created_at, last_password_change 
             FROM users WHERE id = ? LIMIT 1",
            [$userId]
        );
        
        if (!$user) {
            flash('error', 'Utilisateur non trouvé');
            $this->redirect('login');
        }
        
        // Calculer jours avant expiration mot de passe
        $isExpired = PasswordPolicyService::isExpired($userId);
        
        $daysUntilExpiration = null;
        if ($user['last_password_change']) {
            $changedAt = strtotime($user['last_password_change']);
            $expiresAt = strtotime('+' . PasswordPolicyService::MAX_AGE_DAYS . ' days', $changedAt);
            $now = time();
            $daysUntilExpiration = max(0, floor(($expiresAt - $now) / 86400));
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
    
    /**
     * Afficher les paramètres de notifications
     * 
     * @return void
     */
    public function notifications(): void
    {
        if (!$this->isAuthenticated()) {
            $this->redirect('login');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->updateNotifications();
            return;
        }
        
        $this->view('profile.notifications');
    }
    
    /**
     * Mettre à jour les paramètres de notifications
     * 
     * @return void
     */
    private function updateNotifications(): void
    {
        $userId = $_SESSION['user_id'];
        
        // Récupérer les données du formulaire
        $data = [
            'budget_alert_enabled' => isset($_POST['budget_alert_enabled']) ? 1 : 0,
            'budget_threshold_80' => isset($_POST['budget_threshold_80']) ? 1 : 0,
            'budget_threshold_90' => isset($_POST['budget_threshold_90']) ? 1 : 0,
            'budget_exceeded' => isset($_POST['budget_exceeded']) ? 1 : 0,
            'budget_exceeded_method' => $_POST['budget_exceeded_method'] ?? 'both',
            'weekly_summary' => isset($_POST['weekly_summary']) ? 1 : 0,
            'monthly_summary' => isset($_POST['monthly_summary']) ? 1 : 0,
            'notify_email' => isset($_POST['notify_email']) ? 1 : 0,
            'notify_web' => isset($_POST['notify_web']) ? 1 : 0,
            'max_emails_per_day' => (int)($_POST['max_emails_per_day'] ?? 5)
        ];
        
        // Validation
        $validMethods = ['none', 'web_only', 'email_only', 'both'];
        if (!in_array($data['budget_exceeded_method'], $validMethods)) {
            $data['budget_exceeded_method'] = 'both'; // Valeur par défaut
        }
        
        if ($data['max_emails_per_day'] < 1 || $data['max_emails_per_day'] > 20) {
            flash('error', 'Le nombre maximum d\'emails par jour doit être entre 1 et 20');
            $this->redirect('profile/notifications');
        }
        
        // Vérifier si les paramètres existent déjà
        $existing = Database::selectOne(
            "SELECT id FROM notifications_settings WHERE user_id = ? LIMIT 1",
            [$userId]
        );
        
        if ($existing) {
            // Mise à jour
            $updated = Database::update(
                "UPDATE notifications_settings SET 
                    budget_alert_enabled = ?,
                    budget_threshold_80 = ?,
                    budget_threshold_90 = ?,
                    budget_exceeded = ?,
                    budget_exceeded_method = ?,
                    weekly_summary = ?,
                    monthly_summary = ?,
                    notify_email = ?,
                    notify_web = ?,
                    max_emails_per_day = ?
                WHERE user_id = ?",
                [
                    $data['budget_alert_enabled'],
                    $data['budget_threshold_80'],
                    $data['budget_threshold_90'],
                    $data['budget_exceeded'],
                    $data['budget_exceeded_method'],
                    $data['weekly_summary'],
                    $data['monthly_summary'],
                    $data['notify_email'],
                    $data['notify_web'],
                    $data['max_emails_per_day'],
                    $userId
                ]
            );
        } else {
            // Insertion
            Database::insert(
                "INSERT INTO notifications_settings 
                    (user_id, budget_alert_enabled, budget_threshold_80, budget_threshold_90, 
                     budget_exceeded, budget_exceeded_method, weekly_summary, monthly_summary, 
                     notify_email, notify_web, max_emails_per_day) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $userId,
                    $data['budget_alert_enabled'],
                    $data['budget_threshold_80'],
                    $data['budget_threshold_90'],
                    $data['budget_exceeded'],
                    $data['budget_exceeded_method'],
                    $data['weekly_summary'],
                    $data['monthly_summary'],
                    $data['notify_email'],
                    $data['notify_web'],
                    $data['max_emails_per_day']
                ]
            );
        }
        
        flash('success', 'Paramètres de notifications mis à jour avec succès');
        $this->redirect('profile/notifications');
    }
}
