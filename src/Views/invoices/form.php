<?php
$pageTitle = $invoice ? 'Modifier la facture' : 'Nouvelle facture';
$items = $items ?? [];
$items = array_values($items);
if (count($items) < 5) {
    $items = array_pad($items, 5, ['description' => '', 'quantity' => 1, 'unit_price' => 0, 'total' => 0]);
}
$statusLabels = ['draft' => 'Brouillon', 'pending' => 'En attente', 'paid' => 'Payée', 'overdue' => 'En retard'];
?>
<?php ob_start(); ?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h1 class="h4 mb-0">
        <?= $invoice ? '<i class="bi bi-pencil"></i> Modifier la facture' : '<i class="bi bi-plus-lg"></i> Nouvelle facture' ?>
    </h1>
    <a href="/invoices" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Retour</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?= e($action) ?>" id="formInvoice">
            <input type="hidden" name="_csrf" value="<?= csrfToken() ?>">

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label">Client <span class="text-danger">*</span></label>
                    <select name="client_id" class="form-select" required>
                        <option value="">— Choisir un client —</option>
                        <?php foreach ($clients as $c): ?>
                        <option value="<?= (int) $c['id'] ?>" <?= (isset($invoice['client_id']) && (int)$invoice['client_id'] === (int)$c['id']) ? 'selected' : '' ?>>
                            <?= e($c['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Statut</label>
                    <select name="status" class="form-select">
                        <?php foreach ($statusLabels as $val => $label): ?>
                        <option value="<?= e($val) ?>" <?= (($invoice['status'] ?? 'draft') === $val) ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date d'émission</label>
                    <input type="date" name="issue_date" class="form-control" required
                           value="<?= e($invoice['issue_date'] ?? date('Y-m-d')) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Échéance</label>
                    <input type="date" name="due_date" class="form-control" required
                           value="<?= e($invoice['due_date'] ?? date('Y-m-d', strtotime('+30 days'))) ?>">
                </div>
            </div>

            <h6 class="mb-2">Lignes de facture</h6>
            <div class="table-responsive">
                <table class="table table-sm align-middle" id="invoiceItems">
                    <thead class="table-light">
                        <tr>
                            <th>Description</th>
                            <th class="text-center" style="width:90px">Quantité</th>
                            <th class="text-end" style="width:110px">Prix unitaire</th>
                            <th class="text-end" style="width:110px">Total ligne</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $i => $row):
                            $qty = (float)($row['quantity'] ?? 1);
                            $price = (float)($row['unit_price'] ?? 0);
                            $lineTotal = $row['total'] ?? ($qty * $price);
                        ?>
                        <tr>
                            <td>
                                <input type="text" name="items[<?= $i ?>][description]" class="form-control form-control-sm"
                                       value="<?= e($row['description'] ?? '') ?>"
                                       placeholder="Désignation">
                            </td>
                            <td>
                                <input type="number" name="items[<?= $i ?>][quantity]" class="form-control form-control-sm text-center item-qty" step="0.01" min="0"
                                       value="<?= e($row['quantity'] ?? '1') ?>">
                            </td>
                            <td>
                                <input type="number" name="items[<?= $i ?>][unit_price]" class="form-control form-control-sm text-end item-price" step="0.01" min="0"
                                       value="<?= e($row['unit_price'] ?? '0') ?>">
                            </td>
                            <td class="text-end align-middle item-total small"><?= number_format($lineTotal, 2, ',', ' ') ?> €</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="row g-3 mt-2">
                <div class="col-md-4 col-lg-3">
                    <label class="form-label">Sous-total HT</label>
                    <input type="number" name="subtotal" id="inputSubtotal" class="form-control" step="0.01" min="0" required
                           value="<?= e($invoice['subtotal'] ?? '0') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">TVA %</label>
                    <input type="number" name="tax_rate" id="inputTaxRate" class="form-control" step="0.01" min="0" max="100"
                           value="<?= e($invoice['tax_rate'] ?? '20') ?>">
                </div>
                <div class="col-md-4 col-lg-3">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Optionnel"><?= e($invoice['notes'] ?? '') ?></textarea>
                </div>
            </div>
            <p class="small text-muted mt-2">Le montant TTC est calculé automatiquement (sous-total + TVA). Vous pouvez ajuster le sous-total à la main ou le recalculer à partir des lignes.</p>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">
                    <?= $invoice ? 'Enregistrer' : 'Créer la facture' ?>
                </button>
                <a href="/invoices" class="btn btn-outline-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>

<script>
(function() {
    var form = document.getElementById('formInvoice');
    if (!form) return;
    var table = document.getElementById('invoiceItems');
    var inputSubtotal = document.getElementById('inputSubtotal');
    var inputTaxRate = document.getElementById('inputTaxRate');

    function parseNum(el) { return parseFloat(el.value) || 0; }

    function updateLineTotal(row) {
        var qty = parseNum(row.querySelector('.item-qty'));
        var price = parseNum(row.querySelector('.item-price'));
        var total = (qty * price).toFixed(2);
        row.querySelector('.item-total').textContent = total.replace('.', ',') + ' €';
        return parseFloat(total);
    }

    function updateSubtotalFromItems() {
        var total = 0;
        table.querySelectorAll('tbody tr').forEach(function(row) {
            total += updateLineTotal(row);
        });
        inputSubtotal.value = total.toFixed(2);
    }

    table.querySelectorAll('.item-qty, .item-price').forEach(function(input) {
        input.addEventListener('input', function() {
            var row = this.closest('tr');
            updateLineTotal(row);
            updateSubtotalFromItems();
        });
    });

    table.querySelectorAll('.item-qty, .item-price').forEach(function(input) {
        input.addEventListener('change', updateSubtotalFromItems);
    });
})();
</script>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/src/Views/layouts/main.php'; ?>
