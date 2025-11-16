<?php

namespace MonBudget\Controllers;

use MonBudget\Models\Transaction;
use MonBudget\Models\Compte;
use MonBudget\Models\Categorie;
use MonBudget\Models\Tiers;
use MonBudget\Models\RegleAutomatisation;
use MonBudget\Models\Recurrence;
use MonBudget\Models\Attachment;
use MonBudget\Services\FileUploadService;

/**
 * Contrôleur de gestion des transactions bancaires
 * 
 * Gère toutes les opérations CRUD sur les transactions : création, modification,
 * suppression, consultation. Gère également la catégorisation automatique via les
 * règles d'automatisation et les transactions récurrentes.
 * 
 * @package MonBudget\Controllers
 * @author MonBudget
 * @version 1.0.0
 */
class TransactionController extends BaseController
{
    /**
     * Afficher la liste des transactions d'un compte
     * 
     * Récupère et affiche toutes les transactions d'un compte spécifique.
     * Vérifie que le compte appartient à l'utilisateur connecté.
     * 
     * @param int $compteId ID du compte dont afficher les transactions
     * @return void
     */
    public function index(int $compteId): void
    {
        $this->requireAuth();
        
        // Vérifier que le compte existe et appartient à l'utilisateur
        $compte = Compte::find($compteId);
        if (!$compte || $compte['user_id'] != $this->userId) {
            flash('error', 'Compte non trouvé');
            $this->redirect('comptes');
            return;
        }
        
        // Récupérer les transactions du compte
        $transactions = Transaction::getByCompte($compteId);
        
        $this->view('transactions.index', [
            'transactions' => $transactions,
            'compte' => $compte,
            'title' => 'Transactions - ' . $compte['nom']
        ]);
    }
    
    /**
     * Afficher le formulaire de création d'une transaction
     * 
     * Charge les données nécessaires pour le formulaire : comptes actifs,
     * catégories principales (les sous-catégories sont chargées via AJAX),
     * et la liste des tiers enregistrés.
     * 
     * @param int $compteId ID du compte sur lequel créer la transaction
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
        
        // Récupérer les comptes actifs de l'utilisateur
        $comptes = Compte::getActifs();
        
        // Récupérer les catégories
        // Récupérer les catégories principales uniquement (les sous-catégories sont chargées via AJAX)
        $categories = Categorie::getCategoriesPrincipales($this->userId);
        
        // Récupérer les tiers
        $tiers = Tiers::getAllByUser($this->userId);
        
        // Récupérer les tags
        $tagModel = new \MonBudget\Models\Tag();
        $tags = $tagModel->getAllByUser($this->userId, 'name');
        
        $this->view('transactions.create', [
            'compte' => $compte,
            'comptes' => $comptes,
            'categories' => $categories,
            'tiers' => $tiers,
            'tags' => $tags,
            'title' => 'Nouvelle Transaction - ' . $compte['nom']
        ]);
    }
    
    /**
     * Créer une nouvelle transaction
     * 
     * Valide les données du formulaire, crée la transaction et applique
     * automatiquement les règles de catégorisation si configurées.
     * Gère les crédits, débits et virements internes entre comptes.
     * 
     * @param int $compteId ID du compte concerné
     * @return void
     */
    public function store(int $compteId): void
    {
        $this->requireAuth();
        
        // Vérifier que le compte existe et appartient à l'utilisateur
        $compte = Compte::findWithBanque($compteId);
        if (!$compte || $compte['user_id'] != $this->userId) {
            $this->flashAndRedirect('error', 'Compte non trouvé', 'comptes');
            return;
        }
        
        if (!$this->validateCsrfOrFail("comptes/{$compteId}/transactions")) return;
        
        // Validation de base
        $data = $this->validate([
            'compte_id' => 'required|numeric',
            'compte_destination_id' => 'numeric',
            'categorie_id' => 'numeric',
            'sous_categorie_id' => 'numeric',
            'tiers_id' => 'numeric',
            'date_transaction' => 'required',
            'montant' => 'required|numeric',
            'libelle' => 'required|max:255',
            'description' => '',
            'type_operation' => 'required',
            'moyen_paiement' => '',
            'beneficiaire' => 'max:255',
            'est_recurrente' => 'numeric',
            'frequence' => '',
            'intervalle' => 'numeric',
            'jour_execution' => 'numeric',
            'date_debut' => '',
            'date_fin' => '',
            'nb_executions_max' => 'numeric',
            'auto_validation' => 'numeric',
            'tolerance_weekend' => ''
        ]);
        
        // Ajouter user_id
        $data['user_id'] = $_SESSION['user']['id'];
        
        // Traitement des champs nullable (NULL si vide)
        $data['categorie_id'] = !empty($data['categorie_id']) ? (int)$data['categorie_id'] : null;
        $data['sous_categorie_id'] = !empty($data['sous_categorie_id']) ? (int)$data['sous_categorie_id'] : null;
        $data['tiers_id'] = !empty($data['tiers_id']) ? (int)$data['tiers_id'] : null;
        $data['compte_destination_id'] = !empty($data['compte_destination_id']) ? (int)$data['compte_destination_id'] : null;
        
        // ✅ NOUVEAU : Détecter si c'est une récurrence
        $estRecurrente = isset($_POST['est_recurrente']) && $_POST['est_recurrente'];
        
        // Traitement des booléens (pour transactions normales)
        $data['validee'] = 1; // Par défaut validée
        $data['est_recurrente'] = 0; // TOUJOURS 0 maintenant (nouvelle archi)
        
        // Appliquer les règles d'automatisation sur les champs non renseignés
        $automatisation = RegleAutomatisation::applyRules($this->userId, $data['libelle']);
        
        if ($automatisation['categorie_id'] && !$data['categorie_id']) {
            $data['categorie_id'] = $automatisation['categorie_id'];
        }
        if ($automatisation['sous_categorie_id'] && !$data['sous_categorie_id']) {
            $data['sous_categorie_id'] = $automatisation['sous_categorie_id'];
        }
        if ($automatisation['tiers_id'] && !$data['tiers_id']) {
            $data['tiers_id'] = $automatisation['tiers_id'];
        }
        if ($automatisation['moyen_paiement'] && empty($data['moyen_paiement'])) {
            $data['moyen_paiement'] = $automatisation['moyen_paiement'];
        }
        
        // Gestion des virements internes
        if ($data['type_operation'] === 'virement') {
            if (empty($data['compte_destination_id'])) {
                flash('error', 'Le compte de destination est obligatoire pour un virement interne');
                $this->redirect("comptes/{$compteId}/transactions/create");
                return;
            }
            
            // Vérifier que le compte de destination appartient à l'utilisateur
            $compteDestination = Compte::find($data['compte_destination_id']);
            if (!$compteDestination || $compteDestination['user_id'] != $this->userId) {
                flash('error', 'Compte de destination invalide');
                $this->redirect("comptes/{$compteId}/transactions/create");
                return;
            }
            
            // Créer la transaction de débit sur le compte source
            $dataDebit = $data;
            $dataDebit['type_operation'] = 'debit';
            $dataDebit['compte_id'] = $compteId;
            $idDebit = Transaction::create($dataDebit);
            
            // Créer la transaction de crédit sur le compte destination
            $dataCredit = $data;
            $dataCredit['type_operation'] = 'credit';
            $dataCredit['compte_id'] = $data['compte_destination_id'];
            $dataCredit['compte_destination_id'] = $compteId; // Référence inverse
            $idCredit = Transaction::create($dataCredit);
            
            if ($idDebit && $idCredit) {
                // Recalculer les soldes des deux comptes
                Compte::recalculerSolde($compteId);
                Compte::recalculerSolde($data['compte_destination_id']);
                
                flash('success', 'Virement interne créé avec succès');
                $this->redirect("comptes/{$compteId}/transactions");
            } else {
                flash('error', 'Erreur lors de la création du virement interne');
                $this->redirect("comptes/{$compteId}/transactions/create");
            }
        } else {
            // ✅ NOUVELLE ARCHITECTURE : Si récurrence, créer dans table recurrences
            if ($estRecurrente) {
                // Créer le modèle de récurrence
                $recurrenceData = [
                    'user_id' => $this->userId,
                    'compte_id' => $compteId,
                    'libelle' => $data['libelle'],
                    'montant' => $data['montant'],
                    'type_operation' => $data['type_operation'],
                    'frequence' => $_POST['frequence'] ?? 'mensuel',
                    'intervalle' => (int)($_POST['intervalle'] ?? 1),
                    'date_debut' => $_POST['date_debut'] ?? $data['date_transaction'],
                    'prochaine_execution' => $_POST['date_debut'] ?? $data['date_transaction'],
                    'recurrence_active' => 1
                ];
                
                // Champs optionnels
                if (!empty($data['description'])) $recurrenceData['description'] = $data['description'];
                if (!empty($data['compte_destination_id'])) $recurrenceData['compte_destination_id'] = $data['compte_destination_id'];
                if (!empty($data['categorie_id'])) $recurrenceData['categorie_id'] = $data['categorie_id'];
                if (!empty($data['sous_categorie_id'])) $recurrenceData['sous_categorie_id'] = $data['sous_categorie_id'];
                if (!empty($data['tiers_id'])) $recurrenceData['tiers_id'] = $data['tiers_id'];
                if (!empty($data['moyen_paiement'])) $recurrenceData['moyen_paiement'] = $data['moyen_paiement'];
                if (!empty($data['beneficiaire'])) $recurrenceData['beneficiaire'] = $data['beneficiaire'];
                if (!empty($_POST['date_fin']) && $_POST['date_fin'] !== '0000-00-00') {
                    $recurrenceData['date_fin'] = $_POST['date_fin'];
                }
                if (!empty($_POST['nb_executions_max'])) {
                    $recurrenceData['nb_executions_max'] = (int)$_POST['nb_executions_max'];
                }
                if (!empty($_POST['jour_execution'])) {
                    $recurrenceData['jour_execution'] = (int)$_POST['jour_execution'];
                }
                $recurrenceData['auto_validation'] = isset($_POST['auto_validation']) ? 1 : 0;
                if (!empty($_POST['tolerance_weekend'])) {
                    $recurrenceData['tolerance_weekend'] = $_POST['tolerance_weekend'];
                }
                
                $recurrenceId = Recurrence::create($recurrenceData);
                
                if ($recurrenceId) {
                    // Créer la transaction initiale avec lien vers récurrence
                    $data['recurrence_id'] = $recurrenceId;
                    $transactionId = Transaction::create($data);
                    
                    if ($transactionId) {
                        // Calculer prochaine exécution (après cette occurrence)
                        $recurrence = Recurrence::find($recurrenceId);
                        $prochaineExec = Recurrence::calculerProchaineExecution($recurrence);
                        Recurrence::update($recurrenceId, [
                            'prochaine_execution' => $prochaineExec,
                            'derniere_execution' => $data['date_transaction'],
                            'nb_executions' => 1
                        ]);
                        
                        Compte::recalculerSolde($compteId);
                        flash('success', 'Récurrence créée avec première occurrence');
                        $this->redirect("comptes/{$compteId}/transactions");
                    } else {
                        flash('error', 'Erreur lors de la création de l\'occurrence');
                        $this->redirect("comptes/{$compteId}/transactions/create");
                    }
                } else {
                    flash('error', 'Erreur lors de la création de la récurrence');
                    $this->redirect("comptes/{$compteId}/transactions/create");
                }
            } else {
                // Transaction normale (non récurrente)
                $id = Transaction::create($data);
                
                if ($id) {
                    // Gestion des tags
                    if (!empty($_POST['tags']) && is_array($_POST['tags'])) {
                        $transactionModel = new Transaction();
                        $transactionModel->syncTags($id, array_map('intval', $_POST['tags']));
                    }
                    
                    Compte::recalculerSolde($compteId);
                    flash('success', 'Transaction créée avec succès');
                    $this->redirect("comptes/{$compteId}/transactions");
                } else {
                    flash('error', 'Erreur lors de la création de la transaction');
                    $this->redirect("comptes/{$compteId}/transactions/create");
                }
            }
        }
    }
    
    /**
     * Dupliquer une transaction existante
     * 
     * Charge les données de la transaction source et redirige vers le formulaire
     * de création pré-rempli avec date=aujourd'hui et est_recurrente=0.
     * 
     * @param int $compteId ID du compte
     * @param int $id ID de la transaction à dupliquer
     * @return void
     */
    public function duplicate(int $compteId, int $id): void
    {
        $this->requireAuth();
        
        // Vérifier que le compte existe et appartient à l'utilisateur
        $compte = Compte::findWithBanque($compteId);
        if (!$compte || $compte['user_id'] != $this->userId) {
            flash('error', 'Compte non trouvé');
            $this->redirect('comptes');
            return;
        }
        
        // Récupérer la transaction source
        $transactionSource = Transaction::find($id);
        
        if (!$transactionSource || $transactionSource['compte_id'] != $compteId) {
            flash('error', 'Transaction introuvable');
            $this->redirect("comptes/{$compteId}/transactions");
            return;
        }
        
        // Récupérer les comptes actifs de l'utilisateur
        $comptes = Compte::getActifs();
        
        // Récupérer les catégories principales
        $categories = Categorie::getCategoriesPrincipales($this->userId);
        
        // Récupérer les tiers
        $tiers = Tiers::getAllByUser($this->userId);
        
        // Préparer les données pour le formulaire de création
        // Dupliquer TOUS les champs sauf id, created_at, updated_at
        $transactionDupliquee = [
            'compte_id' => $transactionSource['compte_id'],
            'compte_destination_id' => $transactionSource['compte_destination_id'],
            'categorie_id' => $transactionSource['categorie_id'],
            'sous_categorie_id' => $transactionSource['sous_categorie_id'],
            'tiers_id' => $transactionSource['tiers_id'],
            'date_transaction' => date('Y-m-d'), // Date du jour
            'montant' => abs($transactionSource['montant']), // Montant absolu
            'libelle' => $transactionSource['libelle'],
            'description' => $transactionSource['description'],
            'type_operation' => $transactionSource['type_operation'],
            'moyen_paiement' => $transactionSource['moyen_paiement'],
            'beneficiaire' => $transactionSource['beneficiaire'],
            'validee' => $transactionSource['validee'],
            'est_recurrente' => 0, // TOUJOURS 0 (transaction simple)
            'recurrence_id' => null // Pas de lien vers récurrence
        ];
        
        // Afficher le formulaire de création pré-rempli
        $this->view('transactions.create', [
            'compte' => $compte,
            'comptes' => $comptes,
            'categories' => $categories,
            'tiers' => $tiers,
            'transaction' => $transactionDupliquee, // Données pré-remplies
            'isDuplicate' => true, // Flag pour afficher un message
            'title' => 'Dupliquer Transaction - ' . $compte['nom']
        ]);
    }
    
    /**
     * Afficher le formulaire d'édition d'une transaction
     * 
     * Charge la transaction, le compte, les catégories et tiers pour le formulaire.
     * Vérifie que la transaction appartient à un compte de l'utilisateur.
     * 
     * @param int $compteId ID du compte
     * @param int $id ID de la transaction à modifier
     * @return void
     */
    public function edit(int $compteId, int $id): void
    {
        $this->requireAuth();
        
        // Vérifier que le compte existe et appartient à l'utilisateur
        $compte = Compte::findWithBanque($compteId);
        if (!$compte || $compte['user_id'] != $this->userId) {
            flash('error', 'Compte non trouvé');
            $this->redirect('comptes');
            return;
        }
        
        $transaction = Transaction::find($id);
        
        if (!$transaction || $transaction['compte_id'] != $compteId) {
            flash('error', 'Transaction introuvable');
            $this->redirect("comptes/{$compteId}/transactions");
            return;
        }
        
        // Récupérer les comptes actifs
        $comptes = Compte::getActifs();
        
        // Récupérer les catégories principales uniquement (les sous-catégories sont chargées via AJAX)
        $categories = Categorie::getCategoriesPrincipales($this->userId);
        
        // Récupérer les tiers
        $tiers = Tiers::getAllByUser($this->userId);
        
        // Récupérer les tags
        $tagModel = new \MonBudget\Models\Tag();
        $tags = $tagModel->getAllByUser($this->userId, 'name');
        
        // Récupérer les tags déjà assignés à cette transaction
        $transactionModel = new Transaction();
        $selectedTags = $transactionModel->getTagIds($id);
        
        $this->view('transactions.edit', [
            'compte' => $compte,
            'transaction' => $transaction,
            'comptes' => $comptes,
            'categories' => $categories,
            'tiers' => $tiers,
            'tags' => $tags,
            'selectedTags' => $selectedTags,
            'title' => 'Modifier la Transaction'
        ]);
    }
    
    /**
     * Mettre à jour une transaction existante
     * 
     * Valide les données, met à jour la transaction et recalcule les soldes
     * des comptes concernés. Gère les virements et la catégorisation.
     * 
     * @param int $compteId ID du compte
     * @param int $id ID de la transaction à mettre à jour
     * @return void
     */
    public function update(int $compteId, int $id): void
    {
        $this->requireAuth();
        
        // Vérifier que le compte existe et appartient à l'utilisateur
        $compte = Compte::find($compteId);
        if (!$compte || $compte['user_id'] != $this->userId) {
            $this->flashAndRedirect('error', 'Compte non trouvé', 'comptes');
            return;
        }
        
        if (!$this->validateCsrfOrFail("comptes/{$compteId}/transactions")) return;
        
        $transaction = Transaction::find($id);
        
        if (!$transaction || $transaction['compte_id'] != $compteId) {
            flash('error', 'Transaction introuvable');
            $this->redirect("comptes/{$compteId}/transactions");
            return;
        }
        
        // Validation
        $data = $this->validate([
            'compte_id' => 'required|numeric',
            'compte_destination_id' => 'numeric',
            'categorie_id' => 'numeric',
            'sous_categorie_id' => 'numeric',
            'tiers_id' => 'numeric',
            'date_transaction' => 'required',
            'montant' => 'required|numeric',
            'libelle' => 'required|max:255',
            'description' => '',
            'type_operation' => 'required',
            'moyen_paiement' => '',
            'beneficiaire' => 'max:255',
            'est_recurrente' => 'numeric',
            'frequence' => '',
            'intervalle' => 'numeric',
            'jour_execution' => 'numeric',
            'date_debut' => '',
            'date_fin' => '',
            'nb_executions_max' => 'numeric',
            'auto_validation' => 'numeric',
            'tolerance_weekend' => '',
            'recurrence_active' => 'numeric'
        ]);
        
        // Ajouter user_id
        $data['user_id'] = $_SESSION['user']['id'];
        
        // Traitement des champs nullable (NULL si vide)
        $data['categorie_id'] = !empty($data['categorie_id']) ? (int)$data['categorie_id'] : null;
        $data['sous_categorie_id'] = !empty($data['sous_categorie_id']) ? (int)$data['sous_categorie_id'] : null;
        $data['tiers_id'] = !empty($data['tiers_id']) ? (int)$data['tiers_id'] : null;
        $data['compte_destination_id'] = !empty($data['compte_destination_id']) ? (int)$data['compte_destination_id'] : null;
        
        // ✅ NOUVELLE ARCHITECTURE : Détecter si conversion vers récurrence
        $estRecurrente = isset($_POST['est_recurrente']) && $_POST['est_recurrente'];
        $data['est_recurrente'] = 0; // TOUJOURS 0 (nouvelle archi)
        
        // Normaliser les dates vides en NULL (éviter '0000-00-00')
        if (isset($data['date_fin']) && ($data['date_fin'] === '' || $data['date_fin'] === '0000-00-00')) {
            $data['date_fin'] = null;
        }
        if (isset($data['date_debut']) && ($data['date_debut'] === '' || $data['date_debut'] === '0000-00-00')) {
            $data['date_debut'] = null;
        }
        
        // ✅ Si conversion transaction normale → récurrence
        if ($estRecurrente && !$transaction['recurrence_id']) {
            // Créer le modèle de récurrence dans table recurrences
            $recurrenceData = [
                'user_id' => $this->userId,
                'compte_id' => $compteId,
                'libelle' => $data['libelle'],
                'montant' => $data['montant'],
                'type_operation' => $data['type_operation'],
                'frequence' => $_POST['frequence'] ?? 'mensuel',
                'intervalle' => (int)($_POST['intervalle'] ?? 1),
                'date_debut' => $_POST['date_debut'] ?? $data['date_transaction'],
                'prochaine_execution' => $_POST['date_debut'] ?? $data['date_transaction'],
                'recurrence_active' => 1
            ];
            
            // Champs optionnels
            if (!empty($data['description'])) $recurrenceData['description'] = $data['description'];
            if (!empty($data['compte_destination_id'])) $recurrenceData['compte_destination_id'] = $data['compte_destination_id'];
            if (!empty($data['categorie_id'])) $recurrenceData['categorie_id'] = $data['categorie_id'];
            if (!empty($data['sous_categorie_id'])) $recurrenceData['sous_categorie_id'] = $data['sous_categorie_id'];
            if (!empty($data['tiers_id'])) $recurrenceData['tiers_id'] = $data['tiers_id'];
            if (!empty($data['moyen_paiement'])) $recurrenceData['moyen_paiement'] = $data['moyen_paiement'];
            if (!empty($data['beneficiaire'])) $recurrenceData['beneficiaire'] = $data['beneficiaire'];
            if (!empty($_POST['date_fin']) && $_POST['date_fin'] !== '0000-00-00') {
                $recurrenceData['date_fin'] = $_POST['date_fin'];
            }
            if (!empty($_POST['nb_executions_max'])) {
                $recurrenceData['nb_executions_max'] = (int)$_POST['nb_executions_max'];
            }
            if (!empty($_POST['jour_execution'])) {
                $recurrenceData['jour_execution'] = (int)$_POST['jour_execution'];
            }
            $recurrenceData['auto_validation'] = isset($_POST['auto_validation']) ? 1 : 0;
            if (!empty($_POST['tolerance_weekend'])) {
                $recurrenceData['tolerance_weekend'] = $_POST['tolerance_weekend'];
            }
            
            $recurrenceId = Recurrence::create($recurrenceData);
            
            if ($recurrenceId) {
                // Lier la transaction existante à cette récurrence
                $data['recurrence_id'] = $recurrenceId;
                
                // Calculer prochaine exécution
                $recurrence = Recurrence::find($recurrenceId);
                $prochaineExec = Recurrence::calculerProchaineExecution($recurrence);
                Recurrence::update($recurrenceId, [
                    'prochaine_execution' => $prochaineExec,
                    'derniere_execution' => $data['date_transaction'],
                    'nb_executions' => 1
                ]);
                
                flash('success', 'Récurrence créée à partir de cette transaction');
            } else {
                flash('error', 'Erreur lors de la création de la récurrence');
                $this->redirect("comptes/{$compteId}/transactions/{$id}/edit");
                return;
            }
        }
        
        // Mettre à jour la transaction originale
        $result = Transaction::update($id, $data);
        
        if ($result >= 0) {
            // Gestion des tags
            if (isset($_POST['tags']) && is_array($_POST['tags'])) {
                $transactionModel = new Transaction();
                $transactionModel->syncTags($id, array_map('intval', $_POST['tags']));
            } else {
                // Si aucun tag sélectionné, supprimer tous les tags
                $transactionModel = new Transaction();
                $transactionModel->detachTags($id);
            }
            
            // Recalculer le solde du compte
            Compte::recalculerSolde($compteId);
            
            flash('success', 'Transaction mise à jour avec succès');
            $this->redirect("comptes/{$compteId}/transactions");
        } else {
            flash('error', 'Erreur lors de la mise à jour');
            $this->redirect("comptes/{$compteId}/transactions/{$id}/edit");
        }
    }
    
    /**
     * Supprimer une transaction
     * 
     * Supprime la transaction et recalcule le solde du compte.
     * Gère la suppression des virements (transaction liée).
     * 
     * @param int $compteId ID du compte
     * @param int $id ID de la transaction à supprimer
     * @return void
     */
    /**
     * Supprimer une transaction
     * 
     * Gère deux types de suppression :
     * 1. Transaction normale (est_recurrente = 0) : Suppression simple
     * 2. Modèle de récurrence (est_recurrente = 1) : Selon le paramètre 'mode' :
     *    - 'modele' : Supprime uniquement le modèle (défaut, pour résiliation)
     *    - 'tout' : Supprime modèle + toutes les occurrences générées
     * 
     * @param int $compteId ID du compte
     * @param int $id ID de la transaction/récurrence
     * @return void
     */
    public function delete(int $compteId, int $id): void
    {
        $this->requireAuth();
        
        // Vérifier que le compte existe et appartient à l'utilisateur
        $compte = Compte::find($compteId);
        if (!$compte || $compte['user_id'] != $this->userId) {
            $this->flashAndRedirect('error', 'Compte non trouvé', 'comptes');
            return;
        }
        
        if (!$this->validateCsrfOrFail("comptes/{$compteId}/transactions")) return;
        
        $transaction = Transaction::find($id);
        if (!$transaction || $transaction['compte_id'] != $compteId) {
            flash('error', 'Transaction introuvable');
            $this->redirect("comptes/{$compteId}/transactions");
            return;
        }
        
        // Vérifier si c'est un modèle de récurrence
        if ($transaction['est_recurrente'] == 1) {
            // Mode de suppression : 'modele' (défaut) ou 'tout'
            $mode = $_POST['mode_suppression'] ?? 'modele';
            
            if ($mode === 'tout') {
                // Supprimer le modèle + toutes les occurrences
                $result = Transaction::deleteRecurrenceWithOccurrences($id);
                
                if ($result['modele'] > 0) {
                    // Recalculer le solde si des occurrences ont été supprimées
                    if ($result['occurrences'] > 0) {
                        Compte::recalculerSolde($compteId);
                    }
                    
                    $message = "Récurrence supprimée avec succès";
                    if ($result['occurrences'] > 0) {
                        $message .= " ({$result['occurrences']} occurrence(s) supprimée(s))";
                    }
                    flash('success', $message);
                } else {
                    flash('error', 'Erreur lors de la suppression');
                }
            } else {
                // Supprimer uniquement le modèle
                $result = Transaction::delete($id);
                
                if ($result > 0) {
                    flash('success', 'Récurrence supprimée (les transactions déjà créées sont conservées)');
                } else {
                    flash('error', 'Erreur lors de la suppression');
                }
            }
            
            // Rediriger vers la liste des récurrences
            $this->redirect("comptes/{$compteId}/transactions/recurrentes");
        } else {
            // Transaction normale : suppression simple
            $result = Transaction::delete($id);
            
            if ($result > 0) {
                // Recalculer le solde du compte
                Compte::recalculerSolde($compteId);
                
                flash('success', 'Transaction supprimée avec succès');
            } else {
                flash('error', 'Erreur lors de la suppression');
            }
            
            $this->redirect("comptes/{$compteId}/transactions");
        }
    }
    
    /**
     * Afficher les transactions récurrentes
     * 
     * Liste toutes les transactions récurrentes configurées pour un compte donné.
     * 
     * @param int $compteId ID du compte
     * @return void
     */
    public function recurrentes(int $compteId): void
    {
        $this->requireAuth();
        
        // Vérifier que le compte existe et appartient à l'utilisateur
        $compte = Compte::findWithBanque($compteId);
        if (!$compte || $compte['user_id'] != $this->userId) {
            flash('error', 'Compte non trouvé');
            $this->redirect('comptes');
            return;
        }
        
        // Récupérer les transactions récurrentes actives du compte
        $recurrentes = Transaction::getRecurrentesActives($compteId);
        
        $this->view('transactions.recurrentes', [
            'compte' => $compte,
            'recurrentes' => $recurrentes,
            'title' => 'Transactions Récurrentes'
        ]);
    }
    
    /**
     * Exécuter manuellement une transaction récurrente
     * 
     * Crée une nouvelle transaction basée sur le modèle de récurrence.
     * Utile pour tester ou exécuter en avance une récurrence planifiée.
     * 
     * @param int $compteId ID du compte
     * @param int $id ID de la transaction récurrente à exécuter
     * @return void
     */
    public function executerRecurrence(int $compteId, int $id): void
    {
        $this->requireAuth();
        
        // Vérifier que le compte existe et appartient à l'utilisateur
        $compte = Compte::find($compteId);
        if (!$compte || $compte['user_id'] != $this->userId) {
            flash('error', 'Compte non trouvé');
            $this->redirect('comptes');
            return;
        }
        
        if (!$this->validateCsrfOrFail("comptes/{$compteId}/transactions/recurrentes")) return;
        
        $newId = Transaction::executerRecurrence($id);
        
        if ($newId) {
            // Recalculer le solde du compte
            Compte::recalculerSolde($compteId);
            
            flash('success', 'Récurrence exécutée avec succès. Transaction créée.');
            $this->redirect("comptes/{$compteId}/transactions");
        } else {
            flash('error', 'Erreur lors de l\'exécution de la récurrence');
            $this->redirect("comptes/{$compteId}/transactions/recurrentes");
        }
    }

    /**
     * Upload d'une pièce jointe pour une transaction
     * 
     * Endpoint AJAX pour uploader un fichier et l'attacher à une transaction.
     * Vérifie l'ownership de la transaction avant autorisation.
     * 
     * @param int $compteId ID du compte
     * @param int $transactionId ID de la transaction
     * @return void
     */
    public function uploadAttachment(int $compteId, int $transactionId): void
    {
        $this->requireAuth();
        header('Content-Type: application/json');
        
        // DEBUG
        error_log("=== UPLOAD ATTACHMENT DEBUG ===");
        error_log("CompteId: $compteId, TransactionId: $transactionId");
        error_log("UserId: " . $this->userId);
        error_log("FILES: " . print_r($_FILES, true));
        
        try {
            // Vérifier que la transaction existe et appartient à l'utilisateur
            $transaction = Transaction::find($transactionId);
            if (!$transaction || $transaction['compte_id'] != $compteId) {
                http_response_code(404);
                echo json_encode(['error' => 'Transaction non trouvée']);
                return;
            }

            // Vérifier que le compte appartient à l'utilisateur
            $compte = Compte::find($compteId);
            if (!$compte || $compte['user_id'] != $this->userId) {
                http_response_code(403);
                echo json_encode(['error' => 'Accès refusé']);
                return;
            }

            // Vérifier qu'un fichier a été uploadé
            if (!isset($_FILES['file']) || $_FILES['file']['error'] === UPLOAD_ERR_NO_FILE) {
                http_response_code(400);
                echo json_encode(['error' => 'Aucun fichier fourni']);
                return;
            }

            // Upload du fichier
            $uploadService = new FileUploadService();
            $fileData = $uploadService->uploadFile($_FILES['file'], $this->userId);

            // Créer l'entrée en BDD
            $attachmentData = array_merge($fileData, [
                'transaction_id' => $transactionId
            ]);

            $attachmentId = Attachment::create($attachmentData);

            if (!$attachmentId) {
                // Supprimer le fichier si échec BDD
                $uploadService->deleteFile($fileData);
                http_response_code(500);
                echo json_encode(['error' => 'Erreur lors de la sauvegarde']);
                return;
            }

            // Récupérer l'attachment complet
            $attachment = Attachment::find($attachmentId);

            echo json_encode([
                'success' => true,
                'attachment' => [
                    'id' => $attachment['id'],
                    'original_name' => $attachment['original_name'],
                    'size' => Attachment::formatFileSize($attachment['size']),
                    'icon' => Attachment::getIcon($attachment['mimetype']),
                    'is_image' => Attachment::isImage($attachment),
                    'uploaded_at' => $attachment['uploaded_at'],
                    'path' => $attachment['path']
                ]
            ]);

        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Supprimer une pièce jointe
     * 
     * Supprime le fichier physique et l'entrée en BDD.
     * Vérifie l'ownership de la transaction.
     * 
     * @param int $compteId ID du compte
     * @param int $transactionId ID de la transaction
     * @param int $attachmentId ID de la pièce jointe
     * @return void
     */
    public function deleteAttachment(int $compteId, int $transactionId, int $attachmentId): void
    {
        $this->requireAuth();
        header('Content-Type: application/json');
        
        try {
            // Vérifier que la transaction appartient à l'utilisateur
            $transaction = Transaction::find($transactionId);
            if (!$transaction || $transaction['compte_id'] != $compteId) {
                http_response_code(404);
                echo json_encode(['error' => 'Transaction non trouvée']);
                return;
            }

            $compte = Compte::find($compteId);
            if (!$compte || $compte['user_id'] != $this->userId) {
                http_response_code(403);
                echo json_encode(['error' => 'Accès refusé']);
                return;
            }

            // Vérifier que l'attachment existe et appartient à cette transaction
            $attachment = Attachment::find($attachmentId);
            if (!$attachment || $attachment['transaction_id'] != $transactionId) {
                http_response_code(404);
                echo json_encode(['error' => 'Pièce jointe non trouvée']);
                return;
            }

            // Supprimer le fichier physique
            $uploadService = new FileUploadService();
            $uploadService->deleteFile($attachment);

            // Supprimer de la BDD
            $deleted = Attachment::delete($attachmentId);

            if ($deleted) {
                echo json_encode(['success' => true]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Erreur lors de la suppression']);
            }

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Télécharger une pièce jointe
     * 
     * Sert le fichier avec les headers appropriés.
     * Vérifie l'ownership de la transaction.
     * 
     * @param int $compteId ID du compte
     * @param int $transactionId ID de la transaction
     * @param int $attachmentId ID de la pièce jointe
     * @return void
     */
    public function downloadAttachment(int $compteId, int $transactionId, int $attachmentId): void
    {
        $this->requireAuth();
        
        // Vérifier ownership
        $transaction = Transaction::find($transactionId);
        if (!$transaction || $transaction['compte_id'] != $compteId) {
            http_response_code(404);
            die('Transaction non trouvée');
        }

        $compte = Compte::find($compteId);
        if (!$compte || $compte['user_id'] != $this->userId) {
            http_response_code(403);
            die('Accès refusé');
        }

        // Récupérer l'attachment
        $attachment = Attachment::find($attachmentId);
        if (!$attachment || $attachment['transaction_id'] != $transactionId) {
            http_response_code(404);
            die('Pièce jointe non trouvée');
        }

        // Récupérer le fichier
        $filePath = Attachment::getFullPath($attachment);
        
        if (!file_exists($filePath)) {
            http_response_code(404);
            die('Fichier non trouvé');
        }

        // Headers pour le téléchargement
        header('Content-Type: ' . $attachment['mimetype']);
        header('Content-Length: ' . filesize($filePath));
        header('Content-Disposition: attachment; filename="' . $attachment['original_name'] . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');

        // Servir le fichier
        readfile($filePath);
        exit;
    }
}
