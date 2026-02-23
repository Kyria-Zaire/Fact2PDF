<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Client;

class ClientController
{
    private Client $model;

    public function __construct()
    {
        $this->model = new Client();
    }

    /** GET /clients */
    public function index(): void
    {
        $clients = $this->model->allWithStats();
        view('clients/index', compact('clients'));
    }

    /** GET /clients/create */
    public function create(): void
    {
        view('clients/form', ['client' => null, 'action' => '/clients']);
    }

    /** POST /clients */
    public function store(): void
    {
        verifyCsrf();
        $data = $this->sanitize($_POST);
        $data['created_by'] = $_SESSION['user_id'];

        // Gestion logo (upload image)
        if (!empty($_FILES['logo']['name'])) {
            $data['logo_path'] = $this->handleLogoUpload($_FILES['logo']);
        }

        $id = $this->model->create($data);
        redirect("/clients/{$id}");
    }

    /** GET /clients/{id} */
    public function show(string $id): void
    {
        $client   = $this->findOrAbort((int) $id);
        $contacts = $this->model->contacts((int) $id);
        view('clients/show', compact('client', 'contacts'));
    }

    /** GET /clients/{id}/edit */
    public function edit(string $id): void
    {
        $client = $this->findOrAbort((int) $id);
        view('clients/form', ['client' => $client, 'action' => "/clients/{$id}"]);
    }

    /** POST /clients/{id} */
    public function update(string $id): void
    {
        verifyCsrf();
        $this->findOrAbort((int) $id);

        $data = $this->sanitize($_POST);

        if (!empty($_FILES['logo']['name'])) {
            $data['logo_path'] = $this->handleLogoUpload($_FILES['logo']);
        }

        $this->model->update((int) $id, $data);
        redirect("/clients/{$id}");
    }

    /** POST /clients/{id}/delete */
    public function delete(string $id): void
    {
        verifyCsrf();
        $this->findOrAbort((int) $id);
        $this->model->delete((int) $id);
        redirect('/clients');
    }

    // ---- Private helpers ----

    private function findOrAbort(int $id): array
    {
        $client = $this->model->find($id);
        if (!$client) {
            http_response_code(404);
            exit('Client introuvable.');
        }
        return $client;
    }

    private function sanitize(array $post): array
    {
        return [
            'name'        => trim($post['name'] ?? ''),
            'email'       => filter_var($post['email'] ?? '', FILTER_SANITIZE_EMAIL),
            'phone'       => trim($post['phone'] ?? ''),
            'address'     => trim($post['address'] ?? ''),
            'city'        => trim($post['city'] ?? ''),
            'postal_code' => trim($post['postal_code'] ?? ''),
            'country'     => trim($post['country'] ?? 'FR'),
            'notes'       => trim($post['notes'] ?? ''),
        ];
    }

    private function handleLogoUpload(array $file): string
    {
        $config   = require ROOT_PATH . '/config/app.php';
        $allowed  = $config['upload']['allowed'];
        $maxSize  = $config['upload']['max_size'];
        $uploadDir = $config['upload']['path'] . '/logos/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        if (!in_array($file['type'], $allowed, true)) {
            throw new \RuntimeException('Type de fichier non autorisé.');
        }

        if ($file['size'] > $maxSize) {
            throw new \RuntimeException('Fichier trop volumineux.');
        }

        $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = bin2hex(random_bytes(8)) . '.' . strtolower($ext);
        move_uploaded_file($file['tmp_name'], $uploadDir . $filename);

        return '/storage/uploads/logos/' . $filename;
    }
}
