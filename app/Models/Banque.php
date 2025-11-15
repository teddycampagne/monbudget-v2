<?php

namespace MonBudget\Models;

use MonBudget\Core\Database;

/**
 * Class Banque
 * 
 * Modèle de gestion des établissements bancaires.
 * 
 * Représente une banque ou institution financière avec ses coordonnées complètes.
 * Les banques sont liées aux comptes bancaires de l'utilisateur.
 * 
 * @package MonBudget\Models
 * @extends BaseModel
 * 
 * @property int $id Identifiant unique de la banque
 * @property string $nom Nom de la banque (ex: "BNP Paribas")
 * @property string|null $code_banque Code banque à 5 chiffres
 * @property string|null $bic Code BIC/SWIFT (ex: "BNPAFRPPXXX")
 * @property string|null $adresse Adresse complète (deprecated, utiliser adresse_ligne1/2)
 * @property string|null $adresse_ligne1 Adresse ligne 1
 * @property string|null $adresse_ligne2 Adresse ligne 2 (complément)
 * @property string|null $code_postal Code postal
 * @property string|null $ville Ville
 * @property string|null $pays Pays (défaut: France)
 * @property string|null $telephone Téléphone du service client
 * @property string|null $contact_email Email de contact
 * @property string|null $site_web URL du site web
 * @property string|null $logo Nom du fichier logo (deprecated)
 * @property string|null $logo_path Chemin complet du logo (deprecated)
 * @property string|null $logo_file Nom du fichier logo (nouveau format)
 */
class Banque extends BaseModel
{
    /** @var string Table de la base de données */
    protected static string $table = 'banques';
    
    /** @var array Champs autorisés pour les opérations de création/modification */
    protected static array $fillable = [
        'nom',
        'code_banque',
        'bic',
        'adresse',
        'telephone',
        'site_web',
        'logo',
        'adresse_ligne1',
        'adresse_ligne2',
        'code_postal',
        'ville',
        'pays',
        'logo_path',
        'logo_file',
        'contact_email'
    ];
    
    /**
     * Récupère toutes les banques avec tri personnalisable
     * 
     * @param string $orderBy Champ de tri (défaut: 'nom')
     * @param string $direction Direction du tri (ASC ou DESC, défaut: 'ASC')
     * @return array Liste de toutes les banques
     * 
     * @example
     * $banques = Banque::getAll('nom', 'ASC');
     * foreach ($banques as $banque) {
     *     echo "{$banque['nom']} - {$banque['ville']}\n";
     * }
     */
    public static function getAll(string $orderBy = 'nom', string $direction = 'ASC'): array
    {
        return Database::select(
            "SELECT * FROM " . static::$table . " ORDER BY {$orderBy} {$direction}"
        );
    }
    
    /**
     * Récupère une banque par son identifiant
     * 
     * @param int $id ID de la banque
     * @return array|null Données de la banque ou null si non trouvée
     * 
     * @example
     * $banque = Banque::find(3);
     * if ($banque) {
     *     echo "BIC: {$banque['bic']}";
     * }
     */
    public static function find(int $id): ?array
    {
        return Database::selectOne(
            "SELECT * FROM " . static::$table . " WHERE id = ?",
            [$id]
        );
    }
    
    /**
     * Crée une nouvelle banque
     * 
     * Insère uniquement les champs définis dans $fillable qui sont présents dans $data.
     * 
     * @param array $data Données de la banque à créer
     *                    - string $data['nom'] Nom de la banque (requis)
     *                    - string $data['code_banque'] Code à 5 chiffres (optionnel)
     *                    - string $data['bic'] Code BIC/SWIFT (optionnel)
     *                    - autres champs optionnels...
     * @return int ID de la banque créée
     * 
     * @throws \PDOException Si l'insertion échoue
     * 
     * @example
     * $banqueId = Banque::create([
     *     'nom' => 'Crédit Agricole',
     *     'code_banque' => '12345',
     *     'bic' => 'AGRIFRPPXXX',
     *     'ville' => 'Paris',
     *     'telephone' => '0825825825'
     * ]);
     */
    public static function create(array $data): int
    {
        $fields = [];
        $placeholders = [];
        $values = [];
        
        foreach (static::$fillable as $field) {
            if (isset($data[$field])) {
                $fields[] = $field;
                $placeholders[] = '?';
                $values[] = $data[$field];
            }
        }
        
        $sql = "INSERT INTO " . static::$table . " (" . implode(', ', $fields) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";
        
        return Database::insert($sql, $values);
    }
    
    /**
     * Met à jour une banque existante
     * 
     * Met à jour uniquement les champs définis dans $fillable qui sont présents dans $data.
     * 
     * @param int $id ID de la banque à mettre à jour
     * @param array $data Données à modifier
     * @return int Nombre de lignes affectées (0 ou 1)
     * 
     * @example
     * Banque::update($banqueId, [
     *     'telephone' => '0800123456',
     *     'site_web' => 'https://www.mabanque.fr'
     * ]);
     */
    public static function update(int $id, array $data): int
    {
        $setParts = [];
        $values = [];
        
        foreach (static::$fillable as $field) {
            if (isset($data[$field])) {
                $setParts[] = "{$field} = ?";
                $values[] = $data[$field];
            }
        }
        
        $values[] = $id;
        
        $sql = "UPDATE " . static::$table . " SET " . implode(', ', $setParts) . " WHERE id = ?";
        
        return Database::update($sql, $values);
    }
    
    /**
     * Supprime une banque de la base de données
     * 
     * ⚠️ ATTENTION : La suppression est définitive.
     * TODO: Vérifier et gérer les comptes associés avant suppression.
     * 
     * @param int $id ID de la banque à supprimer
     * @return int Nombre de lignes supprimées (0 ou 1)
     * 
     * @example
     * if (Banque::delete($banqueId)) {
     *     echo "Banque supprimée";
     * }
     */
    public static function delete(int $id): int
    {
        return Database::delete(
            "DELETE FROM " . static::$table . " WHERE id = ?",
            [$id]
        );
    }
    
    /**
     * Recherche des banques par mots-clés
     * 
     * Recherche dans les champs : nom, ville et code banque.
     * Recherche insensible à la casse avec correspondances partielles (LIKE %query%).
     * 
     * @param string $query Terme de recherche
     * @return array Liste des banques correspondantes
     * 
     * @example
     * $resultats = Banque::search('agricole');
     * foreach ($resultats as $banque) {
     *     echo "{$banque['nom']} ({$banque['ville']})\n";
     * }
     */
    public static function search(string $query): array
    {
        $searchTerm = "%{$query}%";
        return Database::select(
            "SELECT * FROM " . static::$table . " 
             WHERE nom LIKE ? OR ville LIKE ? OR code_banque LIKE ?
             ORDER BY nom ASC",
            [$searchTerm, $searchTerm, $searchTerm]
        );
    }
    
    /**
     * Récupère toutes les banques avec le nombre de comptes associés
     * 
     * Effectue un LEFT JOIN avec la table comptes pour compter le nombre de comptes
     * par banque. Utile pour afficher une vue d'ensemble du patrimoine.
     * 
     * @return array Liste des banques avec colonne 'nb_comptes' ajoutée
     * 
     * @example
     * $banques = Banque::withComptesCount();
     * foreach ($banques as $banque) {
     *     echo "{$banque['nom']} : {$banque['nb_comptes']} compte(s)\n";
     * }
     */
    public static function withComptesCount(): array
    {
        $sql = "SELECT b.*, 
                       COALESCE(COUNT(c.id), 0) as nb_comptes
                FROM " . static::$table . " b
                LEFT JOIN comptes c ON c.banque_id = b.id
                GROUP BY b.id
                ORDER BY b.nom ASC";
        
        return Database::select($sql);
    }
    
    /**
     * Vérifie si un nom de banque existe déjà
     * 
     * Utile lors de la création/modification pour garantir l'unicité des noms.
     * Permet d'exclure la banque en cours de modification via $excludeId.
     * 
     * @param string $nom Nom de la banque à vérifier
     * @param int|null $excludeId ID de la banque à exclure (pour édition)
     * @return bool True si le nom existe déjà, false sinon
     * 
     * @example
     * // Lors de la création
     * if (Banque::nomExists($_POST['nom'])) {
     *     throw new \Exception("Cette banque existe déjà");
     * }
     * 
     * // Lors de l'édition
     * if (Banque::nomExists($_POST['nom'], $banqueId)) {
     *     throw new \Exception("Ce nom est déjà utilisé");
     * }
     */
    public static function nomExists(string $nom, ?int $excludeId = null): bool
    {
        if ($excludeId) {
            $result = Database::selectOne(
                "SELECT COUNT(*) as count FROM " . static::$table . " WHERE nom = ? AND id != ?",
                [$nom, $excludeId]
            );
        } else {
            $result = Database::selectOne(
                "SELECT COUNT(*) as count FROM " . static::$table . " WHERE nom = ?",
                [$nom]
            );
        }
        
        return $result && $result['count'] > 0;
    }
}
