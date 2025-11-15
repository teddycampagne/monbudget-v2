<?php

namespace MonBudget\Controllers;

use MonBudget\Models\User;

/**
 * Contrôleur de gestion du profil utilisateur
 * 
 * Gère toutes les opérations liées au profil de l'utilisateur :
 * consultation, modification des informations personnelles,
 * changement de mot de passe, gestion des préférences.
 * 
 * @package MonBudget\Controllers
 * @author MonBudget
 * @version 1.0.0
 */
class UserController extends BaseController
{
    /**
     * Afficher le profil de l'utilisateur connecté
     * 
     * Affiche les informations du profil : username, email, rôle, etc.
     * 
     * @return void
     */
    public function profile(): void
    {
        $this->requireAuth();
        
        $user = User::find($this->userId);
        
        if (!$user) {
            flash('error', 'Utilisateur non trouvé');
            $this->redirect('/');
            return;
        }
        
        $this->view('user.profile', [
            'user' => $user,
            'title' => 'Mon Profil'
        ]);
    }
    
    /**
     * Mettre à jour le profil
     */
    public function updateProfile(): void
    {
        $this->requireAuth();
        
        if (!$this->validateCsrfOrFail('profile')) return;
        
        $data = $this->validate([
            'email' => 'required|email|max:255'
        ]);
        
        // Vérifier si l'email existe déjà (pour un autre utilisateur)
        $existingUser = User::findByEmail($data['email']);
        if ($existingUser && $existingUser['id'] != $this->userId) {
            flash('error', 'Cet email est déjà utilisé par un autre compte');
            $this->redirect('profile');
            return;
        }
        
        $result = User::update($this->userId, $data);
        
        if ($result >= 0) {
            // Mettre à jour la session
            $_SESSION['user']['email'] = $data['email'];
            
            flash('success', 'Profil mis à jour avec succès');
        } else {
            flash('error', 'Erreur lors de la mise à jour du profil');
        }
        
        $this->redirect('profile');
    }
    
    /**
     * Afficher la page des paramètres
     */
    public function settings(): void
    {
        $this->requireAuth();
        
        $user = User::find($this->userId);
        
        if (!$user) {
            flash('error', 'Utilisateur non trouvé');
            $this->redirect('/');
            return;
        }
        
        $this->view('user.settings', [
            'user' => $user,
            'title' => 'Paramètres'
        ]);
    }
    
    /**
     * Mettre à jour le mot de passe
     */
    public function updatePassword(): void
    {
        $this->requireAuth();
        
        if (!$this->validateCsrfOrFail('profile')) return;
        
        $data = $this->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8',
            'confirm_password' => 'required'
        ]);
        
        // Vérifier que les mots de passe correspondent
        if ($data['new_password'] !== $data['confirm_password']) {
            flash('error', 'Les nouveaux mots de passe ne correspondent pas');
            $this->redirect('profile');
            return;
        }
        
        // Récupérer l'utilisateur
        $user = User::find($this->userId);
        
        // Vérifier le mot de passe actuel
        if (!password_verify($data['current_password'], $user['password'])) {
            flash('error', 'Le mot de passe actuel est incorrect');
            $this->redirect('profile');
            return;
        }
        
        // Mettre à jour le mot de passe
        $result = User::update($this->userId, [
            'password' => password_hash($data['new_password'], PASSWORD_DEFAULT)
        ]);
        
        if ($result >= 0) {
            flash('success', 'Mot de passe modifié avec succès');
        } else {
            flash('error', 'Erreur lors de la modification du mot de passe');
        }
        
        $this->redirect('profile');
    }
    
    /**
     * Mettre à jour les préférences
     */
    public function updatePreferences(): void
    {
        $this->requireAuth();
        
        if (!$this->validateCsrfOrFail('profile')) return;
        
        // Pour l'instant, on stocke les préférences en session
        // Plus tard, on pourra créer une table user_preferences
        $_SESSION['preferences'] = [
            'langue' => $_POST['langue'] ?? 'fr',
            'devise' => $_POST['devise'] ?? 'EUR',
            'format_date' => $_POST['format_date'] ?? 'd/m/Y',
            'theme' => $_POST['theme'] ?? 'light',
            'notif_transactions' => isset($_POST['notif_transactions']),
            'notif_budgets' => isset($_POST['notif_budgets']),
            'notif_recurrences' => isset($_POST['notif_recurrences'])
        ];
        
        flash('success', 'Préférences enregistrées avec succès');
        $this->redirect('profile');
    }
}

