<?php

namespace App\Services;

use Exception;

/**
 * Service d'envoi d'emails via SMTP
 * Utilise PHPMailer pour l'envoi avec support SMTP complet
 */
class MailService
{
    private $config;
    private $mailer;
    private $db;
    
    /**
     * Constructeur - Initialise la configuration
     */
    public function __construct($db = null)
    {
        $this->config = require(__DIR__ . '/../../config/mail.php');
        $this->db = $db;
        
        // Charger PHPMailer si disponible
        if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            $this->initializeMailer();
        }
    }
    
    /**
     * Initialise PHPMailer avec la configuration
     */
    private function initializeMailer()
    {
        $this->mailer = new \PHPMailer\PHPMailer\PHPMailer(true);
        
        try {
            // Configuration SMTP
            if ($this->config['driver'] === 'smtp') {
                $this->mailer->isSMTP();
                $this->mailer->Host = $this->config['smtp']['host'];
                $this->mailer->Port = $this->config['smtp']['port'];
                $this->mailer->SMTPAuth = $this->config['smtp']['auth'];
                $this->mailer->Username = $this->config['smtp']['username'];
                $this->mailer->Password = $this->config['smtp']['password'];
                
                // Encryption
                if ($this->config['smtp']['encryption'] === 'tls') {
                    $this->mailer->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                } elseif ($this->config['smtp']['encryption'] === 'ssl') {
                    $this->mailer->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                }
                
                $this->mailer->Timeout = $this->config['smtp']['timeout'];
            } else {
                $this->mailer->isMail();
            }
            
            // Configuration expéditeur
            $this->mailer->setFrom(
                $this->config['from']['address'],
                $this->config['from']['name']
            );
            
            // Charset
            $this->mailer->CharSet = $this->config['charset'];
            
        } catch (Exception $e) {
            error_log("MailService: Erreur initialisation PHPMailer - " . $e->getMessage());
        }
    }
    
    /**
     * Envoie un email simple
     * 
     * @param string $to Adresse destinataire
     * @param string $subject Sujet
     * @param string $body Corps du message (HTML ou texte selon $isHtml)
     * @param array $options Options supplémentaires (cc, bcc, attachments, etc.)
     * @return bool Succès ou échec
     */
    public function send($to, $subject, $body, $options = [])
    {
        if (!$this->mailer) {
            error_log("MailService: PHPMailer non disponible");
            return false;
        }
        
        try {
            // Réinitialiser pour nouvel envoi
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            $this->mailer->clearCCs();
            $this->mailer->clearBCCs();
            
            // Destinataire principal
            $this->mailer->addAddress($to);
            
            // Sujet
            $this->mailer->Subject = $subject;
            
            // Corps du message
            $isHtml = $options['html'] ?? $this->config['html'];
            if ($isHtml) {
                $this->mailer->isHTML(true);
                $this->mailer->Body = $body;
                
                // Version texte alternative
                if (isset($options['text'])) {
                    $this->mailer->AltBody = $options['text'];
                }
            } else {
                $this->mailer->isHTML(false);
                $this->mailer->Body = $body;
            }
            
            // CC
            if (isset($options['cc'])) {
                if (is_array($options['cc'])) {
                    foreach ($options['cc'] as $cc) {
                        $this->mailer->addCC($cc);
                    }
                } else {
                    $this->mailer->addCC($options['cc']);
                }
            }
            
            // BCC
            if (isset($options['bcc'])) {
                if (is_array($options['bcc'])) {
                    foreach ($options['bcc'] as $bcc) {
                        $this->mailer->addBCC($bcc);
                    }
                } else {
                    $this->mailer->addBCC($options['bcc']);
                }
            }
            
            // Pièces jointes
            if (isset($options['attachments']) && is_array($options['attachments'])) {
                foreach ($options['attachments'] as $attachment) {
                    if (is_array($attachment)) {
                        $this->mailer->addAttachment($attachment['path'], $attachment['name'] ?? '');
                    } else {
                        $this->mailer->addAttachment($attachment);
                    }
                }
            }
            
            // Priorité
            if (isset($options['priority'])) {
                $this->mailer->Priority = $options['priority'];
            }
            
            // Envoyer
            $result = $this->mailer->send();
            
            // Logger si DB disponible
            if ($result && $this->db) {
                $this->logEmail($to, $subject, 'sent');
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("MailService: Erreur envoi email à $to - " . $e->getMessage());
            
            // Logger l'échec
            if ($this->db) {
                $this->logEmail($to, $subject, 'failed', $e->getMessage());
            }
            
            return false;
        }
    }
    
    /**
     * Envoie un email à partir d'un template
     * 
     * @param string $to Destinataire
     * @param string $templateName Nom du template
     * @param array $data Données pour le template
     * @param array $options Options supplémentaires
     * @return bool
     */
    public function sendTemplate($to, $templateName, $data = [], $options = [])
    {
        try {
            // Charger le template depuis la base de données
            $template = $this->loadTemplate($templateName);
            
            if (!$template) {
                error_log("MailService: Template '$templateName' non trouvé");
                return false;
            }
            
            // Remplacer les variables dans le sujet et le corps
            $subject = $this->replaceVariables($template['subject'], $data);
            $body = $this->replaceVariables($template['body_html'], $data);
            
            // Version texte si disponible
            if (!empty($template['body_text'])) {
                $options['text'] = $this->replaceVariables($template['body_text'], $data);
            }
            
            // HTML par défaut pour les templates
            $options['html'] = true;
            
            // Envoyer
            return $this->send($to, $subject, $body, $options);
            
        } catch (Exception $e) {
            error_log("MailService: Erreur sendTemplate '$templateName' - " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Charge un template depuis la base de données
     */
    private function loadTemplate($name)
    {
        if (!$this->db) {
            return null;
        }
        
        try {
            $stmt = $this->db->prepare("
                SELECT subject, body_html, body_text 
                FROM email_templates 
                WHERE name = ? AND is_active = 1
            ");
            $stmt->execute([$name]);
            return $stmt->fetch(\PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("MailService: Erreur chargement template - " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Remplace les variables {{variable}} dans un template
     */
    private function replaceVariables($text, $data)
    {
        foreach ($data as $key => $value) {
            $text = str_replace('{{' . $key . '}}', $value, $text);
        }
        return $text;
    }
    
    /**
     * Enregistre un email dans la base de données
     */
    private function logEmail($to, $subject, $status, $error = null)
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO emails_log (recipient, subject, status, error_message, sent_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$to, $subject, $status, $error]);
            
        } catch (Exception $e) {
            error_log("MailService: Erreur log email - " . $e->getMessage());
        }
    }
    
    /**
     * Teste la connexion SMTP
     * 
     * @return array ['success' => bool, 'message' => string]
     */
    public function testConnection()
    {
        if (!$this->mailer) {
            return [
                'success' => false,
                'message' => 'PHPMailer non disponible'
            ];
        }
        
        try {
            // Test SMTP connect
            if ($this->config['driver'] === 'smtp') {
                $this->mailer->SMTPDebug = 0; // Désactiver debug
                
                // Connexion
                if (!$this->mailer->smtpConnect()) {
                    return [
                        'success' => false,
                        'message' => 'Impossible de se connecter au serveur SMTP'
                    ];
                }
                
                // Déconnexion
                $this->mailer->smtpClose();
                
                return [
                    'success' => true,
                    'message' => 'Connexion SMTP réussie'
                ];
            }
            
            return [
                'success' => true,
                'message' => 'Configuration mail() PHP'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Envoie un email de test
     * 
     * @param string $to Destinataire
     * @return bool
     */
    public function sendTest($to)
    {
        $subject = 'Test Email - MonBudget v2.4.0';
        $body = '<h1>Email de test</h1>
                 <p>Ceci est un email de test envoyé depuis MonBudget v2.4.0.</p>
                 <p>Si vous recevez cet email, la configuration email fonctionne correctement.</p>
                 <hr>
                 <p style="color: #666; font-size: 12px;">
                 Envoyé le ' . date('d/m/Y à H:i:s') . '<br>
                 Configuration: ' . $this->config['driver'] . ' - ' . $this->config['smtp']['host'] . ':' . $this->config['smtp']['port'] . '
                 </p>';
        
        return $this->send($to, $subject, $body, ['html' => true]);
    }
    
    /**
     * Récupère les statistiques d'envoi
     * 
     * @param int $days Nombre de jours (par défaut 7)
     * @return array
     */
    public function getStats($days = 7)
    {
        if (!$this->db) {
            return [];
        }
        
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    DATE(sent_at) as date,
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
                FROM emails_log
                WHERE sent_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY DATE(sent_at)
                ORDER BY date DESC
            ");
            $stmt->execute([$days]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("MailService: Erreur getStats - " . $e->getMessage());
            return [];
        }
    }
}
