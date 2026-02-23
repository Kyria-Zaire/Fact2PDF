<?php $pageTitle = 'Clients'; ?>
<?php ob_start(); ?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h1 class="h4 mb-0">Clients <span class="badge text-bg-secondary ms-1" id="clientCount"><?= count($clients) ?></span></h1>
    <div class="d-flex gap-2 flex-wrap flex-grow-1 flex-md-grow-0">
        <!-- Recherche live -->
        <div class="input-group input-group-sm client-search-group">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="search" id="clientSearch" class="form-control" placeholder="Rechercher…">
        </div>
        <?php if (in_array($_SESSION['role'] ?? '', ['admin','user'])): ?>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalClient">
            <i class="bi bi-plus-lg"></i> Ajouter
        </button>
        <?php endif; ?>
    </div>
</div>

<!-- ---- Liste clients (rendue par JS après fetch, ou SSR fallback) ---- -->
<div class="row g-3" id="clientsGrid">
<?php foreach ($clients as $c): ?>
    <div class="col-md-4 col-lg-3 client-card" data-name="<?= e(strtolower($c['name'])) ?>">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <?php if (!empty($c['logo_path'])): ?>
                    <img src="<?= e($c['logo_path']) ?>" alt="" class="client-logo">
                <?php else: ?>
                    <div class="client-logo-placeholder rounded d-flex align-items-center justify-content-center text-white fw-bold fs-5"
                         style="width:48px;height:48px;background:<?= sprintf('#%06x', crc32($c['name']) & 0xFFFFFF) ?>">
                        <?= strtoupper(substr($c['name'], 0, 1)) ?>
                    </div>
                <?php endif; ?>
                <div class="flex-grow-1 min-w-0">
                    <div class="fw-semibold text-truncate"><?= e($c['name']) ?></div>
                    <div class="text-muted small text-truncate"><?= e($c['email'] ?? '—') ?></div>
                    <div class="small mt-1">
                        <span class="badge text-bg-light border">
                            <i class="bi bi-receipt"></i> <?= (int)($c['invoice_count'] ?? 0) ?> facture(s)
                        </span>
                        <?php if (($c['total_billed'] ?? 0) > 0): ?>
                        <span class="badge text-bg-success ms-1"><?= formatMoney((float)$c['total_billed']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent d-flex gap-1 justify-content-end">
                <a href="/clients/<?= $c['id'] ?>" class="btn btn-xs btn-outline-primary" title="Voir">
                    <i class="bi bi-eye"></i>
                </a>
                <?php if (in_array($_SESSION['role'] ?? '', ['admin','user'])): ?>
                <button class="btn btn-xs btn-outline-secondary btn-edit-client"
                        title="Modifier"
                        data-id="<?= $c['id'] ?>"
                        data-name="<?= e($c['name']) ?>"
                        data-email="<?= e($c['email'] ?? '') ?>"
                        data-phone="<?= e($c['phone'] ?? '') ?>"
                        data-address="<?= e($c['address'] ?? '') ?>"
                        data-city="<?= e($c['city'] ?? '') ?>"
                        data-postal="<?= e($c['postal_code'] ?? '') ?>"
                        data-country="<?= e($c['country'] ?? 'FR') ?>"
                        data-bs-toggle="modal" data-bs-target="#modalClient">
                    <i class="bi bi-pencil"></i>
                </button>
                <?php endif; ?>
                <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
                <form method="POST" action="/clients/<?= $c['id'] ?>/delete"
                      data-confirm="Supprimer « <?= e($c['name']) ?> » et toutes ses factures ?">
                    <input type="hidden" name="_csrf" value="<?= csrfToken() ?>">
                    <button type="submit" class="btn btn-xs btn-outline-danger" title="Supprimer">
                        <i class="bi bi-trash"></i>
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>

<!-- ---- Message vide ---- -->
<div id="noResults" class="text-center text-muted py-5 d-none">
    <i class="bi bi-search fs-1"></i>
    <p class="mt-2">Aucun client trouvé.</p>
</div>

<!-- ================================================================
     Modal Ajout / Édition client (AJAX submit avec Fetch)
     ================================================================ -->
<?php if (in_array($_SESSION['role'] ?? '', ['admin','user'])): ?>
<div class="modal fade" id="modalClient" tabindex="-1" aria-labelledby="modalClientLabel">
    <div class="modal-dialog modal-lg modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalClientLabel">Ajouter un client</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formClient" enctype="multipart/form-data" novalidate>
                <input type="hidden" name="_csrf" value="<?= csrfToken() ?>">
                <input type="hidden" name="_method" value="POST"> <!-- POST = create, PUT+id = edit -->
                <input type="hidden" id="clientId" name="client_id" value="">

                <div class="modal-body">
                    <!-- Alerte erreur -->
                    <div id="clientFormError" class="alert alert-danger d-none"></div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nom <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="fName" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" id="fEmail" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Téléphone</label>
                            <input type="tel" name="phone" id="fPhone" class="form-control">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Adresse</label>
                            <input type="text" name="address" id="fAddress" class="form-control">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Ville</label>
                            <input type="text" name="city" id="fCity" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Code postal</label>
                            <input type="text" name="postal_code" id="fPostal" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Pays</label>
                            <select name="country" id="fCountry" class="form-select">
                                <option value="FR">France</option>
                                <option value="BE">Belgique</option>
                                <option value="CH">Suisse</option>
                                <option value="LU">Luxembourg</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Logo</label>
                            <input type="file" name="logo" id="fLogo" class="form-control" accept="image/*">
                            <img id="fLogoPreview" src="" alt="" class="mt-2 rounded" style="max-height:60px;display:none">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="btnClientSave">
                        <span class="spinner-border spinner-border-sm d-none me-1" id="clientSpinner"></span>
                        Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/src/Views/layouts/main.php'; ?>
