/**
 * Fact2PDF — JavaScript principal
 * Vanilla JS, pas de framework.
 */

'use strict';

// ---- Confirmation avant suppression ----
document.addEventListener('DOMContentLoaded', () => {

    // Formulaires de suppression : demander confirmation
    document.querySelectorAll('form[data-confirm]').forEach(form => {
        form.addEventListener('submit', e => {
            const msg = form.dataset.confirm || 'Confirmer la suppression ?';
            if (!confirm(msg)) {
                e.preventDefault();
            }
        });
    });

    // ---- Lignes de facture dynamiques ----
    const itemsContainer = document.getElementById('invoice-items');
    if (itemsContainer) {
        initInvoiceItems();
    }

    // ---- Preview logo client ----
    const logoInput = document.getElementById('logo');
    if (logoInput) {
        logoInput.addEventListener('change', function () {
            const file = this.files[0];
            if (!file) return;
            const preview = document.getElementById('logo-preview');
            if (preview) {
                preview.src = URL.createObjectURL(file);
                preview.style.display = 'block';
            }
        });
    }

    // ---- Auto-dismiss alertes flash après 4s ----
    document.querySelectorAll('.alert.alert-dismissible').forEach(alert => {
        setTimeout(() => {
            const btn = alert.querySelector('.btn-close');
            if (btn) btn.click();
        }, 4000);
    });

});

/**
 * Gestion des lignes de facture (ajout/suppression dynamique).
 */
function initInvoiceItems() {
    const container = document.getElementById('invoice-items');
    const addBtn    = document.getElementById('add-item');
    let   rowIndex  = container.querySelectorAll('.item-row').length;

    if (addBtn) {
        addBtn.addEventListener('click', () => addRow(rowIndex++));
    }

    // Calculer les totaux au chargement
    container.querySelectorAll('.item-row').forEach(row => attachRowListeners(row));
    recalcTotals();

    function addRow(idx) {
        const template = document.getElementById('item-template').content.cloneNode(true);
        // Remplacer l'index dans les noms de champs
        template.querySelectorAll('[name]').forEach(el => {
            el.name = el.name.replace('__IDX__', idx);
        });
        const row = template.querySelector('.item-row');
        container.appendChild(template);
        attachRowListeners(container.lastElementChild);
    }

    function attachRowListeners(row) {
        row.querySelectorAll('input').forEach(input => {
            input.addEventListener('input', recalcTotals);
        });
        const removeBtn = row.querySelector('.remove-item');
        if (removeBtn) {
            removeBtn.addEventListener('click', () => {
                row.remove();
                recalcTotals();
            });
        }
    }

    function recalcTotals() {
        let subtotal = 0;
        container.querySelectorAll('.item-row').forEach(row => {
            const qty   = parseFloat(row.querySelector('[data-qty]')?.value)   || 0;
            const price = parseFloat(row.querySelector('[data-price]')?.value) || 0;
            const total = qty * price;
            const totalEl = row.querySelector('[data-line-total]');
            if (totalEl) totalEl.textContent = formatMoney(total);
            subtotal += total;
        });

        const taxRate    = parseFloat(document.getElementById('tax_rate')?.value) || 20;
        const taxAmount  = subtotal * taxRate / 100;
        const grandTotal = subtotal + taxAmount;

        setText('subtotal-display',  formatMoney(subtotal));
        setText('tax-display',       formatMoney(taxAmount));
        setText('total-display',     formatMoney(grandTotal));

        // Remplir les champs hidden pour soumission
        setVal('subtotal',   subtotal.toFixed(2));
        setVal('tax_amount', taxAmount.toFixed(2));
        setVal('total',      grandTotal.toFixed(2));
    }
}

function formatMoney(amount) {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(amount);
}

function setText(id, val) {
    const el = document.getElementById(id);
    if (el) el.textContent = val;
}

function setVal(id, val) {
    const el = document.getElementById(id);
    if (el) el.value = val;
}
