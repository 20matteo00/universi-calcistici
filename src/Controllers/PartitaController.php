<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Http\Request;
use App\Models\EdizioneCompetizione;
use App\Models\PartitaQuery;
use App\Services\Partite\PartitaContextService;
use App\Services\Partite\PartitaLockService;
use App\Services\Partite\PartitaResetService;
use App\Services\Partite\PartitaResultService;
use App\Services\Partite\PartitaSimulationService;

final class PartitaController
{
    private PartitaQuery $partiteQuery;
    private EdizioneCompetizione $edizioneCompetizioni;
    private PartitaContextService $partitaContext;
    private PartitaResultService $partitaResult;
    private PartitaSimulationService $partitaSimulation;
    private PartitaResetService $partitaReset;
    private PartitaLockService $partitaLock;

    public function __construct()
    {
        Database::getConnessione();

        $this->partiteQuery = new PartitaQuery();
        $this->edizioneCompetizioni = new EdizioneCompetizione();
        $this->partitaContext = new PartitaContextService();
        $this->partitaResult = new PartitaResultService();
        $this->partitaSimulation = new PartitaSimulationService();
        $this->partitaReset = new PartitaResetService();
        $this->partitaLock = new PartitaLockService();
    }

    private function bloccaSeCompetizioneConclusa(
        int $idUniverso,
        int $idEdizione,
        int $idEdizioneCompetizione,
        ?string $anchor = null
    ): void {
        $competizione = $this->edizioneCompetizioni->findEdizioneCompetizione($idEdizioneCompetizione);

        if ($competizione === null) {
            http_response_code(404);
            echo 'Competizione non trovata';
            exit;
        }

        if ((string) ($competizione['Stato'] ?? 'in_corso') !== 'conclusa') {
            return;
        }

        $_SESSION['flash_error'] = 'La competizione è conclusa e non può più essere modificata.';
        $this->redirectCompetizione($idUniverso, $idEdizione, $idEdizioneCompetizione, $anchor);
    }

    private function partiteFaseGiornata(
        int $idEdizioneCompetizione,
        string $fase,
        int $giornata
    ): array {
        $gruppi = $this->partiteQuery->partiteRaggruppatePerFaseEGiornata($idEdizioneCompetizione);
        $chiave = mb_strtolower(trim($fase)) . '-' . $giornata;

        return $gruppi[$chiave]['partite'] ?? [];
    }

    public function salvaRisultato(Request $request, array $params): void
    {
        $idUniverso = (int) ($params['id'] ?? 0);
        $idEdizione = (int) ($params['idEdizione'] ?? 0);
        $idEdizioneCompetizione = (int) ($params['idEdizioneCompetizione'] ?? 0);

        if (!$this->partitaContext->contestoCompetizioneValido(
            $idUniverso,
            $idEdizione,
            $idEdizioneCompetizione
        )) {
            http_response_code(404);
            echo 'Risorsa non trovata';
            return;
        }

        $this->bloccaSeCompetizioneConclusa(
            $idUniverso,
            $idEdizione,
            $idEdizioneCompetizione
        );

        $idPartita = (int) ($_POST['id_partita'] ?? 0);
        $goalCasaRaw = $_POST['goal_casa'] ?? '';
        $goalTrasfertaRaw = $_POST['goal_trasferta'] ?? '';

        if ($idPartita <= 0) {
            http_response_code(422);
            echo 'Partita non valida';
            return;
        }

        $partita = $this->partitaContext->trovaPartitaDellaCompetizione(
            $idPartita,
            $idEdizioneCompetizione
        );

        if (!$partita) {
            http_response_code(404);
            echo 'Partita non trovata';
            return;
        }

        if ($this->partitaLock->turnoEliminazioneBloccato($idEdizioneCompetizione, $partita)) {
            $this->redirectCompetizione(
                $idUniverso,
                $idEdizione,
                $idEdizioneCompetizione,
                $this->partitaContext->anchorDaPartita($partita)
            );
        }

        $this->partitaResult->salvaRisultato(
            $idPartita,
            $goalCasaRaw,
            $goalTrasfertaRaw,
            true
        );

        $this->redirectCompetizione(
            $idUniverso,
            $idEdizione,
            $idEdizioneCompetizione,
            $this->partitaContext->anchorDaPartita($partita)
        );
    }

    public function simulaPartita(Request $request, array $params): void
    {
        $idUniverso = (int) ($params['id'] ?? 0);
        $idEdizione = (int) ($params['idEdizione'] ?? 0);
        $idEdizioneCompetizione = (int) ($params['idEdizioneCompetizione'] ?? 0);
        $idPartita = (int) ($params['idPartita'] ?? 0);

        if (!$this->partitaContext->contestoCompetizioneValido(
            $idUniverso,
            $idEdizione,
            $idEdizioneCompetizione
        )) {
            http_response_code(404);
            echo 'Risorsa non trovata';
            return;
        }

        $this->bloccaSeCompetizioneConclusa(
            $idUniverso,
            $idEdizione,
            $idEdizioneCompetizione
        );

        $partita = $this->partitaContext->trovaPartitaDellaCompetizione(
            $idPartita,
            $idEdizioneCompetizione
        );

        if (!$partita) {
            http_response_code(404);
            echo 'Partita non trovata';
            return;
        }

        if ($this->partitaLock->turnoEliminazioneBloccato($idEdizioneCompetizione, $partita)) {
            $this->redirectCompetizione(
                $idUniverso,
                $idEdizione,
                $idEdizioneCompetizione,
                $this->partitaContext->anchorDaPartita($partita)
            );
        }

        $this->partitaSimulation->simula($idPartita, true);

        $this->redirectCompetizione(
            $idUniverso,
            $idEdizione,
            $idEdizioneCompetizione,
            $this->partitaContext->anchorDaPartita($partita)
        );
    }

    public function resetPartita(Request $request, array $params): void
    {
        $idUniverso = (int) ($params['id'] ?? 0);
        $idEdizione = (int) ($params['idEdizione'] ?? 0);
        $idEdizioneCompetizione = (int) ($params['idEdizioneCompetizione'] ?? 0);
        $idPartita = (int) ($params['idPartita'] ?? 0);

        if (!$this->partitaContext->contestoCompetizioneValido(
            $idUniverso,
            $idEdizione,
            $idEdizioneCompetizione
        )) {
            http_response_code(404);
            echo 'Risorsa non trovata';
            return;
        }

        $this->bloccaSeCompetizioneConclusa(
            $idUniverso,
            $idEdizione,
            $idEdizioneCompetizione
        );

        $partita = $this->partitaContext->trovaPartitaDellaCompetizione(
            $idPartita,
            $idEdizioneCompetizione
        );

        if (!$partita) {
            http_response_code(404);
            echo 'Partita non trovata';
            return;
        }

        if ($this->partitaLock->turnoEliminazioneBloccato($idEdizioneCompetizione, $partita)) {
            $this->redirectCompetizione(
                $idUniverso,
                $idEdizione,
                $idEdizioneCompetizione,
                $this->partitaContext->anchorDaPartita($partita)
            );
        }

        $this->partitaReset->resetta($idPartita);

        $this->redirectCompetizione(
            $idUniverso,
            $idEdizione,
            $idEdizioneCompetizione,
            $this->partitaContext->anchorDaPartita($partita)
        );
    }

    public function salvaGiornata(Request $request, array $params): void
    {
        $idUniverso = (int) ($params['id'] ?? 0);
        $idEdizione = (int) ($params['idEdizione'] ?? 0);
        $idEdizioneCompetizione = (int) ($params['idEdizioneCompetizione'] ?? 0);
        $giornata = (int) ($params['giornata'] ?? 0);

        if (!$this->partitaContext->contestoCompetizioneValido(
            $idUniverso,
            $idEdizione,
            $idEdizioneCompetizione
        )) {
            http_response_code(404);
            echo 'Risorsa non trovata';
            return;
        }

        if ($giornata <= 0) {
            http_response_code(422);
            echo 'Giornata non valida';
            return;
        }

        $this->bloccaSeCompetizioneConclusa(
            $idUniverso,
            $idEdizione,
            $idEdizioneCompetizione,
            'giornata-' . $giornata
        );

        $partitePerGiornata = $this->partiteQuery->partiteRaggruppatePerGiornata($idEdizioneCompetizione);
        $partite = $partitePerGiornata[$giornata] ?? [];
        $payloadPartite = $_POST['partite'] ?? [];

        if (!is_array($payloadPartite)) {
            $payloadPartite = [];
        }

        foreach ($partite as $partita) {
            $idPartita = (int) ($partita['ID'] ?? 0);

            if (
                $idPartita <= 0 ||
                !isset($payloadPartite[$idPartita]) ||
                !is_array($payloadPartite[$idPartita])
            ) {
                continue;
            }

            $goalCasaRaw = $payloadPartite[$idPartita]['goal_casa'] ?? '';
            $goalTrasfertaRaw = $payloadPartite[$idPartita]['goal_trasferta'] ?? '';

            $this->partitaResult->salvaRisultato(
                $idPartita,
                $goalCasaRaw,
                $goalTrasfertaRaw,
                false
            );
        }

        $anchor = 'giornata-' . $giornata;

        if ($partite !== []) {
            $anchor = $this->partitaContext->anchorDaPartita($partite[0]);
        }

        $this->redirectCompetizione($idUniverso, $idEdizione, $idEdizioneCompetizione, $anchor);
    }

    public function simulaGiornata(Request $request, array $params): void
    {
        $idUniverso = (int) ($params['id'] ?? 0);
        $idEdizione = (int) ($params['idEdizione'] ?? 0);
        $idEdizioneCompetizione = (int) ($params['idEdizioneCompetizione'] ?? 0);
        $giornata = (int) ($params['giornata'] ?? 0);

        if (!$this->partitaContext->contestoCompetizioneValido(
            $idUniverso,
            $idEdizione,
            $idEdizioneCompetizione
        )) {
            http_response_code(404);
            echo 'Risorsa non trovata';
            return;
        }

        if ($giornata <= 0) {
            http_response_code(422);
            echo 'Giornata non valida';
            return;
        }

        $this->bloccaSeCompetizioneConclusa(
            $idUniverso,
            $idEdizione,
            $idEdizioneCompetizione,
            'giornata-' . $giornata
        );

        $partitePerGiornata = $this->partiteQuery->partiteRaggruppatePerGiornata($idEdizioneCompetizione);
        $partite = $partitePerGiornata[$giornata] ?? [];

        foreach ($partite as $partita) {
            $idPartita = (int) ($partita['ID'] ?? 0);

            if ($idPartita <= 0) {
                continue;
            }

            $this->partitaSimulation->simula($idPartita, false);
        }

        $anchor = 'giornata-' . $giornata;

        if ($partite !== []) {
            $anchor = $this->partitaContext->anchorDaPartita($partite[0]);
        }

        $this->redirectCompetizione($idUniverso, $idEdizione, $idEdizioneCompetizione, $anchor);
    }

    public function resetGiornata(Request $request, array $params): void
    {
        $idUniverso = (int) ($params['id'] ?? 0);
        $idEdizione = (int) ($params['idEdizione'] ?? 0);
        $idEdizioneCompetizione = (int) ($params['idEdizioneCompetizione'] ?? 0);
        $giornata = (int) ($params['giornata'] ?? 0);

        if (!$this->partitaContext->contestoCompetizioneValido(
            $idUniverso,
            $idEdizione,
            $idEdizioneCompetizione
        )) {
            http_response_code(404);
            echo 'Risorsa non trovata';
            return;
        }

        if ($giornata <= 0) {
            http_response_code(422);
            echo 'Giornata non valida';
            return;
        }

        $this->bloccaSeCompetizioneConclusa(
            $idUniverso,
            $idEdizione,
            $idEdizioneCompetizione,
            'giornata-' . $giornata
        );

        $partitePerGiornata = $this->partiteQuery->partiteRaggruppatePerGiornata($idEdizioneCompetizione);
        $partite = $partitePerGiornata[$giornata] ?? [];

        foreach ($partite as $partita) {
            $idPartita = (int) ($partita['ID'] ?? 0);

            if ($idPartita > 0) {
                $this->partitaReset->resetta($idPartita);
            }
        }

        $anchor = 'giornata-' . $giornata;

        if ($partite !== []) {
            $anchor = $this->partitaContext->anchorDaPartita($partite[0]);
        }

        $this->redirectCompetizione($idUniverso, $idEdizione, $idEdizioneCompetizione, $anchor);
    }

    public function salvaFaseGiornata(Request $request, array $params): void
    {
        $idUniverso = (int) ($params['id'] ?? 0);
        $idEdizione = (int) ($params['idEdizione'] ?? 0);
        $idEdizioneCompetizione = (int) ($params['idEdizioneCompetizione'] ?? 0);
        $fase = trim((string) ($params['fase'] ?? ''));
        $giornata = (int) ($params['giornata'] ?? 0);

        if (!$this->partitaContext->contestoCompetizioneValido(
            $idUniverso,
            $idEdizione,
            $idEdizioneCompetizione
        )) {
            http_response_code(404);
            echo 'Risorsa non trovata';
            return;
        }

        if ($fase === '' || $giornata <= 0) {
            http_response_code(422);
            echo 'Fase o giornata non valida';
            return;
        }

        $this->bloccaSeCompetizioneConclusa(
            $idUniverso,
            $idEdizione,
            $idEdizioneCompetizione,
            $this->partitaContext->anchorDaFaseEGiornata($fase, $giornata)
        );

        $partite = $this->partiteFaseGiornata($idEdizioneCompetizione, $fase, $giornata);

        if (
            $partite !== [] &&
            $this->partitaLock->turnoEliminazioneBloccato($idEdizioneCompetizione, $partite[0])
        ) {
            $this->redirectCompetizione(
                $idUniverso,
                $idEdizione,
                $idEdizioneCompetizione,
                $this->partitaContext->anchorDaPartita($partite[0])
            );
        }

        $payloadPartite = $_POST['partite'] ?? [];

        if (!is_array($payloadPartite)) {
            $payloadPartite = [];
        }

        foreach ($partite as $partita) {
            $idPartita = (int) ($partita['ID'] ?? 0);

            if (
                $idPartita <= 0 ||
                !isset($payloadPartite[$idPartita]) ||
                !is_array($payloadPartite[$idPartita])
            ) {
                continue;
            }

            $goalCasaRaw = $payloadPartite[$idPartita]['goal_casa'] ?? '';
            $goalTrasfertaRaw = $payloadPartite[$idPartita]['goal_trasferta'] ?? '';

            $this->partitaResult->salvaRisultato(
                $idPartita,
                $goalCasaRaw,
                $goalTrasfertaRaw,
                false
            );
        }

        $anchor = $this->partitaContext->anchorDaFaseEGiornata($fase, $giornata);

        $this->redirectCompetizione($idUniverso, $idEdizione, $idEdizioneCompetizione, $anchor);
    }

    public function simulaFaseGiornata(Request $request, array $params): void
    {
        $idUniverso = (int) ($params['id'] ?? 0);
        $idEdizione = (int) ($params['idEdizione'] ?? 0);
        $idEdizioneCompetizione = (int) ($params['idEdizioneCompetizione'] ?? 0);
        $fase = trim((string) ($params['fase'] ?? ''));
        $giornata = (int) ($params['giornata'] ?? 0);

        if (!$this->partitaContext->contestoCompetizioneValido(
            $idUniverso,
            $idEdizione,
            $idEdizioneCompetizione
        )) {
            http_response_code(404);
            echo 'Risorsa non trovata';
            return;
        }

        if ($fase === '' || $giornata <= 0) {
            http_response_code(422);
            echo 'Fase o giornata non valida';
            return;
        }

        $this->bloccaSeCompetizioneConclusa(
            $idUniverso,
            $idEdizione,
            $idEdizioneCompetizione,
            $this->partitaContext->anchorDaFaseEGiornata($fase, $giornata)
        );

        $partite = $this->partiteFaseGiornata($idEdizioneCompetizione, $fase, $giornata);

        if (
            $partite !== [] &&
            $this->partitaLock->turnoEliminazioneBloccato($idEdizioneCompetizione, $partite[0])
        ) {
            $this->redirectCompetizione(
                $idUniverso,
                $idEdizione,
                $idEdizioneCompetizione,
                $this->partitaContext->anchorDaPartita($partite[0])
            );
        }

        foreach ($partite as $partita) {
            $idPartita = (int) ($partita['ID'] ?? 0);

            if ($idPartita <= 0) {
                continue;
            }

            $this->partitaSimulation->simula($idPartita, false);
        }

        $anchor = $this->partitaContext->anchorDaFaseEGiornata($fase, $giornata);

        $this->redirectCompetizione($idUniverso, $idEdizione, $idEdizioneCompetizione, $anchor);
    }

    public function resetFaseGiornata(Request $request, array $params): void
    {
        $idUniverso = (int) ($params['id'] ?? 0);
        $idEdizione = (int) ($params['idEdizione'] ?? 0);
        $idEdizioneCompetizione = (int) ($params['idEdizioneCompetizione'] ?? 0);
        $fase = trim((string) ($params['fase'] ?? ''));
        $giornata = (int) ($params['giornata'] ?? 0);

        if (!$this->partitaContext->contestoCompetizioneValido(
            $idUniverso,
            $idEdizione,
            $idEdizioneCompetizione
        )) {
            http_response_code(404);
            echo 'Risorsa non trovata';
            return;
        }

        if ($fase === '' || $giornata <= 0) {
            http_response_code(422);
            echo 'Fase o giornata non valida';
            return;
        }

        $this->bloccaSeCompetizioneConclusa(
            $idUniverso,
            $idEdizione,
            $idEdizioneCompetizione,
            $this->partitaContext->anchorDaFaseEGiornata($fase, $giornata)
        );

        $partite = $this->partiteFaseGiornata($idEdizioneCompetizione, $fase, $giornata);

        if (
            $partite !== [] &&
            $this->partitaLock->turnoEliminazioneBloccato($idEdizioneCompetizione, $partite[0])
        ) {
            $this->redirectCompetizione(
                $idUniverso,
                $idEdizione,
                $idEdizioneCompetizione,
                $this->partitaContext->anchorDaPartita($partite[0])
            );
        }

        foreach ($partite as $partita) {
            $idPartita = (int) ($partita['ID'] ?? 0);

            if ($idPartita > 0) {
                $this->partitaReset->resetta($idPartita);
            }
        }

        $anchor = $this->partitaContext->anchorDaFaseEGiornata($fase, $giornata);

        $this->redirectCompetizione($idUniverso, $idEdizione, $idEdizioneCompetizione, $anchor);
    }

    public function salvaTutte(Request $request, array $params): void
    {
        $idUniverso = (int) ($params['id'] ?? 0);
        $idEdizione = (int) ($params['idEdizione'] ?? 0);
        $idEdizioneCompetizione = (int) ($params['idEdizioneCompetizione'] ?? 0);

        if ($this->partitaContext->isCompetizioneEliminazioneDiretta($idEdizioneCompetizione)) {
            $this->redirectCompetizione($idUniverso, $idEdizione, $idEdizioneCompetizione);
        }

        if (!$this->partitaContext->contestoCompetizioneValido(
            $idUniverso,
            $idEdizione,
            $idEdizioneCompetizione
        )) {
            http_response_code(404);
            echo 'Risorsa non trovata';
            return;
        }

        $this->bloccaSeCompetizioneConclusa(
            $idUniverso,
            $idEdizione,
            $idEdizioneCompetizione
        );

        $partitePerGiornata = $this->partiteQuery->partiteRaggruppatePerGiornata($idEdizioneCompetizione);
        $payloadPartite = $_POST['partite'] ?? [];

        if (!is_array($payloadPartite)) {
            $payloadPartite = [];
        }

        foreach ($partitePerGiornata as $partite) {
            foreach ($partite as $partita) {
                $idPartita = (int) ($partita['ID'] ?? 0);

                if (
                    $idPartita <= 0 ||
                    !isset($payloadPartite[$idPartita]) ||
                    !is_array($payloadPartite[$idPartita])
                ) {
                    continue;
                }

                $goalCasaRaw = $payloadPartite[$idPartita]['goal_casa'] ?? '';
                $goalTrasfertaRaw = $payloadPartite[$idPartita]['goal_trasferta'] ?? '';

                $this->partitaResult->salvaRisultato(
                    $idPartita,
                    $goalCasaRaw,
                    $goalTrasfertaRaw,
                    false
                );
            }
        }

        $this->redirectCompetizione($idUniverso, $idEdizione, $idEdizioneCompetizione);
    }

    public function simulaTutte(Request $request, array $params): void
    {
        $idUniverso = (int) ($params['id'] ?? 0);
        $idEdizione = (int) ($params['idEdizione'] ?? 0);
        $idEdizioneCompetizione = (int) ($params['idEdizioneCompetizione'] ?? 0);

        if ($this->partitaContext->isCompetizioneEliminazioneDiretta($idEdizioneCompetizione)) {
            $this->redirectCompetizione($idUniverso, $idEdizione, $idEdizioneCompetizione);
        }

        if (!$this->partitaContext->contestoCompetizioneValido(
            $idUniverso,
            $idEdizione,
            $idEdizioneCompetizione
        )) {
            http_response_code(404);
            echo 'Risorsa non trovata';
            return;
        }

        $this->bloccaSeCompetizioneConclusa(
            $idUniverso,
            $idEdizione,
            $idEdizioneCompetizione
        );

        $partitePerGiornata = $this->partiteQuery->partiteRaggruppatePerGiornata($idEdizioneCompetizione);

        foreach ($partitePerGiornata as $partite) {
            foreach ($partite as $partita) {
                $idPartita = (int) ($partita['ID'] ?? 0);

                if ($idPartita <= 0) {
                    continue;
                }

                $this->partitaSimulation->simula($idPartita, false);
            }
        }

        $this->redirectCompetizione($idUniverso, $idEdizione, $idEdizioneCompetizione);
    }

    public function resetTutte(Request $request, array $params): void
    {
        $idUniverso = (int) ($params['id'] ?? 0);
        $idEdizione = (int) ($params['idEdizione'] ?? 0);
        $idEdizioneCompetizione = (int) ($params['idEdizioneCompetizione'] ?? 0);

        if ($this->partitaContext->isCompetizioneEliminazioneDiretta($idEdizioneCompetizione)) {
            $this->redirectCompetizione($idUniverso, $idEdizione, $idEdizioneCompetizione);
        }

        if (!$this->partitaContext->contestoCompetizioneValido(
            $idUniverso,
            $idEdizione,
            $idEdizioneCompetizione
        )) {
            http_response_code(404);
            echo 'Risorsa non trovata';
            return;
        }

        $this->bloccaSeCompetizioneConclusa(
            $idUniverso,
            $idEdizione,
            $idEdizioneCompetizione
        );

        $partitePerGiornata = $this->partiteQuery->partiteRaggruppatePerGiornata($idEdizioneCompetizione);

        foreach ($partitePerGiornata as $partite) {
            foreach ($partite as $partita) {
                $idPartita = (int) ($partita['ID'] ?? 0);

                if ($idPartita > 0) {
                    $this->partitaReset->resetta($idPartita);
                }
            }
        }

        $this->redirectCompetizione($idUniverso, $idEdizione, $idEdizioneCompetizione);
    }

    private function redirectCompetizione(
        int $idUniverso,
        int $idEdizione,
        int $idEdizioneCompetizione,
        ?string $anchor = null
    ): void {
        $url =
            '/universi/' .
            $idUniverso .
            '/edizioni/' .
            $idEdizione .
            '/competizioni/' .
            $idEdizioneCompetizione;

        if ($anchor !== null && $anchor !== '') {
            $url .= '#' . rawurlencode($anchor);
        }

        header('Location: ' . $url);
        exit;
    }
}