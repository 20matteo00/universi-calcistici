<?php

/** @var array $universo */
/** @var array $edizioni */

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edizioni - <?= htmlspecialchars((string) ($universo['Nome'] ?? 'Universo')) ?></title>
    <?php require __DIR__ . '/../partials/link.php'; ?>
</head>
<body>
    <div class="container py-4">
        <div class="mx-auto">
            <div class="mb-4">
                <a href="/universi/<?= (int) ($universo['ID'] ?? 0) ?>" class="link-secondary text-decoration-none d-inline-block mb-2">← Torna all'universo</a>
                <h1 class="h2 mb-1">Edizioni</h1>
                <p class="text-muted mb-0">
                    Elenco delle stagioni dell'universo <?= htmlspecialchars((string) ($universo['Nome'] ?? '')) ?>.
                </p>
            </div>

            <?php if (empty($edizioni)): ?>
                <div class="alert alert-info mb-0">
                    Nessuna edizione presente.
                </div>
            <?php else: ?>
                <div class="card shadow-sm border-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Anno</th>
                                    <th>Nome</th>
                                    <th>Stato</th>
                                    <th>Azioni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($edizioni as $edizione): ?>
                                    <tr>
                                        <td><?= (int) ($edizione['ID'] ?? 0) ?></td>
                                        <td><?= (int) ($edizione['Anno'] ?? 0) ?></td>
                                        <td><?= htmlspecialchars((string) ($edizione['Nome'] ?? '')) ?></td>
                                        <td><?= htmlspecialchars((string) ($edizione['Stato'] ?? '')) ?></td>
                                        <td>
                                            <a class="btn btn-sm btn-outline-primary" href="/universi/<?= (int) ($universo['ID'] ?? 0) ?>/edizioni/<?= (int) ($edizione['ID'] ?? 0) ?>">
                                                Apri
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php require __DIR__ . '/../partials/script.php'; ?>
</body>
</html>