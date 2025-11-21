<?php
/**
 * Template Email - Réponse à une demande d'aide
 *
 * Variables disponibles :
 * - $user : objet utilisateur avec name, email
 * - $request : détails de la demande originale (subject)
 * - $response : réponse de l'admin (message, admin_name)
 */

$subject = "Réponse à votre demande d'aide - {$request['subject']}";

$message = "
Bonjour {$user['name']},

Votre demande d'aide concernant \"{$request['subject']}\" a été traitée.

Réponse de l'administrateur {$response['admin_name']} :

{$response['message']}

Si vous avez d'autres questions, n'hésitez pas à nous contacter.

Cordialement,
L'équipe MonBudget
";

return [
    'subject' => $subject,
    'message' => $message
];