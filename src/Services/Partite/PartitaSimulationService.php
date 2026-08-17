<?php

declare(strict_types=1);

namespace App\Services\Partite;

use App\Models\EdizioneCompetizione;
use App\Models\EdizioneGiocatore;
use App\Models\EdizioneSquadra;
use App\Models\Partita;
use App\Services\Partite\PartitaEventGeneratorService;

final class PartitaSimulationService
{
    private Partita $partite;
    private EdizioneCompetizione $edizioneCompetizioni;
    private EdizioneSquadra $edizioneSquadre;
    private EdizioneGiocatore $edizioneGiocatori;
    private PartitaEventGeneratorService $eventi;

    public function __construct()
    {
        $this->partite = new Partita();
        $this->edizioneCompetizioni = new EdizioneCompetizione();
        $this->edizioneSquadre = new EdizioneSquadra();
        $this->edizioneGiocatori = new EdizioneGiocatore();
        $this->eventi = new PartitaEventGeneratorService();
    }

    public function simula(int $idPartita, bool $forza = true): bool
    {
        $partita = $this->partite->find($idPartita);

        if (!$partita) {
            return false;
        }

        $stato = (string) ($partita['Stato'] ?? '');

        if (!$forza && $stato === 'giocata') {
            return false;
        }

        $simulazione = $this->simulaPartita($idPartita);

        if ($simulazione === null) {
            return false;
        }

        $this->eventi->rigeneraPerPartita($idPartita);

        return true;
    }

    public function calcolaPreviewPartita(int $idPartita): ?array
    {
        $contesto = $this->caricaContestoPartita($idPartita);

        if ($contesto === null) {
            return null;
        }

        $forzaCasa = $contesto['forza_casa'];
        $forzaTrasferta = $contesto['forza_trasferta'];

        $differenza = $forzaCasa['rating_globale'] - $forzaTrasferta['rating_globale'];

        return [
            'casa' => $forzaCasa,
            'trasferta' => $forzaTrasferta,
            'differenza' => $differenza,
            'esito_atteso' => $this->etichettaEsitoAtteso($differenza),
        ];
    }

    private function simulaPartita(int $idPartita): ?array
    {
        $contesto = $this->caricaContestoPartita($idPartita);

        if ($contesto === null) {
            return null;
        }

        $forzaCasa = $contesto['forza_casa'];
        $forzaTrasferta = $contesto['forza_trasferta'];

        $attaccoCasa = $forzaCasa['attacco_finale'];
        $difesaCasa = $forzaCasa['difesa_finale'];

        $attaccoTrasferta = $forzaTrasferta['attacco_finale'];
        $difesaTrasferta = $forzaTrasferta['difesa_finale'];

        $differenzaBase = $forzaCasa['rating_globale'] - $forzaTrasferta['rating_globale'];
        $differenzaNormalizzata = $differenzaBase / 160;

        $baseCasa = (($attaccoCasa - $difesaTrasferta) * 0.015);
        $baseTrasferta = (($attaccoTrasferta - $difesaCasa) * 0.015);

        $vantaggioCasa = 0.18;

        $indiceCasa = 1.08 + $vantaggioCasa + $baseCasa + ($differenzaNormalizzata * 0.24);
        $indiceTrasferta = 1.08 + $baseTrasferta - ($differenzaNormalizzata * 0.24);

        $indiceCasa += $this->randomFloat(-0.16, 0.16);
        $indiceTrasferta += $this->randomFloat(-0.16, 0.16);

        $indiceCasa = max(0.25, min(2.40, $indiceCasa));
        $indiceTrasferta = max(0.25, min(2.40, $indiceTrasferta));

        $golCasa = $this->convertiIndiceInGol($indiceCasa);
        $golTrasferta = $this->convertiIndiceInGol($indiceTrasferta);

        if ($differenzaBase > 180 && $golCasa === 0 && $this->percentuale(25)) {
            $golCasa = 1;
        }

        if ($differenzaBase < -180 && $golTrasferta === 0 && $this->percentuale(25)) {
            $golTrasferta = 1;
        }

        if (abs($differenzaBase) < 45 && $this->percentuale(10)) {
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
            'indice_casa' => $indiceCasa,
            'indice_trasferta' => $indiceTrasferta,
        ];
    }

    private function caricaContestoPartita(int $idPartita): ?array
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

        $competizione = $this->edizioneCompetizioni->findEdizioneCompetizione($idEdizioneCompetizione);

        if ($competizione === null) {
            return null;
        }

        $idEdizione = (int) ($competizione['IDEdizione'] ?? 0);

        if ($idEdizione <= 0) {
            return null;
        }

        $squadraCasa = $this->edizioneSquadre->findEdizioneSquadra($idEdizione, $idSquadraCasa);
        $squadraTrasferta = $this->edizioneSquadre->findEdizioneSquadra($idEdizione, $idSquadraTrasferta);

        if ($squadraCasa === null || $squadraTrasferta === null) {
            return null;
        }

        $rosaCasa = $this->edizioneGiocatori->giocatoriAssegnatiASquadra($idEdizione, $idSquadraCasa);
        $rosaTrasferta = $this->edizioneGiocatori->giocatoriAssegnatiASquadra($idEdizione, $idSquadraTrasferta);

        $forzaCasa = $this->calcolaForzaSquadra($squadraCasa, $rosaCasa, true);
        $forzaTrasferta = $this->calcolaForzaSquadra($squadraTrasferta, $rosaTrasferta, false);

        return [
            'partita' => $partita,
            'competizione' => $competizione,
            'id_edizione' => $idEdizione,
            'squadra_casa' => $squadraCasa,
            'squadra_trasferta' => $squadraTrasferta,
            'rosa_casa' => $rosaCasa,
            'rosa_trasferta' => $rosaTrasferta,
            'forza_casa' => $forzaCasa,
            'forza_trasferta' => $forzaTrasferta,
        ];
    }

    private function calcolaForzaSquadra(array $squadraEdizione, array $rosa, bool $inCasa): array
    {
        $valoreSquadra = (float) ($squadraEdizione['Valore'] ?? 0);
        $fattoreCasa = (float) ($squadraEdizione['FattoreCasa'] ?? 0);

        $mediaAttacco = $this->mediaCampo($rosa, 'Attacco');
        $mediaDifesa = $this->mediaCampo($rosa, 'Difesa');

        $bonusCasa = $inCasa ? ($fattoreCasa * 0.08) : 0.0;

        $attaccoFinale = ($valoreSquadra * 0.55) + ($mediaAttacco * 0.45) + $bonusCasa;
        $difesaFinale = ($valoreSquadra * 0.55) + ($mediaDifesa * 0.45);
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

    private function convertiIndiceInGol(float $indice): int
    {
        $base = $indice;
        $base += $this->randomFloat(-0.08, 0.08);

        if ($base < 0.50) {
            return $this->estraiGolPesati([0 => 78, 1 => 17, 2 => 4, 3 => 1]);
        }

        if ($base < 0.85) {
            return $this->estraiGolPesati([0 => 54, 1 => 30, 2 => 11, 3 => 4, 4 => 1]);
        }

        if ($base < 1.20) {
            return $this->estraiGolPesati([0 => 34, 1 => 38, 2 => 18, 3 => 7, 4 => 2, 5 => 1]);
        }

        if ($base < 1.55) {
            return $this->estraiGolPesati([0 => 22, 1 => 36, 2 => 24, 3 => 11, 4 => 5, 5 => 1, 6 => 1]);
        }

        if ($base < 1.95) {
            return $this->estraiGolPesati([0 => 13, 1 => 29, 2 => 27, 3 => 16, 4 => 8, 5 => 4, 6 => 3]);
        }

        return $this->estraiGolPesati([0 => 8, 1 => 21, 2 => 26, 3 => 20, 4 => 12, 5 => 7, 6 => 6]);
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

    private function etichettaEsitoAtteso(float $differenza): string
    {
        if ($differenza >= 180) {
            return 'Casa nettamente favorita';
        }

        if ($differenza >= 85) {
            return 'Casa favorita';
        }

        if ($differenza >= 30) {
            return 'Casa leggermente favorita';
        }

        if ($differenza <= -180) {
            return 'Trasferta nettamente favorita';
        }

        if ($differenza <= -85) {
            return 'Trasferta favorita';
        }

        if ($differenza <= -30) {
            return 'Trasferta leggermente favorita';
        }

        return 'Partita equilibrata';
    }
}