<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Fact2PDF') ?> — Fact2PDF</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <?php $v = defined('ROOT_PATH') ? '?v=' . @filemtime(ROOT_PATH . '/public/assets/css/app.css') : ''; ?>
    <link rel="stylesheet" href="/assets/css/app.css<?= $v ?>">
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="/dashboard">
            <i class="bi bi-file-earmark-pdf"></i> Fact2PDF
        </a>
        <!-- Cloche + toggler groupés sur mobile -->
        <div class="d-flex align-items-center gap-1 d-lg-none">
            <button class="btn btn-link nav-link text-white position-relative p-1" id="btnNotifMobile" title="Notifications">
                <i class="bi bi-bell"></i>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none" id="notifBadgeMobile">0</span>
            </button>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>
        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?= str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/clients')  ? 'active' : '' ?>" href="/clients">
                        <i class="bi bi-people"></i> Clients
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/invoices') ? 'active' : '' ?>" href="/invoices">
                        <i class="bi bi-receipt"></i> Factures
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/projects') ? 'active' : '' ?>" href="/projects">
                        <i class="bi bi-kanban"></i> Projets
                    </a>
                </li>
                <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="/admin"><i class="bi bi-gear"></i> Admin</a>
                </li>
                <?php endif; ?>
            </ul>
            <!-- Section utilisateur (séparée visuellement sur mobile) -->
            <div class="navbar-nav navbar-user-section align-items-lg-center">
                <!-- Cloche desktop uniquement -->
                <button class="btn btn-link nav-link text-white position-relative d-none d-lg-inline-flex" id="btnNotif" title="Notifications">
                    <i class="bi bi-bell fs-5"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none" id="notifBadge">0</span>
                </button>
                <span class="nav-link text-white-50 pe-none">
                    <i class="bi bi-person-circle"></i>
                    <?= e($_SESSION['username'] ?? '') ?>
                    <span class="badge bg-secondary ms-1"><?= e($_SESSION['role'] ?? '') ?></span>
                </span>
                <a class="nav-link text-danger" href="/logout" title="Déconnexion">
                    <i class="bi bi-box-arrow-right"></i>
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
<main class="container my-3 my-md-4 px-2 px-sm-3">
    <?= $content ?? '' ?>
</main>

<footer class="bg-light border-top py-3 mt-auto">
    <div class="container text-center text-muted small">
        Fact2PDF &copy; <?= date('Y') ?> — Gestion comptable
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js" defer></script>
<?php $jsV = defined('ROOT_PATH') ? '?v=' . @filemtime(ROOT_PATH . '/public/assets/js/app.js') : ''; ?>
<script src="/assets/js/app.js<?= $jsV ?>" defer></script>
</body>
</html>
