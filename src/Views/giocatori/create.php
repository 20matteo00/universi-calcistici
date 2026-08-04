<?php

/** @var array $errori */
/** @var array $vecchiDati */
/** @var array $paesi */
/** @var array $posizioni */

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crea giocatore</title>
    <?php require __DIR__ . '/../partials/link.php'; ?>
</head>
<body>
    <div class="container py-4">
        <div class="mx-auto">

            <div class="mb-4">
                <a href="/giocatori" class="link-secondary text-decoration-none d-inline-block mb-2">← Torna ai giocatori</a>
                <h1 class="h2 mb-1">Crea giocatore</h1>
                <p class="text-muted mb-0">Inserisci un nuovo giocatore globale da riutilizzare negli universi.</p>
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

                    <form action="/giocatori/crea" method="post" class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="nome" class="form-label">Nome *</label>
                            <input
                                type="text"
                                id="nome"
                                name="nome"
                                class="form-control"
                                value="<?= htmlspecialchars((string) ($vecchiDati['nome'] ?? '')) ?>"
                                required
                            >
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="posizione" class="form-label">Posizione *</label>
                            <select id="posizione" name="posizione" class="form-select" required>
                                <?php foreach ($posizioni as $codice => $etichetta): ?>
                                    <option value="<?= htmlspecialchars($codice) ?>" <?= (($vecchiDati['posizione'] ?? 'CC') === $codice) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($etichetta) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="paese" class="form-label">Paese</label>
                            <select id="paese" name="paese" class="form-select">
                                <option value="">-- Nessuno --</option>
                                <?php foreach ($paesi as $codice => $nomePaese): ?>
                                    <option value="<?= htmlspecialchars($codice) ?>" <?= (($vecchiDati['paese'] ?? '') === $codice) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($nomePaese) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="nascita" class="form-label">Data di nascita</label>
                            <input
                                type="date"
                                id="nascita"
                                name="nascita"
                                class="form-control"
                                value="<?= htmlspecialchars((string) ($vecchiDati['nascita'] ?? '')) ?>"
                            >
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="attacco" class="form-label">Attacco</label>
                            <input
                                type="number"
                                id="attacco"
                                name="attacco"
                                class="form-control"
                                step="0.01"
                                min="0"
                                value="<?= htmlspecialchars((string) ($vecchiDati['attacco'] ?? '0')) ?>"
                            >
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="difesa" class="form-label">Difesa</label>
                            <input
                                type="number"
                                id="difesa"
                                name="difesa"
                                class="form-control"
                                step="0.01"
                                min="0"
                                value="<?= htmlspecialchars((string) ($vecchiDati['difesa'] ?? '0')) ?>"
                            >
                        </div>

                        <div class="col-12">
                            <div class="d-flex flex-column flex-sm-row gap-2 pt-2">
                                <button class="btn btn-primary" type="submit">Salva giocatore</button>
                                <a class="btn btn-outline-secondary" href="/giocatori">Annulla</a>
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