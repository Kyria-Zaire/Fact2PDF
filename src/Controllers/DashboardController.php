<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Models\Invoice;
use App\Models\Client;
use App\Models\Project;

class DashboardController
{
    /** GET /dashboard */
    public function index(): void
    {
        $invoiceModel = new Invoice();
        $clientModel  = new Client();
        $projectModel = new Project();
        $db           = Database::getInstance();

        $stats        = $invoiceModel->stats();
        $recent       = array_slice($invoiceModel->allWithClient(), 0, 5);
        $clientCount  = count($clientModel->all());
        $projectStats = $projectModel->stats();

        // CA mensuel sur 12 mois pour le graphique bar (Chart.js)
        $caMonthly = $db->fetchAll(
            'SELECT DATE_FORMAT(issue_date, "%Y-%m") AS month,
                    SUM(total) AS revenue
             FROM invoices
             WHERE issue_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
               AND status = "paid"
             GROUP BY month
             ORDER BY month ASC'
        );

        // Répartition des statuts de factures pour le graphique pie
        $statusBreakdown = $db->fetchAll(
            'SELECT status, COUNT(*) AS count, SUM(total) AS total
             FROM invoices
             GROUP BY status'
        );

        view('dashboard/index', compact(
            'stats', 'recent', 'clientCount',
            'projectStats', 'caMonthly', 'statusBreakdown'
        ));
    }
}
