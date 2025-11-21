<?php

namespace App\Controllers;

use App\Core\Database;
use App\Services\MailService;
use PDO;
use Exception;

/**
 * Contrôleur de réinitialisation de mot de passe
 * 
 * Gère :
 * - Demande de réinitialisation (envoi email avec token)
 * - Validation du token
 * - Changement du mot de passe
 * - Fallback admin (si email impossible)
 */
class PasswordResetController
{
    private $db;
    private $mailService;
    
    const TOKEN_EXPIRATION_HOURS = 1;
    const TOKEN_LENGTH = 64;
    const MAX_ATTEMPTS_PER_DAY = 5; // Max 5 demandes par jour par IP
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->mailService = new MailService($this->db);
    }
    
    /**
     * Demande de réinitialisation de mot de passe
     * 
     * @param string $email Email de l'utilisateur
     * @return array ['success' => bool, 'message' => string]
     */
    public function requestReset($email)
    {
        try {
            // Protection anti-spam : limiter les tentatives
            if (!$this->checkRateLimiting()) {
                return [
                    'success' => false,
                    'message' => 'Trop de tentatives. Veuillez réessayer dans 24 heures.'
                ];
            }
            
            // Vérifier si l'utilisateur existe
            $user = $this->getUserByEmail($email);
            
            // IMPORTANT : Ne jamais révéler si l'email existe ou non (sécurité)
            // On retourne toujours le même message
            if (!$user) {
                // Logger la tentative pour monitoring
                $this->logResetAttempt($email, 'user_not_found');
                
                return [
                    'success' => true,
                    'message' => 'Si cet email existe, un lien de réinitialisation a été envoyé.'
                ];
            }
            
            // Vérifier si le compte est actif
            if (isset($user['is_active']) && $user['is_active'] == 0) {
                $this->logResetAttempt($email, 'account_disabled');
                
                return [
                    'success' => true,
                    'message' => 'Si cet email existe, un lien de réinitialisation a été envoyé.'
                ];
            }
            
            // Générer un token sécurisé
            $token = $this->generateSecureToken();
            $hashedToken = hash('sha256', $token);
            
            // Calculer l'expiration (1 heure)
            $expiresAt = date('Y-m-d H:i:s', strtotime('+' . self::TOKEN_EXPIRATION_HOURS . ' hours'));
            
            // Enregistrer le token dans la base
            $this->storeResetToken($user['id'], $user['email'], $hashedToken, $expiresAt);
            
            // Construire l'URL de réinitialisation
            $resetUrl = $this->buildResetUrl($token);
            
            // Envoyer l'email
            $emailSent = $this->mailService->sendTemplate(
                $user['email'],
                'password_reset',
                [
                    'username' => $user['username'],
                    'reset_url' => $resetUrl,
                    'year' => date('Y')
                ]
            );
            
            if (!$emailSent) {
                // Si l'email échoue, proposer le fallback admin
                $this->logResetAttempt($email, 'email_failed');
                
                return [
                    'success' => false,
                    'message' => 'Erreur d\'envoi d\'email. Veuillez contacter l\'administrateur.',
                    'fallback' => 'admin'
                ];
            }
            
            // Logger le succès
            $this->logResetAttempt($email, 'success');
            
            return [
                'success' => true,
                'message' => 'Un email de réinitialisation a été envoyé. Vérifiez votre boîte de réception.',
                'expires_in' => self::TOKEN_EXPIRATION_HOURS . ' heure(s)'
            ];
            
        } catch (Exception $e) {
            error_log("PasswordResetController::requestReset - Erreur: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Une erreur est survenue. Veuillez réessayer plus tard.'
            ];
        }
    }
    
    /**
     * Valide un token de réinitialisation
     * 
     * @param string $token Token de réinitialisation
     * @return array ['valid' => bool, 'user_id' => int, 'message' => string]
     */
    public function validateToken($token)
    {
        try {
            $hashedToken = hash('sha256', $token);
            
            $stmt = $this->db->prepare("
                SELECT id, user_id, email, expires_at, used_at
                FROM password_resets
                WHERE token = ?
                AND expires_at > NOW()
                AND used_at IS NULL
            ");
            $stmt->execute([$hashedToken]);
            $reset = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$reset) {
                return [
                    'valid' => false,
                    'message' => 'Token invalide ou expiré.'
                ];
            }
            
            return [
                'valid' => true,
                'user_id' => $reset['user_id'],
                'email' => $reset['email'],
                'reset_id' => $reset['id']
            ];
            
        } catch (Exception $e) {
            error_log("PasswordResetController::validateToken - Erreur: " . $e->getMessage());
            
            return [
                'valid' => false,
                'message' => 'Erreur de validation du token.'
            ];
        }
    }
    
    /**
     * Réinitialise le mot de passe
     * 
     * @param string $token Token de réinitialisation
     * @param string $newPassword Nouveau mot de passe
     * @return array ['success' => bool, 'message' => string]
     */
    public function resetPassword($token, $newPassword)
    {
        try {
            // Valider le token
            $validation = $this->validateToken($token);
            
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'message' => $validation['message']
                ];
            }
            
            // Valider le nouveau mot de passe (politique PCI DSS)
            $passwordValidation = $this->validatePassword($newPassword);
            if (!$passwordValidation['valid']) {
                return [
                    'success' => false,
                    'message' => $passwordValidation['message']
                ];
            }
            
            // Hasher le nouveau mot de passe
            $hashedPassword = password_hash($newPassword, PASSWORD_ARGON2ID);
            
            // Commencer une transaction
            $this->db->beginTransaction();
            
            try {
                // Mettre à jour le mot de passe
                $stmt = $this->db->prepare("
                    UPDATE users 
                    SET password = ?,
                        last_password_change = NOW(),
                        password_expires_at = DATE_ADD(NOW(), INTERVAL 90 DAY),
                        must_change_password = 0,
                        failed_login_attempts = 0
                    WHERE id = ?
                ");
                $stmt->execute([$hashedPassword, $validation['user_id']]);
                
                // Ajouter à l'historique des mots de passe
                $stmt = $this->db->prepare("
                    INSERT INTO password_history (user_id, password_hash, created_at)
                    VALUES (?, ?, NOW())
                ");
                $stmt->execute([$validation['user_id'], $hashedPassword]);
                
                // Marquer le token comme utilisé
                $stmt = $this->db->prepare("
                    UPDATE password_resets 
                    SET used_at = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$validation['reset_id']]);
                
                // Logger dans audit_logs
                $stmt = $this->db->prepare("
                    INSERT INTO audit_logs (user_id, action, details, ip_address, created_at)
                    VALUES (?, 'password_reset_success', ?, ?, NOW())
                ");
                $stmt->execute([
                    $validation['user_id'],
                    'Mot de passe réinitialisé via email',
                    $this->getIpAddress()
                ]);
                
                $this->db->commit();
                
                return [
                    'success' => true,
                    'message' => 'Mot de passe réinitialisé avec succès. Vous pouvez maintenant vous connecter.'
                ];
                
            } catch (Exception $e) {
                $this->db->rollBack();
                throw $e;
            }
            
        } catch (Exception $e) {
            error_log("PasswordResetController::resetPassword - Erreur: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Erreur lors de la réinitialisation. Veuillez réessayer.'
            ];
        }
    }
    
    /**
     * Demande de réinitialisation via admin (fallback)
     * 
     * @param string $email Email utilisateur
     * @param string $reason Raison de la demande
     * @return array ['success' => bool, 'message' => string]
     */
    public function requestAdminReset($email, $reason = '')
    {
        try {
            // Vérifier si l'utilisateur existe
            $user = $this->getUserByEmail($email);
            
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Utilisateur non trouvé.'
                ];
            }
            
            // Enregistrer la demande
            $stmt = $this->db->prepare("
                INSERT INTO admin_password_requests 
                (user_id, requester_email, reason, ip_address, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $user['id'],
                $email,
                $reason,
                $this->getIpAddress()
            ]);
            
            $requestId = $this->db->lastInsertId();
            
            // Récupérer tous les admins
            $admins = $this->getAdmins();
            
            // Envoyer une notification à tous les admins
            foreach ($admins as $admin) {
                $this->mailService->sendTemplate(
                    $admin['email'],
                    'admin_password_request',
                    [
                        'username' => $user['username'],
                        'user_email' => $email,
                        'request_date' => date('d/m/Y à H:i'),
                        'reason' => $reason ?: 'Non spécifiée',
                        'admin_url' => $this->buildAdminUrl($requestId),
                        'year' => date('Y')
                    ]
                );
            }
            
            return [
                'success' => true,
                'message' => 'Demande envoyée aux administrateurs. Vous serez contacté sous 24-48h.'
            ];
            
        } catch (Exception $e) {
            error_log("PasswordResetController::requestAdminReset - Erreur: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Erreur lors de la demande. Veuillez réessayer.'
            ];
        }
    }
    
    /**
     * Traite une demande admin (pour les admins)
     * 
     * @param int $requestId ID de la demande
     * @param int $adminId ID de l'admin
     * @param string $action 'approve' ou 'reject'
     * @param string $notes Notes de l'admin
     * @return array ['success' => bool, 'message' => string]
     */
    public function processAdminRequest($requestId, $adminId, $action, $notes = '')
    {
        try {
            // Vérifier que l'admin existe et est bien admin
            if (!$this->isAdmin($adminId)) {
                return [
                    'success' => false,
                    'message' => 'Accès non autorisé.'
                ];
            }
            
            // Récupérer la demande
            $stmt = $this->db->prepare("
                SELECT * FROM admin_password_requests WHERE id = ?
            ");
            $stmt->execute([$requestId]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$request) {
                return [
                    'success' => false,
                    'message' => 'Demande non trouvée.'
                ];
            }
            
            if ($request['status'] !== 'pending') {
                return [
                    'success' => false,
                    'message' => 'Cette demande a déjà été traitée.'
                ];
            }
            
            $status = ($action === 'approve') ? 'approved' : 'rejected';
            
            // Mettre à jour la demande
            $stmt = $this->db->prepare("
                UPDATE admin_password_requests 
                SET status = ?,
                    admin_id = ?,
                    admin_notes = ?,
                    processed_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$status, $adminId, $notes, $requestId]);
            
            if ($action === 'approve') {
                // Générer un mot de passe temporaire
                $tempPassword = $this->generateTemporaryPassword();
                $hashedPassword = password_hash($tempPassword, PASSWORD_ARGON2ID);
                
                // Mettre à jour le mot de passe utilisateur
                $stmt = $this->db->prepare("
                    UPDATE users 
                    SET password = ?,
                        must_change_password = 1,
                        last_password_change = NOW(),
                        password_expires_at = DATE_ADD(NOW(), INTERVAL 7 DAY),
                        failed_login_attempts = 0
                    WHERE id = ?
                ");
                $stmt->execute([$hashedPassword, $request['user_id']]);
                
                // Envoyer le nouveau mot de passe par email
                $user = $this->getUserById($request['user_id']);
                
                $this->mailService->send(
                    $user['email'],
                    'Nouveau mot de passe temporaire - MonBudget',
                    $this->buildPasswordResetEmail($user['username'], $tempPassword),
                    ['html' => true]
                );
                
                // Marquer l'envoi
                $stmt = $this->db->prepare("
                    UPDATE admin_password_requests 
                    SET new_password_sent_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$requestId]);
                
                return [
                    'success' => true,
                    'message' => 'Mot de passe temporaire envoyé à l\'utilisateur.'
                ];
            }
            
            return [
                'success' => true,
                'message' => 'Demande rejetée.'
            ];
            
        } catch (Exception $e) {
            error_log("PasswordResetController::processAdminRequest - Erreur: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Erreur lors du traitement.'
            ];
        }
    }
    
    // =========================================================================
    // MÉTHODES PRIVÉES
    // =========================================================================
    
    /**
     * Génère un token sécurisé
     */
    private function generateSecureToken()
    {
        return bin2hex(random_bytes(self::TOKEN_LENGTH / 2));
    }
    
    /**
     * Génère un mot de passe temporaire sécurisé
     */
    private function generateTemporaryPassword()
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $password = '';
        for ($i = 0; $i < 16; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $password;
    }
    
    /**
     * Récupère un utilisateur par email
     */
    private function getUserByEmail($email)
    {
        $stmt = $this->db->prepare("
            SELECT id, username, email, is_active 
            FROM users 
            WHERE email = ?
        ");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Récupère un utilisateur par ID
     */
    private function getUserById($id)
    {
        $stmt = $this->db->prepare("
            SELECT id, username, email 
            FROM users 
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Enregistre le token de réinitialisation
     */
    private function storeResetToken($userId, $email, $hashedToken, $expiresAt)
    {
        $stmt = $this->db->prepare("
            INSERT INTO password_resets 
            (user_id, email, token, expires_at, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId,
            $email,
            $hashedToken,
            $expiresAt,
            $this->getIpAddress(),
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }
    
    /**
     * Construit l'URL de réinitialisation
     */
    private function buildResetUrl($token)
    {
        $baseUrl = $_ENV['APP_URL'] ?? 'http://localhost';
        return $baseUrl . '/reset-password?token=' . urlencode($token);
    }
    
    /**
     * Construit l'URL admin pour gérer les demandes
     */
    private function buildAdminUrl($requestId)
    {
        $baseUrl = $_ENV['APP_URL'] ?? 'http://localhost';
        return $baseUrl . '/admin/password-requests?id=' . $requestId;
    }
    
    /**
     * Récupère l'adresse IP du client
     */
    private function getIpAddress()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    /**
     * Vérifie le rate limiting (anti-spam)
     */
    private function checkRateLimiting()
    {
        $ip = $this->getIpAddress();
        
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count
            FROM password_resets
            WHERE ip_address = ?
            AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        $stmt->execute([$ip]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return ($result['count'] < self::MAX_ATTEMPTS_PER_DAY);
    }
    
    /**
     * Log les tentatives de réinitialisation
     */
    private function logResetAttempt($email, $status)
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO audit_logs (action, details, ip_address, created_at)
                VALUES ('password_reset_attempt', ?, ?, NOW())
            ");
            $stmt->execute([
                "Email: $email, Status: $status",
                $this->getIpAddress()
            ]);
        } catch (Exception $e) {
            error_log("Erreur log reset attempt: " . $e->getMessage());
        }
    }
    
    /**
     * Valide un mot de passe selon la politique PCI DSS
     */
    private function validatePassword($password)
    {
        if (strlen($password) < 12) {
            return [
                'valid' => false,
                'message' => 'Le mot de passe doit contenir au moins 12 caractères.'
            ];
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            return [
                'valid' => false,
                'message' => 'Le mot de passe doit contenir au moins une majuscule.'
            ];
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            return [
                'valid' => false,
                'message' => 'Le mot de passe doit contenir au moins une minuscule.'
            ];
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            return [
                'valid' => false,
                'message' => 'Le mot de passe doit contenir au moins un chiffre.'
            ];
        }
        
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            return [
                'valid' => false,
                'message' => 'Le mot de passe doit contenir au moins un caractère spécial.'
            ];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Récupère tous les admins
     */
    private function getAdmins()
    {
        $stmt = $this->db->query("
            SELECT id, username, email 
            FROM users 
            WHERE role = 'admin' 
            AND is_active = 1
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Vérifie si un utilisateur est admin
     */
    private function isAdmin($userId)
    {
        $stmt = $this->db->prepare("
            SELECT role FROM users WHERE id = ?
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return ($user && $user['role'] === 'admin');
    }
    
    /**
     * Construit l'email de mot de passe temporaire
     */
    private function buildPasswordResetEmail($username, $tempPassword)
    {
        return '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #4CAF50; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f9f9f9; }
        .password-box { background: white; border: 2px solid #4CAF50; padding: 15px; text-align: center; font-size: 18px; font-weight: bold; margin: 20px 0; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 10px; margin: 15px 0; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔑 Mot de passe temporaire</h1>
        </div>
        <div class="content">
            <h2>Bonjour ' . htmlspecialchars($username) . ',</h2>
            <p>Votre mot de passe a été réinitialisé par un administrateur.</p>
            <p>Voici votre mot de passe temporaire :</p>
            <div class="password-box">
                ' . htmlspecialchars($tempPassword) . '
            </div>
            <div class="warning">
                <strong>⚠️ IMPORTANT :</strong>
                <ul>
                    <li>Ce mot de passe expire dans <strong>7 jours</strong></li>
                    <li>Vous <strong>devrez le changer</strong> lors de votre prochaine connexion</li>
                    <li>Ne partagez jamais votre mot de passe</li>
                </ul>
            </div>
            <p>Connectez-vous maintenant pour définir votre nouveau mot de passe personnel.</p>
        </div>
        <div class="footer">
            <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
            <p>© ' . date('Y') . ' MonBudget</p>
        </div>
    </div>
</body>
</html>';
    }
}
