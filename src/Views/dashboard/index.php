<?php $pageTitle = 'Dashboard'; ?>
<?php ob_start(); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4">Dashboard</h1>
    <a href="/invoices/create" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Nouvelle facture
    </a>
</div>

<!-- Stats cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card text-bg-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title opacity-75">Clients</h6>
                        <p class="fs-3 fw-bold mb-0"><?= e($clientCount) ?></p>
                    </div>
                    <i class="bi bi-people fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-bg-success">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title opacity-75">CA Encaissé</h6>
                        <p class="fs-3 fw-bold mb-0"><?= formatMoney((float)($stats['paid_revenue'] ?? 0)) ?></p>
                    </div>
                    <i class="bi bi-cash-stack fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-bg-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title opacity-75">En attente</h6>
                        <p class="fs-3 fw-bold mb-0"><?= e($stats['pending_count'] ?? 0) ?></p>
                    </div>
                    <i class="bi bi-hourglass-split fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-bg-danger">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title opacity-75">En retard</h6>
                        <p class="fs-3 fw-bold mb-0"><?= e($stats['overdue_count'] ?? 0) ?></p>
                    </div>
                    <i class="bi bi-exclamation-triangle fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Factures récentes -->
<div class="card">
    <div class="card-header d-flex justify-content-between">
        <span>Factures récentes</span>
        <a href="/invoices" class="btn btn-sm btn-outline-secondary">Voir tout</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Numéro</th>
                    <th>Client</th>
                    <th>Date</th>
                    <th>Montant</th>
                    <th>Statut</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($recent as $inv): ?>
                <tr>
                    <td><code><?= e($inv['number']) ?></code></td>
                    <td><?= e($inv['client_name']) ?></td>
                    <td><?= formatDate($inv['issue_date']) ?></td>
                    <td><?= formatMoney((float)$inv['total']) ?></td>
                    <td>
                        <?php
                        $badges = ['draft'=>'secondary','pending'=>'warning','paid'=>'success','overdue'=>'danger'];
                        $badge  = $badges[$inv['status']] ?? 'secondary';
                        ?>
                        <span class="badge text-bg-<?= $badge ?>"><?= e($inv['status']) ?></span>
                    </td>
                    <td>
                        <a href="/invoices/<?= $inv['id'] ?>" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/src/Views/layouts/main.php'; ?>
