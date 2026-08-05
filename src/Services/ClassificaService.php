<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Partita;

class ClassificaService
{
    public function calcolaPerCompetizione(
        int $idEdizioneCompetizione,
        int $giornataDa,
        int $giornataA,
        array $struttura = []
    ): array {
        $partitaModel = new Partita();
        $partite = $partitaModel->partitePerCompetizioneEIntervallo(
            $idEdizioneCompetizione,
            $giornataDa,
            $giornataA
        );

        $puntiVittoria = (int) ($struttura['punti']['vittoria'] ?? 3);
        $puntiPareggio = (int) ($struttura['punti']['pareggio'] ?? 1);
        $puntiSconfitta = (int) ($struttura['punti']['sconfitta'] ?? 0);

        $ordinamento = $struttura['classifica']['ordinamento'] ?? ['punti', 'differenza_reti', 'gol_fatti'];
        if (!is_array($ordinamento) || $ordinamento === []) {
            $ordinamento = ['punti', 'differenza_reti', 'gol_fatti'];
        }

        $classifica = [];

        foreach ($partite as $partita) {
            if ($partita['GoalCasa'] === null || $partita['GoalTrasferta'] === null) {
                continue;
            }

            $idCasa = (int) $partita['IDSquadraCasa'];
            $idTrasferta = (int) $partita['IDSquadraTrasferta'];
            $goalCasa = (int) $partita['GoalCasa'];
            $goalTrasferta = (int) $partita['GoalTrasferta'];

            $this->inizializzaSquadra($classifica, $idCasa, (string) $partita['NomeSquadraCasa']);
            $this->inizializzaSquadra($classifica, $idTrasferta, (string) $partita['NomeSquadraTrasferta']);

            $this->aggiornaSquadra(
                $classifica[$idCasa],
                $goalCasa,
                $goalTrasferta,
                $puntiVittoria,
                $puntiPareggio,
                $puntiSconfitta
            );

            $this->aggiornaSquadra(
                $classifica[$idTrasferta],
                $goalTrasferta,
                $goalCasa,
                $puntiVittoria,
                $puntiPareggio,
                $puntiSconfitta
            );
        }

        foreach ($classifica as &$riga) {
            $riga['DifferenzaReti'] = $riga['Fatti'] - $riga['Subiti'];
            $riga['Forma'] = array_slice($riga['_forma'], -5);
            unset($riga['_forma']);
        }
        unset($riga);

        $classifica = array_values($classifica);

        usort($classifica, function (array $a, array $b) use ($ordinamento, $partite, $puntiVittoria, $puntiPareggio, $puntiSconfitta): int {
            return $this->confrontaRighe(
                $a,
                $b,
                $ordinamento,
                $partite,
                $puntiVittoria,
                $puntiPareggio,
                $puntiSconfitta
            );
        });

        foreach ($classifica as $indice => &$riga) {
            $riga['Posizione'] = $indice + 1;
        }
        unset($riga);

        return $classifica;
    }

    private function inizializzaSquadra(array &$classifica, int $idSquadra, string $nome): void
    {
        if (isset($classifica[$idSquadra])) {
            return;
        }

        $classifica[$idSquadra] = [
            'IDSquadra' => $idSquadra,
            'Nome' => $nome,
            'Giocate' => 0,
            'Vinte' => 0,
            'Pareggiate' => 0,
            'Perse' => 0,
            'Fatti' => 0,
            'Subiti' => 0,
            'DifferenzaReti' => 0,
            'Punti' => 0,
            '_forma' => [],
        ];
    }

    private function aggiornaSquadra(
        array &$riga,
        int $goalFatti,
        int $goalSubiti,
        int $puntiVittoria,
        int $puntiPareggio,
        int $puntiSconfitta
    ): void {
        $riga['Giocate']++;
        $riga['Fatti'] += $goalFatti;
        $riga['Subiti'] += $goalSubiti;

        if ($goalFatti > $goalSubiti) {
            $riga['Vinte']++;
            $riga['Punti'] += $puntiVittoria;
            $riga['_forma'][] = 'V';
            return;
        }

        if ($goalFatti === $goalSubiti) {
            $riga['Pareggiate']++;
            $riga['Punti'] += $puntiPareggio;
            $riga['_forma'][] = 'N';
            return;
        }

        $riga['Perse']++;
        $riga['Punti'] += $puntiSconfitta;
        $riga['_forma'][] = 'P';
    }

    private function confrontaRighe(
        array $a,
        array $b,
        array $ordinamento,
        array $partite,
        int $puntiVittoria,
        int $puntiPareggio,
        int $puntiSconfitta
    ): int {
        foreach ($ordinamento as $criterio) {
            $risultato = 0;

            switch ($criterio) {
                case 'punti':
                    $risultato = $b['Punti'] <=> $a['Punti'];
                    break;

                case 'differenza_reti':
                    $risultato = $b['DifferenzaReti'] <=> $a['DifferenzaReti'];
                    break;

                case 'gol_fatti':
                    $risultato = $b['Fatti'] <=> $a['Fatti'];
                    break;

                case 'gol_subiti':
                    $risultato = $a['Subiti'] <=> $b['Subiti'];
                    break;

                case 'nome':
                    $risultato = $a['Nome'] <=> $b['Nome'];
                    break;

                case 'scontri_diretti':
                    $risultato = $this->confrontaScontriDiretti(
                        $a,
                        $b,
                        $partite,
                        $puntiVittoria,
                        $puntiPareggio,
                        $puntiSconfitta
                    );
                    break;
            }

            if ($risultato !== 0) {
                return $risultato;
            }
        }

        return $a['Nome'] <=> $b['Nome'];
    }

    private function confrontaScontriDiretti(
        array $a,
        array $b,
        array $partite,
        int $puntiVittoria,
        int $puntiPareggio,
        int $puntiSconfitta
    ): int {
        $idA = (int) $a['IDSquadra'];
        $idB = (int) $b['IDSquadra'];

        $statsA = [
            'Punti' => 0,
            'DifferenzaReti' => 0,
            'Fatti' => 0,
            'Subiti' => 0,
        ];

        $statsB = [
            'Punti' => 0,
            'DifferenzaReti' => 0,
            'Fatti' => 0,
            'Subiti' => 0,
        ];

        foreach ($partite as $partita) {
            if ($partita['GoalCasa'] === null || $partita['GoalTrasferta'] === null) {
                continue;
            }

            $casa = (int) $partita['IDSquadraCasa'];
            $trasferta = (int) $partita['IDSquadraTrasferta'];

            $matchDiretto = ($casa === $idA && $trasferta === $idB)
                || ($casa === $idB && $trasferta === $idA);

            if (!$matchDiretto) {
                continue;
            }

            $goalCasa = (int) $partita['GoalCasa'];
            $goalTrasferta = (int) $partita['GoalTrasferta'];

            if ($casa === $idA) {
                $this->aggiornaMiniStats($statsA, $goalCasa, $goalTrasferta, $puntiVittoria, $puntiPareggio, $puntiSconfitta);
                $this->aggiornaMiniStats($statsB, $goalTrasferta, $goalCasa, $puntiVittoria, $puntiPareggio, $puntiSconfitta);
            } else {
                $this->aggiornaMiniStats($statsA, $goalTrasferta, $goalCasa, $puntiVittoria, $puntiPareggio, $puntiSconfitta);
                $this->aggiornaMiniStats($statsB, $goalCasa, $goalTrasferta, $puntiVittoria, $puntiPareggio, $puntiSconfitta);
            }
        }

        $statsA['DifferenzaReti'] = $statsA['Fatti'] - $statsA['Subiti'];
        $statsB['DifferenzaReti'] = $statsB['Fatti'] - $statsB['Subiti'];

        return ($statsB['Punti'] <=> $statsA['Punti'])
            ?: ($statsB['DifferenzaReti'] <=> $statsA['DifferenzaReti'])
            ?: ($statsB['Fatti'] <=> $statsA['Fatti']);
    }

    private function aggiornaMiniStats(
        array &$stats,
        int $goalFatti,
        int $goalSubiti,
        int $puntiVittoria,
        int $puntiPareggio,
        int $puntiSconfitta
    ): void {
        $stats['Fatti'] += $goalFatti;
        $stats['Subiti'] += $goalSubiti;

        if ($goalFatti > $goalSubiti) {
            $stats['Punti'] += $puntiVittoria;
            return;
        }

        if ($goalFatti === $goalSubiti) {
            $stats['Punti'] += $puntiPareggio;
            return;
        }

        $stats['Punti'] += $puntiSconfitta;
    }
}