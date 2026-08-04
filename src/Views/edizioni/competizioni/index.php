<?php

/** @var array $universo */
/** @var array $edizione */
/** @var array $competizioni */
/** @var array $conteggi */
/** @var bool $haGiocatoriEdizione */
/** @var bool $roseComplete */

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Competizioni - <?= htmlspecialchars((string) ($edizione['Nome'] ?? 'Edizione')) ?></title>
    <?php require __DIR__ . '/../../partials/link.php'; ?>
</head>
<body>
    <div class="container py-4">
        <div class="mb-4">
            <a href="/universi/<?= (int) ($universo['ID'] ?? 0) ?>/edizioni/<?= (int) ($edizione['ID'] ?? 0) ?>" class="link-secondary text-decoration-none d-inline-block mb-2">← Torna all'edizione</a>
            <h1 class="h2 mb-1">Associa squadre → competizioni</h1>
            <p class="text-muted mb-0">
                Gestisci le squadre partecipanti per ogni competizione stagionale.
            </p>
        </div>

        <?php if ($haGiocatoriEdizione && !$roseComplete): ?>
            <div class="alert alert-warning">
                Le rose non sono ancora tutte complete. Puoi comunque preparare le competizioni, ma l'edizione non sarà pronta finché non completi tutte le rose.
            </div>
        <?php endif; ?>

        <div class="row g-3">
            <?php foreach ($competizioni as $competizione): ?>
                <?php
                $idEdizioneCompetizione = (int) ($competizione['ID'] ?? 0);
                $assegnate = (int) ($conteggi[$idEdizioneCompetizione] ?? 0);
                $richieste = (int) ($competizione['NumeroPartecipanti'] ?? 0);
                ?>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body">
                            <h2 class="h5 mb-2"><?= htmlspecialchars((string) ($competizione['NomeCompetizione'] ?? '')) ?></h2>
                            <div class="small text-muted mb-3">
                                <div>Tipo: <?= htmlspecialchars((string) ($competizione['Tipo'] ?? '')) ?></div>
                                <div>Partecipanti previsti: <?= $richieste ?></div>
                                <div>Squadre assegnate: <?= $assegnate ?></div>
                            </div>

                            <?php if ($richieste > 0 && $assegnate === $richieste): ?>
                                <div class="badge text-bg-success mb-3">Completa</div>
                            <?php else: ?>
                                <div class="badge text-bg-warning mb-3">Da completare</div>
                            <?php endif; ?>

                            <div>
                                <a class="btn btn-sm btn-outline-primary" href="/universi/<?= (int) ($universo['ID'] ?? 0) ?>/edizioni/<?= (int) ($edizione['ID'] ?? 0) ?>/competizioni/<?= $idEdizioneCompetizione ?>">
                                    Gestisci squadre
                                </a>
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