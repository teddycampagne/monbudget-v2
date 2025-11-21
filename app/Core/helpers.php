<?php

/**
 * Fonctions helper globales
 */

if (!function_exists('env')) {
    /**
     * Obtenir une variable d'environnement
     */
    function env(string $key, mixed $default = null): mixed
    {
        return $_ENV[$key] ?? $_SERVER[$key] ?? $default;
    }
}

if (!function_exists('config')) {
    /**
     * Obtenir une valeur de configuration
     */
    function config(string $key, mixed $default = null): mixed
    {
        static $config = [];
        
        if (empty($config)) {
            $configPath = dirname(__DIR__, 2) . '/config/app.php';
            if (file_exists($configPath)) {
                $config = require $configPath;
            }
        }
        
        $keys = explode('.', $key);
        $value = $config;
        
        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }
        
        return $value;
    }
}

if (!function_exists('base_path')) {
    /**
     * Obtenir le chemin de base de l'application
     */
    function base_path(string $path = ''): string
    {
        $basePath = dirname(__DIR__, 2);
        return $path ? $basePath . '/' . ltrim($path, '/') : $basePath;
    }
}

if (!function_exists('storage_path')) {
    /**
     * Obtenir le chemin du dossier storage
     */
    function storage_path(string $path = ''): string
    {
        return base_path('storage/' . ltrim($path, '/'));
    }
}

if (!function_exists('public_path')) {
    /**
     * Obtenir le chemin du dossier uploads (anciennement public)
     */
    function public_path(string $path = ''): string
    {
        return base_path('uploads/' . ltrim($path, '/'));
    }
}

if (!function_exists('view')) {
    /**
     * Charger une vue
     */
    function view(string $name, array $data = []): void
    {
        extract($data);
        
        $viewPath = base_path('app/Views/' . str_replace('.', '/', $name) . '.php');
        
        if (!file_exists($viewPath)) {
            throw new Exception("View not found: $name");
        }
        
        require $viewPath;
    }
}

if (!function_exists('redirect')) {
    /**
     * Rediriger vers une URL
     */
    function redirect(string $url): void
    {
        // Si l'URL ne commence pas par http:// ou https://, c'est une URL relative
        if (!preg_match('/^https?:\/\//', $url)) {
            // Retirer le / initial s'il existe
            $url = ltrim($url, '/');
            // Ajouter BASE_URL
            if (defined('BASE_URL') && BASE_URL !== '') {
                $url = BASE_URL . '/' . $url;
            } else {
                $url = '/' . $url;
            }
        }
        
        header("Location: $url");
        exit;
    }
}

if (!function_exists('session')) {
    /**
     * Obtenir/définir une valeur de session
     */
    function session(?string $key = null, mixed $value = null): mixed
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if ($key === null) {
            return $_SESSION;
        }
        
        if ($value !== null) {
            $_SESSION[$key] = $value;
            return $value;
        }
        
        return $_SESSION[$key] ?? null;
    }
}

if (!function_exists('csrf_token')) {
    /**
     * Générer un token CSRF
     */
    function csrf_token(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Générer un champ CSRF pour les formulaires
     */
    function csrf_field(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
    }
}

if (!function_exists('csrf_check')) {
    /**
     * Vérifier un token CSRF
     */
    function csrf_check(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $token = $_POST['csrf_token'] ?? '';
        $sessionToken = $_SESSION['csrf_token'] ?? '';
        
        return !empty($token) && hash_equals($sessionToken, $token);
    }
}

if (!function_exists('old')) {
    /**
     * Obtenir une ancienne valeur de formulaire
     */
    function old(string $key, mixed $default = ''): mixed
    {
        return $_SESSION['old'][$key] ?? $default;
    }
}

if (!function_exists('flash')) {
    /**
     * Définir un message flash
     */
    function flash(string $key, mixed $value = null): mixed
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if ($value !== null) {
            $_SESSION['flash'][$key] = $value;
            return $value;
        }
        
        $flash = $_SESSION['flash'][$key] ?? null;
        unset($_SESSION['flash'][$key]);
        return $flash;
    }
}

if (!function_exists('asset')) {
    /**
     * Générer une URL pour un asset
     */
    function asset(string $path): string
    {
        return '/assets/' . ltrim($path, '/');
    }
}

if (!function_exists('sanitize')) {
    /**
     * Sanitize une chaîne de caractères
     */
    function sanitize(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('validate_email')) {
    /**
     * Valider une adresse email
     */
    function validate_email(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

if (!function_exists('old')) {
    /**
     * Récupérer les anciennes valeurs de formulaire
     */
    function old(string $key, mixed $default = ''): mixed
    {
        return $_SESSION['old'][$key] ?? $default;
    }
}

if (!function_exists('url')) {
    /**
     * Générer une URL complète avec le base path
     * 
     * @param string $path Chemin relatif (ex: 'documentation/guide')
     * @return string URL complète (ex: '/monbudgetV2/documentation/guide')
     */
    function url(string $path = ''): string
    {
        // Retirer le / initial s'il existe
        $path = ltrim($path, '/');
        
        // Ajouter BASE_URL si défini
        if (defined('BASE_URL') && BASE_URL !== '') {
            return BASE_URL . '/' . $path;
        }
        
        return '/' . $path;
    }
}

if (!function_exists('asset')) {
    /**
     * Générer une URL pour un asset (CSS, JS, images)
     * 
     * @param string $path Chemin relatif depuis assets/ (ex: 'css/style.css')
     * @return string URL complète
     */
    function asset(string $path): string
    {
        return url('assets/' . ltrim($path, '/'));
    }
}
