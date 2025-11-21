<?php
/**
 * Template Email - Nouvelle demande d'aide admin
 *
 * Variables disponibles :
 * - $user : objet utilisateur avec name, email
 * - $request : détails de la demande (subject, message, created_at)
 */

$subject = "Nouvelle demande d'aide - {$request['subject']}";

$message = "
Une nouvelle demande d'aide a été soumise par {$user['name']} ({$user['email']}).

Sujet : {$request['subject']}

Message :
{$request['message']}

Date : {$request['created_at']}

Vous pouvez traiter cette demande dans l'interface d'administration.

Cordialement,
Système MonBudget
";

return [
    'subject' => $subject,
    'message' => $message
];