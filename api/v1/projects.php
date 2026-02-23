<?php
/**
 * API /projects — CRUD projets (mobile)
 *
 * GET    /api/v1/projects            → Liste avec détails
 * POST   /api/v1/projects            → Créer
 * GET    /api/v1/projects/{id}       → Détail
 * PUT    /api/v1/projects/{id}       → Modifier
 * PATCH  /api/v1/projects/{id}/timeline → Mettre à jour timeline
 * DELETE /api/v1/projects/{id}       → Supprimer (admin)
 */

declare(strict_types=1);

use App\Models\Project;

$auth  = requireAuth();
$model = new Project();

match (true) {

    $method === 'GET' && $id === null =>
        apiResponse($model->allWithDetails()),

    $method === 'GET' && $id !== null && $subresource === null => (function () use ($model, $id) {
        $project = $model->find($id);
        if (!$project) apiError(404, 'Projet introuvable.');

        $project['timeline'] = $project['timeline']
            ? json_decode($project['timeline'], true)
            : [];
        $project['progress'] = $model->computeProgress(json_encode($project['timeline']));
        apiResponse($project);
    })(),

    $method === 'POST' && $id === null => (function () use ($model, $auth) {
        $body = jsonBody();
        if (empty(trim($body['name'] ?? ''))) apiError(422, 'name requis.');
        if (empty($body['client_id']))        apiError(422, 'client_id requis.');

        $body['created_by'] = $auth['user_id'];
        $body['timeline']   = json_encode($body['timeline'] ?? [], JSON_UNESCAPED_UNICODE);

        $newId = $model->create($body);
        apiResponse($model->find($newId), 201);
    })(),

    ($method === 'PUT' || $method === 'PATCH') && $id !== null && $subresource === null => (function () use ($model, $id) {
        if (!$model->find($id)) apiError(404, 'Projet introuvable.');

        $body = jsonBody();
        if (isset($body['timeline'])) {
            $body['timeline'] = json_encode($body['timeline'], JSON_UNESCAPED_UNICODE);
        }

        $model->update($id, $body);
        $updated = $model->find($id);
        $updated['timeline'] = json_decode($updated['timeline'] ?? '[]', true);
        apiResponse($updated);
    })(),

    // PATCH /projects/{id}/timeline — Met à jour uniquement les étapes
    $method === 'PATCH' && $id !== null && $subresource === 'timeline' => (function () use ($model, $id) {
        if (!$model->find($id)) apiError(404, 'Projet introuvable.');

        $steps = jsonBody()['steps'] ?? [];
        $ok    = $model->updateTimeline($id, $steps);

        apiResponse([
            'success'  => $ok,
            'progress' => $model->computeProgress(json_encode($steps)),
        ]);
    })(),

    $method === 'DELETE' && $id !== null => (function () use ($model, $id, $auth) {
        if ($auth['role'] !== 'admin') apiError(403, 'Accès refusé.');
        if (!$model->find($id))        apiError(404, 'Projet introuvable.');

        $model->delete($id);
        apiResponse(['deleted' => true]);
    })(),

    default => apiError(405, 'Méthode non supportée.')
};
