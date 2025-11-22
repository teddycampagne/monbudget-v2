<?php

namespace MonBudget\Controllers;

use MonBudget\Services\NotificationService;

/**
 * Contrôleur de gestion des notifications
 *
 * Gère l'affichage et la gestion des notifications in-app
 *
 * @package MonBudget\Controllers
 * @author MonBudget Team
 * @version 2.4.0
 */
class NotificationController extends BaseController
{
    private NotificationService $notificationService;

    public function __construct()
    {
        parent::__construct();
        $this->notificationService = new NotificationService(Database::getInstance(), $this->getCurrentUserId());
    }

    /**
     * Afficher la page des notifications
     */
    public function index(): void
    {
        if (!$this->isAuthenticated()) {
            $this->redirect('login');
        }

        $page = (int)($_GET['page'] ?? 1);
        $limit = 20;
        $offset = ($page - 1) * $limit;
        $includeRead = isset($_GET['all']) && $_GET['all'] === '1';

        $notifications = $this->notificationService->getNotifications($limit, $offset, $includeRead);
        $unreadCount = $this->notificationService->getUnreadCount();
        $stats = $this->notificationService->getNotificationStats();

        // Calculer le nombre total pour la pagination
        $totalNotifications = $this->getTotalNotifications($includeRead);
        $totalPages = ceil($totalNotifications / $limit);

        $this->view('notifications.index', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
            'stats' => $stats,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'includeRead' => $includeRead
        ]);
    }

    /**
     * Obtenir le nombre total de notifications
     */
    private function getTotalNotifications($includeRead = false): int
    {
        $db = Database::getInstance();
        $whereClause = $includeRead ? "" : "AND is_read = 0";
        $stmt = $db->prepare("
            SELECT COUNT(*) as count
            FROM web_notifications
            WHERE user_id = ? {$whereClause}
        ");
        $stmt->bind_param("i", $this->getCurrentUserId());
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return (int)$row['count'];
    }

    /**
     * Marquer une notification comme lue (AJAX)
     */
    public function markAsRead(): void
    {
        if (!$this->isAuthenticated()) {
            $this->jsonResponse(['error' => 'Non authentifié'], 401);
            return;
        }

        $notificationId = (int)($_POST['notification_id'] ?? 0);

        if (!$notificationId) {
            $this->jsonResponse(['error' => 'ID de notification requis'], 400);
            return;
        }

        try {
            $success = $this->notificationService->markAsRead($notificationId);
            $unreadCount = $this->notificationService->getUnreadCount();

            $this->jsonResponse([
                'success' => $success,
                'unread_count' => $unreadCount
            ]);
        } catch (Exception $e) {
            $this->jsonResponse(['error' => 'Erreur lors du marquage'], 500);
        }
    }

    /**
     * Marquer toutes les notifications comme lues (AJAX)
     */
    public function markAllAsRead(): void
    {
        if (!$this->isAuthenticated()) {
            $this->jsonResponse(['error' => 'Non authentifié'], 401);
            return;
        }

        try {
            $success = $this->notificationService->markAllAsRead();
            $this->jsonResponse(['success' => $success]);
        } catch (Exception $e) {
            $this->jsonResponse(['error' => 'Erreur lors du marquage'], 500);
        }
    }

    /**
     * Supprimer une notification (AJAX)
     */
    public function delete(): void
    {
        if (!$this->isAuthenticated()) {
            $this->jsonResponse(['error' => 'Non authentifié'], 401);
            return;
        }

        $notificationId = (int)($_POST['notification_id'] ?? 0);

        if (!$notificationId) {
            $this->jsonResponse(['error' => 'ID de notification requis'], 400);
            return;
        }

        try {
            $success = $this->notificationService->deleteNotification($notificationId);
            $unreadCount = $this->notificationService->getUnreadCount();

            $this->jsonResponse([
                'success' => $success,
                'unread_count' => $unreadCount
            ]);
        } catch (Exception $e) {
            $this->jsonResponse(['error' => 'Erreur lors de la suppression'], 500);
        }
    }

    /**
     * Obtenir le nombre de notifications non lues (AJAX)
     */
    public function getUnreadCount(): void
    {
        if (!$this->isAuthenticated()) {
            $this->jsonResponse(['error' => 'Non authentifié'], 401);
            return;
        }

        try {
            $count = $this->notificationService->getUnreadCount();
            $this->jsonResponse(['count' => $count]);
        } catch (Exception $e) {
            $this->jsonResponse(['error' => 'Erreur lors de la récupération'], 500);
        }
    }

    /**
     * Obtenir les dernières notifications (pour le widget)
     */
    public function getLatest(): void
    {
        if (!$this->isAuthenticated()) {
            $this->jsonResponse(['error' => 'Non authentifié'], 401);
            return;
        }

        try {
            $notifications = $this->notificationService->getNotifications(5, 0, false);
            $this->jsonResponse(['notifications' => $notifications]);
        } catch (Exception $e) {
            $this->jsonResponse(['error' => 'Erreur lors de la récupération'], 500);
        }
    }
}
?>