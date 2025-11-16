<?php
/**
 * MonBudget v2.0 - Configuration
 * Architecture MVC Moderne
 */

return [
    // Application
    'app' => [
        'name' => 'MonBudget',
        'version' => '2.2.0',
        'env' => 'development', // development, production
        'debug' => true,
        'url' => 'http://localhost/monbudgetV2',
        'timezone' => 'Europe/Paris'
    ],

    // Base de données (même BDD que v1 pour migration)
    'database' => [
        'driver' => 'mysql',
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'monbudget',
        'username' => 'root',
        'password' => 'd667tu3.',
        'charset' => 'utf8mb4',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    ],

    // Sécurité
    'security' => [
        'session_name' => 'monbudget_v2_session',
        'csrf_token_name' => 'csrf_token',
        'jwt_secret' => 'your_jwt_secret_key_here_change_in_production',
        'password_hash_algo' => PASSWORD_ARGON2ID,
        'session_lifetime' => 7200, // 2 heures
    ],

    // Chemins
    'paths' => [
        'uploads' => __DIR__ . '/../uploads/',
        'exports' => __DIR__ . '/../storage/exports/',
        'logs' => __DIR__ . '/../storage/logs/',
        'cache' => __DIR__ . '/../storage/cache/',
        'views' => __DIR__ . '/../app/Views/'
    ],

    // Formats
    'formats' => [
        'date' => 'd/m/Y',
        'datetime' => 'd/m/Y H:i:s',
        'decimal_places' => 2,
        'currency' => '€'
    ],

    // Limites
    'limits' => [
        'max_upload_size' => 10 * 1024 * 1024, // 10MB
        'allowed_extensions' => ['ofx', 'csv', 'xlsx', 'pdf'],
        'pagination_per_page' => 25,
        'api_rate_limit' => 100 // requêtes par minute
    ],

    // API
    'api' => [
        'version' => 'v1',
        'prefix' => '/api/v1',
        'enable_cors' => true,
        'enable_swagger' => true
    ],

    // Migration depuis v1
    'migration' => [
        'legacy_path' => 'C:/wamp64/www/monbudget',
        'preserve_legacy' => true,
        'backup_before_migration' => true
    ]
];
?>