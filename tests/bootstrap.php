<?php
/**
 * Bootstrap des tests PHPUnit
 * Charge l'autoloader Composer + helpers + constantes.
 */

declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));

require ROOT_PATH . '/vendor/autoload.php';

// Variables d'env de test (pas de vraie DB)
$_ENV['APP_ENV']    = 'testing';
$_ENV['APP_SECRET'] = 'test_secret_key_for_phpunit_32ch';
$_ENV['JWT_SECRET'] = 'test_jwt_secret_key_for_phpunit_32';
$_ENV['DB_HOST']    = '127.0.0.1';
$_ENV['DB_NAME']    = 'fact2pdf_test';
$_ENV['DB_USER']    = 'test';
$_ENV['DB_PASS']    = 'test';
