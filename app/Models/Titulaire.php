<?php

namespace MonBudget\Models;

use MonBudget\Core\Database;

/**
 * Class Titulaire
 * 
 * Modèle de gestion des titulaires de comptes bancaires.
 * 
 * Représente une personne physique titulaire ou co-titulaire d'un compte.
 * Un compte peut avoir plusieurs titulaires (compte joint).
 * Un titulaire peut être associé à plusieurs comptes avec des rôles différents.
 * 
 * @package MonBudget\Models
 * @extends BaseModel
 * 
 * @property int $id Identifiant unique du titulaire
 * @property int $user_id ID de l'utilisateur propriétaire
 * @property string $nom Nom de famille
 * @property string $prenom Prénom
 * @property string|null $date_naissance Date de naissance (YYYY-MM-DD)
 * @property string|null $lieu_naissance Lieu de naissance (ville, pays)
 * @property string|null $adresse_ligne1 Adresse ligne 1 (numéro et rue)
 * @property string|null $adresse_ligne2 Adresse ligne 2 (complément)
 * @property string|null $code_postal Code postal
 * @property string|null $ville Ville de résidence
 * @property string|null $pays Pays de résidence (défaut: France)
 * @property string|null $telephone Numéro de téléphone
 * @property string|null $email Adresse email
 */
class Titulaire extends BaseModel
{
    /** @var string Table de la base de données */
    protected static string $table = 'titulaires';
    
    /** @var array Champs autorisés pour les opérations de création/modification */
    protected static array $fillable = [
        'user_id',
        'nom',
        'prenom',
        'date_naissance',
        'lieu_naissance',
        'adresse_ligne1',
        'adresse_ligne2',
        'code_postal',
        'ville',
        'pays',
        'telephone',
        'email'
    ];
    
    /**
     * Récupère tous les titulaires de l'utilisateur connecté avec tri personnalisable
     * 
     * @param string $orderBy Champ de tri (défaut: 'nom')
     * @param string $direction Direction du tri (ASC ou DESC, défaut: 'ASC')
     * @return array Liste des titulaires
     * 
     * @example
     * $titulaires = Titulaire::getAll('nom', 'ASC');
     */
    public static function getAll(string $orderBy = 'nom', string $direction = 'ASC'): array
    {
        $userId = $_SESSION['user']['id'] ?? 0;
        
        return Database::select(
            "SELECT * FROM " . static::$table . " 
             WHERE user_id = ? 
             ORDER BY {$orderBy} {$direction}",
            [$userId]
        );
    }
    
    /**
     * Récupère un titulaire par son identifiant
     * 
     * Vérifie que le titulaire appartient bien à l'utilisateur connecté.
     * 
     * @param int $id ID du titulaire
     * @return array|null Données du titulaire ou null si non trouvé
     */
    public static function find(int $id): ?array
    {
        $userId = $_SESSION['user']['id'] ?? 0;
        
        return Database::selectOne(
            "SELECT * FROM " . static::$table . " 
             WHERE id = ? AND user_id = ?",
            [$id, $userId]
        );
    }
    
    /**
     * Crée un nouveau titulaire
     * 
     * @param array $data Données du titulaire (nom et prenom requis minimum)
     * @return int ID du titulaire créé
     * 
     * @example
     * $titulaireId = Titulaire::create([
     *     'user_id' => $userId,
     *     'nom' => 'DUPONT',
     *     'prenom' => 'Jean',
     *     'date_naissance' => '1980-05-15',
     *     'adresse_ligne1' => '123 Rue de la Paix',
     *     'code_postal' => '75001',
     *     'ville' => 'Paris',
     *     'pays' => 'France',
     *     'telephone' => '0612345678',
     *     'email' => 'jean.dupont@example.com'
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
     * Modifie un titulaire existant
     * 
     * Vérifie l'appartenance du titulaire à l'utilisateur connecté avant modification.
     * Met à jour uniquement les champs fournis dans $data.
     * 
     * @param int $id ID du titulaire à modifier
     * @param array $data Nouvelles données (champs partiels acceptés)
     * @return int Nombre de lignes affectées (0 ou 1)
     * 
     * @example
     * $updated = Titulaire::update($titulaireId, [
     *     'telephone' => '0687654321',
     *     'email' => 'nouveau.email@example.com'
     * ]);
     */
    public static function update(int $id, array $data): int
    {
        $sets = [];
        $values = [];
        
        foreach (static::$fillable as $field) {
            if (isset($data[$field])) {
                $sets[] = "{$field} = ?";
                $values[] = $data[$field];
            }
        }
        
        $values[] = $id;
        $userId = $_SESSION['user']['id'] ?? 0;
        $values[] = $userId;
        
        $sql = "UPDATE " . static::$table . " 
                SET " . implode(', ', $sets) . " 
                WHERE id = ? AND user_id = ?";
        
        return Database::update($sql, $values);
    }
    
    /**
     * Supprime un titulaire
     * 
     * Vérifie que le titulaire appartient bien à l'utilisateur connecté.
     * ATTENTION : Vérifier avec hasComptes() avant suppression pour éviter
     * les contraintes d'intégrité référentielle.
     * 
     * @param int $id ID du titulaire à supprimer
     * @return int Nombre de lignes supprimées (0 ou 1)
     * 
     * @example
     * if (Titulaire::hasComptes($titulaireId)) {
     *     echo "Impossible : titulaire associé à des comptes";
     * } else {
     *     Titulaire::delete($titulaireId);
     * }
     * 
     * @see hasComptes()
     */
    public static function delete(int $id): int
    {
        $userId = $_SESSION['user']['id'] ?? 0;
        
        return Database::delete(
            "DELETE FROM " . static::$table . " WHERE id = ? AND user_id = ?",
            [$id, $userId]
        );
    }
    
    /**
     * Formate le nom complet d'un titulaire
     * 
     * Convention : "Prénom NOM" (nom en majuscules, prénom capitalisé).
     * 
     * @param array $titulaire Données du titulaire avec 'nom' et 'prenom'
     * @return string Nom formaté
     * 
     * @example
     * $nom = Titulaire::getNomComplet(['prenom' => 'marie', 'nom' => 'martin']);
     * // Retourne: "Marie MARTIN"
     */
    public static function getNomComplet(array $titulaire): string
    {
        $nom = strtoupper($titulaire['nom'] ?? '');
        $prenom = ucfirst(strtolower($titulaire['prenom'] ?? ''));
        
        return trim($prenom . ' ' . $nom);
    }
    
    /**
     * Formate l'adresse complète d'un titulaire
     * 
     * Retourne une adresse formatée sur plusieurs lignes avec saut de ligne (\n).
     * Seuls les champs remplis sont inclus dans le résultat.
     * 
     * Format :
     * - Ligne 1 : adresse_ligne1
     * - Ligne 2 : adresse_ligne2 (si présente)
     * - Ligne 3 : code_postal ville
     * - Ligne 4 : pays (si présent)
     * 
     * @param array $titulaire Données du titulaire avec champs d'adresse
     * @return string Adresse formatée avec sauts de ligne (\n)
     * 
     * @example
     * $adresse = Titulaire::getAdresseComplete([
     *     'adresse_ligne1' => '123 Rue de la Paix',
     *     'adresse_ligne2' => 'Bâtiment A',
     *     'code_postal' => '75001',
     *     'ville' => 'Paris',
     *     'pays' => 'France'
     * ]);
     * // Retourne:
     * // 123 Rue de la Paix
     * // Bâtiment A
     * // 75001 Paris
     * // France
     */
    public static function getAdresseComplete(array $titulaire): string
    {
        $adresse = [];
        
        if (!empty($titulaire['adresse_ligne1'])) {
            $adresse[] = $titulaire['adresse_ligne1'];
        }
        if (!empty($titulaire['adresse_ligne2'])) {
            $adresse[] = $titulaire['adresse_ligne2'];
        }
        if (!empty($titulaire['code_postal']) || !empty($titulaire['ville'])) {
            $ligne = trim(($titulaire['code_postal'] ?? '') . ' ' . ($titulaire['ville'] ?? ''));
            if ($ligne) {
                $adresse[] = $ligne;
            }
        }
        if (!empty($titulaire['pays'])) {
            $adresse[] = $titulaire['pays'];
        }
        
        return implode("\n", $adresse);
    }
    
    /**
     * Vérifie si le titulaire a des comptes associés
     * 
     * Consulte la table compte_titulaires pour détecter les associations.
     * Utile avant suppression pour éviter les contraintes d'intégrité référentielle.
     * 
     * @param int $id ID du titulaire
     * @return bool True si au moins un compte associé, false sinon
     * 
     * @example
     * if (Titulaire::hasComptes($titulaireId)) {
     *     echo "Suppression impossible : {$count} compte(s) associé(s)";
     * }
     * 
     * @see delete()
     * @see getComptes()
     */
    public static function hasComptes(int $id): bool
    {
        $result = Database::selectOne(
            "SELECT COUNT(*) as count FROM compte_titulaires WHERE titulaire_id = ?",
            [$id]
        );
        
        return ($result['count'] ?? 0) > 0;
    }
    
    /**
     * Récupère tous les comptes associés à un titulaire
     * 
     * Retourne les comptes avec informations enrichies :
     * - Nom de la banque (via JOIN)
     * - Rôle du titulaire (titulaire, co-titulaire)
     * - Ordre d'affichage
     * 
     * @param int $titulaireId ID du titulaire
     * @return array Liste des comptes avec banque_nom, role, ordre
     * 
     * @example
     * $comptes = Titulaire::getComptes($titulaireId);
     * foreach ($comptes as $compte) {
     *     echo "{$compte['nom']} ({$compte['banque_nom']}) - {$compte['role']}";
     * }
     * 
     * @see hasComptes()
     */
    public static function getComptes(int $titulaireId): array
    {
        return Database::select(
            "SELECT c.*, b.nom as banque_nom, ct.role, ct.ordre
             FROM comptes c
             INNER JOIN compte_titulaires ct ON c.id = ct.compte_id
             LEFT JOIN banques b ON c.banque_id = b.id
             WHERE ct.titulaire_id = ?
             ORDER BY ct.ordre ASC, c.nom ASC",
            [$titulaireId]
        );
    }
    
    /**
     * Recherche des titulaires par nom, prénom ou ville
     * 
     * Effectue une recherche LIKE (insensible à la casse) sur nom, prénom et ville.
     * Limité à 20 résultats pour performances.
     * 
     * @param string $query Terme de recherche (3+ caractères recommandés)
     * @return array Liste des titulaires correspondants (max 20)
     * 
     * @example
     * $resultats = Titulaire::search('Dupont');
     * // Trouve "Jean DUPONT", "Marie Dupond", etc.
     * 
     * $resultats = Titulaire::search('Paris');
     * // Trouve tous les titulaires habitant Paris
     */
    public static function search(string $query): array
    {
        $userId = $_SESSION['user']['id'] ?? 0;
        $searchTerm = '%' . $query . '%';
        
        return Database::select(
            "SELECT * FROM " . static::$table . " 
             WHERE user_id = ? 
             AND (nom LIKE ? OR prenom LIKE ? OR ville LIKE ?)
             ORDER BY nom ASC, prenom ASC
             LIMIT 20",
            [$userId, $searchTerm, $searchTerm, $searchTerm]
        );
    }
    
    /**
     * Vérifie si un titulaire avec ce nom et prénom existe déjà
     * 
     * Validation pour éviter les doublons (même nom + même prénom).
     * Utile avant création ou modification.
     * 
     * @param string $nom Nom de famille
     * @param string $prenom Prénom
     * @param int|null $excludeId ID à exclure de la recherche (pour modification)
     * @return bool True si doublon détecté, false sinon
     * 
     * @example
     * // Avant création
     * if (Titulaire::nomPrenomExists('Dupont', 'Jean')) {
     *     echo "Ce titulaire existe déjà";
     * }
     * 
     * // Avant modification
     * if (Titulaire::nomPrenomExists('Martin', 'Paul', $titulaireId)) {
     *     echo "Un autre titulaire porte déjà ce nom";
     * }
     */
    public static function nomPrenomExists(string $nom, string $prenom, ?int $excludeId = null): bool
    {
        $userId = $_SESSION['user']['id'] ?? 0;
        
        if ($excludeId) {
            $result = Database::selectOne(
                "SELECT COUNT(*) as count FROM " . static::$table . " 
                 WHERE user_id = ? AND nom = ? AND prenom = ? AND id != ?",
                [$userId, $nom, $prenom, $excludeId]
            );
        } else {
            $result = Database::selectOne(
                "SELECT COUNT(*) as count FROM " . static::$table . " 
                 WHERE user_id = ? AND nom = ? AND prenom = ?",
                [$userId, $nom, $prenom]
            );
        }
        
        return ($result['count'] ?? 0) > 0;
    }
}
