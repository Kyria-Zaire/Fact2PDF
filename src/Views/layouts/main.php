<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Fact2PDF') ?> — Fact2PDF</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="/dashboard">
            <i class="bi bi-file-earmark-pdf"></i> Fact2PDF
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="/clients"><i class="bi bi-people"></i> Clients</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/invoices"><i class="bi bi-receipt"></i> Factures</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/projects"><i class="bi bi-kanban"></i> Projets</a>
                </li>
                <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="/admin"><i class="bi bi-gear"></i> Admin</a>
                </li>
                <?php endif; ?>
            </ul>
            <div class="navbar-nav align-items-center">
                <!-- Cloche notifications (polling) -->
                <button class="btn btn-link nav-link text-white position-relative me-2" id="btnNotif" title="Notifications">
                    <i class="bi bi-bell fs-5"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none" id="notifBadge">0</span>
                </button>
                <span class="navbar-text me-3 text-white-50">
                    <i class="bi bi-person-circle"></i>
                    <?= e($_SESSION['username'] ?? '') ?>
                    <span class="badge bg-secondary ms-1"><?= e($_SESSION['role'] ?? '') ?></span>
                </span>
                <a class="nav-link text-warning" href="/logout">
                    <i class="bi bi-box-arrow-right"></i> Déconnexion
                </a>
            </div>
        </div>
    </div>
</nav>

<!-- Flash messages -->
<?php if (!empty($_SESSION['flash'])): ?>
<div class="container mt-2">
    <div class="alert alert-<?= e($_SESSION['flash']['type']) ?> alert-dismissible fade show">
        <?= e($_SESSION['flash']['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
</div>
<?php unset($_SESSION['flash']); endif; ?>

<!-- Contenu principal -->
<main class="container my-4">
    <?= $content ?? '' ?>
</main>

<footer class="bg-light border-top py-3 mt-auto">
    <div class="container text-center text-muted small">
        Fact2PDF &copy; <?= date('Y') ?> — Gestion comptable
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Chart.js chargé conditionnellement (lazy) via app.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js" defer></script>
<script src="/assets/js/app.js" defer></script>
</body>
</html>
