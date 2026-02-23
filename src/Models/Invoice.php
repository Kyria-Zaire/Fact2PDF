<?php

declare(strict_types=1);

namespace App\Models;

class Invoice extends BaseModel
{
    protected string $table = 'invoices';

    protected array $fillable = [
        'client_id', 'number', 'status', 'issue_date', 'due_date',
        'subtotal', 'tax_rate', 'tax_amount', 'total', 'notes', 'created_by'
    ];

    /**
     * Retourne les factures avec le nom du client.
     */
    public function allWithClient(): array
    {
        return $this->db->fetchAll(
            'SELECT i.*, c.name AS client_name
             FROM invoices i
             JOIN clients c ON c.id = i.client_id
             ORDER BY i.issue_date DESC'
        );
    }

    /**
     * Retourne les lignes d'une facture.
     */
    public function items(int $invoiceId): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM invoice_items WHERE invoice_id = :id ORDER BY position ASC',
            ['id' => $invoiceId]
        );
    }

    /**
     * Retourne les factures d'un client.
     */
    public function byClient(int $clientId): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM invoices WHERE client_id = :id ORDER BY issue_date DESC',
            ['id' => $clientId]
        );
    }

    /**
     * Génère un numéro de facture unique (ex: FACT-2026-0042).
     */
    public function generateNumber(): string
    {
        $year = date('Y');
        $row  = $this->db->fetchOne(
            'SELECT COUNT(*) AS cnt FROM invoices WHERE YEAR(issue_date) = :year',
            ['year' => $year]
        );
        $seq = (int)($row['cnt'] ?? 0) + 1;
        return sprintf('FACT-%d-%04d', $year, $seq);
    }

    /**
     * Statistiques pour le dashboard.
     */
    public function stats(): array
    {
        return $this->db->fetchOne(
            'SELECT
                COUNT(*)                                         AS total_count,
                SUM(total)                                       AS total_revenue,
                SUM(CASE WHEN status = "paid"    THEN total END) AS paid_revenue,
                SUM(CASE WHEN status = "pending" THEN 1 END)     AS pending_count,
                SUM(CASE WHEN status = "overdue" THEN 1 END)     AS overdue_count
             FROM invoices'
        );
    }
}
