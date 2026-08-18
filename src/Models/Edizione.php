<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\Database;
use PDO;

class Edizione
{
    public function allByUniverso(int $idUniverso): array
    {
        $pdo = Database::getConnessione();

        $statement = $pdo->prepare("
            SELECT *
            FROM Edizioni
            WHERE IDUniverso = :idUniverso
            ORDER BY Anno ASC, ID ASC
        ");

        $statement->execute([
            'idUniverso' => $idUniverso,
        ]);

        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function find(int $id): ?array
    {
        $pdo = Database::getConnessione();

        $statement = $pdo->prepare("
            SELECT *
            FROM Edizioni
            WHERE ID = :id
            LIMIT 1
        ");

        $statement->execute([
            'id' => $id,
        ]);

        $edizione = $statement->fetch(PDO::FETCH_ASSOC);

        return $edizione !== false ? $edizione : null;
    }

    public function haEdizioniPerUniverso(int $idUniverso): bool
    {
        $pdo = Database::getConnessione();

        $statement = $pdo->prepare("
            SELECT 1
            FROM Edizioni
            WHERE IDUniverso = :idUniverso
            LIMIT 1
        ");

        $statement->execute([
            'idUniverso' => $idUniverso,
        ]);

        return (bool) $statement->fetchColumn();
    }

    public function create(array $data): int
    {
        $pdo = Database::getConnessione();

        $statement = $pdo->prepare("
            INSERT INTO Edizioni (IDUniverso, Anno, Nome, Stato)
            VALUES (:idUniverso, :anno, :nome, :stato)
        ");

        $statement->execute([
            'idUniverso' => (int) ($data['id_universo'] ?? 0),
            'anno' => (int) ($data['anno'] ?? 0),
            'nome' => trim((string) ($data['nome'] ?? '')),
            'stato' => trim((string) ($data['stato'] ?? 'bozza')),
        ]);

        return (int) $pdo->lastInsertId();
    }

    public function delete(int $id): bool
    {
        $pdo = Database::getConnessione();

        $statement = $pdo->prepare("
            DELETE FROM Edizioni
            WHERE ID = :id
            LIMIT 1
        ");

        return $statement->execute([
            'id' => $id,
        ]);
    }

    public function isUltimaEdizione(int $idUniverso, int $idEdizione): bool
    {
        $pdo = Database::getConnessione();

        $statement = $pdo->prepare("
        SELECT ID
        FROM Edizioni
        WHERE IDUniverso = :idUniverso
        ORDER BY Anno DESC, ID DESC
        LIMIT 1
    ");

        $statement->execute([
            'idUniverso' => $idUniverso,
        ]);

        $ultimoId = (int) ($statement->fetchColumn() ?: 0);

        return $ultimoId === $idEdizione;
    }

    public function aggiornaStato(int $idEdizione, string $stato): void
    {
        $pdo = Database::getConnessione();

        $statement = $pdo->prepare("
        UPDATE Edizioni
        SET Stato = :stato
        WHERE ID = :id
        LIMIT 1
    ");

        $statement->execute([
            'id' => $idEdizione,
            'stato' => trim($stato),
        ]);
    }
}
