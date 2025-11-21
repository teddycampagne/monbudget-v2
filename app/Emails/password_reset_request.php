<?php
/**
 * Template Email - Demande de réinitialisation de mot de passe
 *
 * Variables disponibles :
 * - $user : objet utilisateur avec name, email
 * - $resetUrl : URL complète pour réinitialiser le mot de passe
 * - $expiresAt : date d'expiration du lien
 */

$subject = "Réinitialisation de votre mot de passe MonBudget";

$message = "
Bonjour {$user['name']},

Vous avez demandé la réinitialisation de votre mot de passe pour votre compte MonBudget.

Pour réinitialiser votre mot de passe, cliquez sur le lien suivant :
{$resetUrl}

Ce lien est valable jusqu'au : {$expiresAt}

Si vous n'avez pas demandé cette réinitialisation, ignorez simplement cet email.

Cordialement,
L'équipe MonBudget
";

return [
    'subject' => $subject,
    'message' => $message
];