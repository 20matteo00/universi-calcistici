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

        $transazioneApertaQui = false;

        try {
            if (!$pdo->inTransaction()) {
                $pdo->beginTransaction();
                $transazioneApertaQui = true;
            }

            foreach ($partite as $partita) {
                $statement->execute([
                    'idEdizioneCompetizione' => (int) $partita['id_edizione_competizione'],
                    'idSquadraCasa' => (int) $partita['id_squadra_casa'],
                    'idSquadraTrasferta' => (int) $partita['id_squadra_trasferta'],
                    'fase' => $partita['fase'] !== null ? (string) $partita['fase'] : null,
                    'giornata' => $partita['giornata'] !== null ? (int) $partita['giornata'] : null,
                    'girone' => $partita['girone'] ?? null,
                    'stato' => (string) $partita['stato'],
                    'dettagli' => $partita['dettagli'] ?? null,
                ]);
            }

            if ($transazioneApertaQui) {
                $pdo->commit();
            }
        } catch (\Throwable $e) {
            if ($transazioneApertaQui && $pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $e;
        }
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

    public function resetRisultatoPartita(int $idPartita): bool
    {
        return $this->aggiornaRisultatoPartita($idPartita, null, null, 'programmata');
    }
}