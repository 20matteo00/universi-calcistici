<?php

declare(strict_types=1);

namespace App\Services\Competizioni;

use App\Models\Edizione;
use App\Models\EdizioneCompetizione;
use App\Models\PartitaEvento;
use App\Models\PartitaQuery;
use App\Models\Universo;
use App\Services\Competizioni\CompetizioneEliminazioneDirettaService;
use App\Services\Partite\PartitaSimulationService;

final class CompetizioneShowService
{
    private Universo $universi;
    private Edizione $edizioni;
    private EdizioneCompetizione $edizioneCompetizioni;
    private PartitaQuery $partiteQuery;
    private PartitaEvento $partitaEventi;
    private PartitaSimulationService $simulazione;
    private CompetizioneEliminazioneDirettaService $eliminazioneDiretta;

    public function __construct()
    {
        $this->universi = new Universo();
        $this->edizioni = new Edizione();
        $this->edizioneCompetizioni = new EdizioneCompetizione();
        $this->partiteQuery = new PartitaQuery();
        $this->partitaEventi = new PartitaEvento();
        $this->simulazione = new PartitaSimulationService();
        $this->eliminazioneDiretta = new CompetizioneEliminazioneDirettaService();
    }

    public function build(
        int $idUniverso,
        int $idEdizione,
        int $idEdizioneCompetizione
    ): ?array {
        $universo = $this->universi->find($idUniverso);
        $edizione = $this->edizioni->find($idEdizione);
        $competizione = $this->edizioneCompetizioni->findEdizioneCompetizione($idEdizioneCompetizione);

        if (!$universo || !$edizione || !$competizione) {
            return null;
        }

        if ((int) ($edizione['IDUniverso'] ?? 0) !== $idUniverso) {
            return null;
        }

        if ((int) ($competizione['IDEdizione'] ?? 0) !== $idEdizione) {
            return null;
        }

        $tipoCompetizione = mb_strtolower(trim((string) ($competizione['Tipo'] ?? '')));

        $fasiBloccate = [];
        $statoEliminazione = [
            'ok' => true,
            'bloccanti' => [],
            'vincitori' => [],
            'perdenti' => [],
            'turno' => null,
            'turno_label' => null,
            'finale_secca' => true,
            'finale_terzo_posto' => false,
        ];

        if ($tipoCompetizione === 'eliminazione_diretta') {
            $blocchiPartite = $this->partiteQuery->partiteRaggruppatePerFaseEGiornata($idEdizioneCompetizione);
            $statoEliminazione = $this->eliminazioneDiretta->analizzaTurnoCorrente($idEdizioneCompetizione);
            $fasiBloccate = $this->eliminazioneDiretta->mappaFasiBloccate($idEdizioneCompetizione);
        } else {
            $partitePerGiornata = $this->partiteQuery->partiteRaggruppatePerGiornata($idEdizioneCompetizione);
            $blocchiPartite = [];

            foreach ($partitePerGiornata as $giornata => $partite) {
                $chiave = 'giornata-' . (int) $giornata;

                $blocchiPartite[$chiave] = [
                    'chiave' => $chiave,
                    'anchor' => 'giornata-' . (int) $giornata,
                    'fase' => null,
                    'giornata' => (int) $giornata,
                    'titolo' => 'Giornata ' . (int) $giornata,
                    'partite' => $partite,
                ];
            }
        }

        $blocchiPartite = $this->arricchisciBlocchi($blocchiPartite);

        return [
            'universo' => $universo,
            'edizione' => $edizione,
            'competizione' => $competizione,
            'tipoCompetizione' => $tipoCompetizione,
            'blocchiPartite' => $blocchiPartite,
            'fasiBloccate' => $fasiBloccate,
            'statoEliminazione' => $statoEliminazione,
        ];
    }

    private function arricchisciBlocchi(array $blocchiPartite): array
    {
        foreach ($blocchiPartite as $chiave => $blocco) {
            foreach ($blocco['partite'] as $indice => $partita) {
                $idPartita = (int) ($partita['ID'] ?? 0);

                $blocchiPartite[$chiave]['partite'][$indice]['PreviewSimulazione'] =
                    $this->simulazione->calcolaPreviewPartita($idPartita);

                $blocchiPartite[$chiave]['partite'][$indice]['Eventi'] =
                    $this->partitaEventi->findByPartita($idPartita);
            }
        }

        return $blocchiPartite;
    }
}