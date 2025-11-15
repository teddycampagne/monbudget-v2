<?php

namespace MonBudget\Models;

/**
 * Class Tiers
 * 
 * Modèle de gestion des tiers (créanciers, débiteurs, organismes).
 * 
 * Représente les entités tierces avec lesquelles l'utilisateur effectue des transactions :
 * - Créditeurs : entités qui versent de l'argent (employeur, CAF, etc.)
 * - Débiteurs : entités qui reçoivent de l'argent (EDF, propriétaire, commerces, etc.)
 * - Mixtes : entités avec transactions dans les deux sens
 * 
 * Les tiers peuvent être organisés en groupes (Fournisseurs, Employeurs, etc.).
 * 
 * @package MonBudget\Models
 * @extends BaseModel
 * 
 * @property int $id Identifiant unique du tiers
 * @property int $user_id ID de l'utilisateur propriétaire
 * @property string $nom Nom du tiers (ex: "EDF", "Employeur ACME", "Carrefour")
 * @property string|null $groupe Groupe d'appartenance (ex: "Fournisseurs", "Employeurs")
 * @property string $type Type de tiers (crediteur, debiteur, mixte)
 * @property string|null $notes Notes ou informations complémentaires
 */
class Tiers extends BaseModel
{
    /** @var string Table de la base de données */
    protected static string $table = 'tiers';
    
    /** @var array Champs autorisés pour les opérations de création/modification */
    protected static array $fillable = [
        'user_id',
        'nom',
        'groupe',
        'type',
        'notes'
    ];

    /**
     * Récupère tous les tiers d'un utilisateur avec filtrage optionnel par type
     * 
     * @param int $userId ID de l'utilisateur
     * @param string|null $type Type de tiers à filtrer (crediteur, debiteur, mixte) ou null pour tous
     * @return array Liste des tiers triés par nom
     * 
     * @example
     * // Tous les tiers
     * $tiers = Tiers::getAllByUser($userId);
     * 
     * // Uniquement les débiteurs (ceux à qui on paye)
     * $debiteurs = Tiers::getAllByUser($userId, 'debiteur');
     */
    public static function getAllByUser(int $userId, ?string $type = null): array
    {
        $sql = "SELECT * FROM " . static::$table . " WHERE user_id = ?";
        $params = [$userId];
        
        if ($type) {
            $sql .= " AND type = ?";
            $params[] = $type;
        }
        
        $sql .= " ORDER BY nom";
        
        return static::query($sql, $params);
    }

    /**
     * Récupère les tiers organisés par type dans un tableau structuré
     * 
     * Retourne un tableau associatif avec 3 clés : crediteurs, debiteurs, mixtes.
     * Utile pour afficher des listes séparées par type.
     * 
     * @param int $userId ID de l'utilisateur
     * @return array Tableau avec clés 'crediteurs', 'debiteurs', 'mixtes'
     * 
     * @example
     * $grouped = Tiers::getGroupedByType($userId);
     * echo "Créditeurs : " . count($grouped['crediteurs']) . "\n";
     * echo "Débiteurs : " . count($grouped['debiteurs']) . "\n";
     * echo "Mixtes : " . count($grouped['mixtes']) . "\n";
     */
    public static function getGroupedByType(int $userId): array
    {
        $crediteurs = static::query(
            "SELECT * FROM " . static::$table . " WHERE user_id = ? AND type = 'crediteur' ORDER BY nom",
            [$userId]
        );
        
        $debiteurs = static::query(
            "SELECT * FROM " . static::$table . " WHERE user_id = ? AND type = 'debiteur' ORDER BY nom",
            [$userId]
        );
        
        $mixtes = static::query(
            "SELECT * FROM " . static::$table . " WHERE user_id = ? AND type = 'mixte' ORDER BY nom",
            [$userId]
        );
        
        return [
            'crediteurs' => $crediteurs,
            'debiteurs' => $debiteurs,
            'mixtes' => $mixtes
        ];
    }

    /**
     * Récupère tous les tiers d'un groupe spécifique
     * 
     * Filtre les tiers appartenant au même groupe (ex: tous les "Fournisseurs").
     * 
     * @param int $userId ID de l'utilisateur
     * @param string $groupe Nom du groupe
     * @return array Liste des tiers du groupe triés par nom
     * 
     * @example
     * $fournisseurs = Tiers::getByGroupe($userId, 'Fournisseurs');
     * foreach ($fournisseurs as $tiers) {
     *     echo "{$tiers['nom']} - {$tiers['type']}\n";
     * }
     */
    public static function getByGroupe(int $userId, string $groupe): array
    {
        return static::query(
            "SELECT * FROM " . static::$table . " WHERE user_id = ? AND groupe = ? ORDER BY nom",
            [$userId, $groupe]
        );
    }

    /**
     * Récupère la liste des groupes existants pour l'utilisateur
     * 
     * Retourne un tableau simple des noms de groupes uniques (DISTINCT).
     * Utile pour générer des listes déroulantes de groupes.
     * 
     * @param int $userId ID de l'utilisateur
     * @return array Liste des noms de groupes (tableau simple)
     * 
     * @example
     * $groupes = Tiers::getGroupes($userId);
     * // Résultat: ['Employeurs', 'Fournisseurs', 'Commerces']
     */
    public static function getGroupes(int $userId): array
    {
        $sql = "SELECT DISTINCT groupe FROM " . static::$table . " 
                WHERE user_id = ? AND groupe IS NOT NULL 
                ORDER BY groupe";
        
        $result = static::query($sql, [$userId]);
        
        return array_column($result, 'groupe');
    }

    /**
     * Compte le nombre de transactions associées à un tiers
     * 
     * Vérifie combien de transactions font référence à ce tiers.
     * Utile avant suppression pour éviter de casser l'intégrité référentielle.
     * 
     * @param int $tiersId ID du tiers
     * @return int Nombre de transactions liées
     * 
     * @example
     * $nbTransactions = Tiers::countTransactions($tiersId);
     * if ($nbTransactions > 0) {
     *     echo "Impossible de supprimer : {$nbTransactions} transactions liées";
     * }
     */
    public static function countTransactions(int $tiersId): int
    {
        $sql = "SELECT COUNT(*) as total FROM transactions WHERE tiers_id = ?";
        $result = static::query($sql, [$tiersId]);
        
        return $result[0]['total'] ?? 0;
    }

    /**
     * Recherche des tiers par mots-clés
     * 
     * Recherche dans les champs : nom, groupe et notes.
     * Recherche insensible à la casse avec correspondances partielles (LIKE %query%).
     * 
     * @param int $userId ID de l'utilisateur
     * @param string $query Terme de recherche
     * @return array Liste des tiers correspondants
     * 
     * @example
     * $resultats = Tiers::search($userId, 'edf');
     * foreach ($resultats as $tiers) {
     *     echo "{$tiers['nom']} ({$tiers['type']})\n";
     * }
     */
    public static function search(int $userId, string $query): array
    {
        $sql = "SELECT * FROM " . static::$table . " 
                WHERE user_id = ? 
                AND (nom LIKE ? OR groupe LIKE ? OR notes LIKE ?)
                ORDER BY nom";
        
        $searchTerm = "%{$query}%";
        
        return static::query($sql, [$userId, $searchTerm, $searchTerm, $searchTerm]);
    }

    /**
     * Récupère les tiers formatés pour un élément select HTML
     * 
     * Retourne un tableau simplifié avec uniquement les champs nécessaires
     * pour générer une liste déroulante (id, nom, groupe, type).
     * 
     * @param int $userId ID de l'utilisateur
     * @param string|null $type Type de tiers à filtrer ou null pour tous
     * @return array Liste simplifiée pour select
     * 
     * @example
     * $options = Tiers::getForSelect($userId, 'debiteur');
     * foreach ($options as $option) {
     *     echo "<option value='{$option['id']}'>{$option['nom']}</option>";
     * }
     */
    public static function getForSelect(int $userId, ?string $type = null): array
    {
        $tiers = static::getAllByUser($userId, $type);
        
        $options = [];
        foreach ($tiers as $t) {
            $options[] = [
                'id' => $t['id'],
                'nom' => $t['nom'],
                'groupe' => $t['groupe'],
                'type' => $t['type']
            ];
        }
        
        return $options;
    }

    /**
     * Supprime un tiers seulement s'il n'a pas de transactions liées
     * 
     * Vérifie d'abord le nombre de transactions associées.
     * Si des transactions existent, refuse la suppression pour préserver l'intégrité.
     * 
     * @param int $id ID du tiers à supprimer
     * @return bool True si supprimé, false si des transactions existent
     * 
     * @example
     * if (Tiers::deleteTiers($tiersId)) {
     *     echo "Tiers supprimé avec succès";
     * } else {
     *     echo "Impossible : des transactions sont liées à ce tiers";
     * }
     * 
     * @see countTransactions()
     */
    public static function deleteTiers(int $id): bool
    {
        if (static::countTransactions($id) > 0) {
            return false;
        }
        
        return static::delete($id) > 0;
    }
}
