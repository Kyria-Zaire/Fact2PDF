<?php

declare(strict_types=1);

namespace App\Models;

class User extends BaseModel
{
    protected string $table = 'users';

    protected array $fillable = ['username', 'email', 'password_hash', 'role', 'is_active'];

    /**
     * Trouve un utilisateur par son email.
     */
    public function findByEmail(string $email): array|false
    {
        return $this->db->fetchOne(
            'SELECT * FROM users WHERE email = :email AND is_active = 1 LIMIT 1',
            ['email' => $email]
        );
    }

    /**
     * Vérifie les credentials et retourne l'utilisateur si valide.
     */
    public function authenticate(string $email, string $password): array|false
    {
        $user = $this->findByEmail($email);

        if (!$user) {
            return false;
        }

        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }

        return $user;
    }

    /**
     * Crée un utilisateur avec mot de passe hashé.
     */
    public function createUser(array $data): int
    {
        $data['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        unset($data['password']);

        return $this->create($data);
    }
}
