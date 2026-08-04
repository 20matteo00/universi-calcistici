<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Http\Request;
use App\Models\Edizione;
use App\Models\Universo;
use App\Services\CreazioneEdizioneService;
use App\Services\CalendarioService;

class EdizioneController
{
    private Universo $universi;
    private Edizione $edizioni;
    private CreazioneEdizioneService $creazioneEdizioneService;
    private CalendarioService $calendarioService;

    public function __construct()
    {
        $this->universi = new Universo();
        $this->edizioni = new Edizione();
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
            echo 'Squadra non trovata nell’edizione';
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
            echo 'Squadra non trovata nell’edizione';
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
                $errori[] = 'Una o più squadre selezionate non appartengono all’edizione.';
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

        header('Location: /universi/' . $idUniverso . '/edizioni/' . $idEdizione . '/competizioni/' . $idEdizioneCompetizione);
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

        foreach ($competizioni as $competizione) {
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

        if (!$roseComplete || !$competizioniComplete) {
            http_response_code(400);
            echo 'Non puoi finalizzare: rose incomplete oppure esistono squadre non presenti in alcuna competizione.';
            return;
        }

        $pdo = Database::getConnessione();
        $stmt = $pdo->prepare("
            UPDATE Edizioni
            SET Stato = 'in_corso'
            WHERE ID = :idEdizione
        ");

        $this->calendarioService->generaPerEdizione($idEdizione);

        $stmt->execute([
            'idEdizione' => $idEdizione,
        ]);

        header('Location: /universi/' . $idUniverso . '/edizioni/' . $idEdizione);
        exit;
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

        $partitePerGiornata = $this->edizioni->partiteRaggruppatePerGiornata($idEdizioneCompetizione);

        require __DIR__ . '/../Views/edizioni/competizioni/show.php';
    }

    public function salvaRisultatoPartita(Request $request, array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        $idEdizione = (int) ($params['idEdizione'] ?? 0);
        $idEdizioneCompetizione = (int) ($params['idEdizioneCompetizione'] ?? 0);

        $edizione = $this->edizioni->find($idEdizione);
        $competizione = $this->edizioni->findEdizioneCompetizione($idEdizioneCompetizione);

        if (!$edizione || !$competizione || (int) ($competizione['IDEdizione'] ?? 0) !== $idEdizione) {
            http_response_code(404);
            echo 'Risorsa non trovata';
            return;
        }

        $idPartita = (int) ($_POST['id_partita'] ?? 0);
        $goalCasa = $_POST['goal_casa'] ?? null;
        $goalTrasferta = $_POST['goal_trasferta'] ?? null;

        $goalCasa = $goalCasa === '' ? null : (int) $goalCasa;
        $goalTrasferta = $goalTrasferta === '' ? null : (int) $goalTrasferta;

        if ($idPartita <= 0) {
            http_response_code(422);
            echo 'Partita non valida';
            return;
        }

        $partita = $this->edizioni->findPartita($idPartita);

        if (!$partita || (int) ($partita['IDEdizioneCompetizione'] ?? 0) !== $idEdizioneCompetizione) {
            http_response_code(404);
            echo 'Partita non trovata';
            return;
        }

        $stato = ($goalCasa !== null && $goalTrasferta !== null) ? 'giocata' : 'programmata';

        $this->edizioni->aggiornaRisultatoPartita($idPartita, $goalCasa, $goalTrasferta, $stato);

        header('Location: /universi/' . $id . '/edizioni/' . $idEdizione . '/competizioni/' . $idEdizioneCompetizione);
        exit;
    }
}
