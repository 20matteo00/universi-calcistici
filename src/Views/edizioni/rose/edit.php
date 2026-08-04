<?php

/** @var array $universo */
/** @var array $edizione */
/** @var array $squadra */
/** @var array $errori */
/** @var array $giocatoriAssegnati */
/** @var array $giocatoriDisponibili */
/** @var array $verificaRosa */

$pool = [];
$idsSelezionati = [];

foreach ($giocatoriAssegnati as $giocatore) {
    $idGiocatore = (int) ($giocatore['IDGiocatore'] ?? 0);
    $pool[$idGiocatore] = $giocatore;
    $idsSelezionati[] = $idGiocatore;
}

foreach ($giocatoriDisponibili as $giocatore) {
    $idGiocatore = (int) ($giocatore['IDGiocatore'] ?? 0);
    if (!isset($pool[$idGiocatore])) {
        $pool[$idGiocatore] = $giocatore;
    }
}

$assegnatiMap = array_flip($idsSelezionati);

$assegnati = [];
$disponibili = [];

foreach ($pool as $idGiocatore => $giocatore) {
    if (isset($assegnatiMap[$idGiocatore])) {
        $assegnati[] = $giocatore;
    } else {
        $disponibili[] = $giocatore;
    }
}
?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rosa - <?= htmlspecialchars((string) ($squadra['Nome'] ?? 'Squadra')) ?></title>
    <?php require __DIR__ . '/../../partials/link.php'; ?>
</head>

<body>
    <div class="container py-4">
        <div class="mb-4">
            <div class="d-flex flex-column flex-lg-row align-items-lg-start justify-content-between gap-3">
                <div>
                    <a
                        href="/universi/<?= (int) ($universo['ID'] ?? 0) ?>/edizioni/<?= (int) ($edizione['ID'] ?? 0) ?>/rose"
                        class="link-secondary text-decoration-none d-inline-block mb-2">
                        ← Torna alle rose
                    </a>

                    <h1 class="h2 mb-2"><?= htmlspecialchars((string) ($squadra['Nome'] ?? '')) ?></h1>
                    <p class="text-muted mb-0">
                        Costruisci la rosa controllando composizione, vincoli minimi per ruolo e stato di completezza in tempo reale.
                    </p>
                </div>

                <div class="d-flex flex-shrink-0">
                    <form
                        method="post"
                        action="/universi/<?= (int) ($universo['ID'] ?? 0) ?>/edizioni/<?= (int) ($edizione['ID'] ?? 0) ?>/rose/<?= (int) ($squadra['IDSquadra'] ?? 0) ?>/auto"
                        class="m-0">
                        <button type="submit" class="btn btn-success px-4">
                            Assegna automaticamente
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <?php if (!empty($errori)): ?>
            <div class="alert alert-danger shadow-sm border-0 mb-4" role="alert">
                <div class="fw-semibold mb-2">Non è stato possibile salvare la rosa</div>
                <ul class="mb-0 ps-3">
                    <?php foreach ($errori as $errore): ?>
                        <li><?= htmlspecialchars((string) $errore) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
                    <div>
                        <div class="text-uppercase text-muted small fw-semibold mb-1">Riepilogo rosa</div>
                        <h2 class="h5 mb-1">Verifica composizione attuale</h2>
                        <p class="text-muted mb-0 small">
                            I conteggi si aggiornano in base ai giocatori selezionati per questa squadra.
                        </p>
                    </div>

                    <span
                        id="roster-status"
                        class="badge <?= (bool) ($verificaRosa['ok'] ?? false) ? 'text-bg-success' : 'text-bg-warning' ?> px-3 py-2">
                        <?= (bool) ($verificaRosa['ok'] ?? false) ? 'Rosa completa' : 'Rosa incompleta' ?>
                    </span>
                </div>

                <div class="row g-3">
                    <div class="col-6 col-md-4 col-xl-2">
                        <div class="rounded bg-light p-3 h-100">
                            <div class="text-muted small mb-1">Totale</div>
                            <div class="fw-semibold fs-5"><span id="count-total"><?= (int) ($verificaRosa['conteggi']['totale'] ?? 0) ?></span>/18</div>
                        </div>
                    </div>

                    <div class="col-6 col-md-4 col-xl-2">
                        <div class="rounded bg-light p-3 h-100">
                            <div class="text-muted small mb-1">Portieri</div>
                            <div class="fw-semibold fs-5"><span id="count-por"><?= (int) ($verificaRosa['conteggi']['POR'] ?? 0) ?></span>/2</div>
                        </div>
                    </div>

                    <div class="col-6 col-md-4 col-xl-2">
                        <div class="rounded bg-light p-3 h-100">
                            <div class="text-muted small mb-1">Difensivi</div>
                            <div class="fw-semibold fs-5"><span id="count-dif"><?= (int) ($verificaRosa['conteggi']['difensivi'] ?? 0) ?></span>/5</div>
                        </div>
                    </div>

                    <div class="col-6 col-md-4 col-xl-2">
                        <div class="rounded bg-light p-3 h-100">
                            <div class="text-muted small mb-1">Centrocampo</div>
                            <div class="fw-semibold fs-5"><span id="count-cen"><?= (int) ($verificaRosa['conteggi']['centrocampo'] ?? 0) ?></span>/6</div>
                        </div>
                    </div>

                    <div class="col-6 col-md-4 col-xl-2">
                        <div class="rounded bg-light p-3 h-100">
                            <div class="text-muted small mb-1">Offensivi</div>
                            <div class="fw-semibold fs-5"><span id="count-off"><?= (int) ($verificaRosa['conteggi']['offensivi'] ?? 0) ?></span>/5</div>
                        </div>
                    </div>

                    <div class="col-6 col-md-4 col-xl-2">
                        <div class="rounded bg-light p-3 h-100">
                            <div class="text-muted small mb-1">Stato</div>
                            <div class="fw-semibold">
                                <?= (bool) ($verificaRosa['ok'] ?? false) ? 'Completa' : 'Da completare' ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="live-alert" class="alert alert-warning mt-4 mb-0 <?= (bool) ($verificaRosa['ok'] ?? false) ? 'd-none' : '' ?>" role="alert">
                    La rosa non è ancora valida: completa i ruoli mancanti per soddisfare tutti i vincoli minimi.
                </div>
            </div>
        </div>

        <form method="post" action="/universi/<?= (int) ($universo['ID'] ?? 0) ?>/edizioni/<?= (int) ($edizione['ID'] ?? 0) ?>/rose/<?= (int) ($squadra['IDSquadra'] ?? 0) ?>">
            <div class="row g-4">
                <div class="col-12 col-lg-5">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h2 class="h5 mb-0">Già nella rosa</h2>
                                <span class="badge text-bg-primary" id="selected-visible-count"><?= count($assegnati) ?></span>
                            </div>

                            <input type="text" class="form-control mb-3" id="filter-selected" placeholder="Cerca nella rosa...">

                            <div class="uc-transfer-list" id="selected-list">
                                <?php foreach ($assegnati as $giocatore): ?>
                                    <?php
                                    $idGiocatore = (int) ($giocatore['IDGiocatore'] ?? 0);
                                    $posizione = strtoupper(trim((string) ($giocatore['Posizione'] ?? '')));
                                    ?>
                                    <div
                                        class="uc-list-item player-item"
                                        data-name="<?= htmlspecialchars(mb_strtolower((string) ($giocatore['Nome'] ?? ''))) ?>"
                                        data-role-group="<?= htmlspecialchars(
                                                                $posizione === 'POR' ? 'POR' : (in_array($posizione, ['TD', 'TS', 'DC'], true) ? 'DIF' : (in_array($posizione, ['CC', 'MED', 'CS', 'CD', 'TRQ'], true) ? 'CEN' : (in_array($posizione, ['AS', 'AD', 'ATT'], true) ? 'OFF' : 'ALT')))
                                                            ) ?>">
                                        <div class="form-check">
                                            <input
                                                class="form-check-input roster-checkbox"
                                                type="checkbox"
                                                name="ids_giocatori[]"
                                                value="<?= $idGiocatore ?>"
                                                data-posizione="<?= htmlspecialchars($posizione) ?>"
                                                checked>
                                            <label class="form-check-label">
                                                <strong><?= htmlspecialchars((string) ($giocatore['Nome'] ?? '')) ?></strong>
                                            </label>
                                        </div>

                                        <div class="small text-muted mt-2">
                                            <div>Posizione: <?= htmlspecialchars($posizione) ?></div>
                                            <div>Attacco: <?= htmlspecialchars((string) ($giocatore['Attacco'] ?? '0')) ?></div>
                                            <div>Difesa: <?= htmlspecialchars((string) ($giocatore['Difesa'] ?? '0')) ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-7">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h2 class="h5 mb-0">Giocatori disponibili</h2>
                                <span class="badge text-bg-secondary" id="available-visible-count"><?= count($disponibili) ?></span>
                            </div>

                            <div class="row g-2 uc-toolbar mb-3">
                                <div class="col-12 col-md-6">
                                    <input type="text" class="form-control" id="filter-available" placeholder="Cerca giocatore...">
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="btn-group w-100 uc-role-filter" role="group" aria-label="Filtro ruolo">
                                        <button type="button" class="btn btn-outline-secondary active" data-role-filter="ALL">Tutti</button>
                                        <button type="button" class="btn btn-outline-secondary" data-role-filter="POR">POR</button>
                                        <button type="button" class="btn btn-outline-secondary" data-role-filter="DIF">DIF</button>
                                        <button type="button" class="btn btn-outline-secondary" data-role-filter="CEN">CEN</button>
                                        <button type="button" class="btn btn-outline-secondary" data-role-filter="OFF">OFF</button>
                                    </div>
                                </div>
                            </div>

                            <div class="uc-transfer-list" id="available-list">
                                <?php foreach ($disponibili as $giocatore): ?>
                                    <?php
                                    $idGiocatore = (int) ($giocatore['IDGiocatore'] ?? 0);
                                    $posizione = strtoupper(trim((string) ($giocatore['Posizione'] ?? '')));
                                    $roleGroup = $posizione === 'POR' ? 'POR' : (in_array($posizione, ['TD', 'TS', 'DC'], true) ? 'DIF' : (in_array($posizione, ['CC', 'MED', 'CS', 'CD', 'TRQ'], true) ? 'CEN' : (in_array($posizione, ['AS', 'AD', 'ATT'], true) ? 'OFF' : 'ALT')));
                                    ?>
                                    <div
                                        class="uc-list-item player-item"
                                        data-name="<?= htmlspecialchars(mb_strtolower((string) ($giocatore['Nome'] ?? ''))) ?>"
                                        data-role-group="<?= htmlspecialchars($roleGroup) ?>">
                                        <div class="form-check">
                                            <input
                                                class="form-check-input roster-checkbox"
                                                type="checkbox"
                                                name="ids_giocatori[]"
                                                value="<?= $idGiocatore ?>"
                                                data-posizione="<?= htmlspecialchars($posizione) ?>">
                                            <label class="form-check-label">
                                                <strong><?= htmlspecialchars((string) ($giocatore['Nome'] ?? '')) ?></strong>
                                            </label>
                                        </div>

                                        <div class="small text-muted mt-2">
                                            <div>Posizione: <?= htmlspecialchars($posizione) ?></div>
                                            <div>Attacco: <?= htmlspecialchars((string) ($giocatore['Attacco'] ?? '0')) ?></div>
                                            <div>Difesa: <?= htmlspecialchars((string) ($giocatore['Difesa'] ?? '0')) ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <a class="btn btn-outline-secondary" href="/universi/<?= (int) ($universo['ID'] ?? 0) ?>/edizioni/<?= (int) ($edizione['ID'] ?? 0) ?>/rose">Indietro</a>
                <button type="submit" class="btn btn-primary">Salva rosa</button>
            </div>
        </form>
    </div>

    <script>
        (function() {
            const selectedSearch = document.getElementById('filter-selected');
            const selectedList = document.getElementById('selected-list');
            const selectedVisibleCount = document.getElementById('selected-visible-count');

            const availableSearch = document.getElementById('filter-available');
            const availableList = document.getElementById('available-list');
            const availableVisibleCount = document.getElementById('available-visible-count');
            const roleButtons = document.querySelectorAll('[data-role-filter]');

            const totalEl = document.getElementById('count-total');
            const porEl = document.getElementById('count-por');
            const difEl = document.getElementById('count-dif');
            const cenEl = document.getElementById('count-cen');
            const offEl = document.getElementById('count-off');
            const statusEl = document.getElementById('roster-status');
            const liveAlert = document.getElementById('live-alert');

            let currentRoleFilter = 'ALL';

            const mapRoleGroup = (posizione) => {
                if (posizione === 'POR') return 'POR';
                if (['TD', 'TS', 'DC'].includes(posizione)) return 'DIF';
                if (['CC', 'MED', 'CS', 'CD', 'TRQ'].includes(posizione)) return 'CEN';
                if (['AS', 'AD', 'ATT'].includes(posizione)) return 'OFF';
                return 'ALT';
            };

            const updateRosterCounters = () => {
                const checked = Array.from(document.querySelectorAll('.roster-checkbox:checked'));
                let total = checked.length;
                let por = 0;
                let dif = 0;
                let cen = 0;
                let off = 0;

                checked.forEach((checkbox) => {
                    const posizione = (checkbox.getAttribute('data-posizione') || '').toUpperCase();
                    const gruppo = mapRoleGroup(posizione);

                    if (gruppo === 'POR') por++;
                    if (gruppo === 'DIF') dif++;
                    if (gruppo === 'CEN') cen++;
                    if (gruppo === 'OFF') off++;
                });

                totalEl.textContent = String(total);
                porEl.textContent = String(por);
                difEl.textContent = String(dif);
                cenEl.textContent = String(cen);
                offEl.textContent = String(off);

                const ok = total === 18 && por >= 2 && dif >= 5 && cen >= 6 && off >= 5;

                statusEl.textContent = ok ? 'Completa' : 'Incompleta';
                statusEl.classList.remove('text-bg-success', 'text-bg-warning');
                statusEl.classList.add(ok ? 'text-bg-success' : 'text-bg-warning');

                if (liveAlert) {
                    liveAlert.classList.toggle('d-none', ok);
                    if (!ok) {
                        const parts = [];
                        if (total !== 18) parts.push('Totale ' + total + '/18');
                        if (por < 2) parts.push('POR ' + por + '/2');
                        if (dif < 5) parts.push('Difensivi ' + dif + '/5');
                        if (cen < 6) parts.push('Centrocampo ' + cen + '/6');
                        if (off < 5) parts.push('Offensivi ' + off + '/5');
                        liveAlert.textContent = 'Rosa non valida: ' + parts.join(' · ');
                    }
                }
            };

            const applyListFilter = (listEl, searchValue, roleFilter, counterEl) => {
                if (!listEl || !counterEl) return;

                let visible = 0;

                listEl.querySelectorAll('.player-item').forEach((item) => {
                    const name = item.getAttribute('data-name') || '';
                    const role = item.getAttribute('data-role-group') || 'ALT';

                    const matchSearch = searchValue === '' || name.includes(searchValue);
                    const matchRole = roleFilter === 'ALL' || role === roleFilter;
                    const show = matchSearch && matchRole;

                    item.style.display = show ? '' : 'none';

                    if (show) visible++;
                });

                counterEl.textContent = String(visible);
            };

            const refreshFilters = () => {
                const selectedQuery = (selectedSearch?.value || '').trim().toLowerCase();
                const availableQuery = (availableSearch?.value || '').trim().toLowerCase();

                applyListFilter(selectedList, selectedQuery, currentRoleFilter, selectedVisibleCount);
                applyListFilter(availableList, availableQuery, currentRoleFilter, availableVisibleCount);
            };

            if (selectedSearch) {
                selectedSearch.addEventListener('input', refreshFilters);
            }

            if (availableSearch) {
                availableSearch.addEventListener('input', refreshFilters);
            }

            roleButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    currentRoleFilter = button.getAttribute('data-role-filter') || 'ALL';

                    roleButtons.forEach((btn) => btn.classList.remove('active'));
                    button.classList.add('active');

                    refreshFilters();
                });
            });

            document.querySelectorAll('.roster-checkbox').forEach((checkbox) => {
                checkbox.addEventListener('change', updateRosterCounters);
            });

            updateRosterCounters();
            refreshFilters();
        })();
    </script>

    <?php require __DIR__ . '/../../partials/script.php'; ?>
</body>

</html>