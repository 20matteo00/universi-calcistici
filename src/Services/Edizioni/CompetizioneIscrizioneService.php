<?php

declare(strict_types=1);

namespace App\Services\Edizioni;

use App\Models\EdizioneCompetizione;

class CompetizioneIscrizioneService
{
    private EdizioneCompetizione $edizioneCompetizioni;

    public function __construct()
    {
        $this->edizioneCompetizioni = new EdizioneCompetizione();
    }

    public function riepilogoDuplicatiCompetizione(
        int $idEdizione,
        int $idEdizioneCompetizioneCorrente,
        array $idsSquadre
    ): array {
        $altre = $this->edizioneCompetizioni->squadreConAltreCompetizioni(
            $idEdizione,
            $idEdizioneCompetizioneCorrente
        );

        $risultato = [];

        foreach ($idsSquadre as $idSquadra) {
            $idSquadra = (int) $idSquadra;

            if (isset($altre[$idSquadra])) {
                $risultato[$idSquadra] = $altre[$idSquadra];
            }
        }

        return $risultato;
    }
}