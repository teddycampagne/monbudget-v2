<?php
/**
 * Script de diagnostic pour le système de gestion des versions
 * À exécuter sur le serveur de production pour déboguer les problèmes de détection de mises à jour
 */

// Charger l'autoloader de Composer
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

use MonBudget\Services\VersionChecker;

echo "=== DIAGNOSTIC SYSTÈME DE VERSIONS ===\n\n";

// 1. Vérifier la version locale
$config = require __DIR__ . '/config/app.php';
$localVersion = $config['app']['version'] ?? '0.0.0';
echo "1. Version locale configurée: {$localVersion}\n";

// 2. Tester l'accès à GitHub API directement
echo "\n2. Test d'accès à l'API GitHub:\n";

$ch = curl_init('https://api.github.com/repos/teddycampagne/monbudget-v2/releases/latest');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_USERAGENT => 'MonBudget-VersionChecker',
    CURLOPT_TIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "   HTTP Code: {$httpCode}\n";
if ($curlError) {
    echo "   Erreur cURL: {$curlError}\n";
} else {
    echo "   Connexion réussie\n";
}

if ($httpCode === 200 && $response) {
    $data = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $latestTag = $data['tag_name'] ?? 'N/A';
        $latestVersion = ltrim($latestTag, 'v');
        echo "   Dernière release: {$latestTag}\n";
        echo "   Version extraite: {$latestVersion}\n";

        // Comparaison
        $comparison = version_compare($latestVersion, $localVersion, '>');
        echo "   Comparaison ({$latestVersion} > {$localVersion}): " . ($comparison ? 'TRUE' : 'FALSE') . "\n";
    } else {
        echo "   Erreur JSON: " . json_last_error_msg() . "\n";
    }
} else {
    echo "   Échec de récupération des données GitHub\n";
}

// 3. Tester le VersionChecker
echo "\n3. Test du VersionChecker:\n";
try {
    $checker = new VersionChecker();
    $update = $checker->checkForUpdates();

    if ($update) {
        echo "   ✅ Mise à jour détectée:\n";
        echo "      Version: {$update['version']}\n";
        echo "      Version actuelle: {$update['current_version']}\n";
        echo "      Tag: {$update['tag_name']}\n";
    } else {
        echo "   ❌ Aucune mise à jour détectée\n";
    }
} catch (Exception $e) {
    echo "   Erreur VersionChecker: " . $e->getMessage() . "\n";
}

// 4. Vérifier le cache
echo "\n4. État du cache:\n";
$cacheFile = __DIR__ . '/storage/cache/version_check.json';
if (file_exists($cacheFile)) {
    $cacheTime = filemtime($cacheFile);
    $age = time() - $cacheTime;
    echo "   Cache existe (âge: " . round($age / 60) . " minutes)\n";

    $cacheContent = file_get_contents($cacheFile);
    $cacheData = json_decode($cacheContent, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        if (isset($cacheData['update']) && $cacheData['update']) {
            echo "   Cache contient une mise à jour\n";
        } else {
            echo "   Cache indique aucune mise à jour\n";
        }
    } else {
        echo "   Cache corrompu\n";
    }
} else {
    echo "   Aucun cache trouvé\n";
}

echo "\n=== FIN DIAGNOSTIC ===\n";
echo "\nPour forcer une nouvelle vérification, supprimez le fichier cache:\n";
echo "rm " . $cacheFile . "\n";
?>