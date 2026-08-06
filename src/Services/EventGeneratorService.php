<?php

declare(strict_types=1);

namespace App\Services;

use App\Config\Database;
use App\Models\Partita;
use App\Models\PartitaEvento;
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

        $goalCasa = $partita['GoalCasa'];
        $goalTrasferta = $partita['GoalTrasferta'];

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

            $eventi[] = [
                'IDPartita' => $idPartita,
                'IDGiocatore' => $this->pickRandomPlayerId($rosaRigore),
                'IDSquadra' => $squadraRigore,
                'Tipo' => 'rigore_sbagliato',
                'Minuto' => $this->randomMinuto(),
                'Dettagli' => null,
            ];
        }

        usort($eventi, static function (array $a, array $b): int {
            if ($a['Minuto'] === $b['Minuto']) {
                return 0;
            }

            return $a['Minuto'] <=> $b['Minuto'];
        });

        foreach ($eventi as $evento) {
            $this->partitaEventi->create($evento);
        }
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
            SELECT g.ID, g.Nome
            FROM EdizioneSquadraGiocatore esg
            INNER JOIN Giocatori g ON g.ID = esg.IDGiocatore
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
                $autoreAutogol = $this->pickRandomPlayerId($rosaAvversaria);

                $eventi[] = [
                    'IDPartita' => $idPartita,
                    'IDGiocatore' => $autoreAutogol,
                    'IDSquadra' => $idSquadraBeneficio,
                    'Tipo' => 'gol',
                    'Minuto' => $minuto,
                    'Dettagli' => json_encode(['autogol' => true], JSON_UNESCAPED_UNICODE),
                ];
                continue;
            }

            if ($roll <= 6) {
                $rigorista = $this->pickRandomPlayerId($rosaBeneficio);

                $eventi[] = [
                    'IDPartita' => $idPartita,
                    'IDGiocatore' => $rigorista,
                    'IDSquadra' => $idSquadraBeneficio,
                    'Tipo' => 'gol',
                    'Minuto' => $minuto,
                    'Dettagli' => json_encode(['rigore' => true], JSON_UNESCAPED_UNICODE),
                ];
                continue;
            }

            if ($roll <= 26) {
                $marcatore = $this->pickRandomPlayerId($rosaBeneficio);

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

            $marcatore = $this->pickRandomPlayerId($rosaBeneficio);
            $assist = $this->pickDifferentPlayerId($rosaBeneficio, $marcatore);

            if ($assist === null) {
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

            $eventi[] = [
                'IDPartita' => $idPartita,
                'IDGiocatore' => $marcatore,
                'IDSquadra' => $idSquadraBeneficio,
                'Tipo' => 'gol',
                'Minuto' => $minuto,
                'Dettagli' => json_encode(['assist_id' => $assist], JSON_UNESCAPED_UNICODE),
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
            $giocatoreId = $this->pickRandomPlayerIdExcluding($rosa, $giaAmmoniti);

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
                static fn(array $g): bool => !in_array((int) $g['ID'], $giaAmmoniti, true)
            ));

            $espulso = $this->pickRandomPlayerId($candidatiEspulsione);

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

    private function pickDifferentPlayerId(array $giocatori, ?int $excludedId): ?int
    {
        $filtrati = array_values(array_filter(
            $giocatori,
            static fn(array $g): bool => (int) ($g['ID'] ?? 0) !== (int) $excludedId
        ));

        return $this->pickRandomPlayerId($filtrati);
    }

    private function pickRandomPlayerIdExcluding(array $giocatori, array $excludedIds): ?int
    {
        $filtrati = array_values(array_filter(
            $giocatori,
            static fn(array $g): bool => !in_array((int) ($g['ID'] ?? 0), $excludedIds, true)
        ));

        return $this->pickRandomPlayerId($filtrati);
    }

    private function randomMinuto(): int
    {
        return random_int(1, 90);
    }

    private function chance(int $percent): bool
    {
        return random_int(1, 100) <= $percent;
    }

    public function cancellaPerPartita(int $idPartita): void
    {
        $this->partitaEventi->deleteByPartita($idPartita);
    }
}
