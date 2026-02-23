<?php
/**
 * Fonctions helper globales
 * Auto-chargées via public/index.php
 */

declare(strict_types=1);

/**
 * Redirige vers une URL et stoppe l'exécution.
 */
function redirect(string $url): never
{
    header("Location: {$url}");
    exit;
}

/**
 * Échappe une valeur pour affichage HTML sécurisé.
 */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Récupère une variable d'environnement.
 */
function env(string $key, mixed $default = null): mixed
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    return $value !== false ? $value : $default;
}

/**
 * Charge et retourne une vue PHP avec variables injectées.
 *
 * @param string $view   Chemin relatif depuis src/Views/ (ex: 'clients/index')
 * @param array  $data   Variables à extraire dans la vue
 */
function view(string $view, array $data = []): void
{
    extract($data, EXTR_SKIP);
    $path = ROOT_PATH . '/src/Views/' . str_replace('.', '/', $view) . '.php';

    if (!file_exists($path)) {
        throw new RuntimeException("Vue introuvable : {$view}");
    }

    require $path;
}

/**
 * Retourne la valeur d'un champ POST nettoyée.
 */
function post(string $key, mixed $default = ''): mixed
{
    return $_POST[$key] ?? $default;
}

/**
 * Génère un token CSRF et le stocke en session.
 */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie le token CSRF d'un formulaire POST.
 *
 * @throws RuntimeException Si token invalide
 */
function verifyCsrf(): void
{
    $token = $_POST['_csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        throw new RuntimeException('Token CSRF invalide.');
    }
}

/**
 * Formate un montant en euros.
 */
function formatMoney(float $amount): string
{
    return number_format($amount, 2, ',', ' ') . ' €';
}

/**
 * Formate une date SQL (YYYY-MM-DD) en DD/MM/YYYY.
 */
function formatDate(string $date): string
{
    return date('d/m/Y', strtotime($date));
}

/**
 * Journalise un message dans storage/logs/app.log.
 */
function logMessage(string $level, string $message): void
{
    $logPath = ROOT_PATH . '/storage/logs/app.log';
    $line    = sprintf("[%s] [%s] %s\n", date('Y-m-d H:i:s'), strtoupper($level), $message);
    file_put_contents($logPath, $line, FILE_APPEND | LOCK_EX);
}
