<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\Database;
use PDO;

class Competizione
{
    public function all(array $filtri = []): array
    {
        $pdo = Database::getConnessione();

        $sql = "
            SELECT
                c.ID,
                c.IDUniverso,
                c.NomeCompetizione,
                c.Tipo,
                c.NumeroPartecipanti,
                c.Giri,
                c.InizialmenteVuota,
                c.Struttura,
                c.Creato,
                c.Modificato,
                u.Nome AS UniversoNome
            FROM Competizioni c
            INNER JOIN Universi u ON u.ID = c.IDUniverso
            WHERE 1=1
        ";

        $params = [];

        if (($filtri['q'] ?? '') !== '') {
            $sql .= " AND c.NomeCompetizione LIKE :q";
            $params['q'] = '%' . $filtri['q'] . '%';
        }

        if (($filtri['id_universo'] ?? '') !== '') {
            $sql .= " AND c.IDUniverso = :id_universo";
            $params['id_universo'] = (int) $filtri['id_universo'];
        }

        if (($filtri['tipo'] ?? '') !== '') {
            $sql .= " AND c.Tipo = :tipo";
            $params['tipo'] = (string) $filtri['tipo'];
        }

        $sortMap = [
            'ID' => 'c.ID',
            'NomeCompetizione' => 'c.NomeCompetizione',
            'Tipo' => 'c.Tipo',
            'NumeroPartecipanti' => 'c.NumeroPartecipanti',
            'Giri' => 'c.Giri',
            'InizialmenteVuota' => 'c.InizialmenteVuota',
            'Universo' => 'u.Nome',
            'Creato' => 'c.Creato',
            'Modificato' => 'c.Modificato',
        ];

        $sort = (string) ($filtri['sort'] ?? 'ID');
        $dir = strtolower((string) ($filtri['dir'] ?? 'asc')) === 'desc' ? 'DESC' : 'ASC';
        $orderBy = $sortMap[$sort] ?? 'c.ID';

        $sql .= " ORDER BY {$orderBy} {$dir}, c.ID ASC";

        $statement = $pdo->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function allUniversi(): array
    {
        $pdo = Database::getConnessione();

        $statement = $pdo->query("
            SELECT ID, Nome
            FROM Universi
            ORDER BY Nome ASC, ID ASC
        ");

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $dati): int
    {
        $pdo = Database::getConnessione();

        $statement = $pdo->prepare("
            INSERT INTO Competizioni (
                IDUniverso,
                NomeCompetizione,
                Tipo,
                NumeroPartecipanti,
                Giri,
                InizialmenteVuota,
                Struttura
            )
            VALUES (
                :id_universo,
                :nome_competizione,
                :tipo,
                :numero_partecipanti,
                :giri,
                :inizialmente_vuota,
                :struttura
            )
        ");

        $statement->bindValue(':id_universo', (int) $dati['id_universo'], PDO::PARAM_INT);
        $statement->bindValue(':nome_competizione', (string) $dati['nome_competizione'], PDO::PARAM_STR);
        $statement->bindValue(':tipo', (string) $dati['tipo'], PDO::PARAM_STR);
        $statement->bindValue(':numero_partecipanti', (int) $dati['numero_partecipanti'], PDO::PARAM_INT);
        $statement->bindValue(':giri', max(1, (int) ($dati['giri'] ?? 1)), PDO::PARAM_INT);
        $statement->bindValue(':inizialmente_vuota', !empty($dati['inizialmente_vuota']) ? 1 : 0, PDO::PARAM_INT);

        if (($dati['struttura'] ?? null) === null) {
            $statement->bindValue(':struttura', null, PDO::PARAM_NULL);
        } else {
            $statement->bindValue(':struttura', (string) $dati['struttura'], PDO::PARAM_STR);
        }

        $statement->execute();

        return (int) $pdo->lastInsertId();
    }

    public function update(int $id, array $dati): void
    {
        $pdo = Database::getConnessione();

        $statement = $pdo->prepare("
            UPDATE Competizioni
            SET
                IDUniverso = :id_universo,
                NomeCompetizione = :nome_competizione,
                Tipo = :tipo,
                NumeroPartecipanti = :numero_partecipanti,
                Giri = :giri,
                InizialmenteVuota = :inizialmente_vuota,
                Struttura = :struttura
            WHERE ID = :id
        ");

        $statement->bindValue(':id', $id, PDO::PARAM_INT);
        $statement->bindValue(':id_universo', (int) $dati['id_universo'], PDO::PARAM_INT);
        $statement->bindValue(':nome_competizione', (string) $dati['nome_competizione'], PDO::PARAM_STR);
        $statement->bindValue(':tipo', (string) $dati['tipo'], PDO::PARAM_STR);
        $statement->bindValue(':numero_partecipanti', (int) $dati['numero_partecipanti'], PDO::PARAM_INT);
        $statement->bindValue(':giri', max(1, (int) ($dati['giri'] ?? 1)), PDO::PARAM_INT);
        $statement->bindValue(':inizialmente_vuota', !empty($dati['inizialmente_vuota']) ? 1 : 0, PDO::PARAM_INT);

        if (($dati['struttura'] ?? null) === null) {
            $statement->bindValue(':struttura', null, PDO::PARAM_NULL);
        } else {
            $statement->bindValue(':struttura', (string) $dati['struttura'], PDO::PARAM_STR);
        }

        $statement->execute();
    }

    public function delete(int $id): void
    {
        $pdo = Database::getConnessione();

        $statement = $pdo->prepare("DELETE FROM Competizioni WHERE ID = :id");
        $statement->execute(['id' => $id]);
    }

    public function allByUniverso(int $idUniverso, array $filtri = []): array
    {
        $pdo = Database::getConnessione();

        $sql = "
            SELECT
                c.ID,
                c.IDUniverso,
                c.NomeCompetizione,
                c.Tipo,
                c.NumeroPartecipanti,
                c.Giri,
                c.InizialmenteVuota,
                c.Struttura,
                c.Creato,
                c.Modificato
            FROM Competizioni c
            WHERE c.IDUniverso = :id_universo
        ";

        $params = [
            'id_universo' => $idUniverso,
        ];

        if (($filtri['q'] ?? '') !== '') {
            $sql .= " AND c.NomeCompetizione LIKE :q";
            $params['q'] = '%' . $filtri['q'] . '%';
        }

        if (($filtri['tipo'] ?? '') !== '') {
            $sql .= " AND c.Tipo = :tipo";
            $params['tipo'] = (string) $filtri['tipo'];
        }

        $sortMap = [
            'ID' => 'c.ID',
            'NomeCompetizione' => 'c.NomeCompetizione',
            'Tipo' => 'c.Tipo',
            'NumeroPartecipanti' => 'c.NumeroPartecipanti',
            'Giri' => 'c.Giri',
            'InizialmenteVuota' => 'c.InizialmenteVuota',
            'Creato' => 'c.Creato',
            'Modificato' => 'c.Modificato',
        ];

        $sort = (string) ($filtri['sort'] ?? 'ID');
        $dir = strtolower((string) ($filtri['dir'] ?? 'asc')) === 'desc' ? 'DESC' : 'ASC';
        $orderBy = $sortMap[$sort] ?? 'c.ID';

        $sql .= " ORDER BY {$orderBy} {$dir}, c.ID ASC";

        $statement = $pdo->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByUniverso(int $idUniverso, int $idCompetizione): ?array
    {
        $pdo = Database::getConnessione();

        $statement = $pdo->prepare("
            SELECT
                c.ID,
                c.IDUniverso,
                c.NomeCompetizione,
                c.Tipo,
                c.NumeroPartecipanti,
                c.Giri,
                c.InizialmenteVuota,
                c.Struttura,
                c.Creato,
                c.Modificato,
                u.Nome AS UniversoNome
            FROM Competizioni c
            INNER JOIN Universi u ON u.ID = c.IDUniverso
            WHERE c.ID = :id_competizione
              AND c.IDUniverso = :id_universo
            LIMIT 1
        ");

        $statement->execute([
            'id_competizione' => $idCompetizione,
            'id_universo' => $idUniverso,
        ]);

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }
}