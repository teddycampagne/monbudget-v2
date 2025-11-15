<?php

namespace MonBudget\Models;

/**
 * Class SousCategorie
 * 
 * Modèle de gestion des sous-catégories (niveau 2 de la hiérarchie).
 * 
 * Les sous-catégories permettent une classification fine des transactions.
 * Elles appartiennent toujours à une catégorie parente (niveau 1).
 * 
 * Exemples :
 * - Alimentation (catégorie) → Supermarché, Restaurant, Boulangerie (sous-catégories)
 * - Transport (catégorie) → Essence, Péage, Parking, Transport public (sous-catégories)
 * 
 * Note : Ce modèle est distinct de Categorie (qui gère les 2 niveaux via parent_id).
 * Certaines installations utilisent une table sous_categories dédiée.
 * 
 * @package MonBudget\Models
 * @extends BaseModel
 * 
 * @property int $id Identifiant unique
 * @property int $categorie_id ID de la catégorie parente
 * @property string $nom Nom de la sous-catégorie
 * @property string|null $description Description
 */
class SousCategorie extends BaseModel
{
    /** @var string Table de la base de données */
    protected static string $table = 'sous_categories';
    
    /** @var array Champs autorisés pour création/modification */
    protected static array $fillable = [
        'categorie_id',
        'nom',
        'description'
    ];

    /**
     * Récupère toutes les sous-catégories d'une catégorie parente
     * 
     * Tri alphabétique par nom.
     * 
     * @param int $categorieId ID de la catégorie parente
     * @return array Liste des sous-catégories
     * 
     * @example
     * $sousCategories = SousCategorie::getByCategorie(5); // 5 = Alimentation
     * // Retourne: Supermarché, Restaurant, Boulangerie, etc.
     */
    public static function getByCategorie(int $categorieId): array
    {
        return static::query(
            "SELECT * FROM " . static::$table . " WHERE categorie_id = ? ORDER BY nom",
            [$categorieId]
        );
    }

    /**
     * Récupère une sous-catégorie avec toutes les informations de sa catégorie parente
     * 
     * Jointure avec la table categories pour récupérer :
     * - Type (depense/revenu)
     * - Couleur et icône
     * - User_id de la catégorie parente
     * 
     * @param int $id ID de la sous-catégorie
     * @return array|null Sous-catégorie enrichie ou null si non trouvée
     * 
     * @example
     * $sousCategorie = SousCategorie::findWithCategorie(12);
     * echo "{$sousCategorie['nom']} ({$sousCategorie['categorie_nom']})";
     * echo "Type: {$sousCategorie['type']}"; // depense ou revenu
     */
    public static function findWithCategorie(int $id): ?array
    {
        $sql = "SELECT sc.*, c.nom as categorie_nom, c.type, c.couleur, c.icone, c.user_id as categorie_user_id
                FROM " . static::$table . " sc
                INNER JOIN categories c ON sc.categorie_id = c.id
                WHERE sc.id = ?";
        
        $result = static::query($sql, [$id]);
        
        return $result[0] ?? null;
    }

    /**
     * Compte le nombre de transactions utilisant cette sous-catégorie
     * 
     * Vérifie l'utilisation avant suppression pour éviter les orphelins.
     * 
     * @param int $sousCategorieId ID de la sous-catégorie
     * @return int Nombre de transactions associées
     * 
     * @example
     * $count = SousCategorie::countTransactions($sousCategorieId);
     * if ($count > 0) {
     *     echo "Impossible de supprimer : {$count} transactions liées";
     * }
     * 
     * @see deleteSousCategorie()
     */
    public static function countTransactions(int $sousCategorieId): int
    {
        $sql = "SELECT COUNT(*) as total FROM transactions WHERE sous_categorie_id = ?";
        $result = static::query($sql, [$sousCategorieId]);
        
        return $result[0]['total'] ?? 0;
    }

    /**
     * Supprime une sous-catégorie de manière sécurisée
     * 
     * Vérifie qu'aucune transaction n'utilise cette sous-catégorie avant suppression.
     * 
     * @param int $id ID de la sous-catégorie à supprimer
     * @return bool True si suppression réussie, false si transactions liées
     * 
     * @example
     * if (SousCategorie::deleteSousCategorie($id)) {
     *     echo "Sous-catégorie supprimée";
     * } else {
     *     echo "Impossible : transactions liées";
     * }
     * 
     * @see countTransactions()
     */
    public static function deleteSousCategorie(int $id): bool
    {
        if (static::countTransactions($id) > 0) {
            return false;
        }
        
        return static::delete($id) > 0;
    }
}
