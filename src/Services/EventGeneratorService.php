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
    private array $minutiUsati = [];

    public function __construct()
    {
        $this->pdo = Database::getConnessione();
        $this->partite = new Partita();
        $this->partitaEventi = new PartitaEvento();
    }

    public function rigeneraPerPartita(int $idPartita): void
    {
        $partita = $this->caricaPartita($idPartita);
        $this->minutiUsati = [];

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

        $this->partitaEventi->deleteByPartita($idPartita);

        $eventi = [];

        $eventi = array_merge(
            $eventi,
            $this->generaGolSquadra(
                $idPartita,
                $idSquadraCasa,
                $idSquadraTrasferta,
                (int) $goalCasa,
                $rosaCasa,
                $rosaTrasferta
            )
        );

        $eventi = array_merge(
            $eventi,
            $this->generaGolSquadra(
                $idPartita,
                $idSquadraTrasferta,
                $idSquadraCasa,
                (int) $goalTrasferta,
                $rosaTrasferta,
                $rosaCasa
            )
        );

        $eventi = array_merge(
            $eventi,
            $this->generaCartelliniSquadra($idPartita, $idSquadraCasa, $rosaCasa),
            $this->generaCartelliniSquadra($idPartita, $idSquadraTrasferta, $rosaTrasferta)
        );

        if ($this->chance(8)) {
            $squadraRigore = $this->chance(50) ? $idSquadraCasa : $idSquadraTrasferta;
            $rosaRigore = $squadraRigore === $idSquadraCasa ? $rosaCasa : $rosaTrasferta;

            $rigorista = $this->pickWeightedPlayerId(
                $rosaRigore,
                fn(array $g): float => $this->pesoRigore($g)
            );

            $eventi[] = [
                'IDPartita' => $idPartita,
                'IDGiocatore' => $rigorista,
                'IDSquadra' => $squadraRigore,
                'Tipo' => 'rigore_sbagliato',
                'Minuto' => $this->randomMinuto(),
                'Dettagli' => null,
            ];
        }

        usort($eventi, static function (array $a, array $b): int {
            return ($a['Minuto'] ?? 0) <=> ($b['Minuto'] ?? 0);
        });

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
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id_edizione' => $idEdizione,
            'id_squadra' => $idSquadra,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function generaGolSquadra(
        int $idPartita,
        int $idSquadraBeneficio,
        int $idSquadraAvversaria,
        int $numeroGol,
        array $rosaBeneficio,
        array $rosaAvversaria
    ): array {
        $eventi = [];

        for ($i = 0; $i < $numeroGol; $i++) {
            $roll = random_int(1, 100);
            $minuto = $this->randomMinuto();

            if ($roll <= 1) {
                $autoreAutogol = $this->pickWeightedPlayerId(
                    $rosaAvversaria,
                    fn(array $g): float => $this->pesoAutogol($g)
                );

                $eventi[] = [
                    'IDPartita' => $idPartita,
                    'IDGiocatore' => $autoreAutogol,
                    'IDSquadra' => $idSquadraBeneficio,
                    'Tipo' => 'gol',
                    'Minuto' => $minuto,
                    'Dettagli' => $this->json(['autogol' => true]),
                ];
                continue;
            }

            if ($roll <= 6) {
                $rigorista = $this->pickWeightedPlayerId(
                    $rosaBeneficio,
                    fn(array $g): float => $this->pesoRigore($g)
                );

                $eventi[] = [
                    'IDPartita' => $idPartita,
                    'IDGiocatore' => $rigorista,
                    'IDSquadra' => $idSquadraBeneficio,
                    'Tipo' => 'gol',
                    'Minuto' => $minuto,
                    'Dettagli' => $this->json(['rigore' => true]),
                ];
                continue;
            }

            $marcatore = $this->pickWeightedPlayerId(
                $rosaBeneficio,
                fn(array $g): float => $this->pesoGol($g)
            );

            if ($marcatore === null) {
                $marcatore = $this->pickRandomPlayerId($rosaBeneficio);
            }

            if ($marcatore === null) {
                continue;
            }

            if ($roll <= 26) {
                $eventi[] = [
                    'IDPartita' => $idPartita,
                    'IDGiocatore' => $marcatore,
                    'IDSquadra' => $idSquadraBeneficio,
                    'Tipo' => 'gol',
                    'Minuto' => $minuto,
                    'Dettagli' => null,
                ];
                continue;
            }

            $giocatoriAssist = array_values(array_filter(
                $rosaBeneficio,
                static fn(array $g): bool => (int) ($g['ID'] ?? 0) !== $marcatore
            ));

            $assist = $this->pickWeightedPlayerId(
                $giocatoriAssist,
                fn(array $g): float => $this->pesoAssist($g)
            );

            $eventi[] = [
                'IDPartita' => $idPartita,
                'IDGiocatore' => $marcatore,
                'IDSquadra' => $idSquadraBeneficio,
                'Tipo' => 'gol',
                'Minuto' => $minuto,
                'Dettagli' => $assist !== null
                    ? $this->json(['assist_id' => $assist])
                    : null,
            ];
        }

        return $eventi;
    }

    private function generaCartelliniSquadra(int $idPartita, int $idSquadra, array $rosa): array
    {
        $eventi = [];
        $giaAmmoniti = [];

        $numeroGialli = random_int(0, 3);

        for ($i = 0; $i < $numeroGialli; $i++) {
            $candidati = array_values(array_filter(
                $rosa,
                static fn(array $g): bool => !in_array((int) ($g['ID'] ?? 0), $giaAmmoniti, true)
            ));

            $giocatoreId = $this->pickWeightedPlayerId(
                $candidati,
                fn(array $g): float => $this->pesoCartellino($g)
            );

            if ($giocatoreId === null) {
                break;
            }

            $giaAmmoniti[] = $giocatoreId;

            $eventi[] = [
                'IDPartita' => $idPartita,
                'IDGiocatore' => $giocatoreId,
                'IDSquadra' => $idSquadra,
                'Tipo' => 'ammonizione',
                'Minuto' => $this->randomMinuto(),
                'Dettagli' => null,
            ];
        }

        if ($this->chance(7)) {
            $candidatiEspulsione = array_values(array_filter(
                $rosa,
                static fn(array $g): bool => !in_array((int) ($g['ID'] ?? 0), $giaAmmoniti, true)
            ));

            $espulso = $this->pickWeightedPlayerId(
                $candidatiEspulsione,
                fn(array $g): float => $this->pesoCartellino($g)
            );

            if ($espulso !== null) {
                $eventi[] = [
                    'IDPartita' => $idPartita,
                    'IDGiocatore' => $espulso,
                    'IDSquadra' => $idSquadra,
                    'Tipo' => 'espulsione',
                    'Minuto' => $this->randomMinuto(),
                    'Dettagli' => null,
                ];
            }
        }

        return $eventi;
    }

    private function pickRandomPlayerId(array $giocatori): ?int
    {
        if ($giocatori === []) {
            return null;
        }

        $index = array_rand($giocatori);

        return isset($giocatori[$index]['ID']) ? (int) $giocatori[$index]['ID'] : null;
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

    private function pesoGol(array $giocatore): float
    {
        $attacco = (float) ($giocatore['Attacco'] ?? 0);
        $difesa = (float) ($giocatore['Difesa'] ?? 0);
        $posizione = (string) ($giocatore['Posizione'] ?? '');

        $bonusRuolo = match ($posizione) {
            'ATT' => 2.2,
            'AS', 'AD' => 1.8,
            'TRQ' => 1.7,
            'CL', 'CR' => 1.35,
            'CC' => 1.15,
            'MED' => 0.9,
            'TD', 'TS' => 0.75,
            'DC' => 0.55,
            'POR' => 0.1,
            default => 1.0,
        };

        return max(0.1, ($attacco * 1.8 + $difesa * 0.2) * $bonusRuolo);
    }

    private function pesoAssist(array $giocatore): float
    {
        $attacco = (float) ($giocatore['Attacco'] ?? 0);
        $difesa = (float) ($giocatore['Difesa'] ?? 0);
        $posizione = (string) ($giocatore['Posizione'] ?? '');

        $bonusRuolo = match ($posizione) {
            'TRQ' => 2.0,
            'AS', 'AD' => 1.9,
            'CL', 'CR' => 1.7,
            'CC' => 1.5,
            'ATT' => 1.3,
            'MED' => 1.1,
            'TD', 'TS' => 1.15,
            'DC' => 0.45,
            'POR' => 0.05,
            default => 1.0,
        };

        return max(0.1, (($attacco * 1.2) + ($difesa * 0.45)) * $bonusRuolo);
    }

    private function pesoRigore(array $giocatore): float
    {
        $attacco = (float) ($giocatore['Attacco'] ?? 0);
        $posizione = (string) ($giocatore['Posizione'] ?? '');

        $bonusRuolo = match ($posizione) {
            'ATT' => 2.0,
            'TRQ' => 1.8,
            'AS', 'AD' => 1.6,
            'CL', 'CR' => 1.25,
            'CC' => 1.0,
            default => 0.6,
        };

        return max(0.1, $attacco * $bonusRuolo);
    }

    private function pesoCartellino(array $giocatore): float
    {
        $attacco = (float) ($giocatore['Attacco'] ?? 0);
        $difesa = (float) ($giocatore['Difesa'] ?? 0);
        $posizione = (string) ($giocatore['Posizione'] ?? '');

        $bonusRuolo = match ($posizione) {
            'DC' => 2.0,
            'TD', 'TS' => 1.8,
            'MED' => 1.7,
            'CC' => 1.35,
            'CL', 'CR' => 1.1,
            'ATT', 'AS', 'AD', 'TRQ' => 0.8,
            'POR' => 0.7,
            default => 1.0,
        };

        return max(0.1, (($difesa * 1.4) + ($attacco * 0.2)) * $bonusRuolo);
    }

    private function pesoAutogol(array $giocatore): float
    {
        $difesa = (float) ($giocatore['Difesa'] ?? 0);
        $posizione = (string) ($giocatore['Posizione'] ?? '');

        $bonusRuolo = match ($posizione) {
            'POR' => 1.8,
            'DC' => 2.2,
            'TD', 'TS' => 1.7,
            'MED' => 1.2,
            default => 0.35,
        };

        return max(0.1, ($difesa + 1) * $bonusRuolo);
    }

    private function randomMinuto(): int
    {
        if (count($this->minutiUsati) >= 90) {
            return 90;
        }

        do {
            $minuto = random_int(1, 90);
        } while (in_array($minuto, $this->minutiUsati, true));

        $this->minutiUsati[] = $minuto;

        return $minuto;
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
}