<?php

use App\Support\Countries;
use App\Support\Positions;

/** @var array $giocatori */
/** @var array $paesi */
/** @var array $posizioni */
/** @var int $pagineTotali */
/** @var int $page */
/** @var array $filtri */

?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giocatori</title>
    <?php require __DIR__ . '/../partials/link.php'; ?>
</head>

<body>
    <div class="container-fluid py-4">

        <div class="d-flex flex-column flex-lg-row align-items-lg-start justify-content-between gap-3 mb-4">
            <div>
                <a href="/" class="link-secondary text-decoration-none d-inline-block mb-2">← Home</a>
                <h1 class="h2 mb-1">Giocatori</h1>
                <p class="text-muted mb-0">Gestione anagrafica dei giocatori globali.</p>
            </div>

            <div class="d-flex flex-column flex-sm-row flex-wrap gap-2">
                <a class="btn btn-primary" href="/giocatori/crea">Nuovo giocatore</a>

                <form action="/giocatori/genera" method="post" class="d-inline">
                    <button class="btn btn-outline-secondary w-100" type="submit">
                        Genera random
                    </button>
                </form>

                <form action="/giocatori/genera-multiple" method="post" class="d-flex gap-2">
                    <input
                        type="number"
                        name="quantita"
                        min="1"
                        max="500"
                        value="10"
                        class="form-control">
                    <button class="btn btn-outline-secondary text-nowrap" type="submit">
                        Genera multipli
                    </button>
                </form>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <form method="get" action="/giocatori" class="row g-3 align-items-end">
                    <div class="col-12 col-md-6 col-xl-3">
                        <label for="q" class="form-label">Cerca</label>
                        <input
                            id="q"
                            type="text"
                            name="q"
                            class="form-control"
                            placeholder="Cerca nome..."
                            value="<?= htmlspecialchars((string) ($filtri['q'] ?? '')) ?>">
                    </div>

                    <div class="col-12 col-sm-6 col-xl-2">
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

                    <div class="col-12 col-sm-6 col-xl-2">
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

                    <div class="col-6 col-md-4 col-xl-2">
                        <label for="sort" class="form-label">Ordina per</label>
                        <select id="sort" name="sort" class="form-select">
                            <option value="ID" <?= (($filtri['sort'] ?? '') === 'ID') ? 'selected' : '' ?>>ID</option>
                            <option value="Nome" <?= (($filtri['sort'] ?? '') === 'Nome') ? 'selected' : '' ?>>Nome</option>
                            <option value="Attacco" <?= (($filtri['sort'] ?? '') === 'Attacco') ? 'selected' : '' ?>>Attacco</option>
                            <option value="Difesa" <?= (($filtri['sort'] ?? '') === 'Difesa') ? 'selected' : '' ?>>Difesa</option>
                            <option value="Nascita" <?= (($filtri['sort'] ?? '') === 'Nascita') ? 'selected' : '' ?>>Nascita</option>
                            <option value="Posizione" <?= (($filtri['sort'] ?? '') === 'Posizione') ? 'selected' : '' ?>>Posizione</option>
                            <option value="Paese" <?= (($filtri['sort'] ?? '') === 'Paese') ? 'selected' : '' ?>>Paese</option>
                            <option value="Creato" <?= (($filtri['sort'] ?? '') === 'Creato') ? 'selected' : '' ?>>Creato</option>
                        </select>
                    </div>

                    <div class="col-6 col-md-4 col-xl-1">
                        <label for="dir" class="form-label">Direzione</label>
                        <select id="dir" name="dir" class="form-select">
                            <option value="asc" <?= (($filtri['dir'] ?? 'asc') === 'asc') ? 'selected' : '' ?>>Asc</option>
                            <option value="desc" <?= (($filtri['dir'] ?? '') === 'desc') ? 'selected' : '' ?>>Desc</option>
                        </select>
                    </div>

                    <div class="col-6 col-md-4 col-xl-1">
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

                    <div class="col-12">
                        <div class="d-flex flex-column flex-sm-row gap-2">
                            <button class="btn btn-primary" type="submit">Applica filtri</button>
                            <a class="btn btn-outline-secondary" href="/giocatori">Reset</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <?php if (empty($giocatori)): ?>
                    <div class="text-center py-5">
                        <h2 class="h5 mb-2">Nessun giocatore presente</h2>
                        <p class="text-muted mb-3">Crea un nuovo giocatore oppure genera dati casuali.</p>
                        <div class="d-flex justify-content-center flex-wrap gap-2">
                            <a class="btn btn-primary" href="/giocatori/crea">Nuovo giocatore</a>
                            <form action="/giocatori/genera" method="post" class="d-inline">
                                <button class="btn btn-outline-secondary" type="submit">Genera random</button>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <form action="/giocatori/elimina-selezionate" method="post" onsubmit="return confirm('Eliminare i giocatori selezionati?');">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                            <div>
                                <h2 class="h5 mb-1">Elenco giocatori</h2>
                                <p class="text-muted mb-0">Seleziona uno o più record per le azioni massive.</p>
                            </div>
                            <div>
                                <button class="btn btn-outline-danger" type="submit">Elimina selezionati</button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col" style="width: 44px;">
                                            <input type="checkbox" class="form-check-input" data-bulk-toggle="giocatori">
                                        </th>
                                        <th scope="col">ID</th>
                                        <th scope="col">Nome</th>
                                        <th scope="col">Posizione</th>
                                        <th scope="col">Attacco</th>
                                        <th scope="col">Difesa</th>
                                        <th scope="col">Paese</th>
                                        <th scope="col">Nascita</th>
                                        <th scope="col">Creato</th>
                                        <th scope="col" class="text-end">Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($giocatori as $giocatore): ?>
                                        <?php
                                        $codicePaese = (string) ($giocatore['Paese'] ?? '');
                                        $nomePaese = $codicePaese !== '' ? Countries::nameFromCode($codicePaese) : '';
                                        $codicePosizione = (string) ($giocatore['Posizione'] ?? '');
                                        $nomePosizione = Positions::label($codicePosizione);
                                        ?>
                                        <tr>
                                            <td>
                                                <input
                                                    type="checkbox"
                                                    class="form-check-input"
                                                    name="ids[]"
                                                    value="<?= (int) $giocatore['ID'] ?>"
                                                    data-bulk-item="giocatori">
                                            </td>
                                            <td><?= (int) $giocatore['ID'] ?></td>
                                            <td class="fw-semibold"><?= htmlspecialchars((string) ($giocatore['Nome'] ?? '')) ?></td>
                                            <td><?= htmlspecialchars($nomePosizione !== '' ? $nomePosizione : '-') ?></td>
                                            <td><?= htmlspecialchars((string) ($giocatore['Attacco'] ?? '0')) ?></td>
                                            <td><?= htmlspecialchars((string) ($giocatore['Difesa'] ?? '0')) ?></td>
                                            <td><?= htmlspecialchars($nomePaese !== '' ? $nomePaese : ($codicePaese !== '' ? $codicePaese : '-')) ?></td>
                                            <td><?= htmlspecialchars((string) ($giocatore['Nascita'] ?? '-')) ?></td>
                                            <td class="text-muted small"><?= htmlspecialchars((string) ($giocatore['Creato'] ?? '-')) ?></td>
                                            <td class="text-end">
                                                <div class="d-inline-flex flex-wrap justify-content-end gap-2">
                                                    <a class="btn btn-sm btn-outline-primary" href="/giocatori/modifica/<?= (int) $giocatore['ID'] ?>">
                                                        Modifica
                                                    </a>

                                                    <form action="/giocatori/duplica/<?= (int) $giocatore['ID'] ?>" method="post" class="d-inline">
                                                        <button class="btn btn-sm btn-outline-secondary" type="submit">
                                                            Duplica
                                                        </button>
                                                    </form>

                                                    <form action="/giocatori/elimina/<?= (int) $giocatore['ID'] ?>" method="post" class="d-inline" onsubmit="return confirm('Eliminare questo giocatore?');">
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

                        <?php if (($pagineTotali) > 1): ?>
                            <div class="d-flex justify-content-center mt-4">
                                <nav aria-label="Paginazione giocatori">
                                    <ul class="pagination mb-0 flex-wrap">
                                        <?php for ($i = 1; $i <= $pagineTotali; $i++): ?>
                                            <?php
                                            $query = http_build_query([
                                                'q' => $filtri['q'] ?? '',
                                                'paese' => $filtri['paese'] ?? '',
                                                'posizione' => $filtri['posizione'] ?? '',
                                                'sort' => $filtri['sort'] ?? 'Nome',
                                                'dir' => $filtri['dir'] ?? 'asc',
                                                'per_page' => $filtri['per_page'] ?? 25,
                                                'page' => $i,
                                            ]);
                                            ?>
                                            <?php if ($i === (int) $page): ?>
                                                <li class="page-item active" aria-current="page">
                                                    <span class="page-link"><?= $i ?></span>
                                                </li>
                                            <?php else: ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="/giocatori?<?= htmlspecialchars($query) ?>">
                                                        <?= $i ?>
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                    </ul>
                                </nav>
                            </div>
                        <?php endif; ?>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php require __DIR__ . '/../partials/script.php'; ?>
</body>

</html>