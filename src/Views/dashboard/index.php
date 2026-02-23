<?php $pageTitle = 'Dashboard'; ?>
<?php ob_start(); ?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h1 class="h4 mb-0">Dashboard</h1>
    <div class="d-flex gap-2 flex-grow-1 flex-md-grow-0 justify-content-md-end">
        <a href="/invoices/create" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg"></i> Nouvelle facture
        </a>
        <a href="/projects/create" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-kanban"></i> Nouveau projet
        </a>
    </div>
</div>

<!-- ---- KPI Cards ---- -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card text-bg-primary h-100">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="small opacity-75">Clients</div>
                    <div class="fs-3 fw-bold"><?= e($clientCount) ?></div>
                </div>
                <i class="bi bi-people fs-1 opacity-50"></i>
            </div>
            <a href="/clients" class="card-footer text-white text-decoration-none small opacity-75">
                Voir les clients <i class="bi bi-arrow-right"></i>
            </a>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-bg-success h-100">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="small opacity-75">CA encaissé</div>
                    <div class="fs-3 fw-bold"><?= formatMoney((float)($stats['paid_revenue'] ?? 0)) ?></div>
                </div>
                <i class="bi bi-cash-stack fs-1 opacity-50"></i>
            </div>
            <a href="/invoices" class="card-footer text-white text-decoration-none small opacity-75">
                Voir les factures <i class="bi bi-arrow-right"></i>
            </a>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-bg-warning h-100">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="small opacity-75">En attente</div>
                    <div class="fs-3 fw-bold"><?= e($stats['pending_count'] ?? 0) ?></div>
                </div>
                <i class="bi bi-hourglass-split fs-1 opacity-50"></i>
            </div>
            <a href="/invoices" class="card-footer text-dark text-decoration-none small opacity-75">
                Voir en attente <i class="bi bi-arrow-right"></i>
            </a>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-bg-danger h-100">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="small opacity-75">Projets en retard</div>
                    <div class="fs-3 fw-bold"><?= e($projectStats['late'] ?? 0) ?></div>
                </div>
                <i class="bi bi-exclamation-triangle fs-1 opacity-50"></i>
            </div>
            <a href="/projects" class="card-footer text-white text-decoration-none small opacity-75">
                Voir les projets <i class="bi bi-arrow-right"></i>
            </a>
        </div>
    </div>
</div>

<!-- ---- Charts (lazy-loaded via IntersectionObserver) ---- -->
<div class="row g-3 mb-4">
    <div class="col-md-8">
        <div class="card h-100">
            <div class="card-header">
                CA mensuel
                <span class="text-muted small ms-1">(12 derniers mois — payées)</span>
            </div>
            <div class="card-body">
                <canvas id="chartCA" height="120" data-chart="bar"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header">Statuts factures</div>
            <div class="card-body d-flex align-items-center justify-content-center" id="chartStatusContainer">
                <canvas id="chartStatus" style="max-height:220px"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- ---- Récent + Projets ---- -->
<div class="row g-3">
    <div class="col-md-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <span>Factures récentes</span>
                <a href="/invoices" class="btn btn-sm btn-outline-secondary">Tout voir</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>N°</th><th>Client</th><th class="col-hide-sm">Date</th><th>Total</th><th>Statut</th><th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                        $badges = ['draft'=>'secondary','pending'=>'warning text-dark','paid'=>'success','overdue'=>'danger'];
                        $invoiceStatusLabels = \App\Models\Invoice::STATUS_LABELS;
                        foreach ($recent as $inv):
                    ?>
                        <tr>
                            <td><code class="small"><?= e($inv['number']) ?></code></td>
                            <td><?= e($inv['client_name']) ?></td>
                            <td class="text-muted small col-hide-sm"><?= formatDate($inv['issue_date']) ?></td>
                            <td class="fw-semibold"><?= formatMoney((float)$inv['total']) ?></td>
                            <td>
                                <span class="badge text-bg-<?= $badges[$inv['status']] ?? 'secondary' ?>">
                                    <?= e($invoiceStatusLabels[$inv['status']] ?? $inv['status']) ?>
                                </span>
                            </td>
                            <td>
                                <a href="/invoices/<?= $inv['id'] ?>/pdf" class="btn btn-sm btn-outline-secondary" title="PDF">
                                    <i class="bi bi-file-pdf"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-5">
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <span>Projets actifs</span>
                <a href="/projects" class="btn btn-sm btn-outline-secondary">Tout voir</a>
            </div>
            <?php
            $statColors = [
                'todo'        => 'secondary',
                'in_progress' => 'primary',
                'review'      => 'warning',
                'done'        => 'success',
                'archived'    => 'light',
            ];
            ?>
            <ul class="list-group list-group-flush">
            <?php foreach (array_slice($projects ?? [], 0, 5) as $p):
                $pct   = $p['progress'] ?? 0;
                $color = $statColors[$p['status']] ?? 'secondary';
            ?>
                <li class="list-group-item px-3 py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <a href="/projects/<?= $p['id'] ?>" class="text-decoration-none fw-semibold small">
                            <?= e($p['name']) ?>
                        </a>
                        <span class="badge text-bg-<?= $color ?>"><?= e($p['status']) ?></span>
                    </div>
                    <div class="text-muted" style="font-size:.78rem"><?= e($p['client_name']) ?></div>
                    <div class="progress mt-1" style="height:4px" role="progressbar" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar bg-<?= $color ?>" style="width:<?= $pct ?>%"></div>
                    </div>
                </li>
            <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<?php
$statusColorMap = ['draft' => '#6c757d', 'pending' => '#ffc107', 'paid' => '#198754', 'overdue' => '#dc3545'];
$dashStatusColors = [];
foreach ($statusBreakdown as $r) {
    $dashStatusColors[] = $statusColorMap[$r['status']] ?? '#adb5bd';
}
?>
<!-- Données JSON injectées côté serveur → lues par app.js -->
<script id="dashData" type="application/json">
{
    "ca": {
        "labels": <?= json_encode(array_column($caMonthly, 'month'), JSON_UNESCAPED_UNICODE) ?>,
        "data":   <?= json_encode(array_map(fn($r) => (float)$r['revenue'], $caMonthly)) ?>
    },
    "status": {
        "labels": <?= json_encode(array_map(fn($r) => \App\Models\Invoice::STATUS_LABELS[$r['status']] ?? $r['status'], $statusBreakdown), JSON_UNESCAPED_UNICODE) ?>,
        "data":   <?= json_encode(array_map(fn($r) => (int)$r['count'], $statusBreakdown)) ?>,
        "colors": <?= json_encode($dashStatusColors) ?>
    }
}
</script>
<!-- Graphique Statuts factures : nouveau canvas à chaque init pour éviter "Canvas is already in use". -->
<script>
(function() {
    function initStatusChart() {
        if (typeof Chart === 'undefined') { setTimeout(initStatusChart, 50); return; }
        var container = document.getElementById('chartStatusContainer');
        var dataEl = document.getElementById('dashData');
        if (!container || !dataEl) return;
        var dash = JSON.parse(dataEl.textContent);
        var status = dash.status;
        if (!status || !status.labels || !status.labels.length) return;
        var colors = Array.isArray(status.colors) && status.colors.length === status.labels.length
            ? status.colors
            : ['#6c757d','#ffc107','#198754','#dc3545'].slice(0, status.labels.length);
        container.innerHTML = '';
        var canvas = document.createElement('canvas');
        canvas.id = 'chartStatus';
        canvas.setAttribute('style', 'max-height:220px');
        container.appendChild(canvas);
        new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: status.labels,
                datasets: [{ data: status.data, backgroundColor: colors, borderWidth: 2 }]
            },
            options: {
                responsive: true, cutout: '65%',
                plugins: { legend: { position: 'bottom', labels: { padding: 12, font: { size: 11 } } } }
            }
        });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initStatusChart);
    else initStatusChart();
})();
</script>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/src/Views/layouts/main.php'; ?>
