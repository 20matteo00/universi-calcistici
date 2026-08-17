<?php

declare(strict_types=1);

namespace App\Services\Edizioni;

use App\Models\EdizioneGiocatore;
use App\Models\EdizioneSquadra;

class RosaValidatorService
{
    private EdizioneGiocatore $edizioneGiocatori;
    private EdizioneSquadra $edizioneSquadre;

    public function __construct()
    {
        $this->edizioneGiocatori = new EdizioneGiocatore();
        $this->edizioneSquadre = new EdizioneSquadra();
    }

    public function verificaRosaSquadra(int $idEdizione, int $idSquadra): array
    {
        $giocatori = $this->edizioneGiocatori->giocatoriAssegnatiASquadra($idEdizione, $idSquadra);

        $conteggi = [
            'totale' => count($giocatori),
            'POR' => 0,
            'difensivi' => 0,
            'centrocampo' => 0,
            'offensivi' => 0,
        ];

        foreach ($giocatori as $giocatore) {
            $posizione = strtoupper(trim((string) ($giocatore['Posizione'] ?? '')));

            if ($posizione === 'POR') {
                $conteggi['POR']++;
            }

            if (in_array($posizione, ['TD', 'TS', 'DC'], true)) {
                $conteggi['difensivi']++;
            }

            if (in_array($posizione, ['CC', 'MED', 'CS', 'CD', 'TRQ'], true)) {
                $conteggi['centrocampo']++;
            }

            if (in_array($posizione, ['AS', 'AD', 'ATT'], true)) {
                $conteggi['offensivi']++;
            }
        }

        $richiesti = [
            'totale' => 18,
            'POR' => 2,
            'difensivi' => 5,
            'centrocampo' => 6,
            'offensivi' => 5,
        ];

        $mancanze = [
            'totale' => max(0, $richiesti['totale'] - $conteggi['totale']),
            'POR' => max(0, $richiesti['POR'] - $conteggi['POR']),
            'difensivi' => max(0, $richiesti['difensivi'] - $conteggi['difensivi']),
            'centrocampo' => max(0, $richiesti['centrocampo'] - $conteggi['centrocampo']),
            'offensivi' => max(0, $richiesti['offensivi'] - $conteggi['offensivi']),
        ];

        $ok = $mancanze['totale'] === 0
            && $mancanze['POR'] === 0
            && $mancanze['difensivi'] === 0
            && $mancanze['centrocampo'] === 0
            && $mancanze['offensivi'] === 0;

        return [
            'ok' => $ok,
            'conteggi' => $conteggi,
            'richiesti' => $richiesti,
            'mancanze' => $mancanze,
        ];
    }

    public function tutteLeRoseComplete(int $idEdizione): bool
    {
        $squadre = $this->edizioneSquadre->squadreEdizione($idEdizione);

        foreach ($squadre as $squadra) {
            $verifica = $this->verificaRosaSquadra($idEdizione, (int) ($squadra['IDSquadra'] ?? 0));

            if (!(bool) ($verifica['ok'] ?? false)) {
                return false;
            }
        }

        return true;
    }
}