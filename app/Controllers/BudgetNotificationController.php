<?php

namespace MonBudget\Controllers;

use MonBudget\Services\BudgetNotificationService;

/**
 * Contrôleur de gestion des notifications de budget
 */
class BudgetNotificationController extends BaseController
{
    private BudgetNotificationService $notificationService;

    public function __construct()
    {
        parent::__construct();
        $this->notificationService = new BudgetNotificationService();
    }

    /**
     * Affiche la page de configuration des notifications
     */
    public function settings(): void
    {
        $this->requireAuth();

        $currentPreference = $this->notificationService->getUserNotificationPreference($this->userId);
        $currentThresholds = $this->notificationService->getUserThresholds($this->userId);

        $this->view('budget_notifications/settings', [
            'title' => 'Notifications de Budget',
            'current_preference' => $currentPreference,
            'current_thresholds' => $currentThresholds,
            'notification_types' => [
                BudgetNotificationService::NOTIFICATION_NONE => 'Aucune notification',
                BudgetNotificationService::NOTIFICATION_IN_APP_ONLY => 'Notifications in-app uniquement',
                BudgetNotificationService::NOTIFICATION_EMAIL_ONLY => 'Alertes email uniquement',
                BudgetNotificationService::NOTIFICATION_BOTH => 'Notifications in-app + emails'
            ]
        ]);
    }

    /**
     * Met à jour les préférences de notification
     */
    public function updateSettings(): void
    {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('budget-notifications/settings');
        }

        $notificationType = $_POST['notification_type'] ?? BudgetNotificationService::NOTIFICATION_IN_APP_ONLY;

        $validTypes = [
            BudgetNotificationService::NOTIFICATION_NONE,
            BudgetNotificationService::NOTIFICATION_IN_APP_ONLY,
            BudgetNotificationService::NOTIFICATION_EMAIL_ONLY,
            BudgetNotificationService::NOTIFICATION_BOTH
        ];

        if (!in_array($notificationType, $validTypes)) {
            flash('error', 'Type de notification invalide.');
            $this->redirect('budget-notifications/settings');
        }

        // Gestion des seuils d'alerte
        $warningThreshold = (float)($_POST['warning_threshold'] ?? BudgetNotificationService::DEFAULT_WARNING_THRESHOLD);
        $alertThreshold = (float)($_POST['alert_threshold'] ?? BudgetNotificationService::DEFAULT_ALERT_THRESHOLD);
        $criticalThreshold = (float)($_POST['critical_threshold'] ?? BudgetNotificationService::DEFAULT_CRITICAL_THRESHOLD);

        // Validation des seuils
        if ($warningThreshold >= $alertThreshold || $alertThreshold >= $criticalThreshold ||
            $warningThreshold < 0 || $alertThreshold < 0 || $criticalThreshold < 0) {
            flash('error', 'Les seuils doivent être croissants et positifs.');
            $this->redirect('budget-notifications/settings');
        }

        $success = true;
        $errors = [];

        // Sauvegarder le type de notification
        if (!$this->notificationService->setUserNotificationPreference($this->userId, $notificationType)) {
            $success = false;
            $errors[] = 'Erreur lors de la sauvegarde du type de notification.';
        }

        // Sauvegarder les seuils
        if (!$this->notificationService->setUserThresholds($this->userId, $warningThreshold, $alertThreshold, $criticalThreshold)) {
            $success = false;
            $errors[] = 'Erreur lors de la sauvegarde des seuils d\'alerte.';
        }

        if ($success) {
            flash('success', 'Préférences de notification mises à jour avec succès.');
        } else {
            flash('error', 'Erreurs lors de la sauvegarde: ' . implode(', ', $errors));
        }

        $this->redirect('budget-notifications/settings');
    }

    /**
     * Affiche la liste des notifications
     */
    public function index(): void
    {
        $this->requireAuth();

        $notifications = $this->notificationService->getUnreadNotificationsWithBudgetInfo($this->userId);

        $this->view('budget_notifications/index', [
            'title' => 'Notifications Budget',
            'notifications' => $notifications
        ]);
    }

    /**
     * Marque une notification comme lue
     */
    public function markAsRead(): void
    {
        $this->requireAuth();

        $notificationId = (int)($_POST['notification_id'] ?? 0);

        if ($notificationId <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'ID de notification invalide']);
        }

        if ($this->notificationService->markAsRead($notificationId, $this->userId)) {
            $this->jsonResponse(['success' => true]);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Erreur lors de la mise à jour']);
        }
    }

    /**
     * API: Vérifie les dépassements de budget (pour les tâches automatiques)
     */
    public function checkOverruns(): void
    {
        // Cette méthode peut être appelée par une tâche cron
        // Pour l'instant, on la rend accessible via une route admin

        $this->requireAuth();

        if (!$this->isAdmin()) {
            $this->jsonResponse(['success' => false, 'message' => 'Accès non autorisé']);
        }

        $annee = (int)($_GET['annee'] ?? date('Y'));
        $mois = isset($_GET['mois']) ? (int)$_GET['mois'] : null;

        $result = $this->notificationService->checkAndNotifyBudgetOverruns($this->userId, $annee, $mois);

        $this->jsonResponse([
            'success' => true,
            'result' => $result
        ]);
    }

    /**
     * Méthode utilitaire pour vérifier si l'utilisateur est admin
     */
    protected function isAdmin(): bool
    {
        return isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin';
    }
}