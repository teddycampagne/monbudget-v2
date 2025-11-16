<?php

namespace MonBudget\Controllers;

use MonBudget\Models\Recurrence;
use MonBudget\Models\Transaction;
use MonBudget\Models\Compte;
use MonBudget\Models\Categorie;
use MonBudget\Models\Tiers;
use MonBudget\Services\RecurrenceService;

/**
 * Contrôleur de gestion des récurrences bancaires
 * 
 * Gère toutes les opérations CRUD sur les récurrences (modèles générateurs de transactions).
 * Permet de créer, modifier, supprimer des récurrences et d'exécuter manuellement des occurrences.
 * 
 * @package MonBudget\Controllers
 * @author MonBudget
 * @version 2.0.0 (Session 11 - Refactoring table séparée)
 */
class RecurrenceController extends BaseController
{
    /**
     * Afficher la liste des récurrences actives
     * 
     * @param int|null $compteId Optionnel : filtrer par compte
     * @return void
     */
    public function index(?int $compteId = null): void
    {
        $this->requireAuth();
        
        $compte = null;
        
        // Si filtre par compte
        if ($compteId !== null) {
            $compte = Compte::find($compteId);
            if (!$compte || $compte['user_id'] != $this->userId) {
                flash('error', 'Compte non trouvé');
                $this->redirect('comptes');
                return;
            }
        }
        
        // Récupérer les récurrences actives
        $recurrences = Recurrence::getActives($compteId);
        
        $this->view('recurrences.index', [
            'recurrences' => $recurrences,
            'compte' => $compte,
            'title' => $compte ? 'Récurrences - ' . $compte['nom'] : 'Toutes les Récurrences'
        ]);
    }
    
    /**
     * Afficher le formulaire de création d'une récurrence
     * 
     * @param int $compteId ID du compte sur lequel créer la récurrence
     * @return void
     */
    public function create(int $compteId): void
    {
        $this->requireAuth();
        
        // Vérifier que le compte existe et appartient à l'utilisateur
        $compte = Compte::findWithBanque($compteId);
        if (!$compte || $compte['user_id'] != $this->userId) {
            flash('error', 'Compte non trouvé');
            $this->redirect('comptes');
            return;
        }
        
        // Récupérer les comptes actifs
        $comptes = Compte::getActifs();
        
        // Récupérer les catégories principales
        $categories = Categorie::getCategoriesPrincipales($this->userId);
        
        // Récupérer les tiers
        $tiers = Tiers::getAllByUser($this->userId);
        
        $this->view('recurrences.create', [
            'compte' => $compte,
            'comptes' => $comptes,
            'categories' => $categories,
            'tiers' => $tiers,
            'title' => 'Nouvelle Récurrence - ' . $compte['nom']
        ]);
    }
    
    /**
     * Créer une nouvelle récurrence
     * 
     * @return void
     */
    public function store(): void
    {
        $this->requireAuth();
        
        // Valider les données
        $data = [
            'user_id' => $this->userId,
            'compte_id' => (int) $_POST['compte_id'],
            'libelle' => trim($_POST['libelle']),
            'montant' => (float) $_POST['montant'],
            'type_operation' => $_POST['type_operation'],
            'frequence' => $_POST['frequence'],
            'intervalle' => (int) ($_POST['intervalle'] ?? 1),
            'date_debut' => $_POST['date_debut'],
            'prochaine_execution' => $_POST['date_debut'], // Initialement = date_debut
            'recurrence_active' => 1
        ];
        
        // Champs optionnels
        if (!empty($_POST['description'])) {
            $data['description'] = trim($_POST['description']);
        }
        if (!empty($_POST['compte_destination_id'])) {
            $data['compte_destination_id'] = (int) $_POST['compte_destination_id'];
        }
        if (!empty($_POST['categorie_id'])) {
            $data['categorie_id'] = (int) $_POST['categorie_id'];
        }
        if (!empty($_POST['sous_categorie_id'])) {
            $data['sous_categorie_id'] = (int) $_POST['sous_categorie_id'];
        }
        if (!empty($_POST['tiers_id'])) {
            $data['tiers_id'] = (int) $_POST['tiers_id'];
        }
        if (!empty($_POST['moyen_paiement'])) {
            $data['moyen_paiement'] = $_POST['moyen_paiement'];
        }
        if (!empty($_POST['beneficiaire'])) {
            $data['beneficiaire'] = trim($_POST['beneficiaire']);
        }
        
        // Date de fin (optionnelle)
        if (!empty($_POST['date_fin']) && $_POST['date_fin'] !== '0000-00-00') {
            $data['date_fin'] = $_POST['date_fin'];
        } else {
            $data['date_fin'] = null;
        }
        
        // Nombre max d'exécutions (optionnel)
        if (!empty($_POST['nb_executions_max'])) {
            $data['nb_executions_max'] = (int) $_POST['nb_executions_max'];
        }
        
        // Jour d'exécution
        if (!empty($_POST['jour_execution'])) {
            $data['jour_execution'] = (int) $_POST['jour_execution'];
        }
        
        // Validation automatique
        $data['auto_validation'] = isset($_POST['auto_validation']) ? 1 : 0;
        
        // Tolérance weekend
        if (!empty($_POST['tolerance_weekend'])) {
            $data['tolerance_weekend'] = $_POST['tolerance_weekend'];
        }
        
        // Créer la récurrence
        $id = Recurrence::create($data);
        
        if ($id) {
            // Si création immédiate demandée (date_debut = aujourd'hui)
            // ✅ IMPORTANT : Exécuter AVANT calculerProchaineExecution pour créer à date_debut
            if (isset($_POST['creer_occurrence_immediate']) && $_POST['date_debut'] === date('Y-m-d')) {
                Recurrence::executerRecurrence($id);
                flash('success', 'Récurrence créée et première occurrence générée');
            } else {
                // Calculer la prochaine exécution (seulement si pas d'occurrence immédiate)
                $recurrence = Recurrence::find($id);
                $prochaineExec = Recurrence::calculerProchaineExecution($recurrence);
                
                if ($prochaineExec) {
                    Recurrence::update($id, ['prochaine_execution' => $prochaineExec]);
                }
                flash('success', 'Récurrence créée avec succès');
            }
            
            $this->redirect('comptes/' . $data['compte_id'] . '/recurrences');
        } else {
            flash('error', 'Erreur lors de la création de la récurrence');
            $this->redirect('comptes/' . $data['compte_id'] . '/recurrences/create');
        }
    }
    
    /**
     * Afficher le formulaire d'édition d'une récurrence
     * 
     * @param int $id ID de la récurrence
     * @return void
     */
    public function edit(int $id): void
    {
        $this->requireAuth();
        
        $recurrence = Recurrence::find($id);
        
        if (!$recurrence || $recurrence['user_id'] != $this->userId) {
            flash('error', 'Récurrence non trouvée');
            $this->redirect('comptes');
            return;
        }
        
        // Récupérer le compte
        $compte = Compte::findWithBanque($recurrence['compte_id']);
        
        // Récupérer les comptes actifs
        $comptes = Compte::getActifs();
        
        // Récupérer les catégories principales
        $categories = Categorie::getCategoriesPrincipales($this->userId);
        
        // Récupérer les sous-catégories de la catégorie sélectionnée
        $sousCategories = [];
        if ($recurrence['categorie_id']) {
            $sousCategories = Categorie::getSousCategories($recurrence['categorie_id']);
        }
        
        // Récupérer les tiers
        $tiers = Tiers::getAllByUser($this->userId);
        
        // Compter les occurrences générées
        $nbOccurrences = Recurrence::countOccurrences($id);
        
        $this->view('recurrences.edit', [
            'recurrence' => $recurrence,
            'compte' => $compte,
            'comptes' => $comptes,
            'categories' => $categories,
            'sousCategories' => $sousCategories,
            'tiers' => $tiers,
            'nbOccurrences' => $nbOccurrences,
            'title' => 'Modifier Récurrence - ' . $recurrence['libelle']
        ]);
    }
    
    /**
     * Mettre à jour une récurrence
     * 
     * @param int $id ID de la récurrence
     * @return void
     */
    public function update(int $id): void
    {
        $this->requireAuth();
        
        $recurrence = Recurrence::find($id);
        
        if (!$recurrence || $recurrence['user_id'] != $this->userId) {
            flash('error', 'Récurrence non trouvée');
            $this->redirect('comptes');
            return;
        }
        
        // Valider les données
        $data = [
            'libelle' => trim($_POST['libelle']),
            'montant' => (float) $_POST['montant'],
            'type_operation' => $_POST['type_operation'],
            'frequence' => $_POST['frequence'],
            'intervalle' => (int) ($_POST['intervalle'] ?? 1),
            'date_debut' => $_POST['date_debut'],
        ];
        
        // Champs optionnels
        if (!empty($_POST['description'])) {
            $data['description'] = trim($_POST['description']);
        }
        if (!empty($_POST['compte_destination_id'])) {
            $data['compte_destination_id'] = (int) $_POST['compte_destination_id'];
        } else {
            $data['compte_destination_id'] = null;
        }
        if (!empty($_POST['categorie_id'])) {
            $data['categorie_id'] = (int) $_POST['categorie_id'];
        } else {
            $data['categorie_id'] = null;
        }
        if (!empty($_POST['sous_categorie_id'])) {
            $data['sous_categorie_id'] = (int) $_POST['sous_categorie_id'];
        } else {
            $data['sous_categorie_id'] = null;
        }
        if (!empty($_POST['tiers_id'])) {
            $data['tiers_id'] = (int) $_POST['tiers_id'];
        } else {
            $data['tiers_id'] = null;
        }
        if (!empty($_POST['moyen_paiement'])) {
            $data['moyen_paiement'] = $_POST['moyen_paiement'];
        }
        if (!empty($_POST['beneficiaire'])) {
            $data['beneficiaire'] = trim($_POST['beneficiaire']);
        }
        
        // Date de fin (optionnelle)
        if (!empty($_POST['date_fin']) && $_POST['date_fin'] !== '0000-00-00') {
            $data['date_fin'] = $_POST['date_fin'];
        } else {
            $data['date_fin'] = null;
        }
        
        // Nombre max d'exécutions (optionnel)
        if (!empty($_POST['nb_executions_max'])) {
            $data['nb_executions_max'] = (int) $_POST['nb_executions_max'];
        } else {
            $data['nb_executions_max'] = null;
        }
        
        // Jour d'exécution
        if (!empty($_POST['jour_execution'])) {
            $data['jour_execution'] = (int) $_POST['jour_execution'];
        }
        
        // Validation automatique
        $data['auto_validation'] = isset($_POST['auto_validation']) ? 1 : 0;
        
        // Tolérance weekend
        if (!empty($_POST['tolerance_weekend'])) {
            $data['tolerance_weekend'] = $_POST['tolerance_weekend'];
        }
        
        // Actif/Inactif
        $data['recurrence_active'] = isset($_POST['recurrence_active']) ? 1 : 0;
        
        // Mettre à jour
        $updated = Recurrence::update($id, $data);
        
        if ($updated) {
            flash('success', 'Récurrence modifiée avec succès');
        } else {
            flash('error', 'Erreur lors de la modification');
        }
        
        $this->redirect('comptes/' . $recurrence['compte_id'] . '/recurrences');
    }
    
    /**
     * Exécuter manuellement une récurrence (créer occurrence immédiate)
     * 
     * @param int $id ID de la récurrence
     * @return void
     */
    public function execute(int $id): void
    {
        $this->requireAuth();
        
        $recurrence = Recurrence::find($id);
        
        if (!$recurrence || $recurrence['user_id'] != $this->userId) {
            flash('error', 'Récurrence non trouvée');
            $this->redirect('comptes');
            return;
        }
        
        // Exécuter la récurrence
        $transactionId = Recurrence::executerRecurrence($id);
        
        if ($transactionId) {
            flash('success', 'Transaction créée avec succès (ID: ' . $transactionId . ')');
        } else {
            flash('error', 'Erreur lors de l\'exécution de la récurrence');
        }
        
        $this->redirect('comptes/' . $recurrence['compte_id'] . '/recurrences');
    }
    
    /**
     * Supprimer une récurrence (avec modal pour choix modèle ou tout)
     * 
     * @param int $id ID de la récurrence
     * @return void
     */
    public function destroy(int $id): void
    {
        $this->requireAuth();
        
        $recurrence = Recurrence::find($id);
        
        if (!$recurrence || $recurrence['user_id'] != $this->userId) {
            flash('error', 'Récurrence non trouvée');
            $this->redirect('comptes');
            return;
        }
        
        $modeDelete = $_POST['mode'] ?? 'modele'; // modele | tout
        
        if ($modeDelete === 'tout') {
            // ✅ FIX BUG 7 : Suppression via recurrence_id (plus d'heuristique)
            $result = Recurrence::deleteWithOccurrences($id);
            
            flash('success', sprintf(
                'Récurrence supprimée (%d occurrence(s) supprimée(s))',
                $result['occurrences']
            ));
        } else {
            // Supprimer uniquement le modèle (ON DELETE SET NULL sur FK)
            $result = Recurrence::deleteModeleOnly($id);
            
            if ($result) {
                flash('success', 'Récurrence supprimée (les transactions déjà créées sont conservées)');
            } else {
                flash('error', 'Erreur lors de la suppression');
            }
        }
        
        $this->redirect('comptes/' . $recurrence['compte_id'] . '/recurrences');
    }
    
    /**
     * API : Compter les occurrences d'une récurrence
     * 
     * @param int $id ID de la récurrence
     * @return void
     */
    public function apiCountOccurrences(int $id): void
    {
        $this->requireAuth();
        
        $recurrence = Recurrence::find($id);
        
        if (!$recurrence || $recurrence['user_id'] != $this->userId) {
            http_response_code(404);
            echo json_encode(['error' => 'Récurrence non trouvée']);
            return;
        }
        
        $count = Recurrence::countOccurrences($id);
        
        header('Content-Type: application/json');
        echo json_encode(['count' => $count]);
    }
    
    /**
     * Afficher les statistiques d'administration des récurrences
     * 
     * @return void
     */
    public function admin(): void
    {
        $this->requireAuth();
        
        // Statistiques globales
        $stats = [
            'total_recurrences' => Recurrence::countTotal($this->userId),
            'actives' => Recurrence::countActives($this->userId),
            'inactives' => Recurrence::countInactives($this->userId),
            'echues' => Recurrence::countEchues($this->userId),
            'total_transactions_generees' => Recurrence::countTransactionsGenerees($this->userId)
        ];
        
        // Dernière exécution automatique
        $service = new RecurrenceService();
        $lastExecution = $service->getLastExecutionStats();
        
        // Prochaines exécutions (7 prochains jours)
        $prochainesExecutions = Recurrence::getUpcoming($this->userId, 7);
        
        // Récurrences les plus actives (par nombre de transactions générées)
        $topRecurrences = Recurrence::getTopByTransactions($this->userId, 10);
        
        // Logs récents (5 dernières lignes)
        $recentLogs = $this->getRecentLogs(5);
        
        $this->view('recurrences.admin', [
            'stats' => $stats,
            'lastExecution' => $lastExecution,
            'prochainesExecutions' => $prochainesExecutions,
            'topRecurrences' => $topRecurrences,
            'recentLogs' => $recentLogs,
            'title' => 'Administration des Récurrences'
        ]);
    }
    
    /**
     * Lire les dernières lignes du log de récurrence
     * 
     * @param int $lines Nombre de lignes à récupérer
     * @return array
     */
    private function getRecentLogs(int $lines = 5): array
    {
        $logFile = BASE_PATH . '/storage/logs/recurrence_auto_' . date('Y-m') . '.log';
        
        if (!file_exists($logFile)) {
            return [];
        }
        
        $allLines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (empty($allLines)) {
            return [];
        }
        
        // Prendre les N dernières lignes
        return array_slice($allLines, -$lines);
    }
}
