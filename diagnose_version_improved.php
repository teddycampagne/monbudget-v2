<?php
/**
 * Script de diagnostic amélioré avec gestion d'erreurs complètes
 */

// Activer l'affichage des erreurs
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "=== DIAGNOSTIC AMÉLIORÉ ===\n\n";

// 1. Vérifier l'autoloading
echo "1. Test d'autoloading:\n";
$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    echo "   ✅ Fichier autoload trouvé\n";
    try {
        require_once $autoloadPath;
        echo "   ✅ Autoload chargé\n";
    } catch (Exception $e) {
        echo "   ❌ Erreur autoload: " . $e->getMessage() . "\n";
        exit(1);
    }
} else {
    echo "   ❌ Fichier autoload manquant: {$autoloadPath}\n";
    exit(1);
}

// 2. Vérifier la classe VersionChecker
echo "\n2. Test de la classe VersionChecker:\n";
$classExists = class_exists('MonBudget\Services\VersionChecker');
echo "   Classe VersionChecker existe: " . ($classExists ? '✅ OUI' : '❌ NON') . "\n";

if (!$classExists) {
    echo "   ❌ Impossible de continuer sans la classe VersionChecker\n";
    exit(1);
}

// 3. Tester l'instanciation
echo "\n3. Test d'instanciation VersionChecker:\n";
try {
    $checker = new MonBudget\Services\VersionChecker();
    echo "   ✅ VersionChecker instancié\n";

    // Tester getVersionInfo
    $versionInfo = $checker->getVersionInfo();
    echo "   ✅ getVersionInfo(): " . json_encode($versionInfo) . "\n";

} catch (Throwable $e) {
    echo "   ❌ Erreur lors de l'instanciation: " . $e->getMessage() . "\n";
    echo "   Type d'erreur: " . get_class($e) . "\n";
    echo "   Fichier: " . $e->getFile() . " ligne " . $e->getLine() . "\n";
    exit(1);
}

// 4. Tester checkForUpdates
echo "\n4. Test de checkForUpdates():\n";
try {
    $update = $checker->checkForUpdates();

    if ($update) {
        echo "   ✅ Mise à jour détectée:\n";
        echo "      Version: {$update['version']}\n";
        echo "      Version actuelle: {$update['current_version']}\n";
        echo "      Tag: {$update['tag_name']}\n";
    } else {
        echo "   ❌ Aucune mise à jour détectée\n";
    }
} catch (Throwable $e) {
    echo "   ❌ Erreur checkForUpdates: " . $e->getMessage() . "\n";
    echo "   Type d'erreur: " . get_class($e) . "\n";
    echo "   Fichier: " . $e->getFile() . " ligne " . $e->getLine() . "\n";
}

// 5. Vérifier le cache
echo "\n5. État du cache:\n";
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
        echo "   Cache corrompu: " . json_last_error_msg() . "\n";
        echo "   Contenu: " . substr($cacheContent, 0, 200) . "\n";
    }
} else {
    echo "   Aucun cache trouvé\n";
}

echo "\n=== FIN DIAGNOSTIC ===\n";
?>