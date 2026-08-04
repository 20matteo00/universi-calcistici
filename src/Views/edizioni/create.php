<?php

/** @var array $universo */
/** @var array $errori */
/** @var array $vecchiDati */
/** @var bool $roseMinimeOk */
/** @var bool $coperturaCompetizioniOk */
/** @var int $numeroSquadreUniverso */
/** @var int $totalePartecipantiCompetizioni */
/** @var array $dettaglioRose */

?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crea 1ª Edizione - <?= htmlspecialchars((string) ($universo['Nome'] ?? 'Universo')) ?></title>
    <?php require __DIR__ . '/../partials/link.php'; ?>
</head>

<body>
    <div class="container py-4">
        <div class="mx-auto">

            <div class="mb-4">
                <a href="/universi/<?= (int) ($universo['ID'] ?? 0) ?>" class="link-secondary text-decoration-none d-inline-block mb-2">← Torna all'universo</a>
                <h1 class="h2 mb-1">Crea 1ª Edizione</h1>
                <p class="text-muted mb-0">
                    Verrà creata la prima stagione dell'universo con anno fisso a 1 e stato iniziale sempre impostato a bozza.
                </p>
            </div>

            <?php if (!empty($errori)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errori as $errore): ?>
                            <li><?= htmlspecialchars((string) $errore) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="alert alert-warning">
                <strong>Attenzione:</strong> dopo la creazione della prima edizione non sarà più possibile modificare squadre, giocatori, competizioni e avanzamenti dell'universo.
            </div>

            <?php if (!$roseMinimeOk): ?>
                <div class="alert alert-danger">
                    I giocatori dell'universo non sono sufficienti per coprire i requisiti minimi. L'edizione verrà comunque creata, ma senza copiare i giocatori stagionali.
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    I giocatori dell'universo sono sufficienti: verranno copiati automaticamente dentro l'edizione.
                </div>
            <?php endif; ?>

            <?php if (!$coperturaCompetizioniOk): ?>
                <div class="alert alert-warning">
                    I partecipanti previsti nelle competizioni non coprono tutte le squadre dell'universo. Alcune squadre potrebbero restare fuori dalle competizioni nella prima edizione.
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    Le competizioni configurate coprono tutte le squadre dell'universo.
                </div>
            <?php endif; ?>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-3">Riepilogo controlli</h2>
                    <div class="small">
                        <div><strong>Anno edizione:</strong> 1</div>
                        <div><strong>Stato iniziale:</strong> bozza</div>
                        <div><strong>Squadre nell'universo:</strong> <?= (int) $numeroSquadreUniverso ?></div>
                        <div><strong>Partecipanti previsti nelle competizioni:</strong> <?= (int) $totalePartecipantiCompetizioni ?></div>
                        <div><strong>Copia giocatori stagionali:</strong> <?= $roseMinimeOk ? 'Sì' : 'No' ?></div>
                    </div>

                    <?php if (!empty($dettaglioRose)): ?>
                        <hr>
                        <div class="small">
                            <div><strong>Giocatori presenti:</strong> <?= (int) ($dettaglioRose['conteggi']['totale'] ?? 0) ?> / <?= (int) ($dettaglioRose['richiesti']['totale'] ?? 0) ?></div>
                            <div><strong>POR:</strong> <?= (int) ($dettaglioRose['conteggi']['POR'] ?? 0) ?> / <?= (int) ($dettaglioRose['richiesti']['POR'] ?? 0) ?></div>
                            <div><strong>Difensivi:</strong> <?= (int) ($dettaglioRose['conteggi']['difensivi'] ?? 0) ?> / <?= (int) ($dettaglioRose['richiesti']['difensivi'] ?? 0) ?></div>
                            <div><strong>Centrocampo:</strong> <?= (int) ($dettaglioRose['conteggi']['centrocampo'] ?? 0) ?> / <?= (int) ($dettaglioRose['richiesti']['centrocampo'] ?? 0) ?></div>
                            <div><strong>Offensivi:</strong> <?= (int) ($dettaglioRose['conteggi']['offensivi'] ?? 0) ?> / <?= (int) ($dettaglioRose['richiesti']['offensivi'] ?? 0) ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <form action="/universi/<?= (int) ($universo['ID'] ?? 0) ?>/edizioni/salva" method="post" onsubmit="return confirm('Sei sicuro di voler creare la prima edizione? Dopo questo passaggio la struttura base dell\\'universo non sarà più modificabile.');">
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="nome" class="form-label">Nome edizione</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="nome"
                                    name="nome"
                                    maxlength="100"
                                    required
                                    value="<?= htmlspecialchars((string) ($vecchiDati['nome'] ?? 'Stagione 1')) ?>">
                            </div>

                            <div class="col-12 d-flex gap-2">
                                <a href="/universi/<?= (int) ($universo['ID'] ?? 0) ?>" class="btn btn-outline-secondary">Annulla</a>
                                <button type="submit" class="btn btn-primary">Crea edizione</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm border-0 mt-4">
                <div class="card-body">
                    <h2 class="h5 mb-3">Cosa succede alla creazione</h2>
                    <ul class="mb-0">
                        <li>Viene creata una riga in <code>Edizioni</code> con <strong>Anno = 1</strong> e <strong>Stato = bozza</strong>.</li>
                        <li>Vengono copiate le squadre dell'universo in <code>EdizioneSquadra</code>.</li>
                        <li>Vengono copiati anche i giocatori in <code>EdizioneGiocatore</code>, solo se i requisiti minimi delle rose sono soddisfatti.</li>
                        <li>Vengono create le righe in <code>EdizioneCompetizione</code> per tutte le competizioni dell'universo.</li>
                        <li>Non vengono ancora create iscrizioni squadra-competizione, rose assegnate alle squadre o partite.</li>
                    </ul>
                </div>
            </div>

        </div>
    </div>

    <?php require __DIR__ . '/../partials/script.php'; ?>
</body>

</html>