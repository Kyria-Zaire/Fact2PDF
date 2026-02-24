<?php
/**
 * Point d'entrée Vercel (web) — même bootstrap que public/index.php
 * ROOT_PATH = racine du projet (parent de api/)
 */

declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));

try {
    $composerAutoload = ROOT_PATH . '/vendor/autoload.php';
    if (file_exists($composerAutoload)) {
        require $composerAutoload;
    } else {
        spl_autoload_register(function (string $class): void {
            $file = ROOT_PATH . '/src/' . str_replace(['App\\', '\\'], ['', '/'], $class) . '.php';
            if (file_exists($file)) {
                require $file;
            }
        });
        require ROOT_PATH . '/src/Helpers/helpers.php';
    }

    $envFile = ROOT_PATH . '/.env';
    if (file_exists($envFile)) {
        foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }
            [$key, $val] = explode('=', $line, 2);
            $key = trim($key);
            $val = trim($val, " \t\"'");
            $_ENV[$key] = $val;
        }
    }

    $config = require ROOT_PATH . '/config/app.php';
    if ($config['debug']) {
        ini_set('display_errors', '1');
        error_reporting(E_ALL);
    } else {
        ini_set('display_errors', '0');
        error_reporting(0);
    }

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    if (!empty($config['force_https'])) {
        $isHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
        if (!$isHttps) {
            header('Location: https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '/'), true, 301);
            exit;
        }
    }

    if (getenv('VERCEL') === '1') {
        ini_set('session.save_path', '/tmp');
    }
    foreach (['logs', 'cache'] as $dir) {
        $path = ROOT_PATH . '/storage/' . $dir;
        if (!is_dir($path)) {
            @mkdir($path, 0755, true);
        }
    }
    foreach (['uploads/logos'] as $dir) {
        $path = ROOT_PATH . '/public/storage/' . $dir;
        if (!is_dir($path)) {
            @mkdir($path, 0755, true);
        }
    }

    \App\Core\Auth::startSession();

    $router = new \App\Core\Router();
    $router->dispatch();
} catch (Throwable $e) {
    // Log pour Vercel Runtime Logs (Deployments → Logs)
    error_log('[Fact2PDF 500] ' . $e->getMessage());
    error_log('[Fact2PDF] ' . $e->getFile() . ':' . $e->getLine());
    error_log($e->getTraceAsString());
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
    }
    $debug = (getenv('VERCEL_DEBUG') === '1' || ($_ENV['APP_ENV'] ?? '') === 'development');
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Erreur</title></head><body>';
    echo '<h1>Cette page n\'est pas disponible pour le moment</h1>';
    if ($debug) {
        echo '<pre>' . htmlspecialchars($e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine()) . '</pre>';
    } else {
        echo '<p>Consultez les <strong>Runtime Logs</strong> dans le dashboard Vercel (Deployments → votre déploiement → Logs) pour le détail.</p>';
    }
    echo '</body></html>';
}
