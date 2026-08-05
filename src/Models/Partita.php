<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\Database;
use PDO;

class Partita
{
    public function contaPartitePerCompetizione(int $idEdizioneCompetizione): int
    {
        $pdo = Database::getConnessione();

        $statement = $pdo->prepare("
            SELECT COUNT(*)
            FROM Partite
            WHERE IDEdizioneCompetizione = :idEdizioneCompetizione
        ");

        $statement->execute([
            'idEdizioneCompetizione' => $idEdizioneCompetizione,
        ]);

        return (int) $statement->fetchColumn();
    }

    public function creaPartiteBatch(array $partite): void
    {
        if ($partite === []) {
            return;
        }

        $pdo = Database::getConnessione();

        $statement = $pdo->prepare("
            INSERT INTO Partite (
                IDEdizioneCompetizione,
                IDSquadraCasa,
                IDSquadraTrasferta,
                GoalCasa,
                GoalTrasferta,
                Fase,
                Giornata,
                Girone,
                Data,
                Stato,
                Dettagli
            ) VALUES (
                :idEdizioneCompetizione,
                :idSquadraCasa,
                :idSquadraTrasferta,
                NULL,
                NULL,
                :fase,
                :giornata,
                :girone,
                NULL,
                :stato,
                :dettagli
            )
        ");

        try {
            $pdo->beginTransaction();

            foreach ($partite as $partita) {
                $statement->execute([
                    'idEdizioneCompetizione' => (int) $partita['id_edizione_competizione'],
                    'idSquadraCasa' => (int) $partita['id_squadra_casa'],
                    'idSquadraTrasferta' => (int) $partita['id_squadra_trasferta'],
                    'fase' => (string) $partita['fase'],
                    'giornata' => $partita['giornata'] !== null ? (int) $partita['giornata'] : null,
                    'girone' => $partita['girone'],
                    'stato' => (string) $partita['stato'],
                    'dettagli' => $partita['dettagli'],
                ]);
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $e;
        }
    }

    public function partitePerCompetizione(int $idEdizioneCompetizione): array
    {
        $pdo = Database::getConnessione();

        $statement = $pdo->prepare("
            SELECT
                p.ID,
                p.IDEdizioneCompetizione,
                p.IDSquadraCasa,
                p.IDSquadraTrasferta,
                p.GoalCasa,
                p.GoalTrasferta,
                p.Fase,
                p.Giornata,
                p.Girone,
                p.Data,
                p.Stato,
                p.Dettagli,
                p.Creato,
                p.Modificato,
                sc.Nome AS NomeSquadraCasa,
                st.Nome AS NomeSquadraTrasferta
            FROM Partite p
            INNER JOIN Squadre sc ON sc.ID = p.IDSquadraCasa
            INNER JOIN Squadre st ON st.ID = p.IDSquadraTrasferta
            WHERE p.IDEdizioneCompetizione = :idEdizioneCompetizione
            ORDER BY p.Giornata ASC, p.ID ASC
        ");

        $statement->execute([
            'idEdizioneCompetizione' => $idEdizioneCompetizione,
        ]);

        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function aggiornaRisultatoPartita(
        int $idPartita,
        ?int $goalCasa,
        ?int $goalTrasferta,
        string $stato = 'giocata'
    ): bool {
        $pdo = Database::getConnessione();

        $statement = $pdo->prepare("
            UPDATE Partite
            SET
                GoalCasa = :goalCasa,
                GoalTrasferta = :goalTrasferta,
                Stato = :stato
            WHERE ID = :id
            LIMIT 1
        ");

        return $statement->execute([
            'id' => $idPartita,
            'goalCasa' => $goalCasa,
            'goalTrasferta' => $goalTrasferta,
            'stato' => $stato,
        ]);
    }

    public function find(int $idPartita): ?array
    {
        $pdo = Database::getConnessione();

        $statement = $pdo->prepare("
            SELECT *
            FROM Partite
            WHERE ID = :id
            LIMIT 1
        ");

        $statement->execute([
            'id' => $idPartita,
        ]);

        $riga = $statement->fetch(PDO::FETCH_ASSOC);

        return $riga !== false ? $riga : null;
    }

    public function partiteRaggruppatePerGiornata(int $idEdizioneCompetizione): array
    {
        $partite = $this->partitePerCompetizione($idEdizioneCompetizione);
        $giornate = [];

        foreach ($partite as $partita) {
            $giornata = (int) ($partita['Giornata'] ?? 0);

            if (!isset($giornate[$giornata])) {
                $giornate[$giornata] = [];
            }

            $giornate[$giornata][] = $partita;
        }

        ksort($giornate);

        return $giornate;
    }
}