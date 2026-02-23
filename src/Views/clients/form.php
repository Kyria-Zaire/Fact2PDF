<?php
$pageTitle = $client ? ('Modifier — ' . e($client['name'])) : 'Nouveau client';
$isEdit = (bool) $client;
?>
<?php ob_start(); ?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h1 class="h4 mb-0">
        <?= $isEdit ? '<i class="bi bi-pencil"></i> Modifier le client' : '<i class="bi bi-plus-lg"></i> Nouveau client' ?>
    </h1>
    <a href="<?= $isEdit ? '/clients/' . (int)$client['id'] : '/clients' ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Retour
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?= e($action) ?>" enctype="multipart/form-data">
            <input type="hidden" name="_csrf" value="<?= csrfToken() ?>">

            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Logo</label>
                    <div class="d-flex align-items-center gap-3">
                        <?php if ($isEdit && !empty($client['logo_path'])): ?>
                            <img src="<?= e($client['logo_path']) ?>" alt="" class="rounded" style="width:64px;height:64px;object-fit:contain;background:#f8f9fa;">
                        <?php endif; ?>
                        <input type="file" name="logo" class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp">
                        <span class="text-muted small">JPEG, PNG ou WebP</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Nom <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required
                           value="<?= e($client['name'] ?? '') ?>" placeholder="Raison sociale ou nom">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control"
                           value="<?= e($client['email'] ?? '') ?>" placeholder="contact@exemple.fr">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Téléphone</label>
                    <input type="text" name="phone" class="form-control"
                           value="<?= e($client['phone'] ?? '') ?>" placeholder="+33 1 23 45 67 89">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Pays</label>
                    <select name="country" class="form-select">
                        <?php foreach (['FR' => 'France', 'BE' => 'Belgique', 'CH' => 'Suisse', 'LU' => 'Luxembourg'] as $code => $label): ?>
                            <option value="<?= e($code) ?>" <?= (($client['country'] ?? 'FR') === $code ? 'selected' : '') ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Adresse</label>
                    <input type="text" name="address" class="form-control"
                           value="<?= e($client['address'] ?? '') ?>" placeholder="Numéro et voie">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Code postal</label>
                    <input type="text" name="postal_code" class="form-control"
                           value="<?= e($client['postal_code'] ?? '') ?>" placeholder="75001">
                </div>
                <div class="col-md-8">
                    <label class="form-label">Ville</label>
                    <input type="text" name="city" class="form-control"
                           value="<?= e($client['city'] ?? '') ?>" placeholder="Paris">
                </div>
                <div class="col-12">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="3" placeholder="Notes internes"><?= e($client['notes'] ?? '') ?></textarea>
                </div>
            </div>

            <hr class="my-4">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <?= $isEdit ? 'Enregistrer' : 'Créer le client' ?>
                </button>
                <a href="<?= $isEdit ? '/clients/' . (int)$client['id'] : '/clients' ?>" class="btn btn-outline-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/src/Views/layouts/main.php'; ?>
