<?php

namespace MonBudget\Models;

use MonBudget\Core\Database;

/**
 * Class Projection
 * 
 * Modèle de calcul des projections budgétaires basées sur :
 * - Les récurrences actives (montants fixes récurrents)
 * - Les tendances historiques (moyennes glissantes 3/6/12 mois)
 * 
 * Génère des prévisions pour les 3, 6 ou 12 prochains mois avec intervalles de confiance.
 * 
 * @package MonBudget\Models
 */
class Projection
{
    /**
     * Calcule les projections budgétaires pour un utilisateur
     * 
     * @param int $userId ID de l'utilisateur
     * @param int $nbMoisFutur Nombre de mois à projeter (3, 6 ou 12)
     * @param int|null $compteId Optionnel : filtrer par compte
     * @param int|null $categorieId Optionnel : filtrer par catégorie
     * @return array Données de projection complètes
     */
    public static function calculerProjections(
        int $userId, 
        int $nbMoisFutur = 6,
        ?int $compteId = null,
        ?int $categorieId = null
    ): array {
        // 1. Récupérer les récurrences actives
        $recurrences = self::getRecurrencesActives($userId, $compteId, $categorieId);
        
        // 2. Calculer les tendances historiques
        $tendances = self::calculerTendancesHistoriques($userId, $compteId, $categorieId);
        
        // 3. Générer les projections mois par mois
        $projections = self::genererProjectionsMensuelles($nbMoisFutur, $recurrences, $tendances);
        
        return [
            'projections' => $projections,
            'recurrences' => $recurrences,
            'tendances' => $tendances,
            'resume' => self::calculerResume($projections)
        ];
    }
    
    /**
     * Récupère les récurrences actives avec montants mensuels équivalents
     * 
     * @param int $userId ID utilisateur
     * @param int|null $compteId Filtre compte
     * @param int|null $categorieId Filtre catégorie
     * @return array Récurrences avec montant_mensuel calculé
     */
    private static function getRecurrencesActives(
        int $userId, 
        ?int $compteId = null, 
        ?int $categorieId = null
    ): array {
        $sql = "SELECT r.*,
                c.nom as categorie_nom,
                c.couleur as categorie_couleur,
                co.nom as compte_nom
                FROM recurrences r
                LEFT JOIN categories c ON r.categorie_id = c.id
                LEFT JOIN comptes co ON r.compte_id = co.id
                WHERE r.user_id = ?
                AND r.recurrence_active = 1
                AND (r.date_fin IS NULL OR r.date_fin > CURDATE())";
        
        $params = [$userId];
        
        if ($compteId !== null) {
            $sql .= " AND r.compte_id = ?";
            $params[] = $compteId;
        }
        
        if ($categorieId !== null) {
            $sql .= " AND r.categorie_id = ?";
            $params[] = $categorieId;
        }
        
        $recurrences = Database::select($sql, $params);
        
        // Calculer le montant mensuel équivalent pour chaque récurrence
        foreach ($recurrences as &$rec) {
            $rec['montant_mensuel'] = self::calculerMontantMensuel($rec);
        }
        
        return $recurrences;
    }
    
    /**
     * Calcule le montant mensuel équivalent d'une récurrence
     * 
     * @param array $recurrence Données de la récurrence
     * @return float Montant mensuel équivalent
     */
    private static function calculerMontantMensuel(array $recurrence): float
    {
        $montant = (float) $recurrence['montant'];
        $frequence = $recurrence['frequence'];
        $intervalle = (int) $recurrence['intervalle'];
        
        switch ($frequence) {
            case 'quotidien':
                return $montant * 30.44 / $intervalle; // Moyenne jours/mois
                
            case 'hebdomadaire':
                return $montant * 4.33 / $intervalle; // Moyenne semaines/mois
                
            case 'mensuel':
                return $montant / $intervalle;
                
            case 'bimestriel':
                return $montant / (2 * $intervalle);
                
            case 'trimestriel':
                return $montant / (3 * $intervalle);
                
            case 'semestriel':
                return $montant / (6 * $intervalle);
                
            case 'annuel':
                return $montant / (12 * $intervalle);
                
            default:
                return $montant; // Par défaut, considérer comme mensuel
        }
    }
    
    /**
     * Calcule les tendances historiques (moyennes 3/6/12 mois)
     * 
     * @param int $userId ID utilisateur
     * @param int|null $compteId Filtre compte
     * @param int|null $categorieId Filtre catégorie
     * @return array Moyennes par type d'opération
     */
    private static function calculerTendancesHistoriques(
        int $userId,
        ?int $compteId = null,
        ?int $categorieId = null
    ): array {
        $tendances = [
            'credits' => [
                '3mois' => 0,
                '6mois' => 0,
                '12mois' => 0
            ],
            'debits' => [
                '3mois' => 0,
                '6mois' => 0,
                '12mois' => 0
            ],
            'solde' => [
                '3mois' => 0,
                '6mois' => 0,
                '12mois' => 0
            ]
        ];
        
        // Calculer pour chaque période
        foreach ([3, 6, 12] as $nbMois) {
            $stats = self::getStatsMoyennesPeriode($userId, $nbMois, $compteId, $categorieId);
            
            $tendances['credits']["{$nbMois}mois"] = $stats['moyenne_credits'];
            $tendances['debits']["{$nbMois}mois"] = $stats['moyenne_debits'];
            $tendances['solde']["{$nbMois}mois"] = $stats['moyenne_solde'];
        }
        
        return $tendances;
    }
    
    /**
     * Récupère les statistiques moyennes sur une période
     * 
     * @param int $userId ID utilisateur
     * @param int $nbMois Nombre de mois historiques
     * @param int|null $compteId Filtre compte
     * @param int|null $categorieId Filtre catégorie
     * @return array Moyennes calculées
     */
    private static function getStatsMoyennesPeriode(
        int $userId,
        int $nbMois,
        ?int $compteId = null,
        ?int $categorieId = null
    ): array {
        $dateDebut = date('Y-m-01', strtotime("-{$nbMois} months"));
        
        $sql = "SELECT 
                COUNT(DISTINCT DATE_FORMAT(t.date_transaction, '%Y-%m')) as nb_mois_reel,
                SUM(CASE WHEN t.type_operation = 'credit' THEN t.montant ELSE 0 END) as total_credits,
                SUM(CASE WHEN t.type_operation = 'debit' THEN t.montant ELSE 0 END) as total_debits,
                SUM(CASE WHEN t.type_operation = 'credit' THEN t.montant 
                         WHEN t.type_operation = 'debit' THEN -t.montant 
                         ELSE 0 END) as solde_total
                FROM transactions t
                INNER JOIN comptes c ON t.compte_id = c.id
                WHERE c.user_id = ?
                AND t.date_transaction >= ?
                AND t.validee = 1";
        
        $params = [$userId, $dateDebut];
        
        if ($compteId !== null) {
            $sql .= " AND t.compte_id = ?";
            $params[] = $compteId;
        }
        
        if ($categorieId !== null) {
            $sql .= " AND t.categorie_id = ?";
            $params[] = $categorieId;
        }
        
        $result = Database::select($sql, $params);
        $stats = $result[0] ?? [];
        
        $nbMoisReel = max(1, (int)($stats['nb_mois_reel'] ?? 1));
        
        return [
            'moyenne_credits' => ($stats['total_credits'] ?? 0) / $nbMoisReel,
            'moyenne_debits' => ($stats['total_debits'] ?? 0) / $nbMoisReel,
            'moyenne_solde' => ($stats['solde_total'] ?? 0) / $nbMoisReel
        ];
    }
    
    /**
     * Génère les projections mensuelles futures
     * 
     * @param int $nbMoisFutur Nombre de mois à projeter
     * @param array $recurrences Récurrences actives
     * @param array $tendances Tendances historiques
     * @return array Projections mois par mois
     */
    private static function genererProjectionsMensuelles(
        int $nbMoisFutur,
        array $recurrences,
        array $tendances
    ): array {
        $projections = [];
        
        // Calculer totaux récurrents mensuels
        $recurrents_credits = 0;
        $recurrents_debits = 0;
        
        foreach ($recurrences as $rec) {
            $montantMensuel = $rec['montant_mensuel'];
            if ($rec['type_operation'] === 'credit') {
                $recurrents_credits += $montantMensuel;
            } else {
                $recurrents_debits += $montantMensuel;
            }
        }
        
        // Utiliser moyenne 6 mois par défaut (compromis entre réactivité et stabilité)
        $tendance_credits_variables = max(0, $tendances['credits']['6mois'] - $recurrents_credits);
        $tendance_debits_variables = max(0, $tendances['debits']['6mois'] - $recurrents_debits);
        
        // Solde cumulé pour la projection
        $solde_cumule = 0;
        
        // Générer projection pour chaque mois futur
        for ($i = 1; $i <= $nbMoisFutur; $i++) {
            $date = date('Y-m-01', strtotime("+{$i} months"));
            
            // Formater le mois en français avec DateTime pour remplacer strftime() dépréciée
            $dateTime = new \DateTime($date);
            $formatter = new \IntlDateFormatter(
                'fr_FR',
                \IntlDateFormatter::NONE,
                \IntlDateFormatter::NONE,
                null,
                null,
                'MMMM yyyy'
            );
            $moisLabel = $formatter->format($dateTime);
            
            // Credits = récurrents + tendance variables
            $credits_prevus = $recurrents_credits + $tendance_credits_variables;
            
            // Debits = récurrents + tendance variables
            $debits_prevus = $recurrents_debits + $tendance_debits_variables;
            
            // Solde prévisionnel mensuel
            $solde_previsionnel = $credits_prevus - $debits_prevus;
            
            // Cumuler le solde pour voir l'évolution
            $solde_cumule += $solde_previsionnel;
            
            // Intervalle de confiance ±15% (basé sur volatilité moyenne)
            $confiance_min = $solde_cumule * 0.85;
            $confiance_max = $solde_cumule * 1.15;
            
            $projections[] = [
                'date' => $date,
                'mois' => $moisLabel,
                'credits_prevus' => round($credits_prevus, 2),
                'debits_prevus' => round($debits_prevus, 2),
                'solde_previsionnel' => round($solde_previsionnel, 2),
                'solde_cumule' => round($solde_cumule, 2),
                'confiance_min' => round($confiance_min, 2),
                'confiance_max' => round($confiance_max, 2),
                'recurrents_credits' => round($recurrents_credits, 2),
                'recurrents_debits' => round($recurrents_debits, 2)
            ];
        }
        
        return $projections;
    }
    
    /**
     * Calcule un résumé des projections
     * 
     * @param array $projections Projections mensuelles
     * @return array Résumé statistique
     */
    private static function calculerResume(array $projections): array
    {
        if (empty($projections)) {
            return [
                'total_credits' => 0,
                'total_debits' => 0,
                'solde_cumule' => 0,
                'moyenne_mensuelle' => 0
            ];
        }
        
        $totalCredits = array_sum(array_column($projections, 'credits_prevus'));
        $totalDebits = array_sum(array_column($projections, 'debits_prevus'));
        $soldeCumule = $totalCredits - $totalDebits;
        
        return [
            'total_credits' => round($totalCredits, 2),
            'total_debits' => round($totalDebits, 2),
            'solde_cumule' => round($soldeCumule, 2),
            'moyenne_mensuelle' => round($soldeCumule / count($projections), 2)
        ];
    }
    
    /**
     * Récupère l'historique des transactions pour comparaison
     * 
     * @param int $userId ID utilisateur
     * @param int $nbMois Nombre de mois historiques
     * @param int|null $compteId Filtre compte
     * @param int|null $categorieId Filtre catégorie
     * @return array Historique mois par mois
     */
    public static function getHistoriqueMensuel(
        int $userId,
        int $nbMois = 12,
        ?int $compteId = null,
        ?int $categorieId = null
    ): array {
        $dateDebut = date('Y-m-01', strtotime("-{$nbMois} months"));
        
        $sql = "SELECT 
                DATE_FORMAT(t.date_transaction, '%Y-%m-01') as date,
                DATE_FORMAT(t.date_transaction, '%Y-%m') as mois,
                SUM(CASE WHEN t.type_operation = 'credit' THEN t.montant ELSE 0 END) as credits,
                SUM(CASE WHEN t.type_operation = 'debit' THEN t.montant ELSE 0 END) as debits,
                SUM(CASE WHEN t.type_operation = 'credit' THEN t.montant 
                         WHEN t.type_operation = 'debit' THEN -t.montant 
                         ELSE 0 END) as solde
                FROM transactions t
                INNER JOIN comptes c ON t.compte_id = c.id
                WHERE c.user_id = ?
                AND t.date_transaction >= ?
                AND t.validee = 1";
        
        $params = [$userId, $dateDebut];
        
        if ($compteId !== null) {
            $sql .= " AND t.compte_id = ?";
            $params[] = $compteId;
        }
        
        if ($categorieId !== null) {
            $sql .= " AND t.categorie_id = ?";
            $params[] = $categorieId;
        }
        
        $sql .= " GROUP BY DATE_FORMAT(t.date_transaction, '%Y-%m')
                  ORDER BY date ASC";
        
        $result = Database::select($sql, $params);
        
        // Calculer le solde cumulé pour l'historique aussi
        $soldeCumule = 0;
        $historique = [];
        
        foreach ($result as $row) {
            $soldeCumule += (float)$row['solde'];
            $historique[] = [
                'date' => $row['date'],
                'mois' => $row['mois'],
                'credits' => (float)$row['credits'],
                'debits' => (float)$row['debits'],
                'solde' => (float)$row['solde'],
                'solde_cumule' => round($soldeCumule, 2)
            ];
        }
        
        return $historique;
        
        // Formater les résultats
        $historique = [];
        foreach ($resultats as $row) {
            $historique[] = [
                'date' => $row['date'],
                'mois' => $row['mois'],
                'credits' => (float) $row['credits'],
                'debits' => (float) $row['debits'],
                'solde' => (float) $row['solde']
            ];
        }
        
        return $historique;
    }
}
