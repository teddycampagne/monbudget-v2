#!/usr/bin/env php
<?php
/**
 * Script de génération de la documentation PHPDoc
 */

echo "Génération de la documentation PHPDoc...\n";

$command = "php phpDocumentor.phar run -d app -t .phpdoc/output";
exec($command, $output, $returnCode);

if ($returnCode === 0) {
    echo "Documentation générée avec succès dans .phpdoc/output/\n";
    echo "Accès : http://localhost/monbudgetV2/.phpdoc/output/index.html\n";
} else {
    echo "Erreur lors de la génération de la documentation\n";
    echo implode("\n", $output) . "\n";
}
