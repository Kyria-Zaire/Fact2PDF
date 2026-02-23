<?php
/**
 * JwtAuth - Authentification JWT pour l'API mobile
 *
 * Implémentation manuelle HS256 sans dépendance externe.
 * Usage : JwtAuth::generate($payload) / JwtAuth::verify($token)
 */

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

class JwtAuth
{
    private static string $secret;

    private static function getSecret(): string
    {
        if (empty(self::$secret)) {
            self::$secret = env('JWT_SECRET', '');
            if (strlen(self::$secret) < 32) {
                throw new RuntimeException('JWT_SECRET trop court (min 32 chars).');
            }
        }
        return self::$secret;
    }

    /**
     * Génère un token JWT signé HS256.
     *
     * @param array $payload Données à encoder (ex: ['user_id' => 1, 'role' => 'admin'])
     * @param int   $expiry  Durée de validité en secondes
     */
    public static function generate(array $payload, int $expiry = 3600): string
    {
        $header = self::base64UrlEncode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));

        $payload['iat'] = time();
        $payload['exp'] = time() + $expiry;
        $encodedPayload = self::base64UrlEncode(json_encode($payload));

        $signature = self::sign("{$header}.{$encodedPayload}");

        return "{$header}.{$encodedPayload}.{$signature}";
    }

    /**
     * Vérifie et décode un token JWT.
     *
     * @return array Payload décodé
     * @throws RuntimeException Si token invalide ou expiré
     */
    public static function verify(string $token): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new RuntimeException('Token JWT malformé.');
        }

        [$header, $payload, $signature] = $parts;

        // Vérifier la signature
        $expectedSig = self::sign("{$header}.{$payload}");
        if (!hash_equals($expectedSig, $signature)) {
            throw new RuntimeException('Signature JWT invalide.');
        }

        $data = json_decode(self::base64UrlDecode($payload), true);

        // Vérifier expiration
        if (isset($data['exp']) && $data['exp'] < time()) {
            throw new RuntimeException('Token JWT expiré.');
        }

        return $data;
    }

    /**
     * Extrait le JWT du header Authorization: Bearer <token>.
     */
    public static function fromRequest(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return $matches[1];
        }
        return null;
    }

    private static function sign(string $data): string
    {
        return self::base64UrlEncode(hash_hmac('sha256', $data, self::getSecret(), true));
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
