<?php
/**
 * Template Email - Alerte de seuil budgétaire
 *
 * Variables disponibles :
 * - $user : objet utilisateur avec name, email
 * - $budget : détails du budget (name, amount, period)
 * - $usage : utilisation actuelle (percentage, spent, budget)
 * - $threshold : seuil atteint (80 ou 90)
 */

$subject = "Alerte budgétaire - {$budget['name']} à {$usage['percentage']}%";

$message = "
Bonjour {$user['username']},

Votre budget \"{$budget['name']}\" a atteint {$usage['percentage']}% de son montant alloué.

Détails :
- Budget : {$budget['name']}
- Montant alloué : " . number_format($usage['budget'], 2, ',', ' ') . " €
- Montant dépensé : " . number_format($usage['spent'], 2, ',', ' ') . " €
- Pourcentage utilisé : {$usage['percentage']}%
- Seuil d'alerte : {$threshold}%

Il vous reste " . number_format($usage['budget'] - $usage['spent'], 2, ',', ' ') . " € disponibles pour ce budget.

Période : {$budget['period']}
Date de début : " . date('d/m/Y', strtotime($budget['start_date'])) . "

Nous vous recommandons de surveiller vos dépenses pour éviter un dépassement.

Cordialement,
L'équipe MonBudget
";

return [
    'subject' => $subject,
    'message' => $message
];