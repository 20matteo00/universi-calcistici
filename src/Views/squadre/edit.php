<?php

/** @var array $squadra */
/** @var array $errori */
/** @var array $vecchiDati */
/** @var array $paesi */

$coloreSfondo = trim((string) ($vecchiDati['colore_sfondo'] ?? ''));
$coloreTesto = trim((string) ($vecchiDati['colore_testo'] ?? ''));
$coloreBordo = trim((string) ($vecchiDati['colore_bordo'] ?? ''));

$coloreSfondo = $coloreSfondo !== '' ? $coloreSfondo : '#ffffff';
$coloreTesto = $coloreTesto !== '' ? $coloreTesto : '#000000';
$coloreBordo = $coloreBordo !== '' ? $coloreBordo : '#ffffff';

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifica squadra</title>
    <?php require __DIR__ . '/../partials/link.php'; ?>
</head>
<body>
    <div class="container py-4">
        <div class="mx-auto">

            <div class="mb-4">
                <a href="/squadre" class="link-secondary text-decoration-none d-inline-block mb-2">← Torna alle squadre</a>
                <h1 class="h2 mb-1">Modifica squadra</h1>
                <p class="text-muted mb-0">Stai modificando la squadra ID <?= (int) ($squadra['ID'] ?? 0) ?>.</p>
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

                    <form action="/squadre/modifica/<?= (int) ($squadra['ID'] ?? 0) ?>" method="post" class="row g-3">
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
                            <label for="tipo" class="form-label">Tipo</label>
                            <select id="tipo" name="tipo" class="form-select">
                                <option value="Club" <?= (($vecchiDati['tipo'] ?? 'Club') === 'Club') ? 'selected' : '' ?>>Club</option>
                                <option value="Nazionale" <?= (($vecchiDati['tipo'] ?? '') === 'Nazionale') ? 'selected' : '' ?>>Nazionale</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="valore" class="form-label">Valore</label>
                            <input
                                type="number"
                                id="valore"
                                name="valore"
                                class="form-control"
                                step="0.01"
                                min="0"
                                value="<?= htmlspecialchars((string) ($vecchiDati['valore'] ?? '0')) ?>"
                            >
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="fattore_casa" class="form-label">Fattore casa</label>
                            <input
                                type="number"
                                id="fattore_casa"
                                name="fattore_casa"
                                class="form-control"
                                step="0.01"
                                min="0"
                                value="<?= htmlspecialchars((string) ($vecchiDati['fattore_casa'] ?? '0')) ?>"
                            >
                        </div>

                        <div class="col-12">
                            <hr class="my-2">
                            <h2 class="h5 mb-3">Colori squadra</h2>
                        </div>

                        <div class="col-12 col-md-4">
                            <label for="colore_sfondo" class="form-label">Colore sfondo</label>
                            <input
                                type="color"
                                id="colore_sfondo"
                                name="colore_sfondo"
                                class="form-control form-control-color"
                                value="<?= htmlspecialchars($coloreSfondo) ?>"
                                title="Scegli il colore di sfondo"
                            >
                        </div>

                        <div class="col-12 col-md-4">
                            <label for="colore_testo" class="form-label">Colore testo</label>
                            <input
                                type="color"
                                id="colore_testo"
                                name="colore_testo"
                                class="form-control form-control-color"
                                value="<?= htmlspecialchars($coloreTesto) ?>"
                                title="Scegli il colore del testo"
                            >
                        </div>

                        <div class="col-12 col-md-4">
                            <label for="colore_bordo" class="form-label">Colore bordo</label>
                            <input
                                type="color"
                                id="colore_bordo"
                                name="colore_bordo"
                                class="form-control form-control-color"
                                value="<?= htmlspecialchars($coloreBordo) ?>"
                                title="Scegli il colore del bordo"
                            >
                        </div>

                        <div class="col-12">
                            <div class="d-flex flex-column flex-sm-row gap-2 pt-2">
                                <button class="btn btn-primary" type="submit">Aggiorna squadra</button>
                                <a class="btn btn-outline-secondary" href="/squadre">Annulla</a>
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