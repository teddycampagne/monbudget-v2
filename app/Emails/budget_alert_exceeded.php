<?php
/**
 * Template Email - Alerte de dépassement budgétaire
 *
 * Variables disponibles :
 * - $user : objet utilisateur avec name, email
 * - $budget : détails du budget (name, amount, period)
 * - $usage : utilisation actuelle (percentage, spent, budget)
 * - $overspent : montant du dépassement
 */

$subject = "⚠️ Dépassement budgétaire - {$budget['name']}";

$message = "
Bonjour {$user['username']},

ATTENTION : Votre budget \"{$budget['name']}\" a été dépassé.

Détails du dépassement :
- Budget : {$budget['name']}
- Montant alloué : " . number_format($usage['budget'], 2, ',', ' ') . " €
- Montant dépensé : " . number_format($usage['spent'], 2, ',', ' ') . " €
- Pourcentage utilisé : {$usage['percentage']}%
- Dépassement : " . number_format($overspent, 2, ',', ' ') . " €

Période : {$budget['period']}
Date de début : " . date('d/m/Y', strtotime($budget['start_date'])) . "

Ce dépassement peut avoir un impact sur votre gestion financière globale.
Nous vous recommandons de réajuster vos dépenses ou de reconsidérer votre budget.

Cordialement,
L'équipe MonBudget
";

return [
    'subject' => $subject,
    'message' => $message
];