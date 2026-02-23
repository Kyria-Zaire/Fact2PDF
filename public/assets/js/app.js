/**
 * Fact2PDF — app.js
 * Vanilla JS ES2022. Aucune dépendance externe.
 *
 * Modules :
 *   Api           : Fetch helpers (GET/POST/PATCH) avec CSRF dans les headers
 *   Toast         : Toasts Bootstrap programmatiques
 *   Charts        : Chart.js lazy (bar CA, doughnut statuts)
 *   initClients   : Recherche live, modal add/edit, delete
 *   initInvoices  : Filtre statut, PDF preview offcanvas, lignes dynamiques
 *   initProjects  : Filtre, toggle liste/kanban, drag&drop, timeline AJAX
 *   initNotif     : Polling 30s, badge + toasts
 */

'use strict';

// ============================================================
// 1. API HELPERS
// ============================================================

const Api = (() => {
    function getCsrf() {
        return document.querySelector('input[name="_csrf"]')?.value
            || document.querySelector('meta[name="csrf-token"]')?.content
            || '';
    }

    function jsonHeaders() {
        return {
            'Content-Type':    'application/json',
            'X-CSRF-Token':    getCsrf(),
            'X-Requested-With':'XMLHttpRequest',
        };
    }

    async function request(method, url, body = null) {
        const opts = { method, headers: jsonHeaders(), credentials: 'same-origin' };
        if (body !== null) opts.body = JSON.stringify(body);
        const res = await fetch(url, opts);
        if (!res.ok) {
            const err = await res.json().catch(() => ({}));
            throw new Error(err.error || `HTTP ${res.status}`);
        }
        return res.json();
    }

    async function postForm(url, formData) {
        const csrf = getCsrf();
        if (csrf && !formData.has('_csrf')) formData.append('_csrf', csrf);
        const res = await fetch(url, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        return res.json().catch(() => ({ success: true }));
    }

    return {
        get:      url       => request('GET',   url),
        post:     (url, d)  => request('POST',  url, d),
        patch:    (url, d)  => request('PATCH', url, d),
        postForm,
    };
})();

// ============================================================
// 2. TOAST
// ============================================================

const Toast = (() => {
    let container;

    function get() {
        if (!container) {
            container = Object.assign(document.createElement('div'), {
                className: 'toast-container position-fixed bottom-0 end-0 p-3',
            });
            container.style.zIndex = '9999';
            document.body.appendChild(container);
        }
        return container;
    }

    function show(msg, type = 'info', ms = 4000) {
        const map   = { info: 'primary', success: 'success', warning: 'warning', error: 'danger' };
        const color = map[type] || 'primary';
        const el    = document.createElement('div');
        el.className = `toast align-items-center text-bg-${color} border-0 show`;
        el.setAttribute('role', 'alert');
        el.innerHTML = `<div class="d-flex">
            <div class="toast-body">${msg}</div>
            <button class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>`;
        get().appendChild(el);
        new bootstrap.Toast(el, { delay: ms }).show();
        el.addEventListener('hidden.bs.toast', () => el.remove());
    }

    return { show };
})();

// ============================================================
// 3. CHARTS — Dashboard (lazy : attend que Chart.js soit chargé)
// ============================================================

function initCharts() {
    const dataEl = document.getElementById('dashData');
    if (!dataEl) return;

    // Chart.js est chargé avec defer → attendre s'il n'est pas encore prêt
    const tryInit = () => {
        if (typeof Chart === 'undefined') { setTimeout(tryInit, 100); return; }

        const { ca, status } = JSON.parse(dataEl.textContent);

        // ---- Bar CA mensuel ----
        const ctxCA = document.getElementById('chartCA');
        if (ctxCA) new Chart(ctxCA, {
            type: 'bar',
            data: {
                labels:   ca.labels,
                datasets: [{ label: 'CA (€)', data: ca.data,
                    backgroundColor: 'rgba(25,135,84,.7)', borderRadius: 4, borderWidth: 1 }],
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false },
                    tooltip: { callbacks: { label: c => fmtMoney(c.parsed.y) } } },
                scales: { y: { beginAtZero: true,
                    ticks: { callback: v => v.toLocaleString('fr-FR') + ' €' } } },
            },
        });

        // Statuts factures : créé par le script inline dans dashboard/index.php (couleurs garanties)
    };

    tryInit();
}

// ============================================================
// 4. CLIENTS — Recherche live + Modal AJAX
// ============================================================

function initClients() {
    const search = document.getElementById('clientSearch');
    const grid   = document.getElementById('clientsGrid');
    const noRes  = document.getElementById('noResults');
    const cntEl  = document.getElementById('clientCount');

    // Recherche live
    search?.addEventListener('input', () => {
        const term = search.value.toLowerCase().trim();
        let n = 0;
        grid?.querySelectorAll('.client-card').forEach(c => {
            const show = !term || (c.dataset.name || '').includes(term);
            c.classList.toggle('d-none', !show);
            if (show) n++;
        });
        if (cntEl) cntEl.textContent = n;
        noRes?.classList.toggle('d-none', n > 0);
    });

    // Pre-remplissage modal édition
    document.querySelectorAll('.btn-edit-client').forEach(btn => {
        btn.addEventListener('click', () => {
            const d = btn.dataset;
            document.getElementById('modalClientLabel').textContent = 'Modifier le client';
            document.getElementById('clientId').value  = d.id;
            document.getElementById('fName').value     = d.name;
            document.getElementById('fEmail').value    = d.email;
            document.getElementById('fPhone').value    = d.phone;
            document.getElementById('fAddress').value  = d.address;
            document.getElementById('fCity').value     = d.city;
            document.getElementById('fPostal').value   = d.postal;
            document.getElementById('fCountry').value  = d.country;
        });
    });

    // Reset modal
    document.getElementById('modalClient')?.addEventListener('hidden.bs.modal', () => {
        document.getElementById('modalClientLabel').textContent = 'Ajouter un client';
        document.getElementById('formClient')?.reset();
        document.getElementById('clientId').value = '';
        document.getElementById('clientFormError')?.classList.add('d-none');
        const p = document.getElementById('fLogoPreview');
        if (p) { p.src = ''; p.style.display = 'none'; }
    });

    // Aperçu logo
    document.getElementById('fLogo')?.addEventListener('change', function () {
        const p = document.getElementById('fLogoPreview');
        if (!p || !this.files[0]) return;
        p.src = URL.createObjectURL(this.files[0]);
        p.style.display = 'block';
    });

    // Soumission form client (Fetch + FormData pour fichier)
    document.getElementById('formClient')?.addEventListener('submit', async e => {
        e.preventDefault();
        const id      = document.getElementById('clientId').value;
        const spinner = document.getElementById('clientSpinner');
        const errEl   = document.getElementById('clientFormError');
        spinner?.classList.remove('d-none');
        errEl?.classList.add('d-none');

        try {
            await Api.postForm(id ? `/clients/${id}` : '/clients', new FormData(e.target));
            Toast.show(id ? 'Client mis à jour.' : 'Client créé.', 'success');
            setTimeout(() => location.reload(), 800);
        } catch (err) {
            if (errEl) { errEl.textContent = err.message; errEl.classList.remove('d-none'); }
        } finally {
            spinner?.classList.add('d-none');
        }
    });
}

// ============================================================
// 5. INVOICES — Filtre + PDF Offcanvas + Delete + Lignes
// ============================================================

function initInvoices() {
    // Filtre statut
    document.getElementById('filterStatus')?.addEventListener('change', function () {
        const v = this.value;
        document.querySelectorAll('.invoice-row').forEach(r =>
            r.classList.toggle('d-none', v !== '' && r.dataset.status !== v));
    });

    // PDF preview offcanvas
    const frame  = document.getElementById('pdfPreviewFrame');
    const numEl  = document.getElementById('pdfPreviewNum');
    const dlBtn  = document.getElementById('pdfDownloadBtn');
    const canvas = document.getElementById('pdfPreviewCanvas');

    document.querySelectorAll('.btn-pdf-preview').forEach(btn => {
        btn.addEventListener('click', () => {
            const url = `/invoices/${btn.dataset.id}/pdf`;
            if (frame)  frame.src         = url + '?inline=1';
            if (numEl)  numEl.textContent  = btn.dataset.num;
            if (dlBtn)  dlBtn.href         = url;
            bootstrap.Offcanvas.getOrCreateInstance(canvas).show();
        });
    });

    // Delete invoice (soumettre form caché)
    document.querySelectorAll('.btn-delete-invoice').forEach(btn => {
        btn.addEventListener('click', () => {
            if (!confirm(`Supprimer la facture ${btn.dataset.num} ?`)) return;
            const form = document.getElementById('formDeleteInvoice');
            form.action = `/invoices/${btn.dataset.id}/delete`;
            form.submit();
        });
    });

    initInvoiceItems();
}

function initInvoiceItems() {
    const container = document.getElementById('invoice-items');
    const addBtn    = document.getElementById('add-item');
    if (!container) return;

    let idx = container.querySelectorAll('.item-row').length;

    addBtn?.addEventListener('click', () => {
        const tpl = document.getElementById('item-template');
        if (!tpl) return;
        const clone = tpl.content.cloneNode(true);
        clone.querySelectorAll('[name]').forEach(el => { el.name = el.name.replace('__IDX__', idx); });
        container.appendChild(clone);
        attachRow(container.lastElementChild);
        recalc();
        idx++;
    });

    container.querySelectorAll('.item-row').forEach(attachRow);
    recalc();

    function attachRow(row) {
        row?.querySelectorAll('input').forEach(i => i.addEventListener('input', recalc));
        row?.querySelector('.remove-item')?.addEventListener('click', () => { row.remove(); recalc(); });
    }

    function recalc() {
        let sub = 0;
        container.querySelectorAll('.item-row').forEach(row => {
            const q = parseFloat(row.querySelector('[data-qty]')?.value)   || 0;
            const p = parseFloat(row.querySelector('[data-price]')?.value) || 0;
            const t = +(q * p).toFixed(2);
            const el = row.querySelector('[data-line-total]');
            if (el) el.textContent = fmtMoney(t);
            sub += t;
        });
        const rate  = parseFloat(document.getElementById('tax_rate')?.value) || 20;
        const tax   = +(sub * rate / 100).toFixed(2);
        const total = +(sub + tax).toFixed(2);
        setText('subtotal-display', fmtMoney(sub));
        setText('tax-display',      fmtMoney(tax));
        setText('total-display',    fmtMoney(total));
        setVal('subtotal',   sub);
        setVal('tax_amount', tax);
        setVal('total',      total);
    }
}

// ============================================================
// 6. PROJECTS — Filtre, Kanban, Drag & Drop, Timeline
// ============================================================

function initProjects() {
    // Filtre statut
    document.getElementById('filterProjectStatus')?.addEventListener('change', function () {
        const v = this.value;
        document.querySelectorAll('.project-row').forEach(r =>
            r.classList.toggle('d-none', v !== '' && r.dataset.status !== v));
    });

    // Toggle liste / kanban
    const viewList   = document.getElementById('viewList');
    const viewKanban = document.getElementById('viewKanban');
    const btnList    = document.getElementById('btnViewList');
    const btnKanban  = document.getElementById('btnViewKanban');

    btnList?.addEventListener('click', () => {
        viewList?.classList.remove('d-none');
        viewKanban?.classList.add('d-none');
        btnList.classList.add('active');
        btnKanban?.classList.remove('active');
        localStorage.setItem('f2p_projectView', 'list');
    });

    btnKanban?.addEventListener('click', () => {
        viewList?.classList.add('d-none');
        viewKanban?.classList.remove('d-none');
        btnKanban.classList.add('active');
        btnList?.classList.remove('active');
        updateKanbanCounts();
        localStorage.setItem('f2p_projectView', 'kanban');
    });

    if (localStorage.getItem('f2p_projectView') === 'kanban') btnKanban?.click();

    // Compteurs colonnes kanban
    function updateKanbanCounts() {
        document.querySelectorAll('.kanban-col').forEach(col => {
            col.querySelector('.kanban-count').textContent = col.querySelectorAll('.kanban-card').length;
        });
    }
    updateKanbanCounts();

    // Drag & drop
    let dragged = null;
    document.querySelectorAll('.kanban-card').forEach(card => {
        card.addEventListener('dragstart', e => { dragged = card; card.style.opacity = '.4'; e.dataTransfer.effectAllowed = 'move'; });
        card.addEventListener('dragend',   () => { card.style.opacity = ''; dragged = null; });
    });

    document.querySelectorAll('.kanban-cards').forEach(zone => {
        zone.addEventListener('dragover',  e => { e.preventDefault(); zone.classList.add('kanban-drag-over'); });
        zone.addEventListener('dragleave', () => zone.classList.remove('kanban-drag-over'));
        zone.addEventListener('drop', async e => {
            e.preventDefault();
            zone.classList.remove('kanban-drag-over');
            if (!dragged) return;

            const newStatus = zone.closest('.kanban-col').dataset.status;
            const oldStatus = dragged.dataset.status;
            if (newStatus === oldStatus) return;

            zone.appendChild(dragged);
            dragged.dataset.status = newStatus;
            updateKanbanCounts();

            try {
                await Api.patch(`/projects/${dragged.dataset.id}`, { status: newStatus });
                Toast.show('Statut mis à jour.', 'success');
            } catch {
                Toast.show('Erreur mise à jour statut.', 'error');
                dragged.dataset.status = oldStatus;
            }
        });
    });

    // Delete
    document.querySelectorAll('.btn-delete-project').forEach(btn => {
        btn.addEventListener('click', () => {
            if (!confirm(`Supprimer « ${btn.dataset.name} » ?`)) return;
            const f = document.getElementById('formDeleteProject');
            f.action = `/projects/${btn.dataset.id}/delete`;
            f.submit();
        });
    });

    // Timeline checkboxes
    const debouncedSave = debounce(saveTimeline, 600);
    document.querySelectorAll('.timeline-step-check').forEach(cb => {
        cb.addEventListener('change', debouncedSave);
    });
}

async function saveTimeline() {
    const projectId = document.getElementById('projectId')?.value;
    if (!projectId) return;

    const steps = [...document.querySelectorAll('.timeline-step')].map(el => ({
        label: el.querySelector('.step-label')?.textContent?.trim() || '',
        date:  el.querySelector('.step-date')?.dataset?.date || '',
        done:  el.querySelector('.timeline-step-check')?.checked ?? false,
    }));

    try {
        const res = await Api.post(`/projects/${projectId}/timeline`, { steps });
        const bar = document.getElementById('progressBar');
        if (bar) {
            bar.style.width = res.progress + '%';
            bar.setAttribute('aria-valuenow', res.progress);
            bar.textContent = res.progress + '%';
        }
        Toast.show('Progression sauvegardée.', 'success');
    } catch {
        Toast.show('Erreur sauvegarde timeline.', 'error');
    }
}

// ============================================================
// 7. NOTIFICATIONS — Polling 30s
// ============================================================

function initNotifications() {
    const badge = document.getElementById('notifBadge');
    if (!badge) return;

    let last = 0;

    async function poll() {
        try {
            const data  = await Api.get('/notifications/poll');
            const count = data.count || 0;

            badge.textContent = count > 9 ? '9+' : count;
            badge.classList.toggle('d-none', count === 0);

            if (count > last && last >= 0) {
                data.items?.slice(0, count - last).forEach(n =>
                    Toast.show(`<strong>${n.title}</strong>${n.body ? '<br>' + n.body : ''}`, 'info', 6000)
                );
            }
            last = count;
        } catch { /* réseau indisponible */ }
    }

    poll();
    setInterval(poll, 30_000);

    document.getElementById('btnNotif')?.addEventListener('click', async () => {
        try {
            await Api.post('/notifications/read-all', {});
            badge.classList.add('d-none');
            last = 0;
        } catch { Toast.show('Erreur notifications.', 'error'); }
    });
}

// ============================================================
// 8. UTILS
// ============================================================

function initConfirmForms() {
    document.querySelectorAll('form[data-confirm]').forEach(f =>
        f.addEventListener('submit', e => { if (!confirm(f.dataset.confirm)) e.preventDefault(); })
    );
}

function initFlashDismiss() {
    document.querySelectorAll('.alert.alert-dismissible').forEach(a =>
        setTimeout(() => a.querySelector('.btn-close')?.click(), 4000)
    );
}

const fmtMoney = v => new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(v);

function setText(id, v) { const el = document.getElementById(id); if (el) el.textContent = v; }
function setVal(id, v)  { const el = document.getElementById(id); if (el) el.value = v; }

let _dt;
const debounce = (fn, ms) => (...args) => { clearTimeout(_dt); _dt = setTimeout(() => fn(...args), ms); };

// ============================================================
// INIT
// ============================================================

document.addEventListener('DOMContentLoaded', () => {
    initConfirmForms();
    initFlashDismiss();
    initNotifications();

    if (document.getElementById('dashData'))      initCharts();
    if (document.getElementById('clientsGrid'))   initClients();
    if (document.getElementById('invoicesTable')) initInvoices();
    if (document.getElementById('viewList'))      initProjects();
    if (document.getElementById('invoice-items')) initInvoiceItems();
});
