<?php

namespace MonBudget\Controllers;

use MonBudget\Models\Transaction;
use MonBudget\Models\Compte;
use MonBudget\Models\Categorie;
use MonBudget\Models\Budget;
use MonBudget\Core\Database;

/**
 * Contrôleur de génération de rapports et statistiques
 * 
 * Gère la génération de rapports financiers, graphiques et statistiques :
 * synthèses mensuelles/annuelles, évolution des soldes, répartition par catégories,
 * comparaisons de budgets, exports CSV/PDF.
 * 
 * @package MonBudget\Controllers
 * @author MonBudget
 * @version 1.0.0
 */
class RapportController extends BaseController
{
    /**
     * Page principale des rapports
     * 
     * Point d'entrée pour l'accès aux différents types de rapports disponibles.
     * 
     * @return void
     */
    public function index(): void
    {
        $comptes = Compte::getAllByUser($this->userId);
        
        $this->data['comptes'] = $comptes;
        $this->view('rapports/index');
    }
    
    /**
     * Afficher la page des graphiques d'analyse
     * 
     * Génère des graphiques d'analyse financière : évolution des soldes,
     * répartition des dépenses par catégorie, comparaison mensuelle, etc.
     * 
     * @return void
     */
    public function graphiques(): void
    {
        $compteId = isset($_GET['compte_id']) ? (int) $_GET['compte_id'] : null;
        $annee = (int) ($_GET['annee'] ?? date('Y'));
        $mois = isset($_GET['mois']) && $_GET['mois'] !== '' ? (int) $_GET['mois'] : null;
        
        $comptes = Compte::getAllByUser($this->userId);
        
        $this->data['comptes'] = $comptes;
        $this->data['compte_id'] = $compteId;
        $this->data['annee'] = $annee;
        $this->data['mois'] = $mois;
        
        $this->view('rapports/graphiques');
    }
    
    /**
     * API: Évolution du solde avec projection
     */
    public function apiEvolutionSolde(): void
    {
        $compteId = (int) ($_GET['compte_id'] ?? 0);
        $nbMois = (int) ($_GET['nb_mois'] ?? 12);
        
        if (!$compteId) {
            $this->json(['error' => 'Compte requis'], 400);
            return;
        }
        
        // Vérifier que le compte appartient à l'utilisateur
        $compte = Compte::find($compteId);
        if (!$compte || $compte['user_id'] != $this->userId) {
            $this->json(['error' => 'Accès refusé'], 403);
            return;
        }
        
        $data = $this->calculerEvolutionSolde($compteId, $nbMois);
        $this->json($data);
    }
    
    /**
     * API: Répartition par catégories
     */
    public function apiRepartitionCategories(): void
    {
        $compteId = isset($_GET['compte_id']) ? (int) $_GET['compte_id'] : null;
        $annee = (int) ($_GET['annee'] ?? date('Y'));
        $mois = isset($_GET['mois']) && $_GET['mois'] !== '' ? (int) $_GET['mois'] : null;
        $type = $_GET['type'] ?? 'debit'; // debit ou credit
        
        // Si un compte est spécifié, vérifier qu'il appartient à l'utilisateur
        if ($compteId) {
            $compte = Compte::find($compteId);
            if (!$compte || $compte['user_id'] != $this->userId) {
                $this->json(['error' => 'Accès refusé'], 403);
                return;
            }
        }
        
        $sql = "
            SELECT 
                c.id,
                c.nom,
                c.couleur,
                c.icone,
                SUM(t.montant) as total
            FROM transactions t
            LEFT JOIN categories c ON t.categorie_id = c.id
            WHERE t.user_id = ?
            AND t.type_operation = ?
            AND YEAR(t.date_transaction) = ?
        ";
        
        $params = [$this->userId, $type, $annee];
        
        if ($compteId) {
            $sql .= " AND t.compte_id = ?";
            $params[] = $compteId;
        }
        
        if ($mois !== null) {
            $sql .= " AND MONTH(t.date_transaction) = ?";
            $params[] = $mois;
        }
        
        $sql .= " GROUP BY c.id, c.nom, c.couleur, c.icone
                  ORDER BY total DESC";
        
        $categories = Database::select($sql, $params);
        
        // Traiter les résultats pour gérer les non catégorisées
        $result = [];
        foreach ($categories as $cat) {
            $result[] = [
                'id' => $cat['id'] ?? 0,
                'nom' => $cat['nom'] ?? 'Non catégorisé',
                'couleur' => $cat['couleur'] ?? '#6c757d',
                'icone' => $cat['icone'] ?? 'bi-question-circle',
                'total' => (float) $cat['total']
            ];
        }
        
        $this->json($result);
    }
    
    /**
     * API: Détail d'une catégorie (drill-down)
     */
    public function apiDetailCategorie(): void
    {
        $categorieId = (int) ($_GET['categorie_id'] ?? 0);
        $compteId = isset($_GET['compte_id']) ? (int) $_GET['compte_id'] : null;
        $annee = (int) ($_GET['annee'] ?? date('Y'));
        $mois = isset($_GET['mois']) && $_GET['mois'] !== '' ? (int) $_GET['mois'] : null;
        
        // Si un compte est spécifié, vérifier qu'il appartient à l'utilisateur
        if ($compteId) {
            $compte = Compte::find($compteId);
            if (!$compte || $compte['user_id'] != $this->userId) {
                $this->json(['error' => 'Accès refusé'], 403);
                return;
            }
        }
        
        // Gérer le cas des transactions non catégorisées
        if ($categorieId === 0) {
            $sql = "
                SELECT 
                    t.*,
                    NULL as categorie_nom,
                    NULL as sous_categorie_nom
                FROM transactions t
                WHERE t.user_id = ?
                AND t.categorie_id IS NULL
                AND YEAR(t.date_transaction) = ?
            ";
            
            $params = [$this->userId, $annee];
        } else {
            // Récupérer les transactions de cette catégorie
            $sql = "
                SELECT 
                    t.*,
                    c.nom as categorie_nom,
                    sc.nom as sous_categorie_nom
                FROM transactions t
                INNER JOIN categories c ON t.categorie_id = c.id
                LEFT JOIN categories sc ON t.sous_categorie_id = sc.id
                WHERE t.user_id = ?
                AND (t.categorie_id = ? OR t.sous_categorie_id IN (
                    SELECT id FROM categories WHERE parent_id = ?
                ))
                AND YEAR(t.date_transaction) = ?
            ";
            
            $params = [$this->userId, $categorieId, $categorieId, $annee];
        }
        
        if ($compteId) {
            $sql .= " AND t.compte_id = ?";
            $params[] = $compteId;
        }
        
        if ($mois !== null) {
            $sql .= " AND MONTH(t.date_transaction) = ?";
            $params[] = $mois;
        }
        
        $sql .= " ORDER BY t.date_transaction DESC";
        
        $transactions = Database::select($sql, $params);
        
        // Grouper par sous-catégorie
        $parSousCategorie = [];
        foreach ($transactions as $trans) {
            $key = $trans['sous_categorie_nom'] ?? 'Sans sous-catégorie';
            if (!isset($parSousCategorie[$key])) {
                $parSousCategorie[$key] = [
                    'nom' => $key,
                    'total' => 0,
                    'nb' => 0
                ];
            }
            $parSousCategorie[$key]['total'] += (float) $trans['montant'];
            $parSousCategorie[$key]['nb']++;
        }
        
        $this->json([
            'sous_categories' => array_values($parSousCategorie),
            'transactions' => $transactions
        ]);
    }
    
    /**
     * API: Balances mensuelles
     */
    public function apiBalances(): void
    {
        $compteId = isset($_GET['compte_id']) ? (int) $_GET['compte_id'] : null;
        $annee = (int) ($_GET['annee'] ?? date('Y'));
        
        // Si un compte est spécifié, vérifier qu'il appartient à l'utilisateur
        if ($compteId) {
            $compte = Compte::find($compteId);
            if (!$compte || $compte['user_id'] != $this->userId) {
                $this->json(['error' => 'Accès refusé'], 403);
                return;
            }
        }
        
        $sql = "
            SELECT 
                MONTH(date_transaction) as mois,
                type_operation,
                SUM(montant) as total
            FROM transactions
            WHERE user_id = ?
            AND YEAR(date_transaction) = ?
        ";
        
        $params = [$this->userId, $annee];
        
        if ($compteId) {
            $sql .= " AND compte_id = ?";
            $params[] = $compteId;
        }
        
        $sql .= " GROUP BY MONTH(date_transaction), type_operation
                  ORDER BY mois ASC";
        
        $data = Database::select($sql, $params);
        
        // Organiser les données par mois
        $balances = [];
        $totalAnnuelDebit = 0;
        $totalAnnuelCredit = 0;
        
        for ($m = 1; $m <= 12; $m++) {
            $balances[$m] = [
                'mois' => $m,
                'debit' => 0,
                'credit' => 0
            ];
        }
        
        foreach ($data as $row) {
            $mois = (int) $row['mois'];
            $type = $row['type_operation'];
            $balances[$mois][$type] = (float) $row['total'];
            
            // Cumuler pour le total annuel
            if ($type === 'debit') {
                $totalAnnuelDebit += (float) $row['total'];
            } else {
                $totalAnnuelCredit += (float) $row['total'];
            }
        }
        
        // Ajouter la balance annuelle
        $balances[13] = [
            'mois' => 13, // Indicateur pour "Année"
            'debit' => $totalAnnuelDebit,
            'credit' => $totalAnnuelCredit
        ];
        
        $this->json(array_values($balances));
    }
    
    /**
     * API: Suivi budgétaire (prévu vs réalisé)
     */
    public function apiBudgetaire(): void
    {
        $annee = (int) ($_GET['annee'] ?? date('Y'));
        $mois = isset($_GET['mois']) && $_GET['mois'] !== '' ? (int) $_GET['mois'] : null;
        $compteId = isset($_GET['compte_id']) ? (int) $_GET['compte_id'] : null;
        
        // Si un compte est spécifié, vérifier qu'il appartient à l'utilisateur
        if ($compteId) {
            $compte = Compte::find($compteId);
            if (!$compte || $compte['user_id'] != $this->userId) {
                $this->json(['error' => 'Accès refusé'], 403);
                return;
            }
        }
        
        // Récupérer les budgets
        $sql = "
            SELECT 
                c.id,
                c.nom,
                c.couleur,
                c.icone,
                SUM(b.montant) as budget_prevu
            FROM budgets b
            INNER JOIN categories c ON b.categorie_id = c.id
            WHERE b.user_id = ?
            AND b.annee = ?
        ";
        
        $params = [$this->userId, $annee];
        
        if ($mois !== null) {
            $sql .= " AND b.mois = ?";
            $params[] = $mois;
        }
        
        $sql .= " GROUP BY c.id, c.nom, c.couleur, c.icone";
        
        $budgets = Database::select($sql, $params);
        
        // Récupérer les réalisations
        $sqlRealise = "
            SELECT 
                categorie_id,
                SUM(montant) as realise
            FROM transactions
            WHERE user_id = ?
            AND type_operation = 'debit'
            AND YEAR(date_transaction) = ?
        ";
        
        $paramsRealise = [$this->userId, $annee];
        
        if ($compteId) {
            $sqlRealise .= " AND compte_id = ?";
            $paramsRealise[] = $compteId;
        }
        
        if ($mois !== null) {
            $sqlRealise .= " AND MONTH(date_transaction) = ?";
            $paramsRealise[] = $mois;
        }
        
        $sqlRealise .= " GROUP BY categorie_id";
        
        $realises = Database::select($sqlRealise, $paramsRealise);
        
        // Mapper les réalisations
        $realiseMap = [];
        foreach ($realises as $r) {
            $realiseMap[$r['categorie_id']] = (float) $r['realise'];
        }
        
        // Combiner budgets et réalisations
        $data = [];
        foreach ($budgets as $budget) {
            $data[] = [
                'categorie' => $budget['nom'],
                'couleur' => $budget['couleur'],
                'prevu' => (float) $budget['budget_prevu'],
                'realise' => $realiseMap[$budget['id']] ?? 0
            ];
        }
        
        $this->json($data);
    }
    
    /**
     * API: Tendance d'épargne (revenus - dépenses par mois)
     */
    public function apiTendanceEpargne(): void
    {
        $annee = (int) ($_GET['annee'] ?? date('Y'));
        $compteId = isset($_GET['compte_id']) ? (int) $_GET['compte_id'] : null;
        
        // Si un compte est spécifié, vérifier qu'il appartient à l'utilisateur
        if ($compteId) {
            $compte = Compte::find($compteId);
            if (!$compte || $compte['user_id'] != $this->userId) {
                $this->json(['error' => 'Accès refusé'], 403);
                return;
            }
        }
        
        $sql = "
            SELECT 
                MONTH(date_transaction) as mois,
                type_operation,
                SUM(montant) as total
            FROM transactions
            WHERE user_id = ?
            AND YEAR(date_transaction) = ?
        ";
        
        $params = [$this->userId, $annee];
        
        if ($compteId) {
            $sql .= " AND compte_id = ?";
            $params[] = $compteId;
        }
        
        $sql .= " GROUP BY MONTH(date_transaction), type_operation
                  ORDER BY mois ASC";
        
        $data = Database::select($sql, $params);
        
        // Organiser par mois
        $tendances = [];
        for ($m = 1; $m <= 12; $m++) {
            $tendances[$m] = [
                'mois' => $m,
                'revenus' => 0,
                'depenses' => 0,
                'epargne' => 0
            ];
        }
        
        foreach ($data as $row) {
            $mois = (int) $row['mois'];
            if ($row['type_operation'] === 'credit') {
                $tendances[$mois]['revenus'] = (float) $row['total'];
            } else {
                $tendances[$mois]['depenses'] = (float) $row['total'];
            }
        }
        
        // Calculer l'épargne (revenus - dépenses)
        foreach ($tendances as &$t) {
            $t['epargne'] = $t['revenus'] - $t['depenses'];
        }
        
        $this->json(array_values($tendances));
    }
    
    /**
     * Générer un relevé de compte PDF
     */
    public function releve(): void
    {
        $compteId = (int) ($_GET['compte_id'] ?? 0);
        $annee = (int) ($_GET['annee'] ?? date('Y'));
        $mois = (int) ($_GET['mois'] ?? date('n'));
        
        if (!$compteId) {
            flash('error', 'Compte requis');
            $this->redirect('rapports');
            return;
        }
        
        $compte = Compte::find($compteId);
        if (!$compte || $compte['user_id'] != $this->userId) {
            flash('error', 'Accès refusé');
            $this->redirect('rapports');
            return;
        }
        
        // Récupérer les transactions du mois
        $transactions = Transaction::getByCompteAndPeriod($compteId, $annee, $mois);
        
        // Calculer le solde à la fin du mois
        $dateFin = sprintf('%04d-%02d-%s', $annee, $mois, date('t', strtotime("$annee-$mois-01")));
        $soldeADate = $this->calculerSoldeADate($compteId, $dateFin);
        
        $this->data['compte'] = $compte;
        $this->data['transactions'] = $transactions;
        $this->data['annee'] = $annee;
        $this->data['mois'] = $mois;
        $this->data['solde_a_date'] = $soldeADate;
        
        $this->view('rapports/releve');
    }
    
    /**
     * Calculer le solde d'un compte à une date donnée
     */
    private function calculerSoldeADate(int $compteId, string $date): float
    {
        // Récupérer le solde initial du compte
        $compte = Compte::find($compteId);
        $soldeInitial = (float) ($compte['solde_initial'] ?? 0);
        
        // Récupérer toutes les transactions jusqu'à cette date
        $sql = "
            SELECT 
                type_operation,
                SUM(montant) as total
            FROM transactions
            WHERE compte_id = ?
            AND date_transaction <= ?
            GROUP BY type_operation
        ";
        
        $totaux = Database::select($sql, [$compteId, $date]);
        
        $solde = $soldeInitial;
        foreach ($totaux as $row) {
            if ($row['type_operation'] === 'credit') {
                $solde += (float) $row['total'];
            } else {
                $solde -= (float) $row['total'];
            }
        }
        
        return $solde;
    }
    
    /**
     * Calculer l'évolution du solde avec projection
     */
    private function calculerEvolutionSolde(int $compteId, int $nbMois): array
    {
        $dateDebut = date('Y-m-01', strtotime("-$nbMois months"));
        $dateFin = date('Y-m-t');
        
        // Récupérer le solde initial
        $compte = Compte::find($compteId);
        $soldeActuel = (float) ($compte['solde_actuel'] ?? 0);
        
        // Récupérer toutes les transactions
        $sql = "
            SELECT 
                DATE(date_transaction) as date,
                type_operation,
                SUM(montant) as total
            FROM transactions
            WHERE compte_id = ?
            AND date_transaction >= ?
            AND date_transaction <= ?
            GROUP BY DATE(date_transaction), type_operation
            ORDER BY date_transaction ASC
        ";
        
        $transactions = Database::select($sql, [$compteId, $dateDebut, $dateFin]);
        
        // Calculer le solde pour chaque jour
        $evolution = [];
        $soldeJour = $soldeActuel;
        
        // Remonter dans le temps pour calculer le solde initial
        foreach (array_reverse($transactions) as $trans) {
            if ($trans['type_operation'] === 'debit') {
                $soldeJour += (float) $trans['total'];
            } else {
                $soldeJour -= (float) $trans['total'];
            }
        }
        
        $soldeInitial = $soldeJour;
        
        // Maintenant calculer l'évolution jour par jour
        $currentDate = new \DateTime($dateDebut);
        $endDate = new \DateTime($dateFin);
        $transIndex = 0;
        
        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format('Y-m-d');
            
            // Ajouter les transactions de ce jour
            while ($transIndex < count($transactions) && $transactions[$transIndex]['date'] === $dateStr) {
                $trans = $transactions[$transIndex];
                if ($trans['type_operation'] === 'credit') {
                    $soldeInitial += (float) $trans['total'];
                } else {
                    $soldeInitial -= (float) $trans['total'];
                }
                $transIndex++;
            }
            
            $evolution[] = [
                'date' => $dateStr,
                'solde' => round($soldeInitial, 2)
            ];
            
            $currentDate->modify('+1 day');
        }
        
        // Ajouter la projection avec les récurrences
        $projection = $this->calculerProjectionSolde($compteId, $soldeInitial, 3);
        
        return [
            'historique' => $evolution,
            'projection' => $projection
        ];
    }
    
    /**
     * Calculer la projection du solde avec les récurrences
     */
    private function calculerProjectionSolde(int $compteId, float $soldeFinal, int $nbMoisProjection = 3): array
    {
        // Récupérer les transactions récurrentes actives de ce compte
        $sql = "
            SELECT *
            FROM transactions
            WHERE compte_id = ?
            AND est_recurrente = 1
            AND recurrence_active = 1
            AND (date_fin IS NULL OR date_fin >= CURDATE())
            ORDER BY prochaine_execution ASC
        ";
        
        $recurrences = Database::select($sql, [$compteId]);
        
        if (empty($recurrences)) {
            return [];
        }
        
        $projection = [];
        $soldeProj = $soldeFinal;
        $dateDebut = new \DateTime();
        $dateFin = (new \DateTime())->modify("+{$nbMoisProjection} months");
        
        // Générer les dates pour chaque jour
        $currentDate = clone $dateDebut;
        $executions = [];
        
        // Pour chaque récurrence, calculer ses prochaines exécutions
        foreach ($recurrences as $rec) {
            $tempDate = new \DateTime($rec['prochaine_execution'] ?? 'now');
            $nbExecutions = 0;
            $maxExecutions = $rec['nb_executions_max'] ? 
                ($rec['nb_executions_max'] - $rec['nb_executions']) : 999;
            
            while ($tempDate <= $dateFin && $nbExecutions < $maxExecutions) {
                if ($tempDate >= $dateDebut) {
                    $executions[] = [
                        'date' => $tempDate->format('Y-m-d'),
                        'montant' => (float) $rec['montant'],
                        'type' => $rec['type_operation'],
                        'libelle' => $rec['libelle']
                    ];
                }
                
                // Calculer prochaine exécution
                $intervalle = $rec['intervalle'] ?? 1;
                switch ($rec['frequence']) {
                    case 'quotidien':
                        $tempDate->modify("+{$intervalle} days");
                        break;
                    case 'hebdomadaire':
                        $tempDate->modify("+{$intervalle} weeks");
                        break;
                    case 'mensuel':
                        $tempDate->modify("+{$intervalle} months");
                        break;
                    case 'trimestriel':
                        $tempDate->modify("+" . ($intervalle * 3) . " months");
                        break;
                    case 'semestriel':
                        $tempDate->modify("+" . ($intervalle * 6) . " months");
                        break;
                    case 'annuel':
                        $tempDate->modify("+{$intervalle} years");
                        break;
                }
                
                $nbExecutions++;
            }
        }
        
        // Trier les exécutions par date
        usort($executions, function($a, $b) {
            return strcmp($a['date'], $b['date']);
        });
        
        // Calculer le solde jour par jour
        $currentDate = clone $dateDebut;
        $execIndex = 0;
        
        while ($currentDate <= $dateFin) {
            $dateStr = $currentDate->format('Y-m-d');
            
            // Appliquer les exécutions de ce jour
            while ($execIndex < count($executions) && $executions[$execIndex]['date'] === $dateStr) {
                $exec = $executions[$execIndex];
                if ($exec['type'] === 'credit') {
                    $soldeProj += $exec['montant'];
                } else {
                    $soldeProj -= $exec['montant'];
                }
                $execIndex++;
            }
            
            $projection[] = [
                'date' => $dateStr,
                'solde' => round($soldeProj, 2)
            ];
            
            $currentDate->modify('+1 day');
        }
        
        return $projection;
    }
    
    /**
     * API - Rapport par tags
     * 
     * @return void
     */
    public function apiRapportTags(): void
    {
        $this->requireAuth();
        header('Content-Type: application/json');
        
        $compteId = (int)($_GET['compte_id'] ?? 0);
        $annee = (int)($_GET['annee'] ?? date('Y'));
        $mois = isset($_GET['mois']) ? (int)$_GET['mois'] : null;
        
        if (!$compteId) {
            echo json_encode(['error' => 'Compte requis']);
            return;
        }
        
        $db = Database::getConnection();
        
        // Construire la requête SQL
        $sql = "SELECT 
                    tags.id,
                    tags.name,
                    tags.color,
                    COUNT(DISTINCT t.id) as nb_transactions,
                    COALESCE(SUM(CASE WHEN t.type_operation = 'debit' THEN t.montant ELSE 0 END), 0) as total_debits,
                    COALESCE(SUM(CASE WHEN t.type_operation = 'credit' THEN t.montant ELSE 0 END), 0) as total_credits
                FROM tags
                INNER JOIN transaction_tags tt ON tags.id = tt.tag_id
                INNER JOIN transactions t ON tt.transaction_id = t.id
                WHERE t.user_id = ? 
                AND t.compte_id = ?
                AND YEAR(t.date_transaction) = ?";
        
        $params = [$this->userId, $compteId, $annee];
        
        if ($mois) {
            $sql .= " AND MONTH(t.date_transaction) = ?";
            $params[] = $mois;
        }
        
        $sql .= " GROUP BY tags.id, tags.name, tags.color
                  ORDER BY total_debits DESC, total_credits DESC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $tags = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Calculer les totaux
        $totalDebits = 0;
        $totalCredits = 0;
        $totalTransactions = 0;
        
        foreach ($tags as &$tag) {
            $totalDebits += (float)$tag['total_debits'];
            $totalCredits += (float)$tag['total_credits'];
            $totalTransactions += (int)$tag['nb_transactions'];
            
            // Formater les montants
            $tag['total_debits'] = number_format((float)$tag['total_debits'], 2, '.', '');
            $tag['total_credits'] = number_format((float)$tag['total_credits'], 2, '.', '');
        }
        
        $balance = $totalCredits - $totalDebits;
        
        echo json_encode([
            'tags' => $tags,
            'total_transactions' => $totalTransactions,
            'total_debits' => number_format($totalDebits, 2, '.', ''),
            'total_credits' => number_format($totalCredits, 2, '.', ''),
            'balance' => number_format($balance, 2, '.', '')
        ]);
    }
}
