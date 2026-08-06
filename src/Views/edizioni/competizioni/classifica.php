<?php

declare(strict_types=1);

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
$datiClassifica = $datiClassifica ?? [];
$tabellaCapolista = $tabellaCapolista ?? [];
$visteClassifica = $visteClassifica ?? ['generale' => $datiClassifica];

$struttura = json_decode((string) ($competizione['Struttura'] ?? '{}'), true);
if (!is_array($struttura)) {
    $struttura = [];
}

$tabsDisponibili = [
    'generale' => 'Generale',
    'casa' => 'Casa',
    'trasferta' => 'Trasferta',
];

$numeroGiri = max(1, (int) ($competizione['Giri'] ?? 1));
if ($numeroGiri > 1) {
    for ($i = 1; $i <= $numeroGiri; $i++) {
        $tabsDisponibili['giro_' . $i] = 'Giro ' . $i;
    }
}

$tabs = [];
foreach ($tabsDisponibili as $chiave => $label) {
    if (array_key_exists($chiave, $visteClassifica)) {
        $tabs[$chiave] = $label;
    }
}

if (!isset($tabs[$tabAttiva])) {
    $tabAttiva = array_key_first($tabs) ?? 'generale';
}

$righe = $visteClassifica[$tabAttiva] ?? [];

function uc_view_badge_class(string $esito): string
{
    return match ($esito) {
        'V' => 'success',
        'N' => 'warning text-dark',
        'P' => 'danger',
        default => 'secondary',
    };
}

function uc_nome_competizione(array $competizione): string
{
    return (string) (
        $competizione['NomeCompetizione']
        ?? $competizione['Nome']
        ?? $competizione['Titolo']
        ?? 'Competizione'
    );
}
?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Classifica · <?= htmlspecialchars(uc_nome_competizione($competizione)) ?></title>
    <?php require __DIR__ . '/../../partials/link.php'; ?>
</head>

<body>
    <div class="container py-4">
        <div class="uc-page-header mb-4">
            <div>
                <a
                    href="/universi/<?= (int) ($universo['ID'] ?? 0) ?>/edizioni/<?= (int) ($edizione['ID'] ?? 0) ?>/competizioni/<?= (int) ($competizione['ID'] ?? 0) ?>"
                    class="text-decoration-none d-inline-block mb-2">
                    ← Torna alla competizione
                </a>

                <h1 class="h2 mb-1">Classifica</h1>
                <p class="text-muted mb-0">
                    <?= htmlspecialchars(uc_nome_competizione($competizione)) ?>
                    <?php if (!empty($edizione['Nome'])): ?>
                        · <?= htmlspecialchars((string) $edizione['Nome']) ?>
                    <?php endif; ?>
                </p>
            </div>

            <div class="d-flex flex-wrap gap-2 align-items-start">
                <span class="uc-stat-pill">
                    <strong>Intervallo</strong>
                    <span><?= (int) $giornataDa ?> - <?= (int) $giornataA ?></span>
                </span>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form method="get" class="row g-3 align-items-end">
                    <div class="col-12 col-md-3">
                        <label for="giornata_da" class="form-label">Da giornata</label>
                        <select name="giornata_da" id="giornata_da" class="form-select">
                            <?php foreach ($giornate as $giornata): ?>
                                <option value="<?= (int) $giornata ?>" <?= (int) $giornata === $giornataDa ? 'selected' : '' ?>>
                                    <?= (int) $giornata ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12 col-md-3">
                        <label for="giornata_a" class="form-label">A giornata</label>
                        <select name="giornata_a" id="giornata_a" class="form-select">
                            <?php foreach ($giornate as $giornata): ?>
                                <option value="<?= (int) $giornata ?>" <?= (int) $giornata === $giornataA ? 'selected' : '' ?>>
                                    <?= (int) $giornata ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12 col-md-3">
                        <label for="tab" class="form-label">Vista</label>
                        <select name="tab" id="tab" class="form-select">
                            <?php foreach ($tabs as $chiave => $label): ?>
                                <option value="<?= htmlspecialchars($chiave) ?>" <?= $chiave === $tabAttiva ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12 col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100">Applica</button>
                        <a
                            href="/universi/<?= (int) ($universo['ID'] ?? 0) ?>/edizioni/<?= (int) ($edizione['ID'] ?? 0) ?>/competizioni/<?= (int) ($competizione['ID'] ?? 0) ?>/classifica"
                            class="btn btn-outline-secondary w-100">
                            Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <?php if (!empty($tabs)): ?>
            <div class="uc-tabs mb-4">
                <?php foreach ($tabs as $chiave => $label): ?>
                    <a
                        href="?giornata_da=<?= (int) $giornataDa ?>&giornata_a=<?= (int) $giornataA ?>&tab=<?= urlencode($chiave) ?>"
                        class="btn <?= $chiave === $tabAttiva ? 'btn-primary' : 'btn-outline-primary' ?>">
                        <?= htmlspecialchars($label) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="mb-3">
                    <h2 class="h5 uc-card-title">Tabella</h2>
                    <p class="uc-card-subtitle">
                        Vista attiva:
                        <strong><?= htmlspecialchars($tabs[$tabAttiva] ?? 'Generale') ?></strong>
                    </p>
                </div>

                <?php if (empty($righe)): ?>
                    <div class="alert alert-info mb-0">
                        Nessun dato disponibile per i filtri selezionati.
                    </div>
                <?php else: ?>
                    <div class="table-responsive uc-responsive-table">
                        <table class="table table-hover align-middle mb-0">
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
                                <?php foreach ($righe as $indice => $riga): ?>
                                    <?php
                                    $forma = $riga['Forma'] ?? [];
                                    if (is_string($forma)) {
                                        $forma = preg_split('//u', $forma, -1, PREG_SPLIT_NO_EMPTY) ?: [];
                                    }
                                    ?>
                                    <tr>
                                        <td class="fw-semibold"><?= (int) ($riga['Posizione'] ?? ($indice + 1)) ?></td>
                                        <td>
                                            <?php
                                            $nomeSquadra = (string) ($riga['Nome'] ?? '');
                                            $nomeBreveSquadra = (string) (($riga['NomeBreve'] ?? '') ?: $nomeSquadra);
                                            $coloriSquadra = $riga['Colori'] ?? [];
                                            $bgSquadra = (string) ($coloriSquadra['sfondo'] ?? '#6c757d');
                                            $fgSquadra = (string) ($coloriSquadra['testo'] ?? '#ffffff');
                                            $bdSquadra = (string) ($coloriSquadra['bordo'] ?? $bgSquadra);
                                            ?>
                                            <span
                                                class="team-badge team-badge-pill"
                                                title="<?= htmlspecialchars($nomeSquadra) ?>"
                                                style="background-color: <?= htmlspecialchars($bgSquadra) ?>; color: <?= htmlspecialchars($fgSquadra) ?>; border: 2px solid <?= htmlspecialchars($bdSquadra) ?>; min-width: 96px;">
                                                <?= htmlspecialchars($nomeSquadra) ?>
                                            </span>
                                        </td>
                                        <td class="text-center"><?= (int) ($riga['Giocate'] ?? 0) ?></td>
                                        <td class="text-center"><?= (int) ($riga['Vinte'] ?? 0) ?></td>
                                        <td class="text-center"><?= (int) ($riga['Pareggiate'] ?? 0) ?></td>
                                        <td class="text-center"><?= (int) ($riga['Perse'] ?? 0) ?></td>
                                        <td class="text-center"><?= (int) ($riga['Fatti'] ?? 0) ?></td>
                                        <td class="text-center"><?= (int) ($riga['Subiti'] ?? 0) ?></td>
                                        <td class="text-center"><?= (int) ($riga['DifferenzaReti'] ?? 0) ?></td>
                                        <td class="text-center fw-bold"><?= (int) ($riga['Punti'] ?? 0) ?></td>
                                        <td>
                                            <div class="uc-forma">
                                                <?php if (empty($forma)): ?>
                                                    <span class="text-muted">—</span>
                                                <?php else: ?>
                                                    <?php foreach ($forma as $esito): ?>
                                                        <span class="badge bg-<?= uc_view_badge_class((string) $esito) ?>">
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

        <?php
        $segmentiCapolista = [];

        if (!empty($tabellaCapolista)) {
            $corrente = null;

            foreach ($tabellaCapolista as $rigaCapo) {
                $label = (string) (($rigaCapo['NomeBreve'] ?? '') ?: ($rigaCapo['Capolista'] ?? '-'));
                $idSquadra = $rigaCapo['IDSquadra'] ?? null;
                $giornata = (int) ($rigaCapo['Giornata'] ?? 0);
                $pariInTesta = (bool) ($rigaCapo['PariInTesta'] ?? false);
                $colori = $rigaCapo['Colori'] ?? [];

                $chiave = $pariInTesta ? 'pari' : ('squadra_' . (string) $idSquadra);

                if ($corrente === null) {
                    $corrente = [
                        'chiave' => $chiave,
                        'label' => $label,
                        'giornata_inizio' => $giornata,
                        'giornata_fine' => $giornata,
                        'colspan' => 1,
                        'pari' => $pariInTesta,
                        'colori' => $colori,
                    ];
                    continue;
                }

                if ($corrente['chiave'] === $chiave) {
                    $corrente['giornata_fine'] = $giornata;
                    $corrente['colspan']++;
                    continue;
                }

                $segmentiCapolista[] = $corrente;

                $corrente = [
                    'chiave' => $chiave,
                    'label' => $label,
                    'giornata_inizio' => $giornata,
                    'giornata_fine' => $giornata,
                    'colspan' => 1,
                    'pari' => $pariInTesta,
                    'colori' => $colori,
                ];
            }

            if ($corrente !== null) {
                $segmentiCapolista[] = $corrente;
            }
        }
        ?>

        <div class="card border-0 shadow-sm mt-4">
            <div class="card-body">
                <div class="mb-3">
                    <h2 class="h5 uc-card-title">Capolista per giornata</h2>
                    <p class="uc-card-subtitle">
                        Sequenza della vetta: quando la stessa squadra resta prima per più giornate, il blocco viene unito.
                    </p>
                </div>

                <?php if (empty($tabellaCapolista)): ?>
                    <div class="alert alert-light mb-0">
                        Nessuna progressione disponibile.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <?php foreach ($tabellaCapolista as $rigaCapo): ?>
                                        <th class="text-center">
                                            <?= (int) ($rigaCapo['Giornata'] ?? 0) ?>
                                        </th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <?php foreach ($segmentiCapolista as $segmento): ?>
                                        <td class="text-center border" colspan="<?= (int) $segmento['colspan'] ?>">
                                            <?php if ($segmento['pari']): ?>
                                                <span class="fw-semibold text-muted">-</span>
                                            <?php else: ?>
                                                <?php
                                                $bg = (string) ($segmento['colori']['sfondo'] ?? '#6c757d');
                                                $fg = (string) ($segmento['colori']['testo'] ?? '#ffffff');
                                                $bd = (string) ($segmento['colori']['bordo'] ?? $bg);
                                                ?>
                                                <span
                                                    class="team-badge team-badge-pill"
                                                    style="background-color: <?= htmlspecialchars($bg) ?>; color: <?= htmlspecialchars($fg) ?>; border: 2px solid <?= htmlspecialchars($bd) ?>; min-width: 72px;">
                                                    <?= htmlspecialchars((string) $segmento['label']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php require __DIR__ . '/../../partials/script.php'; ?>
</body>

</html>