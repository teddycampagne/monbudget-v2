<?php

namespace MonBudget\Core;

/**
 * Gestionnaire de routage MVC
 * 
 * Classe responsable du routage des requêtes HTTP vers les contrôleurs appropriés.
 * Supporte les méthodes HTTP GET, POST, PUT, DELETE et les paramètres dynamiques dans les URLs.
 * Gère également la page 404 personnalisée pour les routes non trouvées.
 * 
 * @package MonBudget\Core
 * @author MonBudget
 * @version 1.0.0
 */
class Router
{
    /**
     * Tableau des routes enregistrées
     * 
     * @var array
     */
    private array $routes = [];
    
    /**
     * Tableau des routes nommées pour génération d'URLs
     * 
     * @var array
     */
    private array $namedRoutes = [];
    
    /**
     * Ajouter une route GET
     * 
     * @param string $path Chemin de la route (ex: '/users/{id}')
     * @param string|array $handler Handler [Controller::class, 'method'] ou closure
     * @param string|null $name Nom optionnel de la route pour génération d'URL
     * @return self Retourne l'instance pour chaînage
     */
    public function get(string $path, string|array $handler, ?string $name = null): self
    {
        return $this->addRoute('GET', $path, $handler, $name);
    }
    
    /**
     * Ajouter une route POST
     * 
     * @param string $path Chemin de la route
     * @param string|array $handler Handler [Controller::class, 'method'] ou closure
     * @param string|null $name Nom optionnel de la route
     * @return self Retourne l'instance pour chaînage
     */
    public function post(string $path, string|array $handler, ?string $name = null): self
    {
        return $this->addRoute('POST', $path, $handler, $name);
    }
    
    /**
     * Ajouter une route PUT
     * 
     * @param string $path Chemin de la route
     * @param string|array $handler Handler [Controller::class, 'method'] ou closure
     * @param string|null $name Nom optionnel de la route
     * @return self Retourne l'instance pour chaînage
     */
    public function put(string $path, string|array $handler, ?string $name = null): self
    {
        return $this->addRoute('PUT', $path, $handler, $name);
    }
    
    /**
     * Ajouter une route DELETE
     * 
     * @param string $path Chemin de la route
     * @param string|array $handler Handler [Controller::class, 'method'] ou closure
     * @param string|null $name Nom optionnel de la route
     * @return self Retourne l'instance pour chaînage
     */
    public function delete(string $path, string|array $handler, ?string $name = null): self
    {
        return $this->addRoute('DELETE', $path, $handler, $name);
    }
    
    /**
     * Ajouter une route pour toutes les méthodes HTTP
     * 
     * @param string $path Chemin de la route
     * @param string|array $handler Handler [Controller::class, 'method'] ou closure
     * @param string|null $name Nom optionnel de la route
     * @return self Retourne l'instance pour chaînage
     */
    public function any(string $path, string|array $handler, ?string $name = null): self
    {
        return $this->addRoute('ANY', $path, $handler, $name);
    }
    
    /**
     * Ajouter une route au registre
     */
    private function addRoute(string $method, string $path, string|array $handler, ?string $name = null): self
    {
        $route = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'pattern' => $this->convertToPattern($path)
        ];
        
        $this->routes[] = $route;
        
        if ($name) {
            $this->namedRoutes[$name] = $path;
        }
        
        return $this;
    }
    
    /**
     * Convertir un chemin en pattern regex
     */
    private function convertToPattern(string $path): string
    {
        // Remplacer {param} par des groupes regex nommés
        $pattern = preg_replace('/{([a-zA-Z_][a-zA-Z0-9_]*)}/', '(?P<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#';
    }
    
    /**
     * Dispatcher la requête vers le bon handler
     */
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = $this->getUri();
        
        foreach ($this->routes as $route) {
            if ($route['method'] !== 'ANY' && $route['method'] !== $method) {
                continue;
            }
            
            if (preg_match($route['pattern'], $uri, $matches)) {
                // Extraire les paramètres
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                
                $this->callHandler($route['handler'], $params);
                return;
            }
        }
        
        // Route non trouvée
        $this->handleNotFound();
    }
    
    /**
     * Obtenir l'URI propre
     */
    private function getUri(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        
        // Retirer la query string
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }
        
        // Retirer le BASE_URL si défini
        if (defined('BASE_URL') && BASE_URL !== '') {
            $uri = str_replace(BASE_URL, '', $uri);
        }
        
        // Normaliser l'URI
        $uri = '/' . trim($uri, '/');
        
        // Si l'URI est vide après traitement, c'est la racine
        if (empty($uri) || $uri === '//') {
            $uri = '/';
        }
        
        return $uri;
    }
    
    /**
     * Appeler le handler de la route
     */
    private function callHandler(string|array $handler, array $params = []): void
    {
        if (is_array($handler)) {
            [$controller, $method] = $handler;
            
            if (!class_exists($controller)) {
                throw new \Exception("Controller $controller not found");
            }
            
            $controllerInstance = new $controller();
            
            if (!method_exists($controllerInstance, $method)) {
                throw new \Exception("Method $method not found in controller $controller");
            }
            
            call_user_func_array([$controllerInstance, $method], $params);
        } elseif (is_callable($handler)) {
            call_user_func_array($handler, $params);
        }
    }
    
    /**
     * Gérer les routes non trouvées (404)
     */
    private function handleNotFound(): void
    {
        http_response_code(404);
        
        // Afficher la page 404 personnalisée
        $errorPage = __DIR__ . '/../Views/errors/404.php';
        if (file_exists($errorPage)) {
            require $errorPage;
        } else {
            // Fallback JSON si la page 404 n'existe pas
            echo json_encode([
                'error' => 'Route not found',
                'status' => 404
            ]);
        }
        exit;
    }
    
    /**
     * Générer une URL à partir d'un nom de route
     */
    public function url(string $name, array $params = []): string
    {
        if (!isset($this->namedRoutes[$name])) {
            throw new \Exception("Route name $name not found");
        }
        
        $path = $this->namedRoutes[$name];
        
        foreach ($params as $key => $value) {
            $path = str_replace('{' . $key . '}', $value, $path);
        }
        
        return $path;
    }
}
