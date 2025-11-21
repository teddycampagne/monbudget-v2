<?php
/**
 * Exemple d'utilisation du MailService
 * 
 * Ce fichier montre comment utiliser le service d'emails
 * dans différents contextes de l'application.
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';

use App\Services\MailService;

// Connexion DB (requis pour les logs et templates)
try {
    $db = new PDO(
        "mysql:host={$db_config['host']};dbname={$db_config['dbname']};charset=utf8mb4",
        $db_config['username'],
        $db_config['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("Erreur DB: " . $e->getMessage());
}

// Initialiser le service
$mailService = new MailService($db);

echo "<h1>Exemples MailService - MonBudget v2.4.0</h1>";
echo "<hr>";

// =============================================================================
// 1. TESTER LA CONNEXION SMTP
// =============================================================================
echo "<h2>1. Test de connexion SMTP</h2>";
$test = $mailService->testConnection();
if ($test['success']) {
    echo "✅ " . htmlspecialchars($test['message']) . "<br>";
} else {
    echo "❌ " . htmlspecialchars($test['message']) . "<br>";
}
echo "<hr>";

// =============================================================================
// 2. ENVOYER UN EMAIL SIMPLE
// =============================================================================
echo "<h2>2. Email simple (texte)</h2>";
$result = $mailService->send(
    'user@example.com',
    'Test MonBudget',
    'Ceci est un email de test envoyé depuis MonBudget v2.4.0.',
    ['html' => false]
);
echo $result ? "✅ Email envoyé<br>" : "❌ Erreur d'envoi<br>";
echo "<hr>";

// =============================================================================
// 3. ENVOYER UN EMAIL HTML
// =============================================================================
echo "<h2>3. Email HTML</h2>";
$htmlBody = '
<html>
<body style="font-family: Arial, sans-serif;">
    <h1 style="color: #4CAF50;">MonBudget</h1>
    <p>Ceci est un email <strong>HTML</strong> formaté.</p>
    <ul>
        <li>Support du HTML complet</li>
        <li>Styles CSS inline</li>
        <li>Images et liens</li>
    </ul>
</body>
</html>
';
$result = $mailService->send(
    'user@example.com',
    'Test HTML',
    $htmlBody,
    [
        'html' => true,
        'text' => 'Version texte alternative pour clients sans HTML'
    ]
);
echo $result ? "✅ Email HTML envoyé<br>" : "❌ Erreur d'envoi<br>";
echo "<hr>";

// =============================================================================
// 4. EMAIL AVEC CC, BCC, PRIORITÉ
// =============================================================================
echo "<h2>4. Email avec options avancées</h2>";
$result = $mailService->send(
    'primary@example.com',
    'Email avec options',
    '<h1>Email complet</h1><p>Avec CC, BCC et priorité haute.</p>',
    [
        'html' => true,
        'cc' => 'copy@example.com',
        'bcc' => ['hidden1@example.com', 'hidden2@example.com'],
        'priority' => 1 // 1=Haute, 3=Normale, 5=Basse
    ]
);
echo $result ? "✅ Email avec options envoyé<br>" : "❌ Erreur d'envoi<br>";
echo "<hr>";

// =============================================================================
// 5. UTILISER UN TEMPLATE - BIENVENUE
// =============================================================================
echo "<h2>5. Template Bienvenue</h2>";
$result = $mailService->sendTemplate(
    'newuser@example.com',
    'welcome',
    [
        'username' => 'Jean Dupont',
        'app_url' => 'https://monbudget.local',
        'app_name' => 'MonBudget v2.4.0',
        'year' => date('Y')
    ]
);
echo $result ? "✅ Email bienvenue envoyé<br>" : "❌ Erreur d'envoi<br>";
echo "<hr>";

// =============================================================================
// 6. TEMPLATE - RÉINITIALISATION MOT DE PASSE
// =============================================================================
echo "<h2>6. Template Réinitialisation</h2>";
$resetToken = bin2hex(random_bytes(32));
$result = $mailService->sendTemplate(
    'user@example.com',
    'password_reset',
    [
        'username' => 'Jean Dupont',
        'reset_url' => 'https://monbudget.local/reset-password?token=' . $resetToken,
        'year' => date('Y')
    ]
);
echo $result ? "✅ Email réinitialisation envoyé<br>" : "❌ Erreur d'envoi<br>";
echo "<hr>";

// =============================================================================
// 7. TEMPLATE - ALERTE BUDGET 80%
// =============================================================================
echo "<h2>7. Template Alerte Budget 80%</h2>";
$result = $mailService->sendTemplate(
    'user@example.com',
    'budget_alert_80',
    [
        'username' => 'Jean Dupont',
        'budget_name' => 'Alimentation',
        'percentage' => '82',
        'spent' => '820.50',
        'total' => '1000.00',
        'remaining' => '179.50',
        'year' => date('Y')
    ]
);
echo $result ? "✅ Alerte budget 80% envoyée<br>" : "❌ Erreur d'envoi<br>";
echo "<hr>";

// =============================================================================
// 8. TEMPLATE - ALERTE BUDGET 90%
// =============================================================================
echo "<h2>8. Template Alerte Budget 90%</h2>";
$result = $mailService->sendTemplate(
    'user@example.com',
    'budget_alert_90',
    [
        'username' => 'Jean Dupont',
        'budget_name' => 'Loisirs',
        'percentage' => '93',
        'spent' => '465.00',
        'total' => '500.00',
        'remaining' => '35.00',
        'year' => date('Y')
    ]
);
echo $result ? "✅ Alerte budget 90% envoyée<br>" : "❌ Erreur d'envoi<br>";
echo "<hr>";

// =============================================================================
// 9. TEMPLATE - BUDGET DÉPASSÉ
// =============================================================================
echo "<h2>9. Template Budget Dépassé</h2>";
$result = $mailService->sendTemplate(
    'user@example.com',
    'budget_exceeded',
    [
        'username' => 'Jean Dupont',
        'budget_name' => 'Restaurants',
        'spent' => '650.00',
        'total' => '500.00',
        'exceeded' => '150.00',
        'year' => date('Y')
    ]
);
echo $result ? "✅ Budget dépassé envoyé<br>" : "❌ Erreur d'envoi<br>";
echo "<hr>";

// =============================================================================
// 10. TEMPLATE - RÉCAPITULATIF MENSUEL
// =============================================================================
echo "<h2>10. Template Récapitulatif Mensuel</h2>";
$topCategories = '
<div style="padding: 8px 0; border-bottom: 1px solid #eee;">
    <span>🍔 Alimentation:</span> <strong>850.00 €</strong>
</div>
<div style="padding: 8px 0; border-bottom: 1px solid #eee;">
    <span>🏠 Logement:</span> <strong>750.00 €</strong>
</div>
<div style="padding: 8px 0;">
    <span>🚗 Transport:</span> <strong>320.00 €</strong>
</div>
';
$balance = 350.50;
$result = $mailService->sendTemplate(
    'user@example.com',
    'monthly_summary',
    [
        'username' => 'Jean Dupont',
        'month' => 'Novembre',
        'year' => '2024',
        'income' => '2500.00',
        'expenses' => '2149.50',
        'balance' => number_format($balance, 2, '.', ''),
        'balance_color' => $balance > 0 ? '#4CAF50' : '#F44336',
        'transaction_count' => '47',
        'top_categories' => $topCategories
    ]
);
echo $result ? "✅ Récapitulatif mensuel envoyé<br>" : "❌ Erreur d'envoi<br>";
echo "<hr>";

// =============================================================================
// 11. TEMPLATE - DEMANDE ADMIN
// =============================================================================
echo "<h2>11. Template Demande Admin</h2>";
$result = $mailService->sendTemplate(
    'admin@example.com',
    'admin_password_request',
    [
        'username' => 'Jean Dupont',
        'user_email' => 'jean.dupont@example.com',
        'request_date' => date('d/m/Y à H:i'),
        'reason' => 'Mot de passe oublié après 3 tentatives',
        'admin_url' => 'https://monbudget.local/admin/password-requests',
        'year' => date('Y')
    ]
);
echo $result ? "✅ Notification admin envoyée<br>" : "❌ Erreur d'envoi<br>";
echo "<hr>";

// =============================================================================
// 12. STATISTIQUES D'ENVOI
// =============================================================================
echo "<h2>12. Statistiques d'envoi (7 derniers jours)</h2>";
$stats = $mailService->getStats(7);
if (!empty($stats)) {
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>Date</th><th>Total</th><th>Envoyés</th><th>Échecs</th></tr>";
    foreach ($stats as $stat) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($stat['date']) . "</td>";
        echo "<td>" . htmlspecialchars($stat['total']) . "</td>";
        echo "<td style='color: green;'>" . htmlspecialchars($stat['sent']) . "</td>";
        echo "<td style='color: red;'>" . htmlspecialchars($stat['failed']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Aucune statistique disponible<br>";
}
echo "<hr>";

// =============================================================================
// 13. ENVOYER EMAIL DE TEST
// =============================================================================
echo "<h2>13. Email de test automatique</h2>";
$result = $mailService->sendTest('test@example.com');
echo $result ? "✅ Email de test envoyé<br>" : "❌ Erreur d'envoi<br>";
echo "<hr>";

echo "<p><strong>✅ Tests terminés</strong></p>";
echo "<p><small>Vérifier la table <code>emails_log</code> pour voir l'historique des envois.</small></p>";
