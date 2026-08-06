<?php

/** @var array $universo */
/** @var array $edizione */
/** @var array $competizione */
/** @var array $blocchiPartite */

$tipoCompetizione = mb_strtolower((string) ($competizione['Tipo'] ?? ''));
$isEliminazioneDiretta = $tipoCompetizione === 'eliminazione_diretta';
$isLega = $tipoCompetizione === 'lega';

$totalePartite = 0;
foreach ($blocchiPartite as $blocco) {
    $totalePartite += count($blocco['partite'] ?? []);
}

function uc_stato_badge_class(string $stato): string
{
    return match ($stato) {
        'giocata' => 'text-bg-success',
        'programmata' => 'text-bg-secondary',
        'riposo' => 'text-bg-warning',
        default => 'text-bg-light',
    };
}

function uc_leggi_dettagli_partita(array $partita): array
{
    $dettagli = json_decode((string) ($partita['Dettagli'] ?? '{}'), true);
    return is_array($dettagli) ? $dettagli : [];
}

function uc_style_squadra(?string $jsonColori): array
{
    $fallback = [
        'background' => '#6c757d',
        'color' => '#ffffff',
        'border' => '#6c757d',
    ];

    if ($jsonColori === null || trim($jsonColori) === '') {
        return $fallback;
    }

    $data = json_decode($jsonColori, true);

    if (!is_array($data)) {
        return $fallback;
    }

    return [
        'background' => (string) ($data['background'] ?? $data['sfondo'] ?? '#6c757d'),
        'color' => (string) ($data['color'] ?? $data['testo'] ?? '#ffffff'),
        'border' => (string) ($data['border'] ?? $data['bordo'] ?? '#6c757d'),
    ];
}
?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars((string) ($competizione['NomeCompetizione'] ?? 'Competizione')) ?></title>
    <?php require __DIR__ . '/../../partials/link.php'; ?>
</head>

<body>
    <div class="container py-4 competizione-page">
        <div class="d-flex flex-wrap align-items-start gap-3 mb-3">
            <div>
                <a
                    href="/universi/<?= (int) $universo['ID'] ?>/edizioni/<?= (int) $edizione['ID'] ?>/competizioni"
                    class="link-secondary text-decoration-none d-inline-block mb-2">
                    ← Torna alle competizioni
                </a>

                <h1 class="h2 mb-1"><?= htmlspecialchars((string) ($competizione['NomeCompetizione'] ?? 'Competizione')) ?></h1>
                <p class="text-muted mb-0">
                    Edizione <?= htmlspecialchars((string) ($edizione['Nome'] ?? '')) ?>
                    · <?= htmlspecialchars((string) ($competizione['Tipo'] ?? '')) ?>
                    · <?= count($blocchiPartite) ?> blocchi
                    · <?= $totalePartite ?> partite
                </p>
            </div>

            <div class="ms-lg-auto d-flex flex-wrap gap-2 uc-page-tools">
                <?php if ($isLega): ?>
                    <a
                        href="/universi/<?= (int) $universo['ID'] ?>/edizioni/<?= (int) $edizione['ID'] ?>/competizioni/<?= (int) $competizione['ID'] ?>/classifica"
                        class="btn btn-outline-secondary">
                        Vai alla classifica
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <form
            method="post"
            action="/universi/<?= (int) $universo['ID'] ?>/edizioni/<?= (int) $edizione['ID'] ?>/competizioni/<?= (int) $competizione['ID'] ?>/partite/salva-tutte"
            id="form-salva-tutto">

            <div class="card uc-page-hero shadow-sm border-0 mb-4">
                <div class="card-body p-4">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                        <div>
                            <div class="d-flex flex-wrap gap-2 mb-2">
                                <span class="badge rounded-pill text-bg-dark">Competizione</span>
                                <span class="badge rounded-pill text-bg-light"><?= htmlspecialchars((string) ($competizione['Tipo'] ?? '')) ?></span>
                                <span class="badge rounded-pill text-bg-light"><?= $totalePartite ?> match</span>
                            </div>
                            <div class="small text-muted">
                                Gestisci risultati, simulazioni e aggiornamenti per ogni blocco.
                            </div>
                        </div>

                        <?php if ($isLega): ?>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="submit" class="btn btn-primary">Salva tutto</button>

                                <button
                                    type="submit"
                                    formaction="/universi/<?= (int) $universo['ID'] ?>/edizioni/<?= (int) $edizione['ID'] ?>/competizioni/<?= (int) $competizione['ID'] ?>/partite/simula-tutte"
                                    formmethod="post"
                                    class="btn btn-outline-primary">
                                    Simula tutto
                                </button>

                                <button
                                    type="submit"
                                    formaction="/universi/<?= (int) $universo['ID'] ?>/edizioni/<?= (int) $edizione['ID'] ?>/competizioni/<?= (int) $competizione['ID'] ?>/partite/reset-tutte"
                                    formmethod="post"
                                    class="btn btn-outline-danger">
                                    Elimina tutto
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-light border mb-0 py-2 px-3 small">
                                Nelle coppe conviene agire per blocco o per singola partita.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if ($blocchiPartite === []): ?>
                <div class="alert alert-warning mb-4">
                    Nessuna partita generata per questa competizione.
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($blocchiPartite as $blocco): ?>
                        <div class="col-12 col-xl-6" id="<?= htmlspecialchars((string) $blocco['anchor']) ?>">
                            <div class="card shadow-sm h-100 uc-match-card">
                                <div class="card-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 py-3 bg-white">
                                    <div class="text-center text-md-start w-100">
                                        <h3 class="h5 fw-semibold mb-1">
                                            <?php if (!empty($blocco['fase'])): ?>
                                                <?= htmlspecialchars((string) $blocco['fase']) ?> ·
                                            <?php endif; ?>
                                            Giornata <?= (int) ($blocco['giornata'] ?? 0) ?>
                                        </h3>

                                        <div class="small text-muted d-flex flex-wrap justify-content-center justify-content-md-start align-items-center gap-2">
                                            <span><?= count($blocco['partite']) ?> partite</span>

                                            <?php
                                            $primaPartitaBlocco = $blocco['partite'][0] ?? null;
                                            $dettagliBlocco = $primaPartitaBlocco ? uc_leggi_dettagli_partita($primaPartitaBlocco) : [];
                                            ?>

                                            <?php if (!empty($dettagliBlocco['giro'])): ?>
                                                <span class="badge text-bg-light border">
                                                    Giro <?= (int) $dettagliBlocco['giro'] ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="d-flex flex-wrap gap-2 uc-block-toolbar">
                                        <?php if (!empty($blocco['fase'])): ?>
                                            <button
                                                type="submit"
                                                formaction="/universi/<?= (int) $universo['ID'] ?>/edizioni/<?= (int) $edizione['ID'] ?>/competizioni/<?= (int) $competizione['ID'] ?>/fasi/<?= urlencode((string) $blocco['fase']) ?>/giornate/<?= (int) $blocco['giornata'] ?>/salva"
                                                formmethod="post"
                                                class="btn btn-sm btn-primary">
                                                Salva
                                            </button>

                                            <button
                                                type="submit"
                                                formaction="/universi/<?= (int) $universo['ID'] ?>/edizioni/<?= (int) $edizione['ID'] ?>/competizioni/<?= (int) $competizione['ID'] ?>/fasi/<?= urlencode((string) $blocco['fase']) ?>/giornate/<?= (int) $blocco['giornata'] ?>/simula"
                                                formmethod="post"
                                                class="btn btn-sm btn-outline-primary">
                                                Simula
                                            </button>

                                            <button
                                                type="submit"
                                                formaction="/universi/<?= (int) $universo['ID'] ?>/edizioni/<?= (int) $edizione['ID'] ?>/competizioni/<?= (int) $competizione['ID'] ?>/fasi/<?= urlencode((string) $blocco['fase']) ?>/giornate/<?= (int) $blocco['giornata'] ?>/reset"
                                                formmethod="post"
                                                class="btn btn-sm btn-outline-danger">
                                                Elimina
                                            </button>
                                        <?php else: ?>
                                            <button
                                                type="submit"
                                                formaction="/universi/<?= (int) $universo['ID'] ?>/edizioni/<?= (int) $edizione['ID'] ?>/competizioni/<?= (int) $competizione['ID'] ?>/giornate/<?= (int) $blocco['giornata'] ?>/salva"
                                                formmethod="post"
                                                class="btn btn-sm btn-primary">
                                                Salva
                                            </button>

                                            <button
                                                type="submit"
                                                formaction="/universi/<?= (int) $universo['ID'] ?>/edizioni/<?= (int) $edizione['ID'] ?>/competizioni/<?= (int) $competizione['ID'] ?>/giornate/<?= (int) $blocco['giornata'] ?>/simula"
                                                formmethod="post"
                                                class="btn btn-sm btn-outline-primary">
                                                Simula
                                            </button>

                                            <button
                                                type="submit"
                                                formaction="/universi/<?= (int) $universo['ID'] ?>/edizioni/<?= (int) $edizione['ID'] ?>/competizioni/<?= (int) $competizione['ID'] ?>/giornate/<?= (int) $blocco['giornata'] ?>/reset"
                                                formmethod="post"
                                                class="btn btn-sm btn-outline-danger">
                                                Elimina
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="card-body p-0">
                                    <ul class="list-group list-group-flush overflow-hidden shadow-sm">
                                        <?php foreach ($blocco['partite'] as $partita): ?>
                                            <?php
                                            $dettagli = uc_leggi_dettagli_partita($partita);

                                            $nomeCasa = (string) ($partita['NomeSquadraCasa'] ?? '');
                                            $nomeTrasferta = (string) ($partita['NomeSquadraTrasferta'] ?? '');

                                            $stato = (string) ($partita['Stato'] ?? '');
                                            $goalCasa = $partita['GoalCasa'];
                                            $goalTrasferta = $partita['GoalTrasferta'];

                                            $preview = $partita['PreviewSimulazione'] ?? [];
                                            $esitoAtteso = (string) ($preview['esito_atteso'] ?? '');

                                            $coloriCasa = uc_style_squadra($partita['ColoriSquadraCasa'] ?? null);
                                            $coloriTrasferta = uc_style_squadra($partita['ColoriSquadraTrasferta'] ?? null);
                                            ?>
                                            <li class="list-group-item border-0 border-bottom py-2 px-2 overflow-auto" id="<?= (int) $blocco['giornata'] ?>-<?= (int) $partita['ID'] ?>">
                                                <div class="row flex-nowrap align-items-center g-1 small" style="min-width: 560px;">
                                                    <div class="col">
                                                        <div class="row flex-nowrap align-items-center g-1 small">
                                                            <div class="col text-end">
                                                                <span class="fw-semibold text-truncate d-block">
                                                                    <span
                                                                        class="squadra d-inline-block text-truncate"
                                                                        style="background-color:<?= htmlspecialchars($coloriCasa['background']) ?>; color:<?= htmlspecialchars($coloriCasa['color']) ?>; border:2px solid <?= htmlspecialchars($coloriCasa['border']) ?>; border-radius:.375rem; padding:.35rem .55rem; max-width:100%;">
                                                                        <?= htmlspecialchars($nomeCasa) ?>
                                                                    </span>
                                                                </span>
                                                            </div>

                                                            <div class="col-auto">
                                                                <div class="input-group input-group-sm flex-nowrap">
                                                                    <input
                                                                        type="number"
                                                                        min="0"
                                                                        max="99"
                                                                        name="partite[<?= (int) $partita['ID'] ?>][goal_casa]"
                                                                        value="<?= $goalCasa !== null ? (int) $goalCasa : '' ?>"
                                                                        class="form-control text-center fw-semibold px-0 js-goal-casa"
                                                                        data-partita-id="<?= (int) $partita['ID'] ?>"
                                                                        placeholder="-"
                                                                        style="width:42px; min-width:42px;">

                                                                    <span class="input-group-text px-1 py-0 border-0 bg-transparent">:</span>

                                                                    <input
                                                                        type="number"
                                                                        min="0"
                                                                        max="99"
                                                                        name="partite[<?= (int) $partita['ID'] ?>][goal_trasferta]"
                                                                        value="<?= $goalTrasferta !== null ? (int) $goalTrasferta : '' ?>"
                                                                        class="form-control text-center fw-semibold px-0 js-goal-trasferta"
                                                                        data-partita-id="<?= (int) $partita['ID'] ?>"
                                                                        placeholder="-"
                                                                        style="width:42px; min-width:42px;">
                                                                </div>
                                                            </div>

                                                            <div class="col text-start">
                                                                <span class="fw-semibold text-truncate d-block">
                                                                    <span
                                                                        class="squadra d-inline-block text-truncate"
                                                                        style="background-color:<?= htmlspecialchars($coloriTrasferta['background']) ?>; color:<?= htmlspecialchars($coloriTrasferta['color']) ?>; border:2px solid <?= htmlspecialchars($coloriTrasferta['border']) ?>; border-radius:.375rem; padding:.35rem .55rem; max-width:100%;">
                                                                        <?= htmlspecialchars($nomeTrasferta) ?>
                                                                    </span>
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="col-auto bg-white p-1 rounded-pill">
                                                        <div class="d-flex gap-1 flex-nowrap justify-content-end">
                                                            <button
                                                                type="button"
                                                                class="btn btn-outline-secondary rounded-circle d-inline-flex align-items-center justify-content-center p-0"
                                                                data-bs-toggle="collapse"
                                                                data-bs-target="#info-<?= (int) $partita['ID'] ?>"
                                                                aria-expanded="false"
                                                                aria-controls="info-<?= (int) $partita['ID'] ?>"
                                                                title="Dettagli"
                                                                style="width:34px; height:34px;">
                                                                <i class="bi bi-info-lg"></i>
                                                            </button>

                                                            <form
                                                                method="post"
                                                                action="/universi/<?= (int) $universo['ID'] ?>/edizioni/<?= (int) $edizione['ID'] ?>/competizioni/<?= (int) $competizione['ID'] ?>/partite/risultato"
                                                                class="m-0 js-form-salva-singola">
                                                                <input type="hidden" name="id_partita" value="<?= (int) $partita['ID'] ?>">
                                                                <input type="hidden" name="goal_casa" value="">
                                                                <input type="hidden" name="goal_trasferta" value="">
                                                                <button
                                                                    type="submit"
                                                                    class="btn btn-primary rounded-circle d-inline-flex align-items-center justify-content-center p-0 js-btn-salva-singola"
                                                                    data-partita-id="<?= (int) $partita['ID'] ?>"
                                                                    title="Salva"
                                                                    style="width:34px; height:34px;">
                                                                    <i class="bi bi-check-lg"></i>
                                                                </button>
                                                            </form>

                                                            <button
                                                                type="submit"
                                                                formaction="/universi/<?= (int) $universo['ID'] ?>/edizioni/<?= (int) $edizione['ID'] ?>/competizioni/<?= (int) $competizione['ID'] ?>/partite/<?= (int) $partita['ID'] ?>/simula"
                                                                formmethod="post"
                                                                class="btn btn-outline-primary rounded-circle d-inline-flex align-items-center justify-content-center p-0"
                                                                title="Simula"
                                                                style="width:34px; height:34px;">
                                                                <i class="bi bi-dice-3"></i>
                                                            </button>

                                                            <button
                                                                type="submit"
                                                                formaction="/universi/<?= (int) $universo['ID'] ?>/edizioni/<?= (int) $edizione['ID'] ?>/competizioni/<?= (int) $competizione['ID'] ?>/partite/<?= (int) $partita['ID'] ?>/reset"
                                                                formmethod="post"
                                                                class="btn btn-outline-danger rounded-circle d-inline-flex align-items-center justify-content-center p-0"
                                                                title="Elimina"
                                                                style="width:34px; height:34px;">
                                                                <i class="bi bi-x-lg"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="collapse mt-2" id="info-<?= (int) $partita['ID'] ?>">
                                                    <div class="px-2 pb-2">
                                                        <div class="bg-light border rounded-3 p-2 p-md-3 small">
                                                            <div class="row g-2">
                                                                <div class="col-12 col-md-4">
                                                                    <div class="text-muted text-uppercase small mb-1">Stato</div>
                                                                    <span class="badge <?= uc_stato_badge_class($stato) ?>">
                                                                        <?= htmlspecialchars($stato ?: 'sconosciuto') ?>
                                                                    </span>
                                                                </div>

                                                                <div class="col-12 col-md-4">
                                                                    <div class="text-muted text-uppercase small mb-1">Power casa</div>
                                                                    <div class="fw-semibold">
                                                                        <?php if (!empty($preview['casa']['rating_globale'])): ?>
                                                                            <?= number_format((float) $preview['casa']['rating_globale'], 1, ',', '.') ?>
                                                                        <?php else: ?>
                                                                            —
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>

                                                                <div class="col-12 col-md-4">
                                                                    <div class="text-muted text-uppercase small mb-1">Power trasferta</div>
                                                                    <div class="fw-semibold">
                                                                        <?php if (!empty($preview['trasferta']['rating_globale'])): ?>
                                                                            <?= number_format((float) $preview['trasferta']['rating_globale'], 1, ',', '.') ?>
                                                                        <?php else: ?>
                                                                            —
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>

                                                                <div class="col-12">
                                                                    <div class="text-muted text-uppercase small mb-1">Pronostico</div>
                                                                    <div class="fw-semibold">
                                                                        <?= $esitoAtteso !== '' ? htmlspecialchars($esitoAtteso) : 'Nessun dettaglio disponibile' ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>

                                <div class="card-footer bg-white">
                                    <div class="small text-muted">
                                        <?= htmlspecialchars((string) $blocco['titolo']) ?> completato: <?= count($blocco['partite']) ?> partite visibili.
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </form>
    </div>

    <?php require __DIR__ . '/../../partials/script.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const buttons = document.querySelectorAll('.js-btn-salva-singola');

            buttons.forEach(function(button) {
                button.addEventListener('click', function() {
                    const partitaId = button.getAttribute('data-partita-id');
                    const form = button.closest('.js-form-salva-singola');

                    if (!partitaId || !form) {
                        return;
                    }

                    const inputCasa = document.querySelector('.js-goal-casa[data-partita-id="' + partitaId + '"]');
                    const inputTrasferta = document.querySelector('.js-goal-trasferta[data-partita-id="' + partitaId + '"]');

                    const hiddenCasa = form.querySelector('input[name="goal_casa"]');
                    const hiddenTrasferta = form.querySelector('input[name="goal_trasferta"]');

                    if (inputCasa && hiddenCasa) {
                        hiddenCasa.value = inputCasa.value;
                    }

                    if (inputTrasferta && hiddenTrasferta) {
                        hiddenTrasferta.value = inputTrasferta.value;
                    }
                });
            });

            if (window.location.hash) {
                const target = document.querySelector(window.location.hash);

                if (target) {
                    setTimeout(function() {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }, 50);
                }
            }
        });
    </script>
</body>

</html>