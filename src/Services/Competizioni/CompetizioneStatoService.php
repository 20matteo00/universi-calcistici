<?php

declare(strict_types=1);

namespace App\Services\Competizioni;

use App\Models\EdizioneCompetizione;
use App\Models\PartitaQuery;

final class CompetizioneStatoService
{
    private EdizioneCompetizione $edizioneCompetizioni;
    private PartitaQuery $partiteQuery;
    private CompetizioneEliminazioneDirettaService $eliminazione;
    private CompetizioneClassificaService $classificaService;

    public function __construct()
    {
        $this->edizioneCompetizioni = new EdizioneCompetizione();
        $this->partiteQuery = new PartitaQuery();
        $this->eliminazione = new CompetizioneEliminazioneDirettaService();
        $this->classificaService = new CompetizioneClassificaService();
    }

    public function analizzaChiusura(int $idEdizioneCompetizione): array
    {
        $competizione = $this->edizioneCompetizioni->findEdizioneCompetizione($idEdizioneCompetizione);

        if (!$competizione) {
            return [
                'ok' => false,
                'gia_conclusa' => false,
                'stato' => null,
                'motivi' => ['Competizione non trovata.'],
            ];
        }

        $stato = (string) ($competizione['Stato'] ?? 'in_corso');
        if ($stato === 'conclusa') {
            return [
                'ok' => false,
                'gia_conclusa' => true,
                'stato' => $stato,
                'motivi' => ['La competizione è già conclusa.'],
            ];
        }

        $tipo = mb_strtolower(trim((string) ($competizione['Tipo'] ?? '')));
        $partite = $this->partiteQuery->findByEdizioneCompetizione($idEdizioneCompetizione);

        if ($partite === []) {
            return [
                'ok' => false,
                'gia_conclusa' => false,
                'stato' => $stato,
                'motivi' => ['Nessuna partita generata per questa competizione.'],
            ];
        }

        $motivi = [];

        foreach ($partite as $partita) {
            $statoPartita = mb_strtolower(trim((string) ($partita['Stato'] ?? '')));
            $goalCasa = $partita['GoalCasa'] ?? null;
            $goalTrasferta = $partita['GoalTrasferta'] ?? null;

            if ($statoPartita !== 'giocata' || $goalCasa === null || $goalTrasferta === null) {
                $motivi[] = 'Non tutte le partite risultano completate.';
                break;
            }
        }

        if ($this->isEliminazioneDiretta($tipo)) {
            $haFinale = false;

            foreach ($partite as $partita) {
                if ((string) ($partita['Fase'] ?? '') === 'Finale') {
                    $haFinale = true;
                    break;
                }
            }

            if (!$haFinale) {
                $motivi[] = 'Manca la fase Finale.';
            }

            $analisi = $this->eliminazione->analizzaTurnoCorrente($idEdizioneCompetizione);
            if (($analisi['turno'] ?? null) !== 'Finale') {
                $motivi[] = 'Il tabellone non è ancora arrivato alla finale.';
            }

            if (!empty($analisi['bloccanti'])) {
                $motivi[] = 'La fase finale risulta ancora bloccata o incompleta.';
            }

            $podio = $this->eliminazione->costruisciPodio($idEdizioneCompetizione);
            if ($podio === []) {
                $motivi[] = 'Impossibile costruire il podio finale della competizione.';
            }
        } else {
            $podio = $this->costruisciPodioClassifica($competizione, $idEdizioneCompetizione);
            if ($podio === []) {
                $motivi[] = 'Impossibile costruire la classifica finale della competizione.';
            }
        }

        return [
            'ok' => $motivi === [],
            'gia_conclusa' => false,
            'stato' => $stato,
            'motivi' => $motivi,
        ];
    }

    public function chiudi(int $idEdizioneCompetizione): array
    {
        $analisi = $this->analizzaChiusura($idEdizioneCompetizione);

        if (!(bool) ($analisi['ok'] ?? false)) {
            return $analisi;
        }

        $competizione = $this->edizioneCompetizioni->findEdizioneCompetizione($idEdizioneCompetizione);
        if (!$competizione) {
            return [
                'ok' => false,
                'gia_conclusa' => false,
                'stato' => null,
                'motivi' => ['Competizione non trovata.'],
            ];
        }

        $tipo = mb_strtolower(trim((string) ($competizione['Tipo'] ?? '')));

        if ($this->isEliminazioneDiretta($tipo)) {
            $podio = $this->eliminazione->costruisciPodio($idEdizioneCompetizione);
        } else {
            $podio = $this->costruisciPodioClassifica($competizione, $idEdizioneCompetizione);
        }

        if ($podio === []) {
            return [
                'ok' => false,
                'gia_conclusa' => false,
                'stato' => (string) ($competizione['Stato'] ?? 'in_corso'),
                'motivi' => ['Impossibile salvare il ranking finale della competizione.'],
            ];
        }

        $this->edizioneCompetizioni->aggiornaPodio($idEdizioneCompetizione, $podio);
        $this->edizioneCompetizioni->aggiornaStato($idEdizioneCompetizione, 'conclusa');

        return $analisi + [
            'chiusa' => true,
            'nuovo_stato' => 'conclusa',
            'podio' => $podio,
        ];
    }

    private function costruisciPodioClassifica(array $competizione, int $idEdizioneCompetizione): array
    {
        $idUniverso = (int) ($competizione['IDUniverso'] ?? 0);
        $idEdizione = (int) ($competizione['IDEdizione'] ?? 0);

        if ($idUniverso <= 0 || $idEdizione <= 0) {
            return [];
        }

        $pagina = $this->classificaService->build(
            $idUniverso,
            $idEdizione,
            $idEdizioneCompetizione,
            []
        );

        if ($pagina === null) {
            return [];
        }

        $righe = $pagina['visteClassifica']['generale'] ?? [];
        if (!is_array($righe) || $righe === []) {
            return [];
        }

        $podio = [];

        foreach ($righe as $riga) {
            $posizione = (int) ($riga['Posizione'] ?? 0);
            $idSquadra = (int) ($riga['IDSquadra'] ?? 0);

            if ($posizione <= 0 || $idSquadra <= 0) {
                continue;
            }

            $podio[] = [
                'posizione' => $posizione,
                'id_squadra' => $idSquadra,
                'tipo' => 'univoca',
            ];
        }

        return $podio;
    }

    private function isEliminazioneDiretta(string $tipo): bool
    {
        return in_array($tipo, ['coppa', 'eliminazione_diretta', 'eliminazione diretta'], true);
    }
}
