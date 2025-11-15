<?php

namespace MonBudget\Models;

use MonBudget\Core\Database;

/**
 * Class Budget
 * 
 * Modèle de gestion des budgets par catégorie.
 * 
 * Permet de définir des budgets prévisionnels par catégorie de dépenses,
 * de suivre les réalisations et d'analyser les dépassements.
 * 
 * Deux types de budgets supportés :
 * - Budgets mensuels : définis mois par mois (periode = 'mensuel', mois renseigné)
 * - Budgets annuels : cumul des budgets mensuels sur l'année (periode = 'annuel', mois NULL)
 * 
 * Pour chaque budget, le système calcule automatiquement :
 * - Montant réalisé : somme des débits de la catégorie sur la période
 * - Pourcentage d'utilisation : (réalisé / prévu) * 100
 * - Dépassement : si réalisé > prévu
 * - Restant : prévu - réalisé
 * 
 * Fonctionnalités avancées :
 * - Analyse des transactions passées pour suggérer des budgets
 * - Détection des valeurs aberrantes (outliers)
 * - Statistiques globales (total prévu, total réalisé, nb dépassements)
 * 
 * @package MonBudget\Models
 * 
 * @property int $id Identifiant unique du budget
 * @property int $user_id ID de l'utilisateur propriétaire
 * @property int $categorie_id ID de la catégorie de dépense
 * @property float $montant Montant budgété (prévu)
 * @property string $periode Type de période (mensuel, annuel)
 * @property int|null $mois Mois concerné (1-12) ou NULL pour annuel
 * @property int $annee Année concernée
 * @property string $created_at Date de création
 * @property string|null $updated_at Date de dernière modification
 */
class Budget
{
    /** @var string Table de la base de données */
    private static string $table = 'budgets';
    
    /**
     * Récupère tous les budgets d'un utilisateur pour une période donnée
     * 
     * Si $mois est NULL, retourne les budgets annuels (somme des budgets mensuels par catégorie).
     * Si $mois est renseigné, retourne les budgets du mois spécifique.
     * 
     * Pour chaque budget, enrichit automatiquement avec :
     * - montant_realise : somme des débits de la catégorie
     * - pourcentage : (réalisé / prévu) * 100
     * - depasse : boolean indiquant un dépassement
     * - restant : montant restant à dépenser
     * 
     * @param int $userId ID de l'utilisateur
     * @param int $annee Année à consulter
     * @param int|null $mois Mois à consulter (1-12) ou null pour l'année complète
     * @return array Liste des budgets enrichis avec infos catégorie et réalisation
     * 
     * @example
     * // Budgets de janvier 2024
     * $budgets = Budget::getAllByPeriod($userId, 2024, 1);
     * foreach ($budgets as $b) {
     *     echo "{$b['categorie_nom']} : {$b['montant_realise']}/{$b['montant']} € ";
     *     echo $b['depasse'] ? '⚠️ DÉPASSÉ' : '✅';
     *     echo "\n";
     * }
     * 
     * // Vue annuelle 2024
     * $budgetsAnnuels = Budget::getAllByPeriod($userId, 2024, null);
     * 
     * @see getMontantRealise()
     */
    public static function getAllByPeriod(int $userId, int $annee, ?int $mois = null): array
    {
        // Si on demande l'année complète, on doit grouper par catégorie
        if ($mois === null) {
            return self::getBudgetsAnnuels($userId, $annee);
        }
        
        // Sinon, afficher les budgets du mois spécifique
        $sql = "
            SELECT 
                b.*,
                c.nom as categorie_nom,
                c.couleur as categorie_couleur,
                c.icone as categorie_icone,
                c.type as categorie_type
            FROM " . self::$table . " b
            INNER JOIN categories c ON b.categorie_id = c.id
            WHERE b.user_id = ? 
            AND b.annee = ?
            AND b.mois = ?
            ORDER BY c.nom ASC
        ";
        
        $budgets = Database::select($sql, [$userId, $annee, $mois]);
        
        // Enrichir avec les montants réalisés
        foreach ($budgets as &$budget) {
            $budget['montant_realise'] = self::getMontantRealise(
                $budget['categorie_id'], 
                $annee, 
                $mois
            );
            
            $budget['pourcentage'] = $budget['montant'] > 0 
                ? round(($budget['montant_realise'] / $budget['montant']) * 100, 1)
                : 0;
            $budget['depasse'] = $budget['montant_realise'] > $budget['montant'];
            $budget['restant'] = $budget['montant'] - $budget['montant_realise'];
        }
        
        return $budgets;
    }
    
    /**
     * Récupérer les budgets annuels (somme des budgets mensuels par catégorie)
     */
    private static function getBudgetsAnnuels(int $userId, int $annee): array
    {
        $sql = "
            SELECT 
                c.id as categorie_id,
                c.nom as categorie_nom,
                c.couleur as categorie_couleur,
                c.icone as categorie_icone,
                c.type as categorie_type,
                SUM(b.montant) as montant,
                'annuel' as periode,
                NULL as mois,
                ? as annee,
                MIN(b.id) as id
            FROM categories c
            INNER JOIN " . self::$table . " b ON c.id = b.categorie_id
            WHERE b.user_id = ? 
            AND b.annee = ?
            AND b.periode = 'mensuel'
            GROUP BY c.id, c.nom, c.couleur, c.icone, c.type
            ORDER BY c.nom ASC
        ";
        
        $budgets = Database::select($sql, [$annee, $userId, $annee]);
        
        // Enrichir avec les montants réalisés
        foreach ($budgets as &$budget) {
            $budget['montant_realise'] = self::getMontantRealise(
                $budget['categorie_id'], 
                $annee, 
                null  // null = toute l'année
            );
            
            $budget['pourcentage'] = $budget['montant'] > 0 
                ? round(($budget['montant_realise'] / $budget['montant']) * 100, 1)
                : 0;
            $budget['depasse'] = $budget['montant_realise'] > $budget['montant'];
            $budget['restant'] = $budget['montant'] - $budget['montant_realise'];
        }
        
        return $budgets;
    }
    
    /**
     * Calcule le montant réalisé (dépensé) pour une catégorie sur une période
     * 
     * Somme tous les débits (type_operation = 'debit') de la catégorie
     * sur la période spécifiée.
     * 
     * @param int $categorieId ID de la catégorie
     * @param int $annee Année concernée
     * @param int|null $mois Mois concerné (1-12) ou null pour toute l'année
     * @return float Montant total dépensé
     * 
     * @example
     * // Dépenses en alimentation pour janvier 2024
     * $depenses = Budget::getMontantRealise($alimentationId, 2024, 1);
     * 
     * // Dépenses en alimentation pour toute l'année 2024
     * $depensesAnnuelles = Budget::getMontantRealise($alimentationId, 2024, null);
     */
    public static function getMontantRealise(int $categorieId, int $annee, ?int $mois = null): float
    {
        $sql = "
            SELECT COALESCE(SUM(montant), 0) as total
            FROM transactions
            WHERE categorie_id = ?
            AND type_operation = 'debit'
            AND YEAR(date_transaction) = ?
        ";
        
        $params = [$categorieId, $annee];
        
        if ($mois !== null) {
            $sql .= " AND MONTH(date_transaction) = ?";
            $params[] = $mois;
        }
        
        $result = Database::selectOne($sql, $params);
        return (float) ($result['total'] ?? 0);
    }
    
    /**
     * Crée un nouveau budget pour une catégorie et une période
     * 
     * Vérifie d'abord qu'aucun budget n'existe déjà pour cette combinaison
     * catégorie/année/mois pour éviter les doublons.
     * 
     * @param array $data Données du budget à créer
     *                    - int $data['user_id'] ID utilisateur (requis)
     *                    - int $data['categorie_id'] ID catégorie (requis)
     *                    - float $data['montant'] Montant budgété (requis)
     *                    - int $data['annee'] Année (requis)
     *                    - int|null $data['mois'] Mois (NULL pour budget annuel)
     *                    - string $data['periode'] Type (mensuel/annuel, défaut: mensuel)
     * @return int|null ID du budget créé, ou null si déjà existant
     * 
     * @example
     * $budgetId = Budget::create([
     *     'user_id' => $userId,
     *     'categorie_id' => $alimentationId,
     *     'montant' => 400.00,
     *     'annee' => 2024,
     *     'mois' => 1,
     *     'periode' => 'mensuel'
     * ]);
     * 
     * @see exists()
     */
    public static function create(array $data): ?int
    {
        // Vérifier si un budget existe déjà pour cette période
        if (self::exists($data['user_id'], $data['categorie_id'], $data['annee'], $data['mois'] ?? null)) {
            return null;
        }
        
        $sql = "INSERT INTO " . self::$table . " 
                (user_id, categorie_id, montant, periode, mois, annee, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        
        return Database::insert($sql, [
            $data['user_id'],
            $data['categorie_id'],
            $data['montant'],
            $data['periode'] ?? 'mensuel',
            $data['mois'] ?? null,
            $data['annee']
        ]);
    }
    
    /**
     * Met à jour un budget existant
     * 
     * @param int $id ID du budget à modifier
     * @param array $data Nouvelles données
     *                    - float $data['montant'] Nouveau montant
     *                    - int $data['annee'] Nouvelle année
     *                    - int|null $data['mois'] Nouveau mois
     *                    - string $data['periode'] Nouvelle période
     * @return bool True si mis à jour avec succès
     * 
     * @example
     * Budget::update($budgetId, [
     *     'montant' => 450.00,
     *     'annee' => 2024,
     *     'mois' => 1,
     *     'periode' => 'mensuel'
     * ]);
     */
    public static function update(int $id, array $data): bool
    {
        $sql = "UPDATE " . self::$table . " 
                SET montant = ?,
                    periode = ?,
                    mois = ?,
                    annee = ?,
                    updated_at = NOW()
                WHERE id = ?";
        
        Database::update($sql, [
            $data['montant'],
            $data['periode'] ?? 'mensuel',
            $data['mois'] ?? null,
            $data['annee'],
            $id
        ]);
        
        return true;
    }
    
    /**
     * Supprime un budget
     * 
     * @param int $id ID du budget à supprimer
     * @return bool True si supprimé avec succès
     * 
     * @example
     * if (Budget::delete($budgetId)) {
     *     echo "Budget supprimé";
     * }
     */
    public static function delete(int $id): bool
    {
        $sql = "DELETE FROM " . self::$table . " WHERE id = ?";
        Database::delete($sql, [$id]);
        return true;
    }
    
    /**
     * Supprime tous les budgets mensuels d'une catégorie pour une année
     * 
     * Utile pour réinitialiser les budgets mensuels d'une catégorie
     * avant de les recalculer ou régénérer.
     * 
     * @param int $userId ID de l'utilisateur
     * @param int $categorieId ID de la catégorie
     * @param int $annee Année concernée
     * @return int Nombre de budgets supprimés
     * 
     * @example
     * $nbSupprimes = Budget::deleteByCategorieMensuel($userId, $alimentationId, 2024);
     * echo "{$nbSupprimes} budgets mensuels supprimés";
     */
    public static function deleteByCategorieMensuel(int $userId, int $categorieId, int $annee): int
    {
        // Compter d'abord
        $sqlCount = "SELECT COUNT(*) as nb FROM " . self::$table . " 
                     WHERE user_id = ? 
                     AND categorie_id = ? 
                     AND annee = ? 
                     AND periode = 'mensuel'";
        
        $result = Database::selectOne($sqlCount, [$userId, $categorieId, $annee]);
        $count = (int) ($result['nb'] ?? 0);
        
        // Supprimer
        $sql = "DELETE FROM " . self::$table . " 
                WHERE user_id = ? 
                AND categorie_id = ? 
                AND annee = ? 
                AND periode = 'mensuel'";
        
        Database::delete($sql, [$userId, $categorieId, $annee]);
        
        return $count;
    }
    
    /**
     * Récupère un budget par son identifiant avec infos catégorie
     * 
     * @param int $id ID du budget
     * @return array|null Données du budget avec nom de catégorie, ou null si non trouvé
     * 
     * @example
     * $budget = Budget::find($budgetId);
     * echo "{$budget['categorie_nom']} : {$budget['montant']} €";
     */
    public static function find(int $id): ?array
    {
        $sql = "SELECT b.*, c.nom as categorie_nom 
                FROM " . self::$table . " b
                LEFT JOIN categories c ON b.categorie_id = c.id
                WHERE b.id = ?";
        
        return Database::selectOne($sql, [$id]);
    }
    
    /**
     * Vérifie si un budget existe déjà pour une période donnée
     * 
     * Empêche la création de doublons (même catégorie, même année, même mois).
     * Permet d'exclure un budget lors de la modification.
     * 
     * @param int $userId ID de l'utilisateur
     * @param int $categorieId ID de la catégorie
     * @param int $annee Année
     * @param int|null $mois Mois (NULL pour budget annuel)
     * @param int|null $excludeId ID du budget à exclure (pour édition)
     * @return bool True si un budget existe déjà
     * 
     * @example
     * // Vérifier avant création
     * if (Budget::exists($userId, $catId, 2024, 1)) {
     *     echo "Un budget existe déjà pour janvier 2024";
     * }
     * 
     * // Vérifier en excluant le budget en cours de modification
     * if (Budget::exists($userId, $catId, 2024, 1, $budgetId)) {
     *     echo "Un autre budget existe déjà";
     * }
     */
    public static function exists(int $userId, int $categorieId, int $annee, ?int $mois = null, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) as count 
                FROM " . self::$table . " 
                WHERE user_id = ? 
                AND categorie_id = ? 
                AND annee = ?";
        
        $params = [$userId, $categorieId, $annee];
        
        if ($mois !== null) {
            $sql .= " AND mois = ?";
            $params[] = $mois;
        } else {
            $sql .= " AND mois IS NULL";
        }
        
        if ($excludeId !== null) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $result = Database::selectOne($sql, $params);
        return ($result['count'] ?? 0) > 0;
    }
    
    /**
     * Récupère uniquement les budgets dépassés sur une période
     * 
     * Filtre les budgets où le montant réalisé dépasse le montant prévu.
     * 
     * @param int $userId ID de l'utilisateur
     * @param int $annee Année
     * @param int|null $mois Mois (NULL pour l'année entière)
     * @return array Liste des budgets dépassés
     * 
     * @example
     * $depassements = Budget::getDepassements($userId, 2024, 1);
     * foreach ($depassements as $budget) {
     *     $ecart = $budget['montant_realise'] - $budget['montant'];
     *     echo "{$budget['categorie_nom']} : dépassement de {$ecart} €\n";
     * }
     */
    public static function getDepassements(int $userId, int $annee, ?int $mois = null): array
    {
        $budgets = self::getAllByPeriod($userId, $annee, $mois);
        
        return array_filter($budgets, function($budget) {
            return $budget['depasse'];
        });
    }
    
    /**
     * Calcule les statistiques globales des budgets sur une période
     * 
     * Agrège les données de tous les budgets pour fournir une vue d'ensemble :
     * - Nombre total de budgets
     * - Total prévu (somme des montants budgétés)
     * - Total réalisé (somme des dépenses)
     * - Total restant
     * - Nombre de dépassements
     * - Pourcentage d'utilisation global
     * 
     * @param int $userId ID de l'utilisateur
     * @param int $annee Année
     * @param int|null $mois Mois (NULL pour l'année entière)
     * @return array Tableau de statistiques avec clés : nb_budgets, total_prevu, total_realise, 
     *               total_restant, nb_depassements, pourcentage_global
     * 
     * @example
     * $stats = Budget::getStats($userId, 2024, 1);
     * echo "Budgets janvier 2024 :\n";
     * echo "  Prévu : {$stats['total_prevu']} €\n";
     * echo "  Réalisé : {$stats['total_realise']} € ({$stats['pourcentage_global']}%)\n";
     * echo "  Restant : {$stats['total_restant']} €\n";
     * echo "  Dépassements : {$stats['nb_depassements']}/{$stats['nb_budgets']}\n";
     */
    public static function getStats(int $userId, int $annee, ?int $mois = null): array
    {
        $budgets = self::getAllByPeriod($userId, $annee, $mois);
        
        $totalPrevu = 0;
        $totalRealise = 0;
        $nbDepassements = 0;
        
        foreach ($budgets as $budget) {
            $totalPrevu += $budget['montant'];
            $totalRealise += $budget['montant_realise'];
            if ($budget['depasse']) {
                $nbDepassements++;
            }
        }
        
        return [
            'nb_budgets' => count($budgets),
            'total_prevu' => $totalPrevu,
            'total_realise' => $totalRealise,
            'total_restant' => $totalPrevu - $totalRealise,
            'nb_depassements' => $nbDepassements,
            'pourcentage_global' => $totalPrevu > 0 ? round(($totalRealise / $totalPrevu) * 100, 1) : 0
        ];
    }
    
    /**
     * Récupère les catégories de dépenses sans budget pour une période
     * 
     * Utile pour afficher les catégories disponibles lors de la création de budgets.
     * Exclut les catégories qui ont déjà un budget défini.
     * 
     * @param int $userId ID de l'utilisateur
     * @param int $annee Année
     * @param int|null $mois Mois (NULL pour budget annuel)
     * @return array Liste des catégories disponibles (type = 'depense')
     * 
     * @example
     * $disponibles = Budget::getCategoriesDisponibles($userId, 2024, 1);
     * echo "Catégories sans budget pour janvier : " . count($disponibles) . "\n";
     * foreach ($disponibles as $cat) {
     *     echo "  - {$cat['nom']}\n";
     * }
     */
    public static function getCategoriesDisponibles(int $userId, int $annee, ?int $mois = null): array
    {
        $sql = "
            SELECT c.*
            FROM categories c
            WHERE (c.user_id = ? OR c.user_id IS NULL)
            AND c.type = 'depense'
            AND c.parent_id IS NULL
            AND c.id NOT IN (
                SELECT categorie_id 
                FROM " . self::$table . " 
                WHERE user_id = ? 
                AND annee = ?
        ";
        
        $params = [$userId, $userId, $annee];
        
        if ($mois !== null) {
            $sql .= " AND mois = ?";
            $params[] = $mois;
        } else {
            $sql .= " AND mois IS NULL";
        }
        
        $sql .= ")
            ORDER BY c.nom ASC";
        
        return Database::select($sql, $params);
    }
    
    /**
     * Analyser les transactions passées pour générer des suggestions de budgets
     * 
     * @param int $userId ID de l'utilisateur
     * @param int $anneeSource Année source pour l'analyse
     * @param int $nbMoisAnalyse Nombre de mois à analyser (6 ou 12)
     * @param bool $excludeOutliers Exclure les valeurs aberrantes
     * @return array Suggestions par catégorie avec détails mensuels
     */
    public static function analyserTransactions(int $userId, int $anneeSource, int $nbMoisAnalyse = 12, bool $excludeOutliers = false): array
    {
        // Déterminer la période d'analyse
        $dateDebut = date('Y-m-01', strtotime("$anneeSource-01-01 -$nbMoisAnalyse months"));
        $dateFin = "$anneeSource-12-31";
        
        $sql = "
            SELECT 
                c.id as categorie_id,
                c.nom as categorie_nom,
                c.icone as categorie_icone,
                c.couleur as categorie_couleur,
                YEAR(t.date_transaction) as annee,
                MONTH(t.date_transaction) as mois,
                SUM(t.montant) as total_mois
            FROM transactions t
            INNER JOIN categories c ON t.categorie_id = c.id
            WHERE t.user_id = ?
            AND t.type_operation = 'debit'
            AND t.date_transaction >= ?
            AND t.date_transaction <= ?
            AND c.type = 'depense'
            GROUP BY c.id, c.nom, c.icone, c.couleur, YEAR(t.date_transaction), MONTH(t.date_transaction)
            ORDER BY c.nom ASC, annee ASC, mois ASC
        ";
        
        $transactions = Database::select($sql, [$userId, $dateDebut, $dateFin]);
        
        // Grouper par catégorie
        $categories = [];
        foreach ($transactions as $trans) {
            $catId = $trans['categorie_id'];
            
            if (!isset($categories[$catId])) {
                $categories[$catId] = [
                    'categorie_id' => $catId,
                    'categorie_nom' => $trans['categorie_nom'],
                    'categorie_icone' => $trans['categorie_icone'],
                    'categorie_couleur' => $trans['categorie_couleur'],
                    'montants_mensuels' => []
                ];
            }
            
            $categories[$catId]['montants_mensuels'][] = (float) $trans['total_mois'];
        }
        
        // Calculer les statistiques pour chaque catégorie
        $suggestions = [];
        foreach ($categories as $catId => $data) {
            $montants = $data['montants_mensuels'];
            
            if (empty($montants)) {
                continue;
            }
            
            // Exclure les outliers si demandé (méthode IQR)
            if ($excludeOutliers && count($montants) >= 4) {
                $montants = self::removeOutliers($montants);
            }
            
            $moyenne = array_sum($montants) / count($montants);
            $min = min($montants);
            $max = max($montants);
            
            $suggestions[] = [
                'categorie_id' => $catId,
                'categorie_nom' => $data['categorie_nom'],
                'categorie_icone' => $data['categorie_icone'],
                'categorie_couleur' => $data['categorie_couleur'],
                'moyenne_mensuelle' => round($moyenne, 2),
                'min_mensuel' => round($min, 2),
                'max_mensuel' => round($max, 2),
                'nb_mois_analyse' => count($montants),
                'montants_detail' => $montants
            ];
        }
        
        return $suggestions;
    }
    
    /**
     * Générer des budgets pour une année cible basés sur l'analyse
     * 
     * @param int $userId ID de l'utilisateur
     * @param int $anneeCible Année pour laquelle générer les budgets
     * @param array $suggestions Résultat de analyserTransactions()
     * @param float $coefficientAjustement Coefficient d'ajustement (ex: 1.05 pour +5%)
     * @param bool $variationsSaisonnieres Tenir compte des variations saisonnières
     * @return array Budgets mensuels suggérés par catégorie
     */
    public static function genererProjection(int $userId, int $anneeCible, array $suggestions, float $coefficientAjustement = 1.0, bool $variationsSaisonnieres = false): array
    {
        $projections = [];
        
        foreach ($suggestions as $suggestion) {
            $catId = $suggestion['categorie_id'];
            $moyenne = $suggestion['moyenne_mensuelle'];
            
            if ($variationsSaisonnieres && !empty($suggestion['montants_detail'])) {
                // Calculer les variations mensuelles par rapport à la moyenne
                $variations = self::calculerVariationsSaisonnieres($suggestion['montants_detail']);
                
                // Générer 12 budgets mensuels avec variations
                $budgetsMensuels = [];
                for ($mois = 1; $mois <= 12; $mois++) {
                    $coefficient = $variations[$mois] ?? 1.0;
                    $montant = round($moyenne * $coefficient * $coefficientAjustement, 2);
                    $budgetsMensuels[$mois] = max(0.01, $montant); // Minimum 0.01€
                }
                
                $projections[] = [
                    'categorie_id' => $catId,
                    'categorie_nom' => $suggestion['categorie_nom'],
                    'categorie_icone' => $suggestion['categorie_icone'],
                    'categorie_couleur' => $suggestion['categorie_couleur'],
                    'budgets_mensuels' => $budgetsMensuels,
                    'total_annuel' => array_sum($budgetsMensuels),
                    'base_calcul' => 'Variations saisonnières'
                ];
            } else {
                // Budget équitable sur 12 mois
                $montantMensuel = round($moyenne * $coefficientAjustement, 2);
                
                $budgetsMensuels = [];
                for ($mois = 1; $mois <= 12; $mois++) {
                    $budgetsMensuels[$mois] = max(0.01, $montantMensuel);
                }
                
                $projections[] = [
                    'categorie_id' => $catId,
                    'categorie_nom' => $suggestion['categorie_nom'],
                    'categorie_icone' => $suggestion['categorie_icone'],
                    'categorie_couleur' => $suggestion['categorie_couleur'],
                    'budgets_mensuels' => $budgetsMensuels,
                    'total_annuel' => $montantMensuel * 12,
                    'base_calcul' => 'Moyenne équitable'
                ];
            }
        }
        
        return $projections;
    }
    
    /**
     * Supprimer les valeurs aberrantes (outliers) avec la méthode IQR
     */
    private static function removeOutliers(array $data): array
    {
        sort($data);
        $count = count($data);
        
        if ($count < 4) {
            return $data;
        }
        
        // Calculer Q1 et Q3
        $q1Index = floor($count * 0.25);
        $q3Index = floor($count * 0.75);
        
        $q1 = $data[$q1Index];
        $q3 = $data[$q3Index];
        $iqr = $q3 - $q1;
        
        // Limites
        $lowerBound = $q1 - (1.5 * $iqr);
        $upperBound = $q3 + (1.5 * $iqr);
        
        // Filtrer
        return array_filter($data, function($value) use ($lowerBound, $upperBound) {
            return $value >= $lowerBound && $value <= $upperBound;
        });
    }
    
    /**
     * Calculer les coefficients de variation saisonnière par mois
     */
    private static function calculerVariationsSaisonnieres(array $montantsDetail): array
    {
        // Regrouper par mois (en supposant que les données sont chronologiques)
        $parMois = [];
        $moisActuel = 1;
        
        foreach ($montantsDetail as $montant) {
            $mois = (($moisActuel - 1) % 12) + 1;
            if (!isset($parMois[$mois])) {
                $parMois[$mois] = [];
            }
            $parMois[$mois][] = $montant;
            $moisActuel++;
        }
        
        // Calculer la moyenne globale
        $moyenneGlobale = array_sum($montantsDetail) / count($montantsDetail);
        
        // Calculer le coefficient pour chaque mois
        $coefficients = [];
        for ($mois = 1; $mois <= 12; $mois++) {
            if (isset($parMois[$mois]) && !empty($parMois[$mois])) {
                $moyenneMois = array_sum($parMois[$mois]) / count($parMois[$mois]);
                $coefficients[$mois] = $moyenneGlobale > 0 ? $moyenneMois / $moyenneGlobale : 1.0;
            } else {
                $coefficients[$mois] = 1.0;
            }
        }
        
        return $coefficients;
    }
}
