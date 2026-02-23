#!/usr/bin/env php
<?php
/**
 * Script de migration : exécute database/schema.sql
 * Usage : docker compose exec web php bin/migrate.php
 */

declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));

// Charger .env
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
$sql = file_get_contents(ROOT_PATH . '/database/schema.sql');

// Exécuter les statements un par un
foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
    if ($stmt) {
        $db->getPdo()->exec($stmt);
        echo '.';
    }
}

echo "\n[OK] Schema appliqué.\n";
