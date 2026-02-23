<?php
/**
 * Configuration de la base de données
 * Utilise PDO avec prepared statements (anti SQL injection)
 */

declare(strict_types=1);

return [
    'driver'   => 'mysql',
    'host'     => env('DB_HOST', 'db'),
    'port'     => (int) env('DB_PORT', 3306),
    'database' => env('DB_NAME', 'fact2pdf'),
    'username' => env('DB_USER', 'fact2pdf_user'),
    'password' => env('DB_PASS', ''),
    'charset'  => env('DB_CHARSET', 'utf8mb4'),

    // Options PDO recommandées pour sécurité et debug
    'options'  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,  // Vrais prepared statements
        PDO::MYSQL_ATTR_FOUND_ROWS   => true,
    ],
];
