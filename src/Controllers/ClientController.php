<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Client;
use App\Services\ImageService;

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

        $existing = $this->findOrAbort((int) $id);
        $data     = $this->sanitize($_POST);

        if (!empty($_FILES['logo']['name'])) {
            // Passer l'ancien chemin pour suppression propre
            $data['logo_path'] = $this->handleLogoUpload($_FILES['logo'], $existing['logo_path'] ?? null);
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

    /**
     * Délègue l'upload à ImageService :
     * - Validation MIME réelle (pas celle déclarée par le client)
     * - Redimensionnement 300×150 WebP via Intervention/Image
     * - Nom aléatoire sécurisé, suppression de l'ancien logo
     */
    private function handleLogoUpload(array $file, ?string $oldPath = null): string
    {
        $svc  = new ImageService();

        // Supprimer l'ancien logo si on remplace
        if ($oldPath) {
            $svc->delete($oldPath);
        }

        return $svc->uploadLogo($file);
    }
}
