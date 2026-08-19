<?php

/** @var array $universo */
/** @var array $edizione */
/** @var array $competizione */
/** @var array $blocchiPartite */
/** @var array $statoEliminazione */
/** @var array $fasiBloccate */
/** @var array $analisiChiusura */

use App\Support\Icons;

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
        <?php
        $statoCompetizione = (string) ($statoCompetizione ?? ($competizione['Stato'] ?? 'in_corso'));
        $isConclusa = (bool) ($isConclusa ?? ($statoCompetizione === 'conclusa'));
        $analisiChiusura = is_array($analisiChiusura) ? $analisiChiusura : ['ok' => false, 'motivi' => []];
        ?>

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

                <?php if ($isConclusa): ?>
                    <button type="button" class="btn btn-success" disabled>
                        Competizione conclusa
                    </button>
                <?php elseif (!empty($analisiChiusura['ok'])): ?>
                    <form
                        method="post"
                        action="/universi/<?= (int) $universo['ID'] ?>/edizioni/<?= (int) $edizione['ID'] ?>/competizioni/<?= (int) $competizione['ID'] ?>/chiudi">
                        <button type="submit" class="btn btn-success">
                            Chiudi competizione
                        </button>
                    </form>
                <?php else: ?>
                    <button type="button" class="btn btn-outline-secondary" disabled>
                        Competizione non chiudibile
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($isConclusa): ?>
            <div class="alert alert-success mb-4">
                <strong>Competizione conclusa.</strong> I risultati non sono più modificabili.
            </div>
        <?php endif; ?>

        <?php if (!$isConclusa && !empty($analisiChiusura['motivi'])): ?>
            <div class="alert alert-info mb-4">
                <strong>Chiusura non disponibile.</strong>
                <ul class="mb-0 mt-2">
                    <?php foreach ($analisiChiusura['motivi'] as $motivo): ?>
                        <li><?= htmlspecialchars((string) $motivo) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!$isConclusa && $isEliminazioneDiretta && !empty($statoEliminazione['bloccanti'])): ?>
            <div class="alert alert-warning mb-4" id="warning-eliminazione">
                <strong>Avanzamento bloccato.</strong> Alcuni accoppiamenti sono ancora in parità o incompleti.
                <ul class="mb-0 mt-2">
                    <?php foreach ($statoEliminazione['bloccanti'] as $bloccoErrore): ?>
                        <li>
                            Accoppiamento <?= (int) ($bloccoErrore['numero_accoppiamento'] ?? 0) ?>:
                            <?= htmlspecialchars((string) ($bloccoErrore['motivo'] ?? 'Non disponibile')) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (
            !$isConclusa
            && $isEliminazioneDiretta
            && empty($statoEliminazione['bloccanti'])
            && !empty($statoEliminazione['turno'])
        ): ?>
            <form
                method="post"
                action="/universi/<?= (int) $universo['ID'] ?>/edizioni/<?= (int) $edizione['ID'] ?>/competizioni/<?= (int) $competizione['ID'] ?>/eliminazione/avanza"
                class="mb-4">
                <button type="submit" class="btn btn-primary">
                    Passa alla fase successiva
                </button>
            </form>
        <?php endif; ?>

        <form
            method="post"
            action="/universi/<?= (int) $universo['ID'] ?>/edizioni/<?= (int) $edizione['ID'] ?>/competizioni/<?= (int) $competizione['ID'] ?>/partite/salva-tutte"
            id="form-salva-tutto">
        </form>

        <div class="card uc-page-hero shadow-sm border-0 mb-4">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                    <div>
                        <div class="d-flex flex-wrap gap-2 mb-2">
                            <span class="badge rounded-pill text-bg-dark">Competizione</span>
                            <span class="badge rounded-pill text-bg-light"><?= htmlspecialchars((string) ($competizione['Tipo'] ?? '')) ?></span>
                            <span class="badge rounded-pill text-bg-light"><?= $totalePartite ?> match</span>
                            <span class="badge rounded-pill <?= $isConclusa ? 'text-bg-success' : 'text-bg-warning' ?>">
                                <?= $isConclusa ? 'Conclusa' : 'In corso' ?>
                            </span>
                        </div>
                        <div class="small text-muted">
                            <?= $isConclusa
                                ? 'Competizione chiusa: i risultati restano disponibili in sola lettura.'
                                : 'Gestisci risultati, simulazioni e aggiornamenti per ogni blocco.' ?>
                        </div>
                    </div>

                    <?php if ($isLega): ?>
                        <div class="d-flex flex-wrap gap-2">
                            <button
                                type="submit"
                                form="form-salva-tutto"
                                class="btn btn-primary"
                                <?= $isConclusa ? 'disabled' : '' ?>>
                                Salva tutto
                            </button>

                            <button
                                type="submit"
                                form="form-salva-tutto"
                                formaction="/universi/<?= (int) $universo['ID'] ?>/edizioni/<?= (int) $edizione['ID'] ?>/competizioni/<?= (int) $competizione['ID'] ?>/partite/simula-tutte"
                                formmethod="post"
                                class="btn btn-outline-primary"
                                <?= $isConclusa ? 'disabled' : '' ?>>
                                Simula tutto
                            </button>

                            <button
                                type="submit"
                                form="form-salva-tutto"
                                formaction="/universi/<?= (int) $universo['ID'] ?>/edizioni/<?= (int) $edizione['ID'] ?>/competizioni/<?= (int) $competizione['ID'] ?>/partite/reset-tutte"
                                formmethod="post"
                                class="btn btn-outline-danger"
                                <?= $isConclusa ? 'disabled' : '' ?>>
                                Elimina tutto
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-light border mb-0 py-2 px-3 small">
                            <?= $isConclusa
                                ? 'La coppa è conclusa e non può più essere modificata.'
                                : 'Nelle coppe conviene agire per blocco o per singola partita.' ?>
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
                    <?php
                    $faseBlocco = (string) ($blocco['fase'] ?? '');
                    $faseBloccata = $isEliminazioneDiretta && $faseBlocco !== ''
                        ? (bool) ($fasiBloccate[$faseBlocco] ?? false)
                        : false;
                    $bloccoNonModificabile = $faseBloccata || $isConclusa;

                    $primaPartitaBlocco = $blocco['partite'][0] ?? null;
                    $dettagliBlocco = $primaPartitaBlocco ? uc_leggi_dettagli_partita($primaPartitaBlocco) : [];
                    ?>
                    <div class="col-12 col-xl-6" id="<?= htmlspecialchars((string) $blocco['anchor']) ?>">
                        <div class="card shadow-sm h-100 uc-match-card <?= $bloccoNonModificabile ? 'border-secondary' : '' ?>">
                            <div class="card-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 py-3 bg-white">
                                <div class="text-center text-md-start w-100">
                                    <h3 class="h5 fw-semibold mb-1">
                                        <?php if ($faseBlocco !== ''): ?>
                                            <?= htmlspecialchars($faseBlocco) ?> ·
                                        <?php endif; ?>
                                        Giornata <?= (int) ($blocco['giornata'] ?? 0) ?>
                                    </h3>

                                    <div class="small text-muted d-flex flex-wrap justify-content-center justify-content-md-start align-items-center gap-2">
                                        <span><?= count($blocco['partite']) ?> partite</span>

                                        <?php if (!empty($dettagliBlocco['giro'])): ?>
                                            <span class="badge text-bg-light border">
                                                Giro <?= (int) $dettagliBlocco['giro'] ?>
                                            </span>
                                        <?php endif; ?>

                                        <?php if ($faseBloccata): ?>
                                            <span class="badge text-bg-secondary">Turno bloccato</span>
                                        <?php endif; ?>

                                        <?php if ($isConclusa): ?>
                                            <span class="badge text-bg-success">Sola lettura</span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="d-flex flex-wrap gap-2 uc-block-toolbar">
                                    <?php if (!$bloccoNonModificabile): ?>
                                        <?php if ($faseBlocco !== ''): ?>
                                            <button
                                                type="submit"
                                                form="form-salva-tutto"
                                                formaction="/universi/<?= (int) $universo['ID'] ?>/edizioni/<?= (int) $edizione['ID'] ?>/competizioni/<?= (int) $competizione['ID'] ?>/fasi/<?= urlencode($faseBlocco) ?>/giornate/<?= (int) $blocco['giornata'] ?>/salva"
                                                formmethod="post"
                                                class="btn btn-sm btn-primary">
                                                Salva
                                            </button>

                                            <button
                                                type="submit"
                                                form="form-salva-tutto"
                                                formaction="/universi/<?= (int) $universo['ID'] ?>/edizioni/<?= (int) $edizione['ID'] ?>/competizioni/<?= (int) $competizione['ID'] ?>/fasi/<?= urlencode($faseBlocco) ?>/giornate/<?= (int) $blocco['giornata'] ?>/simula"
                                                formmethod="post"
                                                class="btn btn-sm btn-outline-primary">
                                                Simula
                                            </button>

                                            <button
                                                type="submit"
                                                form="form-salva-tutto"
                                                formaction="/universi/<?= (int) $universo['ID'] ?>/edizioni/<?= (int) $edizione['ID'] ?>/competizioni/<?= (int) $competizione['ID'] ?>/fasi/<?= urlencode($faseBlocco) ?>/giornate/<?= (int) $blocco['giornata'] ?>/reset"
                                                formmethod="post"
                                                class="btn btn-sm btn-outline-danger">
                                                Elimina
                                            </button>
                                        <?php else: ?>
                                            <button
                                                type="submit"
                                                form="form-salva-tutto"
                                                formaction="/universi/<?= (int) $universo['ID'] ?>/edizioni/<?= (int) $edizione['ID'] ?>/competizioni/<?= (int) $competizione['ID'] ?>/giornate/<?= (int) $blocco['giornata'] ?>/salva"
                                                formmethod="post"
                                                class="btn btn-sm btn-primary">
                                                Salva
                                            </button>

                                            <button
                                                type="submit"
                                                form="form-salva-tutto"
                                                formaction="/universi/<?= (int) $universo['ID'] ?>/edizioni/<?= (int) $edizione['ID'] ?>/competizioni/<?= (int) $competizione['ID'] ?>/giornate/<?= (int) $blocco['giornata'] ?>/simula"
                                                formmethod="post"
                                                class="btn btn-sm btn-outline-primary">
                                                Simula
                                            </button>

                                            <button
                                                type="submit"
                                                form="form-salva-tutto"
                                                formaction="/universi/<?= (int) $universo['ID'] ?>/edizioni/<?= (int) $edizione['ID'] ?>/competizioni/<?= (int) $competizione['ID'] ?>/giornate/<?= (int) $blocco['giornata'] ?>/reset"
                                                formmethod="post"
                                                class="btn btn-sm btn-outline-danger">
                                                Elimina
                                            </button>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" disabled>
                                            <?= $isConclusa ? 'Competizione conclusa' : 'Turno bloccato' ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="card-body p-0">
                                <?php if ($faseBloccata): ?>
                                    <div class="alert alert-secondary rounded-0 border-0 border-bottom mb-0 small">
                                        Questo turno non è più modificabile perché è già stata generata una fase successiva.
                                    </div>
                                <?php endif; ?>

                                <?php if ($isConclusa): ?>
                                    <div class="alert alert-success rounded-0 border-0 border-bottom mb-0 small">
                                        Competizione conclusa: modifiche disabilitate.
                                    </div>
                                <?php endif; ?>

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

                                        $singleFormId = 'form-salva-partita-' . (int) $partita['ID'];
                                        $inputCasaId = 'goal-casa-' . (int) $partita['ID'];
                                        $inputTrasfertaId = 'goal-trasferta-' . (int) $partita['ID'];
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
                                                                    id="<?= htmlspecialchars($inputCasaId) ?>"
                                                                    type="number"
                                                                    min="0"
                                                                    max="99"
                                                                    name="partite[<?= (int) $partita['ID'] ?>][goal_casa]"
                                                                    form="form-salva-tutto"
                                                                    value="<?= $goalCasa !== null ? (int) $goalCasa : '' ?>"
                                                                    class="form-control text-center fw-semibold px-0 js-goal-casa"
                                                                    data-partita-id="<?= (int) $partita['ID'] ?>"
                                                                    placeholder="-"
                                                                    style="width:42px; min-width:42px;"
                                                                    <?= $bloccoNonModificabile ? 'disabled' : '' ?>>

                                                                <span class="input-group-text px-1 py-0 border-0 bg-transparent">:</span>

                                                                <input
                                                                    id="<?= htmlspecialchars($inputTrasfertaId) ?>"
                                                                    type="number"
                                                                    min="0"
                                                                    max="99"
                                                                    name="partite[<?= (int) $partita['ID'] ?>][goal_trasferta]"
                                                                    form="form-salva-tutto"
                                                                    value="<?= $goalTrasferta !== null ? (int) $goalTrasferta : '' ?>"
                                                                    class="form-control text-center fw-semibold px-0 js-goal-trasferta"
                                                                    data-partita-id="<?= (int) $partita['ID'] ?>"
                                                                    placeholder="-"
                                                                    style="width:42px; min-width:42px;"
                                                                    <?= $bloccoNonModificabile ? 'disabled' : '' ?>>
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

                                                        <?php if (!$bloccoNonModificabile): ?>
                                                            <form
                                                                method="post"
                                                                action="/universi/<?= (int) $universo['ID'] ?>/edizioni/<?= (int) $edizione['ID'] ?>/competizioni/<?= (int) $competizione['ID'] ?>/partite/risultato"
                                                                id="<?= htmlspecialchars($singleFormId) ?>"
                                                                class="d-none">
                                                                <input type="hidden" name="id_partita" value="<?= (int) $partita['ID'] ?>">
                                                                <input type="hidden" name="goal_casa" value="">
                                                                <input type="hidden" name="goal_trasferta" value="">
                                                            </form>

                                                            <button
                                                                type="submit"
                                                                form="<?= htmlspecialchars($singleFormId) ?>"
                                                                class="btn btn-primary rounded-circle d-inline-flex align-items-center justify-content-center p-0 js-btn-salva-singola"
                                                                data-partita-id="<?= (int) $partita['ID'] ?>"
                                                                data-form-id="<?= htmlspecialchars($singleFormId) ?>"
                                                                title="Salva"
                                                                style="width:34px; height:34px;">
                                                                <i class="bi bi-check-lg"></i>
                                                            </button>

                                                            <form
                                                                method="post"
                                                                action="/universi/<?= (int) $universo['ID'] ?>/edizioni/<?= (int) $edizione['ID'] ?>/competizioni/<?= (int) $competizione['ID'] ?>/partite/<?= (int) $partita['ID'] ?>/simula"
                                                                class="d-none"
                                                                id="form-simula-partita-<?= (int) $partita['ID'] ?>">
                                                            </form>

                                                            <button
                                                                type="submit"
                                                                form="form-simula-partita-<?= (int) $partita['ID'] ?>"
                                                                class="btn btn-outline-primary rounded-circle d-inline-flex align-items-center justify-content-center p-0"
                                                                title="Simula"
                                                                style="width:34px; height:34px;">
                                                                <i class="bi bi-dice-3"></i>
                                                            </button>

                                                            <form
                                                                method="post"
                                                                action="/universi/<?= (int) $universo['ID'] ?>/edizioni/<?= (int) $edizione['ID'] ?>/competizioni/<?= (int) $competizione['ID'] ?>/partite/<?= (int) $partita['ID'] ?>/reset"
                                                                class="d-none"
                                                                id="form-reset-partita-<?= (int) $partita['ID'] ?>">
                                                            </form>

                                                            <button
                                                                type="submit"
                                                                form="form-reset-partita-<?= (int) $partita['ID'] ?>"
                                                                class="btn btn-outline-danger rounded-circle d-inline-flex align-items-center justify-content-center p-0"
                                                                title="Elimina"
                                                                style="width:34px; height:34px;">
                                                                <i class="bi bi-x-lg"></i>
                                                            </button>
                                                        <?php else: ?>
                                                            <button
                                                                type="button"
                                                                class="btn btn-outline-secondary rounded-circle d-inline-flex align-items-center justify-content-center p-0"
                                                                title="<?= $isConclusa ? 'Competizione conclusa' : 'Turno bloccato' ?>"
                                                                style="width:34px; height:34px;"
                                                                disabled>
                                                                <i class="bi bi-lock"></i>
                                                            </button>
                                                        <?php endif; ?>
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

                                                    <?php $eventi = $partita['Eventi'] ?? []; ?>
                                                    <?php if (!empty($eventi)): ?>
                                                        <div class="text-muted text-uppercase small mb-2 mt-3 text-center">Eventi</div>

                                                        <?php
                                                        $idSquadraCasa = (int) ($partita['IDSquadraCasa'] ?? 0);
                                                        $idSquadraTrasferta = (int) ($partita['IDSquadraTrasferta'] ?? 0);
                                                        ?>

                                                        <div class="position-relative">
                                                            <div class="position-absolute top-0 start-50 border-start h-100" style="z-index: 0;"></div>

                                                            <?php foreach ($eventi as $evento): ?>
                                                                <?php
                                                                $tipo = (string) ($evento['Tipo'] ?? '');
                                                                $minuto = (int) ($evento['Minuto'] ?? 0);
                                                                $idSquadraEvento = (int) ($evento['IDSquadra'] ?? 0);
                                                                $giocatore = trim((string) ($evento['NomeGiocatoreCompleto'] ?? ''));
                                                                $assist = trim((string) ($evento['NomeAssist'] ?? ''));
                                                                $dettagliEvento = $evento['DettagliArray'] ?? [];

                                                                $isCasaEvento = $idSquadraEvento === $idSquadraCasa;
                                                                $eventoUi = Icons::evento($tipo, $dettagliEvento);
                                                                $assistHtml = ($tipo === 'gol' && !empty($dettagliEvento['assist_id']) && $assist !== '')
                                                                    ? Icons::assist($assist)
                                                                    : '';
                                                                ?>

                                                                <div class="row align-items-center g-2 py-1 position-relative" style="z-index: 1;">
                                                                    <?php if ($isCasaEvento): ?>
                                                                        <div class="col-5 text-end">
                                                                            <div class="d-inline-flex align-items-center gap-2 px-2 py-1 rounded bg-light border">
                                                                                <?= $eventoUi['icon'] ?>
                                                                                <span class="small">
                                                                                    <strong><?= htmlspecialchars($eventoUi['label']) ?></strong>
                                                                                    <?php if ($giocatore !== ''): ?>
                                                                                        - <?= htmlspecialchars($giocatore) ?>
                                                                                    <?php endif; ?>
                                                                                </span>
                                                                            </div>
                                                                            <?= $assistHtml ?>
                                                                        </div>
                                                                    <?php else: ?>
                                                                        <div class="col-5"></div>
                                                                    <?php endif; ?>

                                                                    <div class="col-2 text-center small fw-semibold text-muted">
                                                                        <span class="d-inline-block px-2 py-1 rounded bg-white border">
                                                                            <?= $minuto ?>′
                                                                        </span>
                                                                    </div>

                                                                    <?php if (!$isCasaEvento): ?>
                                                                        <div class="col-5 text-start">
                                                                            <div class="d-inline-flex align-items-center gap-2 px-2 py-1 rounded bg-light border">
                                                                                <?= $eventoUi['icon'] ?>
                                                                                <span class="small">
                                                                                    <strong><?= htmlspecialchars($eventoUi['label']) ?></strong>
                                                                                    <?php if ($giocatore !== ''): ?>
                                                                                        - <?= htmlspecialchars($giocatore) ?>
                                                                                    <?php endif; ?>
                                                                                </span>
                                                                            </div>
                                                                            <?= $assistHtml ?>
                                                                        </div>
                                                                    <?php else: ?>
                                                                        <div class="col-5"></div>
                                                                    <?php endif; ?>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php endif; ?>
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
    </div>

    <?php require __DIR__ . '/../../partials/script.php'; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const buttons = document.querySelectorAll('.js-btn-salva-singola');

            buttons.forEach(function(button) {
                button.addEventListener('click', function() {
                    const partitaId = button.getAttribute('data-partita-id');
                    const formId = button.getAttribute('data-form-id');
                    const form = formId ? document.getElementById(formId) : null;

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