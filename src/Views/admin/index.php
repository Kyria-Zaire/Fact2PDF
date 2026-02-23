<?php $pageTitle = 'Administration'; ?>
<?php ob_start(); ?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h1 class="h4 mb-0"><i class="bi bi-gear"></i> Administration</h1>
</div>

<!-- Cartes résumé -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <a href="/admin/users" class="text-decoration-none">
            <div class="card h-100 border-primary">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="small text-muted">Utilisateurs</div>
                        <div class="fs-3 fw-bold text-primary"><?= (int) $userCount ?></div>
                    </div>
                    <i class="bi bi-people fs-1 text-primary opacity-50"></i>
                </div>
                <div class="card-footer bg-transparent small text-primary">
                    Gérer les utilisateurs <i class="bi bi-arrow-right"></i>
                </div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="/clients" class="text-decoration-none">
            <div class="card h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="small text-muted">Clients</div>
                        <div class="fs-3 fw-bold"><?= (int) $clientCount ?></div>
                    </div>
                    <i class="bi bi-building fs-1 opacity-50 text-muted"></i>
                </div>
                <div class="card-footer bg-transparent small text-muted">
                    Voir les clients <i class="bi bi-arrow-right"></i>
                </div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="/invoices" class="text-decoration-none">
            <div class="card h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="small text-muted">Factures</div>
                        <div class="fs-3 fw-bold"><?= (int) ($invoiceStats['total'] ?? 0) ?></div>
                    </div>
                    <i class="bi bi-receipt fs-1 opacity-50 text-muted"></i>
                </div>
                <div class="card-footer bg-transparent small text-muted">
                    Voir les factures <i class="bi bi-arrow-right"></i>
                </div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="/dashboard" class="text-decoration-none">
            <div class="card h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="small text-muted">Dashboard</div>
                        <div class="fw-bold">Vue d'ensemble</div>
                    </div>
                    <i class="bi bi-graph-up fs-1 opacity-50 text-muted"></i>
                </div>
                <div class="card-footer bg-transparent small text-muted">
                    Aller au dashboard <i class="bi bi-arrow-right"></i>
                </div>
            </div>
        </a>
    </div>
</div>

<!-- Derniers utilisateurs -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Derniers utilisateurs inscrits</span>
        <a href="/admin/users" class="btn btn-sm btn-outline-primary">Tous les utilisateurs</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Rôle</th>
                    <th class="col-hide-sm">Créé le</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($recentUsers as $u): ?>
                <tr>
                    <td class="fw-semibold"><?= e($u['username']) ?></td>
                    <td><?= e($u['email']) ?></td>
                    <td><span class="badge text-bg-<?= $u['role'] === 'admin' ? 'danger' : ($u['role'] === 'user' ? 'primary' : 'secondary') ?>"><?= e($u['role']) ?></span></td>
                    <td class="text-muted small col-hide-sm"><?= !empty($u['created_at']) ? date('d/m/Y H:i', strtotime($u['created_at'])) : '—' ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($recentUsers)): ?>
                <tr>
                    <td colspan="4" class="text-center text-muted py-4">Aucun utilisateur.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/src/Views/layouts/main.php'; ?>
