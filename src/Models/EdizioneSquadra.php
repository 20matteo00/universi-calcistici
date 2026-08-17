<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\Database;
use PDO;

class EdizioneSquadra
{
    public function squadreEdizione(int $idEdizione): array
    {
        $pdo = Database::getConnessione();

        $statement = $pdo->prepare("
            SELECT
                es.IDEdizione,
                es.IDSquadra,
                es.Valore,
                es.FattoreCasa,
                s.Nome,
                s.Paese,
                s.Tipo
            FROM EdizioneSquadra es
            INNER JOIN Squadre s ON s.ID = es.IDSquadra
            WHERE es.IDEdizione = :idEdizione
            ORDER BY s.Nome ASC, s.ID ASC
        ");

        $statement->execute([
            'idEdizione' => $idEdizione,
        ]);

        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findEdizioneSquadra(int $idEdizione, int $idSquadra): ?array
    {
        $pdo = Database::getConnessione();

        $statement = $pdo->prepare("
            SELECT
                es.IDEdizione,
                es.IDSquadra,
                es.Valore,
                es.FattoreCasa,
                s.Nome,
                s.Paese,
                s.Tipo
            FROM EdizioneSquadra es
            INNER JOIN Squadre s ON s.ID = es.IDSquadra
            WHERE es.IDEdizione = :idEdizione
              AND es.IDSquadra = :idSquadra
            LIMIT 1
        ");

        $statement->execute([
            'idEdizione' => $idEdizione,
            'idSquadra' => $idSquadra,
        ]);

        $riga = $statement->fetch(PDO::FETCH_ASSOC);

        return $riga !== false ? $riga : null;
    }
}