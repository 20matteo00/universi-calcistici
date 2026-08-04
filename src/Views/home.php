<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Universi Calcistici</title>
    <?php require __DIR__ . '/partials/link.php'; ?>
</head>

<body>
    <nav class="navbar navbar-expand-lg bg-body-tertiary border-bottom sticky-top">
        <div class="container">
            <a class="navbar-brand fw-semibold" href="/">Universi Calcistici</a>

            <button
                class="navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#mainNavbar"
                aria-controls="mainNavbar"
                aria-expanded="false"
                aria-label="Apri navigazione">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mainNavbar">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="/universi">Universi</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/squadre">Squadre</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/giocatori">Giocatori</a>
                    </li>
                </ul>

                <div class="d-flex flex-column flex-lg-row gap-2">
                    <a class="btn btn-outline-secondary" href="/squadre/crea">Nuova squadra</a>
                    <a class="btn btn-primary" href="/giocatori/crea">Nuovo giocatore</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="py-4 py-lg-5">
        <div class="container">

            <section class="card shadow-sm border-0 mb-4 mb-lg-5">
                <div class="card-body p-4 p-lg-5">
                    <div class="row align-items-center g-4">
                        <div class="col-12 col-lg-8">
                            <span class="badge text-bg-primary mb-3">Setup iniziale</span>
                            <h1 class="display-6 fw-semibold mb-3">Universi Calcistici</h1>
                            <p class="lead text-muted mb-4">
                                Base gestionale per squadre, giocatori, universi, edizioni e competizioni.
                                In questa fase stai costruendo soprattutto le anagrafiche globali, così poi il resto
                                del simulatore potrà appoggiarsi a una base dati pulita e coerente.
                            </p>

                            <div class="d-flex flex-column flex-sm-row gap-2">
                                <a class="btn btn-primary" href="/universi">Vai agli universi</a>
                                <a class="btn btn-outline-secondary" href="/squadre">Gestisci squadre</a>
                                <a class="btn btn-outline-secondary" href="/giocatori">Gestisci giocatori</a>
                            </div>
                        </div>

                        <div class="col-12 col-lg-4">
                            <div class="border rounded-3 p-3 bg-body-tertiary h-100">
                                <h2 class="h5 mb-3">Accesso rapido</h2>
                                <div class="d-grid gap-2">
                                    <a class="btn btn-outline-primary text-start" href="/squadre">Archivio squadre</a>
                                    <a class="btn btn-outline-primary text-start" href="/giocatori">Archivio giocatori</a>
                                    <a class="btn btn-outline-primary text-start" href="/universi">Gestione universi</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="row g-4 mb-4 mb-lg-5">
                <div class="col-12 col-lg-6">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body p-4">
                            <h2 class="h4 mb-3">Moduli attivi</h2>
                            <p class="text-muted mb-3">
                                Questi sono i moduli già presenti o pronti per diventare il cuore operativo del progetto.
                            </p>

                            <div class="list-group list-group-flush">
                                <a href="/squadre" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center px-0">
                                    <span>Squadre</span>
                                    <span class="badge text-bg-success">Attivo</span>
                                </a>
                                <a href="/giocatori" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center px-0">
                                    <span>Giocatori</span>
                                    <span class="badge text-bg-success">Attivo</span>
                                </a>
                                <a href="/universi" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center px-0">
                                    <span>Universi</span>
                                    <span class="badge text-bg-warning">Base</span>
                                </a>
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <span>Competizioni</span>
                                    <span class="badge text-bg-secondary">Dopo</span>
                                </div>
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <span>Edizioni stagionali</span>
                                    <span class="badge text-bg-secondary">Dopo</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-6">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body p-4">
                            <h2 class="h4 mb-3">Stato lavoro</h2>
                            <p class="text-muted mb-3">
                                Situazione attuale della base gestionale e del refactor interfaccia.
                            </p>

                            <ul class="list-group list-group-flush">
                                <li class="list-group-item px-0">Routing PHP server-rendered impostato</li>
                                <li class="list-group-item px-0">CRUD squadre disponibile</li>
                                <li class="list-group-item px-0">CRUD giocatori disponibile</li>
                                <li class="list-group-item px-0">Layout Bootstrap in uniformazione</li>
                                <li class="list-group-item px-0">Pattern comune create/edit/index quasi definito</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </section>

            <section class="row g-4 mb-4 mb-lg-5">
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body p-4">
                            <h2 class="h5 mb-2">Squadre</h2>
                            <p class="text-muted mb-3">Anagrafica globale, filtri, bulk actions e generazione random.</p>
                            <a class="btn btn-outline-primary" href="/squadre">Apri modulo</a>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-xl-3">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body p-4">
                            <h2 class="h5 mb-2">Giocatori</h2>
                            <p class="text-muted mb-3">Archivio globale con posizione, paese, attacco, difesa e filtri.</p>
                            <a class="btn btn-outline-primary" href="/giocatori">Apri modulo</a>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-xl-3">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body p-4">
                            <h2 class="h5 mb-2">Universi</h2>
                            <p class="text-muted mb-3">Contenitore logico per stagioni, competizioni e simulazioni future.</p>
                            <a class="btn btn-outline-primary" href="/universi">Apri modulo</a>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-xl-3">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body p-4">
                            <h2 class="h5 mb-2">Prossimi step</h2>
                            <p class="text-muted mb-3">Edizioni, iscrizioni, match, classifiche e avanzamento stagionale.</p>
                            <button class="btn btn-outline-secondary" type="button" disabled>In arrivo</button>
                        </div>
                    </div>
                </div>
            </section>

            <section class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-3">
                        <div>
                            <h2 class="h4 mb-1">Strumenti sviluppo</h2>
                            <p class="text-muted mb-0">
                                Utility locali per test e manutenzione durante la costruzione del progetto.
                            </p>
                        </div>
                        <span class="badge text-bg-danger">Area sensibile</span>
                    </div>

                    <div class="row g-3 align-items-end">
                        <div class="col-12 col-lg-8">
                            <div class="border rounded-3 p-3 bg-body-tertiary">
                                <h3 class="h6 mb-2">Reset completo database locale</h3>
                                <p class="text-muted mb-0">
                                    Questa operazione cancella tutti i dati di test e riporta il database allo stato iniziale locale.
                                </p>
                            </div>
                        </div>

                        <div class="col-12 col-lg-4">
                            <form action="/dev/reset-database" method="post" onsubmit="return confirm('Questa operazione cancella TUTTI i dati. Continuare?');" class="d-grid">
                                <input type="hidden" name="conferma" value="RESET">
                                <button class="btn btn-danger" type="submit">Reset database</button>
                            </form>
                        </div>
                    </div>
                </div>
            </section>

        </div>
    </main>

    <?php require __DIR__ . '/partials/script.php'; ?>
</body>

</html>