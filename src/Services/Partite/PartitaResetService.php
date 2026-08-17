<?php

declare(strict_types=1);

namespace App\Services\Partite;

use App\Models\Partita;
use App\Services\EventGeneratorService;

final class PartitaResetService
{
    private Partita $partite;
    private EventGeneratorService $eventi;

    public function __construct()
    {
        $this->partite = new Partita();
        $this->eventi = new EventGeneratorService();
    }

    public function resetta(int $idPartita): bool
    {
        $partita = $this->partite->find($idPartita);

        if (!$partita) {
            return false;
        }

        $this->partite->resetRisultatoPartita($idPartita);
        $this->eventi->cancellaPerPartita($idPartita);

        return true;
    }
}