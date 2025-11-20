<?php

namespace MonBudget\Controllers;

use MonBudget\Models\Budget;
use MonBudget\Models\Categorie;

/**
 * Contrôleur de gestion des budgets
 * 
 * Gère toutes les opérations CRUD sur les budgets : création, modification,
 * suppression, consultation. Gère le suivi budgétaire mensuel/annuel,
 * les alertes de dépassement et les statistiques.
 * 
 * @package MonBudget\Controllers
 * @author MonBudget
 * @version 1.0.0
 */
class BudgetController extends BaseController
{
    /**
     * Lister les budgets avec statistiques
     * 
     * Affiche la liste des budgets pour une période donnée (mois ou année)
     * avec leurs statistiques : montant alloué, dépensé, restant, dépassements.
     * 
     * @return void
     */
    public function index(): void
    {
        $annee = (int) ($_GET['annee'] ?? date('Y'));
        $mois = isset($_GET['mois']) && $_GET['mois'] !== '' ? (int) $_GET['mois'] : null;
        
        $budgets = Budget::getAllByPeriod($this->userId, $annee, $mois);
        $stats = Budget::getStats($this->userId, $annee, $mois);
        $depassements = Budget::getDepassements($this->userId, $annee, $mois);
        
        $this->data['budgets'] = $budgets;
        $this->data['stats'] = $stats;
        $this->data['depassements'] = $depassements;
        $this->data['annee'] = $annee;
        $this->data['mois'] = $mois;
        $this->data['periode_label'] = $mois !== null 
            ? $this->getMoisNom($mois) . ' ' . $annee 
            : 'Année ' . $annee;
        
        $this->view('budgets/index');
    }
    
    /**
     * Afficher le formulaire de création de budget
     * 
     * Charge les catégories disponibles (non encore budgétisées) pour
     * la période sélectionnée (mois ou année).
     * 
     * @return void
     */
    public function create(): void
    {
        $annee = (int) ($_GET['annee'] ?? date('Y'));
        $mois = isset($_GET['mois']) && $_GET['mois'] !== '' ? (int) $_GET['mois'] : null;
        
        $categories = Budget::getCategoriesDisponibles($this->userId, $annee, $mois);
        
        $this->data['categories'] = $categories;
        $this->data['annee'] = $annee;
        $this->data['mois'] = $mois;
        
        $this->view('budgets/create');
    }
    
    /**
     * Créer un budget
     */
    public function store(): void
    {
        // Initialiser service audit PCI DSS
        $audit = new AuditLogService();
        
        $periode = $_POST['periode'] ?? 'mensuel';
        $annee = (int) $_POST['annee'];
        $categorieId = (int) $_POST['categorie_id'];
        
        if ($periode === 'mensuel') {
            // Budget mensuel simple
            $data = [
                'user_id' => $this->userId,
                'categorie_id' => $categorieId,
                'montant' => (float) $_POST['montant'],
                'periode' => 'mensuel',
                'annee' => $annee,
                'mois' => (int) $_POST['mois']
            ];
            
            if ($data['montant'] <= 0) {
                flash('error', 'Le montant doit être supérieur à 0');
                $this->redirect('budgets/create?' . http_build_query(['annee' => $annee, 'mois' => $data['mois']]));
                return;
            }
            
            $budgetId = Budget::create($data);
            
            if ($budgetId) {
                // Logger création (PCI DSS audit)
                $audit->logCreate('budgets', $budgetId, $data);
                
                flash('success', 'Budget créé avec succès');
            } else {
                flash('error', 'Un budget existe déjà pour cette catégorie et cette période');
            }
            
            $this->redirect('budgets?' . http_build_query(['annee' => $annee, 'mois' => $data['mois']]));
            
        } else {
            // Budget annuel : montants mensuels
            $montantsMensuels = $_POST['montants_mensuels'] ?? [];
            $nbCrees = 0;
            $totalAnnuel = 0;
            
            foreach ($montantsMensuels as $mois => $montant) {
                $montant = (float) $montant;
                
                if ($montant <= 0) {
                    continue; // Ignorer les mois sans montant
                }
                
                $totalAnnuel += $montant;
                
                $data = [
                    'user_id' => $this->userId,
                    'categorie_id' => $categorieId,
                    'montant' => $montant,
                    'periode' => 'mensuel',
                    'annee' => $annee,
                    'mois' => (int) $mois
                ];
                
                $budgetId = Budget::create($data);
                if ($budgetId) {
                    // Logger création (PCI DSS audit)
                    $audit->logCreate('budgets', $budgetId, $data);
                    $nbCrees++;
                }
            }
            
            if ($nbCrees > 0) {
                flash('success', "Budget annuel créé avec succès ($nbCrees mois configurés, total : " . number_format($totalAnnuel, 2) . " €)");
            } else {
                flash('error', 'Aucun budget créé (vérifiez les montants saisis ou si des budgets existent déjà)');
            }
            
            $this->redirect('budgets?' . http_build_query(['annee' => $annee]));
        }
    }
    
    /**
     * Formulaire d'édition
     */
    public function edit(int $id): void
    {
        $budget = Budget::find($id);
        
        if (!$budget || $budget['user_id'] != $this->userId) {
            flash('error', 'Budget introuvable');
            $this->redirect('budgets');
            return;
        }
        
        $this->data['budget'] = $budget;
        
        $this->view('budgets/edit');
    }
    
    /**
     * Mettre à jour un budget
     */
    public function update(int $id): void
    {
        // Initialiser service audit PCI DSS
        $audit = new AuditLogService();
        
        $budget = Budget::find($id);
        
        if (!$budget || $budget['user_id'] != $this->userId) {
            flash('error', 'Budget introuvable');
            $this->redirect('budgets');
            return;
        }
        
        $data = [
            'montant' => (float) $_POST['montant'],
            'periode' => $_POST['periode'] ?? 'mensuel',
            'annee' => (int) $_POST['annee']
        ];
        
        if (!empty($_POST['mois'])) {
            $data['mois'] = (int) $_POST['mois'];
        }
        
        // Validation
        if ($data['montant'] <= 0) {
            flash('error', 'Le montant doit être supérieur à 0');
            $this->redirect('budgets/' . $id . '/edit');
            return;
        }
        
        // Sauvegarder anciennes valeurs pour audit
        $oldValues = $budget;
        
        Budget::update($id, $data);
        
        // Logger modification (PCI DSS audit)
        $audit->logUpdate('budgets', $id, $oldValues, $data);
        
        flash('success', 'Budget modifié avec succès');
        $this->redirect('budgets?' . http_build_query(['annee' => $data['annee'], 'mois' => $data['mois'] ?? '']));
    }
    
    /**
     * Supprimer un budget
     */
    public function delete(int $id): void
    {
        // Initialiser service audit PCI DSS
        $audit = new AuditLogService();
        
        $budget = Budget::find($id);
        
        if (!$budget || $budget['user_id'] != $this->userId) {
            flash('error', 'Budget introuvable');
            $this->redirect('budgets');
            return;
        }
        
        Budget::delete($id);
        
        // Logger suppression (PCI DSS audit)
        $audit->logDelete('budgets', $id, $budget);
        
        flash('success', 'Budget supprimé avec succès');
        $this->redirect('budgets?' . http_build_query(['annee' => $budget['annee'], 'mois' => $budget['mois'] ?? '']));
    }
    
    /**
     * Supprimer tous les budgets mensuels d'une catégorie pour une année
     */
    public function deleteAnnual(): void
    {
        $categorieId = (int) ($_GET['categorie_id'] ?? 0);
        $annee = (int) ($_GET['annee'] ?? 0);
        
        if (!$categorieId || !$annee) {
            flash('error', 'Paramètres invalides');
            $this->redirect('budgets');
            return;
        }
        
        // Supprimer tous les budgets mensuels de cette catégorie pour cette année
        $nbSupprimes = Budget::deleteByCategorieMensuel($this->userId, $categorieId, $annee);
        
        if ($nbSupprimes > 0) {
            flash('success', "Budget annuel supprimé avec succès ($nbSupprimes mois)");
        } else {
            flash('error', 'Aucun budget trouvé');
        }
        
        $this->redirect('budgets?' . http_build_query(['annee' => $annee]));
    }
    
    /**
     * Obtenir le nom du mois
     */
    private function getMoisNom(int $mois): string
    {
        $moisNoms = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];
        
        return $moisNoms[$mois] ?? '';
    }
    
    /**
     * Formulaire de génération automatique de budgets
     */
    public function generate(): void
    {
        $anneeActuelle = (int) date('Y');
        
        $this->data['annee_actuelle'] = $anneeActuelle;
        $this->view('budgets/generate');
    }
    
    /**
     * Prévisualiser les budgets générés
     */
    public function preview(): void
    {
        $anneeSource = (int) ($_POST['annee_source'] ?? date('Y') - 1);
        $anneeCible = (int) ($_POST['annee_cible'] ?? date('Y'));
        $nbMoisAnalyse = (int) ($_POST['nb_mois_analyse'] ?? 12);
        $excludeOutliers = isset($_POST['exclude_outliers']);
        $coefficientAjustement = 1 + ((float) ($_POST['ajustement'] ?? 0) / 100);
        $variationsSaisonnieres = isset($_POST['variations_saisonnieres']);
        
        // Analyser les transactions
        $suggestions = Budget::analyserTransactions($this->userId, $anneeSource, $nbMoisAnalyse, $excludeOutliers);
        
        // Générer les projections
        $projections = Budget::genererProjection($this->userId, $anneeCible, $suggestions, $coefficientAjustement, $variationsSaisonnieres);
        
        $this->data['projections'] = $projections;
        $this->data['annee_source'] = $anneeSource;
        $this->data['annee_cible'] = $anneeCible;
        $this->data['nb_mois_analyse'] = $nbMoisAnalyse;
        $this->data['ajustement'] = ($_POST['ajustement'] ?? 0);
        $this->data['exclude_outliers'] = $excludeOutliers;
        $this->data['variations_saisonnieres'] = $variationsSaisonnieres;
        
        $this->view('budgets/preview');
    }
    
    /**
     * Créer les budgets à partir de la projection
     */
    public function createFromProjection(): void
    {
        $projections = json_decode($_POST['projections'] ?? '[]', true);
        $anneeCible = (int) ($_POST['annee_cible'] ?? date('Y'));
        
        if (empty($projections)) {
            flash('error', 'Aucune projection à créer');
            $this->redirect('budgets/generate');
            return;
        }
        
        $nbCrees = 0;
        $nbEchecs = 0;
        
        foreach ($projections as $projection) {
            $categorieId = (int) $projection['categorie_id'];
            $budgetsMensuels = $projection['budgets_mensuels'] ?? [];
            
            foreach ($budgetsMensuels as $mois => $montant) {
                $data = [
                    'user_id' => $this->userId,
                    'categorie_id' => $categorieId,
                    'montant' => (float) $montant,
                    'periode' => 'mensuel',
                    'annee' => $anneeCible,
                    'mois' => (int) $mois
                ];
                
                if (Budget::create($data)) {
                    $nbCrees++;
                } else {
                    $nbEchecs++;
                }
            }
        }
        
        if ($nbCrees > 0) {
            $message = "Budgets générés avec succès : $nbCrees budgets créés";
            if ($nbEchecs > 0) {
                $message .= " ($nbEchecs déjà existants ignorés)";
            }
            flash('success', $message);
        } else {
            flash('error', 'Aucun budget créé (tous existent déjà)');
        }
        
        $this->redirect('budgets?' . http_build_query(['annee' => $anneeCible]));
    }
}
