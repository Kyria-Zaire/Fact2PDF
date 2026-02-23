#!/usr/bin/env php
<?php
/**
 * Script de seed : insère les données de test
 * Usage : docker compose exec web php bin/seed.php
 */

declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));

foreach (file(ROOT_PATH . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (!str_starts_with(trim($line), '#') && str_contains($line, '=')) {
        [$k, $v] = explode('=', $line, 2);
        $_ENV[trim($k)] = trim($v);
    }
}

require ROOT_PATH . '/src/Helpers/helpers.php';
require ROOT_PATH . '/src/Core/Database.php';

use App\Core\Database;

$db  = Database::getInstance();
$sql = file_get_contents(ROOT_PATH . '/database/seeds.sql');

foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
    if ($stmt) {
        $db->getPdo()->exec($stmt);
        echo '.';
    }
}

echo "\n[OK] Seeds insérés. Login: admin@fact2pdf.local / password\n";
