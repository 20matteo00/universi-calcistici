<?php

/** @var array $universi */
/** @var array $filtri */

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Universi</title>
    <?php require __DIR__ . '/../partials/link.php'; ?>
</head>
<body>
    <div class="container py-4">
        <div class="d-flex flex-column flex-lg-row align-items-lg-start justify-content-between gap-3 mb-4">
            <div>
                <p class="mb-2">
                    <a href="/" class="link-secondary text-decoration-none">← Home</a>
                </p>
                <h1 class="h2 mb-1">Universi</h1>
                <p class="text-muted mb-0">Gestione degli universi calcistici creati nel sistema.</p>
            </div>

            <div>
                <a class="btn btn-primary" href="/universi/crea">Nuovo universo</a>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <form method="get" action="/universi" class="row g-3 align-items-end">
                    <div class="col-12 col-md-8 col-xl-4">
                        <label for="q" class="form-label">Cerca</label>
                        <input
                            type="text"
                            id="q"
                            name="q"
                            class="form-control"
                            placeholder="Cerca nome universo..."
                            value="<?= htmlspecialchars((string) ($filtri['q'] ?? '')) ?>"
                        >
                    </div>

                    <div class="col-6 col-md-2 col-xl-2">
                        <label for="sort" class="form-label">Ordina per</label>
                        <select id="sort" name="sort" class="form-select">
                            <option value="Creato" <?= (($filtri['sort'] ?? 'Creato') === 'Creato') ? 'selected' : '' ?>>Creato</option>
                            <option value="Nome" <?= (($filtri['sort'] ?? '') === 'Nome') ? 'selected' : '' ?>>Nome</option>
                            <option value="Modificato" <?= (($filtri['sort'] ?? '') === 'Modificato') ? 'selected' : '' ?>>Modificato</option>
                            <option value="ID" <?= (($filtri['sort'] ?? '') === 'ID') ? 'selected' : '' ?>>ID</option>
                        </select>
                    </div>

                    <div class="col-6 col-md-2 col-xl-2">
                        <label for="dir" class="form-label">Direzione</label>
                        <select id="dir" name="dir" class="form-select">
                            <option value="desc" <?= (($filtri['dir'] ?? 'desc') === 'desc') ? 'selected' : '' ?>>Desc</option>
                            <option value="asc" <?= (($filtri['dir'] ?? '') === 'asc') ? 'selected' : '' ?>>Asc</option>
                        </select>
                    </div>

                    <div class="col-12 col-xl-4">
                        <div class="d-flex flex-column flex-sm-row gap-2">
                            <button class="btn btn-primary" type="submit">Applica</button>
                            <a class="btn btn-outline-secondary" href="/universi">Reset</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <?php if (empty($universi)): ?>
                    <div class="text-center py-5">
                        <h2 class="h5 mb-2">Nessun universo presente</h2>
                        <p class="text-muted mb-3">Crea il primo universo per iniziare a costruire il tuo ecosistema calcistico.</p>
                        <a class="btn btn-primary" href="/universi/crea">Crea universo</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Nome</th>
                                    <th>Descrizione</th>
                                    <th>Creato</th>
                                    <th>Modificato</th>
                                    <th class="text-end">Azioni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($universi as $universo): ?>
                                    <tr>
                                        <td><?= (int) ($universo['ID'] ?? 0) ?></td>
                                        <td class="fw-semibold">
                                            <?= htmlspecialchars((string) ($universo['Nome'] ?? '')) ?>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars((string) ($universo['Descrizione'] ?? '')) ?>
                                        </td>
                                        <td class="text-muted small">
                                            <?= htmlspecialchars((string) ($universo['Creato'] ?? '-')) ?>
                                        </td>
                                        <td class="text-muted small">
                                            <?= htmlspecialchars((string) ($universo['Modificato'] ?? '-')) ?>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-inline-flex flex-wrap justify-content-end gap-2">
                                                <a class="btn btn-sm btn-outline-primary" href="/universi/<?= (int) ($universo['ID'] ?? 0) ?>">
                                                    Apri
                                                </a>
                                                <a class="btn btn-sm btn-outline-secondary" href="/universi/modifica/<?= (int) ($universo['ID'] ?? 0) ?>">
                                                    Modifica
                                                </a>
                                                <form action="/universi/elimina/<?= (int) ($universo['ID'] ?? 0) ?>" method="post" class="d-inline" onsubmit="return confirm('Eliminare questo universo?');">
                                                    <button class="btn btn-sm btn-outline-danger" type="submit">
                                                        Elimina
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php require __DIR__ . '/../partials/script.php'; ?>
</body>
</html>