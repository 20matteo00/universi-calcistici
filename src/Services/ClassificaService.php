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
        array $struttura = [],
        ?string $filtro = null
    ): array {
        $partitaModel = new Partita();
        $partite = $partitaModel->partitePerCompetizioneEIntervallo(
            $idEdizioneCompetizione,
            $giornataDa,
            $giornataA
        );

        return $this->calcolaClassificaDaPartite($partite, $struttura, $filtro);
    }

    public function calcolaVisteCompetizione(
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

        $viste = [
            'generale' => $this->calcolaClassificaDaPartite($partite, $struttura, null),
            'casa' => $this->calcolaClassificaDaPartite($partite, $struttura, 'casa'),
            'trasferta' => $this->calcolaClassificaDaPartite($partite, $struttura, 'trasferta'),
        ];

        $numeroGiri = (int) ($struttura['giri'] ?? 0);
        if ($numeroGiri > 0) {
            $partitePerGiro = $this->raggruppaPartitePerGiro($partite, $numeroGiri);

            foreach ($partitePerGiro as $numeroGiro => $partiteGiro) {
                $viste['giro_' . $numeroGiro] = $this->calcolaClassificaDaPartite($partiteGiro, $struttura, null);
            }
        }

        return $viste;
    }

    public function calcolaTabellaCapolista(
        int $idEdizioneCompetizione,
        array $struttura = []
    ): array {
        $partitaModel = new Partita();
        $giornate = $partitaModel->giornatePerCompetizione($idEdizioneCompetizione);

        if (empty($giornate)) {
            return [];
        }

        $giornataMin = (int) min($giornate);
        $ultimaGiornata = (int) max($giornate);

        $tutteLePartite = $partitaModel->partitePerCompetizioneEIntervallo(
            $idEdizioneCompetizione,
            $giornataMin,
            $ultimaGiornata
        );

        $mappaSquadre = $this->creaMappaSquadre($tutteLePartite);

        $tabella = [];

        foreach ($giornate as $giornata) {
            $partiteFinoAQui = array_values(array_filter(
                $tutteLePartite,
                static fn(array $partita): bool => (int) ($partita['Giornata'] ?? 0) <= (int) $giornata
            ));

            $classifica = $this->calcolaClassificaDaPartite($partiteFinoAQui, $struttura, null);

            if (empty($classifica)) {
                continue;
            }

            $prima = $classifica[0];
            $seconda = $classifica[1] ?? null;

            $pariInTesta = $seconda && ((int) $prima['Punti'] === (int) $seconda['Punti']);

            $idSquadra = $pariInTesta ? null : (int) ($prima['IDSquadra'] ?? 0);
            $squadraInfo = ($idSquadra && isset($mappaSquadre[$idSquadra])) ? $mappaSquadre[$idSquadra] : [];

            $tabella[] = [
                'Giornata' => (int) $giornata,
                'Capolista' => $pariInTesta ? '-' : (string) ($prima['Nome'] ?? ''),
                'IDSquadra' => $idSquadra,
                'PariInTesta' => $pariInTesta,
                'Punti' => (int) ($prima['Punti'] ?? 0),
                'Colori' => $pariInTesta ? [] : ($squadraInfo['Colori'] ?? []),
                'NomeBreve' => $pariInTesta ? '-' : (string) ($squadraInfo['NomeBreve'] ?? $prima['Nome'] ?? ''),
            ];
        }

        return $tabella;
    }

    private function creaMappaSquadre(array $partite): array
    {
        $mappa = [];

        foreach ($partite as $partita) {
            $idCasa = (int) ($partita['IDSquadraCasa'] ?? 0);
            if ($idCasa > 0 && !isset($mappa[$idCasa])) {
                $mappa[$idCasa] = [
                    'ID' => $idCasa,
                    'Nome' => (string) ($partita['NomeSquadraCasa'] ?? ''),
                    'NomeBreve' => $this->abbreviaNomeSquadra((string) ($partita['NomeSquadraCasa'] ?? '')),
                    'Colori' => $this->decodificaColori((string) ($partita['ColoriSquadraCasa'] ?? '{}')),
                ];
            }

            $idTrasferta = (int) ($partita['IDSquadraTrasferta'] ?? 0);
            if ($idTrasferta > 0 && !isset($mappa[$idTrasferta])) {
                $mappa[$idTrasferta] = [
                    'ID' => $idTrasferta,
                    'Nome' => (string) ($partita['NomeSquadraTrasferta'] ?? ''),
                    'NomeBreve' => $this->abbreviaNomeSquadra((string) ($partita['NomeSquadraTrasferta'] ?? '')),
                    'Colori' => $this->decodificaColori((string) ($partita['ColoriSquadraTrasferta'] ?? '{}')),
                ];
            }
        }

        return $mappa;
    }

    private function decodificaColori(string $json): array
    {
        $colori = json_decode($json, true);
        if (!is_array($colori)) {
            $colori = [];
        }

        return [
            'sfondo' => (string) ($colori['sfondo'] ?? '#6c757d'),
            'testo' => (string) ($colori['testo'] ?? '#ffffff'),
            'bordo' => (string) ($colori['bordo'] ?? '#6c757d'),
        ];
    }

    private function abbreviaNomeSquadra(string $nome): string
    {
        $nome = trim($nome);
        if ($nome === '') {
            return '';
        }

        $parole = preg_split('/\s+/', $nome) ?: [];
        if (count($parole) === 1) {
            return mb_substr($parole[0], 0, 3);
        }

        $sigla = '';
        foreach ($parole as $parola) {
            $sigla .= mb_substr($parola, 0, 1);
        }

        return mb_substr($sigla, 0, 3);
    }

    private function calcolaClassificaDaPartite(array $partite, array $struttura = [], ?string $filtro = null): array
    {
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

            if ($filtro === 'casa') {
                $this->inizializzaSquadra(
                    $classifica,
                    $idCasa,
                    (string) $partita['NomeSquadraCasa'],
                    (string) ($partita['ColoriSquadraCasa'] ?? '{}')
                );

                $this->aggiornaSquadra(
                    $classifica[$idCasa],
                    $goalCasa,
                    $goalTrasferta,
                    $puntiVittoria,
                    $puntiPareggio,
                    $puntiSconfitta
                );
                continue;
            }

            if ($filtro === 'trasferta') {
                $this->inizializzaSquadra(
                    $classifica,
                    $idTrasferta,
                    (string) $partita['NomeSquadraTrasferta'],
                    (string) ($partita['ColoriSquadraTrasferta'] ?? '{}')
                );

                $this->aggiornaSquadra(
                    $classifica[$idTrasferta],
                    $goalTrasferta,
                    $goalCasa,
                    $puntiVittoria,
                    $puntiPareggio,
                    $puntiSconfitta
                );
                continue;
            }

            $this->inizializzaSquadra(
                $classifica,
                $idCasa,
                (string) $partita['NomeSquadraCasa'],
                (string) ($partita['ColoriSquadraCasa'] ?? '{}')
            );

            $this->inizializzaSquadra(
                $classifica,
                $idTrasferta,
                (string) $partita['NomeSquadraTrasferta'],
                (string) ($partita['ColoriSquadraTrasferta'] ?? '{}')
            );

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

    private function raggruppaPartitePerGiro(array $partite, int $numeroGiri): array
    {
        $partitePerGiro = [];
        $trovatoAlmenoUnGiro = false;

        foreach ($partite as $partita) {
            $dettagli = json_decode((string) ($partita['Dettagli'] ?? '{}'), true);
            if (!is_array($dettagli)) {
                $dettagli = [];
            }

            $giro = (int) ($dettagli['giro'] ?? 0);

            if ($giro > 0) {
                $trovatoAlmenoUnGiro = true;
                $partitePerGiro[$giro] ??= [];
                $partitePerGiro[$giro][] = $partita;
            }
        }

        if ($trovatoAlmenoUnGiro) {
            ksort($partitePerGiro);
            return $partitePerGiro;
        }

        $giornate = [];

        foreach ($partite as $partita) {
            $giornata = (int) ($partita['Giornata'] ?? 0);
            if ($giornata > 0) {
                $giornate[$giornata] = true;
            }
        }

        $totaleGiornate = count($giornate);
        if ($totaleGiornate === 0 || $numeroGiri <= 0) {
            return [];
        }

        ksort($giornate);
        $giornateOrdinate = array_keys($giornate);
        $giornatePerGiro = (int) ceil(count($giornateOrdinate) / $numeroGiri);

        $mappaGiornataGiro = [];
        foreach ($giornateOrdinate as $indice => $giornata) {
            $giro = (int) floor($indice / $giornatePerGiro) + 1;
            if ($giro > $numeroGiri) {
                $giro = $numeroGiri;
            }
            $mappaGiornataGiro[(int) $giornata] = $giro;
        }

        foreach ($partite as $partita) {
            $giornata = (int) ($partita['Giornata'] ?? 0);
            if (!isset($mappaGiornataGiro[$giornata])) {
                continue;
            }

            $giro = $mappaGiornataGiro[$giornata];
            $partitePerGiro[$giro] ??= [];
            $partitePerGiro[$giro][] = $partita;
        }

        ksort($partitePerGiro);

        return $partitePerGiro;
    }

    private function inizializzaSquadra(array &$classifica, int $idSquadra, string $nome, string $jsonColori = '{}'): void
    {
        if (isset($classifica[$idSquadra])) {
            return;
        }

        $classifica[$idSquadra] = [
            'IDSquadra' => $idSquadra,
            'Nome' => $nome,
            'NomeBreve' => $this->abbreviaNomeSquadra($nome),
            'Colori' => $this->decodificaColori($jsonColori),
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
