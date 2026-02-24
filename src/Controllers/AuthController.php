<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Models\User;

class AuthController
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    /**
     * GET /login — Affiche le formulaire de connexion.
     */
    public function showLogin(): void
    {
        if (Auth::isLoggedIn()) {
            redirect('/dashboard');
        }

        view('auth/login');
    }

    /**
     * POST /login — Traite la soumission du formulaire.
     */
    public function login(): void
    {
        verifyCsrf();

        $email    = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';

        // Validation basique
        if (empty($email) || empty($password)) {
            view('auth/login', ['error' => 'Email et mot de passe requis.']);
            return;
        }

        $user = $this->userModel->authenticate($email, $password);

        if (!$user) {
            // Message générique (ne pas révéler si c'est l'email ou le mdp)
            view('auth/login', ['error' => 'Identifiants invalides.']);
            return;
        }

        Auth::login($user);
        logMessage('info', 'Connexion : user #' . $user['id']);
        redirect('/dashboard');
    }

    /**
     * GET /logout — Déconnecte l'utilisateur.
     */
    public function logout(): void
    {
        $userId = Auth::user()['id'] ?? 'unknown';
        Auth::logout();
        logMessage('info', "Déconnexion : user #{$userId}");
        redirect('/login');
    }
}
