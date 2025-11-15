<?php

namespace MonBudget\Controllers;

use MonBudget\Models\Titulaire;

/**
 * Contrôleur de gestion des titulaires de comptes
 * 
 * Gère toutes les opérations CRUD sur les titulaires de comptes bancaires.
 * Un compte peut avoir un ou deux titulaires (titulaire principal et co-titulaire).
 * 
 * @package MonBudget\Controllers
 * @author MonBudget
 * @version 1.0.0
 */
class TitulaireController extends BaseController
{
    /**
     * Lister tous les titulaires
     * 
     * Affiche la liste de tous les titulaires enregistrés par l'utilisateur.
     * 
     * @return void
     */
    public function index(): void
    {
        $this->requireAuth();
        
        $titulaires = Titulaire::getAll();
        
        $this->view('titulaires.index', [
            'titulaires' => $titulaires,
            'title' => 'Gestion des Titulaires'
        ]);
    }
    
    /**
     * Afficher le formulaire de création de titulaire
     * 
     * @return void
     */
    public function create(): void
    {
        $this->requireAuth();
        
        $this->view('titulaires.create', [
            'title' => 'Nouveau Titulaire'
        ]);
    }
    
    /**
     * Enregistrer un nouveau titulaire
     */
    public function store(): void
    {
        $this->requireAuth();
        
        if (!$this->validateCsrfOrFail('titulaires')) return;
        
        // Validation
        $data = $this->validate([
            'nom' => 'required|max:100',
            'prenom' => 'max:100',
            'date_naissance' => '',
            'lieu_naissance' => 'max:100',
            'adresse_ligne1' => 'max:255',
            'adresse_ligne2' => 'max:255',
            'code_postal' => 'max:10',
            'ville' => 'max:100',
            'pays' => 'max:100',
            'telephone' => 'max:20',
            'email' => 'email|max:255'
        ]);
        
        // Vérifier si le nom/prénom existe déjà
        if (Titulaire::nomPrenomExists($data['nom'], $data['prenom'] ?? '')) {
            flash('error', 'Un titulaire avec ce nom et prénom existe déjà');
            $this->redirect('titulaires/create');
            return;
        }
        
        // Ajouter user_id
        $data['user_id'] = $_SESSION['user']['id'];
        
        // Définir pays par défaut
        if (empty($data['pays'])) {
            $data['pays'] = 'France';
        }
        
        // Créer le titulaire
        $id = Titulaire::create($data);
        
        if ($id) {
            flash('success', 'Titulaire créé avec succès');
            $this->redirect('titulaires');
        } else {
            flash('error', 'Erreur lors de la création du titulaire');
            $this->redirect('titulaires/create');
        }
    }
    
    /**
     * Afficher le formulaire d'édition
     */
    public function edit(int $id): void
    {
        $this->requireAuth();
        
        $titulaire = Titulaire::find($id);
        
        if (!$titulaire) {
            flash('error', 'Titulaire introuvable');
            $this->redirect('titulaires');
            return;
        }
        
        $this->view('titulaires.edit', [
            'titulaire' => $titulaire,
            'title' => 'Modifier le Titulaire'
        ]);
    }
    
    /**
     * Mettre à jour un titulaire
     */
    public function update(int $id): void
    {
        $this->requireAuth();
        
        if (!$this->validateCsrfOrFail('titulaires')) return;
        
        $titulaire = Titulaire::find($id);
        
        if (!$titulaire) {
            flash('error', 'Titulaire introuvable');
            $this->redirect('titulaires');
            return;
        }
        
        // Validation
        $data = $this->validate([
            'nom' => 'required|max:100',
            'prenom' => 'max:100',
            'date_naissance' => '',
            'lieu_naissance' => 'max:100',
            'adresse_ligne1' => 'max:255',
            'adresse_ligne2' => 'max:255',
            'code_postal' => 'max:10',
            'ville' => 'max:100',
            'pays' => 'max:100',
            'telephone' => 'max:20',
            'email' => 'email|max:255'
        ]);
        
        // Vérifier si le nom/prénom existe déjà (sauf pour ce titulaire)
        if (Titulaire::nomPrenomExists($data['nom'], $data['prenom'] ?? '', $id)) {
            flash('error', 'Un titulaire avec ce nom et prénom existe déjà');
            $this->redirect("titulaires/{$id}/edit");
            return;
        }
        
        // Ajouter user_id
        $data['user_id'] = $_SESSION['user']['id'];
        
        // Mettre à jour
        $result = Titulaire::update($id, $data);
        
        if ($result > 0) {
            flash('success', 'Titulaire mis à jour avec succès');
            $this->redirect('titulaires');
        } else {
            flash('error', 'Aucune modification effectuée');
            $this->redirect("titulaires/{$id}/edit");
        }
    }
    
    /**
     * Supprimer un titulaire
     */
    public function destroy(int $id): void
    {
        $this->requireAuth();
        
        if (!$this->validateCsrfOrFail('titulaires')) return;
        
        $titulaire = Titulaire::find($id);
        
        if (!$titulaire) {
            flash('error', 'Titulaire introuvable');
            $this->redirect('titulaires');
            return;
        }
        
        // Vérifier si le titulaire a des comptes
        if (Titulaire::hasComptes($id)) {
            flash('error', 'Impossible de supprimer ce titulaire car il est lié à un ou plusieurs comptes');
            $this->redirect('titulaires');
            return;
        }
        
        if (Titulaire::delete($id)) {
            flash('success', 'Titulaire supprimé avec succès');
        } else {
            flash('error', 'Erreur lors de la suppression');
        }
        
        $this->redirect('titulaires');
    }
    
    /**
     * Recherche AJAX
     */
    public function search(): void
    {
        $this->requireAuth();
        
        $query = $_GET['q'] ?? '';
        
        if (empty($query)) {
            $this->jsonResponse([]);
            return;
        }
        
        $titulaires = Titulaire::search($query);
        $this->jsonResponse($titulaires);
    }
}
