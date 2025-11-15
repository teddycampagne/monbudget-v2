<?php

namespace MonBudget\Controllers;

use MonBudget\Core\Database;

/**
 * Contrôleur de la page d'accueil et du dashboard
 * 
 * Gère l'affichage du dashboard principal avec les statistiques
 * financières de l'utilisateur : soldes, dépenses, revenus, budgets.
 * 
 * @package MonBudget\Controllers
 * @author MonBudget
 * @version 1.0.0
 */
class HomeController extends BaseController
{
    /**
     * Page d'accueil - Redirige vers le dashboard ou le login
     * 
     * Point d'entrée de l'application. Redirige vers la page de connexion
     * si l'utilisateur n'est pas authentifié, sinon affiche le dashboard.
     * 
     * @return void
     */
    public function index(): void
    {
        // Rediriger vers login si non authentifié
        if (!$this->isAuthenticated()) {
            $this->redirect('login');
        }
        
        $this->dashboard();
    }
    
    /**
     * Afficher le dashboard principal
     * 
     * Affiche les statistiques financières de l'utilisateur : soldes des comptes,
     * dépenses et revenus du mois, budgets, transactions récentes, etc.
     * 
     * @return void
     */
    public function dashboard(): void
    {
        $this->requireAuth();
        
        $user = $this->getUser();
        $userId = $user['id'];
        
        // Récupérer les statistiques
        $stats = $this->getDashboardStats($userId);
        
        $this->view('home.dashboard', [
            'user' => $user,
            'stats' => $stats
        ]);
    }
    
    /**
     * Récupérer les statistiques pour le dashboard
     * 
     * Calcule et retourne toutes les statistiques financières nécessaires
     * au dashboard : soldes, dépenses, revenus, budgets, etc.
     * 
     * @param int $userId ID de l'utilisateur
     * @return array Tableau associatif des statistiques
     */
    private function getDashboardStats(int $userId): array
    {
        $stats = [];
        
        try {
            // Nombre de comptes
            $comptes = Database::selectOne(
                "SELECT COUNT(*) as count FROM comptes WHERE user_id = ?",
                [$userId]
            );
            $stats['comptes'] = $comptes['count'] ?? 0;
            
            // Nombre de transactions
            $transactions = Database::selectOne(
                "SELECT COUNT(*) as count FROM transactions WHERE user_id = ?",
                [$userId]
            );
            $stats['transactions'] = $transactions['count'] ?? 0;
            
            // Solde total (somme des soldes actuels)
            $solde = Database::selectOne(
                "SELECT SUM(solde_actuel) as total FROM comptes WHERE user_id = ?",
                [$userId]
            );
            $stats['solde_total'] = $solde['total'] ?? 0;
            
            // Transactions du mois
            $transactionsMois = Database::selectOne(
                "SELECT COUNT(*) as count FROM transactions 
                 WHERE user_id = ? 
                 AND MONTH(date_transaction) = MONTH(CURRENT_DATE())
                 AND YEAR(date_transaction) = YEAR(CURRENT_DATE())",
                [$userId]
            );
            $stats['transactions_mois'] = $transactionsMois['count'] ?? 0;
            
            // Dépenses du mois
            $depensesMois = Database::selectOne(
                "SELECT SUM(montant) as total FROM transactions 
                 WHERE user_id = ? 
                 AND type_operation = 'debit'
                 AND MONTH(date_transaction) = MONTH(CURRENT_DATE())
                 AND YEAR(date_transaction) = YEAR(CURRENT_DATE())",
                [$userId]
            );
            $stats['depenses_mois'] = abs($depensesMois['total'] ?? 0);
            
            // Revenus du mois
            $revenusMois = Database::selectOne(
                "SELECT SUM(montant) as total FROM transactions 
                 WHERE user_id = ? 
                 AND type_operation = 'credit'
                 AND MONTH(date_transaction) = MONTH(CURRENT_DATE())
                 AND YEAR(date_transaction) = YEAR(CURRENT_DATE())",
                [$userId]
            );
            $stats['revenus_mois'] = $revenusMois['total'] ?? 0;
            
            // Balance du mois
            $stats['balance_mois'] = $stats['revenus_mois'] - $stats['depenses_mois'];
            
            // Évolution par rapport au mois dernier
            $depensesMoisDernier = Database::selectOne(
                "SELECT SUM(montant) as total FROM transactions 
                 WHERE user_id = ? 
                 AND type_operation = 'debit'
                 AND MONTH(date_transaction) = MONTH(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))
                 AND YEAR(date_transaction) = YEAR(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))",
                [$userId]
            );
            $stats['depenses_mois_dernier'] = abs($depensesMoisDernier['total'] ?? 0);
            
            if ($stats['depenses_mois_dernier'] > 0) {
                $stats['evolution_depenses'] = (($stats['depenses_mois'] - $stats['depenses_mois_dernier']) / $stats['depenses_mois_dernier']) * 100;
            } else {
                $stats['evolution_depenses'] = 0;
            }
            
            // Top 5 catégories de dépenses du mois
            $stats['top_categories'] = Database::select(
                "SELECT 
                    c.nom, 
                    c.couleur,
                    c.icone,
                    SUM(t.montant) as total,
                    COUNT(*) as nb_transactions
                 FROM transactions t
                 INNER JOIN categories c ON t.categorie_id = c.id
                 WHERE t.user_id = ? 
                 AND t.type_operation = 'debit'
                 AND MONTH(t.date_transaction) = MONTH(CURRENT_DATE())
                 AND YEAR(t.date_transaction) = YEAR(CURRENT_DATE())
                 GROUP BY c.id, c.nom, c.couleur, c.icone
                 ORDER BY total DESC
                 LIMIT 5",
                [$userId]
            );
            
            // Transactions non catégorisées
            $nonCategorisees = Database::selectOne(
                "SELECT COUNT(*) as count FROM transactions 
                 WHERE user_id = ? AND categorie_id IS NULL",
                [$userId]
            );
            $stats['transactions_non_categorisees'] = $nonCategorisees['count'] ?? 0;
            
            // Transactions non validées - Commenté car le champ est_valide n'existe pas toujours
            /*$nonValidees = Database::selectOne(
                "SELECT COUNT(*) as count FROM transactions 
                 WHERE user_id = ? AND est_valide = 0",
                [$userId]
            );
            $stats['transactions_non_validees'] = $nonValidees['count'] ?? 0;*/
            $stats['transactions_non_validees'] = 0; // Désactivé temporairement
            
            // Budgets du mois en cours - Désactivé temporairement car la structure de la table peut varier
            /*$stats['budgets'] = Database::select(
                "SELECT 
                    b.*,
                    c.nom as categorie_nom,
                    c.couleur as categorie_couleur,
                    c.icone as categorie_icone,
                    COALESCE(SUM(t.montant), 0) as depense_reelle
                 FROM budgets b
                 INNER JOIN categories c ON b.categorie_id = c.id
                 LEFT JOIN transactions t ON t.categorie_id = b.categorie_id 
                    AND t.user_id = b.user_id
                    AND t.type_operation = 'debit'
                    AND MONTH(t.date_transaction) = b.mois
                    AND YEAR(t.date_transaction) = b.annee
                 WHERE b.user_id = ?
                 AND b.mois = MONTH(CURRENT_DATE())
                 AND b.annee = YEAR(CURRENT_DATE())
                 GROUP BY b.id, b.montant_prevu, c.nom, c.couleur, c.icone
                 ORDER BY (COALESCE(SUM(t.montant), 0) / b.montant_prevu) DESC
                 LIMIT 5",
                [$userId]
            );*/
            $stats['budgets'] = []; // Désactivé temporairement
            
            // Dernières transactions
            $stats['dernieres_transactions'] = Database::select(
                "SELECT t.*, 
                    c.nom as compte_nom, 
                    cat.nom as categorie_nom,
                    cat.couleur as categorie_couleur,
                    cat.icone as categorie_icone,
                    ti.nom as tiers_nom
                 FROM transactions t
                 INNER JOIN comptes c ON t.compte_id = c.id
                 LEFT JOIN categories cat ON t.categorie_id = cat.id
                 LEFT JOIN tiers ti ON t.tiers_id = ti.id
                 WHERE t.user_id = ?
                 ORDER BY t.date_transaction DESC, t.created_at DESC
                 LIMIT 10",
                [$userId]
            );
            
            // Prochaines transactions récurrentes
            $stats['prochaines_recurrentes'] = Database::select(
                "SELECT t.*, c.nom as compte_nom, cat.nom as categorie_nom
                 FROM transactions t
                 INNER JOIN comptes c ON t.compte_id = c.id
                 LEFT JOIN categories cat ON t.categorie_id = cat.id
                 WHERE t.user_id = ?
                 AND t.est_recurrente = 1
                 AND t.recurrence_active = 1
                 AND (t.prochaine_execution IS NULL OR t.prochaine_execution >= CURRENT_DATE())
                 ORDER BY t.prochaine_execution ASC
                 LIMIT 5",
                [$userId]
            );
            
        } catch (\Exception $e) {
            // En cas d'erreur, logger et retourner des stats vides
            error_log("ERREUR Dashboard Stats: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            $stats = [
                'comptes' => 0,
                'transactions' => 0,
                'solde_total' => 0,
                'transactions_mois' => 0,
                'depenses_mois' => 0,
                'revenus_mois' => 0,
                'balance_mois' => 0,
                'evolution_depenses' => 0,
                'top_categories' => [],
                'transactions_non_categorisees' => 0,
                'transactions_non_validees' => 0,
                'budgets' => [],
                'dernieres_transactions' => [],
                'prochaines_recurrentes' => [],
                'error' => $e->getMessage() // Pour debug
            ];
        }
        
        return $stats;
    }
}
