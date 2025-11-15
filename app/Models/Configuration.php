<?php

namespace MonBudget\Models;

/**
 * Class Configuration
 * 
 * Modèle de gestion de la configuration système (clé-valeur).
 * 
 * Permet de stocker et récupérer des paramètres globaux de l'application :
 * - Nom et version de l'application
 * - Devise et format de date
 * - Timezone
 * - Pagination
 * - Permissions (inscription autorisée)
 * - Thème
 * 
 * Les valeurs complexes (tableaux, objets) sont automatiquement encodées en JSON.
 * 
 * @package MonBudget\Models
 * @extends BaseModel
 * 
 * @property int $id Identifiant unique
 * @property string $cle Clé de configuration (unique)
 * @property string $valeur Valeur (texte brut ou JSON)
 * @property string|null $created_at Date de création
 * @property string|null $updated_at Date de modification
 */
class Configuration extends BaseModel
{
    /** @var string Table de la base de données */
    protected static string $table = 'configuration';
    
    /** @var string Clé primaire */
    protected static string $primaryKey = 'id';
    
    /**
     * Récupère une valeur de configuration
     * 
     * Retourne la valeur décodée (JSON → array) ou brute (string).
     * Si la clé n'existe pas, retourne la valeur par défaut.
     * 
     * @param string $key Clé de configuration
     * @param mixed $default Valeur par défaut si clé inexistante (défaut: null)
     * @return mixed Valeur décodée ou valeur par défaut
     * 
     * @example
     * $currency = Configuration::get('currency', 'EUR');
     * // Retourne 'EUR' si défini, sinon 'EUR' par défaut
     * 
     * $pagination = Configuration::get('items_per_page', 25);
     * // Retourne 25 si défini, sinon 25 par défaut
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $result = self::where(['cle' => $key]);
        
        if (empty($result)) {
            return $default;
        }
        
        $value = $result[0]['valeur'] ?? $default;
        
        // Tenter de décoder le JSON
        $decoded = json_decode($value, true);
        return $decoded !== null ? $decoded : $value;
    }
    
    /**
     * Définit une valeur de configuration
     * 
     * Crée la configuration si elle n'existe pas, sinon met à jour.
     * Les valeurs complexes (array, object) sont automatiquement encodées en JSON.
     * 
     * @param string $key Clé de configuration
     * @param mixed $value Valeur à stocker (string, int, bool, array, object)
     * @return bool True si création/modification réussie, false sinon
     * 
     * @example
     * Configuration::set('currency', 'EUR');
     * Configuration::set('items_per_page', 50);
     * Configuration::set('features', ['import', 'export', 'budgets']);
     * // Tableau encodé en JSON automatiquement
     */
    public static function set(string $key, mixed $value): bool
    {
        // Encoder en JSON si nécessaire
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value);
        }
        
        $existing = self::where(['cle' => $key]);
        
        if (empty($existing)) {
            // Créer
            return self::create([
                'cle' => $key,
                'valeur' => $value,
                'created_at' => date('Y-m-d H:i:s')
            ]) > 0;
        } else {
            // Mettre à jour
            return self::update($existing[0]['id'], [
                'valeur' => $value,
                'updated_at' => date('Y-m-d H:i:s')
            ]) >= 0;
        }
    }
    
    /**
     * Supprime une configuration
     * 
     * @param string $key Clé de configuration à supprimer
     * @return bool True si suppression réussie, false si clé inexistante
     * 
     * @example
     * Configuration::remove('old_setting');
     */
    public static function remove(string $key): bool
    {
        $existing = self::where(['cle' => $key]);
        
        if (!empty($existing)) {
            return self::delete($existing[0]['id']) > 0;
        }
        
        return false;
    }
    
    /**
     * Récupère toutes les configurations sous forme de tableau associatif
     * 
     * Format : ['cle' => valeur_decodee, ...]
     * Les valeurs JSON sont automatiquement décodées.
     * 
     * @return array Tableau associatif [clé => valeur]
     * 
     * @example
     * $settings = Configuration::getAllSettings();
     * // [
     * //   'currency' => 'EUR',
     * //   'items_per_page' => 25,
     * //   'features' => ['import', 'export', 'budgets']
     * // ]
     */
    public static function getAllSettings(): array
    {
        $results = self::all();
        $settings = [];
        
        foreach ($results as $result) {
            $value = $result['valeur'];
            $decoded = json_decode($value, true);
            $settings[$result['cle']] = $decoded !== null ? $decoded : $value;
        }
        
        return $settings;
    }
    
    /**
     * Initialise les configurations par défaut si non existantes
     * 
     * Crée les paramètres de base pour une installation vierge.
     * N'écrase PAS les configurations existantes.
     * 
     * Configurations par défaut :
     * - app_name : 'MonBudget v2.0'
     * - app_version : '2.0.0'
     * - currency : 'EUR'
     * - currency_symbol : '€'
     * - date_format : 'd/m/Y'
     * - timezone : 'Europe/Paris'
     * - items_per_page : 25
     * - allow_registration : false
     * - theme : 'light'
     * 
     * @return void
     * 
     * @example
     * // Lors de l'installation initiale
     * Configuration::initializeDefaults();
     * 
     * @see set()
     */
    public static function initializeDefaults(): void
    {
        $defaults = [
            'app_name' => 'MonBudget v2.0',
            'app_version' => '2.0.0',
            'currency' => 'EUR',
            'currency_symbol' => '€',
            'date_format' => 'd/m/Y',
            'timezone' => 'Europe/Paris',
            'items_per_page' => 25,
            'allow_registration' => false,
            'theme' => 'light'
        ];
        
        foreach ($defaults as $key => $value) {
            if (self::get($key) === null) {
                self::set($key, $value);
            }
        }
    }
}
