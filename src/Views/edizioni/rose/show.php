<?php

/** @var array $universo */
/** @var array $edizione */
/** @var array $squadra */
/** @var array $giocatoriAssegnati */
/** @var array $verificaRosa */

$raggruppati = [
    'POR' => [],
    'DIF' => [],
    'CEN' => [],
    'OFF' => [],
    'ALT' => [],
];

foreach ($giocatoriAssegnati as $giocatore) {
    $posizione = strtoupper(trim((string) ($giocatore['Posizione'] ?? '')));

    if ($posizione === 'POR') {
        $raggruppati['POR'][] = $giocatore;
    } elseif (in_array($posizione, ['TD', 'TS', 'DC'], true)) {
        $raggruppati['DIF'][] = $giocatore;
    } elseif (in_array($posizione, ['CC', 'MED', 'CS', 'CD', 'TRQ'], true)) {
        $raggruppati['CEN'][] = $giocatore;
    } elseif (in_array($posizione, ['AS', 'AD', 'ATT'], true)) {
        $raggruppati['OFF'][] = $giocatore;
    } else {
        $raggruppati['ALT'][] = $giocatore;
    }
}

$etichetteRuolo = [
    'POR' => 'Portieri',
    'DIF' => 'Difensivi',
    'CEN' => 'Centrocampo',
    'OFF' => 'Offensivi',
    'ALT' => 'Altri',
];

$conteggi = $verificaRosa['conteggi'] ?? [];
$ok = (bool) ($verificaRosa['ok'] ?? false);
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
            <a
                href="/universi/<?= (int) ($universo['ID'] ?? 0) ?>/edizioni/<?= (int) ($edizione['ID'] ?? 0) ?>/rose"
                class="link-secondary text-decoration-none d-inline-block mb-3">
                ← Torna alle rose
            </a>

            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-start gap-3">
                        <div>
                            <div class="text-uppercase text-muted small fw-semibold mb-2">Rosa squadra</div>
                            <h1 class="h2 mb-2"><?= htmlspecialchars((string) ($squadra['Nome'] ?? '')) ?></h1>
                            <p class="text-muted mb-0">
                                Visualizzazione della rosa registrata per l’edizione
                                <?= htmlspecialchars((string) ($edizione['Nome'] ?? '')) ?>.
                            </p>
                        </div>

                        <div class="text-lg-end">
                            <div class="mb-2">
                                <span class="badge <?= $ok ? 'text-bg-success' : 'text-bg-warning' ?> px-3 py-2">
                                    <?= $ok ? 'Rosa completa' : 'Rosa incompleta' ?>
                                </span>
                            </div>
                            <div class="small text-muted">
                                Totale giocatori: <?= (int) ($conteggi['totale'] ?? 0) ?>/18
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-6 col-md-4 col-xl-2">
                            <div class="rounded bg-light p-3 h-100">
                                <div class="text-muted small mb-1">Totale</div>
                                <div class="fw-semibold fs-5"><?= (int) ($conteggi['totale'] ?? 0) ?>/18</div>
                            </div>
                        </div>

                        <div class="col-6 col-md-4 col-xl-2">
                            <div class="rounded bg-light p-3 h-100">
                                <div class="text-muted small mb-1">Portieri</div>
                                <div class="fw-semibold fs-5"><?= (int) ($conteggi['POR'] ?? 0) ?>/2</div>
                            </div>
                        </div>

                        <div class="col-6 col-md-4 col-xl-2">
                            <div class="rounded bg-light p-3 h-100">
                                <div class="text-muted small mb-1">Difensivi</div>
                                <div class="fw-semibold fs-5"><?= (int) ($conteggi['difensivi'] ?? 0) ?>/5</div>
                            </div>
                        </div>

                        <div class="col-6 col-md-4 col-xl-2">
                            <div class="rounded bg-light p-3 h-100">
                                <div class="text-muted small mb-1">Centrocampo</div>
                                <div class="fw-semibold fs-5"><?= (int) ($conteggi['centrocampo'] ?? 0) ?>/6</div>
                            </div>
                        </div>

                        <div class="col-6 col-md-4 col-xl-2">
                            <div class="rounded bg-light p-3 h-100">
                                <div class="text-muted small mb-1">Offensivi</div>
                                <div class="fw-semibold fs-5"><?= (int) ($conteggi['offensivi'] ?? 0) ?>/5</div>
                            </div>
                        </div>

                        <div class="col-6 col-md-4 col-xl-2">
                            <div class="rounded bg-light p-3 h-100">
                                <div class="text-muted small mb-1">Stato</div>
                                <div class="fw-semibold"><?= $ok ? 'Completa' : 'Da completare' ?></div>
                            </div>
                        </div>
                    </div>

                    <?php if (!$ok): ?>
                        <div class="alert alert-warning mt-4 mb-0">
                            Questa rosa non soddisfa ancora tutti i vincoli minimi richiesti.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <?php foreach ($raggruppati as $chiave => $giocatori): ?>
                <?php if ($giocatori === []) continue; ?>
                <div class="col-12 col-xl-6">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h2 class="h5 mb-0"><?= htmlspecialchars($etichetteRuolo[$chiave]) ?></h2>
                                <span class="badge text-bg-secondary"><?= count($giocatori) ?></span>
                            </div>

                            <div class="list-group list-group-flush">
                                <?php foreach ($giocatori as $giocatore): ?>
                                    <div class="list-group-item px-0">
                                        <div class="d-flex justify-content-between align-items-start gap-3">
                                            <div>
                                                <div class="fw-semibold">
                                                    <?= htmlspecialchars((string) ($giocatore['Nome'] ?? '')) ?>
                                                </div>
                                                <div class="small text-muted">
                                                    <?= htmlspecialchars((string) ($giocatore['Posizione'] ?? '')) ?>
                                                    <?php if (!empty($giocatore['Paese'])): ?>
                                                        · <?= htmlspecialchars((string) $giocatore['Paese']) ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <div class="text-end small text-muted">
                                                <div>ATT <?= htmlspecialchars((string) ($giocatore['Attacco'] ?? '0')) ?></div>
                                                <div>DIF <?= htmlspecialchars((string) ($giocatore['Difesa'] ?? '0')) ?></div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if ($giocatoriAssegnati === []): ?>
                <div class="col-12">
                    <div class="alert alert-light border shadow-sm mb-0">
                        Nessun giocatore assegnato a questa squadra.
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php require __DIR__ . '/../../partials/script.php'; ?>
</body>

</html>