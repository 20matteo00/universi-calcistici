<?php

declare(strict_types=1);

/** @var array $competizioni */
?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifica collegamento tra competizioni</title>
    <?php require __DIR__ . '/../../partials/link.php'; ?>
</head>

<body>
    <div class="container py-4">
        <div class="mb-4">
            <a href="/universi/<?= (int) ($universo['ID'] ?? 0) ?>/competizioni" class="text-decoration-none">← Torna alle competizioni</a>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <h1 class="h3 mb-1">Modifica collegamento tra competizioni</h1>
                <p class="text-muted mb-4">
                    Universo: <?= htmlspecialchars((string) ($universo['Nome'] ?? '')) ?>
                </p>

                <?php if (!empty($errori)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errori as $errore): ?>
                                <li><?= htmlspecialchars((string) $errore) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post" action="/universi/<?= (int) ($universo['ID'] ?? 0) ?>/competizioni/collegamenti/<?= (int) ($collegamento['ID'] ?? 0) ?>/update" id="collegamentoForm">
                    <div class="row g-4">
                        <div class="col-12">
                            <div class="border rounded p-3">
                                <h2 class="h5 mb-3">Collegamento</h2>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="id_competizione_partenza" class="form-label">Competizione di partenza</label>
                                        <select class="form-select" id="id_competizione_partenza" name="id_competizione_partenza" required>
                                            <option value="">Seleziona</option>
                                            <?php foreach ($competizioni as $competizione): ?>
                                                <option value="<?= (int) ($competizione['ID'] ?? 0) ?>" <?= (int) ($vecchiDati['id_competizione_partenza'] ?? 0) === (int) ($competizione['ID'] ?? 0) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars((string) ($competizione['NomeCompetizione'] ?? '')) ?> (<?= htmlspecialchars((string) ($competizione['Tipo'] ?? '')) ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="id_competizione_arrivo" class="form-label">Competizione di arrivo</label>
                                        <select class="form-select" id="id_competizione_arrivo" name="id_competizione_arrivo" required>
                                            <option value="">Seleziona</option>
                                            <?php foreach ($competizioni as $competizione): ?>
                                                <option value="<?= (int) ($competizione['ID'] ?? 0) ?>" <?= (int) ($vecchiDati['id_competizione_arrivo'] ?? 0) === (int) ($competizione['ID'] ?? 0) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars((string) ($competizione['NomeCompetizione'] ?? '')) ?> (<?= htmlspecialchars((string) ($competizione['Tipo'] ?? '')) ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="ordine" class="form-label">Ordine</label>
                                        <input type="number" min="1" class="form-control" id="ordine" name="ordine" required value="<?= (int) ($vecchiDati['ordine'] ?? 1) ?>">
                                    </div>

                                    <div class="col-md-6">
                                        <label for="criterio" class="form-label">Criterio</label>
                                        <?php $criterio = (string) ($vecchiDati['criterio'] ?? 'posizione'); ?>
                                        <select class="form-select js-dettagli-field" id="criterio">
                                            <option value="posizione" <?= $criterio === 'posizione' ? 'selected' : '' ?>>Posizione</option>
                                            <option value="migliori_n" <?= $criterio === 'migliori_n' ? 'selected' : '' ?>>Migliori N</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 js-criterio-section" data-criterio="posizione">
                            <div class="border rounded p-3">
                                <h2 class="h5 mb-3">Regola posizione</h2>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="posizione_tipo" class="form-label">Tipo</label>
                                        <?php $posizioneTipo = (string) ($vecchiDati['posizione_tipo'] ?? 'promozione'); ?>
                                        <select class="form-select js-dettagli-field" id="posizione_tipo">
                                            <option value="promozione" <?= $posizioneTipo === 'promozione' ? 'selected' : '' ?>>Promozione</option>
                                            <option value="retrocessione" <?= $posizioneTipo === 'retrocessione' ? 'selected' : '' ?>>Retrocessione</option>
                                            <option value="qualificazione" <?= $posizioneTipo === 'qualificazione' ? 'selected' : '' ?>>Qualificazione</option>
                                            <option value="playoff" <?= $posizioneTipo === 'playoff' ? 'selected' : '' ?>>Playoff</option>
                                            <option value="playout" <?= $posizioneTipo === 'playout' ? 'selected' : '' ?>>Playout</option>
                                        </select>
                                    </div>

                                    <div class="col-md-4">
                                        <label for="posizione_da" class="form-label">Posizione da</label>
                                        <input type="number" min="1" class="form-control js-dettagli-field" id="posizione_da" value="<?= htmlspecialchars((string) ($vecchiDati['posizione_da'] ?? '1')) ?>">
                                    </div>

                                    <div class="col-md-4">
                                        <label for="posizione_a" class="form-label">Posizione a</label>
                                        <input type="number" min="1" class="form-control js-dettagli-field" id="posizione_a" value="<?= htmlspecialchars((string) ($vecchiDati['posizione_a'] ?? '1')) ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 js-criterio-section d-none" data-criterio="migliori_n">
                            <div class="border rounded p-3">
                                <h2 class="h5 mb-3">Regola migliori N</h2>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="numero" class="form-label">Numero squadre</label>
                                        <input type="number" min="1" class="form-control js-dettagli-field" id="numero" value="<?= htmlspecialchars((string) ($vecchiDati['numero'] ?? '1')) ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="border rounded p-3">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h2 class="h5 mb-0">Dettagli JSON</h2>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="aggiornaDettagli">Rigenera JSON</button>
                                </div>

                                <textarea class="form-control" id="dettagli" name="dettagli" rows="14"><?= htmlspecialchars((string) ($vecchiDati['dettagli'] ?? '')) ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">Salva modifiche</button>
                        <a href="/universi/<?= (int) ($universo['ID'] ?? 0) ?>/competizioni" class="btn btn-outline-secondary">Annulla</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php require __DIR__ . '/../../partials/script.php'; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('collegamentoForm');
            const criterioField = document.getElementById('criterio');
            const dettagliField = document.getElementById('dettagli');
            const triggerButton = document.getElementById('aggiornaDettagli');
            const sections = document.querySelectorAll('.js-criterio-section');

            function intOrNull(value) {
                if (value === '' || value === null || typeof value === 'undefined') return null;
                const parsed = parseInt(value, 10);
                return Number.isNaN(parsed) ? null : parsed;
            }

            function updateSections() {
                const criterio = criterioField.value;
                sections.forEach(function(section) {
                    section.classList.add('d-none');
                });
                const active = document.querySelector('[data-criterio="' + criterio + '"]');
                if (active) active.classList.remove('d-none');
            }

            function buildDettagli() {
                const criterio = criterioField.value;
                let dettagli = {};

                if (criterio === 'posizione') {
                    dettagli = {
                        criterio: 'posizione',
                        tipo: document.getElementById('posizione_tipo').value,
                        da: intOrNull(document.getElementById('posizione_da').value),
                        a: intOrNull(document.getElementById('posizione_a').value)
                    };
                }

                if (criterio === 'migliori_n') {
                    dettagli = {
                        criterio: 'migliori_n',
                        numero: intOrNull(document.getElementById('numero').value)
                    };
                }

                dettagliField.value = JSON.stringify(dettagli, null, 2);
            }

            ['posizione_tipo', 'posizione_da', 'posizione_a', 'numero'].forEach(function(id) {
                document.getElementById(id)?.addEventListener('input', buildDettagli);
                document.getElementById(id)?.addEventListener('change', buildDettagli);
            });

            criterioField.addEventListener('change', function() {
                updateSections();
                buildDettagli();
            });

            triggerButton.addEventListener('click', buildDettagli);
            form.addEventListener('submit', buildDettagli);

            updateSections();
            if (dettagliField.value.trim() === '') buildDettagli();
        });
    </script>
</body>

</html>