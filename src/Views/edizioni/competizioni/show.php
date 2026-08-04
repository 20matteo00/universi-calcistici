<?php
/** @var array $universo */
/** @var array $edizione */
/** @var array $competizione */
/** @var array $partitePerGiornata */
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
        <a
            href="/universi/<?= (int) $universo['ID'] ?>/edizioni/<?= (int) $edizione['ID'] ?>/competizioni"
            class="link-secondary text-decoration-none d-inline-block mb-3"
        >← Torna alle competizioni</a>

        <div class="comp-head card shadow-sm border-0 mb-4">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-start gap-3">
                    <div>
                        <h1 class="h2 mb-1"><?= htmlspecialchars((string) ($competizione['NomeCompetizione'] ?? 'Competizione')) ?></h1>
                        <p class="text-muted mb-2">
                            Edizione <?= htmlspecialchars((string) ($edizione['Nome'] ?? '')) ?> ·
                            Tipo <?= htmlspecialchars((string) ($competizione['Tipo'] ?? '')) ?>
                        </p>
                        <div class="small text-muted">
                            Giornate: <?= count($partitePerGiornata) ?> ·
                            Partite totali:
                            <?php
                            $totalePartite = 0;
                            foreach ($partitePerGiornata as $partite) {
                                $totalePartite += count($partite);
                            }
                            echo $totalePartite;
                            ?>
                        </div>
                    </div>

                    <div class="comp-toolbar">
                        <form method="post" action="/universi/<?= (int) $universo['ID'] ?>/edizioni/<?= (int) $edizione['ID'] ?>/competizioni/<?= (int) $competizione['ID'] ?>/partite/salva-tutte" class="m-0">
                            <button type="submit" class="btn btn-primary">Salva tutto</button>
                        </form>

                        <form method="post" action="/universi/<?= (int) $universo['ID'] ?>/edizioni/<?= (int) $edizione['ID'] ?>/competizioni/<?= (int) $competizione['ID'] ?>/partite/simula-tutte" class="m-0">
                            <button type="submit" class="btn btn-outline-primary">Simula tutto</button>
                        </form>

                        <form method="post" action="/universi/<?= (int) $universo['ID'] ?>/edizioni/<?= (int) $edizione['ID'] ?>/competizioni/<?= (int) $competizione['ID'] ?>/partite/reset-tutte" class="m-0">
                            <button type="submit" class="btn btn-outline-danger">Elimina tutto</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($partitePerGiornata === []): ?>
            <div class="alert alert-warning mb-4">
                Nessuna partita generata per questa competizione.
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($partitePerGiornata as $giornata => $partite): ?>
                    <div class="col-12 col-xl-6">
                        <div class="card shadow-sm border-0 h-100 giornata-card">
                            <div class="card-header bg-white border-0 pt-4 pb-3">
                                <div class="d-flex flex-column gap-3">
                                    <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
                                        <div>
                                            <h2 class="h5 mb-1">Giornata <?= (int) $giornata ?></h2>
                                            <div class="small text-muted">
                                                <?= count($partite) ?> partite
                                            </div>
                                        </div>

                                        <div class="giornata-toolbar">
                                            <form method="post" action="/universi/<?= (int) $universo['ID'] ?>/edizioni/<?= (int) $edizione['ID'] ?>/competizioni/<?= (int) $competizione['ID'] ?>/giornate/<?= (int) $giornata ?>/salva" class="m-0">
                                                <button type="submit" class="btn btn-sm btn-primary">Salva</button>
                                            </form>

                                            <form method="post" action="/universi/<?= (int) $universo['ID'] ?>/edizioni/<?= (int) $edizione['ID'] ?>/competizioni/<?= (int) $competizione['ID'] ?>/giornate/<?= (int) $giornata ?>/simula" class="m-0">
                                                <button type="submit" class="btn btn-sm btn-outline-primary">Simula</button>
                                            </form>

                                            <form method="post" action="/universi/<?= (int) $universo['ID'] ?>/edizioni/<?= (int) $edizione['ID'] ?>/competizioni/<?= (int) $competizione['ID'] ?>/giornate/<?= (int) $giornata ?>/reset" class="m-0">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Elimina</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0 competizione-table">
                                        <thead>
                                            <tr>
                                                <th>Casa</th>
                                                <th class="text-center risultato-col">Risultato</th>
                                                <th>Trasferta</th>
                                                <th class="stato-col">Stato</th>
                                                <th class="azioni-col">Azioni</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($partite as $partita): ?>
                                                <tr>
                                                    <td class="fw-semibold">
                                                        <?= htmlspecialchars((string) ($partita['NomeSquadraCasa'] ?? '')) ?>
                                                    </td>

                                                    <td class="text-center">
                                                        <?php if ($partita['GoalCasa'] !== null && $partita['GoalTrasferta'] !== null): ?>
                                                            <span class="score-pill">
                                                                <?= (int) $partita['GoalCasa'] ?> - <?= (int) $partita['GoalTrasferta'] ?>
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="text-muted">vs</span>
                                                        <?php endif; ?>
                                                    </td>

                                                    <td class="fw-semibold">
                                                        <?= htmlspecialchars((string) ($partita['NomeSquadraTrasferta'] ?? '')) ?>
                                                    </td>

                                                    <td>
                                                        <span class="badge text-bg-secondary">
                                                            <?= htmlspecialchars((string) ($partita['Stato'] ?? '')) ?>
                                                        </span>
                                                    </td>

                                                    <td>
                                                        <div class="partita-actions">
                                                            <form
                                                                method="post"
                                                                action="/universi/<?= (int) $universo['ID'] ?>/edizioni/<?= (int) $edizione['ID'] ?>/competizioni/<?= (int) $competizione['ID'] ?>/partite/risultato"
                                                                class="partita-score-form"
                                                            >
                                                                <input type="hidden" name="id_partita" value="<?= (int) $partita['ID'] ?>">

                                                                <input
                                                                    type="number"
                                                                    min="0"
                                                                    name="goal_casa"
                                                                    value="<?= $partita['GoalCasa'] !== null ? (int) $partita['GoalCasa'] : '' ?>"
                                                                    class="form-control form-control-sm score-input"
                                                                    placeholder="0"
                                                                >

                                                                <span class="score-sep">-</span>

                                                                <input
                                                                    type="number"
                                                                    min="0"
                                                                    name="goal_trasferta"
                                                                    value="<?= $partita['GoalTrasferta'] !== null ? (int) $partita['GoalTrasferta'] : '' ?>"
                                                                    class="form-control form-control-sm score-input"
                                                                    placeholder="0"
                                                                >

                                                                <button type="submit" class="btn btn-sm btn-primary">Salva</button>
                                                            </form>

                                                            <div class="partita-mini-toolbar">
                                                                <form
                                                                    method="post"
                                                                    action="/universi/<?= (int) $universo['ID'] ?>/edizioni/<?= (int) $edizione['ID'] ?>/competizioni/<?= (int) $competizione['ID'] ?>/partite/<?= (int) $partita['ID'] ?>/simula"
                                                                    class="m-0"
                                                                >
                                                                    <button type="submit" class="btn btn-sm btn-outline-primary">Simula</button>
                                                                </form>

                                                                <form
                                                                    method="post"
                                                                    action="/universi/<?= (int) $universo['ID'] ?>/edizioni/<?= (int) $edizione['ID'] ?>/competizioni/<?= (int) $competizione['ID'] ?>/partite/<?= (int) $partita['ID'] ?>/reset"
                                                                    class="m-0"
                                                                >
                                                                    <button type="submit" class="btn btn-sm btn-outline-danger">Elimina</button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php require __DIR__ . '/../../partials/script.php'; ?>
</body>
</html>