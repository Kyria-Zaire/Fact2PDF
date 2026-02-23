<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — Fact2PDF</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="bg-light d-flex align-items-center" style="min-height:100vh">

<div class="container" style="max-width:400px">
    <div class="text-center mb-4">
        <h1 class="h3 text-primary fw-bold">
            <i class="bi bi-file-earmark-pdf-fill"></i> Fact2PDF
        </h1>
        <p class="text-muted">Gestion comptable</p>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-4">
            <h2 class="card-title h5 mb-4">Connexion</h2>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="/login" novalidate>
                <input type="hidden" name="_csrf" value="<?= csrfToken() ?>">

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" id="email" name="email"
                           class="form-control" required autocomplete="email"
                           value="<?= e($_POST['email'] ?? '') ?>">
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label">Mot de passe</label>
                    <input type="password" id="password" name="password"
                           class="form-control" required autocomplete="current-password">
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    Se connecter
                </button>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
