<?php

namespace MonBudget\Services;

/**
 * Service d'envoi d'emails
 *
 * Service générique pour l'envoi d'emails dans MonBudget :
 * - Notifications de budget
 * - Réinitialisation de mot de passe
 * - Tickets d'administration
 * - Autres communications
 */
class EmailService
{
    /**
     * Configuration SMTP (pour développement local)
     */
    private const SMTP_HOST = 'localhost';
    private const SMTP_PORT = 25;
    private const FROM_EMAIL = 'noreply@monbudget.local';
    private const FROM_NAME = 'MonBudget';
    private const DEV_MODE = true; // En développement, logger les emails au lieu de les envoyer

    /**
     * Envoie un email générique
     *
     * @param string $to Adresse email du destinataire
     * @param string $subject Sujet de l'email
     * @param string $message Corps de l'email (HTML ou texte)
     * @param string $recipientName Nom du destinataire
     * @param bool $isHtml Si true, le message est traité comme HTML
     * @return bool Succès de l'envoi
     */
    public function sendEmail(string $to, string $subject, string $message, string $recipientName = '', bool $isHtml = true): bool
    {
        try {
            // MODE DÉVELOPPEMENT : Logger l'email au lieu de l'envoyer
            if (self::DEV_MODE) {
                return $this->logEmail($to, $subject, $message, $recipientName, $isHtml);
            }

            // En-têtes de base
            $headers = [
                'MIME-Version: 1.0',
                'From: ' . self::FROM_NAME . ' <' . self::FROM_EMAIL . '>',
                'Reply-To: ' . self::FROM_EMAIL,
                'X-Mailer: MonBudget System'
            ];

            // Type de contenu selon le format
            if ($isHtml) {
                $headers[] = 'Content-type: text/html; charset=UTF-8';
                $htmlBody = $this->buildHtmlEmail($recipientName, $message);
            } else {
                $headers[] = 'Content-type: text/plain; charset=UTF-8';
                $htmlBody = $message;
            }

            // Sujet avec encodage UTF-8
            $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

            // Envoi de l'email
            $result = mail($to, $encodedSubject, $htmlBody, implode("\r\n", $headers));

            if ($result) {
                // Log succès seulement en mode debug
                if (config('app.debug', false)) {
                    error_log("Email envoyé avec succès à: $to - Sujet: $subject");
                }
                return true;
            } else {
                // Log échec seulement en mode debug
                if (config('app.debug', false)) {
                    error_log("Échec de l'envoi d'email à: $to");
                }
                return false;
            }

        } catch (\Exception $e) {
            error_log("Erreur lors de l'envoi d'email: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Log un email en mode développement (au lieu de l'envoyer)
     */
    private function logEmail(string $to, string $subject, string $message, string $recipientName = '', bool $isHtml = true): bool
    {
        try {
            // Créer le dossier de logs s'il n'existe pas
            $logDir = BASE_PATH . '/storage/logs';
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }

            $logFile = $logDir . '/mail.log';
            $timestamp = date('Y-m-d H:i:s');

            // Construire le contenu de l'email pour le log
            $logContent = "=== EMAIL LOG - $timestamp ===\n";
            $logContent .= "To: $to\n";
            $logContent .= "Subject: $subject\n";
            $logContent .= "Recipient Name: $recipientName\n";
            $logContent .= "Format: " . ($isHtml ? 'HTML' : 'Text') . "\n";
            $logContent .= "From: " . self::FROM_NAME . " <" . self::FROM_EMAIL . ">\n\n";

            if ($isHtml) {
                $logContent .= "HTML Body:\n" . $this->buildHtmlEmail($recipientName, $message) . "\n";
            } else {
                $logContent .= "Text Body:\n$message\n";
            }

            $logContent .= "\n=== END EMAIL ===\n\n";

            // Écrire dans le fichier de log
            file_put_contents($logFile, $logContent, FILE_APPEND | LOCK_EX);

            // Log succès seulement en mode debug
            if (config('app.debug', false)) {
                error_log("Email loggé avec succès pour: $to - Sujet: $subject");
            }
            return true;

        } catch (\Exception $e) {
            error_log("Erreur lors du logging d'email: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Construit le corps HTML de l'email
     *
     * @param string $recipientName Nom du destinataire
     * @param string $message Message de notification
     * @return string Corps HTML complet
     */
    private function buildHtmlEmail(string $recipientName, string $message): string
    {
        $greeting = !empty($recipientName) ? "Bonjour $recipientName," : "Bonjour,";

        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification MonBudget</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            background-color: #f8f9fa;
        }
        .container {
            background-color: white;
            margin: 20px;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #0d6efd;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #0d6efd;
        }
        .content {
            margin: 20px 0;
        }
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .alert-warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
        }
        .alert-danger {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .alert-info {
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            font-size: 12px;
            color: #6c757d;
            text-align: center;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #0d6efd;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 5px;
        }
        .btn:hover {
            background-color: #0b5ed7;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">🧾 MonBudget</div>
            <h2>Notification de Budget</h2>
        </div>

        <div class="content">
            <p>$greeting</p>

            <div class="alert alert-info">
                $message
            </div>

            <p>Nous vous recommandons de consulter votre tableau de bord pour prendre les mesures nécessaires.</p>

            <div style="text-align: center; margin: 30px 0;">
                <a href="http://localhost:8000/dashboard" class="btn">Accéder à MonBudget</a>
                <a href="http://localhost:8000/budgets" class="btn">Voir mes Budgets</a>
            </div>
        </div>

        <div class="footer">
            <p>
                Cet email a été envoyé automatiquement par MonBudget.<br>
                Si vous ne souhaitez plus recevoir ces notifications,
                vous pouvez modifier vos préférences dans les paramètres de l'application.
            </p>
            <p>
                <small>
                    MonBudget - Application de gestion budgétaire<br>
                    © 2025 MonBudget. Tous droits réservés.
                </small>
            </p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Envoie un email de notification budget
     *
     * @param string $to Adresse email du destinataire
     * @param string $subject Sujet de l'email
     * @param string $message Corps de l'email (HTML)
     * @param string $recipientName Nom du destinataire
     * @return bool Succès de l'envoi
     */
    public function sendBudgetNotification(string $to, string $subject, string $message, string $recipientName = ''): bool
    {
        return $this->sendEmail($to, $subject, $message, $recipientName, true);
    }

    /**
     * Envoie un email de réinitialisation de mot de passe
     *
     * @param string $to Adresse email du destinataire
     * @param string $resetToken Token de réinitialisation
     * @param string $recipientName Nom du destinataire
     * @return bool Succès de l'envoi
     */
    public function sendPasswordReset(string $to, string $resetToken, string $recipientName = ''): bool
    {
        $subject = 'Réinitialisation de votre mot de passe - MonBudget';

        $resetLink = "http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . (defined('BASE_URL') && BASE_URL !== '' ? BASE_URL : '') . "/reset-password?token=" . $resetToken;

        $message = "
            <h2>Réinitialisation de mot de passe</h2>
            <p>Bonjour" . (!empty($recipientName) ? " $recipientName" : "") . ",</p>

            <p>Vous avez demandé la réinitialisation de votre mot de passe pour votre compte MonBudget.</p>

            <p>Cliquez sur le lien ci-dessous pour définir un nouveau mot de passe :</p>

            <div style='text-align: center; margin: 30px 0;'>
                <a href='$resetLink' style='background-color: #0d6efd; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;'>Réinitialiser mon mot de passe</a>
            </div>

            <p><strong>Ce lien expire dans 1 heure.</strong></p>

            <p>Si vous n'avez pas demandé cette réinitialisation, ignorez simplement cet email.</p>

            <p>Cordialement,<br>L'équipe MonBudget</p>
        ";

        return $this->sendEmail($to, $subject, $message, $recipientName, true);
    }

    /**
     * Envoie un email de ticket d'administration
     *
     * @param string $to Adresse email du destinataire
     * @param string $ticketId ID du ticket
     * @param string $subject Sujet du ticket
     * @param string $message Message du ticket
     * @param string $recipientName Nom du destinataire
     * @return bool Succès de l'envoi
     */
    public function sendAdminTicket(string $to, string $ticketId, string $subject, string $message, string $recipientName = ''): bool
    {
        $emailSubject = "Ticket Admin #$ticketId - $subject";

        $ticketMessage = "
            <h2>Nouveau ticket d'administration</h2>
            <p>Bonjour" . (!empty($recipientName) ? " $recipientName" : "") . ",</p>

            <div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                <strong>Ticket #$ticketId</strong><br>
                <strong>Sujet :</strong> $subject
            </div>

            <p><strong>Message :</strong></p>
            <div style='background-color: #ffffff; border: 1px solid #dee2e6; padding: 15px; border-radius: 5px; margin: 10px 0;'>
                " . nl2br(htmlspecialchars($message)) . "
            </div>

            <p>Veuillez traiter ce ticket dans les plus brefs délais.</p>

            <div style='text-align: center; margin: 30px 0;'>
                <a href='http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost:8000') . "/admin/tickets/$ticketId' style='background-color: #dc3545; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;'>Voir le ticket</a>
            </div>

            <p>Cordialement,<br>Le système MonBudget</p>
        ";

        return $this->sendEmail($to, $emailSubject, $ticketMessage, $recipientName, true);
    }

    /**
     * Test de configuration email
     *
     * @param string $testEmail Adresse email de test
     * @return bool Résultat du test
     */
    public function testEmailConfiguration(string $testEmail): bool
    {
        $subject = "Test de configuration email - MonBudget";
        $message = "Ceci est un email de test envoyé par MonBudget pour vérifier la configuration email.";

        return $this->sendEmail($testEmail, $subject, $message, "Test User", true);
    }
}
