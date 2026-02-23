<?php
/**
 * API /invoices — Lecture factures (mobile = read-only)
 *
 * GET /api/v1/invoices            → Liste (avec client)
 * GET /api/v1/invoices/{id}       → Détail + lignes
 * GET /api/v1/invoices/{id}/items → Lignes uniquement
 */

declare(strict_types=1);

use App\Models\Invoice;
use App\Models\Client;

$auth  = requireAuth();
$model = new Invoice();

match (true) {

    // GET /invoices — Liste
    $method === 'GET' && $id === null =>
        apiResponse($model->allWithClient()),

    // GET /invoices/{id} — Détail complet
    $method === 'GET' && $id !== null && $subresource === null => (function () use ($model, $id) {
        $invoice = $model->find($id);
        if (!$invoice) apiError(404, 'Facture introuvable.');

        $invoice['items']  = $model->items($id);
        $invoice['client'] = (new Client())->find((int) $invoice['client_id']);
        apiResponse($invoice);
    })(),

    // GET /invoices/{id}/items
    $method === 'GET' && $id !== null && $subresource === 'items' => (function () use ($model, $id) {
        if (!$model->find($id)) apiError(404, 'Facture introuvable.');
        apiResponse($model->items($id));
    })(),

    default => apiError(405, 'Méthode non supportée.')
};
