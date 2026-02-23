<?php $pageTitle = 'Factures'; ?>
<?php ob_start(); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 mb-0">
        Factures <span class="badge text-bg-secondary ms-1"><?= count($invoices) ?></span>
    </h1>
    <div class="d-flex gap-2 flex-wrap">
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
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="invoicesTable">
            <thead class="table-light">
                <tr>
                    <th>Numéro</th>
                    <th>Client</th>
                    <th>Date</th>
                    <th>Échéance</th>
                    <th class="text-end">Total TTC</th>
                    <th>Statut</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($invoices as $inv):
                $badges   = ['draft'=>'secondary','pending'=>'warning text-dark','paid'=>'success','overdue'=>'danger'];
                $isLate   = $inv['status'] === 'overdue';
            ?>
                <tr class="invoice-row" data-status="<?= e($inv['status']) ?>" <?= $isLate ? 'class="table-danger"' : '' ?>>
                    <td>
                        <code class="small"><?= e($inv['number']) ?></code>
                    </td>
                    <td>
                        <a href="/clients/<?= $inv['client_id'] ?>" class="text-decoration-none">
                            <?= e($inv['client_name']) ?>
                        </a>
                    </td>
                    <td class="text-muted small"><?= formatDate($inv['issue_date']) ?></td>
                    <td class="text-muted small <?= ($inv['status'] === 'overdue') ? 'text-danger fw-semibold' : '' ?>">
                        <?= formatDate($inv['due_date']) ?>
                    </td>
                    <td class="text-end fw-semibold"><?= formatMoney((float)$inv['total']) ?></td>
                    <td>
                        <span class="badge text-bg-<?= $badges[$inv['status']] ?? 'secondary' ?>">
                            <?= e($inv['status']) ?>
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
<div class="offcanvas offcanvas-end" style="width:55%" tabindex="-1" id="pdfPreviewCanvas">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title">Aperçu — <span id="pdfPreviewNum"></span></h5>
        <div class="ms-auto d-flex gap-2">
            <a id="pdfDownloadBtn" href="#" class="btn btn-sm btn-primary" download>
                <i class="bi bi-download"></i> Télécharger
            </a>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
    </div>
    <div class="offcanvas-body p-0">
        <!-- Iframe pour afficher le PDF inline -->
        <iframe id="pdfPreviewFrame" src="" style="width:100%;height:100%;border:none"></iframe>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/src/Views/layouts/main.php'; ?>
