<?php

declare(strict_types=1);

namespace App\Services\Edizioni;

use App\Models\Edizione;
use App\Models\EdizioneCompetizione;
use App\Models\Universo;

final class EdizioneContextService
{
    private Universo $universi;
    private Edizione $edizioni;
    private EdizioneCompetizione $edizioneCompetizioni;

    public function __construct()
    {
        $this->universi = new Universo();
        $this->edizioni = new Edizione();
        $this->edizioneCompetizioni = new EdizioneCompetizione();
    }

    public function requireUniversoEdizione(int $idUniverso, int $idEdizione): ?array
    {
        $universo = $this->universi->find($idUniverso);
        if ($universo === null) {
            return null;
        }

        $edizione = $this->edizioni->find($idEdizione);
        if ($edizione === null || (int) ($edizione['IDUniverso'] ?? 0) !== $idUniverso) {
            return null;
        }

        return [
            'universo' => $universo,
            'edizione' => $edizione,
        ];
    }

    public function requireCompetizione(int $idUniverso, int $idEdizione, int $idEdizioneCompetizione): ?array
    {
        $base = $this->requireUniversoEdizione($idUniverso, $idEdizione);
        if ($base === null) {
            return null;
        }

        $competizione = $this->edizioneCompetizioni->findEdizioneCompetizione($idEdizioneCompetizione);
        if ($competizione === null || (int) ($competizione['IDEdizione'] ?? 0) !== $idEdizione) {
            return null;
        }

        return $base + [
            'competizione' => $competizione,
        ];
    }
}