<?php

namespace MonBudget\Services;

use MonBudget\Core\Database;
use PDO;

/**
 * Service de gestion des alertes budgétaires
 * Phase 3: Budget Alerts - Détection et notification des dépassements
 *
 * @package MonBudget\Services
 * @author MonBudget Team
 * @version 2.4.0
 */
class BudgetAlertService
{
    private $db;
    private $mailService;

    // Seuils d'alerte par défaut
    const THRESHOLD_80 = 80.0;
    const THRESHOLD_90 = 90.0;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->mailService = new MailService($this->db);
    }

    /**
     * Vérifier le statut d'un budget spécifique
     *
     * @param int $budgetId ID du budget
     * @return array Résultat de la vérification
     */
    public function checkBudgetStatus(int $budgetId): array
    {
        // Récupérer le budget
        $budget = $this->getBudget($budgetId);
        if (!$budget) {
            return ['success' => false, 'message' => 'Budget non trouvé'];
        }

        // Calculer l'usage actuel
        $usage = $this->calculateBudgetUsage($budgetId);
        $percentage = $usage['percentage'];

        $alerts = [];

        // Vérifier les seuils
        if ($percentage >= self::THRESHOLD_80) {
            $alerts[] = $this->checkThresholdAlert($budget, $usage, 'threshold_80', self::THRESHOLD_80);
        }

        if ($percentage >= self::THRESHOLD_90) {
            $alerts[] = $this->checkThresholdAlert($budget, $usage, 'threshold_90', self::THRESHOLD_90);
        }

        if ($percentage >= 100) {
            $alerts[] = $this->checkExceededAlert($budget, $usage);
        }

        return [
            'success' => true,
            'budget_id' => $budgetId,
            'percentage' => $percentage,
            'alerts' => array_filter($alerts)
        ];
    }

    /**
     * Vérifier tous les budgets d'un utilisateur
     *
     * @param int $userId ID de l'utilisateur
     * @return void
     */
    public function checkAllUserBudgets(int $userId): void
    {
        // Récupérer les budgets actifs de l'utilisateur
        $budgets = $this->getUserActiveBudgets($userId);

        foreach ($budgets as $budget) {
            $this->checkBudgetStatus($budget['id']);
        }
    }

    /**
     * Calculer l'usage d'un budget (pourcentage et montants)
     *
     * @param int $budgetId ID du budget
     * @return array ['percentage' => float, 'spent' => float, 'budget' => float]
     */
    public function calculateBudgetUsage(int $budgetId): array
    {
        $budget = $this->getBudget($budgetId);
        if (!$budget) {
            return ['percentage' => 0, 'spent' => 0, 'budget' => 0];
        }

        // Calculer les dépenses pour ce budget
        $spent = $this->getBudgetSpentAmount($budgetId, $budget['period'], $budget['start_date']);

        $percentage = $budget['amount'] > 0 ? ($spent / $budget['amount']) * 100 : 0;

        return [
            'percentage' => round($percentage, 2),
            'spent' => $spent,
            'budget' => $budget['amount']
        ];
    }

    /**
     * Obtenir le montant restant d'un budget
     *
     * @param int $budgetId ID du budget
     * @return float Montant restant
     */
    public function getBudgetRemaining(int $budgetId): float
    {
        $usage = $this->calculateBudgetUsage($budgetId);
        return max(0, $usage['budget'] - $usage['spent']);
    }

    /**
     * Récupérer les utilisateurs avec alertes activées
     *
     * @return array Liste des utilisateurs
     */
    public function getUsersWithAlertsEnabled(): array
    {
        $stmt = $this->db->prepare("
            SELECT DISTINCT u.id, u.username, u.email
            FROM users u
            INNER JOIN notifications_settings ns ON u.id = ns.user_id
            WHERE u.is_active = 1
            AND ns.budget_alert_enabled = 1
        ");
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Récupérer les budgets actifs d'un utilisateur (méthode publique pour CLI)
     *
     * @param int $userId ID de l'utilisateur
     * @return array Liste des budgets
     */
    public function getUserActiveBudgetsPublic(int $userId): array
    {
        return $this->getUserActiveBudgets($userId);
    }

    /**
     * Récupérer l'historique des alertes d'un utilisateur
     *
     * @param int $userId ID de l'utilisateur
     * @param int $days Nombre de jours (défaut 30)
     * @return array Historique des alertes
     */
    public function getAlertHistory(int $userId, int $days = 30): array
    {
        $stmt = $this->db->prepare("
            SELECT ba.*, b.name as budget_name, b.amount as budget_amount
            FROM budget_alerts ba
            INNER JOIN budgets b ON ba.budget_id = b.id
            WHERE ba.user_id = ?
            AND ba.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ORDER BY ba.created_at DESC
        ");
        $stmt->execute([$userId, $days]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Marquer une alerte comme envoyée
     *
     * @param int $budgetId ID du budget
     * @param string $type Type d'alerte
     * @return void
     */
    public function markAlertSent(int $budgetId, string $type): void
    {
        $stmt = $this->db->prepare("
            UPDATE budget_alerts
            SET email_sent = 1
            WHERE budget_id = ?
            AND alert_type = ?
            AND DATE(created_at) = CURDATE()
        ");
        $stmt->execute([$budgetId, $type]);
    }

    // =========================================================================
    // MÉTHODES PRIVÉES
    // =========================================================================

    /**
     * Récupérer un budget par ID
     */
    private function getBudget(int $budgetId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM budgets WHERE id = ? LIMIT 1
        ");
        $stmt->execute([$budgetId]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Récupérer les budgets actifs d'un utilisateur
     */
    private function getUserActiveBudgets(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM budgets
            WHERE user_id = ?
            AND is_active = 1
            AND start_date <= CURDATE()
            AND (end_date IS NULL OR end_date >= CURDATE())
        ");
        $stmt->execute([$userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Calculer le montant dépensé pour un budget
     */
    private function getBudgetSpentAmount(int $budgetId, string $period, string $startDate): float
    {
        // Déterminer la période
        $dateCondition = $this->getPeriodDateCondition($period, $startDate);

        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(t.amount), 0) as spent
            FROM transactions t
            INNER JOIN budget_categories bc ON t.category_id = bc.category_id
            WHERE bc.budget_id = ?
            AND t.type = 'expense'
            AND t.date >= ?
            AND t.date <= CURDATE()
        ");
        $stmt->execute([$budgetId, $dateCondition['start']]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (float)$result['spent'];
    }

    /**
     * Obtenir les conditions de date pour une période
     */
    private function getPeriodDateCondition(string $period, string $startDate): array
    {
        $start = $startDate;

        switch ($period) {
            case 'weekly':
                // Lundi de la semaine courante
                $start = date('Y-m-d', strtotime('monday this week'));
                break;
            case 'monthly':
                // Premier jour du mois
                $start = date('Y-m-01');
                break;
            case 'yearly':
                // Premier jour de l'année
                $start = date('Y-01-01');
                break;
            case 'custom':
                // Utiliser la date de début spécifiée
                break;
        }

        return ['start' => $start, 'end' => date('Y-m-d')];
    }

    /**
     * Vérifier et envoyer une alerte de seuil
     */
    private function checkThresholdAlert(array $budget, array $usage, string $type, float $threshold): ?array
    {
        // Vérifier si l'alerte a déjà été envoyée aujourd'hui
        if ($this->alertAlreadySentToday($budget['id'], $type)) {
            return null;
        }

        // Récupérer les paramètres de notification
        $settings = getUserNotificationSettings($budget['user_id']);
        if (!$settings) {
            return null;
        }

        // Vérifier si ce type d'alerte est activé
        $settingKey = $type === 'threshold_80' ? 'budget_threshold_80' : 'budget_threshold_90';
        if (!$settings[$settingKey]) {
            return null;
        }

        // Enregistrer l'alerte
        $alertId = $this->recordAlert($budget, $usage, $type);

        // Envoyer les notifications
        $this->sendThresholdAlert($budget, $usage, $threshold);

        return [
            'type' => $type,
            'threshold' => $threshold,
            'percentage' => $usage['percentage'],
            'alert_id' => $alertId
        ];
    }

    /**
     * Vérifier et envoyer une alerte de dépassement
     */
    private function checkExceededAlert(array $budget, array $usage): ?array
    {
        // Vérifier si l'alerte a déjà été envoyée aujourd'hui
        if ($this->alertAlreadySentToday($budget['id'], 'exceeded')) {
            return null;
        }

        // Vérifier si l'alerte dépassement est activée
        $settings = getUserNotificationSettings($budget['user_id']);
        if (!$settings || !$settings['budget_exceeded']) {
            return null;
        }

        // Calculer le dépassement
        $overspent = $usage['spent'] - $usage['budget'];

        // Enregistrer l'alerte
        $alertId = $this->recordAlert($budget, $usage, 'exceeded');

        // Envoyer les notifications
        $this->sendExceededAlert($budget, $usage, $overspent);

        return [
            'type' => 'exceeded',
            'overspent' => $overspent,
            'percentage' => $usage['percentage'],
            'alert_id' => $alertId
        ];
    }

    /**
     * Vérifier si une alerte a déjà été envoyée aujourd'hui
     */
    private function alertAlreadySentToday(int $budgetId, string $type): bool
    {
        $stmt = $this->db->prepare("
            SELECT id FROM budget_alerts
            WHERE budget_id = ?
            AND alert_type = ?
            AND DATE(created_at) = CURDATE()
            LIMIT 1
        ");
        $stmt->execute([$budgetId, $type]);

        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }

    /**
     * Enregistrer une alerte dans la base de données
     */
    private function recordAlert(array $budget, array $usage, string $type): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO budget_alerts
            (budget_id, user_id, alert_type, budget_amount, spent_amount, percentage)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $budget['id'],
            $budget['user_id'],
            $type,
            $usage['budget'],
            $usage['spent'],
            $usage['percentage']
        ]);

        return $this->db->lastInsertId();
    }

    /**
     * Envoyer une alerte de seuil
     */
    private function sendThresholdAlert(array $budget, array $usage, float $threshold): bool
    {
        $user = $this->getUser($budget['user_id']);
        if (!$user) {
            return false;
        }

        $settings = getUserNotificationSettings($budget['user_id']);
        $sent = false;

        // Email
        if ($settings['notify_email']) {
            $this->mailService->sendTemplateFromFile(
                'budget_alert_threshold',
                [
                    'user' => $user,
                    'budget' => $budget,
                    'usage' => $usage,
                    'threshold' => $threshold
                ],
                $user['email']
            );
            $sent = true;
        }

        // Notification web (sera implémenté dans Phase 4)
        if ($settings['notify_web']) {
            // TODO: Implémenter notification web
        }

        return $sent;
    }

    /**
     * Envoyer une alerte de dépassement
     */
    private function sendExceededAlert(array $budget, array $usage, float $overspent): bool
    {
        $user = $this->getUser($budget['user_id']);
        if (!$user) {
            return false;
        }

        $settings = getUserNotificationSettings($budget['user_id']);
        $sent = false;

        // Email
        if ($settings['notify_email']) {
            $this->mailService->sendTemplateFromFile(
                'budget_alert_exceeded',
                [
                    'user' => $user,
                    'budget' => $budget,
                    'usage' => $usage,
                    'overspent' => $overspent
                ],
                $user['email']
            );
            $sent = true;
        }

        // Notification web (sera implémenté dans Phase 4)
        if ($settings['notify_web']) {
            // TODO: Implémenter notification web
        }

        return $sent;
    }

    /**
     * Récupérer un utilisateur par ID
     */
    private function getUser(int $userId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT id, username, email FROM users WHERE id = ? LIMIT 1
        ");
        $stmt->execute([$userId]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}