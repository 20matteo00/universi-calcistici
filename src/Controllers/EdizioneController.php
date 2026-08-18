<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Models\Edizione;
use App\Models\EdizioneCompetizione;
use App\Models\EdizioneGiocatore;
use App\Models\EdizioneSquadra;
use App\Models\CompetizioneCollegamento;
use App\Models\Universo;
use App\Services\Competizioni\CompetizioneClassificaService;
use App\Services\Competizioni\CompetizioneShowService;
use App\Services\Competizioni\CompetizioneStatoService;
use App\Services\Edizioni\EdizioneCreateService;
use App\Services\Edizioni\CompetizioneUpdateService;
use App\Services\Edizioni\EdizioneContextService;
use App\Services\Edizioni\EdizioneFinalizeService;
use App\Services\Edizioni\EdizioneAdvanceService;
use App\Services\Edizioni\RosaAutoAssignService;
use App\Services\Edizioni\RosaValidatorService;
use App\Services\Edizioni\RoseUpdateService;
use App\Services\Competizioni\CompetizioneEliminazioneDirettaService;

class EdizioneController
{
    private Universo $universi;
    private Edizione $edizioni;
    private EdizioneSquadra $edizioneSquadre;
    private EdizioneGiocatore $edizioneGiocatori;
    private EdizioneCompetizione $edizioneCompetizioni;
    private CompetizioneCollegamento $competizioneCollegamenti;
    private EdizioneCreateService $creazioneEdizioneService;
    private EdizioneAdvanceService $edizioneAdvanceService;
    private RosaValidatorService $rosaValidatorService;
    private RosaAutoAssignService $rosaAutoAssignService;
    private RoseUpdateService $roseUpdateService;
    private EdizioneContextService $edizioneContextService;
    private EdizioneFinalizeService $edizioneFinalizeService;
    private CompetizioneUpdateService $competizioneUpdateService;

    public function __construct()
    {
        $this->universi = new Universo();
        $this->edizioni = new Edizione();
        $this->edizioneSquadre = new EdizioneSquadra();
        $this->edizioneGiocatori = new EdizioneGiocatore();
        $this->edizioneCompetizioni = new EdizioneCompetizione();
        $this->competizioneCollegamenti = new CompetizioneCollegamento();

        $this->creazioneEdizioneService = new EdizioneCreateService();
        $this->rosaValidatorService = new RosaValidatorService();
        $this->rosaAutoAssignService = new RosaAutoAssignService();
        $this->roseUpdateService = new RoseUpdateService();

        $this->edizioneContextService = new EdizioneContextService();
        $this->edizioneFinalizeService = new EdizioneFinalizeService();
        $this->edizioneAdvanceService = new EdizioneAdvanceService();
        $this->competizioneUpdateService = new CompetizioneUpdateService();
    }

    public function crea(Request $request, array $parametri): void
    {
        $idUniverso = (int) ($parametri['id'] ?? 0);
        $universo = $this->universi->find($idUniverso);

        if ($universo === null) {
            $this->notFound('Universo non trovato');
            return;
        }

        if ($this->edizioni->haEdizioniPerUniverso($idUniverso)) {
            $this->redirect('/universi/' . $idUniverso . '/edizioni');
        }

        $errori = [];
        $vecchiDati = ['nome' => 'Stagione 1'];

        [
            'squadre' => $squadre,
            'numeroSquadreUniverso' => $numeroSquadreUniverso,
            'verificaRose' => $verificaRose,
            'roseMinimeOk' => $roseMinimeOk,
            'dettaglioRose' => $dettaglioRose,
            'totalePartecipantiCompetizioni' => $totalePartecipantiCompetizioni,
            'coperturaCompetizioniOk' => $coperturaCompetizioniOk,
        ] = $this->buildCreazioneViewData($idUniverso);

        require __DIR__ . '/../Views/edizioni/create.php';
    }

    public function salva(Request $request, array $parametri): void
    {
        $idUniverso = (int) ($parametri['id'] ?? 0);
        $universo = $this->universi->find($idUniverso);

        if ($universo === null) {
            $this->notFound('Universo non trovato');
            return;
        }

        if ($this->edizioni->haEdizioniPerUniverso($idUniverso)) {
            $this->redirect('/universi/' . $idUniverso . '/edizioni');
        }

        $nome = trim((string) ($request->body['nome'] ?? ''));
        $errori = $this->validaNomeEdizione($nome);
        $vecchiDati = ['nome' => $nome];

        [
            'squadre' => $squadre,
            'numeroSquadreUniverso' => $numeroSquadreUniverso,
            'verificaRose' => $verificaRose,
            'roseMinimeOk' => $roseMinimeOk,
            'dettaglioRose' => $dettaglioRose,
            'totalePartecipantiCompetizioni' => $totalePartecipantiCompetizioni,
            'coperturaCompetizioniOk' => $coperturaCompetizioniOk,
        ] = $this->buildCreazioneViewData($idUniverso);

        if (!empty($errori)) {
            require __DIR__ . '/../Views/edizioni/create.php';
            return;
        }

        try {
            $idEdizione = $this->creazioneEdizioneService->creaPrimaEdizione(
                $idUniverso,
                1,
                $nome,
                'bozza',
                $roseMinimeOk
            );
        } catch (\Throwable $e) {
            $errori[] = 'Errore durante la creazione dell\'edizione: ' . $e->getMessage();
            require __DIR__ . '/../Views/edizioni/create.php';
            return;
        }

        $this->redirect('/universi/' . $idUniverso . '/edizioni/' . $idEdizione);
    }

    public function indexByUniverso(Request $request, array $parametri): void
    {
        $idUniverso = (int) ($parametri['id'] ?? 0);
        $universo = $this->universi->find($idUniverso);

        if ($universo === null) {
            $this->notFound('Universo non trovato');
            return;
        }

        $edizioni = $this->edizioni->allByUniverso($idUniverso);

        require __DIR__ . '/../Views/edizioni/index.php';
    }

    public function showByUniverso(Request $request, array $parametri): void
    {
        $idUniverso = (int) ($parametri['id'] ?? 0);
        $idEdizione = (int) ($parametri['idEdizione'] ?? 0);

        $context = $this->edizioneContextService->requireUniversoEdizione($idUniverso, $idEdizione);
        if ($context === null) {
            $this->notFound('Edizione non trovata');
            return;
        }

        $universo = $context['universo'];
        $edizione = $context['edizione'];

        $verificaFinalizzazione = $this->edizioneFinalizeService->verificaFinalizzazione($idEdizione);

        $verificaAvanzamento = $this->edizioneAdvanceService->puoAvanzare($idUniverso, $idEdizione);
        $puoAvanzareStagione = (bool) ($verificaAvanzamento['ok'] ?? false);
        $messaggioAvanzamento = $verificaAvanzamento['messaggio'] ?? null;

        $haGiocatoriEdizione = (bool) ($verificaFinalizzazione['ha_giocatori_edizione'] ?? false);
        $roseComplete = (bool) ($verificaFinalizzazione['rose_complete'] ?? false);
        $puoFinalizzare = (bool) ($verificaFinalizzazione['ok'] ?? false);
        $messaggioFinalizzazione = $verificaFinalizzazione['messaggio'] ?? null;

        $isUltimaEdizione = $this->edizioni->isUltimaEdizione($idUniverso, $idEdizione);

        $verificaAvanzamento = $this->edizioneAdvanceService->puoAvanzare($idUniverso, $idEdizione);
        $puoAvanzareStagione = $isUltimaEdizione && (bool) ($verificaAvanzamento['ok'] ?? false);
        $messaggioAvanzamento = $isUltimaEdizione ? ($verificaAvanzamento['messaggio'] ?? null) : null;

        require __DIR__ . '/../Views/edizioni/show.php';
    }

    public function roseIndex(Request $request, array $parametri): void
    {
        $idUniverso = (int) ($parametri['id'] ?? 0);
        $idEdizione = (int) ($parametri['idEdizione'] ?? 0);

        $context = $this->edizioneContextService->requireUniversoEdizione($idUniverso, $idEdizione);
        if ($context === null) {
            $this->notFound('Edizione non trovata');
            return;
        }

        $universo = $context['universo'];
        $edizione = $context['edizione'];

        if (!$this->edizioneGiocatori->haGiocatoriEdizione($idEdizione)) {
            $this->redirect('/universi/' . $idUniverso . '/edizioni/' . $idEdizione);
        }

        $squadre = $this->edizioneSquadre->squadreEdizione($idEdizione);
        $verificheRose = [];

        foreach ($squadre as $squadra) {
            $verificheRose[(int) $squadra['IDSquadra']] = $this->rosaValidatorService->verificaRosaSquadra(
                $idEdizione,
                (int) $squadra['IDSquadra']
            );
        }

        $roseComplete = $this->rosaValidatorService->tutteLeRoseComplete($idEdizione);

        require __DIR__ . '/../Views/edizioni/rose/index.php';
    }

    public function roseEdit(Request $request, array $parametri): void
    {
        $idUniverso = (int) ($parametri['id'] ?? 0);
        $idEdizione = (int) ($parametri['idEdizione'] ?? 0);
        $idSquadra = (int) ($parametri['idSquadra'] ?? 0);

        $context = $this->edizioneContextService->requireUniversoEdizione($idUniverso, $idEdizione);
        if ($context === null) {
            $this->notFound('Edizione non trovata');
            return;
        }

        $universo = $context['universo'];
        $edizione = $context['edizione'];

        if (!$this->edizioneGiocatori->haGiocatoriEdizione($idEdizione)) {
            $this->redirect('/universi/' . $idUniverso . '/edizioni/' . $idEdizione);
        }

        $squadra = $this->edizioneSquadre->findEdizioneSquadra($idEdizione, $idSquadra);
        if ($squadra === null) {
            $this->notFound('Squadra non trovata nell\'edizione');
            return;
        }

        $errori = [];
        $giocatoriAssegnati = $this->edizioneGiocatori->giocatoriAssegnatiASquadra($idEdizione, $idSquadra);
        $giocatoriDisponibili = $this->edizioneGiocatori->giocatoriDisponibiliPerSquadra($idEdizione, $idSquadra);
        $verificaRosa = $this->rosaValidatorService->verificaRosaSquadra($idEdizione, $idSquadra);

        require __DIR__ . '/../Views/edizioni/rose/edit.php';
    }

    public function roseUpdate(Request $request, array $parametri): void
    {
        $idUniverso = (int) ($parametri['id'] ?? 0);
        $idEdizione = (int) ($parametri['idEdizione'] ?? 0);
        $idSquadra = (int) ($parametri['idSquadra'] ?? 0);

        $context = $this->edizioneContextService->requireUniversoEdizione($idUniverso, $idEdizione);
        if ($context === null) {
            $this->notFound('Edizione non trovata');
            return;
        }

        $universo = $context['universo'];
        $edizione = $context['edizione'];

        $this->bloccaSeEdizioneNonModificabile($edizione);

        if (!$this->edizioneGiocatori->haGiocatoriEdizione($idEdizione)) {
            $this->redirect('/universi/' . $idUniverso . '/edizioni/' . $idEdizione);
        }

        $idsGiocatori = $request->body['ids_giocatori'] ?? [];
        if (!is_array($idsGiocatori)) {
            $idsGiocatori = [];
        }

        $risultato = $this->roseUpdateService->aggiorna($idEdizione, $idSquadra, $idsGiocatori);

        if (!(bool) ($risultato['ok'] ?? false)) {
            if ((int) ($risultato['codice'] ?? 500) === 404) {
                $this->notFound((string) ($risultato['messaggio'] ?? 'Squadra non trovata'));
                return;
            }

            $errori = [(string) ($risultato['messaggio'] ?? 'Errore imprevisto')];
            $squadra = $risultato['squadra'] ?? null;
            $giocatoriAssegnati = $risultato['giocatori_assegnati'] ?? [];
            $giocatoriDisponibili = $risultato['giocatori_disponibili'] ?? [];
            $verificaRosa = $risultato['verifica_rosa'] ?? ['ok' => false, 'conteggi' => []];

            require __DIR__ . '/../Views/edizioni/rose/edit.php';
            return;
        }

        $this->redirect('/universi/' . $idUniverso . '/edizioni/' . $idEdizione . '/rose/' . $idSquadra);
    }

    public function competizioniIndex(Request $request, array $parametri): void
    {
        $idUniverso = (int) ($parametri['id'] ?? 0);
        $idEdizione = (int) ($parametri['idEdizione'] ?? 0);

        $context = $this->edizioneContextService->requireUniversoEdizione($idUniverso, $idEdizione);
        if ($context === null) {
            $this->notFound('Edizione non trovata');
            return;
        }

        $universo = $context['universo'];
        $edizione = $context['edizione'];

        $competizioni = $this->edizioneCompetizioni->competizioniEdizione($idEdizione);
        $conteggi = [];

        foreach ($competizioni as $competizione) {
            $idEdizioneCompetizione = (int) ($competizione['ID'] ?? 0);
            $conteggi[$idEdizioneCompetizione] = count(
                $this->edizioneCompetizioni->squadreIscritteACompetizione($idEdizioneCompetizione)
            );
        }

        $collegamentiUniverso = $this->competizioneCollegamenti->allByUniverso($idUniverso);
        $schemaCollegamenti = $this->buildSchemaCollegamentiCompetizioni($competizioni, $collegamentiUniverso);

        $haGiocatoriEdizione = $this->edizioneGiocatori->haGiocatoriEdizione($idEdizione);
        $roseComplete = $haGiocatoriEdizione
            ? $this->rosaValidatorService->tutteLeRoseComplete($idEdizione)
            : true;

        require __DIR__ . '/../Views/edizioni/competizioni/index.php';
    }

    public function competizioniEdit(Request $request, array $parametri): void
    {
        $idUniverso = (int) ($parametri['id'] ?? 0);
        $idEdizione = (int) ($parametri['idEdizione'] ?? 0);
        $idEdizioneCompetizione = (int) ($parametri['idEdizioneCompetizione'] ?? 0);

        $context = $this->edizioneContextService->requireCompetizione($idUniverso, $idEdizione, $idEdizioneCompetizione);
        if ($context === null) {
            $this->notFound('Competizione stagionale non trovata');
            return;
        }

        $universo = $context['universo'];
        $edizione = $context['edizione'];
        $edizioneCompetizione = $context['competizione'];

        $warningMessaggi = [];
        $errori = [];
        $squadreEdizione = $this->edizioneSquadre->squadreEdizione($idEdizione);
        $squadreIscritte = $this->edizioneCompetizioni->squadreIscritteACompetizione($idEdizioneCompetizione);
        $altreCompetizioniPerSquadra = $this->edizioneCompetizioni->squadreConAltreCompetizioni(
            $idEdizione,
            $idEdizioneCompetizione
        );

        require __DIR__ . '/../Views/edizioni/competizioni/edit.php';
    }

    public function competizioniUpdate(Request $request, array $parametri): void
    {
        $idUniverso = (int) ($parametri['id'] ?? 0);
        $idEdizione = (int) ($parametri['idEdizione'] ?? 0);
        $idEdizioneCompetizione = (int) ($parametri['idEdizioneCompetizione'] ?? 0);

        $context = $this->edizioneContextService->requireCompetizione($idUniverso, $idEdizione, $idEdizioneCompetizione);
        if ($context === null) {
            $this->notFound('Competizione stagionale non trovata');
            return;
        }

        $universo = $context['universo'];
        $edizione = $context['edizione'];
        $edizioneCompetizione = $context['competizione'];

        $this->bloccaSeEdizioneNonModificabile($edizione);
        $this->competizioneModificabileOrRedirect($idEdizioneCompetizione, $idUniverso, $idEdizione);

        $idsSquadre = $request->body['ids_squadre'] ?? [];
        if (!is_array($idsSquadre)) {
            $idsSquadre = [];
        }

        $stato = trim((string) ($request->body['stato'] ?? 'Iscritta'));
        $motivo = trim((string) ($request->body['motivo'] ?? 'Iscrizione manuale'));

        try {
            $risultato = $this->competizioneUpdateService->aggiorna(
                $idEdizione,
                $idEdizioneCompetizione,
                $idsSquadre,
                $stato,
                $motivo
            );
        } catch (\Throwable $e) {
            $risultato = [
                'ok' => false,
                'messaggio' => 'Errore durante il salvataggio delle squadre: ' . $e->getMessage(),
            ];
        }

        if (!(bool) ($risultato['ok'] ?? false)) {
            $warningMessaggi = [];
            $errori = [(string) ($risultato['messaggio'] ?? 'Errore imprevisto')];
            $squadreEdizione = $this->edizioneSquadre->squadreEdizione($idEdizione);
            $squadreIscritte = $this->edizioneCompetizioni->squadreIscritteACompetizione($idEdizioneCompetizione);
            $altreCompetizioniPerSquadra = $this->edizioneCompetizioni->squadreConAltreCompetizioni(
                $idEdizione,
                $idEdizioneCompetizione
            );

            require __DIR__ . '/../Views/edizioni/competizioni/edit.php';
            return;
        }

        $warningDuplicati = $risultato['warning_duplicati'] ?? [];
        $squadreIscritte = $risultato['squadre_iscritte'] ?? [];
        $altreCompetizioniPerSquadra = $risultato['altre_competizioni_per_squadra'] ?? [];
        $squadreEdizione = $this->edizioneSquadre->squadreEdizione($idEdizione);

        if (!empty($warningDuplicati)) {
            $warningMessaggi = $this->buildWarningMessaggiCompetizioni($squadreIscritte, $warningDuplicati);
            $errori = [];

            require __DIR__ . '/../Views/edizioni/competizioni/edit.php';
            return;
        }

        $this->redirect('/universi/' . $idUniverso . '/edizioni/' . $idEdizione . '/competizioni');
    }

    public function roseAutoTutte(Request $request, array $parametri): void
    {
        $idUniverso = (int) ($parametri['id'] ?? 0);
        $idEdizione = (int) ($parametri['idEdizione'] ?? 0);

        $context = $this->edizioneContextService->requireUniversoEdizione($idUniverso, $idEdizione);
        if ($context === null) {
            $this->notFound('Edizione non trovata');
            return;
        }

        $edizione = $context['edizione'];
        $this->bloccaSeEdizioneNonModificabile($edizione);

        try {
            $this->rosaAutoAssignService->autoAssegnaRose($idEdizione, null);
        } catch (\Throwable $e) {
            http_response_code(400);
            echo 'Errore auto-assegnazione: ' . $e->getMessage();
            return;
        }

        $this->redirect('/universi/' . $idUniverso . '/edizioni/' . $idEdizione . '/rose');
    }

    public function roseAutoSquadra(Request $request, array $parametri): void
    {
        $idUniverso = (int) ($parametri['id'] ?? 0);
        $idEdizione = (int) ($parametri['idEdizione'] ?? 0);
        $idSquadra = (int) ($parametri['idSquadra'] ?? 0);

        $context = $this->edizioneContextService->requireUniversoEdizione($idUniverso, $idEdizione);
        if ($context === null) {
            $this->notFound('Edizione non trovata');
            return;
        }

        $edizione = $context['edizione'];
        $this->bloccaSeEdizioneNonModificabile($edizione);

        try {
            $this->rosaAutoAssignService->autoAssegnaRose($idEdizione, $idSquadra);
        } catch (\Throwable $e) {
            http_response_code(400);
            echo 'Errore auto-assegnazione: ' . $e->getMessage();
            return;
        }

        $this->redirect('/universi/' . $idUniverso . '/edizioni/' . $idEdizione . '/rose/' . $idSquadra);
    }

    public function finalizzaEdizione(Request $request, array $parametri): void
    {
        $idUniverso = (int) ($parametri['id'] ?? 0);
        $idEdizione = (int) ($parametri['idEdizione'] ?? 0);

        $context = $this->edizioneContextService->requireUniversoEdizione($idUniverso, $idEdizione);
        if ($context === null) {
            $this->notFound('Edizione non trovata');
            return;
        }

        $edizione = $context['edizione'];

        if ((string) ($edizione['Stato'] ?? 'bozza') !== 'bozza') {
            $this->redirect('/universi/' . $idUniverso . '/edizioni/' . $idEdizione);
        }

        $risultato = $this->edizioneFinalizeService->finalizza($idEdizione);

        if (!(bool) ($risultato['ok'] ?? false)) {
            http_response_code(400);
            echo (string) ($risultato['messaggio'] ?? 'Operazione non consentita');
            return;
        }

        $this->redirect('/universi/' . $idUniverso . '/edizioni/' . $idEdizione);
    }

    public function showCompetizione(Request $request, array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        $idEdizione = (int) ($params['idEdizione'] ?? 0);
        $idEdizioneCompetizione = (int) ($params['idEdizioneCompetizione'] ?? 0);

        $service = new CompetizioneShowService();
        $pagina = $service->build($id, $idEdizione, $idEdizioneCompetizione);

        if ($pagina === null) {
            $this->notFound('Risorsa non trovata');
            return;
        }

        $competizioneStatoService = new CompetizioneStatoService();
        $analisiChiusura = $competizioneStatoService->analizzaChiusura($idEdizioneCompetizione);

        $universo = $pagina['universo'];
        $edizione = $pagina['edizione'];
        $competizione = $pagina['competizione'];
        $tipoCompetizione = $pagina['tipoCompetizione'];
        $blocchiPartite = $pagina['blocchiPartite'];
        $fasiBloccate = $pagina['fasiBloccate'];
        $statoEliminazione = $pagina['statoEliminazione'];

        $statoCompetizione = (string) ($competizione['Stato'] ?? 'in_corso');
        $isConclusa = $statoCompetizione === 'conclusa';

        require __DIR__ . '/../Views/edizioni/competizioni/show.php';
    }

    public function roseShow(Request $request, array $parametri): void
    {
        $idUniverso = (int) ($parametri['id'] ?? 0);
        $idEdizione = (int) ($parametri['idEdizione'] ?? 0);
        $idSquadra = (int) ($parametri['idSquadra'] ?? 0);

        $context = $this->edizioneContextService->requireUniversoEdizione($idUniverso, $idEdizione);
        if ($context === null) {
            $this->notFound('Edizione non trovata');
            return;
        }

        $universo = $context['universo'];
        $edizione = $context['edizione'];

        if (!$this->edizioneGiocatori->haGiocatoriEdizione($idEdizione)) {
            $this->redirect('/universi/' . $idUniverso . '/edizioni/' . $idEdizione);
        }

        $squadra = $this->edizioneSquadre->findEdizioneSquadra($idEdizione, $idSquadra);
        if ($squadra === null) {
            $this->notFound('Squadra non trovata nell\'edizione');
            return;
        }

        $giocatoriAssegnati = $this->edizioneGiocatori->giocatoriAssegnatiASquadra($idEdizione, $idSquadra);
        $verificaRosa = $this->rosaValidatorService->verificaRosaSquadra($idEdizione, $idSquadra);

        require __DIR__ . '/../Views/edizioni/rose/show.php';
    }

    public function classificaCompetizione(Request $request, array $params): void
    {
        $idUniverso = (int) ($params['id'] ?? 0);
        $idEdizione = (int) ($params['idEdizione'] ?? 0);
        $idEdizioneCompetizione = (int) ($params['idEdizioneCompetizione'] ?? 0);

        $service = new CompetizioneClassificaService();
        $pagina = $service->build(
            $idUniverso,
            $idEdizione,
            $idEdizioneCompetizione,
            $request->query
        );

        if ($pagina === null) {
            $this->notFound('Risorsa non trovata');
            return;
        }

        $universo = $pagina['universo'];
        $edizione = $pagina['edizione'];
        $competizione = $pagina['competizione'];
        $nomeCompetizione = $pagina['nomeCompetizione'];
        $giornate = $pagina['giornate'];
        $giornataMin = $pagina['giornataMin'];
        $giornataMax = $pagina['giornataMax'];
        $giornataDa = $pagina['giornataDa'];
        $giornataA = $pagina['giornataA'];
        $sezioneAttiva = $pagina['sezioneAttiva'];
        $tabAttiva = $pagina['tabAttiva'];
        $tabGiocatoriAttiva = $pagina['tabGiocatoriAttiva'];
        $tabsSquadre = $pagina['tabsSquadre'];
        $tabsGiocatori = $pagina['tabsGiocatori'];
        $righeSquadre = $pagina['righeSquadre'];
        $righeGiocatori = $pagina['righeGiocatori'];
        $tabellaCapolista = $pagina['tabellaCapolista'];
        $segmentiCapolista = $pagina['segmentiCapolista'];

        require __DIR__ . '/../Views/edizioni/competizioni/classifica.php';
    }

    public function avanzaEliminazioneDiretta(Request $request, array $params): void
    {
        $idUniverso = (int) ($params['id'] ?? 0);
        $idEdizione = (int) ($params['idEdizione'] ?? 0);
        $idEdizioneCompetizione = (int) ($params['idEdizioneCompetizione'] ?? 0);

        $context = $this->edizioneContextService->requireCompetizione($idUniverso, $idEdizione, $idEdizioneCompetizione);
        if ($context === null) {
            $this->notFound('Risorsa non trovata');
            return;
        }

        $edizione = $context['edizione'];
        $this->bloccaSeEdizioneNonModificabile($edizione);

        $this->competizioneModificabileOrRedirect($idEdizioneCompetizione, $idUniverso, $idEdizione);

        $service = new CompetizioneEliminazioneDirettaService();
        $risultato = $service->avanzaTurno($idEdizioneCompetizione);

        if (!(bool) ($risultato['ok'] ?? false)) {
            $this->redirect('/universi/' . $idUniverso . '/edizioni/' . $idEdizione . '/competizioni/' . $idEdizioneCompetizione . '#warning-eliminazione');
        }

        $this->redirect('/universi/' . $idUniverso . '/edizioni/' . $idEdizione . '/competizioni/' . $idEdizioneCompetizione);
    }

    private function buildCreazioneViewData(int $idUniverso): array
    {
        $squadre = $this->universi->squadre($idUniverso);
        $numeroSquadreUniverso = count($squadre);

        $verificaRose = $this->universi->verificaRoseMinime($idUniverso);
        $roseMinimeOk = (bool) ($verificaRose['ok'] ?? false);
        $dettaglioRose = $verificaRose;

        $totalePartecipantiCompetizioni = $this->universi->totalePartecipantiCompetizioni($idUniverso);
        $coperturaCompetizioniOk = $numeroSquadreUniverso > 0
            && $totalePartecipantiCompetizioni >= $numeroSquadreUniverso;

        return compact(
            'squadre',
            'numeroSquadreUniverso',
            'verificaRose',
            'roseMinimeOk',
            'dettaglioRose',
            'totalePartecipantiCompetizioni',
            'coperturaCompetizioniOk'
        );
    }

    private function validaNomeEdizione(string $nome): array
    {
        $errori = [];

        if ($nome === '') {
            $errori[] = 'Il nome è obbligatorio.';
        } elseif (mb_strlen($nome) > 100) {
            $errori[] = 'Il nome non può superare 100 caratteri.';
        }

        return $errori;
    }

    private function buildWarningMessaggiCompetizioni(array $squadreIscritte, array $warningDuplicati): array
    {
        $warningMessaggi = [];

        foreach ($squadreIscritte as $squadra) {
            $idSquadra = (int) ($squadra['IDSquadra'] ?? 0);

            if (!isset($warningDuplicati[$idSquadra])) {
                continue;
            }

            $nomiCompetizioni = array_map(
                fn(array $voce): string => (string) ($voce['NomeCompetizione'] ?? ''),
                $warningDuplicati[$idSquadra]
            );

            $warningMessaggi[] = (string) ($squadra['Nome'] ?? 'Squadra') . ' è presente anche in: ' . implode(', ', $nomiCompetizioni);
        }

        return $warningMessaggi;
    }

    private function edizioneModificabile(array $edizione): bool
    {
        return (string) ($edizione['Stato'] ?? 'bozza') === 'bozza';
    }

    private function bloccaSeEdizioneNonModificabile(array $edizione): void
    {
        if ($this->edizioneModificabile($edizione)) {
            return;
        }

        http_response_code(403);
        echo 'Questa edizione non è più modificabile.';
        exit;
    }

    private function notFound(string $messaggio): void
    {
        http_response_code(404);
        echo $messaggio;
    }

    private function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }

    private function buildSchemaCollegamentiCompetizioni(array $competizioniEdizione, array $collegamentiUniverso): array
    {
        $mappaCompetizioniEdizione = [];

        foreach ($competizioniEdizione as $competizione) {
            $idCompetizione = (int) ($competizione['IDCompetizione'] ?? 0);

            if ($idCompetizione <= 0) {
                continue;
            }

            $mappaCompetizioniEdizione[$idCompetizione] = [
                'id_edizione_competizione' => (int) ($competizione['ID'] ?? 0),
                'id_competizione' => $idCompetizione,
                'nome' => (string) ($competizione['NomeCompetizione'] ?? ''),
                'tipo' => (string) ($competizione['Tipo'] ?? ''),
                'uscite' => [],
                'entrate' => [],
            ];
        }

        foreach ($collegamentiUniverso as $collegamento) {
            $idPartenza = (int) ($collegamento['IDCompetizionePartenza'] ?? 0);
            $idArrivo = (int) ($collegamento['IDCompetizioneArrivo'] ?? 0);

            $parteNellEdizione = isset($mappaCompetizioniEdizione[$idPartenza]);
            $arrivaNellEdizione = isset($mappaCompetizioniEdizione[$idArrivo]);

            if (!$parteNellEdizione && !$arrivaNellEdizione) {
                continue;
            }

            $descrizione = $this->descriviDettagliCollegamento((string) ($collegamento['Dettagli'] ?? ''));

            $voce = [
                'id' => (int) ($collegamento['ID'] ?? 0),
                'ordine' => (int) ($collegamento['Ordine'] ?? 0),
                'descrizione' => $descrizione,
                'partenza' => [
                    'id' => $idPartenza,
                    'nome' => (string) ($collegamento['CompetizionePartenzaNome'] ?? ''),
                    'tipo' => (string) ($collegamento['CompetizionePartenzaTipo'] ?? ''),
                ],
                'arrivo' => [
                    'id' => $idArrivo,
                    'nome' => (string) ($collegamento['CompetizioneArrivoNome'] ?? ''),
                    'tipo' => (string) ($collegamento['CompetizioneArrivoTipo'] ?? ''),
                ],
            ];

            if ($parteNellEdizione) {
                $mappaCompetizioniEdizione[$idPartenza]['uscite'][] = $voce;
            }

            if ($arrivaNellEdizione) {
                $mappaCompetizioniEdizione[$idArrivo]['entrate'][] = $voce;
            }
        }

        foreach ($mappaCompetizioniEdizione as &$competizione) {
            usort($competizione['uscite'], fn(array $a, array $b): int => ($a['ordine'] <=> $b['ordine']) ?: ($a['arrivo']['nome'] <=> $b['arrivo']['nome']));
            usort($competizione['entrate'], fn(array $a, array $b): int => ($a['ordine'] <=> $b['ordine']) ?: ($a['partenza']['nome'] <=> $b['partenza']['nome']));
        }
        unset($competizione);

        return array_values($mappaCompetizioniEdizione);
    }

    private function descriviDettagliCollegamento(string $json): string
    {
        $json = trim($json);

        if ($json === '') {
            return 'Collegamento';
        }

        $dettagli = json_decode($json, true);

        if (!is_array($dettagli)) {
            return 'Collegamento';
        }

        $criterio = (string) ($dettagli['criterio'] ?? '');

        if ($criterio === 'posizione') {
            $da = (int) ($dettagli['da'] ?? 0);
            $a = (int) ($dettagli['a'] ?? 0);
            $tipo = (string) ($dettagli['tipo'] ?? '');

            $range = $da > 0 && $a > 0
                ? ($da === $a ? (string) $da : ($da . '-' . $a))
                : 'Posizioni';

            $labelTipo = match ($tipo) {
                'promozione' => 'Promozione',
                'retrocessione' => 'Retrocessione',
                'qualificazione' => 'Qualificazione',
                'playoff' => 'Playoff',
                'playout' => 'Playout',
                default => 'Posizione',
            };

            return $labelTipo . ': ' . $range;
        }

        if ($criterio === 'migliori_n') {
            $numero = (int) ($dettagli['numero'] ?? 0);
            return $numero > 0 ? 'Migliori ' . $numero : 'Migliori';
        }

        return 'Collegamento';
    }

    public function chiudiCompetizione(Request $request, array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        $idEdizione = (int) ($params['idEdizione'] ?? 0);
        $idEdizioneCompetizione = (int) ($params['idEdizioneCompetizione'] ?? 0);

        $competizione = $this->edizioneCompetizioni->findEdizioneCompetizione($idEdizioneCompetizione);
        if ($competizione === null) {
            $_SESSION['flash_error'] = 'Competizione non trovata.';
            $this->redirect('/universi/' . $id . '/edizioni/' . $idEdizione . '/competizioni');
        }

        if ((string) ($competizione['Stato'] ?? 'in_corso') === 'conclusa') {
            $_SESSION['flash_success'] = 'La competizione è già conclusa.';
            $this->redirect('/universi/' . $id . '/edizioni/' . $idEdizione . '/competizioni/' . $idEdizioneCompetizione);
        }

        $service = new CompetizioneStatoService();
        $esito = $service->chiudi($idEdizioneCompetizione);

        if (!($esito['chiusa'] ?? false)) {
            $_SESSION['flash_error'] = $esito['motivi'][0] ?? 'Competizione non chiudibile.';
        } else {
            $_SESSION['flash_success'] = 'Competizione chiusa correttamente.';
        }

        $this->redirect('/universi/' . $id . '/edizioni/' . $idEdizione . '/competizioni/' . $idEdizioneCompetizione);
    }

    private function competizioneModificabileOrRedirect(
        int $idEdizioneCompetizione,
        int $idUniverso,
        int $idEdizione
    ): void {
        $competizione = $this->edizioneCompetizioni->findEdizioneCompetizione($idEdizioneCompetizione);

        if ($competizione === null) {
            $_SESSION['flash_error'] = 'Competizione non trovata.';
            $this->redirect('/universi/' . $idUniverso . '/edizioni/' . $idEdizione . '/competizioni');
        }

        if ((string) ($competizione['Stato'] ?? 'in_corso') === 'conclusa') {
            $_SESSION['flash_error'] = 'La competizione è conclusa e non può più essere modificata.';
            $this->redirect('/universi/' . $idUniverso . '/edizioni/' . $idEdizione . '/competizioni/' . $idEdizioneCompetizione);
        }
    }

    public function avanzaEdizione(Request $request, array $parametri): void
    {
        $idUniverso = (int) ($parametri['id'] ?? 0);
        $idEdizione = (int) ($parametri['idEdizione'] ?? 0);

        $context = $this->edizioneContextService->requireUniversoEdizione($idUniverso, $idEdizione);
        if ($context === null) {
            $this->notFound('Edizione non trovata');
            return;
        }

        $risultato = $this->edizioneAdvanceService->avanza($idUniverso, $idEdizione);

        if (!(bool) ($risultato['ok'] ?? false)) {
            $_SESSION['flash_error'] = (string) ($risultato['messaggio'] ?? 'Impossibile avanzare alla stagione successiva.');
            $this->redirect('/universi/' . $idUniverso . '/edizioni/' . $idEdizione);
        }

        $idNuovaEdizione = (int) ($risultato['id_nuova_edizione'] ?? 0);
        $_SESSION['flash_success'] = 'Nuova stagione creata correttamente.';

        $this->redirect('/universi/' . $idUniverso . '/edizioni/' . $idNuovaEdizione);
    }
}
