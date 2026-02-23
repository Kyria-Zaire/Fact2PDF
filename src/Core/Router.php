<?php
/**
 * Router - Front Controller Pattern
 *
 * Charge les routes depuis config/routes.php,
 * fait correspondre l'URI entrante, vérifie les droits,
 * et dispatche vers le bon Controller::method.
 */

declare(strict_types=1);

namespace App\Core;

use App\Core\Auth;

class Router
{
    /** @var array Routes chargées depuis config/routes.php */
    private array $routes = [];

    public function __construct()
    {
        $this->routes = require ROOT_PATH . '/config/routes.php';
    }

    /**
     * Dispatche la requête courante vers le bon contrôleur.
     */
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri    = $this->parseUri();

        foreach ($this->routes as [$routeMethod, $pattern, $handler, $roles]) {
            $params = $this->match($uri, $pattern);

            if ($routeMethod !== $method || $params === null) {
                continue;
            }

            // Vérification des droits
            if ($roles !== null && !Auth::hasRole($roles)) {
                if (!Auth::isLoggedIn()) {
                    redirect('/login');
                }
                $this->abort(403, 'Accès refusé.');
                return;
            }

            // Résoudre Controller@method
            [$controllerName, $action] = explode('@', $handler);
            $class = "App\\Controllers\\{$controllerName}";

            if (!class_exists($class)) {
                $this->abort(500, "Contrôleur {$class} introuvable.");
                return;
            }

            $controller = new $class();

            if (!method_exists($controller, $action)) {
                $this->abort(500, "Méthode {$action} introuvable dans {$class}.");
                return;
            }

            // Appel avec les paramètres URI extraits ({id}, etc.)
            call_user_func_array([$controller, $action], $params);
            return;
        }

        $this->abort(404, 'Page introuvable.');
    }

    /**
     * Extrait et nettoie l'URI (sans query string).
     */
    private function parseUri(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        return rtrim($uri, '/') ?: '/';
    }

    /**
     * Compare l'URI avec un pattern, retourne les paramètres ou null.
     *
     * @return array|null Tableau de paramètres ou null si pas de match
     */
    private function match(string $uri, string $pattern): ?array
    {
        // Convertit {id} en groupe de capture regex
        $regex = preg_replace('/\{([a-z_]+)\}/', '([^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';

        if (!preg_match($regex, $uri, $matches)) {
            return null;
        }

        // Retirer le match global, garder seulement les groupes
        array_shift($matches);
        return $matches;
    }

    /**
     * Affiche une page d'erreur HTTP et arrête l'exécution.
     */
    private function abort(int $code, string $message): void
    {
        http_response_code($code);
        echo "<h1>Erreur {$code}</h1><p>" . htmlspecialchars($message) . "</p>";
        exit;
    }
}
