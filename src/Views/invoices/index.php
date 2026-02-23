<?php $pageTitle = 'Factures'; ?>
<?php ob_start(); ?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h1 class="h4 mb-0">
        Factures <span class="badge text-bg-secondary ms-1"><?= count($invoices) ?></span>
    </h1>
    <div class="page-toolbar d-flex gap-2 flex-wrap flex-grow-1 flex-md-grow-0 justify-content-md-end">
        <!-- Filtre statut -->
        <select id="filterStatus" class="form-select form-select-sm" style="width:140px">
            <option value="">Tous statuts</option>
            <option value="draft">Brouillon</option>
            <option value="pending">En attente</option>
            <option value="paid">Payée</option>
            <option value="overdue">En retard</option>
        </select>
        <!-- Export -->
        <div class="dropdown">
            <button class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                <i class="bi bi-download"></i> Export
            </button>
            <ul class="dropdown-menu">
                <li>
                    <a class="dropdown-item" href="/invoices/export/xlsx">
                        <i class="bi bi-file-earmark-excel text-success"></i> Excel (.xlsx)
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="/invoices/export/csv">
                        <i class="bi bi-file-earmark-text text-muted"></i> CSV
                    </a>
                </li>
            </ul>
        </div>
        <?php if (in_array($_SESSION['role'] ?? '', ['admin','user'])): ?>
        <a href="/invoices/create" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg"></i> Nouvelle
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- ---- Table factures ---- -->
<div class="card">
    <div class="table-responsive table-invoices">
        <table class="table table-hover align-middle mb-0" id="invoicesTable">
            <thead class="table-light">
                <tr>
                    <th>Numéro</th>
                    <th class="col-hide-xs">Client</th>
                    <th class="col-hide-sm">Date</th>
                    <th class="col-hide-xs">Échéance</th>
                    <th class="text-end">Total TTC</th>
                    <th>Statut</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php
                $badges = ['draft'=>'secondary','pending'=>'warning text-dark','paid'=>'success','overdue'=>'danger'];
                $statusLabels = \App\Models\Invoice::STATUS_LABELS;
                foreach ($invoices as $inv):
                $isLate = $inv['status'] === 'overdue';
            ?>
                <tr class="invoice-row" data-status="<?= e($inv['status']) ?>" <?= $isLate ? 'class="table-danger"' : '' ?>>
                    <td>
                        <code class="small"><?= e($inv['number']) ?></code>
                        <div class="text-muted x-small d-block d-sm-none mt-0"><?= e($inv['client_name']) ?></div>
                    </td>
                    <td class="d-none d-sm-table-cell">
                        <a href="/clients/<?= $inv['client_id'] ?>" class="text-decoration-none">
                            <?= e($inv['client_name']) ?>
                        </a>
                    </td>
                    <td class="text-muted small col-hide-sm"><?= formatDate($inv['issue_date']) ?></td>
                    <td class="text-muted small col-hide-xs <?= ($inv['status'] === 'overdue') ? 'text-danger fw-semibold' : '' ?>">
                        <?= formatDate($inv['due_date']) ?>
                    </td>
                    <td class="text-end fw-semibold"><?= formatMoney((float)$inv['total']) ?></td>
                    <td>
                        <span class="badge text-bg-<?= $badges[$inv['status']] ?? 'secondary' ?>">
                            <?= e($statusLabels[$inv['status']] ?? $inv['status']) ?>
                        </span>
                    </td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm">
                            <a href="/invoices/<?= $inv['id'] ?>" class="btn btn-outline-secondary" title="Voir">
                                <i class="bi bi-eye"></i>
                            </a>
                            <!-- PDF preview dans panneau latéral -->
                            <button class="btn btn-outline-primary btn-pdf-preview"
                                    title="Aperçu PDF"
                                    data-id="<?= $inv['id'] ?>"
                                    data-num="<?= e($inv['number']) ?>">
                                <i class="bi bi-file-earmark-pdf"></i>
                            </button>
                            <a href="/invoices/<?= $inv['id'] ?>/pdf" class="btn btn-outline-dark" title="Télécharger PDF" download>
                                <i class="bi bi-download"></i>
                            </a>
                            <?php if (in_array($_SESSION['role'] ?? '', ['admin','user'])): ?>
                            <a href="/invoices/<?= $inv['id'] ?>/edit" class="btn btn-outline-secondary" title="Modifier">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <?php endif; ?>
                            <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
                            <button class="btn btn-outline-danger btn-delete-invoice"
                                    title="Supprimer"
                                    data-id="<?= $inv['id'] ?>"
                                    data-num="<?= e($inv['number']) ?>">
                                <i class="bi bi-trash"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Formulaire caché pour delete (soumis par JS) -->
<form id="formDeleteInvoice" method="POST" style="display:none">
    <input type="hidden" name="_csrf" value="<?= csrfToken() ?>">
</form>

<!-- ---- Panneau PDF preview (offcanvas Bootstrap) ---- -->
<div class="offcanvas offcanvas-end" style="width:55%;max-width:100%" tabindex="-1" id="pdfPreviewCanvas">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title">Aperçu — <span id="pdfPreviewNum"></span></h5>
        <div class="ms-auto d-flex gap-2">
            <a id="pdfDownloadBtn" href="#" class="btn btn-sm btn-primary" download>
                <i class="bi bi-download"></i> Télécharger
            </a>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
    </div>
    <div class="offcanvas-body p-0 d-flex flex-column" style="min-height:80vh;">
        <!-- Iframe pour afficher le PDF inline (?inline=1) -->
        <iframe id="pdfPreviewFrame" src="" style="width:100%;flex:1;min-height:75vh;border:none" title="Aperçu PDF"></iframe>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/src/Views/layouts/main.php'; ?>
