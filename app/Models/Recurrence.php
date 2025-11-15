<?php

namespace MonBudget\Models;

use MonBudget\Core\Database;

/**
 * Class Recurrence
 * 
 * Modèle de gestion des récurrences bancaires (modèles générateurs de transactions).
 * 
 * Une récurrence est un modèle qui génère automatiquement des transactions à intervalle régulier.
 * Exemples : loyers, salaires, abonnements, prélèvements mensuels, etc.
 * 
 * Les occurrences générées sont stockées dans la table `transactions` avec un `recurrence_id` pointant vers ce modèle.
 * 
 * @package MonBudget\Models
 * @extends BaseModel
 * 
 * @property int $id Identifiant unique de la récurrence
 * @property int $user_id ID de l'utilisateur propriétaire
 * @property int $compte_id ID du compte bancaire concerné
 * @property int|null $compte_destination_id ID du compte destinataire (pour virements récurrents)
 * @property string $libelle Libellé de l'opération récurrente
 * @property string|null $description Description détaillée
 * @property float $montant Montant de l'opération
 * @property string $type_operation Type d'opération (credit, debit, virement)
 * @property string|null $moyen_paiement Moyen de paiement (virement, prelevement, carte, etc.)
 * @property string|null $beneficiaire Nom du bénéficiaire
 * @property int|null $categorie_id ID de la catégorie
 * @property int|null $sous_categorie_id ID de la sous-catégorie
 * @property int|null $tiers_id ID du tiers associé
 * @property string $frequence Fréquence de récurrence (quotidien, hebdomadaire, mensuel, etc.)
 * @property int $intervalle Intervalle entre exécutions (ex: 2 pour "tous les 2 mois")
 * @property int|null $jour_execution Jour du mois d'exécution (1-31)
 * @property int|null $jour_semaine Jour de la semaine (1=Lundi, 7=Dimanche)
 * @property string $date_debut Date de début de récurrence
 * @property string|null $date_fin Date de fin de récurrence (optionnel)
 * @property string $prochaine_execution Date de la prochaine exécution programmée
 * @property string|null $derniere_execution Date de la dernière exécution
 * @property int $nb_executions Nombre d'exécutions effectuées
 * @property int|null $nb_executions_max Nombre maximum d'exécutions (optionnel)
 * @property bool $auto_validation Valider automatiquement les transactions générées
 * @property string $tolerance_weekend Gestion des weekends (aucune, jour_ouvre_suivant, jour_ouvre_precedent)
 * @property bool $recurrence_active Récurrence active ou désactivée
 * @property string $created_at Date de création
 * @property string $updated_at Date de dernière modification
 */
class Recurrence extends BaseModel
{
    /** @var string Nom de la table */
    protected static string $table = 'recurrences';
    
    /** @var string Clé primaire */
    protected static string $primaryKey = 'id';
    
    /**
     * Récupère toutes les récurrences actives de l'utilisateur
     * 
     * Retourne les récurrences actives avec détails des comptes et banques.
     * Peut être filtré par compte spécifique.
     * Triées par date de prochaine exécution.
     * 
     * @param int|null $compteId Optionnel : filtrer par compte
     * @return array Liste des récurrences actives avec détails
     * 
     * @example
     * // Toutes les récurrences de l'utilisateur
     * $recurrences = Recurrence::getActives();
     * 
     * // Récurrences d'un compte spécifique  
     * $recurrences = Recurrence::getActives(5);
     * 
     * foreach ($recurrences as $rec) {
     *     echo "Prochaine exécution : {$rec['prochaine_execution']} - {$rec['libelle']}\n";
     * }
     */
    public static function getActives(?int $compteId = null): array
    {
        $userId = $_SESSION['user']['id'] ?? 0;
        
        $sql = "SELECT r.*, 
                c.nom as compte_nom,
                b.nom as banque_nom,
                cat.nom as categorie_nom,
                scat.nom as sous_categorie_nom,
                t.nom as tiers_nom
                FROM " . static::$table . " r
                LEFT JOIN comptes c ON r.compte_id = c.id
                LEFT JOIN banques b ON c.banque_id = b.id
                LEFT JOIN categories cat ON r.categorie_id = cat.id
                LEFT JOIN categories scat ON r.sous_categorie_id = scat.id
                LEFT JOIN tiers t ON r.tiers_id = t.id
                WHERE r.user_id = ? 
                AND r.recurrence_active = 1";
        
        $params = [$userId];
        
        if ($compteId !== null) {
            $sql .= " AND r.compte_id = ?";
            $params[] = $compteId;
        }
        
        $sql .= " ORDER BY r.prochaine_execution ASC";
        
        return Database::select($sql, $params);
    }
    
    /**
     * Récupère les récurrences dont la prochaine exécution est arrivée
     * 
     * Filtre les récurrences qui doivent être exécutées :
     * - prochaine_execution <= $dateMax
     * - Actives (recurrence_active = 1)
     * - Pas encore expirées (date_fin non atteinte)
     * - Nombre d'exécutions max non atteint
     * 
     * Utilisé par le système d'automatisation des récurrences.
     * 
     * @param string|null $dateMax Date limite d'exécution (défaut: aujourd'hui)
     * @return array Liste des récurrences à exécuter
     * 
     * @example
     * // Récurrences à exécuter aujourd'hui
     * $recurrences = Recurrence::getAExecuter();
     * 
     * // Récurrences à exécuter jusqu'au 31/12
     * $recurrences = Recurrence::getAExecuter('2024-12-31');
     * 
     * foreach ($recurrences as $rec) {
     *     Recurrence::executerRecurrence($rec['id']);
     * }
     */
    public static function getAExecuter(?string $dateMax = null): array
    {
        $userId = $_SESSION['user']['id'] ?? 0;
        $dateMax = $dateMax ?? date('Y-m-d');
        
        $sql = "SELECT r.*, 
                c.nom as compte_nom
                FROM " . static::$table . " r
                LEFT JOIN comptes c ON r.compte_id = c.id
                WHERE r.user_id = ? 
                AND r.recurrence_active = 1
                AND r.prochaine_execution <= ?
                AND (r.date_fin IS NULL OR r.prochaine_execution <= r.date_fin)
                AND (r.nb_executions_max IS NULL OR r.nb_executions < r.nb_executions_max)
                ORDER BY r.prochaine_execution ASC";
        
        return Database::select($sql, [$userId, $dateMax]);
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
     * @param array $recurrence Données de la récurrence
     *                          - string $recurrence['prochaine_execution'] Date actuelle
     *                          - string $recurrence['frequence'] Fréquence de récurrence
     *                          - int $recurrence['intervalle'] Multiplicateur
     *                          - string $recurrence['tolerance_weekend'] Gestion des weekends
     * @return string|null Date de prochaine exécution (YYYY-MM-DD) ou null si invalide
     * 
     * @example
     * $recurrence = [
     *     'prochaine_execution' => '2024-01-15',
     *     'frequence' => 'mensuel',
     *     'intervalle' => 1,
     *     'tolerance_weekend' => 'jour_ouvre_suivant'
     * ];
     * $prochaineDate = Recurrence::calculerProchaineExecution($recurrence);
     * // Résultat: '2024-02-15' (ou '2024-02-16' si 15/02 est un samedi)
     */
    public static function calculerProchaineExecution(array $recurrence): ?string
    {
        if (!$recurrence['prochaine_execution']) {
            return null;
        }
        
        $date = new \DateTime($recurrence['prochaine_execution']);
        $intervalle = $recurrence['intervalle'] ?? 1;
        
        switch ($recurrence['frequence']) {
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
        if (($recurrence['tolerance_weekend'] ?? 'aucune') !== 'aucune') {
            $jourSemaine = (int)$date->format('N'); // 1 = Lundi, 7 = Dimanche
            
            if ($jourSemaine === 6) { // Samedi
                if ($recurrence['tolerance_weekend'] === 'jour_ouvre_suivant') {
                    $date->modify('+2 days'); // Lundi
                } else {
                    $date->modify('-1 day'); // Vendredi
                }
            } elseif ($jourSemaine === 7) { // Dimanche
                if ($recurrence['tolerance_weekend'] === 'jour_ouvre_suivant') {
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
     * 1. Vérifie que la récurrence existe et est active
     * 2. Crée une nouvelle transaction dans la table transactions
     * 3. Lie la transaction à la récurrence via recurrence_id
     * 4. Met à jour la récurrence (derniere_execution, prochaine_execution, nb_executions)
     * 5. Désactive automatiquement si limite atteinte ou date_fin dépassée
     * 
     * @param int $recurrenceId ID de la récurrence à exécuter
     * @return int|null ID de la nouvelle transaction créée, ou null si échec
     * 
     * @example
     * $recurrences = Recurrence::getAExecuter();
     * foreach ($recurrences as $rec) {
     *     $newId = Recurrence::executerRecurrence($rec['id']);
     *     if ($newId) {
     *         echo "Transaction créée : ID {$newId}\n";
     *     }
     * }
     * 
     * @see calculerProchaineExecution()
     * @see getAExecuter()
     */
    public static function executerRecurrence(int $recurrenceId): ?int
    {
        $recurrence = static::find($recurrenceId);
        
        if (!$recurrence || !$recurrence['recurrence_active']) {
            return null;
        }
        
        // Créer la nouvelle transaction dans table transactions
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
            'tiers_id' => $recurrence['tiers_id'],
            'type_operation' => $recurrence['type_operation'],
            'moyen_paiement' => $recurrence['moyen_paiement'],
            'beneficiaire' => $recurrence['beneficiaire'],
            'validee' => $recurrence['auto_validation'],
            'est_recurrente' => 0, // L'occurrence n'est pas récurrente
            'recurrence_id' => $recurrenceId // ✅ LIEN VERS RÉCURRENCE
        ];
        
        $newId = Transaction::create($nouvelleTransaction);
        
        if ($newId) {
            // Mettre à jour la récurrence
            $prochaineExec = static::calculerProchaineExecution($recurrence);
            $nbExec = ($recurrence['nb_executions'] ?? 0) + 1;
            
            $updates = [
                'derniere_execution' => $recurrence['prochaine_execution'],
                'prochaine_execution' => $prochaineExec,
                'nb_executions' => $nbExec
            ];
            
            // Désactiver si limite atteinte
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
            
            // Recalculer le solde du compte
            Compte::recalculerSolde($recurrence['compte_id']);
        }
        
        return $newId;
    }
    
    /**
     * Supprime une récurrence avec toutes ses occurrences générées
     * 
     * ✅ FIX BUG 7 : Utilise recurrence_id pour identification précise
     * 
     * Supprime :
     * 1. TOUTES les transactions liées via recurrence_id (occurrences)
     * 2. Le modèle de récurrence
     * 
     * @param int $recurrenceId ID du modèle de récurrence
     * @return array Résultat ['modele' => int, 'occurrences' => int]
     * 
     * @example
     * $result = Recurrence::deleteWithOccurrences(5);
     * echo "Récurrence supprimée avec {$result['occurrences']} occurrence(s)\n";
     */
    public static function deleteWithOccurrences(int $recurrenceId): array
    {
        $recurrence = static::find($recurrenceId);
        
        if (!$recurrence) {
            return ['modele' => 0, 'occurrences' => 0];
        }
        
        // ✅ Suppression PRÉCISE via recurrence_id (plus d'heuristique)
        $sql = "DELETE FROM transactions WHERE recurrence_id = ?";
        $occurrences = Database::delete($sql, [$recurrenceId]);
        
        // Supprimer le modèle
        $modele = static::delete($recurrenceId);
        
        // Recalculer le solde du compte après suppression
        if ($occurrences > 0) {
            Compte::recalculerSolde($recurrence['compte_id']);
        }
        
        return [
            'modele' => $modele,
            'occurrences' => $occurrences
        ];
    }
    
    /**
     * Compte le nombre d'occurrences générées depuis une récurrence
     * 
     * ✅ Utilise recurrence_id pour comptage précis
     * 
     * @param int $recurrenceId ID du modèle de récurrence
     * @return int Nombre d'occurrences
     * 
     * @example
     * $nbOccurrences = Recurrence::countOccurrences(5);
     * echo "Cette récurrence a généré {$nbOccurrences} transaction(s)\n";
     */
    public static function countOccurrences(int $recurrenceId): int
    {
        $sql = "SELECT COUNT(*) as count 
                FROM transactions 
                WHERE recurrence_id = ?";
        
        $result = Database::selectOne($sql, [$recurrenceId]);
        
        return (int) ($result['count'] ?? 0);
    }
    
    /**
     * Supprime uniquement le modèle de récurrence (garde les occurrences)
     * 
     * Les transactions déjà générées sont conservées et deviennent orphelines (recurrence_id = NULL via ON DELETE SET NULL).
     * 
     * @param int $recurrenceId ID du modèle de récurrence
     * @return int Nombre de lignes supprimées (1 si succès, 0 si échec)
     * 
     * @example
     * $result = Recurrence::deleteModeleOnly(5);
     * echo $result ? "Modèle supprimé (occurrences conservées)" : "Échec";
     */
    public static function deleteModeleOnly(int $recurrenceId): int
    {
        // La FK ON DELETE SET NULL mettra automatiquement recurrence_id = NULL sur les occurrences
        return static::delete($recurrenceId);
    }
}
