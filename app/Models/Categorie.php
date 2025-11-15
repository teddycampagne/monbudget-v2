<?php

namespace MonBudget\Models;

use MonBudget\Core\Database;

/**
 * Class Categorie
 * 
 * Modèle de gestion des catégories et sous-catégories de transactions.
 * 
 * Représente une structure hiérarchique à deux niveaux :
 * - Catégories principales (parent_id = NULL) : ex: "Alimentation", "Logement"
 * - Sous-catégories (parent_id != NULL) : ex: "Supermarché", "Restaurant" sous "Alimentation"
 * 
 * Les catégories peuvent être de type :
 * - depense : pour les dépenses
 * - revenu : pour les revenus
 * 
 * Chaque catégorie peut avoir une couleur et une icône pour la visualisation.
 * Les catégories système (is_system = 1) sont protégées contre la modification.
 * 
 * @package MonBudget\Models
 * @extends BaseModel
 * 
 * @property int $id Identifiant unique de la catégorie
 * @property int $user_id ID de l'utilisateur propriétaire
 * @property string $nom Nom de la catégorie (ex: "Alimentation", "Supermarché")
 * @property string|null $description Description détaillée
 * @property string $type Type de catégorie (depense, revenu)
 * @property string|null $couleur Code couleur hexadécimal (#RRGGBB)
 * @property string|null $icone Classe d'icône (ex: "fas fa-shopping-cart")
 * @property int|null $parent_id ID de la catégorie parente (NULL si principale)
 * @property bool $is_system Catégorie système (non modifiable/supprimable)
 */
class Categorie extends BaseModel
{
    /** @var string Table de la base de données */
    protected static string $table = 'categories';
    
    /** @var array Champs autorisés pour les opérations de création/modification */
    protected static array $fillable = [
        'user_id',
        'nom',
        'description',
        'type',
        'couleur',
        'icone',
        'parent_id',
        'is_system'
    ];

    /**
     * Récupérer une catégorie par son ID
     * 
     * @param int $id ID de la catégorie
     * @return array|null Catégorie ou null si non trouvée
     */
    public static function getById(int $id): ?array
    {
        $sql = "SELECT * FROM categories WHERE id = ?";
        $result = Database::selectOne($sql, [$id]);
        return $result ?: null;
    }

    /**
     * Récupère toutes les catégories avec leurs sous-catégories et infos parent
     * 
     * Join avec la table categories elle-même pour obtenir le nom du parent.
     * Ajoute le compte de sous-catégories pour chaque catégorie.
     * Tri : catégories principales d'abord, puis sous-catégories par parent.
     * 
     * @param int $userId ID de l'utilisateur
     * @param string|null $type Type à filtrer (depense, revenu) ou null pour tous
     * @return array Liste complète des catégories enrichies
     * 
     * @example
     * $categories = Categorie::getAllWithSousCategories($userId, 'depense');
     * foreach ($categories as $cat) {
     *     $prefix = $cat['parent_id'] ? '  → ' : '';
     *     echo "{$prefix}{$cat['nom']} ({$cat['nb_sous_categories']} sous-cat.)\n";
     * }
     */
    public static function getAllWithSousCategories(int $userId, ?string $type = null): array
    {
        $sql = "SELECT 
                    c.*,
                    (SELECT COUNT(*) FROM categories sc WHERE sc.parent_id = c.id) as nb_sous_categories,
                    p.nom as parent_nom
                FROM " . static::$table . " c
                LEFT JOIN categories p ON c.parent_id = p.id
                WHERE c.user_id = ?";
        
        $params = [$userId];
        
        if ($type) {
            $sql .= " AND c.type = ?";
            $params[] = $type;
        }
        
        $sql .= " ORDER BY c.parent_id IS NULL DESC, c.parent_id, c.nom";
        
        return static::query($sql, $params);
    }

    /**
     * Récupère uniquement les catégories principales (sans parent)
     * 
     * Filtre les catégories de premier niveau (parent_id IS NULL).
     * 
     * @param int $userId ID de l'utilisateur
     * @param string|null $type Type à filtrer (depense, revenu) ou null pour tous
     * @return array Liste des catégories principales triées par nom
     * 
     * @example
     * $principales = Categorie::getCategoriesPrincipales($userId, 'depense');
     * // Résultat: ["Alimentation", "Logement", "Transport", ...]
     */
    public static function getCategoriesPrincipales(int $userId, ?string $type = null): array
    {
        // Inclure les catégories système (user_id IS NULL) ET les catégories personnelles (user_id = ?)
        $sql = "SELECT * FROM " . static::$table . " 
                WHERE (user_id IS NULL OR user_id = ?) AND parent_id IS NULL";
        
        $params = [$userId];
        
        if ($type) {
            $sql .= " AND type = ?";
            $params[] = $type;
        }
        
        $sql .= " ORDER BY user_id IS NULL DESC, nom"; // Catégories système en premier
        
        return static::query($sql, $params);
    }

    /**
     * Récupère toutes les sous-catégories d'une catégorie parente
     * 
     * @param int $categorieId ID de la catégorie parente
     * @return array Liste des sous-catégories triées par nom
     * 
     * @example
     * $sousCategories = Categorie::getSousCategories($alimentationId);
     * // Résultat: ["Supermarché", "Restaurant", "Boulangerie", ...]
     */
    public static function getSousCategories(int $categorieId): array
    {
        $sql = "SELECT * FROM " . static::$table . " 
                WHERE parent_id = ? 
                ORDER BY nom";
        
        return static::query($sql, [$categorieId]);
    }

    /**
     * Récupère une catégorie avec toutes ses sous-catégories
     * 
     * Combine find() et getSousCategories() pour obtenir un objet complet.
     * 
     * @param int $id ID de la catégorie
     * @return array|null Catégorie avec clé 'sous_categories' ajoutée, ou null si non trouvée
     * 
     * @example
     * $categorie = Categorie::findWithSousCategories($alimentationId);
     * if ($categorie) {
     *     echo "Catégorie : {$categorie['nom']}\n";
     *     echo "Sous-catégories : " . count($categorie['sous_categories']) . "\n";
     * }
     * 
     * @see getSousCategories()
     */
    public static function findWithSousCategories(int $id): ?array
    {
        $categorie = static::find($id);
        
        if (!$categorie) {
            return null;
        }
        
        $categorie['sous_categories'] = static::getSousCategories($id);
        
        return $categorie;
    }

    /**
     * Récupère toutes les catégories en structure hiérarchique arborescente
     * 
     * Retourne les catégories principales avec leurs sous-catégories imbriquées.
     * Structure optimale pour affichage en arborescence ou menus déroulants hiérarchiques.
     * 
     * @param int $userId ID de l'utilisateur
     * @param string|null $type Type à filtrer (depense, revenu) ou null pour tous
     * @return array Tableau de catégories principales avec sous_categories imbriquées
     * 
     * @example
     * $hierarchie = Categorie::getHierarchie($userId, 'depense');
     * foreach ($hierarchie as $principale) {
     *     echo "{$principale['nom']}\n";
     *     foreach ($principale['sous_categories'] as $sous) {
     *         echo "  → {$sous['nom']}\n";
     *     }
     * }
     */
    public static function getHierarchie(int $userId, ?string $type = null): array
    {
        $principales = static::getCategoriesPrincipales($userId, $type);
        
        foreach ($principales as &$principale) {
            $principale['sous_categories'] = static::getSousCategories($principale['id']);
        }
        
        return $principales;
    }

    /**
     * Compte le nombre de transactions utilisant cette catégorie
     * 
     * Vérifie les transactions avec cette catégorie en tant que categorie_id OU sous_categorie_id.
     * Utile avant suppression pour éviter de casser l'intégrité référentielle.
     * 
     * @param int $categorieId ID de la catégorie
     * @return int Nombre de transactions liées
     * 
     * @example
     * $nbTransactions = Categorie::countTransactions($categorieId);
     * if ($nbTransactions > 0) {
     *     echo "Impossible de supprimer : {$nbTransactions} transactions utilisent cette catégorie";
     * }
     */
    public static function countTransactions(int $categorieId): int
    {
        $sql = "SELECT COUNT(*) as total 
                FROM transactions 
                WHERE categorie_id = ? OR sous_categorie_id = ?";
        
        $result = static::query($sql, [$categorieId, $categorieId]);
        
        return $result[0]['total'] ?? 0;
    }

    /**
     * Supprime une catégorie seulement si elle n'a ni transactions ni sous-catégories
     * 
     * Vérifie deux conditions avant suppression :
     * 1. Aucune transaction ne doit utiliser cette catégorie
     * 2. Aucune sous-catégorie ne doit exister
     * 
     * @param int $id ID de la catégorie à supprimer
     * @return bool True si supprimée, false si impossible (transactions ou sous-catégories existent)
     * 
     * @example
     * if (Categorie::deleteCategorie($categorieId)) {
     *     echo "Catégorie supprimée avec succès";
     * } else {
     *     echo "Impossible : transactions ou sous-catégories liées";
     * }
     * 
     * @see countTransactions()
     * @see getSousCategories()
     */
    public static function deleteCategorie(int $id): bool
    {
        // Vérifier si des transactions utilisent cette catégorie
        if (static::countTransactions($id) > 0) {
            return false;
        }
        
        // Vérifier si elle a des sous-catégories
        $sousCategories = static::getSousCategories($id);
        if (!empty($sousCategories)) {
            return false;
        }
        
        return static::delete($id) > 0;
    }

    /**
     * Recherche des catégories par mots-clés
     * 
     * Recherche dans les champs : nom et description.
     * Inclut le nom de la catégorie parente dans les résultats (JOIN).
     * Tri : catégories principales d'abord, puis par nom.
     * 
     * @param int $userId ID de l'utilisateur
     * @param string $query Terme de recherche
     * @return array Liste des catégories correspondantes avec parent_nom
     * 
     * @example
     * $resultats = Categorie::search($userId, 'alimentaire');
     * foreach ($resultats as $cat) {
     *     $parent = $cat['parent_nom'] ? " sous {$cat['parent_nom']}" : '';
     *     echo "{$cat['nom']}{$parent}\n";
     * }
     */
    public static function search(int $userId, string $query): array
    {
        $sql = "SELECT c.*, p.nom as parent_nom
                FROM " . static::$table . " c
                LEFT JOIN categories p ON c.parent_id = p.id
                WHERE c.user_id = ? 
                AND (c.nom LIKE ? OR c.description LIKE ?)
                ORDER BY c.parent_id IS NULL DESC, c.nom";
        
        $searchTerm = "%{$query}%";
        
        return static::query($sql, [$userId, $searchTerm, $searchTerm]);
    }

    /**
     * Récupère les catégories formatées pour un élément select HTML hiérarchique
     * 
     * Retourne un tableau avec indentation visuelle pour les sous-catégories.
     * Utile pour générer des listes déroulantes <select> avec hiérarchie visible.
     * 
     * @param int $userId ID de l'utilisateur
     * @param string|null $type Type à filtrer (depense, revenu) ou null pour tous
     * @return array Liste formatée avec 'id', 'nom' (indenté), 'is_parent', 'parent_id'
     * 
     * @example
     * $options = Categorie::getForSelect($userId, 'depense');
     * foreach ($options as $option) {
     *     $selected = $option['id'] == $currentId ? 'selected' : '';
     *     echo "<option value='{$option['id']}' {$selected}>{$option['nom']}</option>";
     * }
     * // Affichage :
     * // Alimentation
     * // — Supermarché
     * // — Restaurant
     * // Logement
     * // — Loyer
     */
    public static function getForSelect(int $userId, ?string $type = null): array
    {
        $hierarchie = static::getHierarchie($userId, $type);
        $options = [];
        
        foreach ($hierarchie as $principale) {
            $options[] = [
                'id' => $principale['id'],
                'nom' => $principale['nom'],
                'is_parent' => true
            ];
            
            foreach ($principale['sous_categories'] as $sous) {
                $options[] = [
                    'id' => $sous['id'],
                    'nom' => '— ' . $sous['nom'],
                    'is_parent' => false,
                    'parent_id' => $principale['id']
                ];
            }
        }
        
        return $options;
    }
}
