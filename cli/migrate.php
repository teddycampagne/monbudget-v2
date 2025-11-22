<?php
/**
 * Script de migration simple
 * Exécute les fichiers SQL de migration
 */

require_once __DIR__ . '/../vendor/autoload.php';

use MonBudget\Core\Database;

// Configuration
$config = require __DIR__ . '/../config/database.php';
Database::configure($config);

$db = Database::getConnection();

$migrations = [
    '010_create_notifications_settings.sql',
    '011_create_budget_alerts.sql'
];

echo "=== Migration MonBudget v2.4.0 - Phase 3 ===\n\n";

foreach ($migrations as $migration) {
    $file = __DIR__ . '/../database/migrations/' . $migration;

    if (!file_exists($file)) {
        echo "❌ Migration $migration introuvable\n";
        continue;
    }

    echo "Exécution de $migration...\n";

    try {
        $sql = file_get_contents($file);

        // Exécuter le SQL
        $db->exec($sql);

        echo "✅ Migration $migration exécutée avec succès\n";

    } catch (Exception $e) {
        echo "❌ Erreur lors de l'exécution de $migration: " . $e->getMessage() . "\n";
    }
}

echo "\n=== Migration terminée ===\n";