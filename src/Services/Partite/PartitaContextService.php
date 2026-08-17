<?php

declare(strict_types=1);

namespace App\Services\Partite;

use App\Models\Edizione;
use App\Models\EdizioneCompetizione;
use App\Models\Partita;
use App\Models\Universo;

final class PartitaContextService
{
    private Universo $universi;
    private Edizione $edizioni;
    private EdizioneCompetizione $edizioneCompetizioni;
    private Partita $partite;

    public function __construct()
    {
        $this->universi = new Universo();
        $this->edizioni = new Edizione();
        $this->edizioneCompetizioni = new EdizioneCompetizione();
        $this->partite = new Partita();
    }

    public function contestoCompetizioneValido(
        int $idUniverso,
        int $idEdizione,
        int $idEdizioneCompetizione
    ): bool {
        $universo = $this->universi->find($idUniverso);
        $edizione = $this->edizioni->find($idEdizione);
        $competizione = $this->edizioneCompetizioni->findEdizioneCompetizione($idEdizioneCompetizione);

        if (!$universo || !$edizione || !$competizione) {
            return false;
        }

        if ((int) ($edizione['IDUniverso'] ?? 0) !== $idUniverso) {
            return false;
        }

        if ((int) ($competizione['IDEdizione'] ?? 0) !== $idEdizione) {
            return false;
        }

        return true;
    }

    public function trovaPartitaDellaCompetizione(
        int $idPartita,
        int $idEdizioneCompetizione
    ): ?array {
        $partita = $this->partite->find($idPartita);

        if (
            !$partita ||
            (int) ($partita['IDEdizioneCompetizione'] ?? 0) !== $idEdizioneCompetizione
        ) {
            return null;
        }

        return $partita;
    }

    public function anchorDaPartita(array $partita): string
    {
        $fase = $partita['Fase'] ?? null;
        $giornata = (int) ($partita['Giornata'] ?? 0);

        if ($fase === null || $fase === '') {
            return 'giornata-' . $giornata;
        }

        return 'fase-' . $this->slug((string) $fase) . '-giornata-' . $giornata;
    }

    public function anchorDaFaseEGiornata(string $fase, int $giornata): string
    {
        return 'fase-' . $this->slug($fase) . '-giornata-' . $giornata;
    }

    public function isCompetizioneEliminazioneDiretta(int $idEdizioneCompetizione): bool
    {
        $competizione = $this->edizioneCompetizioni->findEdizioneCompetizione($idEdizioneCompetizione);
        $tipoCompetizione = mb_strtolower(trim((string) ($competizione['Tipo'] ?? '')));

        return $tipoCompetizione === 'eliminazione_diretta'
            || $tipoCompetizione === 'eliminazione';
    }

    public function slug(string $testo): string
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