<?php

/** @var array $universo */
/** @var array $edizione */
/** @var bool $haGiocatoriEdizione */
/** @var bool $roseComplete */
/** @var bool $puoFinalizzare */

?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars((string) ($edizione['Nome'] ?? 'Edizione')) ?></title>
    <?php require __DIR__ . '/../partials/link.php'; ?>
</head>

<body>
    <div class="container py-4">
        <div class="mx-auto">
            <div class="mb-4">
                <a href="/universi/<?= (int) ($universo['ID'] ?? 0) ?>/edizioni" class="link-secondary text-decoration-none d-inline-block mb-2">← Torna alle edizioni</a>
                <h1 class="h2 mb-1"><?= htmlspecialchars((string) ($edizione['Nome'] ?? 'Edizione')) ?></h1>
                <p class="text-muted mb-0">
                    Dettaglio della stagione dell'universo <?= htmlspecialchars((string) ($universo['Nome'] ?? '')) ?>.
                </p>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-3">
                            <strong>ID</strong><br>
                            <?= (int) ($edizione['ID'] ?? 0) ?>
                        </div>
                        <div class="col-12 col-md-3">
                            <strong>Anno</strong><br>
                            <?= (int) ($edizione['Anno'] ?? 0) ?>
                        </div>
                        <div class="col-12 col-md-3">
                            <strong>Stato</strong><br>
                            <?= htmlspecialchars((string) ($edizione['Stato'] ?? '')) ?>
                        </div>
                        <div class="col-12 col-md-3">
                            <strong>Creato</strong><br>
                            <?= htmlspecialchars((string) ($edizione['Creato'] ?? '—')) ?>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($haGiocatoriEdizione): ?>
                <?php if ($roseComplete): ?>
                    <div class="alert alert-success">
                        Tutte le rose delle squadre risultano complete.
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        Non tutte le rose delle squadre sono complete. Finché non completi tutte le rose, non potrai considerare pronta l'edizione.
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="alert alert-info">
                    Questa edizione non usa i giocatori stagionali. Il passaggio di associazione giocatori → squadre non è necessario.
                </div>
            <?php endif; ?>

            <div class="card shadow-sm border-0 mt-4">
                <div class="card-body p-4">
                    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
                        <div>
                            <div class="text-uppercase text-muted small fw-semibold mb-1">Operazioni stagione</div>
                            <h2 class="h5 mb-1">Completa la configurazione dell’edizione</h2>
                            <p class="text-muted mb-0">
                                Gestisci rose, iscrivi le squadre alle competizioni e finalizza l’edizione solo quando tutto è completo.
                            </p>
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <?php if ($haGiocatoriEdizione): ?>
                                <?php if ($roseComplete): ?>
                                    <span class="badge text-bg-success px-3 py-2">Rose complete</span>
                                <?php else: ?>
                                    <span class="badge text-bg-warning px-3 py-2">Rose incomplete</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="badge text-bg-secondary px-3 py-2">Universo senza giocatori stagionali</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="row g-3">
                        <?php if ($haGiocatoriEdizione): ?>
                            <div class="col-12 col-xl-6">
                                <a
                                    class="btn btn-outline-primary w-100 text-start d-flex align-items-center justify-content-between px-4 py-3"
                                    href="/universi/<?= (int) ($universo['ID'] ?? 0) ?>/edizioni/<?= (int) ($edizione['ID'] ?? 0) ?>/rose">
                                    <span>
                                        <span class="d-block fw-semibold">Associa giocatori → squadre</span>
                                        <span class="d-block small">Controlla le rose e completa i vincoli per ruolo</span>
                                    </span>
                                    <span class="fs-4 lh-1">→</span>
                                </a>
                            </div>
                        <?php endif; ?>

                        <div class="col-12 col-xl-6">
                            <a
                                class="btn btn-primary w-100 text-start d-flex align-items-center justify-content-between px-4 py-3"
                                href="/universi/<?= (int) ($universo['ID'] ?? 0) ?>/edizioni/<?= (int) ($edizione['ID'] ?? 0) ?>/competizioni">
                                <span>
                                    <span class="d-block fw-semibold">Associa squadre → competizioni</span>
                                    <span class="d-block small">Definisci iscrizioni, struttura e composizione stagionale</span>
                                </span>
                                <span class="fs-4 lh-1">→</span>
                            </a>
                        </div>
                    </div>

                    <?php
                    $statoBozza = (string) ($edizione['Stato'] ?? 'bozza') === 'bozza';
                    ?>

                    <?php if ($statoBozza && $puoFinalizzare): ?>
                        <div class="border-top pt-4 mt-4 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                            <div>
                                <div class="fw-semibold text-danger mb-1">Avvia stagione e blocca configurazione</div>
                                <p class="text-muted small mb-0">
                                    Dopo la finalizzazione l’edizione passa allo stato in_corso e la configurazione iniziale non potrà più essere modificata.
                                </p>
                            </div>

                            <form method="post" action="/universi/<?= (int) ($universo['ID'] ?? 0) ?>/edizioni/<?= (int) ($edizione['ID'] ?? 0) ?>/finalizza" class="m-0">
                                <button type="submit" class="btn btn-danger px-4">
                                    Finalizza edizione e blocca modifiche
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php require __DIR__ . '/../partials/script.php'; ?>
</body>

</html>