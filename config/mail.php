<?php

/**
 * Configuration Email / SMTP
 * 
 * Charger depuis .env ou utiliser les valeurs par défaut
 */

return [
    // Driver (smtp, sendmail, mail)
    'driver' => getenv('MAIL_DRIVER') ?: 'smtp',
    
    // Configuration SMTP
    'smtp' => [
        'host' => getenv('MAIL_HOST') ?: 'localhost',
        'port' => getenv('MAIL_PORT') ?: 587,
        'username' => getenv('MAIL_USERNAME') ?: '',
        'password' => getenv('MAIL_PASSWORD') ?: '',
        'encryption' => getenv('MAIL_ENCRYPTION') ?: 'tls', // tls, ssl, null
        'auth' => true,
        'timeout' => 30,
    ],
    
    // Expéditeur par défaut
    'from' => [
        'address' => getenv('MAIL_FROM_ADDRESS') ?: 'noreply@monbudget.local',
        'name' => getenv('MAIL_FROM_NAME') ?: 'MonBudget',
    ],
    
    // Options
    'charset' => 'UTF-8',
    'html' => true, // Support HTML par défaut
    
    // Limites
    'max_recipients' => 50, // Maximum destinataires par email
    'daily_limit' => 500, // Limite quotidienne globale
];
