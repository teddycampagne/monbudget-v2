<?php
/**
 * NotificationService
 * Handles in-app notifications for users
 */

class NotificationService
{
    private $db;
    private $userId;

    public function __construct($db, $userId = null)
    {
        $this->db = $db;
        $this->userId = $userId;
    }

    /**
     * Set the current user ID
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    /**
     * Create a new notification
     */
    public function createNotification($type, $title, $message, $data = null, $userId = null)
    {
        $targetUserId = $userId ?: $this->userId;
        if (!$targetUserId) {
            throw new Exception('User ID is required to create notification');
        }

        $stmt = $this->db->prepare("
            INSERT INTO web_notifications (user_id, type, title, message, data, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");

        $jsonData = $data ? json_encode($data) : null;
        $stmt->bind_param("issss", $targetUserId, $type, $title, $message, $jsonData);

        if (!$stmt->execute()) {
            throw new Exception('Failed to create notification: ' . $stmt->error);
        }

        return $this->db->insert_id;
    }

    /**
     * Get notifications for current user
     */
    public function getNotifications($limit = 50, $offset = 0, $includeRead = false)
    {
        if (!$this->userId) {
            return [];
        }

        $whereClause = $includeRead ? "" : "AND is_read = 0";
        $stmt = $this->db->prepare("
            SELECT id, type, title, message, data, is_read, created_at, read_at
            FROM web_notifications
            WHERE user_id = ? {$whereClause}
            ORDER BY created_at DESC
            LIMIT ? OFFSET ?
        ");

        $stmt->bind_param("iii", $this->userId, $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();

        $notifications = [];
        while ($row = $result->fetch_assoc()) {
            if ($row['data']) {
                $row['data'] = json_decode($row['data'], true);
            }
            $notifications[] = $row;
        }

        return $notifications;
    }

    /**
     * Get unread notification count
     */
    public function getUnreadCount()
    {
        if (!$this->userId) {
            return 0;
        }

        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count
            FROM web_notifications
            WHERE user_id = ? AND is_read = 0
        ");

        $stmt->bind_param("i", $this->userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        return (int)$row['count'];
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($notificationId)
    {
        if (!$this->userId) {
            return false;
        }

        $stmt = $this->db->prepare("
            UPDATE web_notifications
            SET is_read = 1, read_at = NOW()
            WHERE id = ? AND user_id = ?
        ");

        $stmt->bind_param("ii", $notificationId, $this->userId);
        return $stmt->execute();
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        if (!$this->userId) {
            return false;
        }

        $stmt = $this->db->prepare("
            UPDATE web_notifications
            SET is_read = 1, read_at = NOW()
            WHERE user_id = ? AND is_read = 0
        ");

        $stmt->bind_param("i", $this->userId);
        return $stmt->execute();
    }

    /**
     * Delete notification
     */
    public function deleteNotification($notificationId)
    {
        if (!$this->userId) {
            return false;
        }

        $stmt = $this->db->prepare("
            DELETE FROM web_notifications
            WHERE id = ? AND user_id = ?
        ");

        $stmt->bind_param("ii", $notificationId, $this->userId);
        return $stmt->execute();
    }

    /**
     * Get notification types and their counts
     */
    public function getNotificationStats()
    {
        if (!$this->userId) {
            return [];
        }

        $stmt = $this->db->prepare("
            SELECT type, COUNT(*) as count, SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread_count
            FROM web_notifications
            WHERE user_id = ?
            GROUP BY type
        ");

        $stmt->bind_param("i", $this->userId);
        $stmt->execute();
        $result = $stmt->get_result();

        $stats = [];
        while ($row = $result->fetch_assoc()) {
            $stats[$row['type']] = $row;
        }

        return $stats;
    }

    /**
     * Create budget alert notification
     */
    public function createBudgetAlertNotification($budgetId, $budgetName, $alertType, $currentAmount, $budgetLimit, $percentage = null)
    {
        $title = "Alerte budget";
        $message = "Le budget '{$budgetName}' ";

        switch ($alertType) {
            case 'threshold_80':
                $message .= "a atteint 80% de son plafond.";
                break;
            case 'threshold_90':
                $message .= "a atteint 90% de son plafond.";
                break;
            case 'exceeded':
                $message .= "a dépassé son plafond.";
                break;
            default:
                $message .= "a déclenché une alerte.";
        }

        $data = [
            'budget_id' => $budgetId,
            'alert_type' => $alertType,
            'current_amount' => $currentAmount,
            'budget_limit' => $budgetLimit,
            'percentage' => $percentage
        ];

        return $this->createNotification('budget_alert', $title, $message, $data);
    }

    /**
     * Create system notification
     */
    public function createSystemNotification($title, $message, $data = null, $userId = null)
    {
        return $this->createNotification('system', $title, $message, $data, $userId);
    }

    /**
     * Create info notification
     */
    public function createInfoNotification($title, $message, $data = null, $userId = null)
    {
        return $this->createNotification('info', $title, $message, $data, $userId);
    }

    /**
     * Create warning notification
     */
    public function createWarningNotification($title, $message, $data = null, $userId = null)
    {
        return $this->createNotification('warning', $title, $message, $data, $userId);
    }

    /**
     * Create error notification
     */
    public function createErrorNotification($title, $message, $data = null, $userId = null)
    {
        return $this->createNotification('error', $title, $message, $data, $userId);
    }

    /**
     * Clean old notifications (keep only last 100 per user, or older than 30 days)
     */
    public function cleanOldNotifications($daysOld = 30, $maxPerUser = 100)
    {
        // Delete notifications older than X days
        $stmt = $this->db->prepare("
            DELETE FROM web_notifications
            WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
        ");
        $stmt->bind_param("i", $daysOld);
        $stmt->execute();

        // For each user, keep only the most recent X notifications
        $usersStmt = $this->db->prepare("SELECT DISTINCT user_id FROM web_notifications");
        $usersStmt->execute();
        $usersResult = $usersStmt->get_result();

        while ($user = $usersResult->fetch_assoc()) {
            $userId = $user['user_id'];

            // Get notifications beyond the limit
            $stmt = $this->db->prepare("
                SELECT id FROM web_notifications
                WHERE user_id = ?
                ORDER BY created_at DESC
                LIMIT 999999 OFFSET ?
            ");
            $stmt->bind_param("ii", $userId, $maxPerUser);
            $stmt->execute();
            $result = $stmt->get_result();

            $idsToDelete = [];
            while ($row = $result->fetch_assoc()) {
                $idsToDelete[] = $row['id'];
            }

            if (!empty($idsToDelete)) {
                $placeholders = str_repeat('?,', count($idsToDelete) - 1) . '?';
                $deleteStmt = $this->db->prepare("DELETE FROM web_notifications WHERE id IN ({$placeholders})");
                $deleteStmt->bind_param(str_repeat('i', count($idsToDelete)), ...$idsToDelete);
                $deleteStmt->execute();
            }
        }

        return true;
    }
}
?>