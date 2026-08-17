<?php

declare(strict_types=1);

namespace App\Services\Edizioni;

use App\Config\Database;
use App\Models\EdizioneCompetizione;
use App\Models\EdizioneGiocatore;
use App\Models\EdizioneSquadra;
use App\Services\Competizioni\CompetizioneCalendarioService;

final class EdizioneFinalizeService
{
    public function __construct(
        private readonly EdizioneSquadra $edizioneSquadre = new EdizioneSquadra(),
        private readonly EdizioneCompetizione $edizioneCompetizioni = new EdizioneCompetizione(),
        private readonly EdizioneGiocatore $edizioneGiocatori = new EdizioneGiocatore(),
        private readonly RosaValidatorService $rosaValidatorService = new RosaValidatorService(),
        private readonly CompetizioneCalendarioService $calendarioService = new CompetizioneCalendarioService(),
    ) {
    }

    public function verificaFinalizzazione(int $idEdizione): array
    {
        $haGiocatoriEdizione = $this->edizioneGiocatori->haGiocatoriEdizione($idEdizione);
        $roseComplete = $haGiocatoriEdizione
            ? $this->rosaValidatorService->tutteLeRoseComplete($idEdizione)
            : true;

        $squadreEdizione = $this->edizioneSquadre->squadreEdizione($idEdizione);
        $competizioni = $this->edizioneCompetizioni->competizioniEdizione($idEdizione);

        $squadreCoperte = [];
        $competizioniComplete = true;
        $messaggioErroreCompetizioni = null;

        foreach ($competizioni as $competizione) {
            if (!empty($competizione['InizialmenteVuota'])) {
                continue;
            }

            $idEdizioneCompetizione = (int) ($competizione['ID'] ?? 0);
            $nomeCompetizione = (string) ($competizione['NomeCompetizione'] ?? 'Competizione');
            $numeroAtteso = (int) ($competizione['NumeroPartecipanti'] ?? 0);

            $squadreIscritte = $this->edizioneCompetizioni->squadreIscritteACompetizione($idEdizioneCompetizione);
            $idsSquadreIscritte = array_values(array_filter(
                array_map(fn(array $squadra): int => (int) ($squadra['IDSquadra'] ?? 0), $squadreIscritte),
                fn(int $id): bool => $id > 0
            ));

            foreach ($idsSquadreIscritte as $idSquadra) {
                $squadreCoperte[$idSquadra] = true;
            }

            if (count($idsSquadreIscritte) !== $numeroAtteso) {
                $competizioniComplete = false;
                $messaggioErroreCompetizioni = 'La competizione "' . $nomeCompetizione . '" deve avere esattamente ' . $numeroAtteso . ' squadre, attualmente ne ha ' . count($idsSquadreIscritte) . '.';
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
        $ok = $roseComplete && $competizioniComplete && $coperturaFinalizzabili;

        return [
            'ok' => $ok,
            'ha_giocatori_edizione' => $haGiocatoriEdizione,
            'rose_complete' => $roseComplete,
            'competizioni_complete' => $competizioniComplete,
            'copertura_finalizzabili' => $coperturaFinalizzabili,
            'messaggio' => $ok
                ? null
                : (!$roseComplete
                    ? 'Non puoi finalizzare: rose incomplete.'
                    : (!$competizioniComplete
                        ? 'Non puoi finalizzare: ' . $messaggioErroreCompetizioni
                        : 'Non puoi finalizzare: configurazione competizioni non valida.')),
        ];
    }

    public function finalizza(int $idEdizione): array
    {
        $verifica = $this->verificaFinalizzazione($idEdizione);

        if (!(bool) ($verifica['ok'] ?? false)) {
            return $verifica;
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

            $stmt->execute(['idEdizione' => $idEdizione]);
            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            return [
                'ok' => false,
                'messaggio' => 'Errore durante la finalizzazione dell\'edizione: ' . $e->getMessage(),
            ];
        }

        return [
            'ok' => true,
            'messaggio' => null,
        ];
    }

    private function calcolaCoperturaCompetizioniFinalizzabili(int $idEdizione): bool
    {
        $squadreEdizione = $this->edizioneSquadre->squadreEdizione($idEdizione);
        $competizioni = $this->edizioneCompetizioni->competizioniEdizione($idEdizione);

        $squadreCoperte = [];

        foreach ($competizioni as $competizione) {
            if (!empty($competizione['InizialmenteVuota'])) {
                continue;
            }

            $idEdizioneCompetizione = (int) ($competizione['ID'] ?? 0);
            $squadreIscritte = $this->edizioneCompetizioni->squadreIscritteACompetizione($idEdizioneCompetizione);

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
}