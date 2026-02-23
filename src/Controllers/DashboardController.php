<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Invoice;
use App\Models\Client;

class DashboardController
{
    /** GET /dashboard */
    public function index(): void
    {
        $invoiceModel = new Invoice();
        $clientModel  = new Client();

        $stats   = $invoiceModel->stats();
        $recent  = $invoiceModel->allWithClient();
        $recent  = array_slice($recent, 0, 5); // 5 dernières

        $clientCount = count($clientModel->all());

        view('dashboard/index', compact('stats', 'recent', 'clientCount'));
    }
}
