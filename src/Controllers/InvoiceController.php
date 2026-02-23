<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Invoice;
use App\Models\Client;

class InvoiceController
{
    private Invoice $model;
    private Client  $clientModel;

    public function __construct()
    {
        $this->model       = new Invoice();
        $this->clientModel = new Client();
    }

    /** GET /invoices */
    public function index(): void
    {
        $invoices = $this->model->allWithClient();
        view('invoices/index', compact('invoices'));
    }

    /** GET /invoices/create */
    public function create(): void
    {
        $clients = $this->clientModel->all('name', 'ASC');
        view('invoices/form', ['invoice' => null, 'clients' => $clients, 'action' => '/invoices']);
    }

    /** POST /invoices */
    public function store(): void
    {
        verifyCsrf();

        $data             = $this->sanitize($_POST);
        $data['number']   = $this->model->generateNumber();
        $data['created_by'] = $_SESSION['user_id'];

        $invoiceId = $this->model->create($data);

        // Enregistrer les lignes de facture
        $this->saveItems($invoiceId, $_POST['items'] ?? []);

        redirect("/invoices/{$invoiceId}");
    }

    /** GET /invoices/{id} */
    public function show(string $id): void
    {
        $invoice = $this->findOrAbort((int) $id);
        $items   = $this->model->items((int) $id);
        $client  = $this->clientModel->find((int) $invoice['client_id']);
        view('invoices/show', compact('invoice', 'items', 'client'));
    }

    /** GET /invoices/{id}/edit */
    public function edit(string $id): void
    {
        $invoice = $this->findOrAbort((int) $id);
        $clients = $this->clientModel->all('name', 'ASC');
        $items   = $this->model->items((int) $id);
        view('invoices/form', [
            'invoice' => $invoice,
            'clients' => $clients,
            'items'   => $items,
            'action'  => "/invoices/{$id}"
        ]);
    }

    /** POST /invoices/{id} */
    public function update(string $id): void
    {
        verifyCsrf();
        $this->findOrAbort((int) $id);

        $data = $this->sanitize($_POST);
        $this->model->update((int) $id, $data);

        // Remplacer les lignes existantes
        $this->model->db->query('DELETE FROM invoice_items WHERE invoice_id = :id', ['id' => $id]);
        $this->saveItems((int) $id, $_POST['items'] ?? []);

        redirect("/invoices/{$id}");
    }

    /** POST /invoices/{id}/delete */
    public function delete(string $id): void
    {
        verifyCsrf();
        $this->findOrAbort((int) $id);
        $this->model->delete((int) $id);
        redirect('/invoices');
    }

    /** GET /invoices/{id}/pdf — Génère et télécharge le PDF */
    public function pdf(string $id): void
    {
        $invoice = $this->findOrAbort((int) $id);
        $items   = $this->model->items((int) $id);
        $client  = $this->clientModel->find((int) $invoice['client_id']);

        // TODO: Intégrer TCPDF ici
        // Pour l'instant, render vue HTML dédiée au PDF
        view('invoices/pdf', compact('invoice', 'items', 'client'));
    }

    /** GET /invoices/export/csv — Export CSV pour Excel */
    public function exportCsv(): void
    {
        $invoices = $this->model->allWithClient();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="factures_' . date('Y-m-d') . '.csv"');

        $out = fopen('php://output', 'w');
        // BOM pour Excel (UTF-8)
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Numéro', 'Client', 'Date', 'Échéance', 'Statut', 'Sous-total', 'TVA', 'Total'], ';');

        foreach ($invoices as $inv) {
            fputcsv($out, [
                $inv['number'],
                $inv['client_name'],
                formatDate($inv['issue_date']),
                formatDate($inv['due_date']),
                $inv['status'],
                $inv['subtotal'],
                $inv['tax_amount'],
                $inv['total'],
            ], ';');
        }

        fclose($out);
        exit;
    }

    // ---- Private helpers ----

    private function findOrAbort(int $id): array
    {
        $invoice = $this->model->find($id);
        if (!$invoice) {
            http_response_code(404);
            exit('Facture introuvable.');
        }
        return $invoice;
    }

    private function sanitize(array $post): array
    {
        $subtotal = (float) ($post['subtotal'] ?? 0);
        $taxRate  = (float) ($post['tax_rate'] ?? 20);
        $taxAmt   = round($subtotal * $taxRate / 100, 2);

        return [
            'client_id'  => (int)   ($post['client_id'] ?? 0),
            'status'     => in_array($post['status'] ?? '', ['draft','pending','paid','overdue'])
                                ? $post['status'] : 'draft',
            'issue_date' => $post['issue_date'] ?? date('Y-m-d'),
            'due_date'   => $post['due_date']   ?? date('Y-m-d', strtotime('+30 days')),
            'subtotal'   => $subtotal,
            'tax_rate'   => $taxRate,
            'tax_amount' => $taxAmt,
            'total'      => round($subtotal + $taxAmt, 2),
            'notes'      => trim($post['notes'] ?? ''),
        ];
    }

    private function saveItems(int $invoiceId, array $items): void
    {
        foreach ($items as $pos => $item) {
            if (empty(trim($item['description'] ?? ''))) {
                continue;
            }

            $qty      = (float) ($item['quantity'] ?? 1);
            $price    = (float) ($item['unit_price'] ?? 0);
            $lineTotal = round($qty * $price, 2);

            $this->model->db->query(
                'INSERT INTO invoice_items (invoice_id, position, description, quantity, unit_price, total)
                 VALUES (:inv, :pos, :desc, :qty, :price, :total)',
                [
                    'inv'   => $invoiceId,
                    'pos'   => $pos,
                    'desc'  => trim($item['description']),
                    'qty'   => $qty,
                    'price' => $price,
                    'total' => $lineTotal,
                ]
            );
        }
    }
}
