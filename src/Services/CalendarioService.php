<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Edizione;

class CalendarioService
{
    private Edizione $edizioni;

    public function __construct()
    {
        $this->edizioni = new Edizione();
    }

    public function generaPerEdizione(int $idEdizione): void
    {
        $competizioni = $this->edizioni->competizioniEdizione($idEdizione);

        foreach ($competizioni as $competizione) {
            $idEdizioneCompetizione = (int) ($competizione['ID'] ?? 0);
            $tipo = (string) ($competizione['Tipo'] ?? '');

            if ($this->edizioni->contaPartitePerCompetizione($idEdizioneCompetizione) > 0) {
                continue;
            }

            if ($tipo !== 'campionato') {
                continue;
            }

            $this->generaCampionato($competizione);
        }
    }

    private function generaCampionato(array $competizione): void
    {
        $idEdizioneCompetizione = (int) ($competizione['ID'] ?? 0);
        $strutturaJson = (string) ($competizione['Struttura'] ?? '{}');
        $struttura = json_decode($strutturaJson, true);

        if (!is_array($struttura)) {
            $struttura = [];
        }

        $giri = max(1, (int) ($struttura['giri'] ?? 1));

        $squadreIscritte = $this->edizioni->squadreIscritteACompetizione($idEdizioneCompetizione);
        $idsSquadre = array_map(
            fn(array $squadra): int => (int) ($squadra['IDSquadra'] ?? 0),
            $squadreIscritte
        );

        $idsSquadre = array_values(array_filter($idsSquadre, fn(int $id): bool => $id > 0));

        $numeroSquadre = count($idsSquadre);

        if ($numeroSquadre < 2) {
            throw new \RuntimeException('Competizione con meno di 2 squadre.');
        }

        if ($numeroSquadre % 2 !== 0) {
            throw new \RuntimeException('Il campionato base supporta per ora solo un numero pari di squadre.');
        }

        $giornateBase = $this->generaRoundRobinBase($idsSquadre);
        $partite = [];

        foreach ($giornateBase as $indiceGiornata => $accoppiamenti) {
            $giornata = $indiceGiornata + 1;

            foreach ($accoppiamenti as [$casa, $trasferta]) {
                $partite[] = [
                    'id_edizione_competizione' => $idEdizioneCompetizione,
                    'id_squadra_casa' => $casa,
                    'id_squadra_trasferta' => $trasferta,
                    'fase' => 'Girone',
                    'giornata' => $giornata,
                    'girone' => null,
                    'stato' => 'programmata',
                    'dettagli' => json_encode([
                        'generata_automaticamente' => true,
                        'giro' => 1,
                        'tipo_calendario' => 'campionato',
                    ], JSON_UNESCAPED_UNICODE),
                ];
            }
        }

        if ($giri >= 2) {
            $totaleGiornateBase = count($giornateBase);

            foreach ($giornateBase as $indiceGiornata => $accoppiamenti) {
                $giornata = $totaleGiornateBase + $indiceGiornata + 1;

                foreach ($accoppiamenti as [$casa, $trasferta]) {
                    $partite[] = [
                        'id_edizione_competizione' => $idEdizioneCompetizione,
                        'id_squadra_casa' => $trasferta,
                        'id_squadra_trasferta' => $casa,
                        'fase' => 'Girone',
                        'giornata' => $giornata,
                        'girone' => null,
                        'stato' => 'programmata',
                        'dettagli' => json_encode([
                            'generata_automaticamente' => true,
                            'giro' => 2,
                            'tipo_calendario' => 'campionato',
                        ], JSON_UNESCAPED_UNICODE),
                    ];
                }
            }
        }

        $this->edizioni->creaPartiteBatch($partite);
    }

    private function generaRoundRobinBase(array $idsSquadre): array
    {
        $squadre = array_values($idsSquadre);
        $numeroSquadre = count($squadre);
        $giornate = [];

        for ($turno = 0; $turno < $numeroSquadre - 1; $turno++) {
            $accoppiamenti = [];

            for ($i = 0; $i < $numeroSquadre / 2; $i++) {
                $casa = $squadre[$i];
                $trasferta = $squadre[$numeroSquadre - 1 - $i];

                if ($turno % 2 === 1 && $i === 0) {
                    [$casa, $trasferta] = [$trasferta, $casa];
                }

                $accoppiamenti[] = [$casa, $trasferta];
            }

            $giornate[] = $accoppiamenti;

            $fissa = array_shift($squadre);
            $ultima = array_pop($squadre);
            array_unshift($squadre, $fissa);
            array_splice($squadre, 1, 0, [$ultima]);
        }

        return $giornate;
    }
}
