<?php

/**
 * Configuration Email / SMTP
 * 
 * Charger depuis .env ou utiliser les valeurs par défaut
 */

return [
    // Driver (smtp, sendmail, mail, log)
    'driver' => 'log', // Mode développement - écrit dans logs/mail.log
    
    // Configuration SMTP
    'smtp' => [
        'host' => 'smtp.ethereal.email',
        'port' => 587,
        'username' => 'your-ethereal-username',
        'password' => 'your-ethereal-password',
        'encryption' => 'tls',
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
