<?php
$pageTitle = e($client['name'] ?? 'Client');
?>
<?php ob_start(); ?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="/clients">Clients</a></li>
                <li class="breadcrumb-item active"><?= e($client['name'] ?? '') ?></li>
            </ol>
        </nav>
        <h1 class="h4 mb-0 d-flex align-items-center gap-3">
            <?php if (!empty($client['logo_path'])): ?>
                <img src="<?= e($client['logo_path']) ?>" alt="" class="rounded" style="width:48px;height:48px;object-fit:contain;background:#f8f9fa;">
            <?php else: ?>
                <div class="rounded d-flex align-items-center justify-content-center text-white fw-bold" style="width:48px;height:48px;background:<?= sprintf('#%06x', crc32($client['name'] ?? '') & 0xFFFFFF) ?>">
                    <?= strtoupper(mb_substr($client['name'] ?? '?', 0, 1)) ?>
                </div>
            <?php endif; ?>
            <?= e($client['name'] ?? '—') ?>
        </h1>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php if (in_array($_SESSION['role'] ?? '', ['admin', 'user'])): ?>
        <a href="/clients/<?= (int)$client['id'] ?>/edit" class="btn btn-primary btn-sm">
            <i class="bi bi-pencil"></i> Modifier
        </a>
        <?php endif; ?>
        <a href="/clients" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Retour</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">Coordonnées</div>
            <ul class="list-group list-group-flush">
                <?php if (!empty($client['email'])): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span class="text-muted">Email</span>
                    <a href="mailto:<?= e($client['email']) ?>"><?= e($client['email']) ?></a>
                </li>
                <?php endif; ?>
                <?php if (!empty($client['phone'])): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span class="text-muted">Téléphone</span>
                    <a href="tel:<?= e(preg_replace('/\s+/', '', $client['phone'])) ?>"><?= e($client['phone']) ?></a>
                </li>
                <?php endif; ?>
                <?php if (!empty($client['address'])): ?>
                <li class="list-group-item">
                    <span class="text-muted d-block small">Adresse</span>
                    <?= nl2br(e($client['address'])) ?>
                </li>
                <?php endif; ?>
                <?php if (!empty($client['city']) || !empty($client['postal_code'])): ?>
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-muted">Ville</span>
                    <span><?= e(trim(($client['postal_code'] ?? '') . ' ' . ($client['city'] ?? ''))) ?></span>
                </li>
                <?php endif; ?>
                <?php if (!empty($client['country'])): ?>
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-muted">Pays</span>
                    <span><?= e($client['country']) ?></span>
                </li>
                <?php endif; ?>
                <?php if (empty($client['email']) && empty($client['phone']) && empty($client['address']) && empty($client['city']) && empty($client['postal_code']) && empty($client['country'])): ?>
                <li class="list-group-item text-muted">Aucune coordonnée renseignée.</li>
                <?php endif; ?>
            </ul>
        </div>
        <?php if (!empty($client['notes'])): ?>
        <div class="card mt-3">
            <div class="card-header">Notes</div>
            <div class="card-body"><?= nl2br(e($client['notes'])) ?></div>
        </div>
        <?php endif; ?>
    </div>
    <div class="col-lg-6">
        <?php if (!empty($contacts)): ?>
        <div class="card">
            <div class="card-header">Contacts</div>
            <ul class="list-group list-group-flush">
                <?php foreach ($contacts as $contact): ?>
                <li class="list-group-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="fw-semibold"><?= e($contact['name']) ?></span>
                            <?php if (!empty($contact['is_primary'])): ?>
                                <span class="badge text-bg-primary ms-1">Principal</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if (!empty($contact['email'])): ?><div class="small"><a href="mailto:<?= e($contact['email']) ?>"><?= e($contact['email']) ?></a></div><?php endif; ?>
                    <?php if (!empty($contact['phone'])): ?><div class="small text-muted"><?= e($contact['phone']) ?></div><?php endif; ?>
                    <?php if (!empty($contact['role'])): ?><div class="small text-muted"><?= e($contact['role']) ?></div><?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        <div class="card mt-3">
            <div class="card-header">Liens rapides</div>
            <div class="list-group list-group-flush">
                <a href="/invoices" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-receipt me-2"></i>Factures</span>
                    <i class="bi bi-chevron-right text-muted"></i>
                </a>
                <a href="/projects" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-kanban me-2"></i>Projets</span>
                    <i class="bi bi-chevron-right text-muted"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/src/Views/layouts/main.php'; ?>
