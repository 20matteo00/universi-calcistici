<?php

declare(strict_types=1);

namespace App\Services;

use App\Config\Database;
use App\Models\Partita;
use App\Models\PartitaEvento;
use JsonException;
use PDO;

final class EventGeneratorService
{
    private PDO $pdo;
    private Partita $partite;
    private PartitaEvento $partitaEventi;

    public function __construct()
    {
        $this->pdo = Database::getConnessione();
        $this->partite = new Partita();
        $this->partitaEventi = new PartitaEvento();
    }

    public function rigeneraPerPartita(int $idPartita): void
    {
        $partita = $this->caricaPartita($idPartita);

        if (!$partita) {
            return;
        }

        $goalCasa = $partita['GoalCasa'] ?? null;
        $goalTrasferta = $partita['GoalTrasferta'] ?? null;

        if ($goalCasa === null || $goalTrasferta === null) {
            return;
        }

        $idEdizione = (int) ($partita['IDEdizione'] ?? 0);
        $idSquadraCasa = (int) ($partita['IDSquadraCasa'] ?? 0);
        $idSquadraTrasferta = (int) ($partita['IDSquadraTrasferta'] ?? 0);

        $rosaCasa = $this->caricaGiocatoriSquadraEdizione($idEdizione, $idSquadraCasa);
        $rosaTrasferta = $this->caricaGiocatoriSquadraEdizione($idEdizione, $idSquadraTrasferta);

        if ($rosaCasa === [] || $rosaTrasferta === []) {
            return;
        }

        $this->partitaEventi->deleteByPartita($idPartita);

        $skeleton = $this->generaScheletroEventi(
            $idPartita,
            $idSquadraCasa,
            $idSquadraTrasferta,
            (int) $goalCasa,
            (int) $goalTrasferta
        );

        $eventi = $this->assegnaGiocatoriEventi(
            $skeleton,
            $idSquadraCasa,
            $idSquadraTrasferta,
            $rosaCasa,
            $rosaTrasferta
        );

        foreach ($eventi as $evento) {
            $this->partitaEventi->create($evento);
        }
    }

    public function cancellaPerPartita(int $idPartita): void
    {
        $this->partitaEventi->deleteByPartita($idPartita);
    }

    private function caricaPartita(int $idPartita): ?array
    {
        $sql = "
            SELECT
                p.*,
                ec.IDEdizione
            FROM Partite p
            INNER JOIN EdizioneCompetizione ec ON ec.ID = p.IDEdizioneCompetizione
            WHERE p.ID = :id
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $idPartita]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    private function caricaGiocatoriSquadraEdizione(int $idEdizione, int $idSquadra): array
    {
        $sql = "
            SELECT
                g.ID,
                g.Nome,
                g.Posizione,
                COALESCE(eg.Attacco, g.Attacco, 0) AS Attacco,
                COALESCE(eg.Difesa, g.Difesa, 0) AS Difesa
            FROM EdizioneSquadraGiocatore esg
            INNER JOIN Giocatori g
                ON g.ID = esg.IDGiocatore
            LEFT JOIN EdizioneGiocatore eg
                ON eg.IDEdizione = esg.IDEdizione
               AND eg.IDGiocatore = esg.IDGiocatore
            WHERE esg.IDEdizione = :id_edizione
              AND esg.IDSquadra = :id_squadra
            ORDER BY g.Nome ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id_edizione' => $idEdizione,
            'id_squadra' => $idSquadra,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function generaScheletroEventi(
        int $idPartita,
        int $idSquadraCasa,
        int $idSquadraTrasferta,
        int $goalCasa,
        int $goalTrasferta
    ): array {
        $eventi = [];

        $minutiGol = $this->generaMinutiUniciGol($goalCasa + $goalTrasferta);

        for ($i = 0; $i < $goalCasa; $i++) {
            $eventi[] = [
                'IDPartita' => $idPartita,
                'IDSquadra' => $idSquadraCasa,
                'TipoSkeleton' => 'gol',
                'Minuto' => array_shift($minutiGol),
            ];
        }

        for ($i = 0; $i < $goalTrasferta; $i++) {
            $eventi[] = [
                'IDPartita' => $idPartita,
                'IDSquadra' => $idSquadraTrasferta,
                'TipoSkeleton' => 'gol',
                'Minuto' => array_shift($minutiGol),
            ];
        }

        $numeroGialliCasa = random_int(0, 3);
        $numeroGialliTrasferta = random_int(0, 3);

        for ($i = 0; $i < $numeroGialliCasa; $i++) {
            $eventi[] = [
                'IDPartita' => $idPartita,
                'IDSquadra' => $idSquadraCasa,
                'TipoSkeleton' => 'ammonizione',
                'Minuto' => $this->randomMinuto(),
            ];
        }

        for ($i = 0; $i < $numeroGialliTrasferta; $i++) {
            $eventi[] = [
                'IDPartita' => $idPartita,
                'IDSquadra' => $idSquadraTrasferta,
                'TipoSkeleton' => 'ammonizione',
                'Minuto' => $this->randomMinuto(),
            ];
        }

        if ($this->chance(7)) {
            $eventi[] = [
                'IDPartita' => $idPartita,
                'IDSquadra' => $idSquadraCasa,
                'TipoSkeleton' => 'espulsione',
                'Minuto' => $this->randomMinuto(),
            ];
        }

        if ($this->chance(7)) {
            $eventi[] = [
                'IDPartita' => $idPartita,
                'IDSquadra' => $idSquadraTrasferta,
                'TipoSkeleton' => 'espulsione',
                'Minuto' => $this->randomMinuto(),
            ];
        }

        if ($this->chance(8)) {
            $eventi[] = [
                'IDPartita' => $idPartita,
                'IDSquadra' => $this->chance(50) ? $idSquadraCasa : $idSquadraTrasferta,
                'TipoSkeleton' => 'rigore_sbagliato',
                'Minuto' => $this->randomMinuto(),
            ];
        }

        usort($eventi, static function (array $a, array $b): int {
            $cmp = ((int) ($a['Minuto'] ?? 0)) <=> ((int) ($b['Minuto'] ?? 0));

            if ($cmp !== 0) {
                return $cmp;
            }

            $ordine = [
                'espulsione' => 1,
                'ammonizione' => 2,
                'rigore_sbagliato' => 3,
                'gol' => 4,
            ];

            return ($ordine[$a['TipoSkeleton']] ?? 99) <=> ($ordine[$b['TipoSkeleton']] ?? 99);
        });

        return $eventi;
    }

    private function assegnaGiocatoriEventi(
        array $skeleton,
        int $idSquadraCasa,
        int $idSquadraTrasferta,
        array $rosaCasa,
        array $rosaTrasferta
    ): array {
        $eventiFinali = [];
        $espulsiDalMinuto = [];
        $ammoniti = [];

        foreach ($skeleton as $evento) {
            $idSquadra = (int) ($evento['IDSquadra'] ?? 0);
            $minuto = (int) ($evento['Minuto'] ?? 0);
            $tipoSkeleton = (string) ($evento['TipoSkeleton'] ?? '');

            $rosaSquadra = $idSquadra === $idSquadraCasa ? $rosaCasa : $rosaTrasferta;
            $rosaAvversaria = $idSquadra === $idSquadraCasa ? $rosaTrasferta : $rosaCasa;

            $disponibiliSquadra = $this->filtraGiocatoriDisponibili($rosaSquadra, $espulsiDalMinuto, $minuto);
            $disponibiliAvversari = $this->filtraGiocatoriDisponibili($rosaAvversaria, $espulsiDalMinuto, $minuto);

            if ($tipoSkeleton === 'ammonizione') {
                $candidati = array_values(array_filter(
                    $disponibiliSquadra,
                    fn(array $g): bool => !isset($ammoniti[(int) ($g['ID'] ?? 0)])
                ));

                $giocatoreId = $this->pickWeightedPlayerId(
                    $candidati,
                    fn(array $g): float => $this->pesoCartellino($g)
                );

                if ($giocatoreId === null) {
                    continue;
                }

                $ammoniti[$giocatoreId] = true;

                $eventiFinali[] = [
                    'IDPartita' => (int) $evento['IDPartita'],
                    'IDGiocatore' => $giocatoreId,
                    'IDSquadra' => $idSquadra,
                    'Tipo' => 'ammonizione',
                    'Minuto' => $minuto,
                    'Dettagli' => null,
                ];

                continue;
            }

            if ($tipoSkeleton === 'espulsione') {
                $giocatoreId = $this->pickWeightedPlayerId(
                    $disponibiliSquadra,
                    fn(array $g): float => $this->pesoCartellino($g)
                );

                if ($giocatoreId === null) {
                    continue;
                }

                $espulsiDalMinuto[$giocatoreId] = $minuto;

                $eventiFinali[] = [
                    'IDPartita' => (int) $evento['IDPartita'],
                    'IDGiocatore' => $giocatoreId,
                    'IDSquadra' => $idSquadra,
                    'Tipo' => 'espulsione',
                    'Minuto' => $minuto,
                    'Dettagli' => null,
                ];

                continue;
            }

            if ($tipoSkeleton === 'rigore_sbagliato') {
                $giocatoreId = $this->pickWeightedPlayerId(
                    $disponibiliSquadra,
                    fn(array $g): float => $this->pesoRigore($g)
                );

                if ($giocatoreId === null) {
                    continue;
                }

                $eventiFinali[] = [
                    'IDPartita' => (int) $evento['IDPartita'],
                    'IDGiocatore' => $giocatoreId,
                    'IDSquadra' => $idSquadra,
                    'Tipo' => 'rigore_sbagliato',
                    'Minuto' => $minuto,
                    'Dettagli' => null,
                ];

                continue;
            }

            if ($tipoSkeleton === 'gol') {
                $roll = random_int(1, 100);
                $dettagli = [];
                $idGiocatore = null;

                if ($roll <= 2) {
                    $idGiocatore = $this->pickWeightedPlayerId(
                        $disponibiliAvversari,
                        fn(array $g): float => $this->pesoAutogol($g)
                    );

                    if ($idGiocatore === null) {
                        $idGiocatore = $this->pickWeightedPlayerId(
                            $disponibiliSquadra,
                            fn(array $g): float => $this->pesoGol($g)
                        );
                    } else {
                        $dettagli['autogol'] = true;
                    }
                } elseif ($roll <= 10) {
                    $idGiocatore = $this->pickWeightedPlayerId(
                        $disponibiliSquadra,
                        fn(array $g): float => $this->pesoRigore($g)
                    );

                    if ($idGiocatore !== null) {
                        $dettagli['rigore'] = true;
                    }
                } else {
                    $idGiocatore = $this->pickWeightedPlayerId(
                        $disponibiliSquadra,
                        fn(array $g): float => $this->pesoGol($g)
                    );
                }

                if ($idGiocatore === null) {
                    continue;
                }

                if (!isset($dettagli['autogol']) && random_int(1, 100) <= 55) {
                    $candidatiAssist = array_values(array_filter(
                        $disponibiliSquadra,
                        static fn(array $g): bool => (int) ($g['ID'] ?? 0) !== $idGiocatore
                    ));

                    $assistId = $this->pickWeightedPlayerId(
                        $candidatiAssist,
                        fn(array $g): float => $this->pesoAssist($g)
                    );

                    if ($assistId !== null) {
                        $dettagli['assist_id'] = $assistId;
                    }
                }

                $eventiFinali[] = [
                    'IDPartita' => (int) $evento['IDPartita'],
                    'IDGiocatore' => $idGiocatore,
                    'IDSquadra' => $idSquadra,
                    'Tipo' => 'gol',
                    'Minuto' => $minuto,
                    'Dettagli' => $dettagli === [] ? null : $this->json($dettagli),
                ];
            }
        }

        usort($eventiFinali, static function (array $a, array $b): int {
            $cmp = ((int) ($a['Minuto'] ?? 0)) <=> ((int) ($b['Minuto'] ?? 0));

            if ($cmp !== 0) {
                return $cmp;
            }

            return strcmp((string) ($a['Tipo'] ?? ''), (string) ($b['Tipo'] ?? ''));
        });

        return $eventiFinali;
    }

    private function filtraGiocatoriDisponibili(array $rosa, array $espulsiDalMinuto, int $minuto): array
    {
        return array_values(array_filter(
            $rosa,
            static function (array $g) use ($espulsiDalMinuto, $minuto): bool {
                $id = (int) ($g['ID'] ?? 0);

                if ($id <= 0) {
                    return false;
                }

                if (!isset($espulsiDalMinuto[$id])) {
                    return true;
                }

                return $minuto <= (int) $espulsiDalMinuto[$id];
            }
        ));
    }

    private function pickWeightedPlayerId(array $giocatori, callable $weightResolver): ?int
    {
        if ($giocatori === []) {
            return null;
        }

        $weighted = [];
        $totalWeight = 0.0;

        foreach ($giocatori as $giocatore) {
            $id = (int) ($giocatore['ID'] ?? 0);

            if ($id <= 0) {
                continue;
            }

            $weight = (float) $weightResolver($giocatore);

            if ($weight <= 0) {
                continue;
            }

            $weighted[] = [
                'ID' => $id,
                'weight' => $weight,
            ];

            $totalWeight += $weight;
        }

        if ($weighted === [] || $totalWeight <= 0) {
            return $this->pickRandomPlayerId($giocatori);
        }

        $pick = (random_int(1, 1000000) / 1000000) * $totalWeight;
        $running = 0.0;

        foreach ($weighted as $entry) {
            $running += $entry['weight'];

            if ($pick <= $running) {
                return $entry['ID'];
            }
        }

        return (int) end($weighted)['ID'];
    }

    private function pickRandomPlayerId(array $giocatori): ?int
    {
        if ($giocatori === []) {
            return null;
        }

        $index = array_rand($giocatori);

        return isset($giocatori[$index]['ID']) ? (int) $giocatori[$index]['ID'] : null;
    }

    private function pesoGol(array $giocatore): float
    {
        $attacco = (float) ($giocatore['Attacco'] ?? 0);
        $difesa = (float) ($giocatore['Difesa'] ?? 0);
        $posizione = (string) ($giocatore['Posizione'] ?? '');

        $bonusRuolo = match ($posizione) {
            'ATT' => 2.4,
            'AS', 'AD' => 2.0,
            'TRQ' => 1.8,
            'CL', 'CR' => 1.35,
            'CC' => 1.1,
            'MED' => 0.85,
            'TD', 'TS' => 0.7,
            'DC' => 0.55,
            'POR' => 0.1,
            default => 1.0,
        };

        return max(0.1, (($attacco * 2.2) + ($difesa * 0.1)) * $bonusRuolo);
    }

    private function pesoAssist(array $giocatore): float
    {
        $attacco = (float) ($giocatore['Attacco'] ?? 0);
        $difesa = (float) ($giocatore['Difesa'] ?? 0);
        $posizione = (string) ($giocatore['Posizione'] ?? '');

        $qualita = ($attacco + $difesa) / 2;

        $bonusRuolo = match ($posizione) {
            'TRQ' => 2.1,
            'AS', 'AD' => 1.9,
            'CL', 'CR' => 1.7,
            'CC' => 1.5,
            'ATT' => 1.15,
            'MED' => 1.1,
            'TD', 'TS' => 1.0,
            'DC' => 0.45,
            'POR' => 0.05,
            default => 1.0,
        };

        return max(0.1, $qualita * $bonusRuolo);
    }

    private function pesoRigore(array $giocatore): float
    {
        $attacco = (float) ($giocatore['Attacco'] ?? 0);
        $posizione = (string) ($giocatore['Posizione'] ?? '');

        $bonusRuolo = match ($posizione) {
            'ATT' => 2.3,
            'TRQ' => 1.9,
            'AS', 'AD' => 1.6,
            'CL', 'CR' => 1.3,
            'CC' => 1.0,
            'MED' => 0.75,
            default => 0.5,
        };

        return max(0.1, $attacco * $bonusRuolo);
    }

    private function pesoCartellino(array $giocatore): float
    {
        $attacco = (float) ($giocatore['Attacco'] ?? 0);
        $difesa = (float) ($giocatore['Difesa'] ?? 0);
        $posizione = (string) ($giocatore['Posizione'] ?? '');

        $bonusRuolo = match ($posizione) {
            'DC' => 2.1,
            'TD', 'TS' => 1.8,
            'MED' => 1.7,
            'CC' => 1.35,
            'CL', 'CR' => 1.1,
            'ATT', 'AS', 'AD', 'TRQ' => 0.8,
            'POR' => 0.7,
            default => 1.0,
        };

        return max(0.1, (($difesa * 1.5) + ($attacco * 0.15)) * $bonusRuolo);
    }

    private function pesoAutogol(array $giocatore): float
    {
        $difesa = (float) ($giocatore['Difesa'] ?? 0);
        $posizione = (string) ($giocatore['Posizione'] ?? '');

        $bonusRuolo = match ($posizione) {
            'POR' => 1.9,
            'DC' => 2.4,
            'TD', 'TS' => 1.8,
            'MED' => 1.15,
            default => 0.35,
        };

        return max(0.1, (($difesa * 1.3) + 1) * $bonusRuolo);
    }

    private function randomMinuto(): int
    {
        return random_int(1, 90);
    }

    private function chance(int $percent): bool
    {
        return random_int(1, 100) <= $percent;
    }

    private function json(array $payload): ?string
    {
        try {
            return json_encode(
                $payload,
                JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
            );
        } catch (JsonException) {
            return null;
        }
    }

    private function generaMinutiUniciGol(int $numeroGol): array
    {
        if ($numeroGol <= 0) {
            return [];
        }

        $pool = range(1, 90);
        shuffle($pool);

        $minuti = array_slice($pool, 0, min($numeroGol, count($pool)));
        sort($minuti);

        return $minuti;
    }
}
