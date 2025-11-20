<?php

namespace MonBudget\Models;

use MonBudget\Core\Database;
use MonBudget\Services\EncryptionService;

/**
 * Class Compte
 * 
 * Modèle de gestion des comptes bancaires.
 * 
 * Représente un compte bancaire appartenant à un utilisateur et lié à une banque.
 * Gère les différents types de comptes (courant, épargne, livret, etc.),
 * les soldes (initial et actuel), les devises et les opérations de recalcul.
 * 
 * @package MonBudget\Models
 * @extends BaseModel
 * 
 * @property int $id Identifiant unique du compte
 * @property int $user_id ID de l'utilisateur propriétaire
 * @property int $banque_id ID de la banque associée
 * @property string $nom Nom du compte (ex: "Compte Courant Principal")
 * @property string $type_compte Type de compte (courant, epargne, livret, titre, etc.)
 * @property string|null $numero_compte Numéro de compte bancaire
 * @property string|null $code_guichet Code guichet de l'agence
 * @property string|null $cle_rib Clé RIB
 * @property string|null $iban IBAN complet du compte
 * @property float $solde_initial Solde à l'ouverture du compte dans l'application
 * @property float $solde_actuel Solde courant calculé (initial + transactions)
 * @property string $devise Code devise (EUR, USD, etc.)
 * @property bool $actif Compte actif ou archivé
 * @property string|null $description Description ou notes sur le compte
 */
class Compte extends BaseModel
{
    /** @var string Table de la base de données */
    protected static string $table = 'comptes';
    
    /** @var array Champs autorisés pour les opérations de création/modification */
    protected static array $fillable = [
        'user_id',
        'banque_id',
        'nom',
        'type_compte',
        'numero_compte',
        'code_guichet',
        'cle_rib',
        'iban',
        'solde_initial',
        'solde_actuel',
        'devise',
        'actif',
        'description'
    ];
    
    /**
     * Récupère tous les comptes avec les informations de leur banque associée
     * 
     * Join avec la table 'banques' pour enrichir les données.
     * Résultats triés par nom de compte (ordre alphabétique).
     * 
     * @return array Liste de tous les comptes avec nom et code de la banque
     * 
     * @example
     * $comptes = Compte::getAllWithBanque();
     * foreach ($comptes as $compte) {
     *     echo "{$compte['nom']} - {$compte['banque_nom']}\n";
     * }
     */
    public static function getAllWithBanque(): array
    {
        $sql = "SELECT c.*, b.nom as banque_nom, b.code_banque 
                FROM " . static::$table . " c
                LEFT JOIN banques b ON c.banque_id = b.id
                ORDER BY c.nom ASC";
        
        return Database::select($sql);
    }
    
    /**
     * Récupère tous les comptes d'un utilisateur spécifique avec leur solde
     * 
     * Join avec la table 'banques' pour enrichir les données.
     * Tri personnalisable, par défaut : comptes actifs en premier, puis par nom.
     * 
     * @param int $userId ID de l'utilisateur dont on veut les comptes
     * @param string $orderBy Clause ORDER BY SQL (défaut: 'c.actif DESC, c.nom ASC')
     * @return array Liste des comptes de l'utilisateur avec infos banque
     * 
     * @example
     * // Récupérer les comptes actifs d'abord
     * $comptes = Compte::getByUser($userId);
     * 
     * // Tri personnalisé par solde décroissant
     * $comptes = Compte::getByUser($userId, 'c.solde_actuel DESC');
     */
    public static function getByUser(int $userId, string $orderBy = 'c.actif DESC, c.nom ASC'): array
    {
        $sql = "SELECT c.*, b.nom as banque_nom, b.code_banque 
                FROM " . static::$table . " c
                LEFT JOIN banques b ON c.banque_id = b.id
                WHERE c.user_id = ?
                ORDER BY $orderBy";
        
        return Database::select($sql, [$userId]);
    }
    
    /**
     * Alias de getByUser pour compatibilité avec l'ancien code
     * 
     * @deprecated Utiliser getByUser() à la place
     * @param int $userId ID de l'utilisateur
     * @return array Liste des comptes de l'utilisateur
     * 
     * @see getByUser()
     */
    public static function getAllByUser(int $userId): array
    {
        return static::getByUser($userId);
    }
    
    /**
     * Récupérer un compte par son ID
     * 
     * @param int $id ID du compte
     * @return array|null Compte ou null si non trouvé
     */
    public static function getById(int $id): ?array
    {
        $sql = "SELECT c.*, b.nom as banque_nom, b.code_banque 
                FROM " . static::$table . " c
                LEFT JOIN banques b ON c.banque_id = b.id
                WHERE c.id = ?";
        
        $result = Database::selectOne($sql, [$id]);
        return $result ?: null;
    }
    
    /**
     * Récupère un compte avec toutes les informations détaillées de sa banque
     * 
     * Join avec la table 'banques' pour obtenir les coordonnées complètes
     * (adresse, téléphone, email, logo, etc.).
     * 
     * @param int $id ID du compte à récupérer
     * @return array|null Données du compte avec infos banque complètes, ou null si non trouvé
     * 
     * @example
     * $compte = Compte::findWithBanque($compteId);
     * if ($compte) {
     *     echo "Banque : {$compte['banque_nom']}\n";
     *     echo "IBAN : {$compte['iban']}\n";
     *     echo "BIC : {$compte['bic']}\n";
     * }
     */
    public static function findWithBanque(int $id): ?array
    {
        $sql = "SELECT c.*, 
                b.nom as banque_nom, 
                b.code_banque,
                b.bic,
                b.adresse_ligne1 as banque_adresse_ligne1,
                b.adresse_ligne2 as banque_adresse_ligne2,
                b.code_postal as banque_code_postal,
                b.ville as banque_ville,
                b.pays as banque_pays,
                b.telephone as banque_telephone,
                b.contact_email as banque_contact_email,
                b.logo_file
                FROM " . static::$table . " c
                LEFT JOIN banques b ON c.banque_id = b.id
                WHERE c.id = ? 
                LIMIT 1";
        
        return Database::selectOne($sql, [$id]);
    }
    
    /**
     * Récupère tous les comptes associés à une banque spécifique
     * 
     * Utile pour afficher tous les comptes d'une même banque,
     * par exemple lors de la visualisation d'une fiche banque.
     * 
     * @param int $banqueId ID de la banque
     * @return array Liste des comptes de cette banque (triés par nom)
     * 
     * @example
     * $comptesBNP = Compte::getByBanque($bnpId);
     * echo "La BNP a " . count($comptesBNP) . " comptes";
     */
    public static function getByBanque(int $banqueId): array
    {
        $sql = "SELECT * FROM " . static::$table . " 
                WHERE banque_id = ? 
                ORDER BY nom ASC";
        
        return Database::select($sql, [$banqueId]);
    }
    
    /**
     * Récupère uniquement les comptes actifs (non archivés)
     * 
     * Join avec la table 'banques' pour enrichir les données.
     * Retourne seulement les comptes où actif = 1.
     * 
     * @return array Liste des comptes actifs avec nom de la banque
     * 
     * @example
     * $comptesActifs = Compte::getActifs();
     * foreach ($comptesActifs as $compte) {
     *     echo "{$compte['nom']} : {$compte['solde_actuel']} €\n";
     * }
     */
    public static function getActifs(): array
    {
        $sql = "SELECT c.*, b.nom as banque_nom 
                FROM " . static::$table . " c
                LEFT JOIN banques b ON c.banque_id = b.id
                WHERE c.actif = 1 
                ORDER BY c.nom ASC";
        
        return Database::select($sql);
    }
    
    /**
     * Crée un nouveau compte en base de données
     * 
     * Insère uniquement les champs définis dans $fillable qui sont présents dans $data.
     * Génère dynamiquement la requête SQL d'insertion.
     * 
     * ⚠️ PCI DSS: Chiffre automatiquement l'IBAN avant insertion.
     * 
     * @param array $data Données du compte à créer (clés = noms de colonnes)
     *                    - int $data['user_id'] ID utilisateur (requis)
     *                    - int $data['banque_id'] ID banque (requis)
     *                    - string $data['nom'] Nom du compte (requis)
     *                    - string $data['type_compte'] Type de compte (requis)
     *                    - float $data['solde_initial'] Solde initial (défaut: 0)
     *                    - float $data['solde_actuel'] Solde actuel (défaut: = solde_initial)
     *                    - autres champs optionnels...
     * @return int ID du compte créé
     * 
     * @throws \PDOException Si l'insertion échoue
     * 
     * @example
     * $compteId = Compte::create([
     *     'user_id' => $_SESSION['user']['id'],
     *     'banque_id' => 3,
     *     'nom' => 'Compte Courant',
     *     'type_compte' => 'courant',
     *     'iban' => 'FR7630006000011234567890189',  // Sera chiffré automatiquement
     *     'solde_initial' => 2500.00,
     *     'solde_actuel' => 2500.00,
     *     'devise' => 'EUR',
     *     'actif' => 1
     * ]);
     */
    public static function create(array $data): int
    {
        // Chiffrer IBAN si présent (PCI DSS Exigence 3)
        if (isset($data['iban'])) {
            $data['iban'] = static::encryptIban($data['iban']);
        }
        
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
     * Met à jour un compte existant
     * 
     * Met à jour uniquement les champs définis dans $fillable qui sont présents dans $data.
     * Génère dynamiquement la clause SET de la requête UPDATE.
     * 
     * ⚠️ PCI DSS: Chiffre automatiquement l'IBAN avant mise à jour.
     * 
     * @param int $id ID du compte à mettre à jour
     * @param array $data Données à modifier (clés = noms de colonnes)
     * @return int Nombre de lignes affectées (0 si aucun changement, 1 si mise à jour)
     * 
     * @example
     * Compte::update($compteId, [
     *     'nom' => 'Compte Courant Principal',
     *     'solde_actuel' => 3250.50
     * ]);
     */
    public static function update(int $id, array $data): int
    {
        // Chiffrer IBAN si présent (PCI DSS Exigence 3)
        if (isset($data['iban'])) {
            $data['iban'] = static::encryptIban($data['iban']);
        }
        
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
     * Supprime un compte de la base de données
     * 
     * ⚠️ ATTENTION : La suppression est définitive.
     * TODO: Vérifier et gérer les transactions associées avant suppression.
     * 
     * @param int $id ID du compte à supprimer
     * @return int Nombre de lignes supprimées (0 ou 1)
     * 
     * @example
     * if (Compte::delete($compteId)) {
     *     echo "Compte supprimé avec succès";
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
     * Met à jour uniquement le solde actuel d'un compte
     * 
     * Méthode de mise à jour rapide du solde sans toucher aux autres champs.
     * Retourne toujours true (même si le solde est déjà correct).
     * 
     * @param int $id ID du compte
     * @param float $nouveauSolde Nouveau solde à enregistrer
     * @return bool Toujours true (opération réussie ou solde déjà correct)
     * 
     * @example
     * Compte::updateSolde($compteId, 5136.69);
     */
    public static function updateSolde(int $id, float $nouveauSolde): bool
    {
        $sql = "UPDATE " . static::$table . " SET solde_actuel = ? WHERE id = ?";
        // Retourner true même si 0 lignes affectées (solde déjà correct)
        Database::update($sql, [$nouveauSolde, $id]);
        return true;
    }
    
    /**
     * Recalcule et met à jour le solde actuel basé sur toutes les transactions
     * 
     * Approche sécurisée avec ABS() : fonctionne que les débits soient stockés
     * en positif ou négatif dans la base de données.
     * 
     * Formule : solde_actuel = solde_initial + SUM(crédits) - SUM(débits)
     * 
     * Cette méthode garantit la cohérence entre le solde stocké et les transactions,
     * notamment après import de fichiers bancaires ou corrections manuelles.
     * 
     * @param int $id ID du compte à recalculer
     * @return bool True si le recalcul a réussi, false si le compte n'existe pas
     * 
     * @example
     * // Après import de transactions
     * if (Compte::recalculerSolde($compteId)) {
     *     echo "Solde recalculé avec succès";
     * }
     * 
     * @see updateSolde()
     */
    public static function recalculerSolde(int $id): bool
    {
        // Récupérer le compte
        $compte = static::find($id);
        if (!$compte) {
            return false;
        }
        
        // Calculer les totaux séparément avec abs() pour sécuriser le calcul
        $sql = "SELECT 
                    COALESCE(SUM(CASE WHEN type_operation = 'credit' THEN ABS(montant) ELSE 0 END), 0) as total_credits,
                    COALESCE(SUM(CASE WHEN type_operation = 'debit' THEN ABS(montant) ELSE 0 END), 0) as total_debits
                FROM transactions
                WHERE compte_id = ?";
        
        $result = Database::selectOne($sql, [$id]);
        
        // Formule sécurisée : solde_initial + crédits - débits
        $soldeCalcule = $compte['solde_initial'] + $result['total_credits'] - $result['total_debits'];
        
        return static::updateSolde($id, $soldeCalcule);
    }
    
    /**
     * Calcule le solde total de tous les comptes actifs
     * 
     * Somme les soldes actuels de tous les comptes où actif = 1.
     * Utile pour afficher le patrimoine total de l'utilisateur.
     * 
     * @return float Somme des soldes de tous les comptes actifs
     * 
     * @example
     * $patrimoine = Compte::getSoldeTotal();
     * echo "Votre patrimoine total : " . number_format($patrimoine, 2) . " €";
     */
    public static function getSoldeTotal(): float
    {
        $result = Database::selectOne(
            "SELECT SUM(solde_actuel) as total FROM " . static::$table . " WHERE actif = 1"
        );
        
        return (float) ($result['total'] ?? 0);
    }
    
    /**
     * Recherche des comptes par mots-clés
     * 
     * Recherche dans les champs : nom du compte, numéro de compte, IBAN et nom de la banque.
     * La recherche est insensible à la casse et supporte les correspondances partielles (LIKE %query%).
     * 
     * @param string $query Terme de recherche
     * @return array Liste des comptes correspondants avec infos banque
     * 
     * @example
     * $resultats = Compte::search('épargne');
     * foreach ($resultats as $compte) {
     *     echo "{$compte['nom']} - {$compte['banque_nom']}\n";
     * }
     */
    public static function search(string $query): array
    {
        $searchTerm = "%{$query}%";
        
        $sql = "SELECT c.*, b.nom as banque_nom 
                FROM " . static::$table . " c
                LEFT JOIN banques b ON c.banque_id = b.id
                WHERE c.nom LIKE ? 
                   OR c.numero_compte LIKE ? 
                   OR c.iban LIKE ?
                   OR b.nom LIKE ?
                ORDER BY c.nom ASC";
        
        return Database::select($sql, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }
    
    /**
     * Vérifie si un nom de compte existe déjà
     * 
     * Utile lors de la création/modification pour garantir l'unicité des noms.
     * Permet d'exclure le compte en cours de modification via $excludeId.
     * 
     * @param string $nom Nom du compte à vérifier
     * @param int|null $excludeId ID du compte à exclure de la vérification (pour édition)
     * @return bool True si le nom existe déjà, false sinon
     * 
     * @example
     * // Lors de la création
     * if (Compte::nomExists($_POST['nom'])) {
     *     throw new \Exception("Ce nom de compte existe déjà");
     * }
     * 
     * // Lors de l'édition (exclure le compte actuel)
     * if (Compte::nomExists($_POST['nom'], $compteId)) {
     *     throw new \Exception("Ce nom est déjà utilisé par un autre compte");
     * }
     */
    public static function nomExists(string $nom, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) as count FROM " . static::$table . " WHERE nom = ?";
        $params = [$nom];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $result = Database::selectOne($sql, $params);
        return $result['count'] > 0;
    }
    
    /**
     * Récupère la liste des titulaires d'un compte avec leurs rôles
     * 
     * Join avec les tables 'titulaires' et 'compte_titulaires'.
     * Retourne les titulaires avec leur rôle (titulaire, co-titulaire, etc.)
     * et leur ordre d'affichage.
     * 
     * @param int $compteId ID du compte
     * @return array Liste des titulaires avec leurs infos et rôles (triés par ordre)
     * 
     * @example
     * $titulaires = Compte::getTitulaires($compteId);
     * foreach ($titulaires as $titulaire) {
     *     echo "{$titulaire['prenom']} {$titulaire['nom']} - {$titulaire['role']}\n";
     * }
     */
    public static function getTitulaires(int $compteId): array
    {
        $sql = "SELECT t.*, ct.role, ct.ordre 
                FROM titulaires t
                INNER JOIN compte_titulaires ct ON t.id = ct.titulaire_id
                WHERE ct.compte_id = ?
                ORDER BY ct.ordre ASC";
        
        return Database::select($sql, [$compteId]);
    }
    
    /**
     * Récupère un compte avec toutes les infos de la banque ET ses titulaires
     * 
     * Combine findWithBanque() et getTitulaires() pour obtenir un objet compte complet.
     * Très utile pour l'affichage des détails complets d'un compte.
     * 
     * @param int $id ID du compte
     * @return array|null Données du compte avec banque et titulaires, ou null si non trouvé
     * 
     * @example
     * $compte = Compte::findWithBanqueAndTitulaires($compteId);
     * if ($compte) {
     *     echo "Compte : {$compte['nom']}\n";
     *     echo "Banque : {$compte['banque_nom']}\n";
     *     echo "Titulaires : " . count($compte['titulaires']) . "\n";
     *     foreach ($compte['titulaires'] as $titulaire) {
     *         echo "  - {$titulaire['prenom']} {$titulaire['nom']} ({$titulaire['role']})\n";
     *     }
     * }
     * 
     * @see findWithBanque()
     * @see getTitulaires()
     */
    public static function findWithBanqueAndTitulaires(int $id): ?array
    {
        $compte = static::findWithBanque($id);
        if (!$compte) {
            return null;
        }
        
        $compte['titulaires'] = static::getTitulaires($id);
        return $compte;
    }
    
    /**
     * Chiffre un IBAN avant sauvegarde
     * 
     * Utilise EncryptionService pour chiffrer l'IBAN selon PCI DSS Exigence 3.
     * Retourne null si aucun IBAN fourni.
     * 
     * @param string|null $iban IBAN en clair
     * @return string|null IBAN chiffré en base64 ou null
     */
    private static function encryptIban(?string $iban): ?string
    {
        if (empty($iban)) {
            return null;
        }
        
        try {
            $encryption = new EncryptionService();
            return $encryption->encryptIBAN($iban);
        } catch (\Exception $e) {
            error_log("Erreur chiffrement IBAN: " . $e->getMessage());
            // En cas d'erreur, on stocke en clair (fallback)
            // TODO: En production, lever une exception
            return $iban;
        }
    }
    
    /**
     * Déchiffre un IBAN depuis la base de données
     * 
     * Utilise EncryptionService pour déchiffrer l'IBAN.
     * Retourne l'IBAN en clair ou masqué selon le paramètre.
     * 
     * @param string|null $encryptedIban IBAN chiffré en base64
     * @param bool $masked Si true, retourne IBAN masqué (FR** **** **89)
     * @return string|null IBAN déchiffré ou masqué, ou null
     */
    public static function decryptIban(?string $encryptedIban, bool $masked = false): ?string
    {
        if (empty($encryptedIban)) {
            return null;
        }
        
        try {
            $encryption = new EncryptionService();
            
            // Si déjà déchiffré (legacy data), retourner tel quel ou masquer
            if (!$encryption->isEncrypted($encryptedIban)) {
                return $masked ? $encryption->maskIBAN($encryptedIban, false) : $encryptedIban;
            }
            
            return $masked 
                ? $encryption->maskIBAN($encryptedIban, true)
                : $encryption->decryptIBAN($encryptedIban);
        } catch (\Exception $e) {
            error_log("Erreur déchiffrement IBAN: " . $e->getMessage());
            return $masked ? 'FR** **** ****' : null;
        }
    }
}
