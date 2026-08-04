<?php

use App\Support\Countries;
use App\Support\Positions;

/** @var array $universo */
/** @var bool $haEdizioni */
/** @var array $filtri */
/** @var array $disponibili */
/** @var array $giocatoriUniverso */
/** @var array $paesi */
/** @var array $posizioni */

$righe = $disponibili['righe'] ?? [];
$totale = (int) ($disponibili['totale'] ?? 0);
$page = (int) ($disponibili['page'] ?? 1);
$perPage = (int) ($disponibili['per_page'] ?? 25);
$pagineTotali = (int) ($disponibili['pagine_totali'] ?? 1);

?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestisci giocatori - <?= htmlspecialchars((string) ($universo['Nome'] ?? 'Universo')) ?></title>
    <?php require __DIR__ . '/../partials/link.php'; ?>
</head>

<body>
    <div class="container py-4">
        <div class="mx-auto">

            <div class="d-flex flex-column flex-lg-row align-items-lg-start justify-content-between gap-3 mb-4">
                <div>
                    <a href="/universi/<?= (int) ($universo['ID'] ?? 0) ?>" class="link-secondary text-decoration-none d-inline-block mb-2">
                        ← Torna all'universo
                    </a>
                    <h1 class="h2 mb-1">Gestisci giocatori</h1>
                    <p class="text-muted mb-0">
                        Universo: <?= htmlspecialchars((string) ($universo['Nome'] ?? '')) ?>
                    </p>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-outline-secondary" href="/universi/<?= (int) ($universo['ID'] ?? 0) ?>">
                        Dettaglio universo
                    </a>
                </div>
            </div>

            <?php if ($haEdizioni): ?>
                <div class="alert alert-warning">
                    Questo universo ha già almeno un'edizione: non puoi più aggiungere o rimuovere giocatori.
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <div class="col-12 col-xl-8">
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-2 mb-3">
                                <div>
                                    <h2 class="h5 mb-1">Giocatori disponibili</h2>
                                    <p class="text-muted mb-0">Filtra e seleziona più giocatori da aggiungere all'universo.</p>
                                </div>
                                <span class="badge text-bg-secondary"><?= $totale ?> risultati</span>
                            </div>

                            <form method="get" action="/universi/<?= (int) ($universo['ID'] ?? 0) ?>/giocatori" class="row g-3 align-items-end mb-3">
                                <div class="col-auto">
                                    <label for="q" class="form-label">Cerca</label>
                                    <input
                                        type="text"
                                        id="q"
                                        name="q"
                                        class="form-control"
                                        placeholder="Nome giocatore..."
                                        value="<?= htmlspecialchars((string) ($filtri['q'] ?? '')) ?>">
                                </div>

                                <div class="col-auto">
                                    <label for="paese" class="form-label">Paese</label>
                                    <select id="paese" name="paese" class="form-select">
                                        <option value="">Tutti i paesi</option>
                                        <?php foreach ($paesi as $codice => $nomePaese): ?>
                                            <option value="<?= htmlspecialchars($codice) ?>" <?= (($filtri['paese'] ?? '') === $codice) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($nomePaese) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-auto">
                                    <label for="posizione" class="form-label">Posizione</label>
                                    <select id="posizione" name="posizione" class="form-select">
                                        <option value="">Tutte le posizioni</option>
                                        <?php foreach ($posizioni as $codice => $label): ?>
                                            <option value="<?= htmlspecialchars($codice) ?>" <?= (($filtri['posizione'] ?? '') === $codice) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($label) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-auto">
                                    <label for="sort" class="form-label">Sort</label>
                                    <select id="sort" name="sort" class="form-select">
                                        <option value="ID" <?= (($filtri['sort'] ?? '') === 'ID') ? 'selected' : '' ?>>ID</option>
                                        <option value="Nome" <?= (($filtri['sort'] ?? '') === 'Nome') ? 'selected' : '' ?>>Nome</option>
                                        <option value="Posizione" <?= (($filtri['sort'] ?? '') === 'Posizione') ? 'selected' : '' ?>>Posizione</option>
                                        <option value="Attacco" <?= (($filtri['sort'] ?? '') === 'Attacco') ? 'selected' : '' ?>>Attacco</option>
                                        <option value="Difesa" <?= (($filtri['sort'] ?? '') === 'Difesa') ? 'selected' : '' ?>>Difesa</option>
                                        <option value="Paese" <?= (($filtri['sort'] ?? '') === 'Paese') ? 'selected' : '' ?>>Paese</option>
                                        <option value="Nascita" <?= (($filtri['sort'] ?? '') === 'Nascita') ? 'selected' : '' ?>>Nascita</option>
                                        <option value="Creato" <?= (($filtri['sort'] ?? '') === 'Creato') ? 'selected' : '' ?>>Creato</option>
                                    </select>
                                </div>

                                <div class="col-auto">
                                    <label for="dir" class="form-label">Dir</label>
                                    <select id="dir" name="dir" class="form-select">
                                        <option value="asc" <?= (($filtri['dir'] ?? '') === 'asc') ? 'selected' : '' ?>>Asc</option>
                                        <option value="desc" <?= (($filtri['dir'] ?? '') === 'desc') ? 'selected' : '' ?>>Desc</option>
                                    </select>
                                </div>

                                <div class="col-auto">
                                    <label for="per_page" class="form-label">Per pagina</label>
                                    <select id="per_page" name="per_page" class="form-select">
                                        <option value="10" <?= ((string) ($filtri['per_page'] ?? '') === '10') ? 'selected' : '' ?>>10</option>
                                        <option value="25" <?= ((string) ($filtri['per_page'] ?? '25') === '25') ? 'selected' : '' ?>>25</option>
                                        <option value="50" <?= ((string) ($filtri['per_page'] ?? '') === '50') ? 'selected' : '' ?>>50</option>
                                        <option value="100" <?= ((string) ($filtri['per_page'] ?? '') === '100') ? 'selected' : '' ?>>100</option>
                                        <option value="200" <?= ((string) ($filtri['per_page'] ?? '') === '200') ? 'selected' : '' ?>>200</option>
                                        <option value="500" <?= ((string) ($filtri['per_page'] ?? '') === '500') ? 'selected' : '' ?>>500</option>
                                        <option value="1000" <?= ((string) ($filtri['per_page'] ?? '') === '1000') ? 'selected' : '' ?>>1000</option>
                                    </select>
                                </div>

                                <div class="col-auto">
                                    <div class="d-flex flex-wrap gap-2">
                                        <button type="submit" class="btn btn-primary">Applica filtri</button>
                                        <a href="/universi/<?= (int) ($universo['ID'] ?? 0) ?>/giocatori" class="btn btn-outline-secondary">Reset</a>
                                    </div>
                                </div>
                            </form>

                            <?php if (empty($righe)): ?>
                                <p class="text-muted mb-0">Nessun giocatore disponibile con i filtri correnti.</p>
                            <?php else: ?>
                                <form action="/universi/<?= (int) ($universo['ID'] ?? 0) ?>/giocatori/aggiungi-selezionati" method="post">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" data-bulk-toggle="giocatori-disponibili" id="checkAllGiocatori">
                                            <label class="form-check-label" for="checkAllGiocatori">
                                                Seleziona tutti i visibili
                                            </label>
                                        </div>

                                        <?php if (!$haEdizioni): ?>
                                            <button type="submit" class="btn btn-primary">Aggiungi selezionati</button>
                                        <?php endif; ?>
                                    </div>

                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width: 44px;"></th>
                                                    <th>ID</th>
                                                    <th>Nome</th>
                                                    <th>Posizione</th>
                                                    <th>Attacco</th>
                                                    <th>Difesa</th>
                                                    <th>Paese</th>
                                                    <th>Nascita</th>
                                                    <th>Creato</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($righe as $giocatore): ?>
                                                    <?php
                                                    $codicePaese = (string) ($giocatore['Paese'] ?? '');
                                                    $nomePaese = $codicePaese !== '' ? Countries::nameFromCode($codicePaese) : '-';
                                                    $codicePosizione = (string) ($giocatore['Posizione'] ?? '');
                                                    $nomePosizione = Positions::label($codicePosizione);
                                                    ?>
                                                    <tr>
                                                        <td>
                                                            <input
                                                                type="checkbox"
                                                                class="form-check-input"
                                                                name="ids[]"
                                                                value="<?= (int) ($giocatore['ID'] ?? 0) ?>"
                                                                data-bulk-item="giocatori-disponibili"
                                                                <?= $haEdizioni ? 'disabled' : '' ?>>
                                                        </td>
                                                        <td><?= (int) ($giocatore['ID'] ?? 0) ?></td>
                                                        <td class="fw-semibold"><?= htmlspecialchars((string) ($giocatore['Nome'] ?? '')) ?></td>
                                                        <td><?= htmlspecialchars($nomePosizione !== '' ? $nomePosizione : '-') ?></td>
                                                        <td><?= htmlspecialchars((string) ($giocatore['Attacco'] ?? '0')) ?></td>
                                                        <td><?= htmlspecialchars((string) ($giocatore['Difesa'] ?? '0')) ?></td>
                                                        <td><?= htmlspecialchars($nomePaese) ?></td>
                                                        <td><?= htmlspecialchars((string) ($giocatore['Nascita'] ?? '-')) ?></td>
                                                        <td class="text-muted small"><?= htmlspecialchars((string) ($giocatore['Creato'] ?? '-')) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </form>

                                <?php if ($pagineTotali > 1): ?>
                                    <nav aria-label="Paginazione giocatori disponibili">
                                        <ul class="pagination mb-0 flex-wrap">
                                            <?php for ($i = 1; $i <= $pagineTotali; $i++): ?>
                                                <?php
                                                $query = http_build_query([
                                                    'q' => $filtri['q'] ?? '',
                                                    'paese' => $filtri['paese'] ?? '',
                                                    'posizione' => $filtri['posizione'] ?? '',
                                                    'sort' => $filtri['sort'] ?? 'ID',
                                                    'dir' => $filtri['dir'] ?? 'asc',
                                                    'per_page' => $filtri['per_page'] ?? 25,
                                                    'page' => $i,
                                                ]);
                                                ?>
                                                <?php if ($i === $page): ?>
                                                    <li class="page-item active"><span class="page-link"><?= $i ?></span></li>
                                                <?php else: ?>
                                                    <li class="page-item">
                                                        <a class="page-link" href="/universi/<?= (int) ($universo['ID'] ?? 0) ?>/giocatori?<?= htmlspecialchars($query) ?>">
                                                            <?= $i ?>
                                                        </a>
                                                    </li>
                                                <?php endif; ?>
                                            <?php endfor; ?>
                                        </ul>
                                    </nav>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-4">
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                                <div>
                                    <h2 class="h5 mb-1">Già nell'universo</h2>
                                    <p class="text-muted mb-0">Giocatori già collegati a questo universo.</p>
                                </div>
                                <span class="badge text-bg-secondary"><?= count($giocatoriUniverso) ?></span>
                            </div>

                            <?php if (empty($giocatoriUniverso)): ?>
                                <p class="text-muted mb-0">Nessun giocatore ancora collegato.</p>
                            <?php else: ?>
                                <form action="/universi/<?= (int) ($universo['ID'] ?? 0) ?>/giocatori/rimuovi-selezionati" method="post" onsubmit="return confirm('Rimuovere i giocatori selezionati dall\'universo?');">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" data-bulk-toggle="giocatori-universo" id="checkAllGiocatoriUniverso" <?= $haEdizioni ? 'disabled' : '' ?>>
                                            <label class="form-check-label" for="checkAllGiocatoriUniverso">
                                                Seleziona tutti
                                            </label>
                                        </div>

                                        <?php if (!$haEdizioni): ?>
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Rimuovi selezionati</button>
                                        <?php endif; ?>
                                    </div>

                                    <div class="list-group list-group-flush">
                                        <?php foreach ($giocatoriUniverso as $giocatore): ?>
                                            <?php
                                            $codicePaese = (string) ($giocatore['Paese'] ?? '');
                                            $nomePaese = $codicePaese !== '' ? Countries::nameFromCode($codicePaese) : '-';
                                            $codicePosizione = (string) ($giocatore['Posizione'] ?? '');
                                            $nomePosizione = Positions::label($codicePosizione);
                                            ?>
                                            <label class="list-group-item px-0">
                                                <div class="d-flex align-items-start gap-3">
                                                    <div class="pt-1">
                                                        <input
                                                            type="checkbox"
                                                            class="form-check-input"
                                                            name="ids[]"
                                                            value="<?= (int) ($giocatore['ID'] ?? 0) ?>"
                                                            data-bulk-item="giocatori-universo"
                                                            <?= $haEdizioni ? 'disabled' : '' ?>>
                                                    </div>

                                                    <div class="flex-grow-1">
                                                        <div class="fw-semibold"><?= htmlspecialchars((string) ($giocatore['Nome'] ?? '')) ?></div>
                                                        <div class="small text-muted">
                                                            ID <?= (int) ($giocatore['ID'] ?? 0) ?>
                                                            · <?= htmlspecialchars($nomePosizione !== '' ? $nomePosizione : '-') ?>
                                                            · <?= htmlspecialchars($nomePaese) ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <?php require __DIR__ . '/../partials/script.php'; ?>
</body>

</html>