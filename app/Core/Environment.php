<?php

namespace MonBudget\Core;

/**
 * Environment - Détection et gestion de l'environnement
 * 
 * Détecte automatiquement l'environnement (WAMP, XAMPP, production)
 */
class Environment
{
    private static ?string $environment = null;
    private static array $config = [];
    
    /**
     * Détecter l'environnement actuel
     */
    public static function detect(): string
    {
        if (self::$environment !== null) {
            return self::$environment;
        }
        
        // Vérifier variable d'environnement
        $env = $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? null;
        
        if ($env) {
            self::$environment = $env;
            return $env;
        }
        
        // Détection automatique
        if (self::isWamp()) {
            self::$environment = 'wamp';
        } elseif (self::isXampp()) {
            self::$environment = 'xampp';
        } elseif (self::isProductionEnvironment()) {
            self::$environment = 'production';
        } else {
            self::$environment = 'development';
        }
        
        return self::$environment;
    }
    
    /**
     * Vérifier si on est sur WAMP
     */
    private static function isWamp(): bool
    {
        return stripos($_SERVER['DOCUMENT_ROOT'] ?? '', 'wamp') !== false
            || file_exists('C:/wamp64')
            || file_exists('C:/wamp');
    }
    
    /**
     * Vérifier si on est sur XAMPP
     */
    private static function isXampp(): bool
    {
        return stripos($_SERVER['DOCUMENT_ROOT'] ?? '', 'xampp') !== false
            || file_exists('C:/xampp')
            || file_exists('/opt/lampp');
    }
    
    /**
     * Vérifier si on est en production (détection)
     */
    private static function isProductionEnvironment(): bool
    {
        return !empty($_SERVER['SERVER_NAME'])
            && $_SERVER['SERVER_NAME'] !== 'localhost'
            && !self::isWamp()
            && !self::isXampp();
    }
    
    /**
     * Obtenir l'environnement actuel
     */
    public static function get(): string
    {
        return self::detect();
    }
    
    /**
     * Vérifier si on est en développement
     */
    public static function isDevelopment(): bool
    {
        return in_array(self::detect(), ['development', 'wamp', 'xampp']);
    }
    
    /**
     * Vérifier si on est en production
     */
    public static function isProduction(): bool
    {
        return self::detect() === 'production';
    }
    
    /**
     * Obtenir la configuration par défaut selon l'environnement
     */
    public static function getDefaultConfig(): array
    {
        $env = self::detect();
        
        $defaults = [
            'wamp' => [
                'db_host' => 'localhost',
                'db_port' => '3306',
                'db_username' => 'root',
                'db_password' => '',
                'app_url' => 'http://localhost',
                'debug' => true,
            ],
            'xampp' => [
                'db_host' => 'localhost',
                'db_port' => '3306',
                'db_username' => 'root',
                'db_password' => '',
                'app_url' => 'http://localhost',
                'debug' => true,
            ],
            'production' => [
                'db_host' => 'localhost',
                'db_port' => '3306',
                'db_username' => 'monbudget',
                'db_password' => '',
                'app_url' => '',
                'debug' => false,
            ],
            'development' => [
                'db_host' => 'localhost',
                'db_port' => '3306',
                'db_username' => 'root',
                'db_password' => '',
                'app_url' => 'http://localhost:8005',
                'debug' => true,
            ],
        ];
        
        return $defaults[$env] ?? $defaults['development'];
    }
    
    /**
     * Vérifier les prérequis système
     */
    public static function checkRequirements(): array
    {
        $requirements = [
            [
                'name' => 'Version PHP',
                'required' => '8.1.0 ou supérieur',
                'current' => PHP_VERSION,
                'status' => version_compare(PHP_VERSION, '8.1.0', '>=')
            ],
            [
                'name' => 'Extension PDO',
                'required' => 'Activée',
                'current' => extension_loaded('pdo') ? 'Activée' : 'Désactivée',
                'status' => extension_loaded('pdo')
            ],
            [
                'name' => 'Extension PDO MySQL',
                'required' => 'Activée',
                'current' => extension_loaded('pdo_mysql') ? 'Activée' : 'Désactivée',
                'status' => extension_loaded('pdo_mysql')
            ],
            [
                'name' => 'Extension JSON',
                'required' => 'Activée',
                'current' => extension_loaded('json') ? 'Activée' : 'Désactivée',
                'status' => extension_loaded('json')
            ],
            [
                'name' => 'Extension MBString',
                'required' => 'Activée',
                'current' => extension_loaded('mbstring') ? 'Activée' : 'Désactivée',
                'status' => extension_loaded('mbstring')
            ],
            [
                'name' => 'Extension OpenSSL',
                'required' => 'Activée',
                'current' => extension_loaded('openssl') ? 'Activée' : 'Désactivée',
                'status' => extension_loaded('openssl')
            ]
        ];
        
        return $requirements;
    }
    
    /**
     * Vérifier si tous les prérequis sont satisfaits
     */
    public static function checkAllRequirements(): bool
    {
        $requirements = self::checkRequirements();
        
        foreach ($requirements as $requirement) {
            if (!$requirement['status']) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Vérifier les permissions des dossiers
     */
    public static function checkPermissions(): array
    {
        $basePath = dirname(__DIR__, 2);
        
        $directories = [
            'storage' => $basePath . '/storage',
            'storage/logs' => $basePath . '/storage/logs',
            'storage/cache' => $basePath . '/storage/cache',
            'storage/sessions' => $basePath . '/storage/sessions',
            'uploads' => $basePath . '/uploads',
        ];
        
        $permissions = [];
        
        foreach ($directories as $name => $path) {
            $permissions[] = [
                'path' => $name,
                'fullPath' => $path,
                'exists' => file_exists($path),
                'writable' => is_writable($path),
                'status' => file_exists($path) && is_writable($path)
            ];
        }
        
        return $permissions;
    }
}
