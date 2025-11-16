<?php

namespace MonBudget\Controllers;

use MonBudget\Core\Database;
use MonBudget\Models\Compte;
use MonBudget\Models\Categorie;
use MonBudget\Models\Tiers;
use MonBudget\Models\Tag;

/**
 * Contrôleur de recherche globale
 * 
 * Gère la recherche et le filtrage avancés des transactions à travers tous les comptes.
 * Permet de rechercher par critères multiples : date, montant, catégorie, tiers, etc.
 * 
 * @package MonBudget\Controllers
 * @author MonBudget
 * @version 1.0.0
 */
class RechercheController extends BaseController
{
    /**
     * Page principale de recherche avec formulaire de filtres
     * 
     * Affiche le formulaire de recherche avancée avec tous les filtres disponibles :
     * comptes, dates, montants, catégories, tiers, etc.
     * 
     * @return void
     */
    public function index(): void
    {
        // Récupérer les données pour les filtres
        $comptes = Compte::getByUser($this->userId);
        $categories = Categorie::getCategoriesPrincipales($this->userId);
        $tiers = Tiers::getAllByUser($this->userId);
        
        // Récupérer les tags
        $tagModel = new Tag();
        $tags = $tagModel->getAllByUser($this->userId, 'name');
        
        // Organiser les catégories par type
        $categoriesDepenses = [];
        $categoriesRevenus = [];
        
        foreach ($categories as $cat) {
            if (empty($cat['parent_id'])) { // Catégories principales seulement
                if ($cat['type'] === 'depense') {
                    $categoriesDepenses[] = $cat;
                } else {
                    $categoriesRevenus[] = $cat;
                }
            }
        }
        
        $this->view('recherche/index', [
            'comptes' => $comptes,
            'categoriesDepenses' => $categoriesDepenses,
            'categoriesRevenus' => $categoriesRevenus,
            'tiers' => $tiers,
            'tags' => $tags
        ]);
    }
    
    /**
     * API: Recherche avancée
     */
    public function apiRecherche(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $limit = 50;
        $offset = ($page - 1) * $limit;
        
        // Construction de la requête
        $sql = "
            SELECT 
                t.*,
                t.validee as est_valide,
                c.nom as compte_nom,
                cat.nom as categorie_nom,
                cat.couleur as categorie_couleur,
                cat.icone as categorie_icone,
                sc.nom as sous_categorie_nom,
                ti.nom as tiers_nom,
                b.nom as banque_nom
            FROM transactions t
            INNER JOIN comptes c ON t.compte_id = c.id
            LEFT JOIN categories cat ON t.categorie_id = cat.id
            LEFT JOIN categories sc ON t.sous_categorie_id = sc.id
            LEFT JOIN tiers ti ON t.tiers_id = ti.id
            LEFT JOIN banques b ON c.banque_id = b.id
            WHERE t.user_id = ?
        ";
        
        $params = [$this->userId];
        
        // Filtre par compte
        if (!empty($_GET['compte_id'])) {
            $sql .= " AND t.compte_id = ?";
            $params[] = (int) $_GET['compte_id'];
        }
        
        // Filtre par type d'opération
        if (!empty($_GET['type_operation'])) {
            $sql .= " AND t.type_operation = ?";
            $params[] = $_GET['type_operation'];
        }
        
        // Filtre par catégorie
        if (!empty($_GET['categorie_id'])) {
            $categorieId = (int) $_GET['categorie_id'];
            if ($categorieId === -1) {
                // Non catégorisé
                $sql .= " AND t.categorie_id IS NULL";
            } else {
                // Catégorie ou ses sous-catégories
                $sql .= " AND (t.categorie_id = ? OR t.sous_categorie_id IN (
                    SELECT id FROM categories WHERE parent_id = ?
                ))";
                $params[] = $categorieId;
                $params[] = $categorieId;
            }
        }
        
        // Filtre par sous-catégorie
        if (!empty($_GET['sous_categorie_id'])) {
            $sql .= " AND t.sous_categorie_id = ?";
            $params[] = (int) $_GET['sous_categorie_id'];
        }
        
        // Filtre par tiers
        if (!empty($_GET['tiers_id'])) {
            $tiersId = (int) $_GET['tiers_id'];
            if ($tiersId === -1) {
                // Sans tiers
                $sql .= " AND t.tiers_id IS NULL";
            } else {
                $sql .= " AND t.tiers_id = ?";
                $params[] = $tiersId;
            }
        }
        
        // Filtre par tags (peut être multiple)
        if (!empty($_GET['tags']) && is_array($_GET['tags'])) {
            $tagIds = array_filter(array_map('intval', $_GET['tags']));
            if (!empty($tagIds)) {
                $placeholders = implode(',', array_fill(0, count($tagIds), '?'));
                $sql .= " AND t.id IN (
                    SELECT transaction_id FROM transaction_tags 
                    WHERE tag_id IN ($placeholders)
                )";
                $params = array_merge($params, $tagIds);
            }
        }
        
        // Filtre par date de début
        if (!empty($_GET['date_debut'])) {
            $sql .= " AND t.date_transaction >= ?";
            $params[] = $_GET['date_debut'];
        }
        
        // Filtre par date de fin
        if (!empty($_GET['date_fin'])) {
            $sql .= " AND t.date_transaction <= ?";
            $params[] = $_GET['date_fin'];
        }
        
        // Filtre par montant minimum
        if (isset($_GET['montant_min']) && $_GET['montant_min'] !== '') {
            $sql .= " AND t.montant >= ?";
            $params[] = (float) $_GET['montant_min'];
        }
        
        // Filtre par montant maximum
        if (isset($_GET['montant_max']) && $_GET['montant_max'] !== '') {
            $sql .= " AND t.montant <= ?";
            $params[] = (float) $_GET['montant_max'];
        }
        
        // Filtre par libellé (recherche textuelle)
        if (!empty($_GET['libelle'])) {
            $sql .= " AND t.libelle LIKE ?";
            $params[] = '%' . $_GET['libelle'] . '%';
        }
        
        // Filtre par statut de validation
        if (isset($_GET['est_valide']) && $_GET['est_valide'] !== '') {
            $sql .= " AND t.validee = ?";
            $params[] = (int) $_GET['est_valide'];
        }
        
        // Note: Le rapprochement bancaire n'est pas encore implémenté dans la table transactions
        // if (isset($_GET['est_rapproche']) && $_GET['est_rapproche'] !== '') {
        //     $sql .= " AND t.rapproche = ?";
        //     $params[] = (int) $_GET['est_rapproche'];
        // }
        
        // Compter le total
        $sqlCount = str_replace(
            "SELECT 
                t.*,
                c.nom as compte_nom,
                cat.nom as categorie_nom,
                cat.couleur as categorie_couleur,
                cat.icone as categorie_icone,
                sc.nom as sous_categorie_nom,
                ti.nom as tiers_nom,
                b.nom as banque_nom
            FROM",
            "SELECT COUNT(*) as total FROM",
            $sql
        );
        
        $countResult = Database::select($sqlCount, $params);
        $total = $countResult[0]['total'] ?? 0;
        
        // Tri
        $orderBy = $_GET['order_by'] ?? 'date_transaction';
        $orderDir = $_GET['order_dir'] ?? 'DESC';
        
        $allowedColumns = ['date_transaction', 'montant', 'libelle', 'compte_nom', 'categorie_nom', 'tiers_nom'];
        if (!in_array($orderBy, $allowedColumns)) {
            $orderBy = 'date_transaction';
        }
        if (!in_array($orderDir, ['ASC', 'DESC'])) {
            $orderDir = 'DESC';
        }
        
        // Requête pour les résultats paginés
        $sqlResults = $sql . " ORDER BY $orderBy $orderDir LIMIT $limit OFFSET $offset";
        $transactions = Database::select($sqlResults, $params);
        
        // Calcul du total et des statistiques (même requête, différentes colonnes SELECT)
        $sqlCount = "
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN t.type_operation = 'debit' THEN t.montant ELSE 0 END) as total_debits,
                SUM(CASE WHEN t.type_operation = 'credit' THEN t.montant ELSE 0 END) as total_credits
            FROM transactions t
            INNER JOIN comptes c ON t.compte_id = c.id
            LEFT JOIN categories cat ON t.categorie_id = cat.id
            LEFT JOIN categories sc ON t.sous_categorie_id = sc.id
            LEFT JOIN tiers ti ON t.tiers_id = ti.id
            LEFT JOIN banques b ON c.banque_id = b.id
            WHERE t.user_id = ?
        ";
        
        // Réappliquer les mêmes filtres (sans ORDER BY ni LIMIT)
        // Extraire la partie WHERE après "WHERE t.user_id = ?"
        $whereClause = substr($sql, strpos($sql, 'WHERE t.user_id = ?') + strlen('WHERE t.user_id = ?'));
        $sqlCount .= $whereClause;
        
        $countResult = Database::select($sqlCount, $params);
        $total = $countResult[0]['total'] ?? 0;
        $totalDebits = (float) ($countResult[0]['total_debits'] ?? 0);
        $totalCredits = (float) ($countResult[0]['total_credits'] ?? 0);
        
        $stats = [
            'total_debits' => $totalDebits,
            'total_credits' => $totalCredits,
            'balance' => $totalCredits - $totalDebits,
            'nb_transactions' => $total
        ];
        
        $this->json([
            'transactions' => $transactions,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit),
            'stats' => $stats
        ]);
    }
    
    /**
     * API: Export CSV des résultats
     */
    public function apiExport(): void
    {
        // Même logique que apiRecherche mais sans pagination
        $sql = "
            SELECT 
                t.date_transaction,
                c.nom as compte,
                t.type_operation,
                t.libelle,
                cat.nom as categorie,
                sc.nom as sous_categorie,
                ti.nom as tiers,
                t.montant,
                CASE WHEN t.est_valide = 1 THEN 'Oui' ELSE 'Non' END as valide,
                CASE WHEN t.est_rapproche = 1 THEN 'Oui' ELSE 'Non' END as rapproche
            FROM transactions t
            INNER JOIN comptes c ON t.compte_id = c.id
            LEFT JOIN categories cat ON t.categorie_id = cat.id
            LEFT JOIN categories sc ON t.sous_categorie_id = sc.id
            LEFT JOIN tiers ti ON t.tiers_id = ti.id
            WHERE t.user_id = ?
        ";
        
        $params = [$this->userId];
        
        // Réappliquer tous les filtres (copier la logique de apiRecherche)
        if (!empty($_GET['compte_id'])) {
            $sql .= " AND t.compte_id = ?";
            $params[] = (int) $_GET['compte_id'];
        }
        
        if (!empty($_GET['type_operation'])) {
            $sql .= " AND t.type_operation = ?";
            $params[] = $_GET['type_operation'];
        }
        
        if (!empty($_GET['categorie_id'])) {
            $categorieId = (int) $_GET['categorie_id'];
            if ($categorieId === -1) {
                $sql .= " AND t.categorie_id IS NULL";
            } else {
                $sql .= " AND (t.categorie_id = ? OR t.sous_categorie_id IN (
                    SELECT id FROM categories WHERE parent_id = ?
                ))";
                $params[] = $categorieId;
                $params[] = $categorieId;
            }
        }
        
        if (!empty($_GET['sous_categorie_id'])) {
            $sql .= " AND t.sous_categorie_id = ?";
            $params[] = (int) $_GET['sous_categorie_id'];
        }
        
        if (!empty($_GET['tiers_id'])) {
            $tiersId = (int) $_GET['tiers_id'];
            if ($tiersId === -1) {
                $sql .= " AND t.tiers_id IS NULL";
            } else {
                $sql .= " AND t.tiers_id = ?";
                $params[] = $tiersId;
            }
        }
        
        if (!empty($_GET['date_debut'])) {
            $sql .= " AND t.date_transaction >= ?";
            $params[] = $_GET['date_debut'];
        }
        
        if (!empty($_GET['date_fin'])) {
            $sql .= " AND t.date_transaction <= ?";
            $params[] = $_GET['date_fin'];
        }
        
        if (isset($_GET['montant_min']) && $_GET['montant_min'] !== '') {
            $sql .= " AND t.montant >= ?";
            $params[] = (float) $_GET['montant_min'];
        }
        
        if (isset($_GET['montant_max']) && $_GET['montant_max'] !== '') {
            $sql .= " AND t.montant <= ?";
            $params[] = (float) $_GET['montant_max'];
        }
        
        if (!empty($_GET['libelle'])) {
            $sql .= " AND t.libelle LIKE ?";
            $params[] = '%' . $_GET['libelle'] . '%';
        }
        
        if (isset($_GET['est_valide']) && $_GET['est_valide'] !== '') {
            $sql .= " AND t.est_valide = ?";
            $params[] = (int) $_GET['est_valide'];
        }
        
        if (isset($_GET['est_rapproche']) && $_GET['est_rapproche'] !== '') {
            $sql .= " AND t.est_rapproche = ?";
            $params[] = (int) $_GET['est_rapproche'];
        }
        
        $sql .= " ORDER BY t.date_transaction DESC";
        
        $transactions = Database::select($sql, $params);
        
        // Générer le CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="recherche_transactions_' . date('Y-m-d_His') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // BOM UTF-8 pour Excel
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // En-têtes
        fputcsv($output, [
            'Date',
            'Compte',
            'Type',
            'Libellé',
            'Catégorie',
            'Sous-catégorie',
            'Tiers',
            'Montant',
            'Validé',
            'Rapproché'
        ], ';');
        
        // Données
        foreach ($transactions as $t) {
            fputcsv($output, [
                date('d/m/Y', strtotime($t['date_transaction'])),
                $t['compte'],
                $t['type_operation'] === 'credit' ? 'Crédit' : 'Débit',
                $t['libelle'],
                $t['categorie'] ?? '',
                $t['sous_categorie'] ?? '',
                $t['tiers'] ?? '',
                number_format($t['montant'], 2, ',', ''),
                $t['valide'],
                $t['rapproche']
            ], ';');
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * API: Récupérer les sous-catégories d'une catégorie
     */
    public function apiSousCategories(): void
    {
        $categorieId = (int) ($_GET['categorie_id'] ?? 0);
        
        if (!$categorieId) {
            $this->json([]);
            return;
        }
        
        $sql = "
            SELECT id, nom, couleur, icone
            FROM categories
            WHERE parent_id = ? AND user_id = ?
            ORDER BY nom ASC
        ";
        
        $sousCategories = Database::select($sql, [$categorieId, $this->userId]);
        
        $this->json($sousCategories);
    }
}
