<?php $pageTitle = 'Gestion des utilisateurs'; ?>
<?php ob_start(); ?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h1 class="h4 mb-0"><i class="bi bi-people"></i> Utilisateurs</h1>
    <a href="/admin" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Retour admin</a>
</div>

<!-- Formulaire création utilisateur -->
<div class="card mb-4">
    <div class="card-header">Ajouter un utilisateur</div>
    <div class="card-body">
        <form method="POST" action="/admin/users" class="row g-3">
            <input type="hidden" name="_csrf" value="<?= csrfToken() ?>">
            <div class="col-md-3">
                <label class="form-label">Nom d'utilisateur <span class="text-danger">*</span></label>
                <input type="text" name="username" class="form-control form-control-sm" required minlength="2" placeholder="john">
            </div>
            <div class="col-md-3">
                <label class="form-label">Email <span class="text-danger">*</span></label>
                <input type="email" name="email" class="form-control form-control-sm" required placeholder="john@example.com">
            </div>
            <div class="col-md-2">
                <label class="form-label">Mot de passe <span class="text-danger">*</span></label>
                <input type="password" name="password" class="form-control form-control-sm" required minlength="8" placeholder="8 caractères min">
            </div>
            <div class="col-md-2">
                <label class="form-label">Rôle</label>
                <select name="role" class="form-select form-select-sm">
                    <option value="user">User</option>
                    <option value="viewer">Viewer</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="bi bi-plus-lg"></i> Créer
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Liste des utilisateurs -->
<div class="card">
    <div class="card-header"><?= count($users) ?> utilisateur(s)</div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Rôle</th>
                    <th class="col-hide-sm">Actif</th>
                    <th class="col-hide-md">Créé le</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td class="text-muted small">#<?= (int) $u['id'] ?></td>
                    <td class="fw-semibold"><?= e($u['username']) ?></td>
                    <td><?= e($u['email']) ?></td>
                    <td>
                        <span class="badge text-bg-<?= $u['role'] === 'admin' ? 'danger' : ($u['role'] === 'user' ? 'primary' : 'secondary') ?>">
                            <?= e($u['role']) ?>
                        </span>
                    </td>
                    <td class="col-hide-sm">
                        <?= !empty($u['is_active']) ? '<span class="text-success"><i class="bi bi-check-circle"></i></span>' : '<span class="text-muted">Non</span>' ?>
                    </td>
                    <td class="text-muted small col-hide-md"><?= !empty($u['created_at']) ? date('d/m/Y', strtotime($u['created_at'])) : '—' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/src/Views/layouts/main.php'; ?>
