<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Models\Edizione;
use App\Models\Partita;
use App\Models\Universo;
use App\Services\SimulazioneService;

class PartitaController
{
    private Universo $universi;
    private Edizione $edizioni;
    private Partita $partite;
    private SimulazioneService $simulazione;

    public function __construct()
    {
        $this->universi = new Universo();
        $this->edizioni = new Edizione();
        $this->partite = new Partita();
        $this->simulazione = new SimulazioneService();
    }

    public function salvaRisultato(Request $request, array $params): void
    {
        $idUniverso = (int) ($params['id'] ?? 0);
        $idEdizione = (int) ($params['idEdizione'] ?? 0);
        $idEdizioneCompetizione = (int) ($params['idEdizioneCompetizione'] ?? 0);

        if (!$this->validaContestoCompetizione($idUniverso, $idEdizione, $idEdizioneCompetizione)) {
            return;
        }

        $idPartita = (int) ($_POST['id_partita'] ?? 0);
        $goalCasaRaw = $_POST['goal_casa'] ?? '';
        $goalTrasfertaRaw = $_POST['goal_trasferta'] ?? '';

        if ($idPartita <= 0) {
            http_response_code(422);
            echo 'Partita non valida';
            return;
        }

        $partita = $this->partite->find($idPartita);

        if (!$partita || (int) ($partita['IDEdizioneCompetizione'] ?? 0) !== $idEdizioneCompetizione) {
            http_response_code(404);
            echo 'Partita non trovata';
            return;
        }

        $this->salvaRisultatoPartitaById($idPartita, $goalCasaRaw, $goalTrasfertaRaw);
        $this->redirectCompetizione($idUniverso, $idEdizione, $idEdizioneCompetizione, $this->anchorDaPartita($partita));
    }

    public function simulaPartita(Request $request, array $params): void
    {
        $idUniverso = (int) ($params['id'] ?? 0);
        $idEdizione = (int) ($params['idEdizione'] ?? 0);
        $idEdizioneCompetizione = (int) ($params['idEdizioneCompetizione'] ?? 0);
        $idPartita = (int) ($params['idPartita'] ?? 0);

        if (!$this->validaContestoCompetizione($idUniverso, $idEdizione, $idEdizioneCompetizione)) {
            return;
        }

        $partita = $this->partite->find($idPartita);

        if (!$partita || (int) ($partita['IDEdizioneCompetizione'] ?? 0) !== $idEdizioneCompetizione) {
            http_response_code(404);
            echo 'Partita non trovata';
            return;
        }

        $this->simulaPartitaById($idPartita);
        $this->redirectCompetizione($idUniverso, $idEdizione, $idEdizioneCompetizione, $this->anchorDaPartita($partita));
    }

    public function resetPartita(Request $request, array $params): void
    {
        $idUniverso = (int) ($params['id'] ?? 0);
        $idEdizione = (int) ($params['idEdizione'] ?? 0);
        $idEdizioneCompetizione = (int) ($params['idEdizioneCompetizione'] ?? 0);
        $idPartita = (int) ($params['idPartita'] ?? 0);

        if (!$this->validaContestoCompetizione($idUniverso, $idEdizione, $idEdizioneCompetizione)) {
            return;
        }

        $partita = $this->partite->find($idPartita);

        if (!$partita || (int) ($partita['IDEdizioneCompetizione'] ?? 0) !== $idEdizioneCompetizione) {
            http_response_code(404);
            echo 'Partita non trovata';
            return;
        }

        $this->resetPartitaById($idPartita);
        $this->redirectCompetizione($idUniverso, $idEdizione, $idEdizioneCompetizione, $this->anchorDaPartita($partita));
    }

    public function salvaGiornata(Request $request, array $params): void
    {
        $idUniverso = (int) ($params['id'] ?? 0);
        $idEdizione = (int) ($params['idEdizione'] ?? 0);
        $idEdizioneCompetizione = (int) ($params['idEdizioneCompetizione'] ?? 0);
        $giornata = (int) ($params['giornata'] ?? 0);

        if (!$this->validaContestoCompetizione($idUniverso, $idEdizione, $idEdizioneCompetizione)) {
            return;
        }

        if ($giornata <= 0) {
            http_response_code(422);
            echo 'Giornata non valida';
            return;
        }

        $partitePerGiornata = $this->partite->partiteRaggruppatePerGiornata($idEdizioneCompetizione);
        $partite = $partitePerGiornata[$giornata] ?? [];
        $payloadPartite = $_POST['partite'] ?? [];

        if (!is_array($payloadPartite)) {
            $payloadPartite = [];
        }

        foreach ($partite as $partita) {
            $idPartita = (int) ($partita['ID'] ?? 0);

            if ($idPartita <= 0 || !isset($payloadPartite[$idPartita]) || !is_array($payloadPartite[$idPartita])) {
                continue;
            }

            $goalCasaRaw = $payloadPartite[$idPartita]['goal_casa'] ?? '';
            $goalTrasfertaRaw = $payloadPartite[$idPartita]['goal_trasferta'] ?? '';

            $this->salvaRisultatoPartitaById($idPartita, $goalCasaRaw, $goalTrasfertaRaw);
        }

        $anchor = 'giornata-' . $giornata;

        if ($partite !== []) {
            $anchor = $this->anchorDaPartita($partite[0]);
        }

        $this->redirectCompetizione($idUniverso, $idEdizione, $idEdizioneCompetizione, $anchor);
    }

    public function simulaGiornata(Request $request, array $params): void
    {
        $idUniverso = (int) ($params['id'] ?? 0);
        $idEdizione = (int) ($params['idEdizione'] ?? 0);
        $idEdizioneCompetizione = (int) ($params['idEdizioneCompetizione'] ?? 0);
        $giornata = (int) ($params['giornata'] ?? 0);

        if (!$this->validaContestoCompetizione($idUniverso, $idEdizione, $idEdizioneCompetizione)) {
            return;
        }

        if ($giornata <= 0) {
            http_response_code(422);
            echo 'Giornata non valida';
            return;
        }

        $partitePerGiornata = $this->partite->partiteRaggruppatePerGiornata($idEdizioneCompetizione);
        $partite = $partitePerGiornata[$giornata] ?? [];

        foreach ($partite as $partita) {
            $idPartita = (int) ($partita['ID'] ?? 0);

            if ($idPartita > 0) {
                $this->simulaPartitaById($idPartita);
            }
        }

        $anchor = 'giornata-' . $giornata;

        if ($partite !== []) {
            $anchor = $this->anchorDaPartita($partite[0]);
        }

        $this->redirectCompetizione($idUniverso, $idEdizione, $idEdizioneCompetizione, $anchor);
    }

    public function resetGiornata(Request $request, array $params): void
    {
        $idUniverso = (int) ($params['id'] ?? 0);
        $idEdizione = (int) ($params['idEdizione'] ?? 0);
        $idEdizioneCompetizione = (int) ($params['idEdizioneCompetizione'] ?? 0);
        $giornata = (int) ($params['giornata'] ?? 0);

        if (!$this->validaContestoCompetizione($idUniverso, $idEdizione, $idEdizioneCompetizione)) {
            return;
        }

        if ($giornata <= 0) {
            http_response_code(422);
            echo 'Giornata non valida';
            return;
        }

        $partitePerGiornata = $this->partite->partiteRaggruppatePerGiornata($idEdizioneCompetizione);
        $partite = $partitePerGiornata[$giornata] ?? [];

        foreach ($partite as $partita) {
            $idPartita = (int) ($partita['ID'] ?? 0);

            if ($idPartita > 0) {
                $this->resetPartitaById($idPartita);
            }
        }

        $anchor = 'giornata-' . $giornata;

        if ($partite !== []) {
            $anchor = $this->anchorDaPartita($partite[0]);
        }

        $this->redirectCompetizione($idUniverso, $idEdizione, $idEdizioneCompetizione, $anchor);
    }

    public function salvaTutte(Request $request, array $params): void
    {
        $idUniverso = (int) ($params['id'] ?? 0);
        $idEdizione = (int) ($params['idEdizione'] ?? 0);
        $idEdizioneCompetizione = (int) ($params['idEdizioneCompetizione'] ?? 0);

        if (!$this->validaContestoCompetizione($idUniverso, $idEdizione, $idEdizioneCompetizione)) {
            return;
        }

        $partitePerGiornata = $this->partite->partiteRaggruppatePerGiornata($idEdizioneCompetizione);
        $payloadPartite = $_POST['partite'] ?? [];

        if (!is_array($payloadPartite)) {
            $payloadPartite = [];
        }

        foreach ($partitePerGiornata as $partite) {
            foreach ($partite as $partita) {
                $idPartita = (int) ($partita['ID'] ?? 0);

                if ($idPartita <= 0 || !isset($payloadPartite[$idPartita]) || !is_array($payloadPartite[$idPartita])) {
                    continue;
                }

                $goalCasaRaw = $payloadPartite[$idPartita]['goal_casa'] ?? '';
                $goalTrasfertaRaw = $payloadPartite[$idPartita]['goal_trasferta'] ?? '';

                $this->salvaRisultatoPartitaById($idPartita, $goalCasaRaw, $goalTrasfertaRaw);
            }
        }

        $this->redirectCompetizione($idUniverso, $idEdizione, $idEdizioneCompetizione);
    }

    public function simulaTutte(Request $request, array $params): void
    {
        $idUniverso = (int) ($params['id'] ?? 0);
        $idEdizione = (int) ($params['idEdizione'] ?? 0);
        $idEdizioneCompetizione = (int) ($params['idEdizioneCompetizione'] ?? 0);

        if (!$this->validaContestoCompetizione($idUniverso, $idEdizione, $idEdizioneCompetizione)) {
            return;
        }

        $partitePerGiornata = $this->partite->partiteRaggruppatePerGiornata($idEdizioneCompetizione);

        foreach ($partitePerGiornata as $partite) {
            foreach ($partite as $partita) {
                $idPartita = (int) ($partita['ID'] ?? 0);

                if ($idPartita > 0) {
                    $this->simulaPartitaById($idPartita);
                }
            }
        }

        $this->redirectCompetizione($idUniverso, $idEdizione, $idEdizioneCompetizione);
    }

    public function resetTutte(Request $request, array $params): void
    {
        $idUniverso = (int) ($params['id'] ?? 0);
        $idEdizione = (int) ($params['idEdizione'] ?? 0);
        $idEdizioneCompetizione = (int) ($params['idEdizioneCompetizione'] ?? 0);

        if (!$this->validaContestoCompetizione($idUniverso, $idEdizione, $idEdizioneCompetizione)) {
            return;
        }

        $partitePerGiornata = $this->partite->partiteRaggruppatePerGiornata($idEdizioneCompetizione);

        foreach ($partitePerGiornata as $partite) {
            foreach ($partite as $partita) {
                $idPartita = (int) ($partita['ID'] ?? 0);

                if ($idPartita > 0) {
                    $this->resetPartitaById($idPartita);
                }
            }
        }

        $this->redirectCompetizione($idUniverso, $idEdizione, $idEdizioneCompetizione);
    }

    private function simulaPartitaById(int $idPartita): void
    {
        $this->simulazione->simulaPartita($idPartita);
    }

    private function salvaRisultatoPartitaById(int $idPartita, mixed $goalCasaRaw, mixed $goalTrasfertaRaw): bool
    {
        $goalCasaString = is_string($goalCasaRaw) ? trim($goalCasaRaw) : (string) $goalCasaRaw;
        $goalTrasfertaString = is_string($goalTrasfertaRaw) ? trim($goalTrasfertaRaw) : (string) $goalTrasfertaRaw;

        $goalCasaVuoto = $goalCasaString === '';
        $goalTrasfertaVuoto = $goalTrasfertaString === '';

        if ($goalCasaVuoto && $goalTrasfertaVuoto) {
            return false;
        }

        if ($goalCasaVuoto || $goalTrasfertaVuoto) {
            return false;
        }

        if (!is_numeric($goalCasaString) || !is_numeric($goalTrasfertaString)) {
            return false;
        }

        $goalCasa = (int) $goalCasaString;
        $goalTrasferta = (int) $goalTrasfertaString;

        if ($goalCasa < 0 || $goalTrasferta < 0) {
            return false;
        }

        $this->partite->aggiornaRisultatoPartita($idPartita, $goalCasa, $goalTrasferta, 'giocata');
        return true;
    }

    private function resetPartitaById(int $idPartita): void
    {
        $this->partite->aggiornaRisultatoPartita($idPartita, null, null, 'programmata');
    }

    private function validaContestoCompetizione(int $idUniverso, int $idEdizione, int $idEdizioneCompetizione): bool
    {
        $universo = $this->universi->find($idUniverso);
        $edizione = $this->edizioni->find($idEdizione);
        $competizione = $this->edizioni->findEdizioneCompetizione($idEdizioneCompetizione);

        if (!$universo || !$edizione || !$competizione) {
            http_response_code(404);
            echo 'Risorsa non trovata';
            return false;
        }

        if ((int) ($edizione['IDUniverso'] ?? 0) !== $idUniverso) {
            http_response_code(404);
            echo 'Edizione non trovata per questo universo';
            return false;
        }

        if ((int) ($competizione['IDEdizione'] ?? 0) !== $idEdizione) {
            http_response_code(404);
            echo 'Competizione non trovata per questa edizione';
            return false;
        }

        return true;
    }

    private function redirectCompetizione(
        int $idUniverso,
        int $idEdizione,
        int $idEdizioneCompetizione,
        ?string $anchor = null
    ): void {
        $url = '/universi/' . $idUniverso . '/edizioni/' . $idEdizione . '/competizioni/' . $idEdizioneCompetizione;

        if ($anchor !== null && $anchor !== '') {
            $url .= '#' . rawurlencode($anchor);
        }

        header('Location: ' . $url);
        exit;
    }

    private function anchorDaPartita(array $partita): string
    {
        $fase = $partita['Fase'] ?? null;
        $giornata = (int) ($partita['Giornata'] ?? 0);

        if ($fase === null || $fase === '') {
            return 'giornata-' . $giornata;
        }

        return 'fase-' . $this->slug((string) $fase) . '-giornata-' . $giornata;
    }

    private function slug(string $testo): string
    {
        $slug = mb_strtolower(trim($testo));
        $slug = str_replace(
            ['à', 'è', 'é', 'ì', 'ò', 'ù'],
            ['a', 'e', 'e', 'i', 'o', 'u'],
            $slug
        );
        $slug = preg_replace('/[^a-z0-9]+/u', '-', $slug) ?? '';
        return trim($slug, '-');
    }
}