<?php
/**
 * RateLimiter — Limitation du nombre de requêtes par IP (API / login)
 *
 * Stockage fichier en storage/cache/ (pas de Redis requis).
 * Usage : RateLimiter::allow('auth_login', $ip) ou allowApi($ip).
 */

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

class RateLimiter
{
    private const WINDOW_SECONDS = 60;
    private const MAX_AUTH_ATTEMPTS = 10;
    private const MAX_API_REQUESTS = 120;

    private static string $cacheDir = '';

    private static function getCacheDir(): string
    {
        if (self::$cacheDir === '') {
            self::$cacheDir = ROOT_PATH . '/storage/cache';
            if (!is_dir(self::$cacheDir)) {
                mkdir(self::$cacheDir, 0755, true);
            }
        }
        return self::$cacheDir;
    }

    /**
     * Clé de cache sécurisée (évite traversal).
     */
    private static function key(string $prefix, string $ip): string
    {
        $safe = preg_replace('/[^a-zA-Z0-9_.-]/', '', $ip);
        return $prefix . '_' . $safe;
    }

    /**
     * Lit le nombre de requêtes dans la fenêtre courante.
     *
     * @return array{count: int, windowStart: int}
     */
    private static function read(string $key): array
    {
        $path = self::getCacheDir() . '/' . $key . '.lim';
        if (!file_exists($path)) {
            return ['count' => 0, 'windowStart' => time()];
        }
        $data = @file_get_contents($path);
        if ($data === false) {
            return ['count' => 0, 'windowStart' => time()];
        }
        $decoded = json_decode($data, true);
        if (!is_array($decoded) || !isset($decoded['count'], $decoded['windowStart'])) {
            return ['count' => 0, 'windowStart' => time()];
        }
        $windowStart = (int) $decoded['windowStart'];
        if (time() - $windowStart >= self::WINDOW_SECONDS) {
            return ['count' => 0, 'windowStart' => time()];
        }
        return [
            'count'       => (int) $decoded['count'],
            'windowStart' => $windowStart,
        ];
    }

    /**
     * Incrémente et persiste le compteur.
     */
    private static function increment(string $key): int
    {
        $data = self::read($key);
        $data['count']++;
        if ($data['count'] === 1) {
            $data['windowStart'] = time();
        }
        $path = self::getCacheDir() . '/' . $key . '.lim';
        file_put_contents($path, json_encode($data), LOCK_EX);
        return $data['count'];
    }

    /**
     * Vérifie si l'IP peut tenter une action (ex: login).
     * Retourne true si autorisé, lance une exception si limite dépassée.
     *
     * @throws RuntimeException Si limite dépassée
     */
    public static function allow(string $action, string $ip, int $maxAttempts = self::MAX_AUTH_ATTEMPTS): bool
    {
        $key = self::key($action, $ip);
        $count = self::increment($key);
        if ($count > $maxAttempts) {
            throw new RuntimeException('Trop de tentatives. Réessayez dans ' . self::WINDOW_SECONDS . ' secondes.');
        }
        return true;
    }

    /**
     * Vérifie la limite globale API pour une IP.
     *
     * @throws RuntimeException Si limite dépassée
     */
    public static function allowApi(string $ip): bool
    {
        $key = self::key('api_global', $ip);
        $data = self::read($key);
        $data['count']++;
        if ($data['count'] === 1) {
            $data['windowStart'] = time();
        }
        $path = self::getCacheDir() . '/' . $key . '.lim';
        file_put_contents($path, json_encode($data), LOCK_EX);

        if ($data['count'] > self::MAX_API_REQUESTS) {
            throw new RuntimeException('Quota de requêtes dépassé. Réessayez plus tard.');
        }
        return true;
    }

    /**
     * Récupère l'IP client (derrière proxy).
     */
    public static function getClientIp(): string
    {
        $headers = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        foreach ($headers as $h) {
            if (!empty($_SERVER[$h])) {
                $parts = explode(',', (string) $_SERVER[$h]);
                $ip = trim($parts[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
