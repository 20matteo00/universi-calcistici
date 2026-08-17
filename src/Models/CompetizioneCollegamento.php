<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\Database;
use PDO;

class CompetizioneCollegamento
{
    public function allByUniverso(int $idUniverso, array $filtri = []): array
    {
        $pdo = Database::getConnessione();

        $sql = "
        SELECT
            ca.ID,
            ca.IDCompetizionePartenza,
            ca.IDCompetizioneArrivo,
            ca.Ordine,
            ca.Dettagli,
            ca.Creato,
            ca.Modificato,
            cp.NomeCompetizione AS CompetizionePartenzaNome,
            cp.Tipo AS CompetizionePartenzaTipo,
            ca2.NomeCompetizione AS CompetizioneArrivoNome,
            ca2.Tipo AS CompetizioneArrivoTipo
        FROM CompetizioneCollegamento ca
        INNER JOIN Competizioni cp ON cp.ID = ca.IDCompetizionePartenza
        INNER JOIN Competizioni ca2 ON ca2.ID = ca.IDCompetizioneArrivo
        WHERE cp.IDUniverso = :id_universo_partenza
          AND ca2.IDUniverso = :id_universo_arrivo
    ";

        $params = [
            'id_universo_partenza' => $idUniverso,
            'id_universo_arrivo' => $idUniverso,
        ];

        if (($filtri['q'] ?? '') !== '') {
            $sql .= " AND (
            cp.NomeCompetizione LIKE :q_nome_partenza
            OR ca2.NomeCompetizione LIKE :q_nome_arrivo
            OR ca.Dettagli LIKE :q_dettagli
        )";
            $params['q_nome_partenza'] = '%' . $filtri['q'] . '%';
            $params['q_nome_arrivo'] = '%' . $filtri['q'] . '%';
            $params['q_dettagli'] = '%' . $filtri['q'] . '%';
        }

        $sortMap = [
            'ID' => 'ca.ID',
            'Ordine' => 'ca.Ordine',
            'CompetizionePartenza' => 'cp.NomeCompetizione',
            'CompetizioneArrivo' => 'ca2.NomeCompetizione',
            'Creato' => 'ca.Creato',
            'Modificato' => 'ca.Modificato',
        ];

        $sort = (string) ($filtri['sort'] ?? 'Ordine');
        $dir = strtolower((string) ($filtri['dir'] ?? 'asc')) === 'desc' ? 'DESC' : 'ASC';
        $orderBy = $sortMap[$sort] ?? 'ca.Ordine';

        $sql .= " ORDER BY {$orderBy} {$dir}, ca.ID ASC";

        $statement = $pdo->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByUniverso(int $idUniverso, int $idCollegamento): ?array
    {
        $pdo = Database::getConnessione();

        $statement = $pdo->prepare("
        SELECT
            ca.ID,
            ca.IDCompetizionePartenza,
            ca.IDCompetizioneArrivo,
            ca.Ordine,
            ca.Dettagli,
            ca.Creato,
            ca.Modificato
        FROM CompetizioneCollegamento ca
        INNER JOIN Competizioni cp ON cp.ID = ca.IDCompetizionePartenza
        INNER JOIN Competizioni ca2 ON ca2.ID = ca.IDCompetizioneArrivo
        WHERE ca.ID = :id
          AND cp.IDUniverso = :id_universo_partenza
          AND ca2.IDUniverso = :id_universo_arrivo
        LIMIT 1
    ");

        $statement->execute([
            'id' => $idCollegamento,
            'id_universo_partenza' => $idUniverso,
            'id_universo_arrivo' => $idUniverso,
        ]);

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function create(array $dati): int
    {
        $pdo = Database::getConnessione();

        $statement = $pdo->prepare("
            INSERT INTO CompetizioneCollegamento (
                IDCompetizionePartenza,
                IDCompetizioneArrivo,
                Ordine,
                Dettagli
            ) VALUES (
                :id_competizione_partenza,
                :id_competizione_arrivo,
                :ordine,
                :dettagli
            )
        ");

        $statement->bindValue(':id_competizione_partenza', (int) $dati['id_competizione_partenza'], PDO::PARAM_INT);
        $statement->bindValue(':id_competizione_arrivo', (int) $dati['id_competizione_arrivo'], PDO::PARAM_INT);
        $statement->bindValue(':ordine', (int) $dati['ordine'], PDO::PARAM_INT);

        if ($dati['dettagli'] === null) {
            $statement->bindValue(':dettagli', null, PDO::PARAM_NULL);
        } else {
            $statement->bindValue(':dettagli', (string) $dati['dettagli'], PDO::PARAM_STR);
        }

        $statement->execute();

        return (int) $pdo->lastInsertId();
    }

    public function update(int $id, array $dati): void
    {
        $pdo = Database::getConnessione();

        $statement = $pdo->prepare("
            UPDATE CompetizioneCollegamento
            SET
                IDCompetizionePartenza = :id_competizione_partenza,
                IDCompetizioneArrivo = :id_competizione_arrivo,
                Ordine = :ordine,
                Dettagli = :dettagli
            WHERE ID = :id
        ");

        $statement->bindValue(':id', $id, PDO::PARAM_INT);
        $statement->bindValue(':id_competizione_partenza', (int) $dati['id_competizione_partenza'], PDO::PARAM_INT);
        $statement->bindValue(':id_competizione_arrivo', (int) $dati['id_competizione_arrivo'], PDO::PARAM_INT);
        $statement->bindValue(':ordine', (int) $dati['ordine'], PDO::PARAM_INT);

        if ($dati['dettagli'] === null) {
            $statement->bindValue(':dettagli', null, PDO::PARAM_NULL);
        } else {
            $statement->bindValue(':dettagli', (string) $dati['dettagli'], PDO::PARAM_STR);
        }

        $statement->execute();
    }

    public function delete(int $id): void
    {
        $pdo = Database::getConnessione();

        $statement = $pdo->prepare("DELETE FROM CompetizioneCollegamento WHERE ID = :id");
        $statement->execute(['id' => $id]);
    }
}