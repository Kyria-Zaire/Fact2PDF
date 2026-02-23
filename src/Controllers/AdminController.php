<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use App\Models\Client;
use App\Models\Invoice;
use App\Core\Database;

class AdminController
{
    private User $userModel;
    private Client $clientModel;
    private Invoice $invoiceModel;

    public function __construct()
    {
        $this->userModel   = new User();
        $this->clientModel = new Client();
        $this->invoiceModel = new Invoice();
    }

    /**
     * GET /admin — Tableau de bord admin (liens vers gestion users, stats).
     */
    public function index(): void
    {
        $userCount   = count($this->userModel->all('id', 'ASC'));
        $clientCount = count($this->clientModel->all());
        $invoiceStats = $this->invoiceModel->stats();
        $db = Database::getInstance();
        $recentUsers = $db->fetchAll(
            'SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 5'
        );

        view('admin/index', [
            'userCount'    => $userCount,
            'clientCount'  => $clientCount,
            'invoiceStats' => $invoiceStats,
            'recentUsers'  => $recentUsers,
        ]);
    }

    /**
     * GET /admin/users — Liste des utilisateurs + formulaire création.
     */
    public function users(): void
    {
        $users = $this->userModel->all('created_at', 'DESC');
        view('admin/users', ['users' => $users]);
    }

    /**
     * POST /admin/users — Création d'un nouvel utilisateur (admin uniquement).
     */
    public function createUser(): void
    {
        verifyCsrf();

        $username = trim((string) ($_POST['username'] ?? ''));
        $email    = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';
        $role     = $_POST['role'] ?? 'user';

        $errors = [];
        if (strlen($username) < 2) {
            $errors[] = 'Le nom d\'utilisateur doit faire au moins 2 caractères.';
        }
        if (empty($email)) {
            $errors[] = 'L\'email est requis.';
        }
        if (strlen($password) < 8) {
            $errors[] = 'Le mot de passe doit faire au moins 8 caractères.';
        }
        if (!in_array($role, ['admin', 'user', 'viewer'], true)) {
            $role = 'user';
        }

        if ($this->userModel->findByEmail($email)) {
            $errors[] = 'Un utilisateur avec cet email existe déjà.';
        }

        if (!empty($errors)) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => implode(' ', $errors)];
            redirect('/admin/users');
        }

        $this->userModel->createUser([
            'username' => $username,
            'email'    => $email,
            'password' => $password,
            'role'     => $role,
        ]);

        logMessage('info', "Admin a créé l'utilisateur : {$email}");
        $_SESSION['flash'] = ['type' => 'success', 'message' => "Utilisateur « " . e($username) . " » créé avec succès."];
        redirect('/admin/users');
    }
}
