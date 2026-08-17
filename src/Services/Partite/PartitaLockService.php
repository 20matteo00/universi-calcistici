<?php

declare(strict_types=1);

namespace App\Services\Partite;

use App\Models\EdizioneCompetizione;
use App\Services\Competizioni\CompetizioneEliminazioneDirettaService;

final class PartitaLockService
{
    private EdizioneCompetizione $edizioneCompetizioni;
    private CompetizioneEliminazioneDirettaService $eliminazioneDiretta;

    public function __construct()
    {
        $this->edizioneCompetizioni = new EdizioneCompetizione();
        $this->eliminazioneDiretta = new CompetizioneEliminazioneDirettaService();
    }

    public function turnoEliminazioneBloccato(
        int $idEdizioneCompetizione,
        array $partita
    ): bool {
        $fase = trim((string) ($partita['Fase'] ?? ''));

        if ($fase === '') {
            return false;
        }

        $competizione = $this->edizioneCompetizioni->findEdizioneCompetizione($idEdizioneCompetizione);
        $tipoCompetizione = mb_strtolower(trim((string) ($competizione['Tipo'] ?? '')));

        if (
            $tipoCompetizione !== 'eliminazione_diretta' &&
            $tipoCompetizione !== 'eliminazione'
        ) {
            return false;
        }

        return $this->eliminazioneDiretta->faseBloccata($idEdizioneCompetizione, $fase);
    }
}