<?php

declare(strict_types=1);

namespace App\Services\Competizioni;

use App\Models\Edizione;
use App\Models\EdizioneCompetizione;
use App\Models\PartitaQuery;
use App\Models\Universo;

final class CompetizioneClassificaService
{
    private Universo $universi;
    private Edizione $edizioni;
    private EdizioneCompetizione $edizioneCompetizioni;
    private PartitaQuery $partiteQuery;
    private CompetizioneClassificaCalculator $classificaCalculator;

    public function __construct()
    {
        $this->universi = new Universo();
        $this->edizioni = new Edizione();
        $this->edizioneCompetizioni = new EdizioneCompetizione();
        $this->partiteQuery = new PartitaQuery();
        $this->classificaCalculator = new CompetizioneClassificaCalculator();
    }

    public function build(
        int $idUniverso,
        int $idEdizione,
        int $idEdizioneCompetizione,
        array $query = []
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

        $giornate = $this->partiteQuery->giornatePerCompetizione($idEdizioneCompetizione);
        if ($giornate === []) {
            $giornate = [1];
        }

        $giornataMin = (int) min($giornate);
        $giornataMax = (int) max($giornate);

        $giornataDa = (int) ($query['giornata_da'] ?? $giornataMin);
        $giornataA = (int) ($query['giornata_a'] ?? $giornataMax);
        $sezioneAttiva = (string) ($query['sezione'] ?? 'squadre');
        $tabAttiva = (string) ($query['tab'] ?? 'generale');
        $tabGiocatoriAttiva = (string) ($query['tab_giocatori'] ?? 'marcatori');

        if ($giornataDa < $giornataMin) {
            $giornataDa = $giornataMin;
        }

        if ($giornataA > $giornataMax) {
            $giornataA = $giornataMax;
        }

        if ($giornataDa > $giornataA) {
            [$giornataDa, $giornataA] = [$giornataA, $giornataDa];
        }

        if (!in_array($sezioneAttiva, ['squadre', 'giocatori'], true)) {
            $sezioneAttiva = 'squadre';
        }

        $struttura = json_decode((string) ($competizione['Struttura'] ?? '{}'), true);
        if (!is_array($struttura)) {
            $struttura = [];
        }

        $numeroGiri = max(1, (int) ($competizione['Giri'] ?? 1));

        $visteClassifica = $this->classificaCalculator->calcolaVisteCompetizione(
            $idEdizioneCompetizione,
            $giornataDa,
            $giornataA,
            $struttura,
            $numeroGiri
        );

        $tabsSquadreDisponibili = [
            'generale' => 'Generale',
            'casa' => 'Casa',
            'trasferta' => 'Trasferta',
        ];

        if ($numeroGiri > 1) {
            for ($i = 1; $i <= $numeroGiri; $i++) {
                $tabsSquadreDisponibili['giro_' . $i] = 'Giro ' . $i;
            }
        }

        $tabsSquadre = [];
        foreach ($tabsSquadreDisponibili as $chiave => $label) {
            if (array_key_exists($chiave, $visteClassifica)) {
                $tabsSquadre[$chiave] = $label;
            }
        }

        if (!isset($tabsSquadre[$tabAttiva])) {
            $tabAttiva = array_key_first($tabsSquadre) ?? 'generale';
        }

        $righeSquadre = $visteClassifica[$tabAttiva] ?? [];

        $statisticheGiocatori = $this->classificaCalculator->calcolaStatisticheGiocatori(
            $idEdizioneCompetizione,
            $giornataDa,
            $giornataA
        );

        $tabsGiocatori = [
            'marcatori' => 'Marcatori',
            'assist' => 'Assist',
            'disciplina' => 'Disciplina',
            'eventi' => 'Eventi',
        ];

        if (!isset($tabsGiocatori[$tabGiocatoriAttiva])) {
            $tabGiocatoriAttiva = 'marcatori';
        }

        $righeGiocatori = $statisticheGiocatori[$tabGiocatoriAttiva] ?? [];

        $tabellaCapolista = $this->classificaCalculator->calcolaTabellaCapolista(
            $idEdizioneCompetizione,
            $struttura
        );

        $segmentiCapolista = $this->calcolaSegmentiCapolista($tabellaCapolista);

        return [
            'universo' => $universo,
            'edizione' => $edizione,
            'competizione' => $competizione,
            'nomeCompetizione' => $this->nomeCompetizione($competizione),
            'giornate' => $giornate,
            'giornataMin' => $giornataMin,
            'giornataMax' => $giornataMax,
            'giornataDa' => $giornataDa,
            'giornataA' => $giornataA,
            'sezioneAttiva' => $sezioneAttiva,
            'tabAttiva' => $tabAttiva,
            'tabGiocatoriAttiva' => $tabGiocatoriAttiva,
            'struttura' => $struttura,
            'numeroGiri' => $numeroGiri,
            'visteClassifica' => $visteClassifica,
            'tabsSquadre' => $tabsSquadre,
            'tabsGiocatori' => $tabsGiocatori,
            'righeSquadre' => $righeSquadre,
            'righeGiocatori' => $righeGiocatori,
            'tabellaCapolista' => $tabellaCapolista,
            'segmentiCapolista' => $segmentiCapolista,
            'statisticheGiocatori' => $statisticheGiocatori,
        ];
    }

    private function nomeCompetizione(array $competizione): string
    {
        return (string) (
            $competizione['NomeCompetizione']
            ?? $competizione['Nome']
            ?? $competizione['Titolo']
            ?? 'Competizione'
        );
    }

    private function calcolaSegmentiCapolista(array $tabellaCapolista): array
    {
        $segmentiCapolista = [];

        if ($tabellaCapolista === []) {
            return $segmentiCapolista;
        }

        $corrente = null;

        foreach ($tabellaCapolista as $rigaCapo) {
            $label = (string) (($rigaCapo['NomeBreve'] ?? '') ?: ($rigaCapo['Capolista'] ?? '-'));
            $idSquadra = $rigaCapo['IDSquadra'] ?? null;
            $giornata = (int) ($rigaCapo['Giornata'] ?? 0);
            $pariInTesta = (bool) ($rigaCapo['PariInTesta'] ?? false);
            $colori = $rigaCapo['Colori'] ?? [];

            $chiave = $pariInTesta ? 'pari' : ('squadra_' . (string) $idSquadra);

            if ($corrente === null) {
                $corrente = [
                    'chiave' => $chiave,
                    'label' => $label,
                    'giornata_inizio' => $giornata,
                    'giornata_fine' => $giornata,
                    'colspan' => 1,
                    'pari' => $pariInTesta,
                    'colori' => $colori,
                ];
                continue;
            }

            if ($corrente['chiave'] === $chiave) {
                $corrente['giornata_fine'] = $giornata;
                $corrente['colspan']++;
                continue;
            }

            $segmentiCapolista[] = $corrente;

            $corrente = [
                'chiave' => $chiave,
                'label' => $label,
                'giornata_inizio' => $giornata,
                'giornata_fine' => $giornata,
                'colspan' => 1,
                'pari' => $pariInTesta,
                'colori' => $colori,
            ];
        }

        if ($corrente !== null) {
            $segmentiCapolista[] = $corrente;
        }

        return $segmentiCapolista;
    }
}