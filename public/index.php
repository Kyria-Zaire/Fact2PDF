<?php
/**
 * Front Controller — Point d'entrée unique de l'application web
 *
 * Toutes les requêtes HTTP passent ici (via Nginx try_files).
 * Charge l'environnement, démarre la session, et dispatche via le Router.
 */

declare(strict_types=1);

// ---- Constante racine ----
define('ROOT_PATH', dirname(__DIR__));

// ---- Autoloader PSR-4 manuel (sans Composer pour l'instant) ----
spl_autoload_register(function (string $class): void {
    // Namespace App\ → src/
    $file = ROOT_PATH . '/src/' . str_replace(['App\\', '\\'], ['', '/'], $class) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

// ---- Charger .env ----
$envFile = ROOT_PATH . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $val] = explode('=', $line, 2);
        $key = trim($key);
        $val = trim($val, " \t\"'");  // Retire quotes si présentes
        $_ENV[$key] = $val;
    }
}

// ---- Helpers globaux ----
require ROOT_PATH . '/src/Helpers/helpers.php';

// ---- Gestion des erreurs ----
$config = require ROOT_PATH . '/config/app.php';
if ($config['debug']) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}

// ---- Créer les dossiers de storage si besoin ----
foreach (['logs', 'uploads/logos', 'cache'] as $dir) {
    $path = ROOT_PATH . '/storage/' . $dir;
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
    }
}

// ---- Démarrer la session sécurisée ----
\App\Core\Auth::startSession();

// ---- Dispatcher ----
$router = new \App\Core\Router();
$router->dispatch();
