<?php

use App\Support\Countries;
use App\Support\Positions;

/** @var array $universo */
/** @var bool $haEdizioni */
/** @var array $squadre */
/** @var array $giocatori */
/** @var array $squadreDisponibili */
/** @var array $giocatoriDisponibili */

?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Universo - <?= htmlspecialchars((string) ($universo['Nome'] ?? 'Dettaglio')) ?></title>
    <?php require __DIR__ . '/../partials/link.php'; ?>
</head>

<body>
    <div class="container py-4">
        <div class="mx-auto">

            <div class="mb-4">
                <a href="/universi" class="link-secondary text-decoration-none d-inline-block mb-2">← Torna agli universi</a>
                <div class="d-flex flex-wrap gap-2">

                    <h1 class="h2 mb-1 me-auto">
                        <?= htmlspecialchars((string) ($universo['Nome'] ?? 'Universo')) ?>
                    </h1>
                    <a class="btn btn-outline-secondary" href="/universi/modifica/<?= (int) ($universo['ID'] ?? 0) ?>">
                        Modifica
                    </a>

                    <form action="/universi/elimina/<?= (int) ($universo['ID'] ?? 0) ?>" method="post" class="d-inline" onsubmit="return confirm('Eliminare questo universo?');">
                        <button class="btn btn-outline-danger" type="submit">
                            Elimina
                        </button>
                    </form>
                </div>
                <p class="text-muted mb-2">
                    Gestione dettagli, squadre e giocatori dell'universo.
                </p>

                <?php if ($haEdizioni): ?>
                    <div class="alert alert-warning py-2 px-3 mb-2">
                        Questo universo ha già almeno un'edizione: squadre, giocatori, competizioni e avanzamenti non sono più modificabili.
                    </div>
                <?php else: ?>
                    <div class="alert alert-success py-2 px-3 mb-2">
                        Universo modificabile: puoi ancora aggiungere o rimuovere squadre, giocatori e configurare competizioni e avanzamenti.
                    </div>
                <?php endif; ?>

                <?php if (!$haEdizioni): ?>
                    <?php if (!($roseMinimeOk ?? false)): ?>
                        <div class="alert alert-danger py-2 px-3 mb-2 d-flex justify-content-between align-items-start gap-2">
                            <div>
                                Attualmente i giocatori dell'universo non sono sufficienti oppure non rispettano i requisiti minimi base per coprire tutte le squadre dell'universo.
                            </div>
                            <button
                                type="button"
                                class="btn btn-link btn-sm text-decoration-none p-0 flex-shrink-0"
                                data-bs-toggle="modal"
                                data-bs-target="#modalRoseMinime"
                                aria-label="Informazioni requisiti minimi giocatori">
                                <i class="bi bi-info-circle"></i>
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info py-2 px-3 mb-2 d-flex justify-content-between align-items-start gap-2">
                            <div>
                                Requisiti minimi giocatori: OK, il bacino giocatori dell'universo è sufficiente a coprire tutte le squadre.
                            </div>
                            <button
                                type="button"
                                class="btn btn-link btn-sm text-decoration-none p-0 flex-shrink-0"
                                data-bs-toggle="modal"
                                data-bs-target="#modalRoseMinime"
                                aria-label="Dettaglio requisiti minimi giocatori">
                                <i class="bi bi-info-circle"></i>
                            </button>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if (!$haEdizioni): ?>
                    <?php if (!($coperturaCompetizioniOk ?? false)): ?>
                        <div class="alert alert-warning py-2 px-3 mb-0 d-flex justify-content-between align-items-start gap-2">
                            <div>
                                Le competizioni configurate non coprono ancora tutte le squadre dell'universo.
                            </div>
                            <button
                                type="button"
                                class="btn btn-link btn-sm text-decoration-none p-0 flex-shrink-0"
                                data-bs-toggle="modal"
                                data-bs-target="#modalCoperturaCompetizioni"
                                aria-label="Informazioni copertura competizioni">
                                <i class="bi bi-info-circle"></i>
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info py-2 px-3 mb-0 d-flex justify-content-between align-items-start gap-2">
                            <div>
                                Copertura competizioni: OK, tutte le squadre possono essere assegnate ad almeno una competizione.
                            </div>
                            <button
                                type="button"
                                class="btn btn-link btn-sm text-decoration-none p-0 flex-shrink-0"
                                data-bs-toggle="modal"
                                data-bs-target="#modalCoperturaCompetizioni"
                                aria-label="Dettaglio copertura competizioni">
                                <i class="bi bi-info-circle"></i>
                            </button>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>



            </div>

            <div class="row g-4 mb-4">
                <div class="col-12 col-lg-8">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body">
                            <h2 class="h5 mb-3">Dati principali</h2>

                            <dl class="row mb-0">
                                <dt class="col-sm-3">ID</dt>
                                <dd class="col-sm-9"><?= (int) ($universo['ID'] ?? 0) ?></dd>

                                <dt class="col-sm-3">Nome</dt>
                                <dd class="col-sm-9">
                                    <?= htmlspecialchars((string) ($universo['Nome'] ?? '-')) ?>
                                </dd>

                                <dt class="col-sm-3">Descrizione</dt>
                                <dd class="col-sm-9">
                                    <?php $descrizione = trim((string) ($universo['Descrizione'] ?? '')); ?>
                                    <?= $descrizione !== '' ? nl2br(htmlspecialchars($descrizione)) : '<span class="text-muted">Nessuna descrizione</span>' ?>
                                </dd>

                                <dt class="col-sm-3">Creato</dt>
                                <dd class="col-sm-9 text-muted">
                                    <?= htmlspecialchars((string) ($universo['Creato'] ?? '-')) ?>
                                </dd>

                                <dt class="col-sm-3">Modificato</dt>
                                <dd class="col-sm-9 text-muted">
                                    <?= htmlspecialchars((string) ($universo['Modificato'] ?? '-')) ?>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-4">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body">
                            <h2 class="h5 mb-3">Azioni</h2>
                            <div class="d-grid gap-2">
                                <a class="btn btn-primary" href="/universi/modifica/<?= (int) ($universo['ID'] ?? 0) ?>">
                                    Modifica universo
                                </a>
                                <?php if (!$haEdizioni): ?>
                                    <a class="btn btn-outline-primary" href="/universi/<?= (int) ($universo['ID'] ?? 0) ?>/competizioni">
                                        Gestisci competizioni
                                    </a>
                                    <a class="btn btn-success" href="/universi/<?= (int) ($universo['ID'] ?? 0) ?>/edizioni/crea">
                                        Crea 1ª Edizione
                                    </a>
                                <?php else: ?>
                                    <a class="btn btn-outline-secondary" href="/universi/<?= (int) ($universo['ID'] ?? 0) ?>/edizioni">
                                        Visualizza edizioni
                                    </a>
                                <?php endif; ?>
                                <a class="btn btn-outline-secondary" href="/universi">
                                    Torna alla lista
                                </a>
                            </div>

                            <hr>

                            <div class="small text-muted">
                                <div><strong>Squadre:</strong> <?= count($squadre) ?></div>
                                <div><strong>Giocatori:</strong> <?= count($giocatori) ?></div>
                                <div><strong>Competizioni:</strong> <?= count($competizioni ?? []) ?></div>
                                <div><strong>Edizioni:</strong> <?= $haEdizioni ? 'Presenti' : 'Nessuna' ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-12 col-xl-6">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                                <div>
                                    <h2 class="h5 mb-1">Squadre dell'universo</h2>
                                    <p class="text-muted mb-0">Elenco delle squadre globali collegate a questo universo.</p>
                                </div>
                                <span class="badge text-bg-secondary"><?= count($squadre) ?></span>
                            </div>

                            <?php if (!$haEdizioni): ?>
                                <a class="btn btn-outline-primary w-100" href="/universi/<?= (int) ($universo['ID'] ?? 0) ?>/squadre">
                                    Gestisci squadre
                                </a>
                                <form action="/universi/<?= (int) ($universo['ID'] ?? 0) ?>/squadre" method="post" class="row g-2 my-3">
                                    <div class="col-12 col-md-8">
                                        <label for="id_squadra" class="form-label">Aggiungi squadra</label>
                                        <select name="id_squadra" id="id_squadra" class="form-select">
                                            <option value="">Seleziona squadra...</option>
                                            <?php foreach ($squadreDisponibili as $squadraDisponibile): ?>
                                                <?php
                                                $paese = (string) ($squadraDisponibile['Paese'] ?? '');
                                                $nomePaese = $paese !== '' ? Countries::nameFromCode($paese) : '';
                                                ?>
                                                <option value="<?= (int) $squadraDisponibile['ID'] ?>">
                                                    <?= htmlspecialchars((string) ($squadraDisponibile['Nome'] ?? '')) ?>
                                                    <?= $nomePaese !== '' ? ' - ' . htmlspecialchars($nomePaese) : '' ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-12 col-md-4 d-grid">
                                        <label class="form-label d-none d-md-block">&nbsp;</label>
                                        <button type="submit" class="btn btn-primary">Aggiungi</button>
                                    </div>
                                </form>
                            <?php endif; ?>

                            <?php if (empty($squadre)): ?>
                                <p class="text-muted mb-0">Nessuna squadra collegata.</p>
                            <?php else: ?>
                                <div class="table-responsive table-maxheight">
                                    <table class="table table-sm align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Nome</th>
                                                <th>Paese</th>
                                                <th>Tipo</th>
                                                <th class="text-end">Azioni</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($squadre as $squadra): ?>
                                                <?php
                                                $paese = (string) ($squadra['Paese'] ?? '');
                                                $nomePaese = $paese !== '' ? Countries::nameFromCode($paese) : '-';
                                                ?>
                                                <tr>
                                                    <td><?= (int) ($squadra['ID'] ?? 0) ?></td>
                                                    <td class="fw-semibold"><?= htmlspecialchars((string) ($squadra['Nome'] ?? '')) ?></td>
                                                    <td><?= htmlspecialchars($nomePaese) ?></td>
                                                    <td><?= htmlspecialchars((string) ($squadra['Tipo'] ?? '-')) ?></td>
                                                    <td class="text-end">
                                                        <?php if (!$haEdizioni): ?>
                                                            <form action="/universi/<?= (int) ($universo['ID'] ?? 0) ?>/squadre/<?= (int) ($squadra['ID'] ?? 0) ?>/rimuovi" method="post" class="d-inline" onsubmit="return confirm('Rimuovere questa squadra dall\'universo?');">
                                                                <button type="submit" class="btn btn-sm btn-outline-danger">Rimuovi</button>
                                                            </form>
                                                        <?php else: ?>
                                                            <span class="text-muted small">Bloccato</span>
                                                        <?php endif; ?>
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

                <div class="col-12 col-xl-6">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                                <div>
                                    <h2 class="h5 mb-1">Giocatori dell'universo</h2>
                                    <p class="text-muted mb-0">Elenco dei giocatori globali collegati a questo universo.</p>
                                </div>
                                <span class="badge text-bg-secondary"><?= count($giocatori) ?></span>
                            </div>

                            <?php if (!$haEdizioni): ?>
                                <a class="btn btn-outline-primary w-100" href="/universi/<?= (int) ($universo['ID'] ?? 0) ?>/giocatori">
                                    Gestisci giocatori
                                </a>
                                <form action="/universi/<?= (int) ($universo['ID'] ?? 0) ?>/giocatori" method="post" class="row g-2 my-3">
                                    <div class="col-12 col-md-8">
                                        <label for="id_giocatore" class="form-label">Aggiungi giocatore</label>
                                        <select name="id_giocatore" id="id_giocatore" class="form-select">
                                            <option value="">Seleziona giocatore...</option>
                                            <?php foreach ($giocatoriDisponibili as $giocatoreDisponibile): ?>
                                                <?php
                                                $paese = (string) ($giocatoreDisponibile['Paese'] ?? '');
                                                $nomePaese = $paese !== '' ? Countries::nameFromCode($paese) : '';
                                                $codicePosizione = (string) ($giocatoreDisponibile['Posizione'] ?? '');
                                                $nomePosizione = Positions::label($codicePosizione);
                                                ?>
                                                <option value="<?= (int) $giocatoreDisponibile['ID'] ?>">
                                                    <?= htmlspecialchars((string) ($giocatoreDisponibile['Nome'] ?? '')) ?>
                                                    <?= $nomePosizione !== '' ? ' - ' . htmlspecialchars($nomePosizione) : '' ?>
                                                    <?= $nomePaese !== '' ? ' - ' . htmlspecialchars($nomePaese) : '' ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-12 col-md-4 d-grid">
                                        <label class="form-label d-none d-md-block">&nbsp;</label>
                                        <button type="submit" class="btn btn-primary">Aggiungi</button>
                                    </div>
                                </form>
                            <?php endif; ?>

                            <?php if (empty($giocatori)): ?>
                                <p class="text-muted mb-0">Nessun giocatore collegato.</p>
                            <?php else: ?>
                                <div class="table-responsive table-maxheight">
                                    <table class="table table-sm align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Nome</th>
                                                <th>Posizione</th>
                                                <th>Paese</th>
                                                <th class="text-end">Azioni</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($giocatori as $giocatore): ?>
                                                <?php
                                                $paese = (string) ($giocatore['Paese'] ?? '');
                                                $nomePaese = $paese !== '' ? Countries::nameFromCode($paese) : '-';
                                                $codicePosizione = (string) ($giocatore['Posizione'] ?? '');
                                                $nomePosizione = Positions::label($codicePosizione);
                                                ?>
                                                <tr>
                                                    <td><?= (int) ($giocatore['ID'] ?? 0) ?></td>
                                                    <td class="fw-semibold"><?= htmlspecialchars((string) ($giocatore['Nome'] ?? '')) ?></td>
                                                    <td><?= htmlspecialchars($nomePosizione !== '' ? $nomePosizione : '-') ?></td>
                                                    <td><?= htmlspecialchars($nomePaese) ?></td>
                                                    <td class="text-end">
                                                        <?php if (!$haEdizioni): ?>
                                                            <form action="/universi/<?= (int) ($universo['ID'] ?? 0) ?>/giocatori/<?= (int) ($giocatore['ID'] ?? 0) ?>/rimuovi" method="post" class="d-inline" onsubmit="return confirm('Rimuovere questo giocatore dall\'universo?');">
                                                                <button type="submit" class="btn btn-sm btn-outline-danger">Rimuovi</button>
                                                            </form>
                                                        <?php else: ?>
                                                            <span class="text-muted small">Bloccato</span>
                                                        <?php endif; ?>
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
            </div>

        </div>
    </div>

    <div class="modal fade" id="modalRoseMinime" tabindex="-1" aria-labelledby="modalRoseMinimeLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="modalRoseMinimeLabel">
                        <i class="bi bi-info-circle me-2 text-primary"></i>
                        Requisiti minimi giocatori
                    </h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">
                        Per poter avviare l'universo, il bacino giocatori collegato all'universo deve essere sufficiente a coprire tutte le squadre presenti.
                    </p>

                    <ul class="mb-3">
                        <li>18 giocatori totali per squadra.</li>
                        <li>2 POR per squadra.</li>
                        <li>5 tra TD, TS, DC per squadra.</li>
                        <li>6 tra CC, MED, CS, CD, TRQ per squadra.</li>
                        <li>5 tra AS, AD, ATT per squadra.</li>
                    </ul>

                    <p class="mb-2">
                        Alcuni giocatori possono restare svincolati, ma il minimo richiesto deve essere coperto.
                    </p>

                    <?php if (!empty($dettaglioRose ?? [])): ?>
                        <hr>
                        <div class="small">
                            <div>
                                <strong>Squadre nell'universo:</strong>
                                <?= (int) ($dettaglioRose['numero_squadre'] ?? 0) ?>
                            </div>
                            <div>
                                <strong>Giocatori presenti:</strong>
                                <?= (int) ($dettaglioRose['conteggi']['totale'] ?? 0) ?>
                                / <?= (int) ($dettaglioRose['richiesti']['totale'] ?? 0) ?>
                            </div>
                            <div>
                                <strong>POR:</strong>
                                <?= (int) ($dettaglioRose['conteggi']['POR'] ?? 0) ?>
                                / <?= (int) ($dettaglioRose['richiesti']['POR'] ?? 0) ?>
                            </div>
                            <div>
                                <strong>Difensivi (TD, TS, DC):</strong>
                                <?= (int) ($dettaglioRose['conteggi']['difensivi'] ?? 0) ?>
                                / <?= (int) ($dettaglioRose['richiesti']['difensivi'] ?? 0) ?>
                            </div>
                            <div>
                                <strong>Centrocampo (CC, MED, CS, CD, TRQ):</strong>
                                <?= (int) ($dettaglioRose['conteggi']['centrocampo'] ?? 0) ?>
                                / <?= (int) ($dettaglioRose['richiesti']['centrocampo'] ?? 0) ?>
                            </div>
                            <div>
                                <strong>Offensivi (AS, AD, ATT):</strong>
                                <?= (int) ($dettaglioRose['conteggi']['offensivi'] ?? 0) ?>
                                / <?= (int) ($dettaglioRose['richiesti']['offensivi'] ?? 0) ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalCoperturaCompetizioni" tabindex="-1" aria-labelledby="modalCoperturaCompetizioniLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="modalCoperturaCompetizioniLabel">
                        <i class="bi bi-info-circle me-2 text-primary"></i>
                        Copertura competizioni
                    </h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">
                        Ogni squadra dell'universo dovrà essere assegnata ad almeno una competizione nella prima edizione.
                    </p>
                    <p class="mb-3">
                        Per questo motivo, il totale dei partecipanti previsti dalle competizioni configurate deve essere almeno pari al numero totale delle squadre presenti nell'universo.
                    </p>

                    <hr>

                    <div class="small">
                        <div><strong>Squadre nell'universo:</strong> <?= (int) ($numeroSquadreUniverso ?? count($squadre)) ?></div>
                        <div>
                            <strong>Partecipanti previsti nelle competizioni:</strong>
                            <?= (int) ($totalePartecipantiCompetizioni ?? 0) ?>
                            / <?= (int) ($numeroSquadreUniverso ?? count($squadre)) ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
                </div>
            </div>
        </div>
    </div>

    <?php require __DIR__ . '/../partials/script.php'; ?>
</body>

</html>