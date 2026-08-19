<?php

declare(strict_types=1);

/** @var array $universo */
/** @var array $edizione */
/** @var array $competizione */
/** @var string $nomeCompetizione */
/** @var array $giornate */
/** @var int $giornataDa */
/** @var int $giornataA */
/** @var string $sezioneAttiva */
/** @var string $tabAttiva */
/** @var string $tabGiocatoriAttiva */
/** @var array $tabsSquadre */
/** @var array $tabsGiocatori */
/** @var array $righeSquadre */
/** @var array $righeGiocatori */
/** @var array $tabellaCapolista */
/** @var array $segmentiCapolista */

$giornate = $giornate ?? [];
$giornataDa = $giornataDa !== null ? (int) $giornataDa : (!empty($giornate) ? (int) min($giornate) : 1);
$giornataA = $giornataA !== null ? (int) $giornataA : (!empty($giornate) ? (int) max($giornate) : $giornataDa);
$sezioneAttiva = in_array((string) $sezioneAttiva, ['squadre', 'giocatori'], true) ? (string) $sezioneAttiva : 'squadre';
$tabAttiva = (string) $tabAttiva;
$tabGiocatoriAttiva = (string) $tabGiocatoriAttiva;
$tabsSquadre = is_array($tabsSquadre) ? $tabsSquadre : [];
$tabsGiocatori = is_array($tabsGiocatori) ? $tabsGiocatori : [];
$righeSquadre = is_array($righeSquadre) ? $righeSquadre : [];
$righeGiocatori = is_array($righeGiocatori) ? $righeGiocatori : [];
$tabellaCapolista = is_array($tabellaCapolista) ? $tabellaCapolista : [];
$segmentiCapolista = is_array($segmentiCapolista) ? $segmentiCapolista : [];

function uc_view_badge_class(string $esito): string
{
    return match ($esito) {
        'V' => 'success',
        'N' => 'warning text-dark',
        'P' => 'danger',
        default => 'secondary',
    };
}

$basePath = '/universi/' . (int) ($universo['ID'] ?? 0)
    . '/edizioni/' . (int) ($edizione['ID'] ?? 0)
    . '/competizioni/' . (int) ($competizione['ID'] ?? 0)
    . '/classifica';

$queryBase = 'giornata_da=' . (int) $giornataDa . '&giornata_a=' . (int) $giornataA;
?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Classifica · <?= htmlspecialchars((string) $nomeCompetizione) ?></title>
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
                    <?= htmlspecialchars((string) $nomeCompetizione) ?>
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
                    <input type="hidden" name="sezione" value="<?= htmlspecialchars($sezioneAttiva) ?>">
                    <input
                        type="hidden"
                        name="<?= $sezioneAttiva === 'giocatori' ? 'tab_giocatori' : 'tab' ?>"
                        value="<?= htmlspecialchars($sezioneAttiva === 'giocatori' ? $tabGiocatoriAttiva : $tabAttiva) ?>">

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

                    <div class="col-12 col-md-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100">Applica</button>
                        <a href="<?= htmlspecialchars($basePath) ?>" class="btn btn-outline-secondary w-100">
                            Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-3">
                    <nav class="btn-group" aria-label="Sezione classifica">
                        <a
                            href="<?= htmlspecialchars($basePath . '?' . $queryBase . '&sezione=squadre&tab=' . urlencode($tabAttiva)) ?>"
                            class="btn <?= $sezioneAttiva === 'squadre' ? 'btn-dark' : 'btn-outline-dark' ?>">
                            Squadre
                        </a>
                        <a
                            href="<?= htmlspecialchars($basePath . '?' . $queryBase . '&sezione=giocatori&tab_giocatori=' . urlencode($tabGiocatoriAttiva)) ?>"
                            class="btn <?= $sezioneAttiva === 'giocatori' ? 'btn-dark' : 'btn-outline-dark' ?>">
                            Giocatori
                        </a>
                    </nav>

                    <div class="d-flex flex-wrap gap-2">
                        <?php if ($sezioneAttiva === 'squadre'): ?>
                            <?php foreach ($tabsSquadre as $chiave => $label): ?>
                                <a
                                    href="<?= htmlspecialchars($basePath . '?' . $queryBase . '&sezione=squadre&tab=' . urlencode($chiave)) ?>"
                                    class="btn <?= $chiave === $tabAttiva ? 'btn-primary' : 'btn-outline-primary' ?>">
                                    <?= htmlspecialchars($label) ?>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <?php foreach ($tabsGiocatori as $chiave => $label): ?>
                                <a
                                    href="<?= htmlspecialchars($basePath . '?' . $queryBase . '&sezione=giocatori&tab_giocatori=' . urlencode($chiave)) ?>"
                                    class="btn <?= $chiave === $tabGiocatoriAttiva ? 'btn-primary' : 'btn-outline-primary' ?>">
                                    <?= htmlspecialchars($label) ?>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($sezioneAttiva === 'squadre'): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <div class="mb-3">
                        <h2 class="h5 uc-card-title mb-1">Tabella squadre</h2>
                        <p class="uc-card-subtitle mb-0">
                            Vista attiva:
                            <strong><?= htmlspecialchars($tabsSquadre[$tabAttiva] ?? 'Generale') ?></strong>
                        </p>
                    </div>

                    <?php if (empty($righeSquadre)): ?>
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
                                    <?php foreach ($righeSquadre as $indice => $riga): ?>
                                        <?php
                                        $forma = $riga['Forma'] ?? [];
                                        if (is_string($forma)) {
                                            $forma = preg_split('//u', $forma, -1, PREG_SPLIT_NO_EMPTY) ?: [];
                                        }

                                        $nomeSquadra = (string) ($riga['Nome'] ?? '');
                                        $coloriSquadra = $riga['Colori'] ?? [];
                                        $bgSquadra = (string) ($coloriSquadra['sfondo'] ?? '#6c757d');
                                        $fgSquadra = (string) ($coloriSquadra['testo'] ?? '#ffffff');
                                        $bdSquadra = (string) ($coloriSquadra['bordo'] ?? $bgSquadra);
                                        ?>
                                        <tr>
                                            <td class="fw-semibold"><?= (int) ($riga['Posizione'] ?? ($indice + 1)) ?></td>
                                            <td>
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
        <?php else: ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <div class="mb-3">
                        <h2 class="h5 uc-card-title mb-1">Statistiche giocatori</h2>
                        <p class="uc-card-subtitle mb-0">
                            Vista attiva:
                            <strong><?= htmlspecialchars($tabsGiocatori[$tabGiocatoriAttiva] ?? 'Marcatori') ?></strong>
                        </p>
                    </div>

                    <?php if (empty($righeGiocatori)): ?>
                        <div class="alert alert-light mb-0">Nessun dato giocatore disponibile.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Giocatore</th>
                                        <th>Squadra</th>
                                        <th class="text-center">Gol</th>
                                        <th class="text-center">Rig.</th>
                                        <th class="text-center">Ass.</th>
                                        <th class="text-center">A.G.</th>
                                        <th class="text-center">Gialli</th>
                                        <th class="text-center">Rossi</th>
                                        <th class="text-center">Rig. sb.</th>
                                        <th class="text-center">Eventi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($righeGiocatori as $riga): ?>
                                        <?php
                                        $nomeSquadra = (string) ($riga['NomeSquadra'] ?? '');
                                        $coloriSquadra = $riga['Colori'] ?? [];
                                        $bgSquadra = (string) ($coloriSquadra['sfondo'] ?? '#6c757d');
                                        $fgSquadra = (string) ($coloriSquadra['testo'] ?? '#ffffff');
                                        $bdSquadra = (string) ($coloriSquadra['bordo'] ?? $bgSquadra);
                                        ?>
                                        <tr>
                                            <td class="fw-semibold"><?= (int) ($riga['Posizione'] ?? 0) ?></td>
                                            <td><?= htmlspecialchars((string) ($riga['NomeGiocatore'] ?? '')) ?></td>
                                            <td>
                                                <span
                                                    class="team-badge team-badge-pill"
                                                    title="<?= htmlspecialchars($nomeSquadra) ?>"
                                                    style="background-color: <?= htmlspecialchars($bgSquadra) ?>; color: <?= htmlspecialchars($fgSquadra) ?>; border: 2px solid <?= htmlspecialchars($bdSquadra) ?>; min-width: 96px;">
                                                    <?= htmlspecialchars($nomeSquadra) ?>
                                                </span>
                                            </td>
                                            <td class="text-center fw-bold"><?= (int) ($riga['Gol'] ?? 0) ?></td>
                                            <td class="text-center"><?= (int) ($riga['GolRigore'] ?? 0) ?></td>
                                            <td class="text-center"><?= (int) ($riga['Assist'] ?? 0) ?></td>
                                            <td class="text-center"><?= (int) ($riga['Autogol'] ?? 0) ?></td>
                                            <td class="text-center"><?= (int) ($riga['Ammonizioni'] ?? 0) ?></td>
                                            <td class="text-center"><?= (int) ($riga['Espulsioni'] ?? 0) ?></td>
                                            <td class="text-center"><?= (int) ($riga['RigoriSbagliati'] ?? 0) ?></td>
                                            <td class="text-center"><?= (int) ($riga['EventiTotali'] ?? 0) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php require __DIR__ . '/../../partials/script.php'; ?>
</body>

</html>