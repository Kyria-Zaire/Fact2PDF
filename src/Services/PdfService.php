<?php
/**
 * PdfService — Génération PDF de factures via TCPDF
 *
 * Usage :
 *   $pdf = new PdfService();
 *   $pdf->generateInvoice($invoice, $items, $client);
 *   // → envoie le PDF en téléchargement
 */

declare(strict_types=1);

namespace App\Services;

use TCPDF;

class PdfService
{
    private TCPDF $pdf;

    // Couleurs palette
    private array $colorPrimary  = [13,  110, 253]; // Bootstrap primary
    private array $colorDark     = [33,  37,  41];
    private array $colorGray     = [108, 117, 125];
    private array $colorLight    = [248, 249, 250];

    public function __construct()
    {
        $this->pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $this->configure();
    }

    // ---- Configuration TCPDF ----

    private function configure(): void
    {
        $this->pdf->SetCreator('Fact2PDF');
        $this->pdf->SetAuthor('Fact2PDF');
        $this->pdf->setPrintHeader(false);
        $this->pdf->setPrintFooter(false);
        $this->pdf->SetMargins(15, 15, 15);
        $this->pdf->SetAutoPageBreak(true, 15);
        $this->pdf->setFontSubsetting(false);
    }

    /**
     * Génère le PDF d'une facture et envoie au navigateur (téléchargement).
     *
     * @param array $invoice  Données facture
     * @param array $items    Lignes de facture
     * @param array $client   Données client
     * @param bool  $download true = forcer téléchargement, false = afficher inline
     */
    public function generateInvoice(
        array $invoice,
        array $items,
        array $client,
        bool  $download = true
    ): void {
        $this->pdf->AddPage();
        $this->pdf->SetFont('helvetica', '', 10);

        $this->renderHeader($invoice, $client);
        $this->renderClientBlock($client);
        $this->renderItemsTable($items);
        $this->renderTotals($invoice);
        $this->renderFooter($invoice);

        $filename = $invoice['number'] . '.pdf';
        $dest     = $download ? 'D' : 'I'; // D = Download, I = Inline

        $this->pdf->Output($filename, $dest);
    }

    // ---- Sections du template ----

    /** En-tête : logo émetteur + titre "FACTURE" + numéro/date */
    private function renderHeader(array $invoice, array $client): void
    {
        $appName = env('APP_NAME', 'Fact2PDF');

        // Bande de couleur en haut
        $this->pdf->SetFillColor(...$this->colorPrimary);
        $this->pdf->Rect(0, 0, 210, 28, 'F');

        // Nom société (blanc sur fond bleu)
        $this->pdf->SetTextColor(255, 255, 255);
        $this->pdf->SetFont('helvetica', 'B', 18);
        $this->pdf->SetXY(15, 8);
        $this->pdf->Cell(100, 10, $appName, 0, 0, 'L');

        // Titre FACTURE
        $this->pdf->SetFont('helvetica', 'B', 22);
        $this->pdf->SetXY(110, 5);
        $this->pdf->Cell(85, 10, 'FACTURE', 0, 0, 'R');

        // Numéro + date (sous la bande)
        $this->pdf->SetTextColor(...$this->colorGray);
        $this->pdf->SetFont('helvetica', '', 9);
        $this->pdf->SetXY(110, 16);
        $this->pdf->Cell(85, 5,
            'N° ' . $invoice['number'] . '  |  ' . formatDate($invoice['issue_date']),
            0, 0, 'R'
        );

        $this->pdf->SetTextColor(...$this->colorDark);
        $this->pdf->SetY(35);
    }

    /** Bloc client (adresse facturation) */
    private function renderClientBlock(array $client): void
    {
        // Logo client si disponible
        $logoPath = ROOT_PATH . '/public' . ($client['logo_path'] ?? '');
        if (!empty($client['logo_path']) && file_exists($logoPath)) {
            $this->pdf->Image($logoPath, 15, 35, 30, 15, '', '', '', true, 300);
        }

        // Adresse client
        $this->pdf->SetFillColor(...$this->colorLight);
        $this->pdf->SetDrawColor(220, 220, 220);
        $this->pdf->RoundedRect(115, 35, 80, 40, 2, '1111', 'DF');

        $this->pdf->SetFont('helvetica', 'B', 9);
        $this->pdf->SetXY(120, 38);
        $this->pdf->Cell(70, 5, 'FACTURÉ À', 0, 1, 'L');

        $this->pdf->SetFont('helvetica', 'B', 10);
        $this->pdf->SetX(120);
        $this->pdf->Cell(70, 5, $client['name'], 0, 1, 'L');

        $this->pdf->SetFont('helvetica', '', 9);
        $this->pdf->SetTextColor(...$this->colorGray);

        foreach ([
            $client['address']     ?? '',
            ($client['postal_code'] ?? '') . ' ' . ($client['city'] ?? ''),
            $client['email']       ?? '',
            $client['phone']       ?? '',
        ] as $line) {
            if (trim($line)) {
                $this->pdf->SetX(120);
                $this->pdf->Cell(70, 4, $line, 0, 1, 'L');
            }
        }

        $this->pdf->SetTextColor(...$this->colorDark);
        $this->pdf->SetY(80);
    }

    /** Tableau des lignes de facture */
    private function renderItemsTable(array $items): void
    {
        $colWidths = [85, 25, 30, 30]; // Description, Qté, Prix unit., Total

        // En-tête tableau
        $this->pdf->SetFillColor(...$this->colorPrimary);
        $this->pdf->SetTextColor(255, 255, 255);
        $this->pdf->SetFont('helvetica', 'B', 9);

        foreach (['DESCRIPTION', 'QTÉ', 'PRIX UNIT.', 'TOTAL'] as $i => $header) {
            $align = $i === 0 ? 'L' : 'R';
            $this->pdf->Cell($colWidths[$i], 8, $header, 0, 0, $align, true);
        }
        $this->pdf->Ln();

        // Lignes alternées
        $this->pdf->SetTextColor(...$this->colorDark);
        $this->pdf->SetFont('helvetica', '', 9);
        $even = false;

        foreach ($items as $item) {
            if ($even) {
                $this->pdf->SetFillColor(...$this->colorLight);
            } else {
                $this->pdf->SetFillColor(255, 255, 255);
            }
            $even = !$even;
            $fill = true;

            $this->pdf->Cell($colWidths[0], 7, $item['description'], 0, 0, 'L', $fill);
            $this->pdf->Cell($colWidths[1], 7, number_format((float)$item['quantity'], 2), 0, 0, 'R', $fill);
            $this->pdf->Cell($colWidths[2], 7, formatMoney((float)$item['unit_price']), 0, 0, 'R', $fill);
            $this->pdf->Cell($colWidths[3], 7, formatMoney((float)$item['total']), 0, 0, 'R', $fill);
            $this->pdf->Ln();
        }

        $this->pdf->Ln(4);
    }

    /** Bloc totaux (sous-total, TVA, total TTC) */
    private function renderTotals(array $invoice): void
    {
        $x = 125;
        $w = [45, 30];

        $this->pdf->SetFont('helvetica', '', 9);
        $this->pdf->SetTextColor(...$this->colorGray);

        // Sous-total
        $this->pdf->SetX($x);
        $this->pdf->Cell($w[0], 6, 'Sous-total HT', 0, 0, 'L');
        $this->pdf->Cell($w[1], 6, formatMoney((float)$invoice['subtotal']), 0, 1, 'R');

        // TVA
        $this->pdf->SetX($x);
        $this->pdf->Cell($w[0], 6, 'TVA (' . $invoice['tax_rate'] . '%)', 0, 0, 'L');
        $this->pdf->Cell($w[1], 6, formatMoney((float)$invoice['tax_amount']), 0, 1, 'R');

        // Séparateur
        $this->pdf->SetDrawColor(...$this->colorPrimary);
        $this->pdf->SetLineWidth(0.5);
        $this->pdf->Line($x, $this->pdf->GetY() + 1, 195, $this->pdf->GetY() + 1);
        $this->pdf->Ln(3);

        // Total TTC
        $this->pdf->SetFillColor(...$this->colorPrimary);
        $this->pdf->SetTextColor(255, 255, 255);
        $this->pdf->SetFont('helvetica', 'B', 11);
        $this->pdf->SetX($x);
        $this->pdf->Cell($w[0], 9, 'TOTAL TTC', 0, 0, 'L', true);
        $this->pdf->Cell($w[1], 9, formatMoney((float)$invoice['total']), 0, 1, 'R', true);

        $this->pdf->SetTextColor(...$this->colorDark);
        $this->pdf->Ln(8);
    }

    /** Pied de page : mentions légales / statut / date échéance */
    private function renderFooter(array $invoice): void
    {
        $this->pdf->SetFont('helvetica', 'I', 8);
        $this->pdf->SetTextColor(...$this->colorGray);

        $statuses = [
            'paid'    => 'Facture PAYÉE',
            'pending' => 'Paiement attendu avant le ' . formatDate($invoice['due_date']),
            'overdue' => 'FACTURE EN RETARD — Paiement dû depuis le ' . formatDate($invoice['due_date']),
            'draft'   => 'BROUILLON — Non officiel',
        ];

        $statusText = $statuses[$invoice['status']] ?? '';

        // Bande de pied
        $this->pdf->SetFillColor(...$this->colorLight);
        $this->pdf->Rect(0, 272, 210, 25, 'F');

        $this->pdf->SetXY(15, 274);
        $this->pdf->Cell(180, 5, $statusText, 0, 1, 'C');

        if (!empty($invoice['notes'])) {
            $this->pdf->SetXY(15, 280);
            $this->pdf->Cell(180, 5, 'Note : ' . $invoice['notes'], 0, 1, 'C');
        }
    }
}
