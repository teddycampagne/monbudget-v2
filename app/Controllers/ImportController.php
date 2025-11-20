<?php

namespace MonBudget\Controllers;

use MonBudget\Core\Database;
use MonBudget\Models\Import;
use MonBudget\Models\Compte;
use MonBudget\Models\Transaction;

/**
 * Contrôleur d'import de fichiers bancaires
 * 
 * Gère l'import de fichiers bancaires au format CSV/OFX : téléchargement,
 * validation, prévisualisation, mappage des colonnes, et insertion en base.
 * Supporte la détection automatique de format et la catégorisation automatique.
 * 
 * @package MonBudget\Controllers
 * @author MonBudget
 * @version 1.0.0
 */
class ImportController extends BaseController
{
    /**
     * Lister l'historique des imports
     * 
     * Affiche tous les imports effectués par l'utilisateur avec leurs statistiques.
     * 
     * @return void
     */
    public function index(): void
    {
        $this->requireAuth();
        
        $imports = Import::getAllByUser($this->userId);
        
        $this->view('imports.index', [
            'imports' => $imports,
            'title' => 'Imports de transactions'
        ]);
    }
    
    /**
     * Afficher le formulaire d'upload de fichier
     * 
     * @return void
     */
    public function upload(): void
    {
        $this->requireAuth();
        
        $comptes = Compte::getActifs();
        
        $this->view('imports.upload', [
            'comptes' => $comptes,
            'title' => 'Importer des transactions'
        ]);
    }
    
    /**
     * Preview et mapping
     */
    public function preview(): void
    {
        $this->requireAuth();
        
        // 🔒 SÉCURITÉ PCI DSS : Nettoyer les anciens fichiers avant nouvel import
        $this->cleanupOldImportFiles();
        
        if (!$this->validateCsrfOrFail('imports/upload')) return;
        
        $compteId = (int) ($_POST['compte_id'] ?? 0);
        
        // Vérifier le compte
        $compte = Compte::findWithBanque($compteId);
        if (!$compte || $compte['user_id'] != $this->userId) {
            flash('error', 'Compte invalide');
            $this->redirect('imports/upload');
            return;
        }
        
        // Gérer l'upload
        if (!isset($_FILES['fichier']) || $_FILES['fichier']['error'] !== UPLOAD_ERR_OK) {
            flash('error', 'Erreur lors de l\'upload du fichier');
            $this->redirect('imports/upload');
            return;
        }
        
        $file = $_FILES['fichier'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($ext, ['csv', 'ofx', 'qfx'])) {
            flash('error', 'Seuls les fichiers CSV, OFX et QFX sont acceptés');
            $this->redirect('imports/upload');
            return;
        }
        
        // Déplacer le fichier
        $uploadDir = __DIR__ . '/../../uploads/imports/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileName = uniqid('import_') . '.' . $ext;
        $filePath = $uploadDir . $fileName;
        
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            flash('error', 'Erreur lors de l\'enregistrement du fichier');
            $this->redirect('imports/upload');
            return;
        }
        
        // Parser selon le type de fichier
        if ($ext === 'csv') {
            // Parser le CSV
            $delimiter = Import::detectDelimiter($filePath);
            $rows = Import::parseCSV($filePath, $delimiter);
            
            if (empty($rows)) {
                flash('error', 'Le fichier CSV est vide');
                // 🔒 SÉCURITÉ : Supprimer le fichier immédiatement
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                $this->redirect('imports/upload');
                return;
            }
            
            // Détecter la ligne d'en-tête (cherche "date" ou "libelle")
            $headerIndex = 0;
            foreach ($rows as $index => $row) {
                $rowText = strtolower(implode(' ', array_filter($row)));
                if (stripos($rowText, 'date') !== false || stripos($rowText, 'libelle') !== false || stripos($rowText, 'debit') !== false) {
                    $headerIndex = $index;
                    break;
                }
            }
            
            $headers = $rows[$headerIndex];
            $dataRows = array_slice($rows, $headerIndex + 1);
            
            // Stocker en session pour le traitement
            $_SESSION['import_preview'] = [
                'compte_id' => $compteId,
                'file_path' => $filePath,
                'file_name' => $file['name'],
                'file_type' => 'csv',
                'delimiter' => $delimiter,
                'header_index' => $headerIndex,
                'rows' => $rows
            ];
            
            $this->view('imports.preview', [
                'compte' => $compte,
                'headers' => $headers,
                'preview_rows' => array_slice($dataRows, 0, 10),
                'total_rows' => count($dataRows),
                'title' => 'Aperçu de l\'import'
            ]);
            
        } else {
            // Parser OFX/QFX
            try {
                $transactions = Import::parseOFX($filePath);
                
                if (empty($transactions)) {
                    flash('error', 'Aucune transaction trouvée dans le fichier OFX');
                    // 🔒 SÉCURITÉ : Supprimer le fichier immédiatement
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                    $this->redirect('imports/upload');
                    return;
                }
                
                // Stocker en session
                $_SESSION['import_preview'] = [
                    'compte_id' => $compteId,
                    'file_path' => $filePath,
                    'file_name' => $file['name'],
                    'file_type' => 'ofx',
                    'transactions' => $transactions
                ];
                
                $this->view('imports.preview_ofx', [
                    'compte' => $compte,
                    'transactions' => array_slice($transactions, 0, 20),
                    'total_transactions' => count($transactions),
                    'title' => 'Aperçu de l\'import OFX'
                ]);
                
            } catch (\Exception $e) {
                // Log plus détaillé pour le debug
                error_log('OFX Parse Error: ' . $e->getMessage());
                error_log('File: ' . $filePath);
                
                // Message utilisateur avec plus de détails
                $errorMsg = 'Erreur de parsing OFX : ' . $e->getMessage();
                
                // Lire les premières lignes du fichier pour diagnostic
                $content = file_get_contents($filePath);
                $firstLines = substr($content, 0, 500);
                if (stripos($firstLines, '<OFX>') === false && stripos($firstLines, 'OFXHEADER') === false) {
                    $errorMsg .= ' - Le fichier ne semble pas être un fichier OFX valide.';
                }
                
                flash('error', $errorMsg);
                // 🔒 SÉCURITÉ : Supprimer le fichier immédiatement
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                $this->redirect('imports/upload');
                return;
            }
        }
    }
    
    /**
     * Nettoyer les fichiers d'import temporaires (sécurité PCI DSS)
     * 
     * Supprime automatiquement les fichiers CSV/OFX de plus d'1 heure
     * pour éviter la conservation de données sensibles en clair.
     * 
     * Cette méthode devrait être appelée périodiquement (cron ou avant chaque import).
     * 
     * @return int Nombre de fichiers supprimés
     */
    private function cleanupOldImportFiles(): int
    {
        $uploadDir = __DIR__ . '/../../uploads/imports/';
        $deleted = 0;
        
        if (!is_dir($uploadDir)) {
            return 0;
        }
        
        $files = glob($uploadDir . 'import_*.*');
        $maxAge = 3600; // 1 heure en secondes
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $age = time() - filemtime($file);
                if ($age > $maxAge) {
                    if (unlink($file)) {
                        $deleted++;
                        error_log("[SÉCURITÉ] Fichier import supprimé : $file (âge: " . round($age/60) . " min)");
                    }
                }
            }
        }
        
        return $deleted;
    }
    
    /**
     * Traiter l'import (CSV ou OFX)
     */
    public function process(): void
    {
        $this->requireAuth();
        
        if (!$this->validateCsrfOrFail('imports')) return;
        
        if (!isset($_SESSION['import_preview'])) {
            flash('error', 'Session expirée, veuillez recommencer');
            $this->redirect('imports/upload');
            return;
        }
        
        $preview = $_SESSION['import_preview'];
        
        // Rediriger selon le type de fichier
        if ($preview['file_type'] === 'ofx') {
            $this->processOFX();
        } else {
            $this->processCSV();
        }
    }
    
    /**
     * Traiter l'import CSV
     */
    private function processCSV(): void
    {
        $preview = $_SESSION['import_preview'];
        
        $compte = Compte::findWithBanque($preview['compte_id']);
        if (!$compte || $compte['user_id'] != $this->userId) {
            flash('error', 'Compte invalide');
            $this->redirect('imports');
            return;
        }
        
        $mapping = [
            'date' => (int) ($_POST['col_date'] ?? 0),
            'libelle' => (int) ($_POST['col_libelle'] ?? 0),
            'montant' => isset($_POST['col_montant']) ? (int) $_POST['col_montant'] : null,
            'debit' => isset($_POST['col_debit']) ? (int) $_POST['col_debit'] : null,
            'credit' => isset($_POST['col_credit']) ? (int) $_POST['col_credit'] : null,
            'type' => isset($_POST['col_type']) ? (int) $_POST['col_type'] : null
        ];
        
        // Parser tout le fichier
        $allRows = array_slice($preview['rows'], $preview['header_index'] + 1);
        
        // Créer l'import (sans chemin_fichier, fichier supprimé après traitement)
        $importId = Import::create([
            'compte_id' => $preview['compte_id'],
            'nom_fichier' => $preview['file_name'],
            'type_fichier' => 'csv',
            'statut' => 'en_cours'
        ]);
        
        // Importer les transactions
        $stats = Import::importTransactions($importId, $preview['compte_id'], $allRows, $mapping);
        
        // Mettre à jour le statut de l'import
        Database::update(
            "UPDATE imports SET 
                nb_transactions = ?, 
                nb_nouvelles_transactions = ?, 
                nb_doublons = ?,
                statut = 'termine' 
            WHERE id = ?",
            [($stats['success'] + $stats['duplicates'] + $stats['errors']), $stats['success'], $stats['duplicates'], $importId]
        );
        
        // Recalculer le solde du compte
        Compte::recalculerSolde($preview['compte_id']);
        
        // 🔒 SÉCURITÉ PCI DSS : Supprimer le fichier importé (données sensibles en clair)
        if (isset($preview['file_path']) && file_exists($preview['file_path'])) {
            unlink($preview['file_path']);
        }
        
        // Nettoyer la session
        unset($_SESSION['import_preview']);
        
        flash('success', sprintf(
            'Import CSV terminé : %d transaction(s) importée(s), %d doublon(s) ignoré(s)',
            $stats['success'],
            $stats['duplicates']
        ));
        
        $this->redirect('imports');
    }
    
    /**
     * Traiter l'import OFX
     */
    private function processOFX(): void
    {
        $preview = $_SESSION['import_preview'];
        
        $compte = Compte::findWithBanque($preview['compte_id']);
        if (!$compte || $compte['user_id'] != $this->userId) {
            flash('error', 'Compte invalide');
            $this->redirect('imports');
            return;
        }
        
        // Créer l'enregistrement d'import (sans chemin_fichier, fichier supprimé après traitement)
        $importId = Import::create([
            'compte_id' => $preview['compte_id'],
            'nom_fichier' => $preview['file_name'],
            'type_fichier' => 'ofx',
            'statut' => 'en_cours'
        ]);
        
        // Importer les transactions
        $stats = Import::importOFXTransactions($importId, $preview['compte_id'], $this->userId, $preview['transactions']);
        
        // 🔒 SÉCURITÉ PCI DSS : Supprimer le fichier importé (données sensibles en clair)
        if (isset($preview['file_path']) && file_exists($preview['file_path'])) {
            unlink($preview['file_path']);
        }
        
        // Nettoyer la session
        unset($_SESSION['import_preview']);
        
        flash('success', sprintf(
            'Import OFX terminé : %d transaction(s) importée(s), %d doublon(s) ignoré(s)',
            $stats['imported'],
            $stats['duplicates']
        ));
        
        $this->redirect('imports');
    }
    
    /**
     * Annuler l'import en cours
     */
    public function cancel(): void
    {
        $this->requireAuth();
        
        if (isset($_SESSION['import_preview']['file_path'])) {
            @unlink($_SESSION['import_preview']['file_path']);
        }
        
        unset($_SESSION['import_preview']);
        
        flash('info', 'Import annulé');
        $this->redirect('imports');
    }
}
