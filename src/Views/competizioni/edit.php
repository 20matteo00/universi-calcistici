<?php

declare(strict_types=1);

use App\Support\CompetitionTypes;
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifica competizione</title>
    <?php require __DIR__ . '/../partials/link.php'; ?>
</head>
<body>
    <div class="container py-4">
        <div class="mb-4">
            <a href="/universi/<?= (int) ($universo['ID'] ?? 0) ?>/competizioni" class="text-decoration-none">← Torna alle competizioni</a>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <h1 class="h3 mb-1">Modifica competizione</h1>
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

                <form method="post" action="/universi/<?= (int) ($universo['ID'] ?? 0) ?>/competizioni/<?= (int) ($competizione['ID'] ?? 0) ?>/update" id="competizioneForm">
                    <div class="row g-4">
                        <div class="col-12">
                            <div class="border rounded p-3">
                                <h2 class="h5 mb-3">Dati generali</h2>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="nome_competizione" class="form-label">Nome competizione</label>
                                        <input type="text" class="form-control" id="nome_competizione" name="nome_competizione" maxlength="150" required value="<?= htmlspecialchars((string) ($vecchiDati['nome_competizione'] ?? '')) ?>">
                                    </div>

                                    <div class="col-md-3">
                                        <label for="numero_partecipanti" class="form-label">Partecipanti</label>
                                        <input type="number" min="2" class="form-control" id="numero_partecipanti" name="numero_partecipanti" required value="<?= (int) ($vecchiDati['numero_partecipanti'] ?? 20) ?>">
                                    </div>

                                    <div class="col-md-3">
                                        <label for="tipo" class="form-label">Tipo</label>
                                        <?php $tipoSelezionato = (string) ($vecchiDati['tipo'] ?? 'lega'); ?>
                                        <select class="form-select js-struttura-field" id="tipo" name="tipo" required>
                                            <?php foreach (CompetitionTypes::all() as $value => $label): ?>
                                                <option value="<?= htmlspecialchars($value) ?>" <?= $tipoSelezionato === $value ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($label) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 js-section" data-section="gironi">
                            <div class="border rounded p-3">
                                <h2 class="h5 mb-3">Gironi</h2>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="gironi_livello" class="form-label">Livello</label>
                                        <input type="number" min="1" class="form-control js-struttura-field" id="gironi_livello" placeholder="Es. 1" value="<?= htmlspecialchars((string) ($vecchiDati['gironi_livello'] ?? '')) ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="gironi_giri" class="form-label">Giri</label>
                                        <input type="number" min="1" class="form-control js-struttura-field" id="gironi_giri" value="<?= (int) ($vecchiDati['gironi_giri'] ?? 1) ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="gironi_numero" class="form-label">Numero gironi</label>
                                        <input type="number" min="1" class="form-control js-struttura-field" id="gironi_numero" value="<?= (int) ($vecchiDati['gironi_numero'] ?? 4) ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 js-section" data-section="lega">
                            <div class="border rounded p-3">
                                <h2 class="h5 mb-3">Lega</h2>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="lega_livello" class="form-label">Livello</label>
                                        <input type="number" min="1" class="form-control js-struttura-field" id="lega_livello" value="<?= htmlspecialchars((string) ($vecchiDati['lega_livello'] ?? '')) ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="lega_giri" class="form-label">Giri</label>
                                        <input type="number" min="1" class="form-control js-struttura-field" id="lega_giri" value="<?= (int) ($vecchiDati['lega_giri'] ?? 2) ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 js-section" data-section="eliminazione_diretta">
                            <div class="border rounded p-3">
                                <h2 class="h5 mb-3">Eliminazione diretta</h2>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="elim_giri" class="form-label">Giri</label>
                                        <input type="number" min="1" class="form-control js-struttura-field" id="elim_giri" value="<?= (int) ($vecchiDati['elim_giri'] ?? 1) ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="elim_finale_secca" class="form-label">Finale secca</label>
                                        <select class="form-select js-struttura-field" id="elim_finale_secca">
                                            <option value="1" <?= !empty($vecchiDati['elim_finale_secca']) ? 'selected' : '' ?>>Sì</option>
                                            <option value="0" <?= empty($vecchiDati['elim_finale_secca']) ? 'selected' : '' ?>>No</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="elim_finale_terzo_posto" class="form-label">Finale 3° posto</label>
                                        <select class="form-select js-struttura-field" id="elim_finale_terzo_posto">
                                            <option value="0" <?= empty($vecchiDati['elim_finale_terzo_posto']) ? 'selected' : '' ?>>No</option>
                                            <option value="1" <?= !empty($vecchiDati['elim_finale_terzo_posto']) ? 'selected' : '' ?>>Sì</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="border rounded p-3">
                                <h2 class="h5 mb-3">Punti e classifica</h2>

                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="punti_vittoria" class="form-label">Punti vittoria</label>
                                        <input type="number" min="0" class="form-control js-struttura-field" id="punti_vittoria" value="<?= (int) ($vecchiDati['punti_vittoria'] ?? 3) ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="punti_pareggio" class="form-label">Punti pareggio</label>
                                        <input type="number" min="0" class="form-control js-struttura-field" id="punti_pareggio" value="<?= (int) ($vecchiDati['punti_pareggio'] ?? 1) ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="punti_sconfitta" class="form-label">Punti sconfitta</label>
                                        <input type="number" min="0" class="form-control js-struttura-field" id="punti_sconfitta" value="<?= (int) ($vecchiDati['punti_sconfitta'] ?? 0) ?>">
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h3 class="h6 mb-0">Ordine classifica</h3>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" id="aggiungiCriterio">Aggiungi criterio</button>
                                    </div>

                                    <div id="ordinamentoList" class="d-flex flex-column gap-2"></div>

                                    <template id="criterioTemplate">
                                        <div class="border rounded p-2 js-criterio-item">
                                            <div class="row g-2 align-items-center">
                                                <div class="col-md-8">
                                                    <select class="form-select js-ordinamento-select">
                                                        <option value="punti">Punti</option>
                                                        <option value="differenza_reti">Diff. Reti</option>
                                                        <option value="gol_fatti">Gol Fatti</option>
                                                        <option value="gol_subiti">Gol Subiti</option>
                                                        <option value="scontri_diretti">Scontri Diretti</option>
                                                        <option value="nome">Nome</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="d-flex gap-2">
                                                        <button type="button" class="btn btn-outline-secondary btn-sm js-move-up">↑</button>
                                                        <button type="button" class="btn btn-outline-secondary btn-sm js-move-down">↓</button>
                                                        <button type="button" class="btn btn-outline-danger btn-sm js-remove">Rimuovi</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </template>

                                    <div class="form-text mt-2">Ordina i criteri dall'alto verso il basso.</div>
                                    <div class="invalid-feedback d-block d-none" id="ordinamentoErrore">Non puoi usare lo stesso criterio più di una volta.</div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="border rounded p-3">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h2 class="h5 mb-0">Struttura JSON</h2>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="aggiornaJson">Rigenera JSON</button>
                                </div>

                                <textarea class="form-control" id="struttura" name="struttura" rows="24"><?= htmlspecialchars((string) ($vecchiDati['struttura'] ?? '')) ?></textarea>
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

    <?php require __DIR__ . '/../partials/script.php'; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('competizioneForm');
            const strutturaField = document.getElementById('struttura');
            const triggerButton = document.getElementById('aggiornaJson');
            const addButton = document.getElementById('aggiungiCriterio');
            const strutturaInputs = document.querySelectorAll('.js-struttura-field');
            const tipoCompetizione = document.getElementById('tipo');
            const sections = document.querySelectorAll('.js-section');
            const list = document.getElementById('ordinamentoList');
            const template = document.getElementById('criterioTemplate');
            const errore = document.getElementById('ordinamentoErrore');

            const existing = (() => {
                try {
                    const raw = strutturaField.value.trim();
                    return raw ? JSON.parse(raw) : null;
                } catch (e) {
                    return null;
                }
            })();

            function intOrNull(value) {
                if (value === '' || value === null || typeof value === 'undefined') return null;
                const parsed = parseInt(value, 10);
                return Number.isNaN(parsed) ? null : parsed;
            }

            function boolFromSelect(value) {
                return String(value) === '1';
            }

            function updateSections() {
                const tipo = tipoCompetizione.value;
                sections.forEach(function(section) { section.classList.add('d-none'); });
                const active = document.querySelector('[data-section="' + tipo + '"]');
                if (active) active.classList.remove('d-none');
            }

            function bindItemEvents(item) {
                const select = item.querySelector('.js-ordinamento-select');
                const removeButton = item.querySelector('.js-remove');
                const upButton = item.querySelector('.js-move-up');
                const downButton = item.querySelector('.js-move-down');

                select.addEventListener('change', function() {
                    validateOrdinamento();
                    buildStruttura();
                });
                removeButton.addEventListener('click', function() {
                    item.remove();
                    validateOrdinamento();
                    buildStruttura();
                });
                upButton.addEventListener('click', function() {
                    const prev = item.previousElementSibling;
                    if (prev) list.insertBefore(item, prev);
                    buildStruttura();
                });
                downButton.addEventListener('click', function() {
                    const next = item.nextElementSibling;
                    if (next) list.insertBefore(next, item);
                    buildStruttura();
                });
            }

            function addCriterio(value = 'punti') {
                const fragment = template.content.cloneNode(true);
                const item = fragment.querySelector('.js-criterio-item');
                const select = fragment.querySelector('.js-ordinamento-select');
                select.value = value;
                list.appendChild(fragment);
                bindItemEvents(item);
            }

            function getOrdinamento() {
                return Array.from(list.querySelectorAll('.js-ordinamento-select')).map(s => s.value);
            }

            function validateOrdinamento() {
                const values = getOrdinamento();
                const hasDuplicates = new Set(values).size !== values.length;
                list.querySelectorAll('.js-ordinamento-select').forEach(function(select) {
                    select.classList.remove('is-invalid');
                    if (hasDuplicates) select.classList.add('is-invalid');
                });
                errore.classList.toggle('d-none', !hasDuplicates);
                return !hasDuplicates;
            }

            function buildStruttura() {
                const tipo = tipoCompetizione.value;
                let struttura = {};

                if (tipo === 'gironi') {
                    struttura = {
                        livello: intOrNull(document.getElementById('gironi_livello').value),
                        giri: intOrNull(document.getElementById('gironi_giri').value),
                        numero_gironi: intOrNull(document.getElementById('gironi_numero').value),
                        punti: {
                            vittoria: intOrNull(document.getElementById('punti_vittoria').value) ?? 3,
                            pareggio: intOrNull(document.getElementById('punti_pareggio').value) ?? 1,
                            sconfitta: intOrNull(document.getElementById('punti_sconfitta').value) ?? 0
                        },
                        classifica: { ordinamento: getOrdinamento() }
                    };
                }

                if (tipo === 'lega') {
                    struttura = {
                        livello: intOrNull(document.getElementById('lega_livello').value),
                        giri: intOrNull(document.getElementById('lega_giri').value),
                        punti: {
                            vittoria: intOrNull(document.getElementById('punti_vittoria').value) ?? 3,
                            pareggio: intOrNull(document.getElementById('punti_pareggio').value) ?? 1,
                            sconfitta: intOrNull(document.getElementById('punti_sconfitta').value) ?? 0
                        },
                        classifica: { ordinamento: getOrdinamento() }
                    };
                }

                if (tipo === 'eliminazione_diretta') {
                    struttura = {
                        giri: intOrNull(document.getElementById('elim_giri').value),
                        finale_secca: boolFromSelect(document.getElementById('elim_finale_secca').value),
                        finale_terzo_posto: boolFromSelect(document.getElementById('elim_finale_terzo_posto').value)
                    };
                }

                strutturaField.value = JSON.stringify(struttura, null, 2);
            }

            document.getElementById('gironi_livello')?.addEventListener('input', buildStruttura);
            document.getElementById('gironi_giri')?.addEventListener('input', buildStruttura);
            document.getElementById('gironi_numero')?.addEventListener('input', buildStruttura);
            document.getElementById('lega_livello')?.addEventListener('input', buildStruttura);
            document.getElementById('lega_giri')?.addEventListener('input', buildStruttura);
            document.getElementById('elim_giri')?.addEventListener('input', buildStruttura);
            document.getElementById('elim_finale_secca')?.addEventListener('change', buildStruttura);
            document.getElementById('elim_finale_terzo_posto')?.addEventListener('change', buildStruttura);
            document.getElementById('punti_vittoria')?.addEventListener('input', buildStruttura);
            document.getElementById('punti_pareggio')?.addEventListener('input', buildStruttura);
            document.getElementById('punti_sconfitta')?.addEventListener('input', buildStruttura);

            strutturaInputs.forEach(function(input) {
                input.addEventListener('input', buildStruttura);
                input.addEventListener('change', buildStruttura);
            });

            tipoCompetizione.addEventListener('change', function() {
                updateSections();
                buildStruttura();
            });

            addButton.addEventListener('click', function() {
                addCriterio('punti');
                buildStruttura();
            });

            triggerButton.addEventListener('click', function() {
                if (validateOrdinamento()) buildStruttura();
            });

            form.addEventListener('submit', function(event) {
                if (!validateOrdinamento()) {
                    event.preventDefault();
                    return;
                }
                buildStruttura();
            });

            updateSections();
            if (existing && Array.isArray(existing.classifica?.ordinamento) && existing.classifica.ordinamento.length) {
                existing.classifica.ordinamento.forEach(v => addCriterio(v));
            } else {
                addCriterio('punti');
                addCriterio('differenza_reti');
                addCriterio('gol_fatti');
            }
            buildStruttura();
        });
    </script>
</body>
</html>