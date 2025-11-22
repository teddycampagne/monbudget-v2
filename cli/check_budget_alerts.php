<?php
/**
 * Script CLI - Vérification des alertes budgétaires
 * Phase 3: Budget Alerts - Cron job quotidien
 *
 * Usage: php cli/check_budget_alerts.php
 * Cron recommandé: 0 8 * * * php /path/to/monbudget/cli/check_budget_alerts.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use MonBudget\Services\BudgetAlertService;

// Configuration
$config = require __DIR__ . '/../config/database.php';
\MonBudget\Core\Database::configure($config);

echo "[" . date('Y-m-d H:i:s') . "] Démarrage vérification alertes budgétaires...\n";

try {
    $alertService = new BudgetAlertService();

    // Récupérer tous utilisateurs actifs avec alertes activées
    $users = $alertService->getUsersWithAlertsEnabled();

    echo "Utilisateurs avec alertes activées: " . count($users) . "\n";

    $totalAlerts = 0;

    foreach ($users as $user) {
        echo "Vérification budgets utilisateur {$user['username']} (ID: {$user['id']})...\n";

        $budgetsChecked = 0;
        $alertsTriggered = 0;

        // Récupérer les budgets actifs
        $budgets = $alertService->getUserActiveBudgetsPublic($user['id']);
        $budgetsChecked = count($budgets);

        foreach ($budgets as $budget) {
            $result = $alertService->checkBudgetStatus($budget['id']);

            if ($result['success'] && !empty($result['alerts'])) {
                $alertsTriggered += count($result['alerts']);
                echo "  - Budget '{$budget['name']}': {$result['percentage']}% - " . count($result['alerts']) . " alerte(s)\n";
            }
        }

        echo "  Résultat: $budgetsChecked budget(s) vérifié(s), $alertsTriggered alerte(s) déclenchée(s)\n";
        $totalAlerts += $alertsTriggered;
    }

    echo "\n[" . date('Y-m-d H:i:s') . "] Vérification terminée. Total alertes déclenchées: $totalAlerts\n";

} catch (Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] ERREUR: " . $e->getMessage() . "\n";
    exit(1);
}

echo "[" . date('Y-m-d H:i:s') . "] Script terminé avec succès.\n";