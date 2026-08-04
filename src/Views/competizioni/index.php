<?php

declare(strict_types=1);

use App\Support\CompetitionTypes;

?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Competizioni <?= htmlspecialchars((string) ($universo['Nome'] ?? '')) ?></title>
    <?php require __DIR__ . '/../partials/link.php'; ?>
</head>

<body>
    <div class="container py-4">
        <div class="mb-4">
            <a href="/universi/<?= (int) ($universo['ID'] ?? 0) ?>" class="text-decoration-none">← Torna all'universo</a>
        </div>

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h1 class="h3 mb-1">Competizioni</h1>
                <p class="text-muted mb-0">
                    Universo: <?= htmlspecialchars((string) ($universo['Nome'] ?? '')) ?>
                </p>
            </div>

            <div class="d-flex gap-2">
                <a href="/universi/<?= (int) ($universo['ID'] ?? 0) ?>/competizioni/create" class="btn btn-primary">
                    Nuova competizione
                </a>
                <a href="/universi/<?= (int) ($universo['ID'] ?? 0) ?>/competizioni/collegamenti/create" class="btn btn-outline-primary">
                    Crea collegamento tra competizioni
                </a>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="get" action="/universi/<?= (int) ($universo['ID'] ?? 0) ?>/competizioni" class="row g-3">
                    <div class="col-md-4">
                        <label for="q" class="form-label">Ricerca</label>
                        <input
                            type="text"
                            class="form-control"
                            id="q"
                            name="q"
                            value="<?= htmlspecialchars((string) ($filtri['q'] ?? '')) ?>"
                            placeholder="Nome competizione">
                    </div>

                    <div class="col-md-3">
                        <label for="tipo" class="form-label">Tipo</label>
                        <?php $tipoFiltro = (string) ($filtri['tipo'] ?? ''); ?>
                        <select class="form-select" id="tipo" name="tipo">
                            <option value="" <?= $tipoFiltro === '' ? 'selected' : '' ?>>Tutti</option>
                            <?php foreach (CompetitionTypes::all() as $value => $label): ?>
                                <option value="<?= htmlspecialchars($value) ?>" <?= $tipoFiltro === $value ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="sort" class="form-label">Ordina per</label>
                        <select class="form-select" id="sort" name="sort">
                            <?php
                            $sortOptions = [
                                'ID' => 'ID',
                                'NomeCompetizione' => 'Nome',
                                'Tipo' => 'Tipo',
                                'NumeroPartecipanti' => 'Partecipanti',
                                'Creato' => 'Creato',
                                'Modificato' => 'Modificato',
                            ];
                            ?>
                            <?php foreach ($sortOptions as $value => $label): ?>
                                <option value="<?= htmlspecialchars($value) ?>" <?= (string) ($filtri['sort'] ?? '') === $value ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="dir" class="form-label">Direzione</label>
                        <select class="form-select" id="dir" name="dir">
                            <option value="asc" <?= (string) ($filtri['dir'] ?? '') === 'asc' ? 'selected' : '' ?>>ASC</option>
                            <option value="desc" <?= (string) ($filtri['dir'] ?? '') === 'desc' ? 'selected' : '' ?>>DESC</option>
                        </select>
                    </div>

                    <div class="col-12 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Filtra</button>
                        <a href="/universi/<?= (int) ($universo['ID'] ?? 0) ?>/competizioni" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <?php if (empty($competizioni)): ?>
                    <p class="text-muted mb-0">Nessuna competizione presente in questo universo.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nome</th>
                                    <th>Tipo</th>
                                    <th>Partecipanti</th>
                                    <th>Struttura</th>
                                    <th>Creato</th>
                                    <th>Modificato</th>
                                    <th class="text-end">Azioni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($competizioni as $competizione): ?>
                                    <?php
                                    $struttura = (string) ($competizione['Struttura'] ?? '');
                                    $tipo = (string) ($competizione['Tipo'] ?? '');
                                    $numeroPartecipanti = (int) ($competizione['NumeroPartecipanti'] ?? 0);
                                    ?>
                                    <tr>
                                        <td><?= (int) ($competizione['ID'] ?? 0) ?></td>
                                        <td><?= htmlspecialchars((string) ($competizione['NomeCompetizione'] ?? '')) ?></td>
                                        <td><?= htmlspecialchars(CompetitionTypes::label($tipo)) ?></td>
                                        <td><?= $numeroPartecipanti ?></td>
                                        <td>
                                            <?php if ($struttura === ''): ?>
                                                <span class="text-muted">Vuota</span>
                                            <?php else: ?>
                                                <span class="badge text-bg-success">JSON</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars((string) ($competizione['Creato'] ?? '')) ?></td>
                                        <td><?= htmlspecialchars((string) ($competizione['Modificato'] ?? '')) ?></td>
                                        <td class="text-end">
                                            <div class="d-flex justify-content-end gap-2">
                                                

                                                <a class="btn btn-sm btn-outline-primary" href="/universi/<?= (int) ($universo['ID'] ?? 0) ?>/competizioni/<?= (int) ($competizione['ID'] ?? 0) ?>">
                                                    Apri
                                                </a>

                                                <a
                                                    href="/universi/<?= (int) ($universo['ID'] ?? 0) ?>/competizioni/<?= (int) ($competizione['ID'] ?? 0) ?>/edit"
                                                    class="btn btn-sm btn-outline-secondary">
                                                    Modifica
                                                </a>

                                                <form
                                                    method="post"
                                                    action="/universi/<?= (int) ($universo['ID'] ?? 0) ?>/competizioni/<?= (int) ($competizione['ID'] ?? 0) ?>/delete"
                                                    onsubmit="return confirm('Eliminare questa competizione?');">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">Elimina</button>
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

        <div class="card shadow-sm mt-4">
            <div class="card-body">
                <h2 class="h5 mb-3">Collegamenti tra competizioni</h2>

                <?php if (empty($collegamenti)): ?>
                    <p class="text-muted mb-0">Nessun collegamento presente in questo universo.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Ordine</th>
                                    <th>Partenza</th>
                                    <th>Arrivo</th>
                                    <th>Dettagli</th>
                                    <th>Creato</th>
                                    <th>Modificato</th>
                                    <th class="text-end">Azioni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($collegamenti as $collegamento): ?>
                                    <?php $dettagli = trim((string) ($collegamento['Dettagli'] ?? '')); ?>
                                    <tr>
                                        <td><?= (int) ($collegamento['ID'] ?? 0) ?></td>
                                        <td><?= (int) ($collegamento['Ordine'] ?? 0) ?></td>
                                        <td>
                                            <?= htmlspecialchars((string) ($collegamento['CompetizionePartenzaNome'] ?? '')) ?>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars((string) ($collegamento['CompetizioneArrivoNome'] ?? '')) ?>
                                        </td>
                                        <td>
                                            <?php if ($dettagli === ''): ?>
                                                <span class="text-muted">Vuoti</span>
                                            <?php else: ?>
                                                <span class="badge text-bg-success">JSON</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars((string) ($collegamento['Creato'] ?? '')) ?></td>
                                        <td><?= htmlspecialchars((string) ($collegamento['Modificato'] ?? '')) ?></td>
                                        <td class="text-end">
                                            <div class="d-flex justify-content-end gap-2">
                                                <a
                                                    href="/universi/<?= (int) ($universo['ID'] ?? 0) ?>/competizioni/collegamenti/<?= (int) ($collegamento['ID'] ?? 0) ?>/edit"
                                                    class="btn btn-sm btn-outline-secondary">
                                                    Modifica
                                                </a>

                                                <form
                                                    method="post"
                                                    action="/universi/<?= (int) ($universo['ID'] ?? 0) ?>/competizioni/collegamenti/<?= (int) ($collegamento['ID'] ?? 0) ?>/delete"
                                                    onsubmit="return confirm('Eliminare questo collegamento?');">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">Elimina</button>
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