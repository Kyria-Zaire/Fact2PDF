<?php
/**
 * API /clients — CRUD clients
 *
 * GET    /api/v1/clients        → Liste
 * POST   /api/v1/clients        → Créer
 * GET    /api/v1/clients/{id}   → Détail + contacts
 * PUT    /api/v1/clients/{id}   → Modifier
 * DELETE /api/v1/clients/{id}   → Supprimer (admin)
 */

declare(strict_types=1);

use App\Models\Client;

$auth   = requireAuth();
$model  = new Client();

match (true) {

    // GET /clients — Liste tous les clients
    $method === 'GET' && $id === null =>
        apiResponse($model->allWithStats()),

    // GET /clients/{id} — Détail + contacts
    $method === 'GET' && $id !== null => (function () use ($model, $id) {
        $client = $model->find($id);
        if (!$client) apiError(404, 'Client introuvable.');

        $client['contacts'] = $model->contacts($id);
        apiResponse($client);
    })(),

    // POST /clients — Créer
    $method === 'POST' && $id === null => (function () use ($model, $auth) {
        $body = jsonBody();
        $body['created_by'] = $auth['user_id'];

        // Validation minimale
        if (empty(trim($body['name'] ?? ''))) {
            apiError(422, 'Le champ name est requis.');
        }

        $newId = $model->create($body);
        apiResponse($model->find($newId), 201);
    })(),

    // PUT /clients/{id} — Modifier
    ($method === 'PUT' || $method === 'PATCH') && $id !== null => (function () use ($model, $id) {
        if (!$model->find($id)) apiError(404, 'Client introuvable.');

        $body = jsonBody();
        $model->update($id, $body);
        apiResponse($model->find($id));
    })(),

    // DELETE /clients/{id} — Supprimer (admin seulement)
    $method === 'DELETE' && $id !== null => (function () use ($model, $id, $auth) {
        if ($auth['role'] !== 'admin') apiError(403, 'Accès refusé.');
        if (!$model->find($id)) apiError(404, 'Client introuvable.');

        $model->delete($id);
        apiResponse(['deleted' => true]);
    })(),

    default => apiError(405, 'Méthode non supportée.')
};
