#!/usr/bin/env php
<?php
/**
 * Script CLI - Exécution manuelle des récurrences
 * 
 * Usage:
 *   php cli/execute_recurrences.php
 *   
 * Peut être utilisé :
 * - Pour tester le système
 * - Pour un cron job quotidien (alternative au hook login)
 * - Pour forcer une exécution en cas de problème
 * 
 * @package MonBudget\CLI
 * @version 2.2.0
 */

// Chargement de l'autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Imports
use MonBudget\Core\Environment;
use MonBudget\Core\Database;
use MonBudget\Services\RecurrenceService;

// Définir BASE_PATH si pas encore défini
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

// Charger les helpers
require_once BASE_PATH . '/app/Core/helpers.php';

// Charger les variables d'environnement
Environment::loadEnv();

// Charger la configuration BDD
$configFile = BASE_PATH . '/config/database.php';
if (file_exists($configFile)) {
    $dbConfig = require $configFile;
    Database::configure($dbConfig);
} else {
    echo "❌ Fichier de configuration BDD introuvable: {$configFile}\n";
    exit(1);
}

// Bannière
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  MonBudget - Exécution automatique des récurrences        ║\n";
echo "║  Version 2.2.0 - " . date('Y-m-d H:i:s') . "                    ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

try {
    $service = new RecurrenceService();
    
    echo "🔍 Recherche des récurrences échues...\n\n";
    
    $stats = $service->executeAllPendingRecurrences();
    
    // Affichage des résultats
    echo "📊 RÉSULTATS:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo sprintf("   Récurrences vérifiées : %d\n", $stats['total_checked']);
    echo sprintf("   ✓ Exécutées           : %d\n", $stats['total_executed']);
    echo sprintf("   ⊘ Ignorées (doublons) : %d\n", $stats['total_skipped']);
    echo sprintf("   ✗ Erreurs             : %d\n", count($stats['errors']));
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    // Détails des exécutions
    if ($stats['total_executed'] > 0) {
        echo "📋 DÉTAILS DES EXÉCUTIONS:\n";
        foreach ($stats['details'] as $detail) {
            if ($detail['status'] === 'executed') {
                echo sprintf(
                    "   ✓ [User:%d] Récurrence #%d exécutée le %s\n     → %s\n",
                    $detail['user_id'],
                    $detail['recurrence_id'],
                    $detail['date'],
                    $detail['libelle']
                );
            }
        }
        echo "\n";
    }
    
    // Détails des doublons
    if ($stats['total_skipped'] > 0) {
        echo "⚠️  DOUBLONS DÉTECTÉS (déjà exécutés aujourd'hui):\n";
        foreach ($stats['details'] as $detail) {
            if ($detail['status'] === 'skipped') {
                echo sprintf(
                    "   ⊘ Récurrence #%d : %s\n",
                    $detail['recurrence_id'],
                    $detail['libelle']
                );
            }
        }
        echo "\n";
    }
    
    // Erreurs
    if (!empty($stats['errors'])) {
        echo "❌ ERREURS:\n";
        foreach ($stats['errors'] as $error) {
            echo "   " . json_encode($error, JSON_PRETTY_PRINT) . "\n";
        }
        echo "\n";
    }
    
    // Message final
    if ($stats['total_executed'] > 0) {
        echo "✅ Exécution terminée avec succès !\n";
        exit(0);
    } elseif ($stats['total_checked'] === 0) {
        echo "ℹ️  Aucune récurrence à exécuter pour le moment.\n";
        exit(0);
    } else {
        echo "ℹ️  Toutes les récurrences ont déjà été exécutées aujourd'hui.\n";
        exit(0);
    }
    
} catch (Exception $e) {
    echo "❌ ERREUR CRITIQUE:\n";
    echo "   " . $e->getMessage() . "\n";
    echo "\n   Trace:\n";
    echo "   " . $e->getTraceAsString() . "\n";
    exit(1);
}
