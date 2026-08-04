<?php

/** @var array $universo */
/** @var array $errori */
/** @var array $vecchiDati */

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifica universo</title>
    <?php require __DIR__ . '/../partials/link.php'; ?>
</head>
<body>
    <div class="container py-4">
        <div class="mx-auto">

            <div class="mb-4">
                <a href="/universi" class="link-secondary text-decoration-none d-inline-block mb-2">← Torna agli universi</a>
                <h1 class="h2 mb-1">Modifica universo</h1>
                <p class="text-muted mb-0">
                    Stai modificando l'universo ID <?= (int) ($universo['ID'] ?? 0) ?>.
                </p>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <?php if (!empty($errori)): ?>
                        <div class="alert alert-danger" role="alert">
                            <div class="fw-semibold mb-2">Ci sono errori nel form:</div>
                            <ul class="mb-0 ps-3">
                                <?php foreach ($errori as $errore): ?>
                                    <li><?= htmlspecialchars($errore) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form action="/universi/modifica/<?= (int) ($universo['ID'] ?? 0) ?>" method="post" class="row g-3">
                        <div class="col-12">
                            <label for="nome" class="form-label">Nome *</label>
                            <input
                                type="text"
                                id="nome"
                                name="nome"
                                class="form-control"
                                value="<?= htmlspecialchars((string) ($vecchiDati['nome'] ?? '')) ?>"
                                maxlength="150"
                                required
                            >
                        </div>

                        <div class="col-12">
                            <label for="descrizione" class="form-label">Descrizione</label>
                            <textarea
                                id="descrizione"
                                name="descrizione"
                                class="form-control"
                                rows="6"
                            ><?= htmlspecialchars((string) ($vecchiDati['descrizione'] ?? '')) ?></textarea>
                        </div>

                        <div class="col-12">
                            <div class="d-flex flex-column flex-sm-row gap-2 pt-2">
                                <button class="btn btn-primary" type="submit">Aggiorna universo</button>
                                <a class="btn btn-outline-secondary" href="/universi">Annulla</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>

    <?php require __DIR__ . '/../partials/script.php'; ?>
</body>
</html>