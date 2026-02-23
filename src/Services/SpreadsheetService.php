<?php
/**
 * SpreadsheetService — Export Excel/CSV via PHPSpreadsheet
 *
 * Usage :
 *   $svc = new SpreadsheetService();
 *   $svc->exportInvoices($invoices); // → envoie le fichier .xlsx au navigateur
 */

declare(strict_types=1);

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Border, Fill, Font};
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class SpreadsheetService
{
    /**
     * Génère et télécharge un fichier Excel (.xlsx) de synthèse des factures.
     *
     * @param array  $invoices Liste des factures (avec client_name)
     * @param string $format   'xlsx' ou 'csv'
     */
    public function exportInvoices(array $invoices, string $format = 'xlsx'): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Factures');

        $this->setMetadata($spreadsheet);
        $this->buildHeader($sheet);
        $this->fillData($sheet, $invoices);
        $this->applyStyles($sheet, count($invoices));

        $filename = 'factures_' . date('Y-m-d');

        match ($format) {
            'csv'  => $this->downloadCsv($spreadsheet, $filename),
            default => $this->downloadXlsx($spreadsheet, $filename),
        };
    }

    // ---- Construction ----

    private function setMetadata(Spreadsheet $ss): void
    {
        $ss->getProperties()
           ->setCreator(env('APP_NAME', 'Fact2PDF'))
           ->setTitle('Synthèse Factures')
           ->setDescription('Export généré par Fact2PDF le ' . date('d/m/Y'));
    }

    private function buildHeader(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet): void
    {
        $headers = ['ID', 'Numéro', 'Client', 'Date émission', 'Échéance', 'Statut', 'Sous-total HT', 'TVA', 'Total TTC'];

        foreach ($headers as $col => $label) {
            $sheet->getCell([$col + 1, 1])->setValue($label);
        }
    }

    private function fillData(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, array $invoices): void
    {
        $statuses = [
            'draft'   => 'Brouillon',
            'pending' => 'En attente',
            'paid'    => 'Payée',
            'overdue' => 'En retard',
        ];

        foreach ($invoices as $row => $inv) {
            $r = $row + 2; // Ligne 1 = en-tête

            $sheet->getCell([1, $r])->setValue((int) $inv['id']);
            $sheet->getCell([2, $r])->setValueExplicit($inv['number'], DataType::TYPE_STRING);
            $sheet->getCell([3, $r])->setValue($inv['client_name']);
            $sheet->getCell([4, $r])->setValue($inv['issue_date']);
            $sheet->getCell([5, $r])->setValue($inv['due_date']);
            $sheet->getCell([6, $r])->setValue($statuses[$inv['status']] ?? $inv['status']);
            $sheet->getCell([7, $r])->setValue((float) $inv['subtotal']);
            $sheet->getCell([8, $r])->setValue((float) $inv['tax_amount']);
            $sheet->getCell([9, $r])->setValue((float) $inv['total']);
        }

        // Ligne totaux
        $lastRow = count($invoices) + 2;
        $sheet->getCell([3, $lastRow])->setValue('TOTAL');
        $sheet->getCell([7, $lastRow])->setValue("=SUM(G2:G" . ($lastRow - 1) . ")");
        $sheet->getCell([8, $lastRow])->setValue("=SUM(H2:H" . ($lastRow - 1) . ")");
        $sheet->getCell([9, $lastRow])->setValue("=SUM(I2:I" . ($lastRow - 1) . ")");
    }

    private function applyStyles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, int $rowCount): void
    {
        // En-tête : fond bleu, texte blanc, gras
        $headerRange = 'A1:I1';
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF0D6EFD']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Colonnes monétaires : format €
        $moneyFormat = '#,##0.00 "€"';
        foreach (['G', 'H', 'I'] as $col) {
            $sheet->getStyle("{$col}2:{$col}" . ($rowCount + 2))
                  ->getNumberFormat()->setFormatCode($moneyFormat);
        }

        // Colonnes dates
        foreach (['D', 'E'] as $col) {
            $sheet->getStyle("{$col}2:{$col}" . ($rowCount + 1))
                  ->getNumberFormat()->setFormatCode('DD/MM/YYYY');
        }

        // Largeurs automatiques
        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Ligne de totaux : gras + bordure supérieure
        $totalRow = $rowCount + 2;
        $sheet->getStyle("A{$totalRow}:I{$totalRow}")->applyFromArray([
            'font'    => ['bold' => true],
            'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM]],
        ]);

        // Filtre automatique Excel
        $sheet->setAutoFilter('A1:I1');

        // Figer la première ligne
        $sheet->freezePane('A2');
    }

    // ---- Téléchargement ----

    private function downloadXlsx(Spreadsheet $ss, string $filename): void
    {
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"{$filename}.xlsx\"");
        header('Cache-Control: max-age=0');

        (new Xlsx($ss))->save('php://output');
        exit;
    }

    private function downloadCsv(Spreadsheet $ss, string $filename): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$filename}.csv\"");

        $writer = new Csv($ss);
        $writer->setDelimiter(';');
        $writer->setEnclosure('"');
        $writer->setUseBOM(true); // BOM pour Excel FR
        $writer->save('php://output');
        exit;
    }
}
