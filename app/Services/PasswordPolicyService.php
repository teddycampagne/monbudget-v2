<?php

namespace MonBudget\Services;

use MonBudget\Core\Database;
use Exception;

/**
 * Service de politique de mots de passe
 * 
 * Impl\u00e9mente les exigences PCI DSS 8.3 pour les mots de passe forts :
 * - Longueur minimum 12 caract\u00e8res
 * - Complexit\u00e9 (majuscules, minuscules, chiffres, sp\u00e9ciaux)
 * - Historique (pas de r\u00e9utilisation 5 derniers mots de passe)
 * - Expiration tous les 90 jours
 * - Verrouillage compte apr\u00e8s 5 \u00e9checs
 * 
 * @package MonBudget\Services
 * @author MonBudget Security Team
 * @version 1.0.0
 */
class PasswordPolicyService
{
    /**
     * Longueur minimum du mot de passe
     * @var int
     */
    public const MIN_LENGTH = 12;
    
    /**
     * Requis : au moins une majuscule
     * @var bool
     */
    public const REQUIRE_UPPERCASE = true;
    
    /**
     * Requis : au moins une minuscule
     * @var bool
     */
    public const REQUIRE_LOWERCASE = true;
    
    /**
     * Requis : au moins un chiffre
     * @var bool
     */
    public const REQUIRE_NUMBERS = true;
    
    /**
     * Requis : au moins un caract\u00e8re sp\u00e9cial
     * @var bool
     */
    public const REQUIRE_SPECIAL = true;
    
    /**
     * Nombre de mots de passe dans l'historique
     * @var int
     */
    public const HISTORY_COUNT = 5;
    
    /**
     * Dur\u00e9e de validit\u00e9 mot de passe (en jours)
     * @var int
     */
    public const MAX_AGE_DAYS = 90;
    
    /**
     * Nombre maximum de tentatives de connexion
     * @var int
     */
    public const MAX_LOGIN_ATTEMPTS = 5;
    
    /**
     * Dur\u00e9e de verrouillage compte (en secondes)
     * @var int
     */
    public const LOCKOUT_DURATION = 900; // 15 minutes
    
    /**
     * Valide un mot de passe selon la politique
     * 
     * @param string $password Mot de passe \u00e0 valider
     * @return array Liste des erreurs (vide si valide)
     */
    public static function validate(string $password): array
    {
        $errors = [];
        
        // Longueur minimum
        if (strlen($password) < self::MIN_LENGTH) {
            $errors[] = sprintf(
                "Le mot de passe doit contenir au moins %d caract\u00e8res",
                self::MIN_LENGTH
            );
        }
        
        // Au moins une majuscule
        if (self::REQUIRE_UPPERCASE && !preg_match('/[A-Z]/', $password)) {
            $errors[] = "Le mot de passe doit contenir au moins une majuscule";
        }
        
        // Au moins une minuscule
        if (self::REQUIRE_LOWERCASE && !preg_match('/[a-z]/', $password)) {
            $errors[] = "Le mot de passe doit contenir au moins une minuscule";
        }
        
        // Au moins un chiffre
        if (self::REQUIRE_NUMBERS && !preg_match('/[0-9]/', $password)) {
            $errors[] = "Le mot de passe doit contenir au moins un chiffre";
        }
        
        // Au moins un caract\u00e8re sp\u00e9cial
        if (self::REQUIRE_SPECIAL && !preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = "Le mot de passe doit contenir au moins un caract\u00e8re sp\u00e9cial (!@#$%^&*...)";
        }
        
        // Mots de passe communs interdits
        if (self::isCommonPassword($password)) {
            $errors[] = "Ce mot de passe est trop commun. Choisissez-en un plus unique";
        }
        
        return $errors;
    }
    
    /**
     * V\u00e9rifie si mot de passe dans liste des mots de passe communs
     * 
     * @param string $password Mot de passe \u00e0 v\u00e9rifier
     * @return bool True si mot de passe commun
     */
    private static function isCommonPassword(string $password): bool
    {
        $commonPasswords = [
            'password', 'Password123', '123456', '12345678',
            'qwerty', 'abc123', 'password123', 'admin',
            'letmein', 'welcome', 'monkey', '1234567890',
            'Password1', 'Password1!', 'Azerty123',
            'MonBudget123', 'monbudget'
        ];
        
        return in_array(strtolower($password), array_map('strtolower', $commonPasswords));
    }
    
    /**
     * V\u00e9rifie si mot de passe d\u00e9j\u00e0 utilis\u00e9 r\u00e9cemment
     * 
     * @param int $userId ID utilisateur
     * @param string $newPassword Nouveau mot de passe
     * @return bool True si d\u00e9j\u00e0 utilis\u00e9
     */
    public static function isInHistory(int $userId, string $newPassword): bool
    {
        // R\u00e9cup\u00e9rer historique mots de passe
        $history = Database::select(
            "SELECT password_hash FROM password_history 
             WHERE user_id = ? 
             ORDER BY created_at DESC 
             LIMIT ?",
            [$userId, self::HISTORY_COUNT]
        );
        
        // V\u00e9rifier si nouveau mot de passe correspond \u00e0 un ancien
        foreach ($history as $record) {
            if (password_verify($newPassword, $record['password_hash'])) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Enregistre un mot de passe dans l'historique
     * 
     * @param int $userId ID utilisateur
     * @param string $passwordHash Hash du mot de passe
     * @return void
     */
    public static function addToHistory(int $userId, string $passwordHash): void
    {
        // Ins\u00e9rer dans historique
        Database::query(
            "INSERT INTO password_history (user_id, password_hash) VALUES (?, ?)",
            [$userId, $passwordHash]
        );
        
        // Supprimer anciens mots de passe (garder seulement HISTORY_COUNT)
        Database::query(
            "DELETE FROM password_history 
             WHERE user_id = ? 
             AND id NOT IN (
                 SELECT id FROM (
                     SELECT id FROM password_history 
                     WHERE user_id = ? 
                     ORDER BY created_at DESC 
                     LIMIT ?
                 ) AS recent
             )",
            [$userId, $userId, self::HISTORY_COUNT]
        );
    }
    
    /**
     * V\u00e9rifie si mot de passe expir\u00e9
     * 
     * @param int $userId ID utilisateur
     * @return bool True si expir\u00e9
     */
    public static function isExpired(int $userId): bool
    {
        $user = Database::selectOne(
            "SELECT password_changed_at FROM users WHERE id = ?",
            [$userId]
        );
        
        if (!$user || !$user['password_changed_at']) {
            return false; // Pas de date = pas expir\u00e9 (migration)
        }
        
        $changedAt = strtotime($user['password_changed_at']);
        $expiresAt = strtotime('+' . self::MAX_AGE_DAYS . ' days', $changedAt);
        
        return time() > $expiresAt;
    }
    
    /**
     * Met \u00e0 jour la date de changement mot de passe
     * 
     * @param int $userId ID utilisateur
     * @return void
     */
    public static function updatePasswordChangedDate(int $userId): void
    {
        Database::query(
            "UPDATE users SET password_changed_at = NOW() WHERE id = ?",
            [$userId]
        );
    }
    
    /**
     * Enregistre une tentative de connexion \u00e9chou\u00e9e
     * 
     * @param string $email Email utilisateur
     * @return void
     */
    public static function recordFailedAttempt(string $email): void
    {
        $key = 'login_attempts_' . md5($email);
        $timeKey = $key . '_time';
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = 0;
        }
        
        $_SESSION[$key]++;
        $_SESSION[$timeKey] = time();
        
        // Logger tentative \u00e9chou\u00e9e
        if (class_exists('MonBudget\\Services\\AuditLogger')) {
            \MonBudget\Services\AuditLogger::logFailedAuth($email);
        }
    }
    
    /**
     * V\u00e9rifie si compte verrouill\u00e9
     * 
     * @param string $email Email utilisateur
     * @return array ['locked' => bool, 'remaining_time' => int]
     */
    public static function checkLockout(string $email): array
    {
        $key = 'login_attempts_' . md5($email);
        $timeKey = $key . '_time';
        
        $attempts = $_SESSION[$key] ?? 0;
        $lastAttempt = $_SESSION[$timeKey] ?? 0;
        
        if ($attempts >= self::MAX_LOGIN_ATTEMPTS) {
            $elapsed = time() - $lastAttempt;
            
            if ($elapsed < self::LOCKOUT_DURATION) {
                // Encore verrouill\u00e9
                return [
                    'locked' => true,
                    'remaining_time' => self::LOCKOUT_DURATION - $elapsed,
                    'attempts' => $attempts
                ];
            } else {
                // D\u00e9verrouillage automatique
                self::resetAttempts($email);
                return ['locked' => false, 'remaining_time' => 0];
            }
        }
        
        return ['locked' => false, 'remaining_time' => 0, 'attempts' => $attempts];
    }
    
    /**
     * R\u00e9initialise les tentatives de connexion
     * 
     * @param string $email Email utilisateur
     * @return void
     */
    public static function resetAttempts(string $email): void
    {
        $key = 'login_attempts_' . md5($email);
        $timeKey = $key . '_time';
        
        unset($_SESSION[$key], $_SESSION[$timeKey]);
    }
    
    /**
     * Obtient le nombre de tentatives restantes avant verrouillage
     * 
     * @param string $email Email utilisateur
     * @return int Tentatives restantes
     */
    public static function getRemainingAttempts(string $email): int
    {
        $key = 'login_attempts_' . md5($email);
        $attempts = $_SESSION[$key] ?? 0;
        
        return max(0, self::MAX_LOGIN_ATTEMPTS - $attempts);
    }
    
    /**
     * G\u00e9n\u00e8re un mot de passe al\u00e9atoire conforme \u00e0 la politique
     * 
     * @param int $length Longueur du mot de passe (minimum MIN_LENGTH)
     * @return string Mot de passe g\u00e9n\u00e9r\u00e9
     */
    public static function generateSecurePassword(int $length = null): string
    {
        if ($length === null) {
            $length = self::MIN_LENGTH;
        }
        
        $length = max($length, self::MIN_LENGTH);
        
        $uppercase = 'ABCDEFGHJKLMNPQRSTUVWXYZ'; // Sans I, O (confusion)
        $lowercase = 'abcdefghjkmnpqrstuvwxyz'; // Sans i, l, o (confusion)
        $numbers = '23456789'; // Sans 0, 1 (confusion)
        $special = '!@#$%^&*-_=+';
        
        $password = '';
        
        // Assurer au moins un de chaque type requis
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $special[random_int(0, strlen($special) - 1)];
        
        // Remplir le reste
        $allChars = $uppercase . $lowercase . $numbers . $special;
        for ($i = strlen($password); $i < $length; $i++) {
            $password .= $allChars[random_int(0, strlen($allChars) - 1)];
        }
        
        // M\u00e9langer
        return str_shuffle($password);
    }
}
