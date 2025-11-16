<?php

namespace MonBudget\Services;

use MonBudget\Core\Database;

/**
 * Service de vérification et déploiement de versions
 * 
 * Compare la version locale avec GitHub et propose mise à jour automatique
 * 
 * @version 2.2.0
 */
class VersionChecker
{
    private const GITHUB_API_URL = 'https://api.github.com/repos/teddycampagne/monbudget-v2/tags';
    private const GITHUB_RELEASES_URL = 'https://api.github.com/repos/teddycampagne/monbudget-v2/releases/latest';
    private const CACHE_DURATION = 3600; // 1 heure
    
    private string $localVersion;
    private string $cacheFile;
    
    public function __construct()
    {
        $this->localVersion = $this->getLocalVersion();
        $this->cacheFile = dirname(__DIR__, 2) . '/storage/cache/version_check.json';
    }
    
    /**
     * Récupérer la version locale
     */
    private function getLocalVersion(): string
    {
        $config = require dirname(__DIR__, 2) . '/config/app.php';
        return $config['app']['version'] ?? '0.0.0';
    }
    
    /**
     * Vérifier si une nouvelle version est disponible
     * 
     * @return array|null ['version' => 'x.y.z', 'changelog' => '...', 'published_at' => '...'] ou null
     */
    public function checkForUpdates(): ?array
    {
        // Vérifier le cache
        if ($this->isCacheValid()) {
            return $this->getCachedResult();
        }
        
        try {
            // Récupérer la dernière release depuis GitHub
            $latestRelease = $this->fetchLatestRelease();
            
            if (!$latestRelease) {
                return null;
            }
            
            $latestVersion = ltrim($latestRelease['tag_name'] ?? '', 'v');
            
            // Comparer les versions
            if (version_compare($latestVersion, $this->localVersion, '>')) {
                $result = [
                    'version' => $latestVersion,
                    'tag_name' => $latestRelease['tag_name'],
                    'changelog' => $latestRelease['body'] ?? 'Aucune note de version disponible',
                    'published_at' => $latestRelease['published_at'] ?? date('Y-m-d H:i:s'),
                    'html_url' => $latestRelease['html_url'] ?? '',
                    'current_version' => $this->localVersion
                ];
                
                // Mettre en cache
                $this->cacheResult($result);
                
                return $result;
            }
            
            // Pas de nouvelle version, mettre en cache résultat négatif
            $this->cacheResult(null);
            return null;
            
        } catch (\Exception $e) {
            error_log("VersionChecker error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Récupérer la dernière release depuis GitHub API
     */
    private function fetchLatestRelease(): ?array
    {
        $ch = curl_init(self::GITHUB_RELEASES_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => 'MonBudget-VersionChecker',
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200 || !$response) {
            return null;
        }
        
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }
        
        return $data;
    }
    
    /**
     * Vérifier si le cache est encore valide
     */
    private function isCacheValid(): bool
    {
        if (!file_exists($this->cacheFile)) {
            return false;
        }
        
        $mtime = filemtime($this->cacheFile);
        return (time() - $mtime) < self::CACHE_DURATION;
    }
    
    /**
     * Récupérer le résultat en cache
     */
    private function getCachedResult(): ?array
    {
        if (!file_exists($this->cacheFile)) {
            return null;
        }
        
        $content = file_get_contents($this->cacheFile);
        $data = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }
        
        return $data['update'] ?? null;
    }
    
    /**
     * Mettre en cache le résultat
     */
    private function cacheResult(?array $result): void
    {
        $cacheDir = dirname($this->cacheFile);
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        
        $data = [
            'checked_at' => time(),
            'update' => $result
        ];
        
        file_put_contents($this->cacheFile, json_encode($data, JSON_PRETTY_PRINT));
    }
    
    /**
     * Déployer une nouvelle version
     * 
     * @param string $version Version à déployer (ex: "2.2.0")
     * @return array ['success' => bool, 'message' => string, 'output' => array]
     */
    public function deployVersion(string $version): array
    {
        $output = [];
        $errors = [];
        
        try {
            // 1. Vérifier que Git est installé
            exec('git --version 2>&1', $gitVersion, $gitCode);
            if ($gitCode !== 0) {
                return [
                    'success' => false,
                    'message' => 'Git n\'est pas installé ou n\'est pas accessible',
                    'output' => $gitVersion
                ];
            }
            $output[] = "✓ Git détecté: " . implode(' ', $gitVersion);
            
            // 2. Vérifier qu'on est dans un repo Git
            $repoPath = dirname(__DIR__, 2);
            if (!is_dir($repoPath . '/.git')) {
                return [
                    'success' => false,
                    'message' => 'Le répertoire n\'est pas un dépôt Git',
                    'output' => $output
                ];
            }
            $output[] = "✓ Dépôt Git détecté";
            
            // 3. Sauvegarder la version actuelle
            exec('git rev-parse HEAD 2>&1', $currentCommit, $code);
            if ($code !== 0) {
                $errors[] = "Impossible de déterminer le commit actuel";
            } else {
                $output[] = "✓ Commit actuel: " . substr($currentCommit[0], 0, 7);
            }
            
            // 4. Fetch les dernières modifications
            $output[] = "\n📡 Récupération des mises à jour...";
            exec('git fetch origin --tags 2>&1', $fetchOutput, $fetchCode);
            if ($fetchCode !== 0) {
                return [
                    'success' => false,
                    'message' => 'Erreur lors du fetch Git',
                    'output' => array_merge($output, $fetchOutput)
                ];
            }
            $output[] = "✓ Fetch réussi";
            
            // 5. Vérifier que le tag existe
            $tagName = 'v' . $version;
            exec("git rev-parse $tagName 2>&1", $tagCheck, $tagCode);
            if ($tagCode !== 0) {
                return [
                    'success' => false,
                    'message' => "Le tag $tagName n'existe pas",
                    'output' => array_merge($output, $tagCheck)
                ];
            }
            $output[] = "✓ Tag $tagName trouvé";
            
            // 6. Vérifier les modifications locales non commitées
            exec('git status --porcelain 2>&1', $statusOutput, $statusCode);
            if (!empty($statusOutput)) {
                return [
                    'success' => false,
                    'message' => 'Des modifications locales non commitées existent. Veuillez les commiter ou les annuler avant de mettre à jour.',
                    'output' => array_merge($output, $statusOutput)
                ];
            }
            $output[] = "✓ Aucune modification locale";
            
            // 7. Checkout le tag
            $output[] = "\n🚀 Déploiement de la version $version...";
            exec("git checkout $tagName 2>&1", $checkoutOutput, $checkoutCode);
            if ($checkoutCode !== 0) {
                return [
                    'success' => false,
                    'message' => "Erreur lors du checkout du tag $tagName",
                    'output' => array_merge($output, $checkoutOutput)
                ];
            }
            $output[] = "✓ Checkout réussi vers $tagName";
            
            // 8. Exécuter les migrations (si nécessaire)
            $output[] = "\n🗄️ Vérification des migrations...";
            $migrationsDir = $repoPath . '/database/migrations';
            if (is_dir($migrationsDir)) {
                $migrations = glob($migrationsDir . '/*.sql');
                if (!empty($migrations)) {
                    $output[] = "  " . count($migrations) . " migrations trouvées (exécution manuelle recommandée)";
                } else {
                    $output[] = "✓ Aucune migration à exécuter";
                }
            }
            
            // 9. Vider le cache
            $output[] = "\n🧹 Nettoyage du cache...";
            $this->clearCache();
            $output[] = "✓ Cache vidé";
            
            // 10. Succès
            $output[] = "\n✅ Mise à jour vers v$version réussie !";
            $output[] = "⚠️  Pensez à exécuter les migrations SQL manuellement si nécessaire";
            
            return [
                'success' => true,
                'message' => "Mise à jour vers v$version réussie",
                'output' => $output,
                'rollback_commit' => $currentCommit[0] ?? null
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur inattendue: ' . $e->getMessage(),
                'output' => array_merge($output, [$e->getTraceAsString()])
            ];
        }
    }
    
    /**
     * Rollback vers un commit précédent
     */
    public function rollback(string $commit): array
    {
        $output = [];
        
        try {
            exec("git checkout $commit 2>&1", $output, $code);
            
            if ($code !== 0) {
                return [
                    'success' => false,
                    'message' => 'Erreur lors du rollback',
                    'output' => $output
                ];
            }
            
            $this->clearCache();
            
            return [
                'success' => true,
                'message' => 'Rollback réussi',
                'output' => $output
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
                'output' => $output
            ];
        }
    }
    
    /**
     * Vider le cache de vérification de version
     */
    public function clearCache(): void
    {
        if (file_exists($this->cacheFile)) {
            unlink($this->cacheFile);
        }
    }
    
    /**
     * Obtenir les informations de version actuelles
     */
    public function getVersionInfo(): array
    {
        exec('git describe --tags 2>&1', $gitVersion, $code);
        exec('git rev-parse --abbrev-ref HEAD 2>&1', $branch, $branchCode);
        exec('git rev-parse HEAD 2>&1', $commit, $commitCode);
        
        return [
            'local_version' => $this->localVersion,
            'git_version' => $code === 0 ? ($gitVersion[0] ?? 'N/A') : 'N/A',
            'branch' => $branchCode === 0 ? ($branch[0] ?? 'N/A') : 'N/A',
            'commit' => $commitCode === 0 ? substr($commit[0] ?? '', 0, 7) : 'N/A',
            'commit_full' => $commitCode === 0 ? ($commit[0] ?? 'N/A') : 'N/A'
        ];
    }
}
