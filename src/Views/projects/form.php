<?php
$pageTitle = $project ? 'Modifier le projet' : 'Nouveau projet';
$steps = $project['timeline'] ?? [];
$steps = array_values($steps);
if (count($steps) < 5) {
    $steps = array_pad($steps, 5, ['label' => '', 'date' => '', 'done' => false]);
}
?>
<?php ob_start(); ?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h1 class="h4 mb-0">
        <?= $project ? '<i class="bi bi-pencil"></i> Modifier le projet' : '<i class="bi bi-plus-lg"></i> Nouveau projet' ?>
    </h1>
    <a href="/projects" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Retour</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?= e($action) ?>">
            <input type="hidden" name="_csrf" value="<?= csrfToken() ?>">

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label">Client <span class="text-danger">*</span></label>
                    <select name="client_id" class="form-select" required>
                        <option value="">— Choisir un client —</option>
                        <?php foreach ($clients as $c): ?>
                        <option value="<?= (int) $c['id'] ?>" <?= (isset($project['client_id']) && (int)$project['client_id'] === (int)$c['id']) ? 'selected' : '' ?>>
                            <?= e($c['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Nom du projet <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required
                           value="<?= e($project['name'] ?? '') ?>"
                           placeholder="Ex. Refonte site web">
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="2" placeholder="Description optionnelle"><?= e($project['description'] ?? '') ?></textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Statut</label>
                    <select name="status" class="form-select">
                        <?php foreach (\App\Models\Project::STATUS_LABELS as $val => $label): ?>
                        <option value="<?= e($val) ?>" <?= (($project['status'] ?? 'todo') === $val) ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Priorité</label>
                    <select name="priority" class="form-select">
                        <?php foreach (\App\Models\Project::PRIORITY_LABELS as $val => $label): ?>
                        <option value="<?= e($val) ?>" <?= (($project['priority'] ?? 'medium') === $val) ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Début</label>
                    <input type="date" name="start_date" class="form-control" value="<?= e($project['start_date'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Échéance</label>
                    <input type="date" name="end_date" class="form-control" value="<?= e($project['end_date'] ?? '') ?>">
                </div>
            </div>

            <h6 class="mb-2">Étapes / Timeline (optionnel)</h6>
            <p class="text-muted small mb-3">Ajoutez des étapes pour suivre l'avancement. Cochez « Fait » quand une étape est terminée.</p>
            <div class="table-responsive">
                <table class="table table-sm align-middle" id="timelineSteps">
                    <thead class="table-light">
                        <tr>
                            <th>Étape</th>
                            <th>Date</th>
                            <th class="text-center" style="width:80px">Fait</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($steps as $i => $step): ?>
                        <tr>
                            <td>
                                <input type="text" name="steps[<?= $i ?>][label]" class="form-control form-control-sm"
                                       value="<?= e($step['label'] ?? '') ?>"
                                       placeholder="Libellé de l'étape">
                            </td>
                            <td>
                                <input type="date" name="steps[<?= $i ?>][date]" class="form-control form-control-sm"
                                       value="<?= e($step['date'] ?? '') ?>">
                            </td>
                            <td class="text-center">
                                <input type="hidden" name="steps[<?= $i ?>][done]" value="0">
                                <input type="checkbox" name="steps[<?= $i ?>][done]" value="1" class="form-check-input"
                                    <?= !empty($step['done']) ? 'checked' : '' ?>>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="small text-muted">Les étapes avec un libellé vide sont ignorées à l'enregistrement.</p>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">
                    <?= $project ? 'Enregistrer' : 'Créer le projet' ?>
                </button>
                <a href="/projects" class="btn btn-outline-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/src/Views/layouts/main.php'; ?>
