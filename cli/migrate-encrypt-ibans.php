#!/usr/bin/env php
<?php
/**
 * Script de migration : Chiffrement des IBAN existants
 * 
 * Chiffre tous les IBAN en clair dans la base de données
 * avec EncryptionService (AES-256-GCM).
 * 
 * Usage:
 *   php cli/migrate-encrypt-ibans.php [--dry-run] [--force]
 * 
 * Options:
 *   --dry-run   Simulation sans modifications BDD
 *   --force     Forcer re-chiffrement même si déjà chiffré
 * 
 * @package MonBudget\CLI
 * @author MonBudget Security Team
 * @version 1.0.0
 */

// Charger l'autoloader
require_once __DIR__ . '/../vendor/autoload.php';

use MonBudget\Core\Database;
use MonBudget\Services\EncryptionService;

// Initialisation
$dryRun = in_array('--dry-run', $argv);
$force = in_array('--force', $argv);

echo "\n";
echo "========================================\n";
echo " Migration: Chiffrement IBAN (PCI DSS)\n";
echo "========================================\n";
echo "\n";

if ($dryRun) {
    echo "⚠️  MODE DRY-RUN: Aucune modification ne sera effectuée\n";
}

if ($force) {
    echo "⚠️  MODE FORCE: Re-chiffrement des IBAN déjà chiffrés\n";
}

echo "\n";

try {
    // Vérifier clé de chiffrement
    $encryption = new EncryptionService();
    echo "✓ Clé de chiffrement chargée\n";
    
    // Récupérer tous les comptes avec IBAN
    $comptes = Database::select(
        "SELECT id, nom, iban FROM comptes WHERE iban IS NOT NULL AND iban != ''"
    );
    
    $total = count($comptes);
    echo "✓ Trouvé {$total} compte(s) avec IBAN\n\n";
    
    if ($total === 0) {
        echo "✓ Aucun IBAN à chiffrer\n";
        exit(0);
    }
    
    // Statistiques
    $stats = [
        'total' => $total,
        'encrypted' => 0,
        'already_encrypted' => 0,
        'errors' => 0,
        'skipped' => 0
    ];
    
    // Traiter chaque compte
    foreach ($comptes as $index => $compte) {
        $num = $index + 1;
        $prefix = "[{$num}/{$total}]";
        
        echo "{$prefix} Compte #{$compte['id']} '{$compte['nom']}':\n";
        
        try {
            // Vérifier si déjà chiffré
            $isEncrypted = $encryption->isEncrypted($compte['iban']);
            
            if ($isEncrypted && !$force) {
                echo "  → IBAN déjà chiffré (skip)\n";
                $stats['already_encrypted']++;
                continue;
            }
            
            // Déchiffrer si force mode et déjà chiffré
            $ibanClair = $isEncrypted 
                ? $encryption->decryptIBAN($compte['iban'])
                : $compte['iban'];
            
            // Afficher IBAN masqué
            $ibanMasked = $encryption->maskIBAN($ibanClair, false);
            echo "  → IBAN: {$ibanMasked}\n";
            
            // Chiffrer
            $ibanEncrypted = $encryption->encryptIBAN($ibanClair);
            
            if (!$dryRun) {
                // Mise à jour BDD
                $updated = Database::update(
                    "UPDATE comptes SET iban = ? WHERE id = ?",
                    [$ibanEncrypted, $compte['id']]
                );
                
                if ($updated > 0) {
                    echo "  ✓ Chiffré et enregistré\n";
                    $stats['encrypted']++;
                } else {
                    echo "  ⚠️  Aucune ligne mise à jour\n";
                    $stats['skipped']++;
                }
            } else {
                echo "  ✓ Chiffré (dry-run, non enregistré)\n";
                $stats['encrypted']++;
            }
            
        } catch (\Exception $e) {
            echo "  ✗ ERREUR: " . $e->getMessage() . "\n";
            $stats['errors']++;
        }
        
        echo "\n";
    }
    
    // Rapport final
    echo "========================================\n";
    echo " Rapport de migration\n";
    echo "========================================\n";
    echo "Total comptes: {$stats['total']}\n";
    echo "Chiffrés: {$stats['encrypted']}\n";
    echo "Déjà chiffrés: {$stats['already_encrypted']}\n";
    echo "Erreurs: {$stats['errors']}\n";
    echo "Ignorés: {$stats['skipped']}\n";
    echo "\n";
    
    if ($stats['errors'] > 0) {
        echo "⚠️  Migration terminée avec erreurs\n";
        exit(1);
    }
    
    if ($dryRun) {
        echo "✓ Simulation terminée (dry-run)\n";
        echo "→ Exécuter sans --dry-run pour appliquer les changements\n";
    } else {
        echo "✓ Migration terminée avec succès\n";
        
        // Vérification post-migration
        echo "\n";
        echo "Vérification post-migration...\n";
        
        $notEncrypted = Database::select(
            "SELECT id, nom FROM comptes 
             WHERE iban IS NOT NULL 
             AND iban != '' 
             AND iban NOT LIKE 'eyJ%'"  // base64 commence généralement par 'eyJ'
        );
        
        if (count($notEncrypted) > 0) {
            echo "⚠️  {count($notEncrypted)} IBAN(s) encore en clair détecté(s)\n";
            foreach ($notEncrypted as $compte) {
                echo "  - Compte #{$compte['id']} '{$compte['nom']}'\n";
            }
        } else {
            echo "✓ Tous les IBAN sont chiffrés\n";
        }
    }
    
} catch (\Exception $e) {
    echo "\n";
    echo "✗ ERREUR CRITIQUE: " . $e->getMessage() . "\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n";
exit(0);
