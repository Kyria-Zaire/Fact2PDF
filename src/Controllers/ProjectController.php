<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Project;
use App\Models\Client;
use App\Models\Invoice;

class ProjectController
{
    private Project $model;
    private Client  $clientModel;

    public function __construct()
    {
        $this->model       = new Project();
        $this->clientModel = new Client();
    }

    /** GET /projects */
    public function index(): void
    {
        $projects = $this->model->allWithDetails();
        $stats    = $this->model->stats();
        view('projects/index', compact('projects', 'stats'));
    }

    /** GET /projects/create */
    public function create(): void
    {
        $clients  = $this->clientModel->all('name', 'ASC');
        view('projects/form', [
            'project' => null,
            'clients' => $clients,
            'action'  => '/projects',
        ]);
    }

    /** POST /projects */
    public function store(): void
    {
        verifyCsrf();
        $data = $this->sanitize($_POST);
        $this->validateOrFail($data);
        $data['created_by'] = $_SESSION['user_id'];

        // Encoder la timeline initiale (étapes vides depuis POST ou vide)
        $data['timeline'] = $this->buildInitialTimeline($_POST['steps'] ?? []);

        $id = $this->model->create($data);
        redirect("/projects/{$id}");
    }

    /** GET /projects/{id} */
    public function show(string $id): void
    {
        $project = $this->findOrAbort((int) $id);
        $project['timeline'] = $project['timeline']
            ? json_decode($project['timeline'], true)
            : [];
        $project['progress'] = $this->model->computeProgress(
            json_encode($project['timeline'])
        );

        $client = $this->clientModel->find((int) $project['client_id']);
        view('projects/show', compact('project', 'client'));
    }

    /** GET /projects/{id}/edit */
    public function edit(string $id): void
    {
        $project  = $this->findOrAbort((int) $id);
        $clients  = $this->clientModel->all('name', 'ASC');
        $project['timeline'] = $project['timeline']
            ? json_decode($project['timeline'], true)
            : [];

        view('projects/form', [
            'project' => $project,
            'clients' => $clients,
            'action'  => "/projects/{$id}",
        ]);
    }

    /** POST /projects/{id} */
    public function update(string $id): void
    {
        verifyCsrf();
        $this->findOrAbort((int) $id);

        $data = $this->sanitize($_POST);
        $this->validateOrFail($data);
        $data['timeline'] = $this->buildInitialTimeline($_POST['steps'] ?? []);

        $this->model->update((int) $id, $data);
        redirect("/projects/{$id}");
    }

    /** POST /projects/{id}/delete */
    public function delete(string $id): void
    {
        verifyCsrf();
        $this->findOrAbort((int) $id);
        $this->model->delete((int) $id);
        redirect('/projects');
    }

    /**
     * POST /projects/{id}/timeline — Met à jour les étapes via AJAX.
     * Attend JSON body : [{"label":"...","date":"...","done":true}, ...]
     */
    public function updateTimeline(string $id): void
    {
        $project = $this->findOrAbort((int) $id);

        $body  = json_decode(file_get_contents('php://input'), true) ?? [];
        $steps = array_map(fn($s) => [
            'label' => htmlspecialchars($s['label'] ?? '', ENT_QUOTES, 'UTF-8'),
            'date'  => $s['date'] ?? '',
            'done'  => (bool) ($s['done'] ?? false),
        ], $body['steps'] ?? []);

        $ok = $this->model->updateTimeline((int) $id, $steps);

        header('Content-Type: application/json');
        echo json_encode([
            'success'  => $ok,
            'progress' => $this->model->computeProgress(json_encode($steps)),
        ]);
        exit;
    }

    // ---- Private helpers ----

    private function findOrAbort(int $id): array
    {
        $project = $this->model->find($id);
        if (!$project) {
            http_response_code(404);
            exit('Projet introuvable.');
        }
        return $project;
    }

    private function sanitize(array $post): array
    {
        return [
            'client_id'   => (int)   filter_var($post['client_id']  ?? 0, FILTER_SANITIZE_NUMBER_INT),
            'invoice_id'  => !empty($post['invoice_id'])
                                ? (int) filter_var($post['invoice_id'], FILTER_SANITIZE_NUMBER_INT)
                                : null,
            'name'        => htmlspecialchars(trim($post['name']        ?? ''), ENT_QUOTES, 'UTF-8'),
            'description' => htmlspecialchars(trim($post['description'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'status'      => in_array($post['status'] ?? '', Project::STATUSES, true)
                                ? $post['status'] : 'todo',
            'priority'    => in_array($post['priority'] ?? '', Project::PRIORITIES, true)
                                ? $post['priority'] : 'medium',
            'start_date'  => !empty($post['start_date']) ? $post['start_date'] : null,
            'end_date'    => !empty($post['end_date'])   ? $post['end_date']   : null,
        ];
    }

    private function validateOrFail(array $data): void
    {
        $errors = [];
        if ($data['client_id'] <= 0)    $errors[] = 'Client requis.';
        if (trim($data['name']) === '')  $errors[] = 'Nom du projet requis.';

        if (!empty($errors)) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => implode(' ', $errors)];
            redirect('/projects/create');
        }
    }

    private function buildInitialTimeline(array $steps): string
    {
        $clean = [];
        foreach ($steps as $step) {
            $label = trim($step['label'] ?? '');
            if ($label === '') continue;
            $clean[] = [
                'label' => htmlspecialchars($label, ENT_QUOTES, 'UTF-8'),
                'date'  => $step['date'] ?? '',
                'done'  => (bool) ($step['done'] ?? false),
            ];
        }
        return json_encode($clean, JSON_UNESCAPED_UNICODE);
    }
}
