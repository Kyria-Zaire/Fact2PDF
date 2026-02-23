<?php
/**
 * Configuration générale de l'application
 * Charge les variables d'environnement depuis .env
 */

declare(strict_types=1);

// Charger .env si pas déjà fait
if (!function_exists('env')) {
    /**
     * Récupère une variable d'environnement avec valeur par défaut.
     */
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        return $value !== false ? $value : $default;
    }
}

return [
    'name'    => env('APP_NAME', 'Fact2PDF'),
    'env'     => env('APP_ENV', 'production'),
    'url'     => env('APP_URL', 'http://localhost:8080'),
    'secret'  => env('APP_SECRET'),
    'debug'   => env('APP_ENV', 'production') === 'development',

    'upload' => [
        'max_size' => (int) env('UPLOAD_MAX_SIZE', 5242880),  // 5MB
        'path'     => env('UPLOAD_PATH', __DIR__ . '/../public/storage/uploads'),
        'allowed'  => ['image/jpeg', 'image/png', 'image/webp'],
    ],

    'session' => [
        'lifetime' => 3600,
        'name'     => 'fact2pdf_sess',
    ],
];
