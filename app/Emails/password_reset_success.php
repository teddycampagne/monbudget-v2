<?php
/**
 * Template Email - Confirmation de réinitialisation de mot de passe
 *
 * Variables disponibles :
 * - $user : objet utilisateur avec name, email
 */

$subject = "Votre mot de passe a été réinitialisé";

$message = "
Bonjour {$user['name']},

Votre mot de passe MonBudget a été réinitialisé avec succès.

Si vous n'avez pas effectué cette action, contactez immédiatement l'administrateur.

Cordialement,
L'équipe MonBudget
";

return [
    'subject' => $subject,
    'message' => $message
];