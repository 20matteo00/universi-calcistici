<?php

/** @var array $universo */
/** @var array $edizione */
/** @var array $competizione */
/** @var array $giornate */
/** @var int $giornataDa */
/** @var int $giornataA */
/** @var string $tabAttiva */
/** @var array $datiClassifica */
/** @var array $tabellaCapolista */
/** @var array $visteClassifica */

$giornate = $giornate ?? [];
$giornataDa = isset($giornataDa) ? (int) $giornataDa : (!empty($giornate) ? (int) min($giornate) : 1);
$giornataA = isset($giornataA) ? (int) $giornataA : (!empty($giornate) ? (int) max($giornate) : $giornataDa);
$tabAttiva = (string) ($tabAttiva ?? 'generale');

$visteClassifica = $visteClassifica ?? [];
$datiClassifica = $datiClassifica ?? [];
$tabellaCapolista = $tabellaCapolista ?? [];

$struttura = json_decode((string) ($competizione['Struttura'] ?? '{}'), true);
if (!is_array($struttura)) {
    $struttura = [];
}

$numeroGiri = (int) ($struttura['giri'] ?? 0);

$tabs = [
    'generale' => 'Generale',
    'casa' => 'Casa',
    'trasferta' => 'Trasferta',
];

for ($i = 1; $i <= $numeroGiri; $i++) {
    $tabs['giro_' . $i] = 'Giro ' . $i;
}

if (empty($visteClassifica)) {
    $visteClassifica = [
        'generale' => $datiClassifica,
    ];
}

if (!isset($visteClassifica[$tabAttiva])) {
    $chiavi = array_keys($visteClassifica);
    $tabAttiva = $chiavi[0] ?? 'generale';
}

$righeClassifica = $visteClassifica[$tabAttiva] ?? [];

function badgeFormaClass(string $esito): string
{
    return match ($esito) {
        'V' => 'success',
        'N' => 'warning',
        'P' => 'danger',
        default => 'secondary',
    };
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Classifica · <?= htmlspecialchars((string) ($competizione['NomeCompetizione'] ?? $competizione['Nome'] ?? 'Competizione')) ?></title>
    <?php require __DIR__ . '/../../partials/link.php'; ?>
</head>
<body>
    <div class="container py-4">
        <div class="mx-auto">
            <div class="mb-4">
                <a
                    href="/universi/<?= (int) ($universo['ID'] ?? 0) ?>/edizioni/<?= (int) ($edizione['ID'] ?? 0) ?>/competizioni/<?= (int) ($competizione['ID'] ?? 0) ?>"
                    class="link-secondary text-decoration-none d-inline-block mb-2"
                >
                    ← Torna alla competizione
                </a>

                <div class="d-flex flex-column flex-lg-row align-items-lg-start justify-content-between gap-3">
                    <div>
                        <h1 class="h2 mb-1">Classifica</h1>
                        <p class="text-muted mb-0">
                            <?= htmlspecialchars((string) ($competizione['NomeCompetizione'] ?? $competizione['Nome'] ?? 'Competizione')) ?>
                            · <?= htmlspecialchars((string) ($edizione['Nome'] ?? 'Edizione')) ?>
                        </p>
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        <a
                            href="/universi/<?= (int) ($universo['ID'] ?? 0) ?>/edizioni/<?= (int) ($edizione['ID'] ?? 0) ?>/competizioni/<?= (int) ($competizione['ID'] ?? 0) ?>"
                            class="btn btn-outline-secondary"
                        >
                            Calendario
                        </a>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <form method="get" class="row g-3 align-items-end">
                        <div class="col-12 col-md-4">
                            <label for="giornata_da" class="form-label">Da giornata</label>
                            <select name="giornata_da" id="giornata_da" class="form-select">
                                <?php foreach ($giornate as $giornata): ?>
                                    <option value="<?= (int) $giornata ?>" <?= (int) $giornata === $giornataDa ? 'selected' : '' ?>>
                                        <?= (int) $giornata ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 col-md-4">
                            <label for="giornata_a" class="form-label">A giornata</label>
                            <select name="giornata_a" id="giornata_a" class="form-select">
                                <?php foreach ($giornate as $giornata): ?>
                                    <option value="<?= (int) $giornata ?>" <?= (int) $giornata === $giornataA ? 'selected' : '' ?>>
                                        <?= (int) $giornata ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 col-md-4">
                            <label for="tab" class="form-label">Vista</label>
                            <select name="tab" id="tab" class="form-select">
                                <?php foreach ($tabs as $chiaveTab => $etichettaTab): ?>
                                    <?php if (!array_key_exists($chiaveTab, $visteClassifica)) continue; ?>
                                    <option value="<?= htmlspecialchars($chiaveTab) ?>" <?= $chiaveTab === $tabAttiva ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($etichettaTab) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary">Aggiorna classifica</button>

                            <a
                                href="/universi/<?= (int) ($universo['ID'] ?? 0) ?>/edizioni/<?= (int) ($edizione['ID'] ?? 0) ?>/competizioni/<?= (int) ($competizione['ID'] ?? 0) ?>/classifica"
                                class="btn btn-outline-secondary"
                            >
                                Reset filtri
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-3">
                        <div>
                            <h2 class="h5 mb-1">Vista classifica</h2>
                            <p class="text-muted mb-0">
                                Intervallo selezionato: dalla giornata <?= (int) $giornataDa ?> alla giornata <?= (int) $giornataA ?>.
                            </p>
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($tabs as $chiaveTab => $etichettaTab): ?>
                                <?php if (!array_key_exists($chiaveTab, $visteClassifica)) continue; ?>
                                <a
                                    href="?giornata_da=<?= (int) $giornataDa ?>&giornata_a=<?= (int) $giornataA ?>&tab=<?= urlencode($chiaveTab) ?>"
                                    class="btn <?= $chiaveTab === $tabAttiva ? 'btn-primary' : 'btn-outline-primary' ?>"
                                >
                                    <?= htmlspecialchars($etichettaTab) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <?php if (empty($righeClassifica)): ?>
                        <div class="alert alert-info mb-0">
                            Nessun dato disponibile per l’intervallo selezionato.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Squadra</th>
                                        <th class="text-center">G</th>
                                        <th class="text-center">V</th>
                                        <th class="text-center">N</th>
                                        <th class="text-center">P</th>
                                        <th class="text-center">GF</th>
                                        <th class="text-center">GS</th>
                                        <th class="text-center">DR</th>
                                        <th class="text-center">Pt</th>
                                        <th>Forma</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($righeClassifica as $indice => $riga): ?>
                                        <?php
                                        $forma = $riga['Forma'] ?? [];
                                        if (is_string($forma)) {
                                            $forma = preg_split('//u', $forma, -1, PREG_SPLIT_NO_EMPTY) ?: [];
                                        }
                                        ?>
                                        <tr>
                                            <td class="fw-semibold"><?= (int) ($riga['Posizione'] ?? ($indice + 1)) ?></td>
                                            <td><?= htmlspecialchars((string) ($riga['Nome'] ?? '')) ?></td>
                                            <td class="text-center"><?= (int) ($riga['Giocate'] ?? 0) ?></td>
                                            <td class="text-center"><?= (int) ($riga['Vinte'] ?? 0) ?></td>
                                            <td class="text-center"><?= (int) ($riga['Pareggiate'] ?? 0) ?></td>
                                            <td class="text-center"><?= (int) ($riga['Perse'] ?? 0) ?></td>
                                            <td class="text-center"><?= (int) ($riga['Fatti'] ?? 0) ?></td>
                                            <td class="text-center"><?= (int) ($riga['Subiti'] ?? 0) ?></td>
                                            <td class="text-center"><?= (int) ($riga['DifferenzaReti'] ?? 0) ?></td>
                                            <td class="text-center fw-bold"><?= (int) ($riga['Punti'] ?? 0) ?></td>
                                            <td>
                                                <div class="d-flex flex-wrap gap-1">
                                                    <?php if (empty($forma)): ?>
                                                        <span class="text-muted small">—</span>
                                                    <?php else: ?>
                                                        <?php foreach ($forma as $esito): ?>
                                                            <span class="badge text-bg-<?= badgeFormaClass((string) $esito) ?>">
                                                                <?= htmlspecialchars((string) $esito) ?>
                                                            </span>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
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

            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="mb-3">
                        <h2 class="h5 mb-1">Capolista per giornata</h2>
                        <p class="text-muted mb-0">
                            Evoluzione della vetta della classifica lungo il campionato.
                        </p>
                    </div>

                    <?php if (empty($tabellaCapolista)): ?>
                        <div class="alert alert-light mb-0">
                            La tabella capolista non è ancora disponibile.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Giornata</th>
                                        <th>Capolista</th>
                                        <th class="text-center">Punti</th>
                                        <th class="text-center">Vantaggio</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tabellaCapolista as $rigaCapolista): ?>
                                        <tr>
                                            <td><?= (int) ($rigaCapolista['Giornata'] ?? 0) ?></td>
                                            <td><?= htmlspecialchars((string) ($rigaCapolista['Capolista'] ?? '')) ?></td>
                                            <td class="text-center"><?= (int) ($rigaCapolista['Punti'] ?? 0) ?></td>
                                            <td class="text-center">
                                                <?= isset($rigaCapolista['Vantaggio']) ? (int) $rigaCapolista['Vantaggio'] : 0 ?>
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

    <?php require __DIR__ . '/../../partials/script.php'; ?>
</body>
</html>