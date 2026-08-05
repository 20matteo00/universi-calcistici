<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Edizione;
use App\Models\Partita;

class SimulazioneService
{
    private Partita $partite;
    private Edizione $edizioni;

    public function __construct()
    {
        $this->partite = new Partita();
        $this->edizioni = new Edizione();
    }

    public function simulaPartita(int $idPartita): ?array
    {
        $partita = $this->partite->find($idPartita);

        if ($partita === null) {
            return null;
        }

        $idEdizioneCompetizione = (int) ($partita['IDEdizioneCompetizione'] ?? 0);
        $idSquadraCasa = (int) ($partita['IDSquadraCasa'] ?? 0);
        $idSquadraTrasferta = (int) ($partita['IDSquadraTrasferta'] ?? 0);

        if ($idEdizioneCompetizione <= 0 || $idSquadraCasa <= 0 || $idSquadraTrasferta <= 0) {
            return null;
        }

        $competizione = $this->edizioni->findEdizioneCompetizione($idEdizioneCompetizione);

        if ($competizione === null) {
            return null;
        }

        $idEdizione = (int) ($competizione['IDEdizione'] ?? 0);

        if ($idEdizione <= 0) {
            return null;
        }

        $squadraCasa = $this->edizioni->findEdizioneSquadra($idEdizione, $idSquadraCasa);
        $squadraTrasferta = $this->edizioni->findEdizioneSquadra($idEdizione, $idSquadraTrasferta);

        if ($squadraCasa === null || $squadraTrasferta === null) {
            return null;
        }

        $rosaCasa = $this->edizioni->giocatoriAssegnatiASquadra($idEdizione, $idSquadraCasa);
        $rosaTrasferta = $this->edizioni->giocatoriAssegnatiASquadra($idEdizione, $idSquadraTrasferta);

        $forzaCasa = $this->calcolaForzaSquadra($squadraCasa, $rosaCasa, true);
        $forzaTrasferta = $this->calcolaForzaSquadra($squadraTrasferta, $rosaTrasferta, false);

        $attaccoCasa = $forzaCasa['attacco_finale'];
        $difesaCasa = $forzaCasa['difesa_finale'];

        $attaccoTrasferta = $forzaTrasferta['attacco_finale'];
        $difesaTrasferta = $forzaTrasferta['difesa_finale'];

        $differenzaBase = $forzaCasa['rating_globale'] - $forzaTrasferta['rating_globale'];
        $differenzaNormalizzata = $differenzaBase / 120;

        $baseCasa = (($attaccoCasa - $difesaTrasferta) * 0.018);
        $baseTrasferta = (($attaccoTrasferta - $difesaCasa) * 0.018);

        $indiceCasa = 1.20 + $baseCasa + ($differenzaNormalizzata * 0.32);
        $indiceTrasferta = 1.00 + $baseTrasferta - ($differenzaNormalizzata * 0.28);

        $indiceCasa += $this->randomFloat(-0.18, 0.18);
        $indiceTrasferta += $this->randomFloat(-0.18, 0.18);

        $indiceCasa = max(0.20, min(2.80, $indiceCasa));
        $indiceTrasferta = max(0.20, min(2.60, $indiceTrasferta));

        $golCasa = $this->convertiIndiceInGol($indiceCasa, true);
        $golTrasferta = $this->convertiIndiceInGol($indiceTrasferta, false);

        if ($differenzaBase > 140 && $golCasa === 0 && $this->percentuale(45)) {
            $golCasa = 1;
        }

        if ($differenzaBase < -140 && $golTrasferta === 0 && $this->percentuale(45)) {
            $golTrasferta = 1;
        }

        if (abs($differenzaBase) < 60 && $this->percentuale(12)) {
            if ($this->percentuale(50)) {
                $golCasa++;
            } else {
                $golTrasferta++;
            }
        }

        $golCasa = max(0, min(6, $golCasa));
        $golTrasferta = max(0, min(6, $golTrasferta));

        $this->partite->aggiornaRisultatoPartita($idPartita, $golCasa, $golTrasferta, 'giocata');

        return [
            'goal_casa' => $golCasa,
            'goal_trasferta' => $golTrasferta,
            'forza_casa' => $forzaCasa,
            'forza_trasferta' => $forzaTrasferta,
            'differenza_base' => $differenzaBase,
        ];
    }

    private function calcolaForzaSquadra(array $squadraEdizione, array $rosa, bool $inCasa): array
    {
        $valoreSquadra = (float) ($squadraEdizione['Valore'] ?? 0);
        $fattoreCasa = (float) ($squadraEdizione['FattoreCasa'] ?? 0);

        $mediaAttacco = $this->mediaCampo($rosa, 'Attacco');
        $mediaDifesa = $this->mediaCampo($rosa, 'Difesa');

        $bonusCasa = $inCasa ? ($fattoreCasa * 0.35) : 0.0;

        $attaccoFinale = ($valoreSquadra * 0.55) + ($mediaAttacco * 0.45) + $bonusCasa;
        $difesaFinale = ($valoreSquadra * 0.55) + ($mediaDifesa * 0.45) + ($bonusCasa * 0.35);
        $ratingGlobale = ($valoreSquadra * 0.50) + ($mediaAttacco * 0.25) + ($mediaDifesa * 0.25) + $bonusCasa;

        return [
            'valore_squadra' => $valoreSquadra,
            'fattore_casa' => $fattoreCasa,
            'bonus_casa' => $bonusCasa,
            'media_attacco' => $mediaAttacco,
            'media_difesa' => $mediaDifesa,
            'attacco_finale' => $attaccoFinale,
            'difesa_finale' => $difesaFinale,
            'rating_globale' => $ratingGlobale,
        ];
    }

    private function mediaCampo(array $righe, string $campo): float
    {
        if ($righe === []) {
            return 0.0;
        }

        $somma = 0.0;
        $conteggio = 0;

        foreach ($righe as $riga) {
            $somma += (float) ($riga[$campo] ?? 0);
            $conteggio++;
        }

        if ($conteggio === 0) {
            return 0.0;
        }

        return $somma / $conteggio;
    }

    private function convertiIndiceInGol(float $indice, bool $isCasa): int
    {
        $base = $indice;

        if ($isCasa) {
            $base += 0.08;
        }

        $base += $this->randomFloat(-0.10, 0.10);

        if ($base < 0.55) {
            return $this->estraiGolPesati([0 => 74, 1 => 20, 2 => 5, 3 => 1]);
        }

        if ($base < 0.90) {
            return $this->estraiGolPesati([0 => 48, 1 => 34, 2 => 13, 3 => 4, 4 => 1]);
        }

        if ($base < 1.25) {
            return $this->estraiGolPesati([0 => 30, 1 => 39, 2 => 20, 3 => 8, 4 => 2, 5 => 1]);
        }

        if ($base < 1.65) {
            return $this->estraiGolPesati([0 => 18, 1 => 35, 2 => 26, 3 => 13, 4 => 5, 5 => 2, 6 => 1]);
        }

        if ($base < 2.05) {
            return $this->estraiGolPesati([0 => 10, 1 => 27, 2 => 29, 3 => 18, 4 => 9, 5 => 4, 6 => 3]);
        }

        return $this->estraiGolPesati([0 => 6, 1 => 18, 2 => 26, 3 => 22, 4 => 13, 5 => 9, 6 => 6]);
    }

    private function estraiGolPesati(array $pesi): int
    {
        $totale = array_sum($pesi);
        $tiro = random_int(1, $totale);
        $progressivo = 0;

        foreach ($pesi as $gol => $peso) {
            $progressivo += $peso;
            if ($tiro <= $progressivo) {
                return (int) $gol;
            }
        }

        return 0;
    }

    private function percentuale(int $valore): bool
    {
        return random_int(1, 100) <= $valore;
    }

    private function randomFloat(float $min, float $max): float
    {
        $numero = mt_rand() / mt_getrandmax();
        return $min + (($max - $min) * $numero);
    }

    public function calcolaPreviewPartita(int $idPartita): ?array
    {
        $partita = $this->partite->find($idPartita);

        if ($partita === null) {
            return null;
        }

        $idEdizioneCompetizione = (int) ($partita['IDEdizioneCompetizione'] ?? 0);
        $idSquadraCasa = (int) ($partita['IDSquadraCasa'] ?? 0);
        $idSquadraTrasferta = (int) ($partita['IDSquadraTrasferta'] ?? 0);

        if ($idEdizioneCompetizione <= 0 || $idSquadraCasa <= 0 || $idSquadraTrasferta <= 0) {
            return null;
        }

        $competizione = $this->edizioni->findEdizioneCompetizione($idEdizioneCompetizione);

        if ($competizione === null) {
            return null;
        }

        $idEdizione = (int) ($competizione['IDEdizione'] ?? 0);

        if ($idEdizione <= 0) {
            return null;
        }

        $squadraCasa = $this->edizioni->findEdizioneSquadra($idEdizione, $idSquadraCasa);
        $squadraTrasferta = $this->edizioni->findEdizioneSquadra($idEdizione, $idSquadraTrasferta);

        if ($squadraCasa === null || $squadraTrasferta === null) {
            return null;
        }

        $rosaCasa = $this->edizioni->giocatoriAssegnatiASquadra($idEdizione, $idSquadraCasa);
        $rosaTrasferta = $this->edizioni->giocatoriAssegnatiASquadra($idEdizione, $idSquadraTrasferta);

        $forzaCasa = $this->calcolaForzaSquadra($squadraCasa, $rosaCasa, true);
        $forzaTrasferta = $this->calcolaForzaSquadra($squadraTrasferta, $rosaTrasferta, false);

        $differenza = $forzaCasa['rating_globale'] - $forzaTrasferta['rating_globale'];

        return [
            'casa' => $forzaCasa,
            'trasferta' => $forzaTrasferta,
            'differenza' => $differenza,
            'esito_atteso' => $this->etichettaEsitoAtteso($differenza),
        ];
    }

    private function etichettaEsitoAtteso(float $differenza): string
    {
        if ($differenza >= 160) {
            return 'Casa nettamente favorita';
        }

        if ($differenza >= 70) {
            return 'Casa favorita';
        }

        if ($differenza >= 25) {
            return 'Casa leggermente favorita';
        }

        if ($differenza <= -160) {
            return 'Trasferta nettamente favorita';
        }

        if ($differenza <= -70) {
            return 'Trasferta favorita';
        }

        if ($differenza <= -25) {
            return 'Trasferta leggermente favorita';
        }

        return 'Partita equilibrata';
    }
}