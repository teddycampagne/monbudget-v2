<?php

namespace MonBudget\Models;

use MonBudget\Core\Database;

/**
 * Class Transaction
 * 
 * Modèle de gestion des transactions bancaires et opérations récurrentes.
 * 
 * Gère deux types de transactions :
 * - Transactions simples : opérations bancaires ponctuelles (débits, crédits, virements)
 * - Transactions récurrentes : modèles générateurs de transactions automatiques (loyers, salaires, etc.)
 * 
 * Supporte la catégorisation, les tiers, les imports de fichiers bancaires,
 * la validation et les transferts entre comptes.
 * 
 * @package MonBudget\Models
 * @extends BaseModel
 * 
 * @property int $id Identifiant unique de la transaction
 * @property int $user_id ID de l'utilisateur propriétaire
 * @property int $compte_id ID du compte bancaire concerné
 * @property int|null $compte_destination_id ID du compte destinataire (pour virements internes)
 * @property string $date_transaction Date de l'opération
 * @property string|null $date_valeur Date de valeur bancaire
 * @property float $montant Montant de l'opération (positif pour crédits, négatif pour débits)
 * @property string $libelle Libellé de l'opération
 * @property string|null $description Description détaillée
 * @property string|null $numero_operation Numéro d'opération bancaire
 * @property string|null $reference_banque Référence unique de la banque
 * @property int|null $categorie_id ID de la catégorie
 * @property int|null $sous_categorie_id ID de la sous-catégorie
 * @property int|null $tiers_id ID du tiers associé
 * @property string $type_operation Type d'opération (credit, debit, virement)
 * @property string|null $moyen_paiement Moyen de paiement (virement, prelevement, carte, cheque, etc.)
 * @property string|null $beneficiaire Nom du bénéficiaire
 * @property bool $est_recurrente Indicateur de transaction récurrente (0 ou 1)
 * @property string|null $frequence Fréquence de récurrence (quotidien, hebdomadaire, mensuel, etc.)
 * @property int|null $intervalle Intervalle entre exécutions (ex: 2 pour "tous les 2 mois")
 * @property int|null $jour_execution Jour du mois d'exécution (1-31)
 * @property int|null $jour_semaine Jour de la semaine (1=Lundi, 7=Dimanche)
 * @property string|null $date_debut Date de début de récurrence
 * @property string|null $date_fin Date de fin de récurrence (optionnel)
 * @property string|null $prochaine_execution Date de la prochaine exécution programmée
 * @property string|null $derniere_execution Date de la dernière exécution
 * @property int $nb_executions Nombre d'exécutions effectuées
 * @property int|null $nb_executions_max Nombre maximum d'exécutions (optionnel)
 * @property bool $auto_validation Valider automatiquement les transactions générées
 * @property string $tolerance_weekend Gestion des weekends (aucune, jour_ouvre_suivant, jour_ouvre_precedent)
 * @property bool $recurrence_active Récurrence active ou en pause
 * @property bool $validee Transaction validée ou en attente
 * @property bool $importee Transaction issue d'un import de fichier
 * @property string|null $fichier_import Nom du fichier d'import source
 */
class Transaction extends BaseModel
{
    /** @var string Table de la base de données */
    protected static string $table = 'transactions';
    
    /** @var array Champs autorisés pour les opérations de création/modification */
    protected static array $fillable = [
        'user_id',
        'compte_id',
        'compte_destination_id',
        'date_transaction',
        'date_valeur',
        'montant',
        'libelle',
        'description',
        'numero_operation',
        'reference_banque',
        'categorie_id',
        'sous_categorie_id',
        'tiers_id',
        'type_operation',
        'moyen_paiement',
        'beneficiaire',
        'recurrence_id', // FK vers table recurrences
        'validee',
        'importee',
        'fichier_import'
    ];
    
    /**
     * Récupère toutes les transactions avec les détails des entités liées
     * 
     * Effectue des joins avec les tables : comptes, banques, catégories, sous-catégories, tiers.
     * Filtre automatiquement par l'utilisateur en session.
     * Résultats triés par date décroissante (plus récentes d'abord).
     * 
     * @return array Liste complète des transactions enrichies
     * 
     * @example
     * $transactions = Transaction::getAllWithDetails();
     * foreach ($transactions as $t) {
     *     echo "{$t['date_transaction']} - {$t['libelle']} : {$t['montant']}€ ({$t['compte_nom']})\n";
     * }
     */
    public static function getAllWithDetails(): array
    {
        $userId = $_SESSION['user']['id'] ?? 0;
        
        $sql = "SELECT t.*, 
                c.nom as compte_nom,
                cd.nom as compte_destination_nom,
                b.nom as banque_nom,
                cat.nom as categorie_nom,
                cat.couleur as categorie_couleur,
                cat.icone as categorie_icone,
                sous_cat.nom as sous_categorie_nom,
                tier.nom as tiers_nom,
                tier.type as tiers_type
                FROM " . static::$table . " t
                LEFT JOIN comptes c ON t.compte_id = c.id
                LEFT JOIN comptes cd ON t.compte_destination_id = cd.id
                LEFT JOIN banques b ON c.banque_id = b.id
                LEFT JOIN categories cat ON t.categorie_id = cat.id
                LEFT JOIN categories sous_cat ON t.sous_categorie_id = sous_cat.id
                LEFT JOIN tiers tier ON t.tiers_id = tier.id
                WHERE t.user_id = ?
                ORDER BY t.date_transaction DESC, t.created_at DESC";
        
        return Database::select($sql, [$userId]);
    }
    
    /**
     * Récupère les transactions d'un compte spécifique avec les détails
     * 
     * Similaire à getAllWithDetails() mais filtré sur un compte particulier.
     * Possibilité de limiter le nombre de résultats (utile pour affichages paginés).
     * 
     * @param int $compteId ID du compte bancaire
     * @param int|null $limit Nombre maximum de transactions à retourner (null = toutes)
     * @return array Liste des transactions du compte enrichies
     * 
     * @example
     * // Récupérer les 50 dernières transactions du compte courant
     * $transactions = Transaction::getByCompte($compteCourantId, 50);
     * 
     * // Récupérer toutes les transactions
     * $all = Transaction::getByCompte($compteId);
     */
    public static function getByCompte(int $compteId, ?int $limit = null): array
    {
        $userId = $_SESSION['user']['id'] ?? 0;
        
        $sql = "SELECT t.*, 
                c.nom as compte_nom,
                cd.nom as compte_destination_nom,
                b.nom as banque_nom,
                cat.nom as categorie_nom,
                cat.couleur as categorie_couleur,
                cat.icone as categorie_icone,
                sous_cat.nom as sous_categorie_nom,
                tier.nom as tiers_nom,
                tier.type as tiers_type,
                GROUP_CONCAT(DISTINCT CONCAT(tags.id, ':', tags.name, ':', tags.color) SEPARATOR '|') as tags_data
                FROM " . static::$table . " t
                LEFT JOIN comptes c ON t.compte_id = c.id
                LEFT JOIN comptes cd ON t.compte_destination_id = cd.id
                LEFT JOIN banques b ON c.banque_id = b.id
                LEFT JOIN categories cat ON t.categorie_id = cat.id
                LEFT JOIN categories sous_cat ON t.sous_categorie_id = sous_cat.id
                LEFT JOIN tiers tier ON t.tiers_id = tier.id
                LEFT JOIN transaction_tags tt ON t.id = tt.transaction_id
                LEFT JOIN tags ON tt.tag_id = tags.id
                WHERE t.user_id = ? AND t.compte_id = ?
                GROUP BY t.id
                ORDER BY t.date_transaction DESC, t.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT " . intval($limit);
        }
        
        $transactions = Database::select($sql, [$userId, $compteId]);
        
        // Parser les tags pour chaque transaction
        foreach ($transactions as &$transaction) {
            $transaction['tags'] = [];
            if (!empty($transaction['tags_data'])) {
                $tagsArray = explode('|', $transaction['tags_data']);
                foreach ($tagsArray as $tagData) {
                    $parts = explode(':', $tagData);
                    if (count($parts) === 3) {
                        $transaction['tags'][] = [
                            'id' => (int)$parts[0],
                            'name' => $parts[1],
                            'color' => $parts[2]
                        ];
                    }
                }
            }
            unset($transaction['tags_data']);
        }
        
        return $transactions;
    }
    
    /**
     * Récupère une transaction par son ID
     * 
     * @param int $id ID de la transaction
     * @return array|null La transaction ou null si non trouvée
     */
    public static function getById(int $id): ?array
    {
        $sql = "SELECT * FROM transactions WHERE id = ?";
        return Database::selectOne($sql, [$id]);
    }
    
    /**
     * Récupère toutes les transactions récurrentes actives
     * 
     * Retourne uniquement les récurrences où :
     * - est_recurrente = 1
     * - recurrence_active = 1 (non pausées)
     * 
     * Triées par date de prochaine exécution (les plus proches d'abord).
     * 
     * @param int|null $compteId ID du compte (optionnel, null = tous les comptes de l'utilisateur)
     * @return array Liste des transactions récurrentes actives
     * 
     * @example
     * // Toutes les récurrences de l'utilisateur
     * $recurrences = Transaction::getRecurrentesActives();
     * 
     * // Récurrences d'un compte spécifique  
     * $recurrences = Transaction::getRecurrentesActives(5);
     * 
     * foreach ($recurrences as $rec) {
     *     echo "Prochaine exécution : {$rec['prochaine_execution']} - {$rec['libelle']}\n";
     * }
     */
    public static function getRecurrentesActives(?int $compteId = null): array
    {
        $userId = $_SESSION['user']['id'] ?? 0;
        
        $sql = "SELECT t.*, 
                c.nom as compte_nom,
                b.nom as banque_nom
                FROM " . static::$table . " t
                LEFT JOIN comptes c ON t.compte_id = c.id
                LEFT JOIN banques b ON c.banque_id = b.id
                WHERE t.user_id = ? 
                AND t.est_recurrente = 1 
                AND t.recurrence_active = 1";
        
        $params = [$userId];
        
        if ($compteId !== null) {
            $sql .= " AND t.compte_id = ?";
            $params[] = $compteId;
        }
        
        $sql .= " ORDER BY t.prochaine_execution ASC";
        
        return Database::select($sql, $params);
    }
    
    /**
     * Récupère les récurrences dont la prochaine exécution est arrivée
     * 
     * Filtre les transactions récurrentes qui doivent être exécutées :
     * - prochaine_execution <= $dateMax
     * - Pas encore expirées (date_fin non atteinte)
     * - Nombre d'exécutions max non atteint
     * 
     * Utilisé par le système d'automatisation des récurrences.
     * 
     * @param string|null $dateMax Date limite d'exécution (défaut: aujourd'hui)
     * @return array Liste des récurrences à exécuter
     * 
     * @example
     * // Exécuter les récurrences jusqu'à aujourd'hui
     * $aExecuter = Transaction::getRecurrencesAExecuter();
     * 
     * // Exécuter les récurrences jusqu'à fin du mois
     * $aExecuter = Transaction::getRecurrencesAExecuter('2024-01-31');
     */
    public static function getRecurrencesAExecuter(?string $dateMax = null): array
    {
        $userId = $_SESSION['user']['id'] ?? 0;
        $dateMax = $dateMax ?? date('Y-m-d');
        
        $sql = "SELECT t.*, 
                c.nom as compte_nom
                FROM " . static::$table . " t
                LEFT JOIN comptes c ON t.compte_id = c.id
                WHERE t.user_id = ? 
                AND t.est_recurrente = 1 
                AND t.recurrence_active = 1
                AND t.prochaine_execution <= ?
                AND (t.date_fin IS NULL OR t.prochaine_execution <= t.date_fin)
                AND (t.nb_executions_max IS NULL OR t.nb_executions < t.nb_executions_max)
                ORDER BY t.prochaine_execution ASC";
        
        return Database::select($sql, [$userId, $dateMax]);
    }
    
    /**
     * Filtre les transactions sur une période donnée
     * 
     * Permet de récupérer toutes les transactions entre deux dates.
     * Optionnellement filtrable par compte.
     * 
     * @param string $dateDebut Date de début (YYYY-MM-DD)
     * @param string $dateFin Date de fin (YYYY-MM-DD)
     * @param int|null $compteId ID du compte (null = tous les comptes)
     * @return array Liste des transactions de la période
     * 
     * @example
     * // Transactions de janvier 2024
     * $transactions = Transaction::getByPeriode('2024-01-01', '2024-01-31');
     * 
     * // Transactions du compte courant en janvier
     * $transactions = Transaction::getByPeriode('2024-01-01', '2024-01-31', $compteCourantId);
     */
    public static function getByPeriode(string $dateDebut, string $dateFin, ?int $compteId = null): array
    {
        $userId = $_SESSION['user']['id'] ?? 0;
        
        $sql = "SELECT t.*, 
                c.nom as compte_nom,
                cd.nom as compte_destination_nom,
                b.nom as banque_nom
                FROM " . static::$table . " t
                LEFT JOIN comptes c ON t.compte_id = c.id
                LEFT JOIN comptes cd ON t.compte_destination_id = cd.id
                LEFT JOIN banques b ON c.banque_id = b.id
                WHERE t.user_id = ? 
                AND t.date_transaction BETWEEN ? AND ?";
        
        $params = [$userId, $dateDebut, $dateFin];
        
        if ($compteId) {
            $sql .= " AND t.compte_id = ?";
            $params[] = $compteId;
        }
        
        $sql .= " ORDER BY t.date_transaction DESC, t.created_at DESC";
        
        return Database::select($sql, $params);
    }
    
    /**
     * Calcule les totaux par type d'opération sur une période
     * 
     * Retourne les statistiques agrégées (somme et nombre) par type d'opération.
     * Utile pour générer des rapports financiers.
     * Seules les transactions validées sont comptabilisées.
     * 
     * @param string $dateDebut Date de début (YYYY-MM-DD)
     * @param string $dateFin Date de fin (YYYY-MM-DD)
     * @param int|null $compteId ID du compte (null = tous les comptes)
     * @return array Tableau avec totaux par type [type_operation, total, nombre]
     * 
     * @example
     * $stats = Transaction::getSommeParType('2024-01-01', '2024-01-31');
     * // Résultat: [
     * //   ['type_operation' => 'credit', 'total' => 2800.00, 'nombre' => 1],
     * //   ['type_operation' => 'debit', 'total' => -1543.50, 'nombre' => 23]
     * // ]
     */
    public static function getSommeParType(string $dateDebut, string $dateFin, ?int $compteId = null): array
    {
        $userId = $_SESSION['user']['id'] ?? 0;
        
        $sql = "SELECT 
                type_operation,
                SUM(montant) as total,
                COUNT(*) as nombre
                FROM " . static::$table . "
                WHERE user_id = ? 
                AND date_transaction BETWEEN ? AND ?
                AND validee = 1";
        
        $params = [$userId, $dateDebut, $dateFin];
        
        if ($compteId) {
            $sql .= " AND compte_id = ?";
            $params[] = $compteId;
        }
        
        $sql .= " GROUP BY type_operation";
        
        return Database::select($sql, $params);
    }
    
    /**
     * Recherche des transactions par mots-clés
     * 
     * Recherche dans les champs : libellé, description, bénéficiaire et référence banque.
     * Recherche insensible à la casse avec correspondances partielles (LIKE %query%).
     * Limitée aux 100 résultats les plus récents pour éviter les listes trop longues.
     * 
     * @param string $query Terme de recherche
     * @return array Liste des transactions correspondantes (max 100)
     * 
     * @example
     * $resultats = Transaction::search('loyer');
     * foreach ($resultats as $t) {
     *     echo "{$t['date_transaction']} - {$t['libelle']} : {$t['montant']}€\n";
     * }
     */
    public static function search(string $query): array
    {
        $userId = $_SESSION['user']['id'] ?? 0;
        $searchTerm = "%{$query}%";
        
        $sql = "SELECT t.*, 
                c.nom as compte_nom,
                b.nom as banque_nom
                FROM " . static::$table . " t
                LEFT JOIN comptes c ON t.compte_id = c.id
                LEFT JOIN banques b ON c.banque_id = b.id
                WHERE t.user_id = ? 
                AND (t.libelle LIKE ? 
                     OR t.description LIKE ? 
                     OR t.beneficiaire LIKE ?
                     OR t.reference_banque LIKE ?)
                ORDER BY t.date_transaction DESC
                LIMIT 100";
        
        return Database::select($sql, [$userId, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }
    
    /**
     * Calcule la prochaine date d'exécution d'une récurrence
     * 
     * Applique l'intervalle et la fréquence pour calculer la date suivante.
     * Gère la tolérance weekend (décalage au vendredi précédent ou lundi suivant).
     * 
     * Fréquences supportées :
     * - quotidien : +N jours
     * - hebdomadaire : +N semaines
     * - mensuel : +N mois
     * - trimestriel : +N*3 mois
     * - semestriel : +N*6 mois
     * - annuel : +N années
     * 
     * @param array $transaction Données de la transaction récurrente
     *                           - string $transaction['prochaine_execution'] Date actuelle
     *                           - string $transaction['frequence'] Fréquence de récurrence
     *                           - int $transaction['intervalle'] Multiplicateur
     *                           - string $transaction['tolerance_weekend'] Gestion des weekends
     * @return string|null Date de prochaine exécution (YYYY-MM-DD) ou null si invalide
     * 
     * @example
     * $recurrence = [
     *     'prochaine_execution' => '2024-01-15',
     *     'frequence' => 'mensuel',
     *     'intervalle' => 1,
     *     'tolerance_weekend' => 'jour_ouvre_suivant'
     * ];
     * $prochaineDate = Transaction::calculerProchaineExecution($recurrence);
     * // Résultat: '2024-02-15' (ou '2024-02-16' si 15/02 est un samedi)
     */
    public static function calculerProchaineExecution(array $transaction): ?string
    {
        if (!$transaction['est_recurrente'] || !$transaction['prochaine_execution']) {
            return null;
        }
        
        $date = new \DateTime($transaction['prochaine_execution']);
        $intervalle = $transaction['intervalle'] ?? 1;
        
        switch ($transaction['frequence']) {
            case 'quotidien':
                $date->modify("+{$intervalle} days");
                break;
            case 'hebdomadaire':
                $date->modify("+{$intervalle} weeks");
                break;
            case 'mensuel':
                $date->modify("+{$intervalle} months");
                break;
            case 'trimestriel':
                $date->modify("+" . ($intervalle * 3) . " months");
                break;
            case 'semestriel':
                $date->modify("+" . ($intervalle * 6) . " months");
                break;
            case 'annuel':
                $date->modify("+{$intervalle} years");
                break;
            default:
                return null;
        }
        
        // Gérer la tolérance weekend
        if ($transaction['tolerance_weekend'] !== 'aucune') {
            $jourSemaine = (int)$date->format('N'); // 1 = Lundi, 7 = Dimanche
            
            if ($jourSemaine === 6) { // Samedi
                if ($transaction['tolerance_weekend'] === 'jour_ouvre_suivant') {
                    $date->modify('+2 days'); // Lundi
                } else {
                    $date->modify('-1 day'); // Vendredi
                }
            } elseif ($jourSemaine === 7) { // Dimanche
                if ($transaction['tolerance_weekend'] === 'jour_ouvre_suivant') {
                    $date->modify('+1 day'); // Lundi
                } else {
                    $date->modify('-2 days'); // Vendredi
                }
            }
        }
        
        return $date->format('Y-m-d');
    }
    
    /**
     * Exécute une récurrence en créant une nouvelle transaction
     * 
     * Processus complet :
     * 1. Vérifie que c'est bien une récurrence
     * 2. Crée une nouvelle transaction simple basée sur le modèle
     * 3. Met à jour la récurrence (dernière_execution, prochaine_execution, nb_executions)
     * 4. Désactive automatiquement si limite atteinte ou date_fin dépassée
     * 
     * La nouvelle transaction créée n'est PAS récurrente (est_recurrente = 0).
     * 
     * @param int $recurrenceId ID de la transaction récurrente à exécuter
     * @return int|null ID de la nouvelle transaction créée, ou null si échec
     * 
     * @example
     * $recurrences = Transaction::getRecurrencesAExecuter();
     * foreach ($recurrences as $rec) {
     *     $newId = Transaction::executerRecurrence($rec['id']);
     *     if ($newId) {
     *         echo "Transaction créée : ID {$newId}\n";
     *     }
     * }
     * 
     * @see calculerProchaineExecution()
     * @see getRecurrencesAExecuter()
     */
    public static function executerRecurrence(int $recurrenceId): ?int
    {
        $recurrence = static::find($recurrenceId);
        
        if (!$recurrence || !$recurrence['est_recurrente']) {
            return null;
        }
        
        // Créer la nouvelle transaction
        $nouvelleTransaction = [
            'user_id' => $recurrence['user_id'],
            'compte_id' => $recurrence['compte_id'],
            'compte_destination_id' => $recurrence['compte_destination_id'],
            'date_transaction' => $recurrence['prochaine_execution'],
            'date_valeur' => $recurrence['prochaine_execution'],
            'montant' => $recurrence['montant'],
            'libelle' => $recurrence['libelle'],
            'description' => $recurrence['description'],
            'categorie_id' => $recurrence['categorie_id'],
            'sous_categorie_id' => $recurrence['sous_categorie_id'],
            'tiers_id' => $recurrence['tiers_id'], // Copier le tiers
            'type_operation' => $recurrence['type_operation'],
            'moyen_paiement' => $recurrence['moyen_paiement'],
            'beneficiaire' => $recurrence['beneficiaire'],
            'validee' => $recurrence['auto_validation'],
            'est_recurrente' => 0 // La transaction créée n'est pas récurrente
        ];
        
        $newId = static::create($nouvelleTransaction);
        
        if ($newId) {
            // Mettre à jour la récurrence
            $prochaineExec = static::calculerProchaineExecution($recurrence);
            $nbExec = ($recurrence['nb_executions'] ?? 0) + 1;
            
            $updates = [
                'derniere_execution' => $recurrence['prochaine_execution'],
                'prochaine_execution' => $prochaineExec,
                'nb_executions' => $nbExec
            ];
            
            // Désactiver si limite atteinte ou date de fin dépassée
            if ($recurrence['nb_executions_max'] && $nbExec >= $recurrence['nb_executions_max']) {
                $updates['recurrence_active'] = 0;
            }
            
            // Validation stricte date_fin pour éviter désactivation avec '0000-00-00'
            if (!empty($recurrence['date_fin']) 
                && $recurrence['date_fin'] !== '0000-00-00' 
                && $prochaineExec > $recurrence['date_fin']) {
                $updates['recurrence_active'] = 0;
            }
            
            static::update($recurrenceId, $updates);
        }
        
        return $newId;
    }
    
    /**
     * Récupère les transactions d'un compte pour un mois spécifique
     * 
     * Utilisé principalement pour la génération de relevés bancaires mensuels.
     * Retourne les transactions triées par date croissante (ordre chronologique).
     * 
     * @param int $compteId ID du compte
     * @param int $annee Année du relevé (ex: 2024)
     * @param int $mois Mois du relevé (1-12)
     * @return array Liste des transactions du mois avec détails catégories et tiers
     * 
     * @example
     * // Relevé de janvier 2024
     * $transactions = Transaction::getByCompteAndPeriod($compteId, 2024, 1);
     * echo "Relevé de janvier 2024 : " . count($transactions) . " opérations\n";
     * foreach ($transactions as $t) {
     *     echo "{$t['date_transaction']} - {$t['libelle']} : {$t['montant']}€\n";
     * }
     */
    public static function getByCompteAndPeriod(int $compteId, int $annee, int $mois): array
    {
        $userId = $_SESSION['user']['id'] ?? 0;
        
        $dateDebut = sprintf('%04d-%02d-01', $annee, $mois);
        $dateFin = date('Y-m-t', strtotime($dateDebut));
        
        $sql = "SELECT t.*, 
                c.nom as compte_nom,
                cat.nom as categorie_nom,
                sous_cat.nom as sous_categorie_nom,
                tier.nom as tiers
                FROM " . static::$table . " t
                LEFT JOIN comptes c ON t.compte_id = c.id
                LEFT JOIN categories cat ON t.categorie_id = cat.id
                LEFT JOIN categories sous_cat ON t.sous_categorie_id = sous_cat.id
                LEFT JOIN tiers tier ON t.tiers_id = tier.id
                WHERE t.user_id = ? 
                AND t.compte_id = ?
                AND t.date_transaction BETWEEN ? AND ?
                ORDER BY t.date_transaction ASC, t.created_at ASC";
        
        return Database::select($sql, [$userId, $compteId, $dateDebut, $dateFin]);
    }
    
    /**
     * Attacher des tags à une transaction
     * 
     * @param int $transactionId
     * @param array $tagIds Tableau d'IDs de tags
     * @return bool
     */
    public function attachTags(int $transactionId, array $tagIds): bool
    {
        if (empty($tagIds)) {
            return true;
        }

        $db = Database::getConnection();
        
        try {
            $db->beginTransaction();

            // Préparer l'insertion multiple
            $placeholders = implode(',', array_fill(0, count($tagIds), '(?, ?, NOW())'));
            $sql = "INSERT IGNORE INTO transaction_tags (transaction_id, tag_id, created_at) 
                    VALUES {$placeholders}";

            $params = [];
            foreach ($tagIds as $tagId) {
                $params[] = $transactionId;
                $params[] = (int) $tagId;
            }

            $stmt = $db->prepare($sql);
            $stmt->execute($params);

            $db->commit();
            return true;
        } catch (\Exception $e) {
            $db->rollBack();
            return false;
        }
    }

    /**
     * Détacher des tags d'une transaction
     * 
     * @param int $transactionId
     * @param array $tagIds Tableau d'IDs de tags à détacher (vide = tous)
     * @return bool
     */
    public function detachTags(int $transactionId, array $tagIds = []): bool
    {
        $db = Database::getConnection();
        
        if (empty($tagIds)) {
            // Détacher tous les tags
            $sql = "DELETE FROM transaction_tags WHERE transaction_id = ?";
            $params = [$transactionId];
        } else {
            // Détacher des tags spécifiques
            $placeholders = implode(',', array_fill(0, count($tagIds), '?'));
            $sql = "DELETE FROM transaction_tags 
                    WHERE transaction_id = ? AND tag_id IN ({$placeholders})";
            $params = array_merge([$transactionId], $tagIds);
        }

        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Synchroniser les tags d'une transaction (remplace tous les tags)
     * 
     * @param int $transactionId
     * @param array $tagIds Tableau d'IDs de tags
     * @return bool
     */
    public function syncTags(int $transactionId, array $tagIds): bool
    {
        $db = Database::getConnection();
        
        try {
            $db->beginTransaction();

            // 1. Supprimer tous les tags existants
            $sqlDelete = "DELETE FROM transaction_tags WHERE transaction_id = ?";
            $stmtDelete = $db->prepare($sqlDelete);
            $stmtDelete->execute([$transactionId]);

            // 2. Ajouter les nouveaux tags
            if (!empty($tagIds)) {
                $placeholders = str_repeat('(?, ?),', count($tagIds));
                $placeholders = rtrim($placeholders, ',');
                
                $sqlInsert = "INSERT IGNORE INTO transaction_tags (transaction_id, tag_id) VALUES $placeholders";
                $stmtInsert = $db->prepare($sqlInsert);
                
                $params = [];
                foreach ($tagIds as $tagId) {
                    $params[] = $transactionId;
                    $params[] = (int)$tagId;
                }
                
                $stmtInsert->execute($params);
            }

            $db->commit();
            return true;
        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return false;
        }
    }

    /**
     * Récupérer les IDs des tags d'une transaction
     * 
     * @param int $transactionId
     * @return array Tableau d'IDs
     */
    public function getTagIds(int $transactionId): array
    {
        $sql = "SELECT tag_id FROM transaction_tags WHERE transaction_id = ?";
        
        $db = Database::getConnection();
        $stmt = $db->prepare($sql);
        $stmt->execute([$transactionId]);
        
        return array_column($stmt->fetchAll(\PDO::FETCH_ASSOC), 'tag_id');
    }

    /**
     * Vérifier si une transaction a un tag spécifique
     * 
     * @param int $transactionId
     * @param int $tagId
     * @return bool
     */
    public function hasTag(int $transactionId, int $tagId): bool
    {
        $sql = "SELECT COUNT(*) FROM transaction_tags 
                WHERE transaction_id = ? AND tag_id = ?";
        
        $db = Database::getConnection();
        $stmt = $db->prepare($sql);
        $stmt->execute([$transactionId, $tagId]);
        
        return $stmt->fetchColumn() > 0;
    }
}

