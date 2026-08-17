<?php

declare(strict_types=1);

namespace App\Services\Partite;

use App\Models\Partita;
use App\Services\Partite\PartitaEventGeneratorService;

final class PartitaResetService
{
    private Partita $partite;
    private PartitaEventGeneratorService $eventi;

    public function __construct()
    {
        $this->partite = new Partita();
        $this->eventi = new PartitaEventGeneratorService();
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