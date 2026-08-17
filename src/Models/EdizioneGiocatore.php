<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\Database;
use PDO;

class EdizioneGiocatore
{
    public function giocatoriEdizione(int $idEdizione): array
    {
        $pdo = Database::getConnessione();

        $statement = $pdo->prepare("
            SELECT
                eg.IDEdizione,
                eg.IDGiocatore,
                eg.Attacco,
                eg.Difesa,
                g.Nome,
                g.Paese,
                g.Posizione,
                g.Nascita
            FROM EdizioneGiocatore eg
            INNER JOIN Giocatori g ON g.ID = eg.IDGiocatore
            WHERE eg.IDEdizione = :idEdizione
            ORDER BY g.Nome ASC, g.ID ASC
        ");

        $statement->execute([
            'idEdizione' => $idEdizione,
        ]);

        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function haGiocatoriEdizione(int $idEdizione): bool
    {
        $pdo = Database::getConnessione();

        $statement = $pdo->prepare("
            SELECT 1
            FROM EdizioneGiocatore
            WHERE IDEdizione = :idEdizione
            LIMIT 1
        ");

        $statement->execute([
            'idEdizione' => $idEdizione,
        ]);

        return (bool) $statement->fetchColumn();
    }

    public function giocatoriAssegnatiASquadra(int $idEdizione, int $idSquadra): array
    {
        $pdo = Database::getConnessione();

        $statement = $pdo->prepare("
            SELECT
                esg.IDEdizione,
                esg.IDSquadra,
                esg.IDGiocatore,
                g.Nome,
                g.Paese,
                g.Posizione,
                eg.Attacco,
                eg.Difesa
            FROM EdizioneSquadraGiocatore esg
            INNER JOIN Giocatori g ON g.ID = esg.IDGiocatore
            INNER JOIN EdizioneGiocatore eg
                ON eg.IDEdizione = esg.IDEdizione
               AND eg.IDGiocatore = esg.IDGiocatore
            WHERE esg.IDEdizione = :idEdizione
              AND esg.IDSquadra = :idSquadra
            ORDER BY g.Nome ASC, g.ID ASC
        ");

        $statement->execute([
            'idEdizione' => $idEdizione,
            'idSquadra' => $idSquadra,
        ]);

        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function giocatoriDisponibiliPerSquadra(int $idEdizione, int $idSquadra): array
    {
        $pdo = Database::getConnessione();

        $statement = $pdo->prepare("
            SELECT
                eg.IDEdizione,
                eg.IDGiocatore,
                eg.Attacco,
                eg.Difesa,
                g.Nome,
                g.Paese,
                g.Posizione,
                g.Nascita
            FROM EdizioneGiocatore eg
            INNER JOIN Giocatori g ON g.ID = eg.IDGiocatore
            WHERE eg.IDEdizione = :idEdizione
              AND NOT EXISTS (
                  SELECT 1
                  FROM EdizioneSquadraGiocatore esg
                  WHERE esg.IDEdizione = eg.IDEdizione
                    AND esg.IDGiocatore = eg.IDGiocatore
                    AND esg.IDSquadra <> :idSquadra
              )
            ORDER BY g.Nome ASC, g.ID ASC
        ");

        $statement->execute([
            'idEdizione' => $idEdizione,
            'idSquadra' => $idSquadra,
        ]);

        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function trovaGiocatoriNonAssegnati(int $idEdizione): array
    {
        $pdo = Database::getConnessione();

        $statement = $pdo->prepare("
            SELECT
                eg.IDEdizione,
                eg.IDGiocatore,
                eg.Attacco,
                eg.Difesa,
                g.Nome,
                g.Paese,
                g.Posizione
            FROM EdizioneGiocatore eg
            INNER JOIN Giocatori g ON g.ID = eg.IDGiocatore
            WHERE eg.IDEdizione = :idEdizione
              AND NOT EXISTS (
                  SELECT 1
                  FROM EdizioneSquadraGiocatore esg
                  WHERE esg.IDEdizione = eg.IDEdizione
                    AND esg.IDGiocatore = eg.IDGiocatore
              )
            ORDER BY g.Nome ASC, g.ID ASC
        ");

        $statement->execute([
            'idEdizione' => $idEdizione,
        ]);

        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function salvaRosaSquadra(int $idEdizione, int $idSquadra, array $idsGiocatori): void
    {
        $pdo = Database::getConnessione();

        $idsGiocatori = array_values(
            array_unique(
                array_filter(
                    array_map('intval', $idsGiocatori),
                    fn(int $id): bool => $id > 0
                )
            )
        );

        try {
            $pdo->beginTransaction();

            $delete = $pdo->prepare("
                DELETE FROM EdizioneSquadraGiocatore
                WHERE IDEdizione = :idEdizione
                  AND IDSquadra = :idSquadra
            ");

            $delete->execute([
                'idEdizione' => $idEdizione,
                'idSquadra' => $idSquadra,
            ]);

            if ($idsGiocatori !== []) {
                $insert = $pdo->prepare("
                    INSERT INTO EdizioneSquadraGiocatore (IDEdizione, IDSquadra, IDGiocatore)
                    VALUES (:idEdizione, :idSquadra, :idGiocatore)
                ");

                foreach ($idsGiocatori as $idGiocatore) {
                    $insert->execute([
                        'idEdizione' => $idEdizione,
                        'idSquadra' => $idSquadra,
                        'idGiocatore' => $idGiocatore,
                    ]);
                }
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $e;
        }
    }
}