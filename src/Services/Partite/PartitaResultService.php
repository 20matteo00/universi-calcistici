<?php

declare(strict_types=1);

namespace App\Services\Partite;

use App\Models\Partita;
use App\Services\Partite\PartitaEventGeneratorService;

final class PartitaResultService
{
    private Partita $partite;
    private PartitaEventGeneratorService $eventi;

    public function __construct()
    {
        $this->partite = new Partita();
        $this->eventi = new PartitaEventGeneratorService();
    }

    public function salvaRisultato(
        int $idPartita,
        mixed $goalCasaRaw,
        mixed $goalTrasfertaRaw,
        bool $forzaRigenerazione = true
    ): bool {
        $partita = $this->partite->find($idPartita);

        if (!$partita) {
            return false;
        }

        $goalCasaString = is_string($goalCasaRaw)
            ? trim($goalCasaRaw)
            : (string) $goalCasaRaw;

        $goalTrasfertaString = is_string($goalTrasfertaRaw)
            ? trim($goalTrasfertaRaw)
            : (string) $goalTrasfertaRaw;

        if ($goalCasaString === '' && $goalTrasfertaString === '') {
            return false;
        }

        if ($goalCasaString === '' || $goalTrasfertaString === '') {
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

        $goalCasaAttuale = $partita['GoalCasa'] ?? null;
        $goalTrasfertaAttuale = $partita['GoalTrasferta'] ?? null;

        $goalCasaAttuale = $goalCasaAttuale === null ? null : (int) $goalCasaAttuale;
        $goalTrasfertaAttuale = $goalTrasfertaAttuale === null ? null : (int) $goalTrasfertaAttuale;

        $statoAttuale = (string) ($partita['Stato'] ?? '');

        $risultatoInvariato =
            $statoAttuale === 'giocata' &&
            $goalCasaAttuale === $goalCasa &&
            $goalTrasfertaAttuale === $goalTrasferta;

        if (!$forzaRigenerazione && $risultatoInvariato) {
            return false;
        }

        $this->partite->aggiornaRisultatoPartita(
            $idPartita,
            $goalCasa,
            $goalTrasferta,
            'giocata'
        );

        $this->eventi->rigeneraPerPartita($idPartita);

        return true;
    }
}