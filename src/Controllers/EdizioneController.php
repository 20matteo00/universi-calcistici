<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Http\Request;
use App\Models\Edizione;
use App\Models\Universo;
use App\Models\Partita;
use App\Services\CreazioneEdizioneService;
use App\Services\CalendarioService;
use App\Services\ClassificaService;
use App\Services\SimulazioneService;

class EdizioneController
{
    private Universo $universi;
    private Edizione $edizioni;
    private CreazioneEdizioneService $creazioneEdizioneService;
    private Partita $partite;
    private CalendarioService $calendarioService;

    public function __construct()
    {
        $this->universi = new Universo();
        $this->edizioni = new Edizione();
        $this->partite = new Partita();
        $this->creazioneEdizioneService = new CreazioneEdizioneService();
        $this->calendarioService = new CalendarioService();
    }

    public function crea(Request $request, array $parametri): void
    {
        $idUniverso = (int) ($parametri['id'] ?? 0);
        $universo = $this->universi->find($idUniverso);

        if ($universo === null) {
            http_response_code(404);
            echo 'Universo non trovato';
            return;
        }

        if ($this->edizioni->haEdizioniPerUniverso($idUniverso)) {
            header('Location: /universi/' . $idUniverso . '/edizioni');
            exit;
        }

        $errori = [];
        $vecchiDati = [
            'nome' => 'Stagione 1',
        ];

        $squadre = $this->universi->squadre($idUniverso);
        $numeroSquadreUniverso = count($squadre);

        $verificaRose = $this->universi->verificaRoseMinime($idUniverso);
        $roseMinimeOk = (bool) ($verificaRose['ok'] ?? false);
        $dettaglioRose = $verificaRose;

        $totalePartecipantiCompetizioni = $this->universi->totalePartecipantiCompetizioni($idUniverso);
        $coperturaCompetizioniOk = $totalePartecipantiCompetizioni >= $numeroSquadreUniverso;

        require __DIR__ . '/../Views/edizioni/create.php';
    }

    public function salva(Request $request, array $parametri): void
    {
        $idUniverso = (int) ($parametri['id'] ?? 0);
        $universo = $this->universi->find($idUniverso);

        if ($universo === null) {
            http_response_code(404);
            echo 'Universo non trovato';
            return;
        }

        if ($this->edizioni->haEdizioniPerUniverso($idUniverso)) {
            header('Location: /universi/' . $idUniverso . '/edizioni');
            exit;
        }

        $nome = trim((string) ($request->body['nome'] ?? ''));

        $errori = [];

        if ($nome === '') {
            $errori[] = 'Il nome è obbligatorio.';
        } elseif (mb_strlen($nome) > 100) {
            $errori[] = 'Il nome non può superare 100 caratteri.';
        }

        $vecchiDati = [
            'nome' => $nome,
        ];

        $squadre = $this->universi->squadre($idUniverso);
        $numeroSquadreUniverso = count($squadre);

        $verificaRose = $this->universi->verificaRoseMinime($idUniverso);
        $roseMinimeOk = (bool) ($verificaRose['ok'] ?? false);
        $dettaglioRose = $verificaRose;

        $totalePartecipantiCompetizioni = $this->universi->totalePartecipantiCompetizioni($idUniverso);
        $coperturaCompetizioniOk = $totalePartecipantiCompetizioni >= $numeroSquadreUniverso;

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

        header('Location: /universi/' . $idUniverso . '/edizioni/' . $idEdizione);
        exit;
    }

    public function indexByUniverso(Request $request, array $parametri): void
    {
        $idUniverso = (int) ($parametri['id'] ?? 0);
        $universo = $this->universi->find($idUniverso);

        if ($universo === null) {
            http_response_code(404);
            echo 'Universo non trovato';
            return;
        }

        $edizioni = $this->edizioni->allByUniverso($idUniverso);

        require __DIR__ . '/../Views/edizioni/index.php';
    }

    public function showByUniverso(Request $request, array $parametri): void
    {
        $idUniverso = (int) ($parametri['id'] ?? 0);
        $idEdizione = (int) ($parametri['idEdizione'] ?? 0);

        $universo = $this->universi->find($idUniverso);

        if ($universo === null) {
            http_response_code(404);
            echo 'Universo non trovato';
            return;
        }

        $edizione = $this->edizioni->find($idEdizione);

        if ($edizione === null || (int) ($edizione['IDUniverso'] ?? 0) !== $idUniverso) {
            http_response_code(404);
            echo 'Edizione non trovata';
            return;
        }

        $haGiocatoriEdizione = $this->edizioni->haGiocatoriEdizione($idEdizione);
        $roseComplete = $haGiocatoriEdizione ? $this->edizioni->tutteLeRoseComplete($idEdizione) : true;

        $squadreEdizione = $this->edizioni->squadreEdizione($idEdizione);
        $competizioni = $this->edizioni->competizioniEdizione($idEdizione);

        $squadreCoperte = [];
        foreach ($competizioni as $competizione) {
            $inizialmenteVuota = !empty($competizione['InizialmenteVuota']);

            if ($inizialmenteVuota) {
                continue;
            }

            $idEdizioneCompetizione = (int) ($competizione['ID'] ?? 0);
            $squadreIscritte = $this->edizioni->squadreIscritteACompetizione($idEdizioneCompetizione);

            foreach ($squadreIscritte as $squadraIscritta) {
                $idSquadra = (int) ($squadraIscritta['IDSquadra'] ?? 0);
                if ($idSquadra > 0) {
                    $squadreCoperte[$idSquadra] = true;
                }
            }
        }

        $competizioniComplete = true;
        foreach ($squadreEdizione as $squadraEdizione) {
            $idSquadra = (int) ($squadraEdizione['IDSquadra'] ?? 0);
            if ($idSquadra > 0 && !isset($squadreCoperte[$idSquadra])) {
                $competizioniComplete = false;
                break;
            }
        }

        $puoFinalizzare = $roseComplete && $competizioniComplete && $this->calcolaCoperturaCompetizioniFinalizzabili($idEdizione);

        require __DIR__ . '/../Views/edizioni/show.php';
    }

    public function roseIndex(Request $request, array $parametri): void
    {
        $idUniverso = (int) ($parametri['id'] ?? 0);
        $idEdizione = (int) ($parametri['idEdizione'] ?? 0);

        $universo = $this->universi->find($idUniverso);
        $edizione = $this->edizioni->find($idEdizione);

        if ($universo === null) {
            http_response_code(404);
            echo 'Universo non trovato';
            return;
        }

        if ($edizione === null || (int) ($edizione['IDUniverso'] ?? 0) !== $idUniverso) {
            http_response_code(404);
            echo 'Edizione non trovata';
            return;
        }

        if (!$this->edizioni->haGiocatoriEdizione($idEdizione)) {
            header('Location: /universi/' . $idUniverso . '/edizioni/' . $idEdizione);
            exit;
        }

        $squadre = $this->edizioni->squadreEdizione($idEdizione);
        $verificheRose = [];

        foreach ($squadre as $squadra) {
            $verificheRose[(int) $squadra['IDSquadra']] = $this->edizioni->verificaRosaSquadra(
                $idEdizione,
                (int) $squadra['IDSquadra']
            );
        }

        $roseComplete = $this->edizioni->tutteLeRoseComplete($idEdizione);

        require __DIR__ . '/../Views/edizioni/rose/index.php';
    }

    public function roseEdit(Request $request, array $parametri): void
    {
        $idUniverso = (int) ($parametri['id'] ?? 0);
        $idEdizione = (int) ($parametri['idEdizione'] ?? 0);
        $idSquadra = (int) ($parametri['idSquadra'] ?? 0);

        $universo = $this->universi->find($idUniverso);
        $edizione = $this->edizioni->find($idEdizione);

        if ($universo === null) {
            http_response_code(404);
            echo 'Universo non trovato';
            return;
        }

        if ($edizione === null || (int) ($edizione['IDUniverso'] ?? 0) !== $idUniverso) {
            http_response_code(404);
            echo 'Edizione non trovata';
            return;
        }

        if (!$this->edizioni->haGiocatoriEdizione($idEdizione)) {
            header('Location: /universi/' . $idUniverso . '/edizioni/' . $idEdizione);
            exit;
        }

        $squadre = $this->edizioni->squadreEdizione($idEdizione);
        $squadra = null;

        foreach ($squadre as $riga) {
            if ((int) $riga['IDSquadra'] === $idSquadra) {
                $squadra = $riga;
                break;
            }
        }

        if ($squadra === null) {
            http_response_code(404);
            echo 'Squadra non trovata nell\'edizione';
            return;
        }

        $errori = [];
        $giocatoriAssegnati = $this->edizioni->giocatoriAssegnatiASquadra($idEdizione, $idSquadra);
        $giocatoriDisponibili = $this->edizioni->giocatoriDisponibiliPerSquadra($idEdizione, $idSquadra);
        $verificaRosa = $this->edizioni->verificaRosaSquadra($idEdizione, $idSquadra);

        require __DIR__ . '/../Views/edizioni/rose/edit.php';
    }

    public function roseUpdate(Request $request, array $parametri): void
    {
        $idUniverso = (int) ($parametri['id'] ?? 0);
        $idEdizione = (int) ($parametri['idEdizione'] ?? 0);
        $idSquadra = (int) ($parametri['idSquadra'] ?? 0);

        $universo = $this->universi->find($idUniverso);
        $edizione = $this->edizioni->find($idEdizione);

        if ($universo === null) {
            http_response_code(404);
            echo 'Universo non trovato';
            return;
        }

        if ($edizione === null || (int) ($edizione['IDUniverso'] ?? 0) !== $idUniverso) {
            http_response_code(404);
            echo 'Edizione non trovata';
            return;
        }

        $this->bloccaSeEdizioneNonModificabile($idUniverso, $idEdizione, $edizione);

        if (!$this->edizioni->haGiocatoriEdizione($idEdizione)) {
            header('Location: /universi/' . $idUniverso . '/edizioni/' . $idEdizione);
            exit;
        }

        $squadre = $this->edizioni->squadreEdizione($idEdizione);
        $squadra = null;

        foreach ($squadre as $riga) {
            if ((int) $riga['IDSquadra'] === $idSquadra) {
                $squadra = $riga;
                break;
            }
        }

        if ($squadra === null) {
            http_response_code(404);
            echo 'Squadra non trovata nell\'edizione';
            return;
        }

        $idsGiocatori = $request->body['ids_giocatori'] ?? [];
        if (!is_array($idsGiocatori)) {
            $idsGiocatori = [];
        }

        $errori = [];

        $idsGiocatori = array_values(array_unique(array_filter(array_map('intval', $idsGiocatori), fn(int $id) => $id > 0)));

        $giocatoriDisponibili = $this->edizioni->giocatoriDisponibiliPerSquadra($idEdizione, $idSquadra);
        $giocatoriAssegnatiCorrenti = $this->edizioni->giocatoriAssegnatiASquadra($idEdizione, $idSquadra);

        $mappaConsentiti = [];

        foreach ($giocatoriDisponibili as $giocatore) {
            $mappaConsentiti[(int) $giocatore['IDGiocatore']] = $giocatore;
        }

        foreach ($giocatoriAssegnatiCorrenti as $giocatore) {
            $mappaConsentiti[(int) $giocatore['IDGiocatore']] = $giocatore;
        }

        foreach ($idsGiocatori as $idGiocatore) {
            if (!isset($mappaConsentiti[$idGiocatore])) {
                $errori[] = 'Uno o più giocatori selezionati non sono validi per questa squadra.';
                break;
            }
        }

        if (!empty($errori)) {
            $giocatoriAssegnati = $this->edizioni->giocatoriAssegnatiASquadra($idEdizione, $idSquadra);
            $giocatoriDisponibili = $this->edizioni->giocatoriDisponibiliPerSquadra($idEdizione, $idSquadra);
            $verificaRosa = $this->edizioni->verificaRosaSquadra($idEdizione, $idSquadra);

            require __DIR__ . '/../Views/edizioni/rose/edit.php';
            return;
        }

        try {
            $this->edizioni->salvaRosaSquadra($idEdizione, $idSquadra, $idsGiocatori);
        } catch (\Throwable $e) {
            $errori[] = 'Errore durante il salvataggio della rosa: ' . $e->getMessage();
            $giocatoriAssegnati = $this->edizioni->giocatoriAssegnatiASquadra($idEdizione, $idSquadra);
            $giocatoriDisponibili = $this->edizioni->giocatoriDisponibiliPerSquadra($idEdizione, $idSquadra);
            $verificaRosa = $this->edizioni->verificaRosaSquadra($idEdizione, $idSquadra);

            require __DIR__ . '/../Views/edizioni/rose/edit.php';
            return;
        }

        header('Location: /universi/' . $idUniverso . '/edizioni/' . $idEdizione . '/rose/' . $idSquadra);
        exit;
    }

    public function competizioniIndex(Request $request, array $parametri): void
    {
        $idUniverso = (int) ($parametri['id'] ?? 0);
        $idEdizione = (int) ($parametri['idEdizione'] ?? 0);

        $universo = $this->universi->find($idUniverso);
        $edizione = $this->edizioni->find($idEdizione);

        if ($universo === null) {
            http_response_code(404);
            echo 'Universo non trovato';
            return;
        }

        if ($edizione === null || (int) ($edizione['IDUniverso'] ?? 0) !== $idUniverso) {
            http_response_code(404);
            echo 'Edizione non trovata';
            return;
        }

        $competizioni = $this->edizioni->competizioniEdizione($idEdizione);
        $conteggi = [];

        foreach ($competizioni as $competizione) {
            $idEdizioneCompetizione = (int) ($competizione['ID'] ?? 0);
            $conteggi[$idEdizioneCompetizione] = count($this->edizioni->squadreIscritteACompetizione($idEdizioneCompetizione));
        }

        $haGiocatoriEdizione = $this->edizioni->haGiocatoriEdizione($idEdizione);
        $roseComplete = $haGiocatoriEdizione ? $this->edizioni->tutteLeRoseComplete($idEdizione) : true;

        require __DIR__ . '/../Views/edizioni/competizioni/index.php';
    }

    public function competizioniEdit(Request $request, array $parametri): void
    {
        $idUniverso = (int) ($parametri['id'] ?? 0);
        $idEdizione = (int) ($parametri['idEdizione'] ?? 0);
        $idEdizioneCompetizione = (int) ($parametri['idEdizioneCompetizione'] ?? 0);
        $warningMessaggi = [];

        $universo = $this->universi->find($idUniverso);
        $edizione = $this->edizioni->find($idEdizione);

        if ($universo === null) {
            http_response_code(404);
            echo 'Universo non trovato';
            return;
        }

        if ($edizione === null || (int) ($edizione['IDUniverso'] ?? 0) !== $idUniverso) {
            http_response_code(404);
            echo 'Edizione non trovata';
            return;
        }

        $edizioneCompetizione = $this->edizioni->findEdizioneCompetizione($idEdizioneCompetizione);

        if ($edizioneCompetizione === null || (int) ($edizioneCompetizione['IDEdizione'] ?? 0) !== $idEdizione) {
            http_response_code(404);
            echo 'Competizione stagionale non trovata';
            return;
        }

        $errori = [];
        $squadreEdizione = $this->edizioni->squadreEdizione($idEdizione);
        $squadreIscritte = $this->edizioni->squadreIscritteACompetizione($idEdizioneCompetizione);

        $altreCompetizioniPerSquadra = $this->edizioni->squadreConAltreCompetizioni($idEdizione, $idEdizioneCompetizione);

        require __DIR__ . '/../Views/edizioni/competizioni/edit.php';
    }

    public function competizioniUpdate(Request $request, array $parametri): void
    {
        $idUniverso = (int) ($parametri['id'] ?? 0);
        $idEdizione = (int) ($parametri['idEdizione'] ?? 0);
        $idEdizioneCompetizione = (int) ($parametri['idEdizioneCompetizione'] ?? 0);

        $universo = $this->universi->find($idUniverso);
        $edizione = $this->edizioni->find($idEdizione);

        if ($universo === null) {
            http_response_code(404);
            echo 'Universo non trovato';
            return;
        }

        if ($edizione === null || (int) ($edizione['IDUniverso'] ?? 0) !== $idUniverso) {
            http_response_code(404);
            echo 'Edizione non trovata';
            return;
        }

        $this->bloccaSeEdizioneNonModificabile($idUniverso, $idEdizione, $edizione);

        $edizioneCompetizione = $this->edizioni->findEdizioneCompetizione($idEdizioneCompetizione);

        if ($edizioneCompetizione === null || (int) ($edizioneCompetizione['IDEdizione'] ?? 0) !== $idEdizione) {
            http_response_code(404);
            echo 'Competizione stagionale non trovata';
            return;
        }

        $idsSquadre = $request->body['ids_squadre'] ?? [];
        if (!is_array($idsSquadre)) {
            $idsSquadre = [];
        }

        $idsSquadre = array_values(array_unique(array_filter(array_map('intval', $idsSquadre), fn(int $id) => $id > 0)));

        $stato = trim((string) ($request->body['stato'] ?? 'Iscritta'));
        $motivo = trim((string) ($request->body['motivo'] ?? 'Iscrizione manuale'));

        $errori = [];

        if (!in_array($stato, ['Iscritta', 'Qualificata', 'Candidata', 'Eliminata', 'Promossa', 'Retrocessa'], true)) {
            $errori[] = 'Lo stato selezionato non è valido.';
        }

        $squadreEdizione = $this->edizioni->squadreEdizione($idEdizione);
        $mappaSquadre = [];

        foreach ($squadreEdizione as $squadra) {
            $mappaSquadre[(int) $squadra['IDSquadra']] = true;
        }

        foreach ($idsSquadre as $idSquadra) {
            if (!isset($mappaSquadre[$idSquadra])) {
                $errori[] = 'Una o più squadre selezionate non appartengono all\'edizione.';
                break;
            }
        }

        $numeroPartecipanti = (int) ($edizioneCompetizione['NumeroPartecipanti'] ?? 0);
        if ($numeroPartecipanti > 0 && count($idsSquadre) > $numeroPartecipanti) {
            $errori[] = 'Hai selezionato più squadre del numero partecipanti previsto.';
        }

        if (!empty($errori)) {
            $squadreIscritte = $this->edizioni->squadreIscritteACompetizione($idEdizioneCompetizione);
            require __DIR__ . '/../Views/edizioni/competizioni/edit.php';
            return;
        }

        $warningDuplicati = $this->edizioni->riepilogoDuplicatiCompetizione(
            $idEdizione,
            $idEdizioneCompetizione,
            $idsSquadre
        );

        try {
            $this->edizioni->salvaSquadreCompetizione(
                $idEdizioneCompetizione,
                $idsSquadre,
                $stato,
                $motivo
            );
        } catch (\Throwable $e) {
            $errori[] = 'Errore durante il salvataggio delle squadre: ' . $e->getMessage();
            $squadreIscritte = $this->edizioni->squadreIscritteACompetizione($idEdizioneCompetizione);
            $altreCompetizioniPerSquadra = $this->edizioni->squadreConAltreCompetizioni($idEdizione, $idEdizioneCompetizione);
            require __DIR__ . '/../Views/edizioni/competizioni/edit.php';
            return;
        }

        $squadreIscritte = $this->edizioni->squadreIscritteACompetizione($idEdizioneCompetizione);
        $altreCompetizioniPerSquadra = $this->edizioni->squadreConAltreCompetizioni($idEdizione, $idEdizioneCompetizione);

        if (!empty($warningDuplicati)) {
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

            require __DIR__ . '/../Views/edizioni/competizioni/edit.php';
            return;
        }

        header('Location: /universi/' . $idUniverso . '/edizioni/' . $idEdizione . '/competizioni');
        exit;
    }

    public function roseAutoTutte(Request $request, array $parametri): void
    {
        $idUniverso = (int) ($parametri['id'] ?? 0);
        $idEdizione = (int) ($parametri['idEdizione'] ?? 0);

        $edizione = $this->edizioni->find($idEdizione);
        if ($edizione === null || (int) ($edizione['IDUniverso'] ?? 0) !== $idUniverso) {
            http_response_code(404);
            echo 'Edizione non trovata';
            return;
        }

        $this->bloccaSeEdizioneNonModificabile($idUniverso, $idEdizione, $edizione);

        try {
            $this->edizioni->autoAssegnaRose($idEdizione, null);
        } catch (\Throwable $e) {
            http_response_code(400);
            echo 'Errore auto-assegnazione: ' . $e->getMessage();
            return;
        }

        header('Location: /universi/' . $idUniverso . '/edizioni/' . $idEdizione . '/rose');
        exit;
    }

    public function roseAutoSquadra(Request $request, array $parametri): void
    {
        $idUniverso = (int) ($parametri['id'] ?? 0);
        $idEdizione = (int) ($parametri['idEdizione'] ?? 0);
        $idSquadra = (int) ($parametri['idSquadra'] ?? 0);

        $edizione = $this->edizioni->find($idEdizione);

        if ($edizione === null || (int) ($edizione['IDUniverso'] ?? 0) !== $idUniverso) {
            http_response_code(404);
            echo 'Edizione non trovata';
            return;
        }

        $this->bloccaSeEdizioneNonModificabile($idUniverso, $idEdizione, $edizione);

        try {
            $this->edizioni->autoAssegnaRose($idEdizione, $idSquadra);
        } catch (\Throwable $e) {
            http_response_code(400);
            echo 'Errore auto-assegnazione: ' . $e->getMessage();
            return;
        }

        header('Location: /universi/' . $idUniverso . '/edizioni/' . $idEdizione . '/rose/' . $idSquadra);
        exit;
    }

    public function finalizzaEdizione(Request $request, array $parametri): void
    {
        $idUniverso = (int) ($parametri['id'] ?? 0);
        $idEdizione = (int) ($parametri['idEdizione'] ?? 0);

        $edizione = $this->edizioni->find($idEdizione);

        if ($edizione === null || (int) ($edizione['IDUniverso'] ?? 0) !== $idUniverso) {
            http_response_code(404);
            echo 'Edizione non trovata';
            return;
        }

        if ((string) ($edizione['Stato'] ?? 'bozza') !== 'bozza') {
            header('Location: /universi/' . $idUniverso . '/edizioni/' . $idEdizione);
            exit;
        }

        $haGiocatoriEdizione = $this->edizioni->haGiocatoriEdizione($idEdizione);
        $roseComplete = $haGiocatoriEdizione ? $this->edizioni->tutteLeRoseComplete($idEdizione) : true;

        $squadreEdizione = $this->edizioni->squadreEdizione($idEdizione);
        $competizioni = $this->edizioni->competizioniEdizione($idEdizione);

        $squadreCoperte = [];
        $competizioniComplete = true;
        $messaggioErroreCompetizioni = null;

        foreach ($competizioni as $competizione) {
            $inizialmenteVuota = !empty($competizione['InizialmenteVuota']);

            if ($inizialmenteVuota) {
                continue;
            }

            $idEdizioneCompetizione = (int) ($competizione['ID'] ?? 0);
            $nomeCompetizione = (string) ($competizione['NomeCompetizione'] ?? 'Competizione');
            $numeroAtteso = (int) ($competizione['NumeroPartecipanti'] ?? 0);

            $squadreIscritte = $this->edizioni->squadreIscritteACompetizione($idEdizioneCompetizione);

            $idsSquadreIscritte = array_values(array_filter(
                array_map(
                    fn(array $squadra): int => (int) ($squadra['IDSquadra'] ?? 0),
                    $squadreIscritte
                ),
                fn(int $id): bool => $id > 0
            ));

            foreach ($idsSquadreIscritte as $idSquadra) {
                $squadreCoperte[$idSquadra] = true;
            }

            $numeroIscritte = count($idsSquadreIscritte);

            if ($numeroIscritte !== $numeroAtteso) {
                $competizioniComplete = false;
                $messaggioErroreCompetizioni = 'La competizione "' . $nomeCompetizione . '" deve avere esattamente ' . $numeroAtteso . ' squadre, attualmente ne ha ' . $numeroIscritte . '.';
                break;
            }
        }

        if ($competizioniComplete) {
            foreach ($squadreEdizione as $squadraEdizione) {
                $idSquadra = (int) ($squadraEdizione['IDSquadra'] ?? 0);

                if ($idSquadra > 0 && !isset($squadreCoperte[$idSquadra])) {
                    $competizioniComplete = false;
                    $messaggioErroreCompetizioni = 'Esistono squadre dell’edizione non presenti in alcuna competizione inizialmente attiva.';
                    break;
                }
            }
        }

        $coperturaFinalizzabili = $this->calcolaCoperturaCompetizioniFinalizzabili($idEdizione);

        if (!$roseComplete || !$competizioniComplete || !$coperturaFinalizzabili) {
            http_response_code(400);

            if (!$roseComplete) {
                echo 'Non puoi finalizzare: rose incomplete.';
                return;
            }

            if (!$competizioniComplete) {
                echo 'Non puoi finalizzare: ' . $messaggioErroreCompetizioni;
                return;
            }

            echo 'Non puoi finalizzare: configurazione competizioni non valida.';
            return;
        }

        $pdo = Database::getConnessione();

        try {
            $pdo->beginTransaction();

            $this->calendarioService->generaPerEdizione($idEdizione);

            $stmt = $pdo->prepare("
            UPDATE Edizioni
            SET Stato = 'in_corso'
            WHERE ID = :idEdizione
        ");

            $stmt->execute([
                'idEdizione' => $idEdizione,
            ]);

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            http_response_code(500);
            echo 'Errore durante la finalizzazione dell\'edizione: ' . $e->getMessage();
            return;
        }

        header('Location: /universi/' . $idUniverso . '/edizioni/' . $idEdizione);
        exit;
    }

    private function calcolaCoperturaCompetizioniFinalizzabili(int $idEdizione): bool
    {
        $squadreEdizione = $this->edizioni->squadreEdizione($idEdizione);
        $competizioni = $this->edizioni->competizioniEdizione($idEdizione);

        $squadreCoperte = [];

        foreach ($competizioni as $competizione) {
            $inizialmenteVuota = !empty($competizione['InizialmenteVuota']);

            if ($inizialmenteVuota) {
                continue;
            }

            $idEdizioneCompetizione = (int) ($competizione['ID'] ?? 0);
            $squadreIscritte = $this->edizioni->squadreIscritteACompetizione($idEdizioneCompetizione);

            foreach ($squadreIscritte as $squadraIscritta) {
                $idSquadra = (int) ($squadraIscritta['IDSquadra'] ?? 0);

                if ($idSquadra > 0) {
                    $squadreCoperte[$idSquadra] = true;
                }
            }
        }

        foreach ($squadreEdizione as $squadraEdizione) {
            $idSquadra = (int) ($squadraEdizione['IDSquadra'] ?? 0);

            if ($idSquadra > 0 && !isset($squadreCoperte[$idSquadra])) {
                return false;
            }
        }

        return true;
    }

    private function edizioneModificabile(array $edizione): bool
    {
        return (string) ($edizione['Stato'] ?? 'bozza') === 'bozza';
    }

    private function bloccaSeEdizioneNonModificabile(int $idUniverso, int $idEdizione, array $edizione): void
    {
        if ($this->edizioneModificabile($edizione)) {
            return;
        }

        http_response_code(403);
        echo 'Questa edizione non è più modificabile.';
        exit;
    }

    public function showCompetizione(Request $request, array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        $idEdizione = (int) ($params['idEdizione'] ?? 0);
        $idEdizioneCompetizione = (int) ($params['idEdizioneCompetizione'] ?? 0);

        $universo = $this->universi->find($id);
        $edizione = $this->edizioni->find($idEdizione);
        $competizione = $this->edizioni->findEdizioneCompetizione($idEdizioneCompetizione);

        if (!$universo || !$edizione || !$competizione) {
            http_response_code(404);
            echo 'Risorsa non trovata';
            return;
        }

        if ((int) ($competizione['IDEdizione'] ?? 0) !== $idEdizione) {
            http_response_code(404);
            echo 'Competizione non trovata per questa edizione';
            return;
        }

        $tipoCompetizione = mb_strtolower(trim((string) ($competizione['Tipo'] ?? '')));
        $simulazione = new SimulazioneService();

        if ($tipoCompetizione === 'eliminazione') {
            $blocchiPartite = $this->partite->partiteRaggruppatePerFaseEGiornata($idEdizioneCompetizione);

            foreach ($blocchiPartite as $chiave => $blocco) {
                foreach ($blocco['partite'] as $indice => $partita) {
                    $preview = $simulazione->calcolaPreviewPartita((int) $partita['ID']);
                    $blocchiPartite[$chiave]['partite'][$indice]['PreviewSimulazione'] = $preview;
                }
            }
        } else {
            $partitePerGiornata = $this->partite->partiteRaggruppatePerGiornata($idEdizioneCompetizione);
            $blocchiPartite = [];

            foreach ($partitePerGiornata as $giornata => $partite) {
                $chiave = 'giornata-' . (int) $giornata;
                $blocchiPartite[$chiave] = [
                    'chiave' => $chiave,
                    'anchor' => 'giornata-' . (int) $giornata,
                    'fase' => null,
                    'giornata' => (int) $giornata,
                    'titolo' => 'Giornata ' . (int) $giornata,
                    'partite' => $partite,
                ];

                foreach ($blocchiPartite[$chiave]['partite'] as $indice => $partita) {
                    $preview = $simulazione->calcolaPreviewPartita((int) $partita['ID']);
                    $blocchiPartite[$chiave]['partite'][$indice]['PreviewSimulazione'] = $preview;
                }
            }
        }

        require __DIR__ . '/../Views/edizioni/competizioni/show.php';
    }

    public function roseShow(Request $request, array $parametri): void
    {
        $idUniverso = (int) ($parametri['id'] ?? 0);
        $idEdizione = (int) ($parametri['idEdizione'] ?? 0);
        $idSquadra = (int) ($parametri['idSquadra'] ?? 0);

        $universo = $this->universi->find($idUniverso);
        $edizione = $this->edizioni->find($idEdizione);

        if ($universo === null) {
            http_response_code(404);
            echo 'Universo non trovato';
            return;
        }

        if ($edizione === null || (int) ($edizione['IDUniverso'] ?? 0) !== $idUniverso) {
            http_response_code(404);
            echo 'Edizione non trovata';
            return;
        }

        if (!$this->edizioni->haGiocatoriEdizione($idEdizione)) {
            header('Location: /universi/' . $idUniverso . '/edizioni/' . $idEdizione);
            exit;
        }

        $squadre = $this->edizioni->squadreEdizione($idEdizione);
        $squadra = null;

        foreach ($squadre as $riga) {
            if ((int) $riga['IDSquadra'] === $idSquadra) {
                $squadra = $riga;
                break;
            }
        }

        if ($squadra === null) {
            http_response_code(404);
            echo 'Squadra non trovata nell\'edizione';
            return;
        }

        $giocatoriAssegnati = $this->edizioni->giocatoriAssegnatiASquadra($idEdizione, $idSquadra);
        $verificaRosa = $this->edizioni->verificaRosaSquadra($idEdizione, $idSquadra);

        require __DIR__ . '/../Views/edizioni/rose/show.php';
    }

    public function classificaCompetizione(Request $request, array $params): void
    {
        $idUniverso = (int) ($params['id'] ?? 0);
        $idEdizione = (int) ($params['idEdizione'] ?? 0);
        $idEdizioneCompetizione = (int) ($params['idEdizioneCompetizione'] ?? 0);

        $universo = $this->universi->find($idUniverso);
        $edizione = $this->edizioni->find($idEdizione);
        $competizione = $this->edizioni->findEdizioneCompetizione($idEdizioneCompetizione);

        if (!$universo || !$edizione || !$competizione) {
            http_response_code(404);
            echo 'Risorsa non trovata';
            return;
        }

        if ((int) ($competizione['IDEdizione'] ?? 0) !== $idEdizione) {
            http_response_code(404);
            echo 'Competizione non trovata per questa edizione';
            return;
        }

        $giornate = $this->partite->giornatePerCompetizione($idEdizioneCompetizione);

        if (empty($giornate)) {
            $giornate = [1];
        }

        $giornataMin = (int) min($giornate);
        $giornataMax = (int) max($giornate);

        $giornataDa = (int) ($request->query['giornata_da'] ?? $giornataMin);
        $giornataA = (int) ($request->query['giornata_a'] ?? $giornataMax);
        $tabAttiva = (string) ($request->query['tab'] ?? 'generale');

        if ($giornataDa < $giornataMin) {
            $giornataDa = $giornataMin;
        }

        if ($giornataA > $giornataMax) {
            $giornataA = $giornataMax;
        }

        if ($giornataDa > $giornataA) {
            [$giornataDa, $giornataA] = [$giornataA, $giornataDa];
        }

        $struttura = json_decode((string) ($competizione['Struttura'] ?? '{}'), true);
        if (!is_array($struttura)) {
            $struttura = [];
        }

        $classificaService = new \App\Services\ClassificaService();

        $visteClassifica = $classificaService->calcolaVisteCompetizione(
            $idEdizioneCompetizione,
            $giornataDa,
            $giornataA,
            $struttura
        );

        $datiClassifica = $visteClassifica['generale'] ?? [];
        $tabellaCapolista = $classificaService->calcolaTabellaCapolista(
            $idEdizioneCompetizione,
            $struttura
        );

        require __DIR__ . '/../Views/edizioni/competizioni/classifica.php';
    }
}
