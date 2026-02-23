<?php
/**
 * API REST v1 - Point d'entrée principal
 *
 * Route les requêtes vers les handlers correspondants.
 * Auth : JWT via header Authorization: Bearer <token>
 *
 * Endpoints :
 *   POST   /api/v1/auth/login      → Obtenir un token JWT
 *   GET    /api/v1/clients         → Liste clients
 *   POST   /api/v1/clients         → Créer client
 *   GET    /api/v1/clients/{id}    → Détail client
 *   GET    /api/v1/invoices        → Liste factures
 *   GET    /api/v1/invoices/{id}   → Détail facture
 */

declare(strict_types=1);

// Bootstrap
define('ROOT_PATH', dirname(__DIR__, 2));

// Autoloader Composer
$composerAutoload = ROOT_PATH . '/vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require $composerAutoload;
} else {
    require ROOT_PATH . '/src/Helpers/helpers.php';
}

// Charger .env
$envFile = ROOT_PATH . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (!str_starts_with(trim($line), '#') && str_contains($line, '=')) {
            [$key, $val] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($val);
        }
    }
}

// Headers CORS + JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

// Répondre aux preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Router simple pour l'API
$method = $_SERVER['REQUEST_METHOD'];
$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
// Normaliser : /api/v1/clients → clients
$path   = preg_replace('#^/api/v1/?#', '', $uri);
$parts  = explode('/', trim($path, '/'));

$resource = $parts[0] ?? '';
$id       = isset($parts[1]) ? (int) $parts[1] : null;
$subresource = $parts[2] ?? null;

try {
    match ($resource) {
        'auth'     => require __DIR__ . '/auth.php',
        'clients'  => require __DIR__ . '/clients.php',
        'invoices' => require __DIR__ . '/invoices.php',
        default    => apiError(404, 'Endpoint introuvable.')
    };
} catch (\Exception $e) {
    apiError(500, $e->getMessage());
}

// ---- Helpers API ----

function apiResponse(mixed $data, int $code = 200): never
{
    http_response_code($code);
    echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
    exit;
}

function apiError(int $code, string $message): never
{
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Vérifie le JWT et retourne le payload ou arrête avec 401.
 */
function requireAuth(): array
{
    $token = \App\Core\JwtAuth::fromRequest();
    if (!$token) {
        apiError(401, 'Token manquant.');
    }
    try {
        return \App\Core\JwtAuth::verify($token);
    } catch (\Exception $e) {
        apiError(401, $e->getMessage());
    }
}

/**
 * Retourne le body JSON décodé.
 */
function jsonBody(): array
{
    $body = file_get_contents('php://input');
    return json_decode($body ?: '{}', true) ?? [];
}
