<?php

namespace MonBudget\Models;

use MonBudget\Core\Database;

/**
 * Class Import
 * 
 * Modèle de gestion des imports de transactions bancaires.
 * 
 * Supporte les formats :
 * - CSV : avec détection automatique du délimiteur et de l'encodage
 * - OFX : format standard bancaire (Open Financial Exchange)
 * 
 * Fonctionnalités :
 * - Parsing avec conversion d'encodage automatique (UTF-8, ISO-8859-1, Windows-1252)
 * - Détection de doublons par date, montant et libellé
 * - Application automatique des règles de catégorisation
 * - Suivi des statistiques d'import (réussites, erreurs, doublons)
 * - Gestion du mapping de colonnes pour CSV personnalisés
 * 
 * @package MonBudget\Models
 * @extends BaseModel
 * 
 * @property int $id Identifiant unique de l'import
 * @property int $user_id ID de l'utilisateur propriétaire
 * @property int $compte_id ID du compte cible
 * @property string $nom_fichier Nom du fichier original
 * @property string $format Format (csv, ofx)
 * @property int $nb_lignes_total Nombre total de lignes traitées
 * @property int $nb_lignes_importees Nombre de transactions créées
 * @property int $nb_lignes_ignorees Nombre de lignes ignorées (doublons + erreurs)
 * @property string $statut Statut (en_cours, termine, erreur)
 * @property string $created_at Date de création
 */
class Import extends BaseModel
{
    /** @var string Table de la base de données */
    protected static string $table = 'imports';
    
    /**
     * Parse un fichier CSV avec détection et conversion d'encodage
     * 
     * Convertit automatiquement de ISO-8859-1 ou Windows-1252 vers UTF-8.
     * Ignore les lignes vides au début du fichier.
     * 
     * @param string $filePath Chemin complet du fichier CSV
     * @param string $delimiter Séparateur (défaut: ';'). Utiliser detectDelimiter() pour auto-détection
     * @return array Tableau de lignes, chaque ligne étant un tableau de valeurs
     * 
     * @example
     * $delimiter = Import::detectDelimiter($filePath);
     * $rows = Import::parseCSV($filePath, $delimiter);
     * // $rows[0] = ['Date', 'Libellé', 'Montant'] (entêtes)
     * // $rows[1] = ['01/12/2024', 'Courses', '-45.50']
     * 
     * @see detectDelimiter()
     */
    public static function parseCSV(string $filePath, string $delimiter = ';'): array
    {
        $rows = [];
        
        // Détecter et convertir l'encodage
        $content = file_get_contents($filePath);
        $encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
        if ($encoding && $encoding !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
            file_put_contents($filePath, $content);
        }
        
        if (($handle = fopen($filePath, 'r')) !== false) {
            while (($data = fgetcsv($handle, 10000, $delimiter)) !== false) {
                // Nettoyer les valeurs vides
                $data = array_map(function($val) {
                    return ($val === '' || $val === null) ? null : trim($val);
                }, $data);
                $rows[] = $data;
            }
            fclose($handle);
        }
        
        // Ignorer les lignes vides au début
        while (!empty($rows) && empty(array_filter($rows[0]))) {
            array_shift($rows);
        }
        
        return $rows;
    }
    
    /**
     * Détecte automatiquement le délimiteur utilisé dans un fichier CSV
     * 
     * Analyse la première ligne du fichier et compte les occurrences de chaque délimiteur.
     * Teste : point-virgule (;), virgule (,), tabulation (\t) et pipe (|).
     * 
     * @param string $filePath Chemin complet du fichier CSV
     * @return string Délimiteur détecté (';' par défaut si aucun trouvé)
     * 
     * @example
     * $delimiter = Import::detectDelimiter('/uploads/export.csv');
     * // Retourne ';' pour fichiers Excel français
     * // Retourne ',' pour fichiers CSV anglo-saxons
     */
    public static function detectDelimiter(string $filePath): string
    {
        $delimiters = [';', ',', "\t", '|'];
        $counts = [];
        
        if (($handle = fopen($filePath, 'r')) !== false) {
            $firstLine = fgets($handle);
            fclose($handle);
            
            foreach ($delimiters as $delimiter) {
                $counts[$delimiter] = substr_count($firstLine, $delimiter);
            }
        }
        
        return array_search(max($counts), $counts) ?: ';';
    }
    
    /**
     * Crée des transactions depuis un fichier CSV parsé
     * 
     * Import avec détection de doublons et application des règles d'automatisation.
     * Supporte deux modes de montant :
     * - Mode débit/crédit : colonnes séparées (montant = crédit - débit)
     * - Mode montant unique : colonne avec signe (+ ou -)
     * 
     * @param int $importId ID de l'enregistrement import
     * @param int $compteId ID du compte cible
     * @param array $rows Lignes CSV (sans entêtes)
     * @param array $mapping Mapping colonnes ['date' => 0, 'libelle' => 1, 'montant' => 2, ...]
     *                       Clés possibles: date, libelle, montant, debit, credit
     * @return array Statistiques ['success' => int, 'errors' => int, 'duplicates' => int]
     * 
     * @example
     * $mapping = [
     *     'date' => 0,        // Colonne 0 = date
     *     'libelle' => 1,     // Colonne 1 = libellé
     *     'debit' => 2,       // Colonne 2 = débit (si séparé)
     *     'credit' => 3,      // Colonne 3 = crédit (si séparé)
     *     'montant' => null   // Ou colonne unique avec signe
     * ];
     * $stats = Import::importTransactions($importId, $compteId, $rows, $mapping);
     * // $stats = ['success' => 150, 'errors' => 2, 'duplicates' => 8]
     * 
     * @see parseCSV()
     * @see isDuplicate()
     */
    public static function importTransactions(int $importId, int $compteId, array $rows, array $mapping): array
    {
        $userId = $_SESSION['user']['id'] ?? 0;
        $stats = ['success' => 0, 'errors' => 0, 'duplicates' => 0];
        
        foreach ($rows as $index => $row) {
            // Ignorer les lignes vides
            if (empty(array_filter($row))) continue;
            
            try {
                // Calculer le montant (débit/crédit ou montant direct)
                $montant = 0;
                if ($mapping['debit'] !== null && $mapping['credit'] !== null) {
                    $debit = self::parseMontant($row[$mapping['debit']] ?? '0');
                    $credit = self::parseMontant($row[$mapping['credit']] ?? '0');
                    $montant = $credit > 0 ? $credit : -$debit;
                } elseif ($mapping['montant'] !== null) {
                    $montant = self::parseMontant($row[$mapping['montant']] ?? '0');
                }
                
                if ($montant == 0) continue; // Ignorer montants nuls
                
                $data = [
                    'user_id' => $userId,
                    'compte_id' => $compteId,
                    'date_transaction' => self::parseDate($row[$mapping['date']] ?? ''),
                    'libelle' => trim($row[$mapping['libelle']] ?? ''),
                    'montant' => abs($montant),
                    'type_operation' => $montant >= 0 ? 'credit' : 'debit',
                    'importee' => 1,
                    'validee' => 1
                ];
                
                if (empty($data['libelle'])) continue;
                
                // Vérifier duplicata
                if (self::isDuplicate($compteId, $data['date_transaction'], $montant, $data['libelle'], $data['type_operation'])) {
                    $stats['duplicates']++;
                    continue;
                }
                
                // Appliquer règles d'automatisation
                $auto = \MonBudget\Models\RegleAutomatisation::applyRules($userId, $data['libelle']);
                $data['categorie_id'] = $auto['categorie_id'] ?? null;
                $data['sous_categorie_id'] = $auto['sous_categorie_id'] ?? null;
                $data['tiers_id'] = $auto['tiers_id'] ?? null;
                $data['moyen_paiement'] = $auto['moyen_paiement'] ?? null;
                
                if (Transaction::create($data)) {
                    $stats['success']++;
                } else {
                    $stats['errors']++;
                }
            } catch (\Exception $e) {
                $stats['errors']++;
            }
        }
        
        return $stats;
    }
    
    /**
     * Parse une date depuis différents formats courants
     * 
     * Formats supportés :
     * - DD/MM/YYYY (français)
     * - YYYY-MM-DD (ISO)
     * 
     * @param string $date Date à parser
     * @return string Date au format YYYY-MM-DD (ou date actuelle si parsing échoue)
     * 
     * @example
     * Import::parseDate('15/12/2024'); // Retourne '2024-12-15'
     * Import::parseDate('2024-12-15'); // Retourne '2024-12-15'
     */
    private static function parseDate(string $date): string
    {
        // Format DD/MM/YYYY
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $date, $m)) {
            return "{$m[3]}-{$m[2]}-{$m[1]}";
        }
        // Format YYYY-MM-DD
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $date;
        }
        return date('Y-m-d');
    }
    
    /**
     * Parse un montant depuis différents formats
     * 
     * Gère les formats français et anglo-saxons :
     * - Supprime les espaces (séparateurs de milliers)
     * - Convertit virgule en point décimal
     * 
     * @param string $montant Montant à parser ('1 234,56' ou '1234.56')
     * @return float Montant en valeur numérique
     * 
     * @example
     * Import::parseMontant('1 234,56'); // Retourne 1234.56
     * Import::parseMontant('-45.50');   // Retourne -45.50
     */
    private static function parseMontant(string $montant): float
    {
        // Remplacer virgule par point, enlever espaces
        $montant = str_replace([' ', ','], ['', '.'], $montant);
        return (float) $montant;
    }
    
    /**
     * Détecter le type d'opération
     */
    private static function detectTypeOperation(array $row, array $mapping): string
    {
        if (isset($mapping['type']) && isset($row[$mapping['type']])) {
            $type = strtolower($row[$mapping['type']]);
            if (strpos($type, 'crédit') !== false || strpos($type, 'credit') !== false) {
                return 'credit';
            }
            if (strpos($type, 'débit') !== false || strpos($type, 'debit') !== false) {
                return 'debit';
            }
        }
        
        // Détecter par montant
        if (isset($mapping['montant']) && isset($row[$mapping['montant']])) {
            $montant = self::parseMontant($row[$mapping['montant']]);
            return $montant >= 0 ? 'credit' : 'debit';
        }
        
        return 'debit';
    }
    
    /**
     * Vérifie si une transaction existe déjà (détection de doublon)
     * 
     * Critères de comparaison :
     * - Même compte
     * - Même date
     * - Même montant (tolérance 0.01€)
     * - Même libellé (exact)
     * - Même type (crédit ou débit)
     * 
     * @param int $compteId ID du compte
     * @param string $date Date au format YYYY-MM-DD
     * @param float $montant Montant avec signe (positif = crédit, négatif = débit)
     * @param string $libelle Libellé de la transaction
     * @return bool True si doublon détecté, false sinon
     * 
     * @example
     * $isDuplicate = Import::isDuplicate(
     *     $compteId,
     *     '2024-12-15',
     *     -45.50,
     *     'CARREFOUR PARIS'
     * );
     */
    private static function isDuplicate(int $compteId, string $date, float $montant, string $libelle): bool
    {
        // Note : On compare le montant en valeur absolue mais aussi le type (signe)
        // Car un crédit de 100€ n'est pas un doublon d'un débit de 100€
        $isCredit = $montant >= 0;
        $montantAbs = abs($montant);
        
        $sql = "SELECT COUNT(*) as count FROM transactions 
                WHERE compte_id = ? 
                AND date_transaction = ? 
                AND ABS(montant - ?) < 0.01 
                AND libelle = ?
                AND type_operation = ?";
        
        $type = $isCredit ? 'credit' : 'debit';
        $result = Database::select($sql, [$compteId, $date, $montantAbs, $libelle, $type]);
        return ($result[0]['count'] ?? 0) > 0;
    }
    
    /**
     * Récupère tous les imports d'un utilisateur avec informations de compte et banque
     * 
     * Retourne l'historique complet des imports avec statistiques.
     * Tri anti-chronologique (plus récents en premier).
     * 
     * @param int $userId ID de l'utilisateur
     * @return array Liste des imports avec compte_nom et banque_nom
     * 
     * @example
     * $imports = Import::getAllByUser($userId);
     * foreach ($imports as $import) {
     *     echo "{$import['nom_fichier']} → {$import['compte_nom']} ";
     *     echo "({$import['nb_lignes_importees']}/{$import['nb_lignes_total']})";
     * }
     */
    public static function getAllByUser(int $userId): array
    {
        $sql = "SELECT i.*, c.nom as compte_nom, b.nom as banque_nom
                FROM " . static::$table . " i
                LEFT JOIN comptes c ON i.compte_id = c.id
                LEFT JOIN banques b ON c.banque_id = b.id
                WHERE i.user_id = ?
                ORDER BY i.created_at DESC";
        
        return Database::select($sql, [$userId]);
    }
    
    /**
     * Parse un fichier OFX (Open Financial Exchange)
     * 
     * Format standard bancaire pour échange de données financières.
     * Supporte deux formats :
     * - SGML (ancien format avec balises non fermées)
     * - XML (format propre avec SimpleXML)
     * 
     * Gère automatiquement :
     * - Conversion d'encodage (UTF-8, ISO-8859-1, Windows-1252)
     * - Conversion SGML → XML pour parsing
     * - Extraction des transactions (BANKTRANLIST ou CCSTMTRS pour cartes)
     * 
     * @param string $filePath Chemin complet du fichier OFX
     * @return array Tableau de transactions avec date, libelle, montant, reference, type
     * 
     * @throws \Exception Si aucune transaction trouvée
     * 
     * @example
     * $transactions = Import::parseOFX('/uploads/releve.ofx');
     * // [
     * //   ['date' => '2024-12-15', 'libelle' => 'CARREFOUR', 'montant' => -45.50, 
     * //    'reference' => '202412150001', 'type' => 'debit'],
     * //   ...
     * // ]
     * 
     * @see importOFXTransactions()
     */
    public static function parseOFX(string $filePath): array
    {
        $content = file_get_contents($filePath);
        
        // Détecter l'encodage et convertir en UTF-8
        $encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
        if ($encoding && $encoding !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
        }
        
        // Nettoyer le contenu OFX (enlever les headers SGML)
        $content = preg_replace('/^.*?<OFX>/s', '<OFX>', $content);
        
        // Convertir les balises SGML en XML (fermer les balises auto-fermantes)
        // Pattern amélioré pour gérer les retours à la ligne et espaces
        $content = preg_replace('/<([A-Z0-9]+)>([^<\n\r]+)(?=\s*[\r\n]+\s*<)/m', '<$1>$2</$1>', $content);
        
        // Fermer les balises qui sont sur une seule ligne
        $content = preg_replace('/<([A-Z0-9]+)>([^<>]+)$/m', '<$1>$2</$1>', $content);
        
        // Ajouter les balises de fermeture pour les balises de structure vides
        $content = preg_replace('/<(\/?)([A-Z0-9]+)>\s*$/m', '<$1$2>', $content);
        
        // Parser manuellement si SimpleXML échoue
        $transactions = [];
        
        // Chercher tous les blocs STMTTRN (transactions)
        if (preg_match_all('/<STMTTRN>(.*?)<\/STMTTRN>/s', $content, $matches)) {
            foreach ($matches[1] as $trnContent) {
                $transaction = self::parseOFXTransaction($trnContent);
                if ($transaction) {
                    $transactions[] = $transaction;
                }
            }
        }
        
        // Si aucune transaction trouvée, essayer avec SimpleXML (format plus propre)
        if (empty($transactions)) {
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($content);
            
            if ($xml) {
                // Parcourir les transactions (BANKTRANLIST)
                if (isset($xml->BANKMSGSRSV1->STMTTRNRS->STMTRS->BANKTRANLIST->STMTTRN)) {
                    foreach ($xml->BANKMSGSRSV1->STMTTRNRS->STMTRS->BANKTRANLIST->STMTTRN as $trn) {
                        $transaction = self::extractTransactionFromXML($trn);
                        if ($transaction) {
                            $transactions[] = $transaction;
                        }
                    }
                }
                
                // Essayer aussi le format CC (carte de crédit)
                if (isset($xml->CREDITCARDMSGSRSV1->CCSTMTTRNRS->CCSTMTRS->BANKTRANLIST->STMTTRN)) {
                    foreach ($xml->CREDITCARDMSGSRSV1->CCSTMTTRNRS->CCSTMTRS->BANKTRANLIST->STMTTRN as $trn) {
                        $transaction = self::extractTransactionFromXML($trn);
                        if ($transaction) {
                            $transactions[] = $transaction;
                        }
                    }
                }
            }
            libxml_clear_errors();
        }
        
        if (empty($transactions)) {
            throw new \Exception("Aucune transaction trouvée dans le fichier OFX");
        }
        
        return $transactions;
    }
    
    /**
     * Parse une transaction OFX depuis du texte brut (format SGML)
     * 
     * Utilise des regex pour extraire les champs d'une transaction OFX.
     * Gère le signe du montant explicitement (+ ou -).
     * 
     * ✅ FIX BUG : Concatène NAME + MEMO pour libellé complet
     * 
     * Champs OFX standards :
     * - DTPOSTED : Date (YYYYMMDD ou YYYYMMDDHHMMSS)
     * - TRNAMT : Montant avec signe (négatif = débit)
     * - NAME : Libellé principal (souvent tronqué)
     * - MEMO : Informations supplémentaires (détails complets)
     * - FITID : Identifiant unique de la transaction
     * 
     * @param string $content Contenu texte d'un bloc <STMTTRN>...</STMTTRN>
     * @return array|null Transaction parsée ou null si champs essentiels manquants
     * 
     * @example
     * $content = "<DTPOSTED>20241215<TRNAMT>-45.50<NAME>CARREFOUR<MEMO>PARIS 15";
     * $trn = Import::parseOFXTransaction($content);
     * // ['date' => '2024-12-15', 'montant' => -45.50, 'libelle' => 'CARREFOUR - PARIS 15', ...]
     */
    private static function parseOFXTransaction(string $content): ?array
    {
        $data = [];
        
        // Extraire les champs
        if (preg_match('/<DTPOSTED>(\d+)/', $content, $match)) {
            $data['date_raw'] = $match[1];
        }
        // IMPORTANT : Capturer le signe + ou - explicitement
        if (preg_match('/<TRNAMT>([+\-]?\d+\.?\d*)/', $content, $match)) {
            $data['montant'] = (float) $match[1];
        }
        
        // ✅ FIX : Capturer NAME ET MEMO séparément (avec support multi-lignes)
        $name = '';
        $memo = '';
        
        // Capturer NAME (peut contenir des retours à la ligne)
        if (preg_match('/<NAME>(.*?)<\/NAME>/s', $content, $match)) {
            $name = trim(preg_replace('/\s+/', ' ', $match[1])); // Remplacer multiples espaces/sauts par un seul espace
        } elseif (preg_match('/<NAME>([^<]+)/s', $content, $match)) {
            // Fallback : si pas de balise fermante, capturer jusqu'au prochain tag
            $name = trim(preg_replace('/\s+/', ' ', $match[1]));
        }
        
        // Capturer MEMO (peut contenir des retours à la ligne)
        if (preg_match('/<MEMO>(.*?)<\/MEMO>/s', $content, $match)) {
            $memo = trim(preg_replace('/\s+/', ' ', $match[1]));
        } elseif (preg_match('/<MEMO>([^<]+)/s', $content, $match)) {
            // Fallback : si pas de balise fermante, capturer jusqu'au prochain tag
            $memo = trim(preg_replace('/\s+/', ' ', $match[1]));
        }
        
        // ✅ FIX : Concaténer NAME + MEMO pour libellé complet
        // Éviter la duplication si MEMO = NAME
        if (!empty($name) && !empty($memo)) {
            if (stripos($memo, $name) === false && stripos($name, $memo) === false) {
                // NAME et MEMO différents : concaténer
                $data['libelle'] = $name . ' - ' . $memo;
            } else {
                // MEMO contient déjà NAME ou vice-versa : prendre le plus long
                $data['libelle'] = strlen($memo) > strlen($name) ? $memo : $name;
            }
        } elseif (!empty($name)) {
            $data['libelle'] = $name;
        } elseif (!empty($memo)) {
            $data['libelle'] = $memo;
        }
        
        // Limiter à 255 caractères (taille colonne BDD)
        if (isset($data['libelle']) && strlen($data['libelle']) > 255) {
            $data['libelle'] = substr($data['libelle'], 0, 252) . '...';
        }
        
        if (preg_match('/<FITID>([^<\n]+)/', $content, $match)) {
            $data['reference'] = trim($match[1]);
        }
        
        // Vérifier qu'on a les champs essentiels
        if (!isset($data['date_raw']) || !isset($data['montant'])) {
            return null;
        }
        
        // Parser la date YYYYMMDD ou YYYYMMDDHHMMSS
        if (preg_match('/^(\d{4})(\d{2})(\d{2})/', $data['date_raw'], $matches)) {
            $dateFormatted = $matches[1] . '-' . $matches[2] . '-' . $matches[3];
        } else {
            $dateFormatted = date('Y-m-d');
        }
        
        return [
            'date' => $dateFormatted,
            'libelle' => $data['libelle'] ?? 'Transaction',
            'montant' => $data['montant'],
            'reference' => $data['reference'] ?? '',
            'type' => $data['montant'] >= 0 ? 'credit' : 'debit'
        ];
    }
    
    /**
     * Extrait une transaction depuis un objet SimpleXML (format OFX propre)
     * 
     * Utilisé pour parser les fichiers OFX bien formatés (XML valide).
     * 
     * ✅ FIX BUG : Concatène NAME + MEMO pour libellé complet
     * 
     * @param \SimpleXMLElement $trn Objet transaction OFX
     * @return array|null Transaction parsée ou null si date invalide
     * 
     * @example
     * foreach ($xml->BANKMSGSRSV1->STMTTRNRS->STMTRS->BANKTRANLIST->STMTTRN as $trn) {
     *     $transaction = Import::extractTransactionFromXML($trn);
     * }
     */
    private static function extractTransactionFromXML($trn): ?array
    {
        $date = (string) $trn->DTPOSTED;
        $montant = (float) $trn->TRNAMT;
        
        // ✅ FIX : Nettoyer les retours à la ligne et espaces multiples
        $name = trim(preg_replace('/\s+/', ' ', (string) ($trn->NAME ?? '')));
        $memo = trim(preg_replace('/\s+/', ' ', (string) ($trn->MEMO ?? '')));
        $reference = (string) ($trn->FITID ?? '');
        
        // ✅ FIX : Concaténer NAME + MEMO pour libellé complet
        $libelle = '';
        if (!empty($name) && !empty($memo)) {
            if (stripos($memo, $name) === false && stripos($name, $memo) === false) {
                // NAME et MEMO différents : concaténer
                $libelle = $name . ' - ' . $memo;
            } else {
                // MEMO contient déjà NAME ou vice-versa : prendre le plus long
                $libelle = strlen($memo) > strlen($name) ? $memo : $name;
            }
        } elseif (!empty($name)) {
            $libelle = $name;
        } elseif (!empty($memo)) {
            $libelle = $memo;
        } else {
            $libelle = 'Transaction';
        }
        
        // Limiter à 255 caractères (taille colonne BDD)
        if (strlen($libelle) > 255) {
            $libelle = substr($libelle, 0, 252) . '...';
        }
        
        // Parser la date YYYYMMDD ou YYYYMMDDHHMMSS
        if (preg_match('/^(\d{4})(\d{2})(\d{2})/', $date, $matches)) {
            $dateFormatted = $matches[1] . '-' . $matches[2] . '-' . $matches[3];
        } else {
            return null;
        }
        
        return [
            'date' => $dateFormatted,
            'libelle' => $libelle,
            'montant' => $montant,
            'reference' => $reference,
            'type' => $montant >= 0 ? 'credit' : 'debit'
        ];
    }
    
    /**
     * Importe des transactions OFX dans un compte
     * 
     * Création massive de transactions avec :
     * - Détection de doublons
     * - Application des règles d'automatisation (catégories, tiers)
     * - Mise à jour des statistiques de l'import
     * - Recalcul du solde du compte
     * 
     * @param int $importId ID de l'enregistrement import
     * @param int $compteId ID du compte cible
     * @param int $userId ID de l'utilisateur
     * @param array $transactions Tableau de transactions parsées depuis parseOFX()
     * @return array Statistiques ['total', 'imported', 'duplicates', 'errors']
     * 
     * @example
     * $transactions = Import::parseOFX($filePath);
     * $stats = Import::importOFXTransactions($importId, $compteId, $userId, $transactions);
     * echo "{$stats['imported']} transactions importées sur {$stats['total']}";
     * echo "{$stats['duplicates']} doublons ignorés";
     * 
     * @see parseOFX()
     * @see isDuplicate()
     */
    public static function importOFXTransactions(int $importId, int $compteId, int $userId, array $transactions): array
    {
        $stats = [
            'total' => count($transactions),
            'imported' => 0,
            'duplicates' => 0,
            'errors' => 0
        ];
        
        foreach ($transactions as $transaction) {
            try {
                // Vérifier les doublons
                if (self::isDuplicate($compteId, $transaction['date'], $transaction['montant'], $transaction['libelle'])) {
                    $stats['duplicates']++;
                    continue;
                }
                
                $data = [
                    'user_id' => $userId,
                    'compte_id' => $compteId,
                    'date_transaction' => $transaction['date'],
                    'montant' => abs($transaction['montant']),
                    'libelle' => $transaction['libelle'],
                    'type_operation' => $transaction['type'],
                    'reference_banque' => $transaction['reference'] ?? null,
                    'importee' => 1,
                    'validee' => 1
                ];
                
                // Appliquer règles d'automatisation (même logique que CSV)
                $auto = RegleAutomatisation::applyRules($userId, $data['libelle']);
                $data['categorie_id'] = $auto['categorie_id'] ?? null;
                $data['sous_categorie_id'] = $auto['sous_categorie_id'] ?? null;
                $data['tiers_id'] = $auto['tiers_id'] ?? null;
                $data['moyen_paiement'] = $auto['moyen_paiement'] ?? null;
                
                Transaction::create($data);
                $stats['imported']++;
                
            } catch (\Exception $e) {
                $stats['errors']++;
            }
        }
        
        // Mettre à jour l'import
        self::update($importId, [
            'nb_lignes_total' => $stats['total'],
            'nb_lignes_importees' => $stats['imported'],
            'nb_lignes_ignorees' => $stats['duplicates'] + $stats['errors'],
            'statut' => 'termine'
        ]);
        
        // Recalculer le solde du compte
        Compte::recalculerSolde($compteId);
        
        return $stats;
    }
}
