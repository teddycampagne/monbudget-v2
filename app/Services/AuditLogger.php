<?php

namespace MonBudget\Services;

use Exception;

/**
 * Service de journalisation et audit de s\u00e9curit\u00e9
 * 
 * Impl\u00e9mente les exigences PCI DSS 10 pour la journalisation :
 * - Tous les acc\u00e8s donn\u00e9es sensibles
 * - Tentatives d'authentification (succ\u00e8s/\u00e9checs)
 * - Modifications de donn\u00e9es critiques
 * - Cr\u00e9ation/suppression utilisateurs
 * - Changements permissions
 * - Exports de donn\u00e9es
 * 
 * Format de log : JSON pour analyse automatis\u00e9e
 * 
 * @package MonBudget\Services
 * @author MonBudget Security Team
 * @version 1.0.0
 */
class AuditLogger
{
    /**
     * Chemin du fichier de log d'audit
     * @var string
     */
    private static string $logFile = __DIR__ . '/../../storage/logs/audit.log';
    
    /**
     * Chemin du fichier de log de s\u00e9curit\u00e9 (incidents)
     * @var string
     */
    private static string $securityLogFile = __DIR__ . '/../../storage/logs/security.log';
    
    /**
     * Niveaux de s\u00e9v\u00e9rit\u00e9
     */
    public const LEVEL_INFO = 'INFO';
    public const LEVEL_WARNING = 'WARNING';
    public const LEVEL_ERROR = 'ERROR';
    public const LEVEL_CRITICAL = 'CRITICAL';
    
    /**
     * Enregistre une entr\u00e9e dans le journal d'audit
     * 
     * @param string $action Action effectu\u00e9e (LOGIN, DATA_ACCESS, DATA_MODIFY, etc.)
     * @param array $context Contexte additionnel
     * @param string $level Niveau de s\u00e9v\u00e9rit\u00e9
     * @return void
     */
    public static function log(string $action, array $context = [], string $level = self::LEVEL_INFO): void
    {
        // Cr\u00e9er r\u00e9pertoire logs si n\u00e9cessaire
        $logDir = dirname(self::$logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        // Construire entr\u00e9e de log
        $entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => $level,
            'action' => $action,
            'user_id' => $_SESSION['user_id'] ?? null,
            'username' => $_SESSION['user']['username'] ?? 'anonymous',
            'role' => $_SESSION['user']['role'] ?? 'guest',
            'ip' => self::getClientIp(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? '',
            'context' => $context
        ];
        
        // \u00c9crire dans fichier
        $logLine = json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
        
        file_put_contents(
            self::$logFile,
            $logLine,
            FILE_APPEND | LOCK_EX
        );
        
        // Si niveau critique, dupliquer dans security.log
        if (in_array($level, [self::LEVEL_ERROR, self::LEVEL_CRITICAL])) {
            file_put_contents(
                self::$securityLogFile,
                $logLine,
                FILE_APPEND | LOCK_EX
            );
        }
    }
    
    /**
     * Enregistre une tentative de connexion
     * 
     * @param int|null $userId ID utilisateur (null si \u00e9chec)
     * @param string $email Email tent\u00e9
     * @param bool $success Succ\u00e8s ou \u00e9chec
     * @param string|null $reason Raison \u00e9chec
     * @return void
     */
    public static function logLogin(?int $userId, string $email, bool $success, ?string $reason = null): void
    {
        $context = [
            'email' => $email,
            'success' => $success
        ];
        
        if (!$success) {
            $context['reason'] = $reason ?? 'Identifiants incorrects';
        }
        
        if ($userId) {
            $context['user_id'] = $userId;
        }
        
        self::log(
            $success ? 'LOGIN_SUCCESS' : 'LOGIN_FAILED',
            $context,
            $success ? self::LEVEL_INFO : self::LEVEL_WARNING
        );
    }
    
    /**
     * Enregistre une d\u00e9connexion
     * 
     * @param int $userId ID utilisateur
     * @return void
     */
    public static function logLogout(int $userId): void
    {
        self::log('LOGOUT', ['user_id' => $userId]);
    }
    
    /**
     * Enregistre un acc\u00e8s \u00e0 des donn\u00e9es sensibles
     * 
     * @param string $resource Type de ressource (compte, transaction, etc.)
     * @param int $resourceId ID de la ressource
     * @param string $action Action (read, export, print, etc.)
     * @return void
     */
    public static function logDataAccess(string $resource, int $resourceId, string $action = 'read'): void
    {
        self::log('DATA_ACCESS', [
            'resource' => $resource,
            'resource_id' => $resourceId,
            'action' => $action
        ]);
    }
    
    /**
     * Enregistre une modification de donn\u00e9es
     * 
     * @param string $table Table modifi\u00e9e
     * @param int $recordId ID enregistrement
     * @param string $operation create|update|delete
     * @param array $changes Champs modifi\u00e9s (avant/apr\u00e8s)
     * @return void
     */
    public static function logDataModification(string $table, int $recordId, string $operation, array $changes = []): void
    {
        // Masquer donn\u00e9es sensibles dans les logs
        $sanitizedChanges = self::sanitizeSensitiveData($changes);
        
        self::log('DATA_MODIFY', [
            'table' => $table,
            'record_id' => $recordId,
            'operation' => $operation,
            'changes' => $sanitizedChanges
        ], self::LEVEL_INFO);
    }
    
    /**
     * Enregistre une tentative d'authentification \u00e9chou\u00e9e
     * 
     * @param string $email Email tent\u00e9
     * @param int $attemptNumber Num\u00e9ro tentative
     * @return void
     */
    public static function logFailedAuth(string $email, int $attemptNumber = 1): void
    {
        self::log('AUTH_FAILED', [
            'email' => $email,
            'attempt_number' => $attemptNumber
        ], self::LEVEL_WARNING);
    }
    
    /**
     * Enregistre un verrouillage de compte
     * 
     * @param string $email Email utilisateur
     * @param int $duration Dur\u00e9e verrouillage (secondes)
     * @return void
     */
    public static function logAccountLockout(string $email, int $duration): void
    {
        self::log('ACCOUNT_LOCKOUT', [
            'email' => $email,
            'duration_seconds' => $duration,
            'duration_minutes' => round($duration / 60, 1)
        ], self::LEVEL_ERROR);
    }
    
    /**
     * Enregistre un export de donn\u00e9es
     * 
     * @param string $exportType Type export (CSV, PDF, etc.)
     * @param string $resource Ressource export\u00e9e
     * @param int $recordCount Nombre enregistrements
     * @return void
     */
    public static function logDataExport(string $exportType, string $resource, int $recordCount): void
    {
        self::log('DATA_EXPORT', [
            'export_type' => $exportType,
            'resource' => $resource,
            'record_count' => $recordCount
        ], self::LEVEL_INFO);
    }
    
    /**
     * Enregistre une cr\u00e9ation d'utilisateur
     * 
     * @param int $newUserId ID nouvel utilisateur
     * @param string $username Username
     * @param string $role R\u00f4le
     * @return void
     */
    public static function logUserCreation(int $newUserId, string $username, string $role): void
    {
        self::log('USER_CREATE', [
            'new_user_id' => $newUserId,
            'username' => $username,
            'role' => $role
        ], self::LEVEL_INFO);
    }
    
    /**
     * Enregistre une suppression d'utilisateur
     * 
     * @param int $deletedUserId ID utilisateur supprim\u00e9
     * @param string $username Username
     * @return void
     */
    public static function logUserDeletion(int $deletedUserId, string $username): void
    {
        self::log('USER_DELETE', [
            'deleted_user_id' => $deletedUserId,
            'username' => $username
        ], self::LEVEL_WARNING);
    }
    
    /**
     * Enregistre un changement de permissions
     * 
     * @param int $targetUserId ID utilisateur modifi\u00e9
     * @param string $oldRole Ancien r\u00f4le
     * @param string $newRole Nouveau r\u00f4le
     * @return void
     */
    public static function logPermissionChange(int $targetUserId, string $oldRole, string $newRole): void
    {
        self::log('PERMISSION_CHANGE', [
            'target_user_id' => $targetUserId,
            'old_role' => $oldRole,
            'new_role' => $newRole
        ], self::LEVEL_WARNING);
    }
    
    /**
     * Enregistre une erreur de s\u00e9curit\u00e9 (CSRF, XSS, injection, etc.)
     * 
     * @param string $errorType Type erreur
     * @param string $details D\u00e9tails
     * @param array $context Contexte additionnel
     * @return void
     */
    public static function logSecurityError(string $errorType, string $details, array $context = []): void
    {
        self::log('SECURITY_ERROR', array_merge([
            'error_type' => $errorType,
            'details' => $details
        ], $context), self::LEVEL_ERROR);
    }
    
    /**
     * Enregistre une alerte de s\u00e9curit\u00e9 critique
     * 
     * @param string $message Message alerte
     * @param array $context Contexte
     * @return void
     */
    public static function logSecurityAlert(string $message, array $context = []): void
    {
        self::log('SECURITY_ALERT', array_merge([
            'message' => $message
        ], $context), self::LEVEL_CRITICAL);
        
        // Envoyer notification email admin
        if (function_exists('sendSecurityAlertEmail')) {
            sendSecurityAlertEmail($message, $context);
        }
    }
    
    /**
     * Obtient l'adresse IP r\u00e9elle du client
     * 
     * @return string Adresse IP
     */
    private static function getClientIp(): string
    {
        // V\u00e9rifier headers proxy
        $headers = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',  // Proxy standard
            'HTTP_X_REAL_IP',        // Nginx
            'REMOTE_ADDR'            // Connexion directe
        ];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                // Si plusieurs IPs (proxy chain), prendre la premi\u00e8re
                $ip = explode(',', $_SERVER[$header])[0];
                return trim($ip);
            }
        }
        
        return 'unknown';
    }
    
    /**
     * Masque les donn\u00e9es sensibles dans les logs
     * 
     * @param array $data Donn\u00e9es \u00e0 masquer
     * @return array Donn\u00e9es masqu\u00e9es
     */
    private static function sanitizeSensitiveData(array $data): array
    {
        $sensitiveFields = [
            'password', 'password_hash', 'iban', 'numero_compte',
            'code_guichet', 'cle_rib', 'bic', 'telephone', 'email'
        ];
        
        foreach ($data as $key => $value) {
            if (in_array(strtolower($key), $sensitiveFields)) {
                if (is_string($value) && strlen($value) > 4) {
                    // Masquer sauf 4 derniers caract\u00e8res
                    $data[$key] = str_repeat('*', strlen($value) - 4) . substr($value, -4);
                } else {
                    $data[$key] = '***';
                }
            }
        }
        
        return $data;
    }
    
    /**
     * Recherche dans les logs d'audit
     * 
     * @param array $filters Filtres (action, user_id, date_from, date_to, etc.)
     * @param int $limit Nombre maximum de r\u00e9sultats
     * @return array Entr\u00e9es de log correspondantes
     */
    public static function search(array $filters = [], int $limit = 100): array
    {
        if (!file_exists(self::$logFile)) {
            return [];
        }
        
        $lines = file(self::$logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $results = [];
        
        // Inverser pour avoir les plus r\u00e9centes en premier
        $lines = array_reverse($lines);
        
        foreach ($lines as $line) {
            $entry = json_decode($line, true);
            if (!$entry) continue;
            
            // Appliquer filtres
            $match = true;
            
            if (isset($filters['action']) && $entry['action'] !== $filters['action']) {
                $match = false;
            }
            
            if (isset($filters['user_id']) && $entry['user_id'] !== $filters['user_id']) {
                $match = false;
            }
            
            if (isset($filters['date_from']) && $entry['timestamp'] < $filters['date_from']) {
                $match = false;
            }
            
            if (isset($filters['date_to']) && $entry['timestamp'] > $filters['date_to']) {
                $match = false;
            }
            
            if (isset($filters['level']) && $entry['level'] !== $filters['level']) {
                $match = false;
            }
            
            if ($match) {
                $results[] = $entry;
                
                if (count($results) >= $limit) {
                    break;
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Obtient les statistiques des logs
     * 
     * @param int $days Nombre de jours \u00e0 analyser
     * @return array Statistiques
     */
    public static function getStatistics(int $days = 7): array
    {
        $dateFrom = date('Y-m-d', strtotime("-{$days} days"));
        $entries = self::search(['date_from' => $dateFrom], PHP_INT_MAX);
        
        $stats = [
            'total_entries' => count($entries),
            'by_action' => [],
            'by_user' => [],
            'by_level' => [],
            'failed_logins' => 0,
            'data_accesses' => 0,
            'security_alerts' => 0
        ];
        
        foreach ($entries as $entry) {
            // Par action
            $action = $entry['action'];
            $stats['by_action'][$action] = ($stats['by_action'][$action] ?? 0) + 1;
            
            // Par utilisateur
            $username = $entry['username'];
            $stats['by_user'][$username] = ($stats['by_user'][$username] ?? 0) + 1;
            
            // Par niveau
            $level = $entry['level'];
            $stats['by_level'][$level] = ($stats['by_level'][$level] ?? 0) + 1;
            
            // Compteurs sp\u00e9cifiques
            if ($action === 'LOGIN_FAILED') $stats['failed_logins']++;
            if ($action === 'DATA_ACCESS') $stats['data_accesses']++;
            if ($action === 'SECURITY_ALERT') $stats['security_alerts']++;
        }
        
        return $stats;
    }
}
