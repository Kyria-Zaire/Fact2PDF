<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Connexion — Fact2PDF</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/app.css">
    <style>
        .login-page { min-height: 100vh; min-height: 100dvh; padding: 1rem 0; box-sizing: border-box; }
        .login-container { max-width: 400px; padding-left: 1rem; padding-right: 1rem; }
    </style>
</head>
<body class="bg-light d-flex align-items-center login-page">

<div class="container login-container mx-auto">
    <div class="text-center mb-4">
        <h1 class="h3 text-primary fw-bold">
            <i class="bi bi-file-earmark-pdf-fill"></i> Fact2PDF
        </h1>
        <p class="text-muted">Gestion comptable</p>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-3 p-sm-4">
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

                <button type="submit" class="btn btn-primary w-100" style="min-height:44px">
                    Se connecter
                </button>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
