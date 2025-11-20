#!/usr/bin/env php
<?php
/**
 * Script de nettoyage du fichier database.sql
 * Corrige les problèmes d'encodage UTF-8
 */

echo "🔧 Nettoyage du fichier database.sql...\n\n";

$sqlFile = __DIR__ . '/database.sql';

if (!file_exists($sqlFile)) {
    die("❌ Fichier database.sql introuvable !\n");
}

// Lire le contenu
$content = file_get_contents($sqlFile);
echo "✓ Fichier chargé (" . strlen($content) . " octets)\n";

// Détecter l'encodage actuel
$encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
echo "✓ Encodage détecté : " . ($encoding ?: 'inconnu') . "\n";

// Convertir en UTF-8 si nécessaire
if ($encoding && $encoding !== 'UTF-8') {
    echo "⚠️  Conversion en UTF-8...\n";
    $content = mb_convert_encoding($content, 'UTF-8', $encoding);
} elseif (!$encoding) {
    // Forcer conversion depuis Windows-1252 (encodage par défaut Windows)
    echo "⚠️  Encodage inconnu, tentative conversion Windows-1252 → UTF-8...\n";
    $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
}

// Corrections spécifiques des caractères mal encodés
$replacements = [
    'Ôö£┬«' => 'é',
    'Ôö£┬┐' => 'è',
    'Ôö£┬á' => 'à',
    '??' => 'é',
    'r??currence' => 'récurrence',
    'd??taill??e' => 'détaillée',
    'concern??' => 'concerné',
    'Libell??' => 'Libellé',
    'Cat??gorie' => 'Catégorie',
    'd??faut' => 'défaut',
    'Sous-cat??gorie' => 'Sous-catégorie',
    'op??ration' => 'opération',
    'B??n??ficiaire' => 'Bénéficiaire',
    '??metteur' => 'émetteur',
    'Fr??quence' => 'Fréquence',
    'r??p??tition' => 'répétition',
    'd??but' => 'début',
    'ex??cution' => 'exécution',
    'derni??re' => 'dernière',
];

$count = 0;
foreach ($replacements as $search => $replace) {
    $newContent = str_replace($search, $replace, $content);
    if ($newContent !== $content) {
        $occurrences = substr_count($content, $search);
        $count += $occurrences;
        echo "  → Remplacé '$search' par '$replace' ($occurrences fois)\n";
        $content = $newContent;
    }
}

echo "\n✓ Total : $count corrections effectuées\n";

// Sauvegarder une copie de backup
$backupFile = $sqlFile . '.backup';
copy($sqlFile, $backupFile);
echo "✓ Backup créé : database.sql.backup\n";

// Écrire le fichier corrigé
file_put_contents($sqlFile, $content);
echo "✓ Fichier database.sql mis à jour\n";

// Vérifier l'encodage final
if (mb_check_encoding($content, 'UTF-8')) {
    echo "\n✅ SUCCESS : Fichier database.sql encodé correctement en UTF-8\n";
} else {
    echo "\n⚠️  WARNING : L'encodage UTF-8 n'est peut-être pas parfait\n";
}

echo "\n📝 Instructions :\n";
echo "1. Vérifiez le fichier database.sql\n";
echo "2. Committez les changements : git add database.sql && git commit -m 'fix: Encodage UTF-8 database.sql'\n";
echo "3. Poussez sur le serveur : git push origin develop\n";
echo "4. Sur le serveur : git pull && relancez l'installation\n";
