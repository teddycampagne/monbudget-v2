<?php

namespace MonBudget\Models;

use MonBudget\Core\Database;

/**
 * Class RegleAutomatisation
 * 
 * Modèle de gestion des règles d'automatisation pour les transactions.
 * 
 * Permet de définir des règles qui détectent automatiquement :
 * - La catégorie et sous-catégorie
 * - Le tiers (fournisseur, organisme, etc.)
 * - Le moyen de paiement
 * 
 * Les règles sont basées sur le libellé de la transaction et utilisent 4 types de patterns :
 * - contient : Le libellé contient le pattern (ex: "CARREFOUR")
 * - commence_par : Le libellé commence par le pattern (ex: "VIR ")
 * - termine_par : Le libellé se termine par le pattern (ex: " SEPA")
 * - regex : Expression régulière (ex: "^CB\s+\d+")
 * 
 * Les règles sont appliquées par ordre de priorité (ASC).
 * Une règle plus prioritaire écrase les valeurs définies par les règles suivantes.
 * 
 * @package MonBudget\Models
 * @extends BaseModel
 * 
 * @property int $id Identifiant unique de la règle
 * @property int $user_id ID de l'utilisateur propriétaire
 * @property string $nom Nom descriptif de la règle
 * @property string $type_pattern Type (contient, commence_par, termine_par, regex)
 * @property string $pattern Pattern de détection (texte ou regex)
 * @property bool $case_sensitive Sensible à la casse (0 ou 1)
 * @property int|null $action_categorie ID de la catégorie à appliquer
 * @property int|null $action_sous_categorie ID de la sous-catégorie à appliquer
 * @property int|null $action_tiers ID du tiers à appliquer
 * @property string|null $action_moyen_paiement Moyen de paiement à appliquer (carte, virement, etc.)
 * @property int $priorite Ordre d'application (1 = plus prioritaire)
 * @property bool $actif Règle active (0 ou 1)
 * @property int $nb_applications Compteur d'applications de la règle
 * @property string|null $derniere_application Date de la dernière application
 */
class RegleAutomatisation extends BaseModel
{
    /** @var string Table de la base de données */
    protected static string $table = 'regles_automatisation';
    
    /**
     * Récupère toutes les règles actives d'un utilisateur triées par priorité
     * 
     * Retourne les règles avec les noms enrichis (catégorie, sous-catégorie, tiers).
     * Tri par priorité ASC puis par ID ASC.
     * 
     * @param int $userId ID de l'utilisateur
     * @return array Liste des règles actives avec catégorie_nom, sous_categorie_nom, tiers_nom
     * 
     * @example
     * $rules = RegleAutomatisation::getActiveRules($userId);
     * foreach ($rules as $rule) {
     *     echo "{$rule['nom']} → {$rule['categorie_nom']}";
     * }
     * 
     * @see applyRules()
     */
    public static function getActiveRules(int $userId): array
    {
        $sql = "SELECT ra.*, 
                       c1.nom as categorie_nom,
                       c2.nom as sous_categorie_nom,
                       t.nom as tiers_nom
                FROM " . static::$table . " ra
                LEFT JOIN categories c1 ON ra.action_categorie = c1.id
                LEFT JOIN categories c2 ON ra.action_sous_categorie = c2.id
                LEFT JOIN tiers t ON ra.action_tiers = t.id
                WHERE ra.user_id = ? AND ra.actif = 1
                ORDER BY ra.priorite ASC, ra.id ASC";
        
        return Database::select($sql, [$userId]);
    }
    
    /**
     * Applique les règles d'automatisation sur un libellé de transaction
     * 
     * Parcourt toutes les règles actives par ordre de priorité.
     * Pour chaque règle qui matche :
     * - Applique les actions (catégorie, sous-catégorie, tiers, moyen_paiement)
     * - Ne surcharge PAS les valeurs déjà définies par une règle plus prioritaire
     * - Incrémente le compteur d'applications
     * 
     * Retourne un tableau avec les valeurs automatiques détectées.
     * 
     * @param int $userId ID de l'utilisateur
     * @param string $libelle Libellé de la transaction à analyser
     * @return array [
     *     'categorie_id' => int|null,
     *     'sous_categorie_id' => int|null,
     *     'tiers_id' => int|null,
     *     'moyen_paiement' => string|null,
     *     'rules_applied' => array Noms des règles appliquées
     * ]
     * 
     * @example
     * $auto = RegleAutomatisation::applyRules($userId, 'CB CARREFOUR PARIS 15/12');
     * // Retourne:
     * // [
     * //   'categorie_id' => 5,           // Alimentation
     * //   'sous_categorie_id' => 12,     // Supermarché
     * //   'tiers_id' => 8,               // Carrefour
     * //   'moyen_paiement' => 'carte',
     * //   'rules_applied' => ['Carrefour → Alimentation', 'CB → Carte bancaire']
     * // ]
     * 
     * @see getActiveRules()
     * @see testRule()
     */
    public static function applyRules(int $userId, string $libelle): array
    {
        $rules = static::getActiveRules($userId);
        $result = [
            'categorie_id' => null,
            'sous_categorie_id' => null,
            'tiers_id' => null,
            'moyen_paiement' => null,
            'rules_applied' => []
        ];
        
        foreach ($rules as $rule) {
            $matched = false;
            
            // Appliquer le pattern selon le type
            switch ($rule['type_pattern']) {
                case 'contient':
                    $pattern = $rule['pattern'];
                    if (!$rule['case_sensitive']) {
                        $matched = stripos($libelle, $pattern) !== false;
                    } else {
                        $matched = strpos($libelle, $pattern) !== false;
                    }
                    break;
                    
                case 'commence_par':
                    $pattern = $rule['pattern'];
                    if (!$rule['case_sensitive']) {
                        $matched = stripos($libelle, $pattern) === 0;
                    } else {
                        $matched = strpos($libelle, $pattern) === 0;
                    }
                    break;
                    
                case 'termine_par':
                    $pattern = $rule['pattern'];
                    $len = strlen($pattern);
                    if (!$rule['case_sensitive']) {
                        $matched = strcasecmp(substr($libelle, -$len), $pattern) === 0;
                    } else {
                        $matched = substr($libelle, -$len) === $pattern;
                    }
                    break;
                    
                case 'regex':
                    $pattern = $rule['pattern'];
                    $flags = $rule['case_sensitive'] ? '' : 'i';
                    $matched = preg_match('/' . $pattern . '/' . $flags, $libelle);
                    break;
            }
            
            if ($matched) {
                // Appliquer les actions (ne pas écraser si déjà défini par une règle plus prioritaire)
                if ($rule['action_categorie'] && $result['categorie_id'] === null) {
                    $result['categorie_id'] = $rule['action_categorie'];
                }
                if ($rule['action_sous_categorie'] && $result['sous_categorie_id'] === null) {
                    $result['sous_categorie_id'] = $rule['action_sous_categorie'];
                }
                if ($rule['action_tiers'] && $result['tiers_id'] === null) {
                    $result['tiers_id'] = $rule['action_tiers'];
                }
                if ($rule['action_moyen_paiement'] && $result['moyen_paiement'] === null) {
                    $result['moyen_paiement'] = $rule['action_moyen_paiement'];
                }
                
                $result['rules_applied'][] = $rule['nom'];
                
                // Incrémenter le compteur d'applications
                static::incrementApplications($rule['id']);
            }
        }
        
        return $result;
    }
    
    /**
     * Incrémente le compteur d'applications d'une règle
     * 
     * Met à jour nb_applications (+1) et derniere_application (NOW()).
     * Utilisé par applyRules() à chaque fois qu'une règle matche.
     * 
     * @param int $ruleId ID de la règle
     * @return void
     * 
     * @see applyRules()
     */
    protected static function incrementApplications(int $ruleId): void
    {
        $sql = "UPDATE " . static::$table . " 
                SET nb_applications = nb_applications + 1,
                    derniere_application = NOW()
                WHERE id = ?";
        
        Database::execute($sql, [$ruleId]);
    }
    
    /**
     * Teste une règle sur un libellé sans l'appliquer
     * 
     * Simule l'application d'une règle sans modifier les compteurs ni créer de transaction.
     * Utile pour tester une règle avant de la créer ou pour afficher des prévisualisations.
     * 
     * @param array $rule Règle à tester avec 'type_pattern', 'pattern', 'case_sensitive'
     * @param string $libelle Libellé de test
     * @return bool True si la règle matche le libellé, false sinon
     * 
     * @example
     * $rule = [
     *     'type_pattern' => 'contient',
     *     'pattern' => 'CARREFOUR',
     *     'case_sensitive' => false
     * ];
     * 
     * RegleAutomatisation::testRule($rule, 'CB carrefour paris'); // true
     * RegleAutomatisation::testRule($rule, 'AUCHAN LILLE');        // false
     * 
     * @see applyRules()
     */
    public static function testRule(array $rule, string $libelle): bool
    {
        $matched = false;
        
        switch ($rule['type_pattern']) {
            case 'contient':
                if (!$rule['case_sensitive']) {
                    $matched = stripos($libelle, $rule['pattern']) !== false;
                } else {
                    $matched = strpos($libelle, $rule['pattern']) !== false;
                }
                break;
                
            case 'commence_par':
                if (!$rule['case_sensitive']) {
                    $matched = stripos($libelle, $rule['pattern']) === 0;
                } else {
                    $matched = strpos($libelle, $rule['pattern']) === 0;
                }
                break;
                
            case 'termine_par':
                $len = strlen($rule['pattern']);
                if (!$rule['case_sensitive']) {
                    $matched = strcasecmp(substr($libelle, -$len), $rule['pattern']) === 0;
                } else {
                    $matched = substr($libelle, -$len) === $rule['pattern'];
                }
                break;
                
            case 'regex':
                $flags = $rule['case_sensitive'] ? '' : 'i';
                $matched = preg_match('/' . $rule['pattern'] . '/' . $flags, $libelle);
                break;
        }
        
        return $matched;
    }
    
    /**
     * Applique rétroactivement les règles sur toutes les transactions existantes
     * 
     * Parcourt toutes les transactions de l'utilisateur et applique les règles.
     * Met à jour uniquement les champs vides (ne surcharge pas les valeurs manuelles).
     * 
     * Utile après :
     * - Création d'une nouvelle règle
     * - Modification d'une règle existante
     * - Import de transactions sans catégorisation
     * 
     * @param int $userId ID de l'utilisateur
     * @return array Statistiques ['total' => int, 'updated' => int, 'skipped' => int]
     * 
     * @example
     * $stats = RegleAutomatisation::applyToAllTransactions($userId);
     * echo "{$stats['updated']} transactions mises à jour sur {$stats['total']}";
     * echo "{$stats['skipped']} déjà catégorisées";
     * 
     * @see applyRules()
     */
    public static function applyToAllTransactions(int $userId): array
    {
        $stats = [
            'total' => 0,
            'updated' => 0,
            'skipped' => 0
        ];
        
        // Récupérer toutes les transactions de l'utilisateur
        $sql = "SELECT t.* FROM transactions t
                INNER JOIN comptes c ON t.compte_id = c.id
                WHERE c.user_id = ?";
        
        $transactions = Database::select($sql, [$userId]);
        $stats['total'] = count($transactions);
        
        foreach ($transactions as $transaction) {
            $result = static::applyRules($userId, $transaction['libelle']);
            
            $updates = [];
            $params = [];
            
            // Construire la requête de mise à jour uniquement pour les champs non définis
            if ($result['categorie_id'] && !$transaction['categorie_id']) {
                $updates[] = 'categorie_id = ?';
                $params[] = $result['categorie_id'];
            }
            if ($result['sous_categorie_id'] && !$transaction['sous_categorie_id']) {
                $updates[] = 'sous_categorie_id = ?';
                $params[] = $result['sous_categorie_id'];
            }
            if ($result['tiers_id'] && !$transaction['tiers_id']) {
                $updates[] = 'tiers_id = ?';
                $params[] = $result['tiers_id'];
            }
            if ($result['moyen_paiement'] && !$transaction['moyen_paiement']) {
                $updates[] = 'moyen_paiement = ?';
                $params[] = $result['moyen_paiement'];
            }
            
            if (!empty($updates)) {
                $params[] = $transaction['id'];
                $updateSQL = "UPDATE transactions SET " . implode(', ', $updates) . " WHERE id = ?";
                Database::execute($updateSQL, $params);
                $stats['updated']++;
            } else {
                $stats['skipped']++;
            }
        }
        
        return $stats;
    }
}
