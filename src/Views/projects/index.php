<?php $pageTitle = 'Projets'; ?>
<?php ob_start(); ?>

<?php
$statColors = [
    'todo'        => 'secondary',
    'in_progress' => 'primary',
    'review'      => 'warning',
    'done'        => 'success',
    'archived'    => 'light text-dark',
];
$priorityColors = [
    'low'      => 'light text-dark',
    'medium'   => 'info text-dark',
    'high'     => 'warning text-dark',
    'critical' => 'danger',
];
$statusLabels = \App\Models\Project::STATUS_LABELS;
?>

<!-- ---- En-tête ---- -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div class="flex-grow-1 min-w-0">
        <h1 class="h4 mb-0">Projets</h1>
        <div class="text-muted small mt-1 stats-line">
            <?= (int)($stats['total'] ?? 0) ?> total —
            <span class="text-primary"><?= (int)($stats['in_progress'] ?? 0) ?> en cours</span> —
            <span class="text-success"><?= (int)($stats['done'] ?? 0) ?> terminés</span>
            <?php if (($stats['late'] ?? 0) > 0): ?>
            — <span class="text-danger fw-semibold"><?= (int)$stats['late'] ?> en retard</span>
            <?php endif; ?>
        </div>
    </div>
    <div class="page-toolbar d-flex gap-2 flex-wrap flex-grow-1 flex-lg-grow-0">
        <!-- Filtre statut -->
        <select id="filterProjectStatus" class="form-select form-select-sm" style="width:150px">
            <option value="">Tous</option>
            <?php foreach ($statusLabels as $val => $label): ?>
            <option value="<?= $val ?>"><?= $label ?></option>
            <?php endforeach; ?>
        </select>
        <!-- Vue toggle -->
        <div class="btn-group btn-sm" role="group">
            <button class="btn btn-outline-secondary active" id="btnViewList" title="Liste">
                <i class="bi bi-list-ul"></i>
            </button>
            <button class="btn btn-outline-secondary" id="btnViewKanban" title="Kanban">
                <i class="bi bi-kanban"></i>
            </button>
        </div>
        <?php if (in_array($_SESSION['role'] ?? '', ['admin','user'])): ?>
        <a href="/projects/create" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg"></i> Nouveau projet
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- ====================================================
     VUE LISTE
     ==================================================== -->
<div id="viewList">
    <div class="card">
        <div class="table-responsive table-projects">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Projet</th>
                        <th class="col-hide-xs">Client</th>
                        <th class="col-hide-sm">Priorité</th>
                        <th>Statut</th>
                        <th class="col-hide-md">Progression</th>
                        <th class="col-hide-xs">Échéance</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($projects as $p):
                    $isLate = $p['is_late'] ?? false;
                ?>
                    <tr class="project-row" data-status="<?= e($p['status']) ?>"
                        <?= $isLate ? 'class="table-warning"' : '' ?>>
                        <td>
                            <a href="/projects/<?= $p['id'] ?>" class="fw-semibold text-decoration-none">
                                <?= e($p['name']) ?>
                            </a>
                            <?php if ($isLate): ?>
                            <span class="badge text-bg-danger ms-1 small">Retard</span>
                            <?php endif; ?>
                            <?php if (!empty($p['invoice_number'])): ?>
                            <div class="text-muted x-small"><i class="bi bi-receipt"></i> <?= e($p['invoice_number']) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($p['client_name'])): ?>
                            <div class="text-muted x-small d-block d-sm-none mt-0"><?= e($p['client_name']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="col-hide-xs">
                            <div class="d-flex align-items-center gap-2">
                                <?php if (!empty($p['client_logo'])): ?>
                                <img src="<?= e($p['client_logo']) ?>" alt="" style="width:24px;height:24px;object-fit:contain">
                                <?php endif; ?>
                                <?= e($p['client_name']) ?>
                            </div>
                        </td>
                        <td class="col-hide-sm">
                            <span class="badge text-bg-<?= $priorityColors[$p['priority']] ?? 'light' ?>">
                                <?= e(\App\Models\Project::PRIORITY_LABELS[$p['priority']] ?? $p['priority']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge text-bg-<?= $statColors[$p['status']] ?? 'secondary' ?>">
                                <?= e($statusLabels[$p['status']] ?? $p['status']) ?>
                            </span>
                        </td>
                        <td class="col-hide-md" style="min-width:120px">
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress flex-grow-1" style="height:6px">
                                    <div class="progress-bar bg-<?= $statColors[$p['status']] ?? 'secondary' ?>"
                                         style="width:<?= $p['progress'] ?>%"></div>
                                </div>
                                <small class="text-muted"><?= $p['progress'] ?>%</small>
                            </div>
                        </td>
                        <td class="text-muted small col-hide-xs <?= $isLate ? 'text-danger fw-semibold' : '' ?>">
                            <?= !empty($p['end_date']) ? formatDate($p['end_date']) : '—' ?>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="/projects/<?= $p['id'] ?>" class="btn btn-outline-secondary" title="Voir">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php if (in_array($_SESSION['role'] ?? '', ['admin','user'])): ?>
                                <a href="/projects/<?= $p['id'] ?>/edit" class="btn btn-outline-secondary" title="Modifier">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php endif; ?>
                                <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
                                <button class="btn btn-outline-danger btn-delete-project"
                                        data-id="<?= $p['id'] ?>"
                                        data-name="<?= e($p['name']) ?>"
                                        title="Supprimer">
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
</div>

<!-- ====================================================
     VUE KANBAN (colonnes par statut, drag & drop via JS)
     ==================================================== -->
<div id="viewKanban" class="d-none">
    <div class="kanban-board d-flex gap-3 overflow-auto pb-2" style="min-height:60vh">
        <?php foreach ($statusLabels as $colStatus => $colLabel): ?>
        <?php if ($colStatus === 'archived') continue; // Archivés hors Kanban ?>
        <div class="kanban-col flex-shrink-0" style="width:280px"
             data-status="<?= $colStatus ?>">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center py-2 bg-<?= $statColors[$colStatus] ?? 'secondary' ?> text-white">
                    <span class="fw-semibold small"><?= $colLabel ?></span>
                    <span class="badge bg-white text-dark kanban-count">0</span>
                </div>
                <div class="kanban-cards p-2 d-flex flex-column gap-2" style="min-height:200px">
                    <?php foreach ($projects as $p):
                        if ($p['status'] !== $colStatus) continue;
                    ?>
                    <div class="card border-0 shadow-sm kanban-card" draggable="true"
                         data-id="<?= $p['id'] ?>" data-status="<?= $p['status'] ?>">
                        <div class="card-body p-2">
                            <div class="fw-semibold small"><?= e($p['name']) ?></div>
                            <div class="text-muted" style="font-size:.75rem"><?= e($p['client_name']) ?></div>
                            <div class="progress mt-2" style="height:4px">
                                <div class="progress-bar" style="width:<?= $p['progress'] ?>%"></div>
                            </div>
                            <div class="d-flex justify-content-between mt-1">
                                <span class="badge text-bg-<?= $priorityColors[$p['priority']] ?? 'light' ?>" style="font-size:.65rem">
                                    <?= \App\Models\Project::PRIORITY_LABELS[$p['priority']] ?? $p['priority'] ?>
                                </span>
                                <span style="font-size:.7rem" class="text-muted"><?= $p['progress'] ?>%</span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Formulaire delete (soumis par JS) -->
<form id="formDeleteProject" method="POST" style="display:none">
    <input type="hidden" name="_csrf" value="<?= csrfToken() ?>">
</form>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/src/Views/layouts/main.php'; ?>
