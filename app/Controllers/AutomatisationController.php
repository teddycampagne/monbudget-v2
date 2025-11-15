<?php

namespace MonBudget\Controllers;

use MonBudget\Models\RegleAutomatisation;
use MonBudget\Models\Categorie;
use MonBudget\Models\Tiers;

/**
 * Contrôleur de gestion des règles d'automatisation
 * 
 * Gère la création et la gestion des règles d'automatisation pour la catégorisation
 * automatique des transactions. Les règles permettent d'affecter automatiquement
 * une catégorie et/ou un tiers selon des critères (montant, libellé, etc.).
 * 
 * @package MonBudget\Controllers
 * @author MonBudget
 * @version 1.0.0
 */
class AutomatisationController extends BaseController
{
    /**
     * Lister les règles d'automatisation
     * 
     * Affiche toutes les règles de l'utilisateur triées par priorité.
     * 
     * @return void
     */
    public function index(): void
    {
        $this->requireAuth();
        
        $regles = RegleAutomatisation::where(['user_id' => $this->userId]);
        
        // Trier par priorité
        usort($regles, function($a, $b) {
            return $a['priorite'] <=> $b['priorite'];
        });
        
        $this->view('automatisation.index', [
            'regles' => $regles,
            'title' => 'Règles d\'automatisation'
        ]);
    }
    
    /**
     * Afficher le formulaire de création
     */
    public function create(): void
    {
        $this->requireAuth();
        
        // Récupérer uniquement les catégories principales (parent_id IS NULL)
        $categories = Categorie::getCategoriesPrincipales($this->userId);
        $tiers = Tiers::where(['user_id' => $this->userId]);
        
        $this->view('automatisation.create', [
            'categories' => $categories,
            'tiers' => $tiers,
            'title' => 'Créer une règle'
        ]);
    }
    
    /**
     * Créer une nouvelle règle
     */
    public function store(): void
    {
        $this->requireAuth();
        
        if (!$this->validateCsrfOrFail('automatisation')) return;
        
        $data = $this->validate([
            'nom' => 'required|max:255',
            'pattern' => 'required|max:500',
            'type_pattern' => 'required',
            'priorite' => 'integer'
        ]);
        
        $data['user_id'] = $this->userId;
        $data['case_sensitive'] = isset($_POST['case_sensitive']) ? 1 : 0;
        $data['actif'] = isset($_POST['actif']) ? 1 : 0;
        
        // Actions optionnelles
        $data['action_categorie'] = !empty($_POST['action_categorie']) ? (int)$_POST['action_categorie'] : null;
        $data['action_sous_categorie'] = !empty($_POST['action_sous_categorie']) ? (int)$_POST['action_sous_categorie'] : null;
        $data['action_tiers'] = !empty($_POST['action_tiers']) ? (int)$_POST['action_tiers'] : null;
        $data['action_moyen_paiement'] = !empty($_POST['action_moyen_paiement']) ? $_POST['action_moyen_paiement'] : null;
        
        $id = RegleAutomatisation::create($data);
        
        if ($id) {
            flash('success', 'Règle créée avec succès');
        } else {
            flash('error', 'Erreur lors de la création de la règle');
        }
        
        $this->redirect('automatisation');
    }
    
    /**
     * Afficher le formulaire d'édition
     */
    public function edit(int $id): void
    {
        $this->requireAuth();
        
        $regle = RegleAutomatisation::find($id);
        
        if (!$regle || $regle['user_id'] != $this->userId) {
            flash('error', 'Règle non trouvée');
            $this->redirect('automatisation');
            return;
        }
        
        $categories = Categorie::getCategoriesPrincipales($this->userId);
        $sousCategories = [];
        if ($regle['action_categorie']) {
            $sousCategories = Categorie::getSousCategories($regle['action_categorie']);
        }
        $tiers = Tiers::where(['user_id' => $this->userId]);
        
        $this->view('automatisation.edit', [
            'regle' => $regle,
            'categories' => $categories,
            'sousCategories' => $sousCategories,
            'tiers' => $tiers,
            'title' => 'Modifier la règle'
        ]);
    }
    
    /**
     * Mettre à jour une règle
     */
    public function update(int $id): void
    {
        $this->requireAuth();
        
        if (!$this->validateCsrfOrFail('automatisation')) return;
        
        $regle = RegleAutomatisation::find($id);
        
        if (!$regle || $regle['user_id'] != $this->userId) {
            flash('error', 'Règle non trouvée');
            $this->redirect('automatisation');
            return;
        }
        
        $data = $this->validate([
            'nom' => 'required|max:255',
            'pattern' => 'required|max:500',
            'type_pattern' => 'required',
            'priorite' => 'integer'
        ]);
        
        $data['case_sensitive'] = isset($_POST['case_sensitive']) ? 1 : 0;
        $data['actif'] = isset($_POST['actif']) ? 1 : 0;
        
        $data['action_categorie'] = !empty($_POST['action_categorie']) ? (int)$_POST['action_categorie'] : null;
        $data['action_sous_categorie'] = !empty($_POST['action_sous_categorie']) ? (int)$_POST['action_sous_categorie'] : null;
        $data['action_tiers'] = !empty($_POST['action_tiers']) ? (int)$_POST['action_tiers'] : null;
        $data['action_moyen_paiement'] = !empty($_POST['action_moyen_paiement']) ? $_POST['action_moyen_paiement'] : null;
        
        $result = RegleAutomatisation::update($id, $data);
        
        if ($result >= 0) {
            flash('success', 'Règle mise à jour avec succès');
        } else {
            flash('error', 'Erreur lors de la mise à jour de la règle');
        }
        
        $this->redirect('automatisation');
    }
    
    /**
     * Supprimer une règle
     */
    public function destroy(int $id): void
    {
        $this->requireAuth();
        
        if (!$this->validateCsrfOrFail('automatisation')) return;
        
        $regle = RegleAutomatisation::find($id);
        
        if (!$regle || $regle['user_id'] != $this->userId) {
            flash('error', 'Règle non trouvée');
            $this->redirect('automatisation');
            return;
        }
        
        $result = RegleAutomatisation::delete($id);
        
        if ($result) {
            flash('success', 'Règle supprimée avec succès');
        } else {
            flash('error', 'Erreur lors de la suppression de la règle');
        }
        
        $this->redirect('automatisation');
    }
    
    /**
     * Tester une règle
     */
    public function test(): void
    {
        $this->requireAuth();
        
        $libelle = $_POST['libelle'] ?? '';
        $ruleId = $_POST['rule_id'] ?? null;
        
        if ($ruleId) {
            $regle = RegleAutomatisation::find($ruleId);
            if (!$regle || $regle['user_id'] != $this->userId) {
                $this->json(['error' => 'Règle non trouvée'], 404);
                return;
            }
            
            $matched = RegleAutomatisation::testRule($regle, $libelle);
            $this->json(['matched' => $matched, 'rule' => $regle]);
        } else {
            // Tester toutes les règles
            $result = RegleAutomatisation::applyRules($this->userId, $libelle);
            $this->json($result);
        }
    }
    
    /**
     * Appliquer les règles sur toutes les transactions (rétroactivité)
     */
    public function applyToAll(): void
    {
        $this->requireAuth();
        
        if (!$this->validateCsrfOrFail('automatisation')) return;
        
        $stats = RegleAutomatisation::applyToAllTransactions($this->userId);
        
        flash('success', sprintf(
            'Application terminée : %d transactions traitées, %d mises à jour, %d ignorées',
            $stats['total'],
            $stats['updated'],
            $stats['skipped']
        ));
        
        $this->redirect('automatisation');
    }
    
    /**
     * Activer/désactiver une règle (AJAX)
     */
    public function toggle(int $id): void
    {
        $this->requireAuth();
        
        $regle = RegleAutomatisation::find($id);
        
        if (!$regle || $regle['user_id'] != $this->userId) {
            $this->json(['error' => 'Règle non trouvée'], 404);
            return;
        }
        
        $newState = !$regle['actif'];
        RegleAutomatisation::update($id, ['actif' => $newState]);
        
        $this->json(['success' => true, 'actif' => $newState]);
    }
}
