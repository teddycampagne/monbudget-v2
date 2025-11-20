<?php

namespace MonBudget\Services;

use Exception;

/**
 * Service de chiffrement AES-256-GCM
 * 
 * Impl\u00e9mente le chiffrement des donn\u00e9es sensibles (IBAN, comptes bancaires)
 * conforme aux standards PCI DSS Exigence 3.
 * 
 * Utilise AES-256 en mode GCM (Galois/Counter Mode) pour :
 * - Chiffrement authentifi\u00e9 (int\u00e9grit\u00e9 + confidentialit\u00e9)
 * - Protection contre alteration
 * - Performance \u00e9lev\u00e9e
 * 
 * @package MonBudget\Services
 * @author MonBudget Security Team
 * @version 1.0.0
 */
class EncryptionService
{
    /**
     * Algorithme de chiffrement (AES-256-GCM)
     * @var string
     */
    private const CIPHER = 'aes-256-gcm';
    
    /**
     * Cl\u00e9 de chiffrement (32 octets pour AES-256)
     * @var string
     */
    private string $key;
    
    /**
     * Longueur du tag d'authentification GCM (16 octets)
     * @var int
     */
    private const TAG_LENGTH = 16;
    
    /**
     * Constructeur
     * 
     * R\u00e9cup\u00e8re la cl\u00e9 de chiffrement depuis la variable d'environnement.
     * Lance une exception si la cl\u00e9 est absente ou invalide.
     * 
     * @throws Exception Si cl\u00e9 de chiffrement absente ou invalide
     */
    public function __construct()
    {
        $this->key = getenv('ENCRYPTION_KEY') ?: '';
        
        if (empty($this->key)) {
            throw new Exception(
                'Cl\u00e9 de chiffrement absente. ' .
                'D\u00e9finir ENCRYPTION_KEY dans .env'
            );
        }
        
        // V\u00e9rifier longueur cl\u00e9 (32 octets = 256 bits)
        $decodedKey = base64_decode($this->key, true);
        if ($decodedKey === false || strlen($decodedKey) !== 32) {
            throw new Exception(
                'Cl\u00e9 de chiffrement invalide. ' .
                'G\u00e9n\u00e9rer avec: php -r "echo base64_encode(openssl_random_pseudo_bytes(32));"'
            );
        }
        
        $this->key = $decodedKey;
    }
    
    /**
     * Chiffre une donn\u00e9e sensible
     * 
     * Processus :
     * 1. G\u00e9n\u00e8re un IV al\u00e9atoire unique
     * 2. Chiffre avec AES-256-GCM
     * 3. G\u00e9n\u00e8re un tag d'authentification
     * 4. Concat\u00e8ne IV + tag + donn\u00e9es chiffr\u00e9es
     * 5. Encode en base64 pour stockage BDD
     * 
     * @param string $data Donn\u00e9e en clair \u00e0 chiffrer (IBAN, num\u00e9ro compte, etc.)
     * @return string Donn\u00e9e chiffr\u00e9e encod\u00e9e en base64
     * @throws Exception Si chiffrement \u00e9choue
     */
    public function encrypt(string $data): string
    {
        // Valider entr\u00e9e
        if (empty($data)) {
            throw new Exception('Donn\u00e9e vide \u00e0 chiffrer');
        }
        
        // G\u00e9n\u00e9rer IV al\u00e9atoire unique
        $ivLength = openssl_cipher_iv_length(self::CIPHER);
        if ($ivLength === false) {
            throw new Exception('Impossible de d\u00e9terminer longueur IV');
        }
        
        $iv = openssl_random_pseudo_bytes($ivLength, $strong);
        if (!$strong) {
            throw new Exception('G\u00e9n\u00e9ration IV non s\u00e9curis\u00e9e');
        }
        
        // Chiffrer avec tag d'authentification
        $tag = '';
        $encrypted = openssl_encrypt(
            $data,
            self::CIPHER,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',  // AAD (Additional Authenticated Data) - optionnel
            self::TAG_LENGTH
        );
        
        if ($encrypted === false) {
            throw new Exception('Chiffrement \u00e9chou\u00e9: ' . openssl_error_string());
        }
        
        // Concat\u00e9ner: IV + tag + donn\u00e9es chiffr\u00e9es
        $result = $iv . $tag . $encrypted;
        
        // Encoder en base64 pour stockage BDD
        return base64_encode($result);
    }
    
    /**
     * D\u00e9chiffre une donn\u00e9e sensible
     * 
     * Processus :
     * 1. D\u00e9code base64
     * 2. Extrait IV, tag, donn\u00e9es chiffr\u00e9es
     * 3. D\u00e9chiffre avec AES-256-GCM
     * 4. V\u00e9rifie tag d'authentification
     * 5. Retourne donn\u00e9e en clair
     * 
     * @param string $encryptedData Donn\u00e9e chiffr\u00e9e (format base64)
     * @return string Donn\u00e9e d\u00e9chiffr\u00e9e en clair
     * @throws Exception Si d\u00e9chiffrement \u00e9choue ou donn\u00e9e alt\u00e9r\u00e9e
     */
    public function decrypt(string $encryptedData): string
    {
        // Valider entr\u00e9e
        if (empty($encryptedData)) {
            throw new Exception('Donn\u00e9e vide \u00e0 d\u00e9chiffrer');
        }
        
        // D\u00e9coder base64
        $decoded = base64_decode($encryptedData, true);
        if ($decoded === false) {
            throw new Exception('Donn\u00e9e chiffr\u00e9e invalide (base64 corrompu)');
        }
        
        // Extraire IV
        $ivLength = openssl_cipher_iv_length(self::CIPHER);
        if ($ivLength === false) {
            throw new Exception('Impossible de d\u00e9terminer longueur IV');
        }
        
        if (strlen($decoded) < $ivLength + self::TAG_LENGTH) {
            throw new Exception('Donn\u00e9e chiffr\u00e9e trop courte (corrompu)');
        }
        
        $iv = substr($decoded, 0, $ivLength);
        $tag = substr($decoded, $ivLength, self::TAG_LENGTH);
        $ciphertext = substr($decoded, $ivLength + self::TAG_LENGTH);
        
        // D\u00e9chiffrer avec v\u00e9rification tag
        $decrypted = openssl_decrypt(
            $ciphertext,
            self::CIPHER,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
        
        if ($decrypted === false) {
            throw new Exception(
                'D\u00e9chiffrement \u00e9chou\u00e9 (donn\u00e9e alt\u00e9r\u00e9e ou cl\u00e9 incorrecte): ' .
                openssl_error_string()
            );
        }
        
        return $decrypted;
    }
    
    /**
     * Chiffre uniquement si la donn\u00e9e n'est pas d\u00e9j\u00e0 chiffr\u00e9e
     * 
     * Utile lors de migrations pour \u00e9viter double chiffrement.
     * D\u00e9tecte si donn\u00e9e d\u00e9j\u00e0 chiffr\u00e9e (base64 valide + longueur correcte).
     * 
     * @param string|null $data Donn\u00e9e \u00e0 chiffrer (potentiellement d\u00e9j\u00e0 chiffr\u00e9e)
     * @return string|null Donn\u00e9e chiffr\u00e9e ou null si entr\u00e9e null
     */
    public function encryptIfNeeded(?string $data): ?string
    {
        if ($data === null || $data === '') {
            return null;
        }
        
        // Tenter d\u00e9chiffrement pour d\u00e9tecter si d\u00e9j\u00e0 chiffr\u00e9
        try {
            $this->decrypt($data);
            // Si succ\u00e8s, d\u00e9j\u00e0 chiffr\u00e9
            return $data;
        } catch (Exception $e) {
            // Pas chiffr\u00e9, chiffrer maintenant
            return $this->encrypt($data);
        }
    }
    
    /**
     * D\u00e9chiffre un tableau de donn\u00e9es sensibles
     * 
     * Utile pour d\u00e9chiffrer plusieurs champs d'un enregistrement BDD.
     * 
     * @param array $data Tableau avec cl\u00e9s/valeurs chiffr\u00e9es
     * @param array $fields Noms des champs \u00e0 d\u00e9chiffrer
     * @return array Tableau avec champs d\u00e9chiffr\u00e9s
     */
    public function decryptFields(array $data, array $fields): array
    {
        foreach ($fields as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                try {
                    $data[$field] = $this->decrypt($data[$field]);
                } catch (Exception $e) {
                    // Si d\u00e9chiffrement \u00e9choue, conserver valeur originale
                    // (donn\u00e9e peut ne pas \u00eatre chiffr\u00e9e lors de migration)
                    error_log("Erreur d\u00e9chiffrement champ {$field}: " . $e->getMessage());
                }
            }
        }
        
        return $data;
    }
    
    /**
     * Chiffre un tableau de donn\u00e9es sensibles
     * 
     * Utile avant INSERT/UPDATE en BDD.
     * 
     * @param array $data Tableau avec cl\u00e9s/valeurs en clair
     * @param array $fields Noms des champs \u00e0 chiffrer
     * @return array Tableau avec champs chiffr\u00e9s
     */
    public function encryptFields(array $data, array $fields): array
    {
        foreach ($fields as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                $data[$field] = $this->encrypt($data[$field]);
            }
        }
        
        return $data;
    }
    
    /**
     * G\u00e9n\u00e8re une nouvelle cl\u00e9 de chiffrement s\u00e9curis\u00e9e
     * 
     * Utile lors de l'installation initiale ou rotation de cl\u00e9s.
     * 
     * @return string Cl\u00e9 encod\u00e9e en base64 (32 octets)
     */
    public static function generateKey(): string
    {
        $key = openssl_random_pseudo_bytes(32, $strong);
        
        if (!$strong) {
            throw new Exception('G\u00e9n\u00e9ration cl\u00e9 non s\u00e9curis\u00e9e');
        }
        
        return base64_encode($key);
    }
    
    /**
     * Valide qu'une cl\u00e9 de chiffrement est correcte
     * 
     * @param string $key Cl\u00e9 encod\u00e9e en base64
     * @return bool True si cl\u00e9 valide
     */
    public static function validateKey(string $key): bool
    {
        $decoded = base64_decode($key, true);
        return $decoded !== false && strlen($decoded) === 32;
    }
    
    /**
     * Hash un IBAN pour recherche/comparaison sans d\u00e9chiffrement
     * 
     * Permet de chercher un IBAN en BDD sans stocker en clair.
     * Utilise HMAC-SHA256 pour hash d\u00e9terministe.
     * 
     * @param string $iban IBAN en clair
     * @return string Hash de l'IBAN (hex)
     */
    public function hashIban(string $iban): string
    {
        return hash_hmac('sha256', $iban, $this->key);
    }
}
