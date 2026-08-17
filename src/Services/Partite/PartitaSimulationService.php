<?php

declare(strict_types=1);

namespace App\Services\Partite;

use App\Models\Partita;
use App\Services\EventGeneratorService;
use App\Services\SimulazioneService;

final class PartitaSimulationService
{
    private Partita $partite;
    private SimulazioneService $simulazione;
    private EventGeneratorService $eventi;

    public function __construct()
    {
        $this->partite = new Partita();
        $this->simulazione = new SimulazioneService();
        $this->eventi = new EventGeneratorService();
    }

    public function simula(int $idPartita, bool $forza = true): bool
    {
        $partita = $this->partite->find($idPartita);

        if (!$partita) {
            return false;
        }

        $stato = (string) ($partita['Stato'] ?? '');

        if (!$forza && $stato === 'giocata') {
            return false;
        }

        $this->simulazione->simulaPartita($idPartita);
        $this->eventi->rigeneraPerPartita($idPartita);

        return true;
    }
}