<?php

/** @var array $universo */
/** @var array $edizione */
/** @var array $squadre */
/** @var array $verificheRose */
/** @var bool $roseComplete */

$edizioneModificabile = ((string) ($edizione['Stato'] ?? 'bozza')) === 'bozza';
?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rose - <?= htmlspecialchars((string) ($edizione['Nome'] ?? 'Edizione')) ?></title>
    <?php require __DIR__ . '/../../partials/link.php'; ?>
</head>

<body>
    <div class="container py-4">
        <div class="mb-4">
            <div class="d-flex flex-column flex-lg-row align-items-lg-start justify-content-between gap-3">
                <div>
                    <a
                        href="/universi/<?= (int) ($universo['ID'] ?? 0) ?>/edizioni/<?= (int) ($edizione['ID'] ?? 0) ?>"
                        class="link-secondary text-decoration-none d-inline-block mb-2">
                        ← Torna all'edizione
                    </a>

                    <h1 class="h2 mb-2">
                        <?= $edizioneModificabile ? 'Associa giocatori → squadre' : 'Rose delle squadre' ?>
                    </h1>

                    <p class="text-muted mb-0">
                        <?php if ($edizioneModificabile): ?>
                            Ogni squadra deve avere una rosa valida di 18 giocatori, con un minimo per ruolo prima della finalizzazione della stagione.
                        <?php else: ?>
                            Consulta la composizione delle rose dell’edizione e il loro stato di completezza.
                        <?php endif; ?>
                    </p>
                </div>

                <?php if ($edizioneModificabile && !$roseComplete): ?>
                    <div class="d-flex flex-shrink-0">
                        <form method="post" action="/universi/<?= (int) ($universo['ID'] ?? 0) ?>/edizioni/<?= (int) ($edizione['ID'] ?? 0) ?>/rose/auto" class="m-0">
                            <button type="submit" class="btn btn-success px-4">
                                Assegna automaticamente tutte le rose
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>

            <div class="mt-4">
                <?php if ($roseComplete): ?>
                    <div class="alert alert-success mb-0" role="alert">
                        <div class="fw-semibold mb-1">Rose complete</div>
                        <div class="small">
                            Tutte le squadre dell’edizione rispettano i vincoli minimi richiesti.
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning mb-0" role="alert">
                        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                            <div>
                                <div class="fw-semibold mb-1">Rose ancora incomplete</div>
                                <div class="small">
                                    Alcune squadre non hanno ancora 18 giocatori o non rispettano la distribuzione minima per ruolo.
                                </div>
                            </div>
                            <?php if ($edizioneModificabile): ?>
                                <span class="badge text-bg-dark px-3 py-2">Azione richiesta</span>
                            <?php else: ?>
                                <span class="badge text-bg-secondary px-3 py-2">Sola lettura</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="row g-3">
            <?php foreach ($squadre as $squadra): ?>
                <?php
                $idSquadra = (int) ($squadra['IDSquadra'] ?? 0);
                $verifica = $verificheRose[$idSquadra] ?? null;
                $ok = (bool) ($verifica['ok'] ?? false);
                ?>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body d-flex flex-column">
                            <h2 class="h5 mb-2"><?= htmlspecialchars((string) ($squadra['Nome'] ?? '')) ?></h2>

                            <?php if ($ok): ?>
                                <div class="badge text-bg-success mb-3">Completa</div>
                            <?php else: ?>
                                <div class="badge text-bg-warning mb-3">Incompleta</div>
                            <?php endif; ?>

                            <div class="small mb-3">
                                <div><strong>Totale:</strong> <?= (int) ($verifica['conteggi']['totale'] ?? 0) ?>/18</div>
                                <div><strong>POR:</strong> <?= (int) ($verifica['conteggi']['POR'] ?? 0) ?>/2</div>
                                <div><strong>Difensivi:</strong> <?= (int) ($verifica['conteggi']['difensivi'] ?? 0) ?>/5</div>
                                <div><strong>Centrocampo:</strong> <?= (int) ($verifica['conteggi']['centrocampo'] ?? 0) ?>/6</div>
                                <div><strong>Offensivi:</strong> <?= (int) ($verifica['conteggi']['offensivi'] ?? 0) ?>/5</div>
                            </div>

                            <div class="mt-auto d-flex flex-wrap gap-2">
                                <?php if ($edizioneModificabile): ?>
                                    <a class="btn btn-sm btn-outline-primary" href="/universi/<?= (int) ($universo['ID'] ?? 0) ?>/edizioni/<?= (int) ($edizione['ID'] ?? 0) ?>/rose/<?= $idSquadra ?>">
                                        Gestisci rosa
                                    </a>

                                    <form method="post" action="/universi/<?= (int) ($universo['ID'] ?? 0) ?>/edizioni/<?= (int) ($edizione['ID'] ?? 0) ?>/rose/<?= $idSquadra ?>/auto" class="d-inline">
                                        <button type="submit" class="btn btn-sm btn-outline-success">Assegna automaticamente</button>
                                    </form>
                                <?php else: ?>
                                    <a class="btn btn-sm btn-outline-secondary" href="/universi/<?= (int) ($universo['ID'] ?? 0) ?>/edizioni/<?= (int) ($edizione['ID'] ?? 0) ?>/rose/<?= $idSquadra ?>/show">
                                        Visualizza rosa
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php require __DIR__ . '/../../partials/script.php'; ?>
</body>

</html>