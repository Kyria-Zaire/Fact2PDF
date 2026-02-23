<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Invoice;
use App\Models\Client;
use App\Services\PdfService;
use App\Services\MailerService;
use App\Services\SpreadsheetService;

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
        view('invoices/form', ['invoice' => null, 'clients' => $clients, 'action' => '/invoices', 'items' => []]);
    }

    /**
     * POST /invoices — Crée la facture, enregistre les lignes, envoie email notif.
     */
    public function store(): void
    {
        verifyCsrf();

        $data = $this->sanitize($_POST);
        $this->validateOrFail($data);

        $data['number']     = $this->model->generateNumber();
        $data['created_by'] = $_SESSION['user_id'];

        $invoiceId = $this->model->create($data);
        $this->saveItems($invoiceId, $_POST['items'] ?? []);

        // Notification email (non bloquante : on log l'erreur sans crasher)
        $this->sendEmailNotification($invoiceId, $data);

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
            'action'  => "/invoices/{$id}",
        ]);
    }

    /** POST /invoices/{id} */
    public function update(string $id): void
    {
        verifyCsrf();
        $this->findOrAbort((int) $id);

        $data = $this->sanitize($_POST);
        $this->validateOrFail($data);

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

    /**
     * GET /invoices/{id}/pdf
     * Génère et télécharge le PDF via TCPDF.
     */
    public function pdf(string $id): void
    {
        $invoice = $this->findOrAbort((int) $id);
        $items   = $this->model->items((int) $id);
        $client  = $this->clientModel->find((int) $invoice['client_id']);

        if (!$client) {
            http_response_code(404);
            exit('Client introuvable.');
        }

        try {
            $pdfService = new PdfService();
            $pdfService->generateInvoice($invoice, $items, $client, download: true);
        } catch (\Exception $e) {
            logMessage('error', "PDF generation failed for invoice #{$id}: " . $e->getMessage());
            http_response_code(500);
            exit('Erreur génération PDF.');
        }
    }

    /**
     * GET /invoices/export/xlsx — Export Excel via PHPSpreadsheet.
     * GET /invoices/export/csv  — Export CSV compatible Excel.
     */
    public function exportXlsx(): void
    {
        $invoices = $this->model->allWithClient();
        (new SpreadsheetService())->exportInvoices($invoices, 'xlsx');
    }

    public function exportCsv(): void
    {
        $invoices = $this->model->allWithClient();
        (new SpreadsheetService())->exportInvoices($invoices, 'csv');
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

    /**
     * Nettoie et calcule les montants depuis le POST.
     */
    private function sanitize(array $post): array
    {
        $subtotal = round((float) filter_var($post['subtotal'] ?? 0, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION), 2);
        $taxRate  = round((float) filter_var($post['tax_rate'] ?? 20, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION), 2);
        $taxAmt   = round($subtotal * $taxRate / 100, 2);

        $allowedStatuses = ['draft', 'pending', 'paid', 'overdue'];

        return [
            'client_id'  => (int) filter_var($post['client_id'] ?? 0, FILTER_SANITIZE_NUMBER_INT),
            'status'     => in_array($post['status'] ?? '', $allowedStatuses, true) ? $post['status'] : 'draft',
            'issue_date' => $this->sanitizeDate($post['issue_date'] ?? ''),
            'due_date'   => $this->sanitizeDate($post['due_date'] ?? '', '+30 days'),
            'subtotal'   => $subtotal,
            'tax_rate'   => $taxRate,
            'tax_amount' => $taxAmt,
            'total'      => round($subtotal + $taxAmt, 2),
            'notes'      => htmlspecialchars(trim($post['notes'] ?? ''), ENT_QUOTES, 'UTF-8'),
        ];
    }

    /**
     * Valide les données obligatoires. Redirige avec message si invalide.
     */
    private function validateOrFail(array $data): void
    {
        $errors = [];

        if ($data['client_id'] <= 0) {
            $errors[] = 'Veuillez sélectionner un client.';
        } elseif (!$this->clientModel->find($data['client_id'])) {
            $errors[] = 'Client invalide.';
        }

        if ($data['subtotal'] < 0) {
            $errors[] = 'Le montant ne peut pas être négatif.';
        }

        if (!empty($errors)) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => implode(' ', $errors)];
            redirect('/invoices/create');
        }
    }

    private function sanitizeDate(string $date, string $fallback = 'now'): string
    {
        $ts = strtotime($date);
        return $ts ? date('Y-m-d', $ts) : date('Y-m-d', strtotime($fallback));
    }

    /**
     * Insère les lignes de facture en base.
     */
    private function saveItems(int $invoiceId, array $items): void
    {
        foreach ($items as $pos => $item) {
            $desc = trim($item['description'] ?? '');
            if ($desc === '') {
                continue;
            }

            $qty   = round((float) filter_var($item['quantity']  ?? 1, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION), 2);
            $price = round((float) filter_var($item['unit_price'] ?? 0, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION), 2);

            $this->model->db->query(
                'INSERT INTO invoice_items (invoice_id, position, description, quantity, unit_price, total)
                 VALUES (:inv, :pos, :desc, :qty, :price, :total)',
                [
                    'inv'   => $invoiceId,
                    'pos'   => (int) $pos,
                    'desc'  => htmlspecialchars($desc, ENT_QUOTES, 'UTF-8'),
                    'qty'   => $qty,
                    'price' => $price,
                    'total' => round($qty * $price, 2),
                ]
            );
        }
    }

    /**
     * Envoie la notification email. Silencieuse si erreur (log uniquement).
     */
    private function sendEmailNotification(int $invoiceId, array $invoiceData): void
    {
        try {
            $invoice = $this->model->find($invoiceId);
            $client  = $this->clientModel->find($invoiceData['client_id']);

            if ($invoice && $client && !empty($client['email'])) {
                (new MailerService())->sendInvoiceNotification($invoice, $client);
            }
        } catch (\Exception $e) {
            logMessage('error', "Email notif failed for invoice #{$invoiceId}: " . $e->getMessage());
        }
    }
}
