<?php

namespace MonBudget\Controllers;

use MonBudget\Models\Banque;
use MonBudget\Core\Database;

/**
 * Contrôleur de gestion des établissements bancaires
 * 
 * Gère toutes les opérations CRUD sur les banques : création, modification,
 * suppression, consultation. Gère également le téléchargement des logos.
 * 
 * @package MonBudget\Controllers
 * @author MonBudget
 * @version 1.0.0
 */
class BanqueController extends BaseController
{
    /**
     * Lister toutes les banques avec comptage des comptes
     * 
     * Affiche la liste de toutes les banques avec le nombre de comptes
     * associés à chacune.
     * 
     * @return void
     */
    public function index(): void
    {
        $this->requireAuth();
        
        $banques = Banque::withComptesCount();
        
        $this->view('banques.index', [
            'banques' => $banques,
            'title' => 'Gestion des Banques'
        ]);
    }
    
    /**
     * Afficher le formulaire de création
     */
    public function create(): void
    {
        $this->requireAuth();
        
        $this->view('banques.create', [
            'title' => 'Nouvelle Banque'
        ]);
    }
    
    /**
     * Afficher les détails d'une banque avec ses comptes
     */
    public function show(int $id): void
    {
        $this->requireAuth();
        
        $banque = Banque::find($id);
        
        if (!$banque) {
            flash('error', 'Banque introuvable');
            $this->redirect('banques');
            return;
        }
        
        // Récupérer les comptes de cette banque
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT c.*, 
                   (SELECT COUNT(*) FROM transactions WHERE compte_id = c.id) as nb_transactions
            FROM comptes c
            WHERE c.banque_id = ? AND c.user_id = ?
            ORDER BY c.nom
        ");
        $stmt->execute([$id, $_SESSION['user']['id']]);
        $comptes = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Calculer les totaux
        $totalSolde = array_sum(array_column($comptes, 'solde_actuel'));
        $totalComptes = count($comptes);
        $totalTransactions = array_sum(array_column($comptes, 'nb_transactions'));
        
        $this->view('banques.show', [
            'banque' => $banque,
            'comptes' => $comptes,
            'totalSolde' => $totalSolde,
            'totalComptes' => $totalComptes,
            'totalTransactions' => $totalTransactions,
            'title' => $banque['nom']
        ]);
    }
    
    /**
     * Enregistrer une nouvelle banque
     */
    public function store(): void
    {
        $this->requireAuth();
        
        if (!$this->validateCsrfOrFail('banques')) return;
        
        // Validation
        $data = $this->validate([
            'nom' => 'required|max:100',
            'code_banque' => 'max:20',
            'bic' => 'max:11',
            'telephone' => 'max:20',
            'site_web' => 'max:255',
            'adresse_ligne1' => 'max:255',
            'adresse_ligne2' => 'max:255',
            'code_postal' => 'max:10',
            'ville' => 'max:100',
            'pays' => 'max:100',
            'contact_email' => 'email|max:255'
        ]);
        
        // Vérifier si le nom existe déjà
        if (Banque::nomExists($data['nom'])) {
            flash('error', 'Une banque avec ce nom existe déjà');
            $this->redirect('banques/create');
            return;
        }
        
        // Définir le pays par défaut
        if (empty($data['pays'])) {
            $data['pays'] = 'France';
        }
        
        // Gérer l'upload du logo
        if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['logo_file'];
            
            // Vérifier le type de fichier
            $allowedTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/gif'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mimeType, $allowedTypes)) {
                flash('error', 'Format de fichier non autorisé. Utilisez PNG, JPG ou GIF.');
                $this->redirect('banques/create');
                return;
            }
            
            // Vérifier la taille (max 2 Mo)
            if ($file['size'] > 2 * 1024 * 1024) {
                flash('error', 'Le fichier est trop volumineux. Taille maximale: 2 Mo.');
                $this->redirect('banques/create');
                return;
            }
            
            // Générer un nom de fichier unique
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid('logo_') . '.' . $extension;
            $uploadPath = __DIR__ . '/../../uploads/logos/' . $filename;
            
            // Déplacer le fichier uploadé
            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                $data['logo_file'] = $filename;
            } else {
                flash('error', 'Erreur lors de l\'upload du logo');
                $this->redirect('banques/create');
                return;
            }
        }
        
        // Créer la banque
        $id = Banque::create($data);
        
        if ($id) {
            flash('success', 'Banque créée avec succès');
            $this->redirect('banques');
        } else {
            flash('error', 'Erreur lors de la création de la banque');
            $this->redirect('banques/create');
        }
    }
    
    /**
     * Afficher le formulaire d'édition
     */
    public function edit(int $id): void
    {
        $this->requireAuth();
        
        $banque = Banque::find($id);
        
        if (!$banque) {
            flash('error', 'Banque introuvable');
            $this->redirect('banques');
            return;
        }
        
        $this->view('banques.edit', [
            'banque' => $banque,
            'title' => 'Modifier la Banque'
        ]);
    }
    
    /**
     * Mettre à jour une banque
     */
    public function update(int $id): void
    {
        $this->requireAuth();
        
        if (!$this->validateCsrfOrFail('banques')) return;
        
        $banque = Banque::find($id);
        
        if (!$banque) {
            flash('error', 'Banque introuvable');
            $this->redirect('banques');
            return;
        }
        
        // Validation
        $data = $this->validate([
            'nom' => 'required|max:100',
            'code_banque' => 'max:20',
            'bic' => 'max:11',
            'telephone' => 'max:20',
            'site_web' => 'max:255',
            'adresse_ligne1' => 'max:255',
            'adresse_ligne2' => 'max:255',
            'code_postal' => 'max:10',
            'ville' => 'max:100',
            'pays' => 'max:100',
            'contact_email' => 'email|max:255'
        ]);
        
        // Vérifier si le nom existe déjà (sauf pour cette banque)
        if (Banque::nomExists($data['nom'], $id)) {
            flash('error', 'Une banque avec ce nom existe déjà');
            $this->redirect("banques/{$id}/edit");
            return;
        }
        
        // Gérer l'upload du logo
        if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['logo_file'];
            
            // Vérifier le type de fichier
            $allowedTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/gif'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mimeType, $allowedTypes)) {
                flash('error', 'Format de fichier non autorisé. Utilisez PNG, JPG ou GIF.');
                $this->redirect("banques/{$id}/edit");
                return;
            }
            
            // Vérifier la taille (max 2 Mo)
            if ($file['size'] > 2 * 1024 * 1024) {
                flash('error', 'Le fichier est trop volumineux. Taille maximale: 2 Mo.');
                $this->redirect("banques/{$id}/edit");
                return;
            }
            
            // Générer un nom de fichier unique
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid('logo_') . '.' . $extension;
            $uploadPath = __DIR__ . '/../../uploads/logos/' . $filename;
            
            // Déplacer le fichier uploadé
            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                // Supprimer l'ancien logo s'il existe
                if (!empty($banque['logo_file'])) {
                    $oldLogoPath = __DIR__ . '/../../uploads/logos/' . $banque['logo_file'];
                    if (file_exists($oldLogoPath)) {
                        unlink($oldLogoPath);
                    }
                }
                $data['logo_file'] = $filename;
            } else {
                flash('error', 'Erreur lors de l\'upload du logo');
                $this->redirect("banques/{$id}/edit");
                return;
            }
        }
        
        // Mettre à jour
        $result = Banque::update($id, $data);
        
        if ($result > 0) {
            flash('success', 'Banque mise à jour avec succès');
            $this->redirect('banques');
        } else {
            flash('error', 'Erreur lors de la mise à jour');
            $this->redirect("banques/{$id}/edit");
        }
    }
    
    /**
     * Supprimer une banque
     */
    public function destroy(int $id): void
    {
        $this->requireAuth();
        
        if (!$this->validateCsrfOrFail('banques')) return;
        
        $result = Banque::delete($id);
        
        if ($result > 0) {
            flash('success', 'Banque supprimée avec succès');
        } else {
            flash('error', 'Impossible de supprimer cette banque (comptes associés)');
        }
        
        $this->redirect('banques');
    }
    
    /**
     * Recherche AJAX
     */
    public function search(): void
    {
        $this->requireAuth();
        
        $query = $_GET['q'] ?? '';
        
        if (empty($query)) {
            echo json_encode([]);
            exit;
        }
        
        $results = Banque::search($query);
        
        header('Content-Type: application/json');
        echo json_encode($results);
        exit;
    }
}
