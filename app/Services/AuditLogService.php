<?php

namespace App\Services;

use App\Core\Database;
use Exception;

/**
 * Service de journalisation d'audit (PCI DSS Exigence 10)
 * 
 * Enregistre tous les événements de sécurité critiques :
 * - Authentifications (succès/échecs)
 * - Modifications de données sensibles
 * - Accès non autorisés
 * - Changements de configuration
 * 
 * Rétention : 1 an minimum (exigence PCI DSS)
 */
class AuditLogService
{
    private Database $db;

    // Types d'actions
    public const ACTION_LOGIN_SUCCESS = 'login_success';
    public const ACTION_LOGIN_FAILED = 'login_failed';
    public const ACTION_LOGOUT = 'logout';
    public const ACTION_PASSWORD_CHANGE = 'password_change';
    public const ACTION_PASSWORD_RESET = 'password_reset';
    public const ACTION_ACCOUNT_LOCKED = 'account_locked';
    public const ACTION_ACCOUNT_UNLOCKED = 'account_unlocked';
    
    public const ACTION_CREATE = 'create';
    public const ACTION_UPDATE = 'update';
    public const ACTION_DELETE = 'delete';
    public const ACTION_VIEW = 'view';
    public const ACTION_EXPORT = 'export';
    
    public const ACTION_UNAUTHORIZED_ACCESS = 'unauthorized_access';
    public const ACTION_PERMISSION_DENIED = 'permission_denied';
    public const ACTION_SUSPICIOUS_ACTIVITY = 'suspicious_activity';

    // Tables critiques nécessitant audit
    private const CRITICAL_TABLES = [
        'users',
        'comptes',
        'banques',
        'transactions',
        'budgets',
        'categories'
    ];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Enregistre un événement d'audit
     * 
     * @param string $action Type d'action (constantes ACTION_*)
     * @param string|null $tableName Table concernée (optionnel)
     * @param int|null $recordId ID de l'enregistrement (optionnel)
     * @param array|null $oldValues Valeurs avant modification (optionnel)
     * @param array|null $newValues Valeurs après modification (optionnel)
     * @param int|null $userId ID utilisateur (null si non authentifié)
     * @return int ID du log créé
     * @throws Exception Si l'enregistrement échoue
     */
    public function log(
        string $action,
        ?string $tableName = null,
        ?int $recordId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $userId = null
    ): int {
        // Récupérer l'utilisateur courant si non fourni
        if ($userId === null && isset($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id'];
        }

        // Récupérer les informations de la requête
        $ipAddress = $this->getClientIP();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? '';

        // Filtrer les données sensibles avant stockage
        if ($oldValues) {
            $oldValues = $this->sanitizeValues($oldValues);
        }
        if ($newValues) {
            $newValues = $this->sanitizeValues($newValues);
        }

        $stmt = $this->db->prepare("
            INSERT INTO audit_logs (
                user_id, action, table_name, record_id,
                old_values, new_values,
                ip_address, user_agent, request_uri, request_method,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->execute([
            $userId,
            $action,
            $tableName,
            $recordId,
            $oldValues ? json_encode($oldValues) : null,
            $newValues ? json_encode($newValues) : null,
            $ipAddress,
            substr($userAgent, 0, 255), // Limiter la longueur
            substr($requestUri, 0, 255),
            $requestMethod
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Enregistre une tentative de connexion
     * 
     * @param string $email Email de l'utilisateur
     * @param bool $success Connexion réussie ou non
     * @param int|null $userId ID utilisateur (null si échec)
     * @param string|null $reason Raison de l'échec (optionnel)
     */
    public function logLogin(string $email, bool $success, ?int $userId = null, ?string $reason = null): void
    {
        $action = $success ? self::ACTION_LOGIN_SUCCESS : self::ACTION_LOGIN_FAILED;
        
        $this->log(
            $action,
            'users',
            $userId,
            null,
            ['email' => $email, 'success' => $success, 'reason' => $reason],
            $userId
        );
    }

    /**
     * Enregistre une déconnexion
     * 
     * @param int $userId ID utilisateur
     */
    public function logLogout(int $userId): void
    {
        $this->log(self::ACTION_LOGOUT, 'users', $userId, null, null, $userId);
    }

    /**
     * Enregistre un changement de mot de passe
     * 
     * @param int $userId ID utilisateur
     * @param bool $forced Si true, changement forcé (expiration)
     */
    public function logPasswordChange(int $userId, bool $forced = false): void
    {
        $this->log(
            self::ACTION_PASSWORD_CHANGE,
            'users',
            $userId,
            null,
            ['forced' => $forced],
            $userId
        );
    }

    /**
     * Enregistre un verrouillage de compte
     * 
     * @param int $userId ID utilisateur
     * @param string $reason Raison du verrouillage
     */
    public function logAccountLocked(int $userId, string $reason): void
    {
        $this->log(
            self::ACTION_ACCOUNT_LOCKED,
            'users',
            $userId,
            null,
            ['reason' => $reason],
            $userId
        );
    }

    /**
     * Enregistre une modification de données
     * 
     * @param string $tableName Table modifiée
     * @param int $recordId ID enregistrement
     * @param array $oldValues Anciennes valeurs
     * @param array $newValues Nouvelles valeurs
     * @param int|null $userId ID utilisateur
     */
    public function logUpdate(
        string $tableName,
        int $recordId,
        array $oldValues,
        array $newValues,
        ?int $userId = null
    ): void {
        // Ne loguer que si table critique
        if (!in_array($tableName, self::CRITICAL_TABLES)) {
            return;
        }

        $this->log(
            self::ACTION_UPDATE,
            $tableName,
            $recordId,
            $oldValues,
            $newValues,
            $userId
        );
    }

    /**
     * Enregistre une création de données
     * 
     * @param string $tableName Table concernée
     * @param int $recordId ID enregistrement créé
     * @param array $values Valeurs de l'enregistrement
     * @param int|null $userId ID utilisateur
     */
    public function logCreate(
        string $tableName,
        int $recordId,
        array $values,
        ?int $userId = null
    ): void {
        if (!in_array($tableName, self::CRITICAL_TABLES)) {
            return;
        }

        $this->log(
            self::ACTION_CREATE,
            $tableName,
            $recordId,
            null,
            $values,
            $userId
        );
    }

    /**
     * Enregistre une suppression de données
     * 
     * @param string $tableName Table concernée
     * @param int $recordId ID enregistrement supprimé
     * @param array $values Valeurs de l'enregistrement supprimé
     * @param int|null $userId ID utilisateur
     */
    public function logDelete(
        string $tableName,
        int $recordId,
        array $values,
        ?int $userId = null
    ): void {
        if (!in_array($tableName, self::CRITICAL_TABLES)) {
            return;
        }

        $this->log(
            self::ACTION_DELETE,
            $tableName,
            $recordId,
            $values,
            null,
            $userId
        );
    }

    /**
     * Enregistre un accès non autorisé
     * 
     * @param string $resource Ressource tentée
     * @param int|null $userId ID utilisateur (null si non authentifié)
     */
    public function logUnauthorizedAccess(string $resource, ?int $userId = null): void
    {
        $this->log(
            self::ACTION_UNAUTHORIZED_ACCESS,
            null,
            null,
            null,
            ['resource' => $resource],
            $userId
        );
    }

    /**
     * Enregistre une activité suspecte
     * 
     * @param string $description Description de l'activité
     * @param array $context Contexte additionnel
     * @param int|null $userId ID utilisateur
     */
    public function logSuspiciousActivity(string $description, array $context = [], ?int $userId = null): void
    {
        $this->log(
            self::ACTION_SUSPICIOUS_ACTIVITY,
            null,
            null,
            null,
            array_merge(['description' => $description], $context),
            $userId
        );
    }

    /**
     * Récupère les logs d'audit avec filtres
     * 
     * @param array $filters Filtres (user_id, action, table_name, date_from, date_to)
     * @param int $limit Nombre de résultats
     * @param int $offset Offset pour pagination
     * @return array Logs d'audit
     */
    public function getLogs(array $filters = [], int $limit = 100, int $offset = 0): array
    {
        $where = [];
        $params = [];

        if (isset($filters['user_id'])) {
            $where[] = 'user_id = ?';
            $params[] = $filters['user_id'];
        }

        if (isset($filters['action'])) {
            $where[] = 'action = ?';
            $params[] = $filters['action'];
        }

        if (isset($filters['table_name'])) {
            $where[] = 'table_name = ?';
            $params[] = $filters['table_name'];
        }

        if (isset($filters['date_from'])) {
            $where[] = 'created_at >= ?';
            $params[] = $filters['date_from'];
        }

        if (isset($filters['date_to'])) {
            $where[] = 'created_at <= ?';
            $params[] = $filters['date_to'];
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = $this->db->prepare("
            SELECT 
                al.*,
                u.email as user_email,
                u.nom as user_nom,
                u.prenom as user_prenom
            FROM audit_logs al
            LEFT JOIN users u ON al.user_id = u.id
            $whereClause
            ORDER BY al.created_at DESC
            LIMIT ? OFFSET ?
        ");

        $params[] = $limit;
        $params[] = $offset;

        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Nettoie les logs anciens (> 1 an)
     * Conforme à PCI DSS : rétention minimum 1 an
     * 
     * @param int $retentionDays Jours de rétention (défaut: 365)
     * @return int Nombre de logs supprimés
     */
    public function cleanOldLogs(int $retentionDays = 365): int
    {
        $stmt = $this->db->prepare("
            DELETE FROM audit_logs
            WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
        ");

        $stmt->execute([$retentionDays]);
        return $stmt->rowCount();
    }

    /**
     * Récupère l'adresse IP du client
     * 
     * @return string Adresse IP
     */
    private function getClientIP(): string
    {
        $ipKeys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];

        foreach ($ipKeys as $key) {
            if (isset($_SERVER[$key]) && filter_var($_SERVER[$key], FILTER_VALIDATE_IP)) {
                return $_SERVER[$key];
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Filtre les données sensibles avant stockage dans les logs
     * 
     * @param array $values Valeurs à filtrer
     * @return array Valeurs filtrées
     */
    private function sanitizeValues(array $values): array
    {
        $sensitiveFields = [
            'password',
            'password_hash',
            'mot_de_passe',
            'iban',
            'card_number',
            'cvv',
            'ssn',
            'api_key',
            'secret',
            'token'
        ];

        foreach ($values as $key => $value) {
            $lowerKey = strtolower($key);
            
            // Masquer les champs sensibles
            foreach ($sensitiveFields as $sensitiveField) {
                if (str_contains($lowerKey, $sensitiveField)) {
                    $values[$key] = '[REDACTED]';
                    break;
                }
            }
        }

        return $values;
    }

    /**
     * Génère un rapport d'audit pour une période
     * 
     * @param string $dateFrom Date début (format: Y-m-d)
     * @param string $dateTo Date fin (format: Y-m-d)
     * @return array Statistiques d'audit
     */
    public function getAuditReport(string $dateFrom, string $dateTo): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                action,
                COUNT(*) as count,
                COUNT(DISTINCT user_id) as unique_users,
                COUNT(DISTINCT ip_address) as unique_ips
            FROM audit_logs
            WHERE created_at BETWEEN ? AND ?
            GROUP BY action
            ORDER BY count DESC
        ");

        $stmt->execute([$dateFrom, $dateTo]);
        $actionStats = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Statistiques par utilisateur
        $stmt = $this->db->prepare("
            SELECT 
                u.email,
                u.nom,
                u.prenom,
                COUNT(*) as action_count,
                MAX(al.created_at) as last_activity
            FROM audit_logs al
            LEFT JOIN users u ON al.user_id = u.id
            WHERE al.created_at BETWEEN ? AND ?
            GROUP BY al.user_id
            ORDER BY action_count DESC
            LIMIT 10
        ");

        $stmt->execute([$dateFrom, $dateTo]);
        $userStats = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return [
            'period' => ['from' => $dateFrom, 'to' => $dateTo],
            'actions' => $actionStats,
            'top_users' => $userStats
        ];
    }
}
