<?php
/**
 * API /auth — Authentification JWT
 *
 * POST /api/v1/auth/login   → { email, password } → { token, user }
 * POST /api/v1/auth/refresh → { token } → { token }
 */

declare(strict_types=1);

use App\Models\User;
use App\Core\JwtAuth;

$action = $parts[1] ?? 'login';

match ([$method, $action]) {
    ['POST', 'login'] => (function () {
        $ip = \App\Core\RateLimiter::getClientIp();
        try {
            \App\Core\RateLimiter::allow('auth_login', $ip);
        } catch (\Throwable $e) {
            apiError(429, $e->getMessage());
        }

        $body     = jsonBody();
        $email    = filter_var($body['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $password = $body['password'] ?? '';

        if (empty($email) || empty($password)) {
            apiError(422, 'Email et mot de passe requis.');
        }

        $userModel = new User();
        $user      = $userModel->authenticate($email, $password);

        if (!$user) {
            apiError(401, 'Identifiants invalides.');
        }

        $token = JwtAuth::generate([
            'user_id'  => $user['id'],
            'username' => $user['username'],
            'role'     => $user['role'],
        ], (int) env('JWT_EXPIRY', 3600));

        apiResponse([
            'token' => $token,
            'user'  => [
                'id'       => $user['id'],
                'username' => $user['username'],
                'email'    => $user['email'],
                'role'     => $user['role'],
            ],
        ]);
    })(),

    default => apiError(405, 'Méthode non supportée.')
};
