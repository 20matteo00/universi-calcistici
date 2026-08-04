<?php

declare(strict_types=1);
/** @var string $strutturaFormattata */

use App\Support\CompetitionTypes;

?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars((string) ($competizione['NomeCompetizione'] ?? 'Competizione')) ?></title>
    <?php require __DIR__ . '/../partials/link.php'; ?>
</head>

<body>
    <div class="container py-4">
        <div class="mb-4">
            <a href="/universi/<?= (int) ($universo['ID'] ?? 0) ?>/competizioni" class="text-decoration-none">← Torna alle competizioni</a>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
                    <div>
                        <h1 class="h3 mb-1"><?= htmlspecialchars((string) ($competizione['NomeCompetizione'] ?? '')) ?></h1>
                        <p class="text-muted mb-0">
                            Universo: <?= htmlspecialchars((string) ($universo['Nome'] ?? '')) ?>
                        </p>
                    </div>

                    <div class="d-flex gap-2">
                        <a href="/universi/<?= (int) ($universo['ID'] ?? 0) ?>/competizioni/<?= (int) ($competizione['ID'] ?? 0) ?>/edit" class="btn btn-outline-secondary">
                            Modifica
                        </a>

                        <form
                            method="post"
                            action="/universi/<?= (int) ($universo['ID'] ?? 0) ?>/competizioni/<?= (int) ($competizione['ID'] ?? 0) ?>/delete"
                            onsubmit="return confirm('Eliminare questa competizione?');">
                            <button type="submit" class="btn btn-outline-danger">Elimina</button>
                        </form>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted small mb-1">ID</div>
                            <div><?= (int) ($competizione['ID'] ?? 0) ?></div>
                        </div>
                    </div>

                    <div class="col">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted small mb-1">Nome</div>
                            <div><?= htmlspecialchars((string) ($competizione['NomeCompetizione'] ?? '')) ?></div>
                        </div>
                    </div>

                    <div class="col">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted small mb-1">Tipo</div>
                            <div><?= htmlspecialchars(CompetitionTypes::label((string) ($competizione['Tipo'] ?? ''))) ?></div>
                        </div>
                    </div>

                    <div class="col">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted small mb-1">Partecipanti</div>
                            <div><?= (int) ($competizione['NumeroPartecipanti'] ?? 0) ?></div>
                        </div>
                    </div>

                    <div class="col">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted small mb-1">Struttura</div>
                            <div>
                                <?php if ((string) ($competizione['Struttura'] ?? '') === ''): ?>
                                    <span class="text-muted">Vuota</span>
                                <?php else: ?>
                                    <span class="badge text-bg-success">JSON presente</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row g-3 mt-2">

                    <div class="col-6">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted small mb-1">Creato</div>
                            <div><?= htmlspecialchars((string) ($competizione['Creato'] ?? '')) ?></div>
                        </div>
                    </div>

                    <div class="col-6">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted small mb-1">Modificato</div>
                            <div><?= htmlspecialchars((string) ($competizione['Modificato'] ?? '')) ?></div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="border rounded p-3">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h2 class="h5 mb-0">Struttura JSON</h2>
                            </div>

                            <?php if ($strutturaFormattata === ''): ?>
                                <p class="text-muted mb-0">Nessuna struttura definita.</p>
                            <?php else: ?>
                                <textarea
                                    class="form-control"
                                    rows="24"
                                    readonly
                                    spellcheck="false"
                                    style="font-family: monospace;"><?= htmlspecialchars($strutturaFormattata) ?></textarea>

                                <div class="form-text mt-2">
                                    Questo JSON contiene solo la struttura interna della competizione.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php require __DIR__ . '/../partials/script.php'; ?>
</body>

</html>