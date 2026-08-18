<?php

declare(strict_types=1);

namespace App\Services\Competizioni;

use App\Models\EdizioneCompetizione;
use App\Models\Partita;
use App\Models\PartitaQuery;

final class CompetizioneEliminazioneDirettaService
{
    private EdizioneCompetizione $edizioneCompetizioni;
    private Partita $partite;
    private PartitaQuery $partiteQuery;

    public function __construct()
    {
        $this->edizioneCompetizioni = new EdizioneCompetizione();
        $this->partite = new Partita();
        $this->partiteQuery = new PartitaQuery();
    }

    public function analizzaTurnoCorrente(int $idEdizioneCompetizione): array
    {
        $competizione = $this->edizioneCompetizioni->findEdizioneCompetizione($idEdizioneCompetizione);

        if (!$competizione) {
            return $this->rispostaBase(false);
        }

        $struttura = $this->decodificaJson($competizione['Struttura'] ?? null);
        $finaleSecca = (bool) ($struttura['finale_secca'] ?? true);
        $finaleTerzoPosto = (bool) ($struttura['finale_terzo_posto'] ?? false);

        $tutteLePartite = $this->partiteQuery->findByEdizioneCompetizione($idEdizioneCompetizione);
        if ($tutteLePartite === []) {
            return [
                'ok' => false,
                'bloccanti' => [],
                'vincitori' => [],
                'turno' => null,
                'turno_label' => null,
                'finale_secca' => $finaleSecca,
                'finale_terzo_posto' => $finaleTerzoPosto,
            ];
        }

        $faseCorrente = $this->trovaFaseCorrente($tutteLePartite);
        if ($faseCorrente === null) {
            return [
                'ok' => false,
                'bloccanti' => [],
                'vincitori' => [],
                'turno' => null,
                'turno_label' => null,
                'finale_secca' => $finaleSecca,
                'finale_terzo_posto' => $finaleTerzoPosto,
            ];
        }

        $partiteTurno = array_values(array_filter(
            $tutteLePartite,
            fn(array $partita): bool => (string) ($partita['Fase'] ?? '') === $faseCorrente
        ));

        $accoppiamenti = [];
        foreach ($partiteTurno as $partita) {
            $dettagli = $this->decodificaJson($partita['Dettagli'] ?? null);
            $numeroAccoppiamento = (int) ($dettagli['numero_accoppiamento'] ?? 0);

            if ($numeroAccoppiamento <= 0) {
                continue;
            }

            if (!isset($accoppiamenti[$numeroAccoppiamento])) {
                $accoppiamenti[$numeroAccoppiamento] = [];
            }

            $accoppiamenti[$numeroAccoppiamento][] = $partita;
        }

        ksort($accoppiamenti);

        $bloccanti = [];
        $vincitori = [];
        $perdenti = [];

        foreach ($accoppiamenti as $numeroAccoppiamento => $partiteAccoppiamento) {
            $esito = $this->analizzaAccoppiamento($numeroAccoppiamento, $partiteAccoppiamento);

            if ($esito['stato'] !== 'ok') {
                $bloccanti[] = $esito;
                continue;
            }

            $vincitori[] = (int) $esito['vincitore'];
            if (isset($esito['perdente']) && (int) $esito['perdente'] > 0) {
                $perdenti[] = (int) $esito['perdente'];
            }
        }

        return [
            'ok' => $bloccanti === [],
            'bloccanti' => $bloccanti,
            'vincitori' => $vincitori,
            'perdenti' => $perdenti,
            'turno' => $faseCorrente,
            'turno_label' => $faseCorrente,
            'finale_secca' => $finaleSecca,
            'finale_terzo_posto' => $finaleTerzoPosto,
        ];
    }

    public function avanzaTurno(int $idEdizioneCompetizione): array
    {
        $analisi = $this->analizzaTurnoCorrente($idEdizioneCompetizione);

        if (!(bool) ($analisi['ok'] ?? false)) {
            return $analisi;
        }

        $turnoCorrente = (string) ($analisi['turno'] ?? '');
        if ($turnoCorrente === '' || $turnoCorrente === 'Finale') {
            return $analisi;
        }

        $competizione = $this->edizioneCompetizioni->findEdizioneCompetizione($idEdizioneCompetizione);
        if (!$competizione) {
            return $analisi;
        }

        $struttura = $this->decodificaJson($competizione['Struttura'] ?? null);
        $finaleSecca = (bool) ($struttura['finale_secca'] ?? true);
        $finaleTerzoPosto = (bool) ($struttura['finale_terzo_posto'] ?? false);

        $vincitori = array_values(array_map('intval', $analisi['vincitori'] ?? []));
        $perdenti = array_values(array_map('intval', $analisi['perdenti'] ?? []));

        $turnoSuccessivo = $this->turnoSuccessivo($turnoCorrente);
        if ($turnoSuccessivo === null) {
            return $analisi;
        }

        if ($this->esisteGiaFase($idEdizioneCompetizione, $turnoSuccessivo)) {
            return $analisi + [
                'gia_generato' => true,
                'turno_successivo' => $turnoSuccessivo,
            ];
        }

        $partiteDaCreare = [];

        $giriTurnoSuccessivo = $turnoSuccessivo === 'Finale' && $finaleSecca ? 1 : (int) ($competizione['Giri'] ?? 1);
        $giriTurnoSuccessivo = max(1, $giriTurnoSuccessivo);

        $numeroAccoppiamento = 0;
        for ($i = 0; $i < count($vincitori); $i += 2) {
            $squadraA = (int) ($vincitori[$i] ?? 0);
            $squadraB = (int) ($vincitori[$i + 1] ?? 0);

            if ($squadraA <= 0 || $squadraB <= 0) {
                continue;
            }

            $numeroAccoppiamento++;

            for ($giro = 1; $giro <= $giriTurnoSuccessivo; $giro++) {
                $casa = $giro === 1 ? $squadraA : $squadraB;
                $trasferta = $giro === 1 ? $squadraB : $squadraA;

                $partiteDaCreare[] = [
                    'id_edizione_competizione' => $idEdizioneCompetizione,
                    'id_squadra_casa' => $casa,
                    'id_squadra_trasferta' => $trasferta,
                    'fase' => $turnoSuccessivo,
                    'giornata' => $giro,
                    'girone' => null,
                    'stato' => 'programmata',
                    'dettagli' => json_encode([
                        'generata_automaticamente' => true,
                        'tipo_calendario' => 'eliminazione_diretta',
                        'indice_turno' => $this->indiceTurno($turnoSuccessivo),
                        'nome_turno' => $turnoSuccessivo,
                        'numero_accoppiamento' => $numeroAccoppiamento,
                        'bye' => false,
                        'giro' => $giro,
                        'giri_previsti_turno' => $giriTurnoSuccessivo,
                        'finale_secca' => $finaleSecca,
                        'finale_terzo_posto' => $finaleTerzoPosto,
                    ], JSON_UNESCAPED_UNICODE),
                ];
            }
        }

        if ($turnoCorrente === 'Semifinale' && $finaleTerzoPosto && count($perdenti) >= 2) {
            $giriFinaleTerzoPosto = 1;
            $numeroAccoppiamentoFinaleTerzo = 1;

            $squadraA = (int) $perdenti[0];
            $squadraB = (int) $perdenti[1];

            $partiteDaCreare[] = [
                'id_edizione_competizione' => $idEdizioneCompetizione,
                'id_squadra_casa' => $squadraA,
                'id_squadra_trasferta' => $squadraB,
                'fase' => 'Finale3Posto',
                'giornata' => 1,
                'girone' => null,
                'stato' => 'programmata',
                'dettagli' => json_encode([
                    'generata_automaticamente' => true,
                    'tipo_calendario' => 'eliminazione_diretta',
                    'indice_turno' => $this->indiceTurno('Finale3Posto'),
                    'nome_turno' => 'Finale3Posto',
                    'numero_accoppiamento' => $numeroAccoppiamentoFinaleTerzo,
                    'bye' => false,
                    'giro' => 1,
                    'giri_previsti_turno' => $giriFinaleTerzoPosto,
                    'finale_secca' => true,
                    'finale_terzo_posto' => true,
                ], JSON_UNESCAPED_UNICODE),
            ];
        }

        if ($partiteDaCreare !== []) {
            $this->partite->creaPartiteBatch($partiteDaCreare);
        }

        return $analisi + [
            'turno_successivo' => $turnoSuccessivo,
            'partite_generate' => count($partiteDaCreare),
        ];
    }

    private function analizzaAccoppiamento(int $numeroAccoppiamento, array $partite): array
    {
        if ($partite === []) {
            return [
                'stato' => 'bloccato',
                'numero_accoppiamento' => $numeroAccoppiamento,
                'motivo' => 'Nessuna partita trovata',
            ];
        }

        $prima = $partite[0];
        $dettagliPrima = $this->decodificaJson($prima['Dettagli'] ?? null);
        $bye = (bool) ($dettagliPrima['bye'] ?? false);

        if ($bye) {
            return [
                'stato' => 'ok',
                'numero_accoppiamento' => $numeroAccoppiamento,
                'vincitore' => (int) ($prima['IDSquadraCasa'] ?? 0),
                'perdente' => null,
                'motivo' => null,
            ];
        }

        $totali = [];
        $squadreNomi = [];

        foreach ($partite as $partita) {
            $stato = mb_strtolower(trim((string) ($partita['Stato'] ?? '')));
            if ($stato !== 'giocata') {
                return [
                    'stato' => 'bloccato',
                    'numero_accoppiamento' => $numeroAccoppiamento,
                    'motivo' => 'Ci sono partite non ancora giocate',
                ];
            }

            $idCasa = (int) ($partita['IDSquadraCasa'] ?? 0);
            $idTrasferta = (int) ($partita['IDSquadraTrasferta'] ?? 0);
            $golCasa = (int) ($partita['GoalCasa'] ?? 0);
            $golTrasferta = (int) ($partita['GoalTrasferta'] ?? 0);

            $nomeCasa = (string) ($partita['SquadraCasa'] ?? $partita['NomeSquadraCasa'] ?? ('Squadra ' . $idCasa));
            $nomeTrasferta = (string) ($partita['SquadraTrasferta'] ?? $partita['NomeSquadraTrasferta'] ?? ('Squadra ' . $idTrasferta));

            $totali[$idCasa] = ($totali[$idCasa] ?? 0) + $golCasa;
            $totali[$idTrasferta] = ($totali[$idTrasferta] ?? 0) + $golTrasferta;

            $squadreNomi[$idCasa] = $nomeCasa;
            $squadreNomi[$idTrasferta] = $nomeTrasferta;
        }

        if (count($totali) !== 2) {
            return [
                'stato' => 'bloccato',
                'numero_accoppiamento' => $numeroAccoppiamento,
                'motivo' => 'Accoppiamento non valido',
            ];
        }

        $idsSquadre = array_keys($totali);
        $idA = (int) $idsSquadre[0];
        $idB = (int) $idsSquadre[1];
        $golA = (int) $totali[$idA];
        $golB = (int) $totali[$idB];

        if ($golA === $golB) {
            return [
                'stato' => 'bloccato',
                'numero_accoppiamento' => $numeroAccoppiamento,
                'motivo' => sprintf(
                    'Pareggio aggregato: %s %d - %d %s',
                    $squadreNomi[$idA] ?? ('Squadra ' . $idA),
                    $golA,
                    $golB,
                    $squadreNomi[$idB] ?? ('Squadra ' . $idB)
                ),
            ];
        }

        $vincitore = $golA > $golB ? $idA : $idB;
        $perdente = $golA > $golB ? $idB : $idA;

        return [
            'stato' => 'ok',
            'numero_accoppiamento' => $numeroAccoppiamento,
            'vincitore' => $vincitore,
            'perdente' => $perdente,
            'motivo' => null,
        ];
    }

    private function trovaFaseCorrente(array $partite): ?string
    {
        $ordine = [
            'Girone' => 0,
            'Sessantaquattresimo' => 1,
            'Trentaduesimo' => 2,
            'Sedicesimo' => 3,
            'Ottavo' => 4,
            'Quarto' => 5,
            'Semifinale' => 6,
            'Finale3Posto' => 7,
            'Finale' => 8,
        ];

        $faseCorrente = null;
        $pesoMassimo = -1;

        foreach ($partite as $partita) {
            $fase = trim((string) ($partita['Fase'] ?? ''));
            if ($fase === '') {
                continue;
            }

            $peso = $ordine[$fase] ?? -1;
            if ($peso > $pesoMassimo) {
                $pesoMassimo = $peso;
                $faseCorrente = $fase;
            }
        }

        return $faseCorrente;
    }

    private function esisteGiaFase(int $idEdizioneCompetizione, string $fase): bool
    {
        $partite = $this->partiteQuery->findByEdizioneCompetizione($idEdizioneCompetizione);

        foreach ($partite as $partita) {
            if ((string) ($partita['Fase'] ?? '') === $fase) {
                return true;
            }
        }

        return false;
    }

    private function turnoSuccessivo(string $turnoCorrente): ?string
    {
        return match ($turnoCorrente) {
            'Sessantaquattresimo' => 'Trentaduesimo',
            'Trentaduesimo' => 'Sedicesimo',
            'Sedicesimo' => 'Ottavo',
            'Ottavo' => 'Quarto',
            'Quarto' => 'Semifinale',
            'Semifinale' => 'Finale',
            default => null,
        };
    }

    private function indiceTurno(string $fase): int
    {
        return match ($fase) {
            'Sessantaquattresimo' => 1,
            'Trentaduesimo' => 2,
            'Sedicesimo' => 3,
            'Ottavo' => 4,
            'Quarto' => 5,
            'Semifinale' => 6,
            'Finale3Posto' => 7,
            'Finale' => 8,
            default => 0,
        };
    }

    private function decodificaJson(mixed $valore): array
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

    private function rispostaBase(bool $ok): array
    {
        return [
            'ok' => $ok,
            'bloccanti' => [],
            'vincitori' => [],
            'perdenti' => [],
            'turno' => null,
            'turno_label' => null,
            'finale_secca' => true,
            'finale_terzo_posto' => false,
        ];
    }

    public function faseBloccata(int $idEdizioneCompetizione, ?string $fase): bool
    {
        if ($fase === null || trim($fase) === '') {
            return false;
        }

        $fase = trim($fase);
        $pesoFase = $this->pesoFase($fase);

        if ($pesoFase < 0) {
            return false;
        }

        $partite = $this->partiteQuery->findByEdizioneCompetizione($idEdizioneCompetizione);

        foreach ($partite as $partita) {
            $fasePartita = trim((string) ($partita['Fase'] ?? ''));
            if ($fasePartita === '') {
                continue;
            }

            if ($this->pesoFase($fasePartita) > $pesoFase) {
                return true;
            }
        }

        return false;
    }

    public function mappaFasiBloccate(int $idEdizioneCompetizione): array
    {
        $partite = $this->partiteQuery->findByEdizioneCompetizione($idEdizioneCompetizione);
        $fasi = [];

        foreach ($partite as $partita) {
            $fase = trim((string) ($partita['Fase'] ?? ''));
            if ($fase !== '') {
                $fasi[$fase] = true;
            }
        }

        $mappa = [];
        foreach (array_keys($fasi) as $fase) {
            $mappa[$fase] = $this->faseBloccata($idEdizioneCompetizione, $fase);
        }

        return $mappa;
    }

    private function pesoFase(string $fase): int
    {
        return match ($fase) {
            'Girone' => 0,
            'Sessantaquattresimo' => 1,
            'Trentaduesimo' => 2,
            'Sedicesimo' => 3,
            'Ottavo' => 4,
            'Quarto' => 5,
            'Semifinale' => 6,
            'Finale3Posto' => 7,
            'Finale' => 8,
            default => -1,
        };
    }

    public function costruisciPodio(int $idEdizioneCompetizione): array
    {
        $competizione = $this->edizioneCompetizioni->findEdizioneCompetizione($idEdizioneCompetizione);
        if (!$competizione) {
            return [];
        }

        $struttura = $this->decodificaJson($competizione['Struttura'] ?? null);
        $finaleTerzoPosto = (bool) ($struttura['finale_terzo_posto'] ?? false);

        $partite = $this->partiteQuery->findByEdizioneCompetizione($idEdizioneCompetizione);
        if ($partite === []) {
            return [];
        }

        $finale = array_values(array_filter(
            $partite,
            fn(array $partita): bool => (string) ($partita['Fase'] ?? '') === 'Finale'
        ));

        if ($finale === []) {
            return [];
        }

        $esitoFinale = $this->analizzaAccoppiamento(1, $finale);
        if (($esitoFinale['stato'] ?? '') !== 'ok') {
            return [];
        }

        $podio = [
            [
                'posizione' => 1,
                'id_squadra' => (int) $esitoFinale['vincitore'],
                'tipo' => 'univoca',
            ],
            [
                'posizione' => 2,
                'id_squadra' => (int) $esitoFinale['perdente'],
                'tipo' => 'univoca',
            ],
        ];

        $finaleTerzo = array_values(array_filter(
            $partite,
            fn(array $partita): bool => (string) ($partita['Fase'] ?? '') === 'Finale3Posto'
        ));

        if ($finaleTerzoPosto && $finaleTerzo !== []) {
            $esitoFinaleTerzo = $this->analizzaAccoppiamento(1, $finaleTerzo);

            if (($esitoFinaleTerzo['stato'] ?? '') === 'ok') {
                $podio[] = [
                    'posizione' => 3,
                    'id_squadra' => (int) $esitoFinaleTerzo['vincitore'],
                    'tipo' => 'univoca',
                ];

                $podio[] = [
                    'posizione' => 4,
                    'id_squadra' => (int) $esitoFinaleTerzo['perdente'],
                    'tipo' => 'univoca',
                ];
            }
        } else {
            $semifinale = array_values(array_filter(
                $partite,
                fn(array $partita): bool => (string) ($partita['Fase'] ?? '') === 'Semifinale'
            ));

            $accoppiamenti = [];
            foreach ($semifinale as $partita) {
                $dettagli = $this->decodificaJson($partita['Dettagli'] ?? null);
                $numeroAccoppiamento = (int) ($dettagli['numero_accoppiamento'] ?? 0);

                if ($numeroAccoppiamento <= 0) {
                    continue;
                }

                if (!isset($accoppiamenti[$numeroAccoppiamento])) {
                    $accoppiamenti[$numeroAccoppiamento] = [];
                }

                $accoppiamenti[$numeroAccoppiamento][] = $partita;
            }

            foreach ($accoppiamenti as $partiteAccoppiamento) {
                $esitoSemifinale = $this->analizzaAccoppiamento(1, $partiteAccoppiamento);

                if (($esitoSemifinale['stato'] ?? '') === 'ok' && isset($esitoSemifinale['perdente'])) {
                    $podio[] = [
                        'posizione' => 3,
                        'id_squadra' => (int) $esitoSemifinale['perdente'],
                        'tipo' => 'ex_aequo',
                        'gruppo' => 'semifinaliste_perdenti',
                    ];
                }
            }
        }

        return $podio;
    }
}
