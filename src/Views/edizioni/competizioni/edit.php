<?php

/** @var array $universo */
/** @var array $edizione */
/** @var array $edizioneCompetizione */
/** @var array $squadreEdizione */
/** @var array $squadreIscritte */
/** @var array $errori */
/** @var array $warningMessaggi */
/** @var array $altreCompetizioniPerSquadra */


use App\Support\Countries;

$idsSelezionati = [];
foreach ($squadreIscritte as $squadra) {
    $idsSelezionati[] = (int) ($squadra['IDSquadra'] ?? 0);
}

$mapIscritte = array_flip($idsSelezionati);
$assegnate = [];
$disponibili = [];

foreach ($squadreEdizione as $squadra) {
    $idSquadra = (int) ($squadra['IDSquadra'] ?? 0);

    if (isset($mapIscritte[$idSquadra])) {
        $assegnate[] = $squadra;
    } else {
        $disponibili[] = $squadra;
    }
}

$paesi = Countries::all();
?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Competizione - <?= htmlspecialchars((string) ($edizioneCompetizione['NomeCompetizione'] ?? '')) ?></title>
    <?php require __DIR__ . '/../../partials/link.php'; ?>
</head>

<body>
    <div class="container py-4">
        <div class="mb-4">
            <a href="/universi/<?= (int) ($universo['ID'] ?? 0) ?>/edizioni/<?= (int) ($edizione['ID'] ?? 0) ?>/competizioni" class="link-secondary text-decoration-none d-inline-block mb-2">← Torna alle competizioni</a>
            <h1 class="h2 mb-1"><?= htmlspecialchars((string) ($edizioneCompetizione['NomeCompetizione'] ?? '')) ?></h1>
            <p class="text-muted mb-0">
                Seleziona le squadre da associare a questa competizione stagionale.
            </p>
        </div>

        <?php if (!empty($errori)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errori as $errore): ?>
                        <li><?= htmlspecialchars((string) $errore) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!empty($warningMessaggi)): ?>
            <div class="alert alert-warning">
                <strong>Attenzione:</strong>
                <ul class="mb-0 mt-2">
                    <?php foreach ($warningMessaggi as $messaggio): ?>
                        <li><?= htmlspecialchars((string) $messaggio) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body small">
                <div><strong>Tipo:</strong> <?= htmlspecialchars((string) ($edizioneCompetizione['Tipo'] ?? '')) ?></div>
                <div><strong>Numero partecipanti previsto:</strong> <?= (int) ($edizioneCompetizione['NumeroPartecipanti'] ?? 0) ?></div>
                <div><strong>Squadre attualmente assegnate:</strong> <span id="count-selected"><?= count($assegnate) ?></span></div>
            </div>
        </div>

        <form method="post" action="/universi/<?= (int) ($universo['ID'] ?? 0) ?>/edizioni/<?= (int) ($edizione['ID'] ?? 0) ?>/competizioni/<?= (int) ($edizioneCompetizione['ID'] ?? 0) ?>">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="stato" class="form-label">Stato da applicare</label>
                            <select class="form-select" id="stato" name="stato">
                                <?php foreach (['Iscritta', 'Qualificata', 'Candidata', 'Eliminata', 'Promossa', 'Retrocessa'] as $valoreStato): ?>
                                    <option value="<?= htmlspecialchars($valoreStato) ?>"><?= htmlspecialchars($valoreStato) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="motivo" class="form-label">Motivo</label>
                            <input type="text" class="form-control" id="motivo" name="motivo" maxlength="150" value="Iscrizione manuale">
                        </div>

                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-12 col-lg-6">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h2 class="h5 mb-0">Squadre già assegnate</h2>
                                <span class="badge text-bg-primary" id="selected-visible-count"><?= count($assegnate) ?></span>
                            </div>

                            <input type="text" class="form-control mb-3" id="filter-selected" placeholder="Cerca tra le assegnate...">

                            <div class="uc-transfer-list" id="selected-list">
                                <?php foreach ($assegnate as $squadra): ?>
                                    <?php
                                    $idSquadra = (int) ($squadra['IDSquadra'] ?? 0);
                                    $altre = $altreCompetizioniPerSquadra[$idSquadra] ?? [];
                                    ?>
                                    <div class="uc-list-item" data-name="<?= htmlspecialchars(mb_strtolower((string) ($squadra['Nome'] ?? ''))) ?>">
                                        <div class="form-check">
                                            <input
                                                class="form-check-input team-checkbox"
                                                type="checkbox"
                                                name="ids_squadre[]"
                                                value="<?= $idSquadra ?>"
                                                checked>
                                            <label class="form-check-label">
                                                <strong><?= htmlspecialchars((string) ($squadra['Nome'] ?? '')) ?></strong> <span>(<?= $paesi[$squadra['Paese']] ?? '' ?>)</span>
                                            </label>
                                        </div>

                                        <?php if (!empty($altre)): ?>
                                            <div class="mt-2">
                                                <?php foreach ($altre as $altra): ?>
                                                    <span class="badge text-bg-warning uc-warning-badge">
                                                        Già in <?= htmlspecialchars((string) ($altra['NomeCompetizione'] ?? '')) ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-6">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h2 class="h5 mb-0">Squadre disponibili</h2>
                                <span class="badge text-bg-secondary" id="available-visible-count"><?= count($disponibili) ?></span>
                            </div>

                            <input type="text" class="form-control mb-3" id="filter-available" placeholder="Cerca tra le disponibili...">

                            <div class="uc-transfer-list" id="available-list">
                                <?php foreach ($disponibili as $squadra): ?>
                                    <?php
                                    $idSquadra = (int) ($squadra['IDSquadra'] ?? 0);
                                    $altre = $altreCompetizioniPerSquadra[$idSquadra] ?? [];
                                    ?>
                                    <div class="uc-list-item" data-name="<?= htmlspecialchars(mb_strtolower((string) ($squadra['Nome'] ?? ''))) ?>">
                                        <div class="form-check">
                                            <input
                                                class="form-check-input team-checkbox"
                                                type="checkbox"
                                                name="ids_squadre[]"
                                                value="<?= $idSquadra ?>">
                                            <label class="form-check-label">
                                                <strong><?= htmlspecialchars((string) ($squadra['Nome'] ?? '')) ?></strong> <span>(<?= $paesi[$squadra['Paese']] ?? '' ?>)</span>
                                            </label>
                                        </div>

                                        <?php if (!empty($altre)): ?>
                                            <div class="mt-2">
                                                <?php foreach ($altre as $altra): ?>
                                                    <span class="badge text-bg-warning uc-warning-badge">
                                                        Già in <?= htmlspecialchars((string) ($altra['NomeCompetizione'] ?? '')) ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <a class="btn btn-outline-secondary" href="/universi/<?= (int) ($universo['ID'] ?? 0) ?>/edizioni/<?= (int) ($edizione['ID'] ?? 0) ?>/competizioni">Indietro</a>
                <button type="submit" class="btn btn-primary">Salva squadre</button>
            </div>
        </form>
    </div>

    <script>
        (function() {
            const filterList = (inputId, listId, counterId) => {
                const input = document.getElementById(inputId);
                const list = document.getElementById(listId);
                const counter = document.getElementById(counterId);
                if (!input || !list || !counter) return;

                const apply = () => {
                    const query = input.value.trim().toLowerCase();
                    let visible = 0;

                    list.querySelectorAll('.uc-list-item').forEach((item) => {
                        const name = item.getAttribute('data-name') || '';
                        const show = query === '' || name.includes(query);
                        item.style.display = show ? '' : 'none';
                        if (show) visible++;
                    });

                    counter.textContent = String(visible);
                };

                input.addEventListener('input', apply);
                apply();
            };

            filterList('filter-selected', 'selected-list', 'selected-visible-count');
            filterList('filter-available', 'available-list', 'available-visible-count');

            const checkboxes = document.querySelectorAll('.team-checkbox');
            const selectedCounter = document.getElementById('count-selected');

            const updateSelectedCount = () => {
                let total = 0;
                checkboxes.forEach((checkbox) => {
                    if (checkbox.checked) total++;
                });
                if (selectedCounter) {
                    selectedCounter.textContent = String(total);
                }
            };

            checkboxes.forEach((checkbox) => {
                checkbox.addEventListener('change', updateSelectedCount);
            });

            updateSelectedCount();
        })();
    </script>

    <?php require __DIR__ . '/../../partials/script.php'; ?>
</body>

</html>