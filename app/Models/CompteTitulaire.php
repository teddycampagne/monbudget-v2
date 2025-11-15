<?php

namespace MonBudget\Models;

use MonBudget\Core\Database;

/**
 * Class CompteTitulaire
 * 
 * Modèle de gestion des associations compte-titulaire (table pivot).
 * 
 * Gère la relation many-to-many entre comptes et titulaires :
 * - Un compte peut avoir plusieurs titulaires (compte joint)
 * - Un titulaire peut posséder plusieurs comptes
 * 
 * Stocke des métadonnées :
 * - Role : 'titulaire' (principal) ou 'co-titulaire' (secondaire)
 * - Ordre : ordre d'affichage (1, 2, 3...)
 * 
 * @package MonBudget\Models
 * 
 * @property int $compte_id ID du compte
 * @property int $titulaire_id ID du titulaire
 * @property string $role Role (titulaire, co-titulaire)
 * @property int $ordre Ordre d'affichage (1 = principal)
 */
class CompteTitulaire
{
    /**
     * Lie un titulaire à un compte
     * 
     * Crée une association avec rôle et ordre d'affichage.
     * 
     * @param int $compteId ID du compte
     * @param int $titulaireId ID du titulaire
     * @param string $role Role ('titulaire' ou 'co-titulaire', défaut: 'titulaire')
     * @param int $ordre Ordre d'affichage (défaut: 1)
     * @return int ID de l'association créée
     * 
     * @example
     * // Titulaire principal
     * CompteTitulaire::attach($compteId, $titulaire1Id, 'titulaire', 1);
     * 
     * // Co-titulaire (compte joint)
     * CompteTitulaire::attach($compteId, $titulaire2Id, 'co-titulaire', 2);
     * 
     * @see sync()
     */
    public static function attach(int $compteId, int $titulaireId, string $role = 'titulaire', int $ordre = 1): int
    {
        return Database::insert(
            "INSERT INTO compte_titulaires (compte_id, titulaire_id, role, ordre) 
             VALUES (?, ?, ?, ?)",
            [$compteId, $titulaireId, $role, $ordre]
        );
    }
    
    /**
     * Détache un titulaire d'un compte
     * 
     * Supprime l'association entre un compte et un titulaire.
     * 
     * @param int $compteId ID du compte
     * @param int $titulaireId ID du titulaire
     * @return bool True si suppression réussie, false sinon
     * 
     * @example
     * CompteTitulaire::detach($compteId, $titulaire2Id);
     * // Retire le co-titulaire du compte joint
     * 
     * @see detachAll()
     */
    public static function detach(int $compteId, int $titulaireId): bool
    {
        return Database::delete(
            "DELETE FROM compte_titulaires WHERE compte_id = ? AND titulaire_id = ?",
            [$compteId, $titulaireId]
        );
    }
    
    /**
     * Détache tous les titulaires d'un compte
     * 
     * Supprime toutes les associations d'un compte.
     * Utilisé avant suppression de compte ou avant sync().
     * 
     * @param int $compteId ID du compte
     * @return bool True si suppression réussie, false sinon
     * 
     * @example
     * CompteTitulaire::detachAll($compteId);
     * // Retire tous les titulaires avant de réassigner
     * 
     * @see sync()
     */
    public static function detachAll(int $compteId): bool
    {
        return Database::delete(
            "DELETE FROM compte_titulaires WHERE compte_id = ?",
            [$compteId]
        );
    }
    
    /**
     * Récupère tous les titulaires associés à un compte
     * 
     * Jointure avec la table titulaires pour récupérer toutes les informations.
     * Tri par ordre d'affichage puis par ID.
     * 
     * @param int $compteId ID du compte
     * @return array Liste des titulaires avec role et ordre
     * 
     * @example
     * $titulaires = CompteTitulaire::getTitulairesByCompte($compteId);
     * foreach ($titulaires as $titulaire) {
     *     echo "{$titulaire['prenom']} {$titulaire['nom']} ({$titulaire['role']})";
     * }
     * 
     * @see attach()
     */
    public static function getTitulairesByCompte(int $compteId): array
    {
        return Database::select(
            "SELECT t.*, ct.role, ct.ordre
             FROM titulaires t
             INNER JOIN compte_titulaires ct ON t.id = ct.titulaire_id
             WHERE ct.compte_id = ?
             ORDER BY ct.ordre ASC",
            [$compteId]
        );
    }
    
    /**
     * Met à jour le rôle d'un titulaire sur un compte
     * 
     * Modifie uniquement le rôle (titulaire → co-titulaire ou inverse).
     * 
     * @param int $compteId ID du compte
     * @param int $titulaireId ID du titulaire
     * @param string $role Nouveau rôle ('titulaire' ou 'co-titulaire')
     * @return int Nombre de lignes affectées (0 ou 1)
     * 
     * @example
     * CompteTitulaire::updateRole($compteId, $titulaireId, 'co-titulaire');
     * // Passe le titulaire principal en co-titulaire
     */
    public static function updateRole(int $compteId, int $titulaireId, string $role): int
    {
        return Database::update(
            "UPDATE compte_titulaires SET role = ? WHERE compte_id = ? AND titulaire_id = ?",
            [$role, $compteId, $titulaireId]
        );
    }
    
    /**
     * Synchronise les titulaires d'un compte
     * 
     * Remplace toutes les associations existantes par une nouvelle liste.
     * Méthode pratique pour mettre à jour en une seule opération.
     * 
     * Processus :
     * 1. Supprime toutes les associations existantes
     * 2. Recrée les associations avec les nouveaux titulaires
     * 3. Assigne automatiquement l'ordre (1, 2, 3...)
     * 
     * @param int $compteId ID du compte
     * @param array $titulaires Liste [['id' => int, 'role' => string], ...]
     * @return void
     * 
     * @example
     * CompteTitulaire::sync($compteId, [
     *     ['id' => 1, 'role' => 'titulaire'],
     *     ['id' => 2, 'role' => 'co-titulaire']
     * ]);
     * // Configure un compte joint avec 2 titulaires
     * 
     * @see attach()
     * @see detachAll()
     */
    public static function sync(int $compteId, array $titulaires): void
    {
        // Supprimer toutes les liaisons existantes
        static::detachAll($compteId);
        
        // Recréer les liaisons
        $ordre = 1;
        foreach ($titulaires as $titulaire) {
            if (!empty($titulaire['id'])) {
                $role = $titulaire['role'] ?? 'titulaire';
                static::attach($compteId, $titulaire['id'], $role, $ordre);
                $ordre++;
            }
        }
    }
}
