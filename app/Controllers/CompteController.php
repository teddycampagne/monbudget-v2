<?php

namespace MonBudget\Controllers;

use MonBudget\Models\Compte;
use MonBudget\Models\Banque;
use MonBudget\Models\CompteTitulaire;
use MonBudget\Models\Transaction;
use MonBudget\Services\AuditLogService;
use MonBudget\Services\RibGenerator;

/**
 * Contrôleur de gestion des comptes bancaires
 * 
 * Gère toutes les opérations CRUD sur les comptes bancaires :
 * création, modification, suppression, consultation des transactions.
 * Gère également l'association avec les titulaires et les banques.
 * 
 * @package MonBudget\Controllers
 * @author MonBudget
 * @version 1.0.0
 */
class CompteController extends BaseController
{
    /**
     * Afficher la liste des comptes de l'utilisateur
     * 
     * Récupère et affiche tous les comptes bancaires appartenant à l'utilisateur connecté.
     * 
     * @return void
     */
    public function index(): void
    {
        $this->requireAuth();
        
        $comptes = Compte::getByUser($this->userId);
        
        $this->view('comptes.index', [
            'comptes' => $comptes,
            'title' => 'Mes Comptes Bancaires'
        ]);
    }
    
    /**
     * Afficher les transactions d'un compte spécifique
     * 
     * Affiche toutes les transactions d'un compte avec calcul du solde réel.
     * Le solde est calculé comme : solde_initial + crédits - débits.
     * Vérifie que le compte appartient à l'utilisateur connecté.
     * 
     * @param int $compteId ID du compte à consulter
     * @return void
     */
    public function transactions(int $compteId): void
    {
        $this->requireAuth();
        
        // Vérifier que le compte existe et appartient à l'utilisateur avec les infos de la banque
        $compte = Compte::findWithBanque($compteId);
        if (!$compte || $compte['user_id'] != $this->userId) {
            flash('error', 'Compte non trouvé');
            $this->redirect('comptes');
            return;
        }
        
        // Récupérer les transactions du compte
        $transactions = Transaction::getByCompte($compteId);
        
        // Calculer le solde réel (initial + crédits - débits)
        // Approche sécurisée : calcul séparé avec abs() pour gérer tous les cas
        $totalCredits = 0;
        $totalDebits = 0;
        
        foreach ($transactions as $t) {
            if ($t['type_operation'] === 'credit') {
                $totalCredits += abs($t['montant']);
            } elseif ($t['type_operation'] === 'debit') {
                $totalDebits += abs($t['montant']);
            }
        }
        
        $compte['solde_calcule'] = $compte['solde_initial'] + $totalCredits - $totalDebits;
        $compte['total_credits'] = $totalCredits;
        $compte['total_debits'] = $totalDebits;
        
        $this->view('transactions.index', [
            'transactions' => $transactions,
            'compte' => $compte,
            'title' => 'Transactions - ' . $compte['nom']
        ]);
    }
    
    /**
     * Afficher le formulaire de création d'un compte
     * 
     * Charge la liste des banques et des titulaires pour les sélecteurs du formulaire.
     * 
     * @return void
     */
    public function create(): void
    {
        $this->requireAuth();
        
        // Récupérer toutes les banques pour le select
        $banques = Banque::all();
        
        // Récupérer tous les titulaires de l'utilisateur
        $titulaires = Titulaire::getAll();
        
        // Récupérer le banque_id depuis l'URL si présent (pour pré-sélection)
        $selectedBanqueId = isset($_GET['banque_id']) ? (int)$_GET['banque_id'] : null;
        
        $this->view('comptes.create', [
            'banques' => $banques,
            'titulaires' => $titulaires,
            'selectedBanqueId' => $selectedBanqueId,
            'title' => 'Nouveau Compte'
        ]);
    }
    
    /**
     * Créer un nouveau compte bancaire
     * 
     * Valide les données du formulaire, vérifie l'unicité du nom, crée le compte
     * et associe les titulaires (1 ou 2). Le solde actuel est initialisé au solde initial.
     * 
     * @return void
     */
    public function store(): void
    {
        $this->requireAuth();
        
        if (!$this->validateCsrfOrFail('comptes')) return;
        
        // Validation
        $data = $this->validate([
            'banque_id' => 'required|numeric',
            'nom' => 'required|max:100',
            'titulaire_1_id' => 'required|numeric',
            'titulaire_1_role' => 'max:20',
            'titulaire_2_id' => 'numeric',
            'titulaire_2_role' => 'max:20',
            'type_compte' => 'max:50',
            'code_guichet' => 'max:10',
            'numero_compte' => 'max:50',
            'cle_rib' => 'max:2',
            'iban' => 'max:34',
            'solde_initial' => 'numeric',
            'devise' => 'max:3',
            'description' => ''
        ]);
        
        // Vérifier si le nom existe déjà
        if (Compte::nomExists($data['nom'])) {
            flash('error', 'Un compte avec ce nom existe déjà');
            $this->redirect('comptes/create');
            return;
        }
        
        // Ajouter user_id depuis la session
        $data['user_id'] = $_SESSION['user']['id'];
        
        // Définir les valeurs par défaut
        if (empty($data['devise'])) {
            $data['devise'] = 'EUR';
        }
        
        if (!isset($data['solde_initial'])) {
            $data['solde_initial'] = 0.00;
        }
        
        // Le solde actuel = solde initial à la création
        $data['solde_actuel'] = $data['solde_initial'];
        
        // Par défaut, compte actif
        $data['actif'] = 1;
        
        // Initialiser service audit PCI DSS
        $audit = new AuditLogService();
        
        // Créer le compte
        $id = Compte::create($data);
        
        if ($id) {
            // Logger création (PCI DSS audit)
            $audit->logCreate('comptes', $id, $data);
            
            // Associer les titulaires
            $titulaires = [];
            
            // Premier titulaire (obligatoire)
            $titulaires[] = [
                'id' => $data['titulaire_1_id'],
                'role' => $data['titulaire_1_role'] ?? 'titulaire',
                'ordre' => 1
            ];
            
            // Second titulaire (optionnel)
            if (!empty($data['titulaire_2_id'])) {
                $titulaires[] = [
                    'id' => $data['titulaire_2_id'],
                    'role' => $data['titulaire_2_role'] ?? 'co-titulaire',
                    'ordre' => 2
                ];
            }
            
            // Synchroniser les titulaires
            CompteTitulaire::sync($id, $titulaires);
            
            flash('success', 'Compte créé avec succès');
            $this->redirect('comptes');
        } else {
            flash('error', 'Erreur lors de la création du compte');
            $this->redirect('comptes/create');
        }
    }
    
    /**
     * Afficher le formulaire d'édition d'un compte
     * 
     * Charge les informations du compte avec sa banque et ses titulaires,
     * ainsi que les listes des banques et titulaires disponibles.
     * 
     * @param int $id ID du compte à modifier
     * @return void
     */
    public function edit(int $id): void
    {
        $this->requireAuth();
        
        $compte = Compte::findWithBanqueAndTitulaires($id);
        
        if (!$compte) {
            flash('error', 'Compte introuvable');
            $this->redirect('comptes');
            return;
        }
        
        // Récupérer toutes les banques pour le select
        $banques = Banque::all();
        
        // Récupérer tous les titulaires de l'utilisateur
        $titulaires = Titulaire::getAll();
        
        $this->view('comptes.edit', [
            'compte' => $compte,
            'banques' => $banques,
            'titulaires' => $titulaires,
            'title' => 'Modifier le Compte'
        ]);
    }
    
    /**
     * Mettre à jour un compte existant
     * 
     * Valide les données du formulaire, vérifie l'unicité du nom (sauf pour le compte actuel),
     * met à jour les informations du compte et synchronise les titulaires associés.
     * 
     * @param int $id ID du compte à mettre à jour
     * @return void
     */
    public function update(int $id): void
    {
        $this->requireAuth();
        
        if (!$this->validateCsrfOrFail('comptes')) return;
        
        $compte = Compte::find($id);
        
        if (!$compte) {
            flash('error', 'Compte introuvable');
            $this->redirect('comptes');
            return;
        }
        
        // Validation
        $data = $this->validate([
            'banque_id' => 'required|numeric',
            'nom' => 'required|max:100',
            'titulaire_1_id' => 'required|numeric',
            'titulaire_1_role' => 'max:20',
            'titulaire_2_id' => 'numeric',
            'titulaire_2_role' => 'max:20',
            'type_compte' => 'max:50',
            'code_guichet' => 'max:10',
            'numero_compte' => 'max:50',
            'cle_rib' => 'max:2',
            'iban' => 'max:34',
            'solde_initial' => 'numeric',
            'solde_actuel' => 'numeric',
            'devise' => 'max:3',
            'actif' => 'numeric',
            'description' => ''
        ]);
        
        // Vérifier si le nom existe déjà (sauf pour ce compte)
        if (Compte::nomExists($data['nom'], $id)) {
            flash('error', 'Un compte avec ce nom existe déjà');
            $this->redirect("comptes/{$id}/edit");
            return;
        }
        
        // Ajouter user_id
        $data['user_id'] = $_SESSION['user']['id'];
        
        // Convertir actif en boolean
        $data['actif'] = isset($data['actif']) ? 1 : 0;
        
        // Initialiser service audit PCI DSS
        $audit = new AuditLogService();
        
        // Sauvegarder anciennes valeurs pour audit
        $oldValues = $compte;
        
        // Mettre à jour le compte
        $result = Compte::update($id, $data);
        
        if ($result >= 0) {
            // Logger modification (PCI DSS audit)
            $audit->logUpdate('comptes', $id, $oldValues, $data);
            
            // Synchroniser les titulaires
            $titulaires = [];
            
            // Premier titulaire (obligatoire)
            $titulaires[] = [
                'id' => $data['titulaire_1_id'],
                'role' => $data['titulaire_1_role'] ?? 'titulaire',
                'ordre' => 1
            ];
            
            // Second titulaire (optionnel)
            if (!empty($data['titulaire_2_id'])) {
                $titulaires[] = [
                    'id' => $data['titulaire_2_id'],
                    'role' => $data['titulaire_2_role'] ?? 'co-titulaire',
                    'ordre' => 2
                ];
            }
            
            // Synchroniser les titulaires
            CompteTitulaire::sync($id, $titulaires);
            
            flash('success', 'Compte mis à jour avec succès');
            $this->redirect('comptes');
        } else {
            flash('error', 'Erreur lors de la mise à jour');
            $this->redirect("comptes/{$id}/edit");
        }
    }
    
    /**
     * Supprimer un compte bancaire
     * 
     * Supprime un compte et toutes ses associations (titulaires).
     * Vérifie le token CSRF avant suppression.
     * 
     * @param int $id ID du compte à supprimer
     * @return void
     */
    public function destroy(int $id): void
    {
        $this->requireAuth();
        
        if (!$this->validateCsrfOrFail('comptes')) return;
        
        // Récupérer les données avant suppression
        $compte = Compte::find($id);
        
        // Initialiser service audit PCI DSS
        $audit = new AuditLogService();
        
        $result = Compte::delete($id);
        
        if ($result > 0) {
            // Logger suppression (PCI DSS audit)
            if ($compte) {
                $audit->logDelete('comptes', $id, $compte);
            }
            
            flash('success', 'Compte supprimé avec succès');
        } else {
            flash('error', 'Impossible de supprimer ce compte');
        }
        
        $this->redirect('comptes');
    }
    
    /**
     * Rechercher des comptes (AJAX)
     * 
     * Recherche des comptes par nom ou autres critères.
     * Utilisé pour l'autocomplétion dans les formulaires.
     * 
     * @return void
     */
    public function search(): void
    {
        $this->requireAuth();
        
        $query = $_GET['q'] ?? '';
        
        if (empty($query)) {
            $this->jsonResponse([]);
            return;
        }
        
        $comptes = Compte::search($query);
        $this->jsonResponse($comptes);
    }
    
    /**
     * Télécharger le RIB d'un compte au format PDF
     * 
     * Génère et télécharge un RIB au format PDF pour le compte spécifié.
     * Vérifie que le compte appartient à l'utilisateur et que les informations
     * RIB (code banque, code guichet, numéro de compte, clé, IBAN) sont complètes.
     * 
     * @param int $id ID du compte dont télécharger le RIB
     * @return void
     */
    public function downloadRib(int $id): void
    {
        $this->requireAuth();
        
        // Récupérer le compte avec les infos de la banque
        $compte = Compte::findWithBanque($id);
        
        if (!$compte) {
            flash('error', 'Compte introuvable');
            $this->redirect('comptes');
            return;
        }
        
        // Vérifier que le compte appartient à l'utilisateur connecté
        if ($compte['user_id'] != $_SESSION['user']['id']) {
            flash('error', 'Accès non autorisé');
            $this->redirect('comptes');
            return;
        }
        
        // Vérifier que les informations RIB sont complètes
        if (empty($compte['code_banque']) || empty($compte['code_guichet']) || 
            empty($compte['numero_compte']) || empty($compte['cle_rib']) || empty($compte['iban'])) {
            flash('error', 'Les informations RIB sont incomplètes pour ce compte');
            $this->redirect('comptes');
            return;
        }
        
        try {
            // Générer le PDF
            $generator = new RibGenerator();
            $pdfContent = $generator->generate($compte);
            
            // Préparer le nom du fichier
            $banqueNom = preg_replace('/[^a-zA-Z0-9_-]/', '_', $compte['banque_nom'] ?? 'Banque');
            $compteNom = preg_replace('/[^a-zA-Z0-9_-]/', '_', $compte['nom'] ?? 'Compte');
            $filename = 'RIB_' . $banqueNom . '_' . $compteNom . '.pdf';
            
            // Envoyer les headers pour le téléchargement
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($pdfContent));
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
            
            // Envoyer le contenu
            echo $pdfContent;
            exit;
            
        } catch (\Exception $e) {
            error_log('Erreur génération RIB: ' . $e->getMessage());
            flash('error', 'Erreur lors de la génération du RIB');
            $this->redirect('comptes');
        }
    }
}
