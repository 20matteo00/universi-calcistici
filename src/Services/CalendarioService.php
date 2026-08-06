<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Edizione;
use App\Models\Partita;

class CalendarioService
{
    private Edizione $edizioni;
    private Partita $partite;

    public function __construct()
    {
        $this->edizioni = new Edizione();
        $this->partite = new Partita();
    }

    public function generaPerEdizione(int $idEdizione): void
    {
        $competizioni = $this->edizioni->competizioniEdizione($idEdizione);

        foreach ($competizioni as $competizione) {
            $idEdizioneCompetizione = (int) ($competizione['ID'] ?? 0);
            $tipo = mb_strtolower(trim((string) ($competizione['Tipo'] ?? '')));
            $inizialmenteVuota = !empty($competizione['InizialmenteVuota']);

            if ($inizialmenteVuota) {
                continue;
            }

            if ($this->partite->contaPartitePerCompetizione($idEdizioneCompetizione) > 0) {
                continue;
            }

            if ($tipo === 'lega') {
                $this->generaLega($competizione);
                continue;
            }

            if ($tipo === 'eliminazione_diretta' || $tipo === 'eliminazione') {
                $this->generaEliminazioneDirettaPrimoTurno($competizione);
                continue;
            }
        }
    }

    private function generaLega(array $competizione): void
    {
        $idEdizioneCompetizione = (int) ($competizione['ID'] ?? 0);
        $giri = $this->estraiNumeroGiriCompetizione($competizione);

        $squadreIscritte = $this->edizioni->squadreIscritteACompetizione($idEdizioneCompetizione);
        $idsSquadre = array_map(
            fn(array $squadra): int => (int) ($squadra['IDSquadra'] ?? 0),
            $squadreIscritte
        );

        $idsSquadre = array_values(array_filter($idsSquadre, fn(int $id): bool => $id > 0));
        $numeroSquadreReali = count($idsSquadre);

        if ($numeroSquadreReali < 2) {
            throw new \RuntimeException('Competizione con meno di 2 squadre.');
        }

        $giornateBase = $this->generaRoundRobinBase($idsSquadre);
        $totaleGiornateBase = count($giornateBase);
        $partite = [];

        for ($giro = 1; $giro <= $giri; $giro++) {
            foreach ($giornateBase as $indiceGiornata => $accoppiamenti) {
                $giornata = (($giro - 1) * $totaleGiornateBase) + $indiceGiornata + 1;

                foreach ($accoppiamenti as [$casaBase, $trasfertaBase]) {
                    if ($casaBase === null || $trasfertaBase === null) {
                        continue;
                    }

                    $casa = $giro % 2 === 1 ? $casaBase : $trasfertaBase;
                    $trasferta = $giro % 2 === 1 ? $trasfertaBase : $casaBase;

                    $partite[] = [
                        'id_edizione_competizione' => $idEdizioneCompetizione,
                        'id_squadra_casa' => $casa,
                        'id_squadra_trasferta' => $trasferta,
                        'fase' => null,
                        'giornata' => $giornata,
                        'girone' => null,
                        'stato' => 'programmata',
                        'dettagli' => json_encode([
                            'generata_automaticamente' => true,
                            'giro' => $giro,
                            'giri_previsti' => $giri,
                            'tipo_calendario' => 'lega',
                            'numero_squadre_reali' => $numeroSquadreReali,
                            'con_bye' => $numeroSquadreReali % 2 !== 0,
                        ], JSON_UNESCAPED_UNICODE),
                    ];
                }
            }
        }

        $this->partite->creaPartiteBatch($partite);
    }

    private function generaEliminazioneDirettaPrimoTurno(array $competizione): void
    {
        $idEdizioneCompetizione = (int) ($competizione['ID'] ?? 0);
        $giri = max(1, (int) ($competizione['Giri'] ?? 1));

        $strutturaJson = (string) ($competizione['Struttura'] ?? '{}');
        $struttura = json_decode($strutturaJson, true);

        if (!is_array($struttura)) {
            $struttura = [];
        }

        $finaleSecca = (bool) ($struttura['finale_secca'] ?? true);
        $finaleTerzoPosto = (bool) ($struttura['finale_terzo_posto'] ?? false);

        $squadreIscritte = $this->edizioni->squadreIscritteACompetizione($idEdizioneCompetizione);
        $idsSquadre = array_map(
            fn(array $squadra): int => (int) ($squadra['IDSquadra'] ?? 0),
            $squadreIscritte
        );

        $idsSquadre = array_values(array_filter($idsSquadre, fn(int $id): bool => $id > 0));
        $numeroSquadre = count($idsSquadre);

        if ($numeroSquadre < 2) {
            throw new \RuntimeException('Competizione a eliminazione diretta con meno di 2 squadre.');
        }

        if ($numeroSquadre > 128) {
            throw new \RuntimeException('Eliminazione diretta supportata fino a 128 squadre.');
        }

        $dimensioneTabellone = $this->prossimaPotenzaDiDue($numeroSquadre);
        $numeroBye = $dimensioneTabellone - $numeroSquadre;
        $matchPrimoTurno = (int) (($numeroSquadre - $numeroBye) / 2);
        $qualificateDirette = $numeroBye;

        $nomeTurno = $this->nomeTurnoDaNumeroSquadre($dimensioneTabellone);
        $partite = [];
        $indice = 0;
        $numeroAccoppiamento = 0;

        // BYE
        for ($slot = 1; $slot <= $qualificateDirette; $slot++) {
            $idSquadra = $idsSquadre[$indice] ?? 0;
            $indice++;

            if ($idSquadra <= 0) {
                throw new \RuntimeException('Errore nella generazione dei bye.');
            }

            $numeroAccoppiamento++;

            $partite[] = [
                'id_edizione_competizione' => $idEdizioneCompetizione,
                'id_squadra_casa' => $idSquadra,
                'id_squadra_trasferta' => $idSquadra,
                'fase' => $nomeTurno,
                'giornata' => 1,
                'girone' => null,
                'stato' => 'riposo',
                'dettagli' => json_encode([
                    'generata_automaticamente' => true,
                    'tipo_calendario' => 'eliminazione_diretta',
                    'indice_turno' => 1,
                    'nome_turno' => $nomeTurno,
                    'numero_accoppiamento' => $numeroAccoppiamento,
                    'bye' => true,
                    'qualificata_direttamente' => true,
                    'giro' => 1,
                    'giri_previsti_turno' => ($nomeTurno === 'Finale' && $finaleSecca) ? 1 : $giri,
                    'finale_secca' => $finaleSecca,
                    'finale_terzo_posto' => $finaleTerzoPosto,
                ], JSON_UNESCAPED_UNICODE),
            ];
        }

        // ACCOPPIAMENTI REALI
        for ($accoppiamento = 1; $accoppiamento <= $matchPrimoTurno; $accoppiamento++) {
            $squadraA = $idsSquadre[$indice] ?? 0;
            $squadraB = $idsSquadre[$indice + 1] ?? 0;
            $indice += 2;

            if ($squadraA <= 0 || $squadraB <= 0) {
                throw new \RuntimeException('Errore nella generazione del primo turno.');
            }

            $numeroAccoppiamento++;
            $giriTurno = ($nomeTurno === 'Finale' && $finaleSecca) ? 1 : $giri;

            // QUI LA CORREZIONE: giornata = 1..giriTurno, NON 1..matchPrimoTurno
            for ($giro = 1; $giro <= $giriTurno; $giro++) {
                $casa = $giro === 1 ? $squadraA : $squadraB;
                $trasferta = $giro === 1 ? $squadraB : $squadraA;

                $partite[] = [
                    'id_edizione_competizione' => $idEdizioneCompetizione,
                    'id_squadra_casa' => $casa,
                    'id_squadra_trasferta' => $trasferta,
                    'fase' => $nomeTurno,
                    'giornata' => $giro, // ← CORRETTO
                    'girone' => null,
                    'stato' => 'programmata',
                    'dettagli' => json_encode([
                        'generata_automaticamente' => true,
                        'tipo_calendario' => 'eliminazione_diretta',
                        'indice_turno' => 1,
                        'nome_turno' => $nomeTurno,
                        'numero_accoppiamento' => $numeroAccoppiamento,
                        'bye' => false,
                        'giro' => $giro,
                        'giri_previsti_turno' => $giriTurno,
                        'finale_secca' => $finaleSecca,
                        'finale_terzo_posto' => $finaleTerzoPosto,
                    ], JSON_UNESCAPED_UNICODE),
                ];
            }
        }

        $this->partite->creaPartiteBatch($partite);
    }

    private function estraiNumeroGiriCompetizione(array $competizione): int
    {
        if (isset($competizione['Giri']) && is_numeric($competizione['Giri'])) {
            return max(1, (int) $competizione['Giri']);
        }

        $configurazioneCalendario = $this->decodificaJsonCompetizione($competizione['ConfigurazioneCalendario'] ?? null);
        if (isset($configurazioneCalendario['giri']) && is_numeric($configurazioneCalendario['giri'])) {
            return max(1, (int) $configurazioneCalendario['giri']);
        }

        $struttura = $this->decodificaJsonCompetizione($competizione['Struttura'] ?? null);
        if (isset($struttura['giri']) && is_numeric($struttura['giri'])) {
            return max(1, (int) $struttura['giri']);
        }

        return 1;
    }

    private function decodificaJsonCompetizione(mixed $valore): array
    {
        if (is_array($valore)) {
            return $valore;
        }

        if (!is_string($valore) || trim($valore) === '') {
            return [];
        }

        $decodificato = json_decode($valore, true);
        return is_array($decodificato) ? $decodificato : [];
    }

    private function generaRoundRobinBase(array $idsSquadre): array
    {
        $squadre = array_values($idsSquadre);

        if (count($squadre) % 2 !== 0) {
            $squadre[] = null;
        }

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

    private function prossimaPotenzaDiDue(int $numero): int
    {
        $potenza = 2;

        while ($potenza < $numero) {
            $potenza *= 2;
        }

        return $potenza;
    }

    private function nomeTurnoDaNumeroSquadre(int $numeroSquadreNelTurno): string
    {
        return match ($numeroSquadreNelTurno) {
            2 => 'Finale',
            4 => 'Semifinale',
            8 => 'Quarto',
            16 => 'Ottavo',
            32 => 'Sedicesimo',
            64 => 'Trentaduesimo',
            128 => 'Sessantaquattresimo',
            default => 'Girone',
        };
    }
}
