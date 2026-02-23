<?php
/**
 * Auth - Gestion sessions et droits utilisateurs
 *
 * Rôles : admin > user > viewer
 * Sessions sécurisées (httponly, samesite=strict).
 */

declare(strict_types=1);

namespace App\Core;

class Auth
{
    /**
     * Démarre une session sécurisée si pas déjà active.
     */
    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $config = require ROOT_PATH . '/config/app.php';

            session_set_cookie_params([
                'lifetime' => $config['session']['lifetime'],
                'path'     => '/',
                'secure'   => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Strict',
            ]);

            session_name($config['session']['name']);
            session_start();
        }
    }

    /**
     * Connecte un utilisateur en session.
     *
     * @param array $user Données utilisateur (id, username, role)
     */
    public static function login(array $user): void
    {
        self::startSession();
        session_regenerate_id(true); // Anti session fixation

        $_SESSION['user_id']   = $user['id'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['role']      = $user['role'];
        $_SESSION['logged_at'] = time();
    }

    /**
     * Déconnecte l'utilisateur et détruit la session.
     */
    public static function logout(): void
    {
        self::startSession();
        $_SESSION = [];
        session_destroy();
        setcookie(session_name(), '', time() - 3600, '/');
    }

    /**
     * Vérifie si l'utilisateur est connecté.
     */
    public static function isLoggedIn(): bool
    {
        self::startSession();
        return isset($_SESSION['user_id']);
    }

    /**
     * Retourne l'utilisateur connecté ou null.
     */
    public static function user(): ?array
    {
        if (!self::isLoggedIn()) {
            return null;
        }

        return [
            'id'       => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'role'     => $_SESSION['role'],
        ];
    }

    /**
     * Vérifie si l'utilisateur a l'un des rôles donnés.
     *
     * @param string|array $roles Rôle(s) autorisé(s)
     */
    public static function hasRole(string|array $roles): bool
    {
        if (!self::isLoggedIn()) {
            return false;
        }

        $allowed = (array) $roles;
        return in_array($_SESSION['role'], $allowed, true);
    }

    /**
     * Hiérarchie des rôles : admin peut faire ce que user peut faire, etc.
     *
     * @param string $minRole Rôle minimum requis
     */
    public static function hasMinRole(string $minRole): bool
    {
        $hierarchy = ['viewer' => 1, 'user' => 2, 'admin' => 3];
        $userLevel = $hierarchy[$_SESSION['role'] ?? ''] ?? 0;
        $required  = $hierarchy[$minRole] ?? 99;

        return $userLevel >= $required;
    }
}
