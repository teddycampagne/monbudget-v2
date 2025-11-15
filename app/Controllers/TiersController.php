<?php

namespace MonBudget\Controllers;

use MonBudget\Models\Tiers;

/**
 * Contrôleur de gestion des tiers/bénéficiaires
 * 
 * Gère toutes les opérations CRUD sur les tiers (fournisseurs, clients, etc.).
 * Permet l'organisation par type pour faciliter la catégorisation des transactions.
 * 
 * @package MonBudget\Controllers
 * @author MonBudget
 * @version 1.0.0
 */
class TiersController extends BaseController
{
    /**
     * Constructeur
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Lister les tiers de l'utilisateur
     * 
     * Affiche la liste des tiers, optionnellement filtrée par type.
     * Si aucun type n'est spécifié, affiche les tiers regroupés par type.
     * 
     * @return void
     */
    public function index(): void
    {
        $this->requireAuth();
        
        $type = $_GET['type'] ?? null;
        
        if ($type) {
            $tiers = Tiers::getAllByUser($this->userId, $type);
            $grouped = null;
        } else {
            $tiers = null;
            $grouped = Tiers::getGroupedByType($this->userId);
        }
        
        $this->view('tiers/index', [
            'tiers' => $tiers,
            'grouped' => $grouped,
            'type_filtre' => $type
        ]);
    }

    /**
     * Affiche le formulaire de création
     */
    public function create(): void
    {
        $this->requireAuth();
        
        $type = $_GET['type'] ?? 'debiteur';
        $groupes = Tiers::getGroupes($this->userId);
        
        $this->view('tiers/create', [
            'type' => $type,
            'groupes' => $groupes
        ]);
    }

    /**
     * Enregistre un nouveau tiers
     */
    public function store(): void
    {
        $this->requireAuth();
        if (!$this->validateCsrfOrFail('tiers')) return;

        $data = [
            'user_id' => $this->userId,
            'nom' => trim($_POST['nom'] ?? ''),
            'groupe' => !empty($_POST['groupe']) ? trim($_POST['groupe']) : null,
            'type' => $_POST['type'] ?? 'debiteur',
            'notes' => trim($_POST['notes'] ?? '')
        ];

        // Validation
        $errors = [];
        
        if (empty($data['nom'])) {
            $errors[] = "Le nom est obligatoire";
        }
        
        if (!in_array($data['type'], ['crediteur', 'debiteur', 'mixte'])) {
            $errors[] = "Le type doit être 'crediteur', 'debiteur' ou 'mixte'";
        }

        if (!empty($errors)) {
            $_SESSION['error'] = implode('<br>', $errors);
            header('Location: ' . url('tiers/create'));
            exit;
        }

        $id = Tiers::create($data);

        if ($id) {
            $_SESSION['success'] = "Tiers créé avec succès";
        } else {
            $_SESSION['error'] = "Erreur lors de la création du tiers";
        }

        header('Location: ' . url('tiers'));
        exit;
    }

    /**
     * Affiche le formulaire d'édition
     */
    public function edit(int $id): void
    {
        $this->requireAuth();
        
        $tiers = Tiers::find($id);

        if (!$tiers || $tiers['user_id'] != $this->userId) {
            $_SESSION['error'] = "Tiers non trouvé";
            header('Location: ' . url('tiers'));
            exit;
        }

        $groupes = Tiers::getGroupes($this->userId);

        $this->view('tiers/edit', [
            'tiers' => $tiers,
            'groupes' => $groupes
        ]);
    }

    /**
     * Met à jour un tiers
     */
    public function update(int $id): void
    {
        $this->requireAuth();
        if (!$this->validateCsrfOrFail('tiers')) return;

        $tiers = Tiers::find($id);

        if (!$tiers || $tiers['user_id'] != $this->userId) {
            $_SESSION['error'] = "Tiers non trouvé";
            header('Location: ' . url('tiers'));
            exit;
        }

        $data = [
            'nom' => trim($_POST['nom'] ?? ''),
            'groupe' => !empty($_POST['groupe']) ? trim($_POST['groupe']) : null,
            'type' => $_POST['type'] ?? 'debiteur',
            'notes' => trim($_POST['notes'] ?? '')
        ];

        // Validation
        if (empty($data['nom'])) {
            $_SESSION['error'] = "Le nom est obligatoire";
            header('Location: ' . url("tiers/{$id}/edit"));
            exit;
        }

        if (Tiers::update($id, $data)) {
            $_SESSION['success'] = "Tiers modifié avec succès";
        } else {
            $_SESSION['error'] = "Erreur lors de la modification";
        }

        header('Location: ' . url('tiers'));
        exit;
    }

    /**
     * Supprime un tiers
     */
    public function destroy(int $id): void
    {
        $this->requireAuth();
        if (!$this->validateCsrfOrFail('tiers')) return;

        $tiers = Tiers::find($id);

        if (!$tiers || $tiers['user_id'] != $this->userId) {
            $_SESSION['error'] = "Tiers non trouvé";
            header('Location: ' . url('tiers'));
            exit;
        }

        if (Tiers::deleteTiers($id)) {
            $_SESSION['success'] = "Tiers supprimé avec succès";
        } else {
            $_SESSION['error'] = "Impossible de supprimer ce tiers (transactions liées)";
        }

        header('Location: ' . url('tiers'));
        exit;
    }
}
