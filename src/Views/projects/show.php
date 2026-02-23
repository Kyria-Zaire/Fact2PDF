<?php
$pageTitle = e($project['name'] ?? 'Projet');
$statColors = [
    'todo' => 'secondary',
    'in_progress' => 'primary',
    'review' => 'warning',
    'done' => 'success',
    'archived' => 'light text-dark',
];
$priorityColors = [
    'low' => 'light text-dark',
    'medium' => 'info text-dark',
    'high' => 'warning text-dark',
    'critical' => 'danger',
];
$statusLabels = \App\Models\Project::STATUS_LABELS;
$priorityLabels = \App\Models\Project::PRIORITY_LABELS;
?>
<?php ob_start(); ?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="/projects">Projets</a></li>
                <li class="breadcrumb-item active"><?= e($project['name']) ?></li>
            </ol>
        </nav>
        <h1 class="h4 mb-0"><?= e($project['name']) ?></h1>
        <div class="text-muted small mt-1">
            Client : <a href="/clients/<?= (int)($project['client_id']) ?>"><?= e($client['name'] ?? '—') ?></a>
            <?php if (!empty($project['invoice_id'])): ?>
                — Facture : <a href="/invoices/<?= (int)$project['invoice_id'] ?>"><?= e($project['invoice_number'] ?? '#' . $project['invoice_id']) ?></a>
            <?php endif; ?>
        </div>
    </div>
    <div class="d-flex gap-2">
        <?php if (in_array($_SESSION['role'] ?? '', ['admin', 'user'])): ?>
        <a href="/projects/<?= (int)$project['id'] ?>/edit" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-pencil"></i> Modifier
        </a>
        <?php endif; ?>
        <a href="/projects" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Retour</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <?php if (!empty($project['description'])): ?>
        <div class="card mb-3">
            <div class="card-header">Description</div>
            <div class="card-body"><?= nl2br(e($project['description'])) ?></div>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Timeline / Étapes</span>
                <span class="badge bg-primary"><?= (int)($project['progress'] ?? 0) ?> %</span>
            </div>
            <div class="card-body">
                <?php if (empty($project['timeline'])): ?>
                <p class="text-muted mb-0">Aucune étape définie. <a href="/projects/<?= (int)$project['id'] ?>/edit">Modifier le projet</a> pour en ajouter.</p>
                <?php else: ?>
                <div class="progress mb-3" style="height:8px">
                    <div class="progress-bar bg-primary" role="progressbar" style="width:<?= (int)($project['progress']) ?>%"></div>
                </div>
                <ul class="list-group list-group-flush">
                    <?php foreach ($project['timeline'] as $step): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><?= e($step['label'] ?? '—') ?></span>
                        <span>
                            <?php if (!empty($step['date'])): ?><span class="text-muted small me-2"><?= e($step['date']) ?></span><?php endif; ?>
                            <?php if (!empty($step['done'])): ?>
                            <span class="badge text-bg-success"><i class="bi bi-check"></i> Fait</span>
                            <?php else: ?>
                            <span class="badge text-bg-secondary">À faire</span>
                            <?php endif; ?>
                        </span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">Informations</div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-muted">Statut</span>
                    <span class="badge text-bg-<?= $statColors[$project['status']] ?? 'secondary' ?>"><?= e($statusLabels[$project['status']] ?? $project['status']) ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-muted">Priorité</span>
                    <span class="badge text-bg-<?= $priorityColors[$project['priority']] ?? 'light' ?>"><?= e($priorityLabels[$project['priority']] ?? $project['priority']) ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-muted">Début</span>
                    <span><?= !empty($project['start_date']) ? formatDate($project['start_date']) : '—' ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-muted">Échéance</span>
                    <span class="<?= (!empty($project['end_date']) && strtotime($project['end_date']) < time() && !in_array($project['status'], ['done', 'archived'], true)) ? 'text-danger fw-semibold' : '' ?>">
                        <?= !empty($project['end_date']) ? formatDate($project['end_date']) : '—' ?>
                    </span>
                </li>
            </ul>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/src/Views/layouts/main.php'; ?>
