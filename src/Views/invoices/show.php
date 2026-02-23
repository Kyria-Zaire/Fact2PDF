<?php
$pageTitle = 'Facture ' . e($invoice['number'] ?? '');
$statusBadges = ['draft' => 'secondary', 'pending' => 'warning text-dark', 'paid' => 'success', 'overdue' => 'danger'];
$statusLabels = \App\Models\Invoice::STATUS_LABELS;
?>
<?php ob_start(); ?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="/invoices">Factures</a></li>
                <li class="breadcrumb-item active"><?= e($invoice['number']) ?></li>
            </ol>
        </nav>
        <h1 class="h4 mb-0">Facture <code><?= e($invoice['number']) ?></code></h1>
        <div class="text-muted small mt-1">
            Client : <a href="/clients/<?= (int)$invoice['client_id'] ?>"><?= e($client['name'] ?? '—') ?></a>
            — Émise le <?= formatDate($invoice['issue_date']) ?> — Échéance <?= formatDate($invoice['due_date']) ?>
        </div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="/invoices/<?= (int)$invoice['id'] ?>/pdf" class="btn btn-primary btn-sm" download>
            <i class="bi bi-file-pdf"></i> Télécharger PDF
        </a>
        <?php if (in_array($_SESSION['role'] ?? '', ['admin', 'user'])): ?>
        <a href="/invoices/<?= (int)$invoice['id'] ?>/edit" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-pencil"></i> Modifier
        </a>
        <?php endif; ?>
        <a href="/invoices" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Retour</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Détail</span>
                <span class="badge text-bg-<?= $statusBadges[$invoice['status']] ?? 'secondary' ?>"><?= e($invoice['status']) ?></span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Description</th>
                            <th class="text-center">Quantité</th>
                            <th class="text-end">Prix unitaire</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= e($item['description']) ?></td>
                            <td class="text-center"><?= e($item['quantity']) ?></td>
                            <td class="text-end"><?= number_format((float)$item['unit_price'], 2, ',', ' ') ?> €</td>
                            <td class="text-end fw-semibold"><?= formatMoney((float)$item['total']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($items)): ?>
                        <tr><td colspan="4" class="text-center text-muted py-3">Aucune ligne.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-transparent">
                <div class="row text-end">
                    <div class="col-6 col-md-8 text-muted">Sous-total HT</div>
                    <div class="col-6 col-md-4"><?= formatMoney((float)$invoice['subtotal']) ?></div>
                    <div class="col-6 col-md-8 text-muted">TVA (<?= e($invoice['tax_rate']) ?> %)</div>
                    <div class="col-6 col-md-4"><?= formatMoney((float)$invoice['tax_amount']) ?></div>
                    <div class="col-6 col-md-8 fw-bold mt-1">Total TTC</div>
                    <div class="col-6 col-md-4 fw-bold mt-1"><?= formatMoney((float)$invoice['total']) ?></div>
                </div>
            </div>
        </div>
        <?php if (!empty($invoice['notes'])): ?>
        <div class="card mt-3">
            <div class="card-header">Notes</div>
            <div class="card-body"><?= nl2br(e($invoice['notes'])) ?></div>
        </div>
        <?php endif; ?>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">Informations</div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-muted">Client</span>
                    <a href="/clients/<?= (int)$invoice['client_id'] ?>"><?= e($client['name'] ?? '—') ?></a>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-muted">Statut</span>
                    <span class="badge text-bg-<?= $statusBadges[$invoice['status']] ?? 'secondary' ?>"><?= e($statusLabels[$invoice['status']] ?? $invoice['status']) ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-muted">Date d'émission</span>
                    <span><?= formatDate($invoice['issue_date']) ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-muted">Échéance</span>
                    <span class="<?= ($invoice['status'] === 'overdue') ? 'text-danger fw-semibold' : '' ?>"><?= formatDate($invoice['due_date']) ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-muted">Total TTC</span>
                    <span class="fw-bold"><?= formatMoney((float)$invoice['total']) ?></span>
                </li>
            </ul>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/src/Views/layouts/main.php'; ?>
