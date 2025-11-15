<?php

namespace MonBudget\Controllers;

/**
 * Contrôleur de base de l'application
 * 
 * Classe parente de tous les contrôleurs, fournissant des méthodes utilitaires
 * pour la gestion des vues, des réponses JSON, de la validation, de l'authentification
 * et des autorisations.
 * 
 * @package MonBudget\Controllers
 * @author MonBudget
 * @version 1.0.0
 */
class BaseController
{
    /**
     * Données à passer aux vues
     * 
     * @var array
     */
    protected array $data = [];
    
    /**
     * ID de l'utilisateur connecté
     * 
     * @var int|null
     */
    protected ?int $userId = null;
    
    /**
     * Constructeur - Initialise la session et l'utilisateur connecté
     * 
     * Démarre la session si elle n'est pas déjà active et récupère l'ID
     * de l'utilisateur connecté depuis la session.
     */
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Initialiser l'ID utilisateur depuis la session
        $this->userId = $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Charger et afficher une vue
     * 
     * Charge un fichier de vue et lui passe les données spécifiées.
     * Le nom de la vue utilise la notation par points (ex: 'user.profile' -> 'app/Views/user/profile.php')
     * 
     * @param string $name Nom de la vue (notation par points)
     * @param array $data Données à passer à la vue
     * @return void
     * @throws \Exception Si la vue n'existe pas
     */
    protected function view(string $name, array $data = []): void
    {
        $this->data = array_merge($this->data, $data);
        extract($this->data);
        
        $viewPath = base_path('app/Views/' . str_replace('.', '/', $name) . '.php');
        
        if (!file_exists($viewPath)) {
            throw new \Exception("View not found: $name");
        }
        
        require $viewPath;
    }
    
    /**
     * Retourner une réponse JSON
     * 
     * Envoie une réponse HTTP au format JSON avec le code de statut spécifié.
     * Termine l'exécution du script après l'envoi.
     * 
     * @param array $data Données à encoder en JSON
     * @param int $status Code de statut HTTP (200, 400, 404, etc.)
     * @return void
     */
    protected function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    /**
     * Rediriger vers une URL
     * 
     * Effectue une redirection HTTP vers l'URL spécifiée.
     * Utilise le helper global qui gère automatiquement BASE_URL.
     * 
     * @param string $url URL de destination (relative ou absolue)
     * @return void
     */
    protected function redirect(string $url): void
    {
        // Utiliser le helper global qui gère BASE_URL
        redirect($url);
    }
    
    /**
     * Valider les données POST selon des règles
     * 
     * Valide les données POST selon les règles spécifiées.
     * Règles supportées : required, email, min:X, max:X
     * En cas d'erreur, stocke les erreurs en session et redirige vers la page précédente.
     * 
     * @param array $rules Tableau associatif [champ => règles] (règles séparées par |)
     * @return array Données validées
     * 
     * @example
     * $data = $this->validate([
     *     'email' => 'required|email',
     *     'password' => 'required|min:6|max:100'
     * ]);
     */
    protected function validate(array $rules): array
    {
        $errors = [];
        $data = [];
        
        foreach ($rules as $field => $fieldRules) {
            $value = $_POST[$field] ?? null;
            $fieldRules = explode('|', $fieldRules);
            
            foreach ($fieldRules as $rule) {
                if ($rule === 'required' && empty($value)) {
                    $errors[$field][] = "Le champ $field est requis";
                    continue;
                }
                
                if ($rule === 'email' && !empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field][] = "Le champ $field doit être un email valide";
                    continue;
                }
                
                if (str_starts_with($rule, 'min:')) {
                    $min = (int) substr($rule, 4);
                    if (!empty($value) && strlen($value) < $min) {
                        $errors[$field][] = "Le champ $field doit contenir au moins $min caractères";
                        continue;
                    }
                }
                
                if (str_starts_with($rule, 'max:')) {
                    $max = (int) substr($rule, 4);
                    if (!empty($value) && strlen($value) > $max) {
                        $errors[$field][] = "Le champ $field ne doit pas dépasser $max caractères";
                        continue;
                    }
                }
            }
            
            if (empty($errors[$field])) {
                $data[$field] = $value;
            }
        }
        
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $_POST;
            $this->redirect($_SERVER['HTTP_REFERER'] ?? '/');
        }
        
        return $data;
    }
    
    /**
     * Vérifier la validité du token CSRF
     * 
     * Compare le token CSRF envoyé en POST avec celui stocké en session.
     * Utilise hash_equals() pour éviter les attaques par timing.
     * 
     * @return bool true si le token est valide, false sinon
     */
    protected function verifyCsrf(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $token = $_POST['csrf_token'] ?? '';
        $sessionToken = $_SESSION['csrf_token'] ?? '';
        
        return hash_equals($sessionToken, $token);
    }
    
    /**
     * Vérifier si l'utilisateur est authentifié
     * 
     * Vérifie la présence de l'ID utilisateur en session.
     * 
     * @return bool true si l'utilisateur est connecté, false sinon
     */
    protected function isAuthenticated(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return isset($_SESSION['user_id']);
    }
    
    /**
     * Obtenir les données de l'utilisateur connecté
     * 
     * Récupère les informations de l'utilisateur stockées en session.
     * 
     * @return array|null Tableau des données utilisateur ou null si non connecté
     */
    protected function getUser(): ?array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            return null;
        }
        
        return $_SESSION['user'] ?? null;
    }
    
    /**
     * Exiger l'authentification
     * 
     * Redirige vers la page de login si l'utilisateur n'est pas authentifié.
     * À utiliser au début des méthodes nécessitant une connexion.
     * 
     * @return void
     */
    protected function requireAuth(): void
    {
        if (!$this->isAuthenticated()) {
            $this->redirect('login');
            exit;
        }
    }
    
    /**
     * Vérifier si l'utilisateur a un rôle administrateur
     * 
     * Vérifie si le rôle de l'utilisateur est 'admin' ou 'super_admin'.
     * 
     * @return bool true si l'utilisateur est admin ou super_admin, false sinon
     */
    protected function isAdmin(): bool
    {
        return isset($_SESSION['user']['role']) && 
               in_array($_SESSION['user']['role'], ['admin', 'super_admin']);
    }
    
    /**
     * Vérifier si l'utilisateur est super-administrateur
     * 
     * Vérifie si le rôle de l'utilisateur est exactement 'super_admin'.
     * Le super-admin a tous les privilèges d'administration.
     * 
     * @return bool true si l'utilisateur est super_admin, false sinon
     */
    protected function isSuperAdmin(): bool
    {
        return isset($_SESSION['user']['role']) && 
               $_SESSION['user']['role'] === 'super_admin';
    }
    
    /**
     * Exiger l'accès administrateur (admin ou super-admin)
     * 
     * Vérifie que l'utilisateur est connecté ET a un rôle administrateur.
     * Redirige vers la page admin avec message d'erreur si non autorisé.
     * 
     * @return bool true si accès autorisé, false sinon (avec redirection)
     */
    protected function requireAdminAccess(): bool
    {
        $this->requireAuth();
        if (!$this->isAdmin() && !$this->isSuperAdmin()) {
            flash('error', 'Accès refusé.');
            $this->redirect('admin');
            return false;
        }
        return true;
    }
    
    /**
     * Exiger l'accès super-administrateur uniquement
     * 
     * Vérifie que l'utilisateur est connecté ET est super-admin.
     * Redirige vers la page admin avec message d'erreur si non autorisé.
     * Réservé aux fonctions critiques (gestion utilisateurs, configuration système, etc.)
     * 
     * @return bool true si accès autorisé, false sinon (avec redirection)
     */
    protected function requireSuperAdminAccess(): bool
    {
        $this->requireAuth();
        if (!$this->isSuperAdmin()) {
            flash('error', 'Accès refusé. Cette fonction est réservée au super-administrateur.');
            $this->redirect('admin');
            return false;
        }
        return true;
    }
    
    /**
     * Valider le token CSRF ou rediriger en cas d'échec
     * 
     * Vérifie le token CSRF et redirige avec message d'erreur si invalide.
     * Simplifie la validation CSRF dans les contrôleurs.
     * 
     * @param string $redirectUrl URL de redirection en cas d'échec (défaut: 'admin')
     * @return bool true si token valide, false sinon (avec redirection)
     */
    protected function validateCsrfOrFail(string $redirectUrl = 'admin'): bool
    {
        if (!$this->verifyCsrf()) {
            flash('error', 'Token CSRF invalide');
            $this->redirect($redirectUrl);
            return false;
        }
        return true;
    }
    
    /**
     * Afficher un message flash et rediriger
     * 
     * Combine l'affichage d'un message flash et une redirection en une seule méthode.
     * Simplifie le code des contrôleurs pour les opérations courantes.
     * 
     * @param string $type Type de message (success, error, warning, info)
     * @param string $message Message à afficher à l'utilisateur
     * @param string $url URL de redirection
     * @return void
     * 
     * @example
     * $this->flashAndRedirect('success', 'Compte créé avec succès', 'comptes');
     */
    protected function flashAndRedirect(string $type, string $message, string $url): void
    {
        flash($type, $message);
        $this->redirect($url);
    }
}
