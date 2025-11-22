<?php

namespace MonBudget\Services;

use MonBudget\Core\Database;
use MonBudget\Models\Budget;
use MonBudget\Models\User;
use MonBudget\Services\EmailService;

/**
 * Service de gestion des notifications de dépassement de budget
 *
 * Ce service gère :
 * - La détection des dépassements de budget
 * - L'envoi de notifications in-app
 * - L'envoi d'alertes par email
 * - La configuration des seuils d'alerte
 *
 * Types de notifications :
 * - warning : 80-90% du budget utilisé
 * - alert : 90-100% du budget utilisé
 * - critical : dépassement du budget
 */
class BudgetNotificationService
{
    // Types de notification
    const TYPE_WARNING = 'warning';
    const TYPE_ALERT = 'alert';
    const TYPE_CRITICAL = 'critical';

    // Seuils par défaut (en pourcentage)
    const DEFAULT_WARNING_THRESHOLD = 80.0;
    const DEFAULT_ALERT_THRESHOLD = 90.0;
    const DEFAULT_CRITICAL_THRESHOLD = 100.0;

    // Types de notification utilisateur
    const NOTIFICATION_NONE = 'none';
    const NOTIFICATION_IN_APP_ONLY = 'in_app_only';
    const NOTIFICATION_EMAIL_ONLY = 'email_only';
    const NOTIFICATION_BOTH = 'both';

    /**
     * Vérifie et traite les dépassements de budget pour un utilisateur
     *
     * @param int $userId ID de l'utilisateur
     * @param int $annee Année à vérifier
     * @param int|null $mois Mois à vérifier (null pour annuel)
     * @return array Résumé des notifications traitées
     */
    public function checkAndNotifyBudgetOverruns(int $userId, int $annee, ?int $mois = null): array
    {
        $result = [
            'checked' => 0,
            'notifications_created' => 0,
            'in_app_sent' => 0,
            'emails_sent' => 0,
            'errors' => []
        ];

        try {
            // Récupérer tous les budgets de la période
            $budgets = Budget::getAllByPeriod($userId, $annee, $mois);

            foreach ($budgets as $budget) {
                $result['checked']++;

                // Vérifier si ce budget nécessite une notification
                $notificationData = $this->shouldNotify($budget, $userId);

                if ($notificationData) {
                    // Créer la notification en base
                    $notificationId = $this->createNotification($notificationData);

                    if ($notificationId) {
                        $result['notifications_created']++;

                        // Envoyer les notifications selon la configuration utilisateur
                        $this->sendNotifications($notificationId, $userId);
                        $result['in_app_sent']++;
                        $result['emails_sent']++;
                    }
                }
            }

        } catch (\Exception $e) {
            $result['errors'][] = 'Erreur lors de la vérification des budgets: ' . $e->getMessage();
        }

        return $result;
    }

    /**
     * Détermine si un budget nécessite une notification
     *
     * @param array $budget Données du budget
     * @param int $userId ID de l'utilisateur
     * @return array|null Données de notification ou null
     */
    private function shouldNotify(array $budget, int $userId): ?array
    {
        // Vérifier si le budget est dépassé ou proche du dépassement
        $pourcentage = $budget['pourcentage_realise'] ?? 0;

        // Récupérer les seuils configurés par l'utilisateur
        $thresholds = $this->getUserThresholds($userId);

        $type = null;
        if ($pourcentage >= $thresholds['critical']) {
            $type = self::TYPE_CRITICAL;
        } elseif ($pourcentage >= $thresholds['alert']) {
            $type = self::TYPE_ALERT;
        } elseif ($pourcentage >= $thresholds['warning']) {
            $type = self::TYPE_WARNING;
        }

        if (!$type) {
            return null;
        }

        // Vérifier si une notification similaire n'a pas déjà été envoyée récemment
        if ($this->hasRecentNotification($budget['id'], $type)) {
            return null;
        }

        // Calculer les montants
        $montantDepasse = max(0, $budget['montant_realise'] - $budget['montant']);

        // Créer le message
        $message = $this->generateMessage($budget, $type, $pourcentage, $montantDepasse);

        return [
            'user_id' => $userId,
            'budget_id' => $budget['id'],
            'type' => $type,
            'message' => $message,
            'pourcentage_depasse' => $pourcentage,
            'montant_depasse' => $montantDepasse
        ];
    }

    /**
     * Vérifie si une notification similaire a été envoyée récemment
     *
     * @param int $budgetId ID du budget
     * @param string $type Type de notification
     * @return bool True si une notification récente existe
     */
    private function hasRecentNotification(int $budgetId, string $type): bool
    {
        // Vérifier les dernières 24h pour éviter le spam
        $yesterday = date('Y-m-d H:i:s', strtotime('-24 hours'));

        $existing = Database::selectOne(
            "SELECT id FROM budget_notifications
             WHERE budget_id = ? AND type = ? AND created_at > ?",
            [$budgetId, $type, $yesterday]
        );

        return $existing !== null;
    }

    /**
     * Génère le message de notification
     *
     * @param array $budget Données du budget
     * @param string $type Type de notification
     * @param float $pourcentage Pourcentage utilisé
     * @param float $montantDepasse Montant dépassé
     * @return string Message formaté
     */
    private function generateMessage(array $budget, string $type, float $pourcentage, float $montantDepasse): string
    {
        $categorie = $budget['categorie_nom'] ?? 'Catégorie inconnue';
        $montant = number_format($budget['montant'], 2, ',', ' ');
        $realise = number_format($budget['montant_realise'], 2, ',', ' ');

        switch ($type) {
            case self::TYPE_WARNING:
                return "⚠️ Attention : Le budget {$categorie} est à {$pourcentage}% d'utilisation " .
                       "({$realise} € sur {$montant} € prévu).";

            case self::TYPE_ALERT:
                return "🚨 Alerte : Le budget {$categorie} approche de la limite " .
                       "({$pourcentage}% - {$realise} € sur {$montant} €).";

            case self::TYPE_CRITICAL:
                $depasse = number_format($montantDepasse, 2, ',', ' ');
                return "🚨 URGENT : Dépassement du budget {$categorie} ! " .
                       "{$realise} € dépensés sur {$montant} € prévu (+{$depasse} €).";

            default:
                return "Notification budget {$categorie}";
        }
    }

    /**
     * Crée une notification en base de données
     *
     * @param array $data Données de la notification
     * @return int|null ID de la notification créée ou null en cas d'erreur
     */
    private function createNotification(array $data): ?int
    {
        try {
            return Database::insert(
                "INSERT INTO budget_notifications
                 (user_id, budget_id, type, message, pourcentage_depasse, montant_depasse)
                 VALUES (?, ?, ?, ?, ?, ?)",
                [
                    $data['user_id'],
                    $data['budget_id'],
                    $data['type'],
                    $data['message'],
                    $data['pourcentage_depasse'],
                    $data['montant_depasse']
                ]
            );
        } catch (\Exception $e) {
            if (config('app.debug', false)) {
                error_log("Erreur création notification budget: " . $e->getMessage());
            }
            return null;
        }
    }

    /**
     * Envoie les notifications selon la configuration utilisateur
     *
     * @param int $notificationId ID de la notification
     * @param int $userId ID de l'utilisateur
     */
    private function sendNotifications(int $notificationId, int $userId): void
    {
        $notificationType = $this->getUserNotificationPreference($userId);

        switch ($notificationType) {
            case self::NOTIFICATION_IN_APP_ONLY:
                $this->markNotificationSent($notificationId, 'in_app');
                break;

            case self::NOTIFICATION_EMAIL_ONLY:
                $this->sendEmailNotification($notificationId);
                break;

            case self::NOTIFICATION_BOTH:
                $this->markNotificationSent($notificationId, 'in_app');
                $this->sendEmailNotification($notificationId);
                break;

            case self::NOTIFICATION_NONE:
            default:
                // Ne rien faire
                break;
        }
    }

    /**
     * Récupère la préférence de notification de l'utilisateur
     *
     * @param int $userId ID de l'utilisateur
     * @return string Type de notification préféré
     */
    public function getUserNotificationPreference(int $userId): string
    {
        $config = Database::selectOne(
            "SELECT valeur FROM configuration WHERE cle = ?",
            ["budget_notifications_type_user_{$userId}"]
        );

        return $config['valeur'] ?? self::NOTIFICATION_IN_APP_ONLY;
    }

    /**
     * Définit la préférence de notification de l'utilisateur
     *
     * @param int $userId ID de l'utilisateur
     * @param string $type Type de notification
     * @return bool Succès de la sauvegarde
     */
    public function setUserNotificationPreference(int $userId, string $type): bool
    {
        // Vérifier que le type est valide
        $validTypes = [
            self::NOTIFICATION_NONE,
            self::NOTIFICATION_IN_APP_ONLY,
            self::NOTIFICATION_EMAIL_ONLY,
            self::NOTIFICATION_BOTH
        ];

        if (!in_array($type, $validTypes)) {
            return false;
        }

        try {
            // Supprimer l'ancienne configuration si elle existe
            Database::execute(
                "DELETE FROM configuration WHERE cle = ?",
                ["budget_notifications_type_user_{$userId}"]
            );

            // Insérer la nouvelle configuration
            Database::insert(
                "INSERT INTO configuration (cle, valeur, type, description) VALUES (?, ?, 'string', ?)",
                [
                    "budget_notifications_type_user_{$userId}",
                    $type,
                    "Type de notification pour les dépassements de budget"
                ]
            );

            return true;
        } catch (\Exception $e) {
            if (config('app.debug', false)) {
                error_log("Erreur sauvegarde préférence notification: " . $e->getMessage());
            }
            return false;
        }
    }

    /**
     * Marque une notification comme envoyée
     *
     * @param int $notificationId ID de la notification
     * @param string $type Type d'envoi ('in_app' ou 'email')
     */
    private function markNotificationSent(int $notificationId, string $type): void
    {
        $field = ($type === 'email') ? 'email_envoye' : 'notification_envoyee';

        Database::execute(
            "UPDATE budget_notifications SET {$field} = 1, sent_at = NOW() WHERE id = ?",
            [$notificationId]
        );
    }

    /**
     * Envoie une notification par email
     *
     * @param int $notificationId ID de la notification
     */
    private function sendEmailNotification(int $notificationId): void
    {
        try {
            $notification = Database::selectOne(
                "SELECT bn.*, u.email, u.username, c.nom as category_name
                 FROM budget_notifications bn
                 JOIN users u ON bn.user_id = u.id
                 JOIN categories c ON bn.budget_id = c.id
                 WHERE bn.id = ?",
                [$notificationId]
            );

            if (!$notification) {
                error_log("Notification non trouvée: $notificationId");
                return;
            }

            if (empty($notification['email'])) {
                error_log("Aucune adresse email pour l'utilisateur: " . $notification['user_id']);
                return;
            }

            // Créer le service d'email
            $emailService = new EmailService();

            // Construire le sujet selon le type de notification
            $subject = $this->buildEmailSubject($notification['type'], $notification['category_name']);

            // Construire le message HTML
            $message = $this->buildEmailMessage($notification);

            // Envoyer l'email
            $emailSent = $emailService->sendBudgetNotification(
                $notification['email'],
                $subject,
                $message,
                $notification['username']
            );

            if ($emailSent) {
                // Marquer comme envoyé
                $this->markNotificationSent($notificationId, 'email');
                if (config('app.debug', false)) {
                    error_log("Email envoyé avec succès pour la notification: $notificationId");
                }
            } else {
                if (config('app.debug', false)) {
                    error_log("Échec de l'envoi d'email pour la notification: $notificationId");
                }
            }

        } catch (\Exception $e) {
            if (config('app.debug', false)) {
                error_log("Erreur envoi email notification: " . $e->getMessage());
            }
        }
    }

    /**
     * Récupère les notifications non lues d'un utilisateur
     *
     * @param int $userId ID de l'utilisateur
     * @param int $limit Nombre maximum de notifications
     * @return array Liste des notifications
     */
    public function getUnreadNotifications(int $userId, int $limit = 10): array
    {
        return Database::select(
            "SELECT * FROM budget_notifications
             WHERE user_id = ? AND notification_envoyee = 1
             ORDER BY created_at DESC LIMIT ?",
            [$userId, $limit]
        );
    }

    /**
     * Compte les notifications non lues d'un utilisateur
     *
     * @param int $userId ID de l'utilisateur
     * @return int Nombre de notifications non lues
     */
    public function countUnreadNotifications(int $userId): int
    {
        $result = Database::selectOne(
            "SELECT COUNT(*) as count FROM budget_notifications
             WHERE user_id = ? AND notification_envoyee = 1",
            [$userId]
        );

        return (int)($result['count'] ?? 0);
    }

    /**
     * Récupère les notifications non lues avec informations sur le budget
     *
     * @param int $userId ID de l'utilisateur
     * @param int $limit Nombre maximum de notifications
     * @return array Liste des notifications avec infos budget
     */
    public function getUnreadNotificationsWithBudgetInfo(int $userId, int $limit = 10): array
    {
        return Database::select(
            "SELECT n.*, c.nom as categorie_nom, CONCAT(c.nom, ' (', b.montant, '€/', CASE b.periode WHEN 'mensuel' THEN 'mois' ELSE 'an' END, ')') as budget_nom
             FROM budget_notifications n
             LEFT JOIN budgets b ON n.budget_id = b.id
             LEFT JOIN categories c ON b.categorie_id = c.id
             WHERE n.user_id = ? AND n.notification_envoyee = 1
             ORDER BY n.created_at DESC LIMIT ?",
            [$userId, $limit]
        );
    }

    /**
     * Récupère les seuils d'alerte configurés pour un utilisateur
     *
     * @param int $userId ID de l'utilisateur
     * @return array Seuils configurés ou valeurs par défaut
     */
    public function getUserThresholds(int $userId): array
    {
        $warning = Database::selectOne(
            "SELECT valeur FROM configuration WHERE cle = ?",
            ["budget_thresholds_warning_user_{$userId}"]
        );

        $alert = Database::selectOne(
            "SELECT valeur FROM configuration WHERE cle = ?",
            ["budget_thresholds_alert_user_{$userId}"]
        );

        $critical = Database::selectOne(
            "SELECT valeur FROM configuration WHERE cle = ?",
            ["budget_thresholds_critical_user_{$userId}"]
        );

        return [
            'warning' => (float)($warning['valeur'] ?? self::DEFAULT_WARNING_THRESHOLD),
            'alert' => (float)($alert['valeur'] ?? self::DEFAULT_ALERT_THRESHOLD),
            'critical' => (float)($critical['valeur'] ?? self::DEFAULT_CRITICAL_THRESHOLD)
        ];
    }

    /**
     * Définit les seuils d'alerte pour un utilisateur
     *
     * @param int $userId ID de l'utilisateur
     * @param float $warning Seuil d'avertissement (en %)
     * @param float $alert Seuil d'alerte (en %)
     * @param float $critical Seuil critique (en %)
     * @return bool Succès de la sauvegarde
     */
    public function setUserThresholds(int $userId, float $warning, float $alert, float $critical): bool
    {
        // Validation des seuils
        if ($warning >= $alert || $alert >= $critical || $warning < 0 || $alert < 0 || $critical < 0) {
            return false;
        }

        try {
            // Supprimer les anciennes configurations
            Database::execute(
                "DELETE FROM configuration WHERE cle LIKE ?",
                ["budget_thresholds_%_user_{$userId}"]
            );

            // Insérer les nouvelles configurations
            $configs = [
                ["budget_thresholds_warning_user_{$userId}", $warning, 'float', 'Seuil d\'avertissement budget (%)'],
                ["budget_thresholds_alert_user_{$userId}", $alert, 'float', 'Seuil d\'alerte budget (%)'],
                ["budget_thresholds_critical_user_{$userId}", $critical, 'float', 'Seuil critique budget (%)']
            ];

            foreach ($configs as $config) {
                Database::insert(
                    "INSERT INTO configuration (cle, valeur, type, description) VALUES (?, ?, ?, ?)",
                    $config
                );
            }

            return true;
        } catch (\Exception $e) {
            if (config('app.debug', false)) {
                error_log("Erreur sauvegarde seuils: " . $e->getMessage());
            }
            return false;
        }
    }

    /**
     * Construit le sujet de l'email selon le type de notification
     *
     * @param string $type Type de notification
     * @param string $categoryName Nom de la catégorie
     * @return string Sujet de l'email
     */
    private function buildEmailSubject(string $type, string $categoryName): string
    {
        $subjects = [
            self::TYPE_WARNING => "⚠️ Alerte Budget - $categoryName approche de la limite",
            self::TYPE_ALERT => "🚨 Alerte Budget - $categoryName proche du dépassement",
            self::TYPE_CRITICAL => "🚨 URGENT - Dépassement de budget pour $categoryName"
        ];

        return $subjects[$type] ?? "Notification Budget - $categoryName";
    }

    /**
     * Construit le message HTML de l'email
     *
     * @param array $notification Données de la notification
     * @return string Message HTML
     */
    private function buildEmailMessage(array $notification): string
    {
        $categoryName = $notification['category_name'];
        $percentage = $notification['percentage_used'];
        $amountUsed = number_format($notification['amount_used'], 2, ',', ' ');
        $budgetAmount = number_format($notification['budget_amount'], 2, ',', ' ');
        $overrunAmount = $notification['overrun_amount'] > 0 ?
            number_format($notification['overrun_amount'], 2, ',', ' ') : null;

        $messages = [
            self::TYPE_WARNING => "Votre budget pour la catégorie <strong>$categoryName</strong> a atteint <strong>$percentage%</strong> d'utilisation.<br>
                <strong>Montant utilisé :</strong> $amountUsed €<br>
                <strong>Budget total :</strong> $budgetAmount €<br><br>
                Il vous reste encore de la marge, mais surveillez vos dépenses de près.",

            self::TYPE_ALERT => "Votre budget pour la catégorie <strong>$categoryName</strong> a atteint <strong>$percentage%</strong> d'utilisation.<br>
                <strong>Montant utilisé :</strong> $amountUsed €<br>
                <strong>Budget total :</strong> $budgetAmount €<br><br>
                Attention ! Vous approchez dangereusement de la limite de votre budget.",

            self::TYPE_CRITICAL => "Votre budget pour la catégorie <strong>$categoryName</strong> a été dépassé de <strong>$percentage%</strong>.<br>
                <strong>Montant utilisé :</strong> $amountUsed €<br>
                <strong>Budget total :</strong> $budgetAmount €<br>
                <strong>Dépassement :</strong> $overrunAmount €<br><br>
                <span style='color: #dc3545; font-weight: bold;'>Action requise :</span> Révisez vos dépenses ou ajustez votre budget."
        ];

        return $messages[$notification['type']] ?? $notification['message'];
    }
}