<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\Database;
use PDO;

class Universo
{
    public function all(array $filtri = []): array
    {
        $pdo = Database::getConnessione();

        $q = trim((string) ($filtri['q'] ?? ''));
        $sort = (string) ($filtri['sort'] ?? 'ID');
        $dir = strtolower((string) ($filtri['dir'] ?? 'desc'));

        $sortConsentiti = ['ID', 'Nome', 'Creato', 'Modificato'];
        if (!in_array($sort, $sortConsentiti, true)) {
            $sort = 'Creato';
        }

        $dir = $dir === 'asc' ? 'asc' : 'desc';

        $sql = "SELECT ID, Nome, Descrizione, Creato, Modificato
                FROM Universi
                WHERE 1=1";

        $params = [];

        if ($q !== '') {
            $sql .= " AND Nome LIKE :q";
            $params['q'] = '%' . $q . '%';
        }

        $sql .= " ORDER BY {$sort} {$dir}, ID DESC";

        $statement = $pdo->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function find(int $id): ?array
    {
        $pdo = Database::getConnessione();

        $statement = $pdo->prepare("
            SELECT ID, Nome, Descrizione, Creato, Modificato
            FROM Universi
            WHERE ID = :id
            LIMIT 1
        ");

        $statement->execute(['id' => $id]);

        $universo = $statement->fetch(PDO::FETCH_ASSOC);

        return $universo !== false ? $universo : null;
    }

    public function create(array $data): int
    {
        $pdo = Database::getConnessione();

        $statement = $pdo->prepare("
            INSERT INTO Universi (Nome, Descrizione)
            VALUES (:nome, :descrizione)
        ");

        $statement->execute([
            'nome' => trim((string) ($data['nome'] ?? '')),
            'descrizione' => $this->nullableString($data['descrizione'] ?? null),
        ]);

        return (int) $pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $pdo = Database::getConnessione();

        $statement = $pdo->prepare("
            UPDATE Universi
            SET Nome = :nome,
                Descrizione = :descrizione
            WHERE ID = :id
            LIMIT 1
        ");

        return $statement->execute([
            'id' => $id,
            'nome' => trim((string) ($data['nome'] ?? '')),
            'descrizione' => $this->nullableString($data['descrizione'] ?? null),
        ]);
    }

    public function delete(int $id): bool
    {
        $pdo = Database::getConnessione();

        $statement = $pdo->prepare("
            DELETE FROM Universi
            WHERE ID = :id
            LIMIT 1
        ");

        return $statement->execute(['id' => $id]);
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    public function haEdizioni(int $idUniverso): bool
    {
        $pdo = Database::getConnessione();

        $statement = $pdo->prepare("
            SELECT 1
            FROM Edizioni
            WHERE IDUniverso = :idUniverso
            LIMIT 1
        ");

        $statement->execute(['idUniverso' => $idUniverso]);

        return (bool) $statement->fetchColumn();
    }

    public function squadre(int $idUniverso): array
    {
        $pdo = Database::getConnessione();

        $statement = $pdo->prepare("
            SELECT s.*
            FROM UniversoSquadre us
            INNER JOIN Squadre s ON s.ID = us.IDSquadra
            WHERE us.IDUniverso = :idUniverso
            ORDER BY s.ID ASC
        ");

        $statement->execute([
            'idUniverso' => $idUniverso,
        ]);

        return $statement->fetchAll();
    }

    public function giocatori(int $idUniverso): array
    {
        $pdo = Database::getConnessione();

        $statement = $pdo->prepare("
            SELECT g.*
            FROM UniversoGiocatori ug
            INNER JOIN Giocatori g ON g.ID = ug.IDGiocatore
            WHERE ug.IDUniverso = :idUniverso
            ORDER BY g.ID ASC
        ");

        $statement->execute([
            'idUniverso' => $idUniverso,
        ]);

        return $statement->fetchAll();
    }

    public function squadreDisponibili(int $idUniverso): array
    {
        $pdo = Database::getConnessione();

        $statement = $pdo->prepare("
            SELECT s.*
            FROM Squadre s
            WHERE NOT EXISTS (
                SELECT 1
                FROM UniversoSquadre us
                WHERE us.IDUniverso = :idUniverso
                  AND us.IDSquadra = s.ID
            )
            ORDER BY s.ID ASC
        ");

        $statement->execute([
            'idUniverso' => $idUniverso,
        ]);

        return $statement->fetchAll();
    }

    public function giocatoriDisponibili(int $idUniverso): array
    {
        $pdo = Database::getConnessione();

        $statement = $pdo->prepare("
            SELECT g.*
            FROM Giocatori g
            WHERE NOT EXISTS (
                SELECT 1
                FROM UniversoGiocatori ug
                WHERE ug.IDUniverso = :idUniverso
                  AND ug.IDGiocatore = g.ID
            )
            ORDER BY g.ID ASC
        ");

        $statement->execute([
            'idUniverso' => $idUniverso,
        ]);

        return $statement->fetchAll();
    }

    public function aggiungiSquadra(int $idUniverso, int $idSquadra): void
    {
        $pdo = Database::getConnessione();

        $statement = $pdo->prepare("
            INSERT INTO UniversoSquadre (IDUniverso, IDSquadra)
            VALUES (:idUniverso, :idSquadra)
        ");

        $statement->execute([
            'idUniverso' => $idUniverso,
            'idSquadra' => $idSquadra,
        ]);
    }

    public function rimuoviSquadra(int $idUniverso, int $idSquadra): void
    {
        $pdo = Database::getConnessione();

        $statement = $pdo->prepare("
            DELETE FROM UniversoSquadre
            WHERE IDUniverso = :idUniverso
              AND IDSquadra = :idSquadra
        ");

        $statement->execute([
            'idUniverso' => $idUniverso,
            'idSquadra' => $idSquadra,
        ]);
    }

    public function aggiungiGiocatore(int $idUniverso, int $idGiocatore): void
    {
        $pdo = Database::getConnessione();

        $statement = $pdo->prepare("
            INSERT INTO UniversoGiocatori (IDUniverso, IDGiocatore)
            VALUES (:idUniverso, :idGiocatore)
        ");

        $statement->execute([
            'idUniverso' => $idUniverso,
            'idGiocatore' => $idGiocatore,
        ]);
    }

    public function rimuoviGiocatore(int $idUniverso, int $idGiocatore): void
    {
        $pdo = Database::getConnessione();

        $statement = $pdo->prepare("
            DELETE FROM UniversoGiocatori
            WHERE IDUniverso = :idUniverso
              AND IDGiocatore = :idGiocatore
        ");

        $statement->execute([
            'idUniverso' => $idUniverso,
            'idGiocatore' => $idGiocatore,
        ]);
    }

    public function cercaSquadreDisponibili(int $idUniverso, array $filtri = []): array
    {
        $pdo = Database::getConnessione();

        $q = trim((string) ($filtri['q'] ?? ''));
        $paese = trim((string) ($filtri['paese'] ?? ''));
        $tipo = trim((string) ($filtri['tipo'] ?? ''));
        $sort = (string) ($filtri['sort'] ?? 'ID');
        $dir = strtolower((string) ($filtri['dir'] ?? 'asc')) === 'desc' ? 'DESC' : 'ASC';
        $page = max(1, (int) ($filtri['page'] ?? 1));
        $perPage = (int) ($filtri['per_page'] ?? 25);

        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 25;
        }

        $sortMap = [
            'ID' => 's.ID',
            'Nome' => 's.Nome',
            'Paese' => 's.Paese',
            'Tipo' => 's.Tipo',
            'Valore' => 's.Valore',
            'Creato' => 's.Creato',
        ];

        $sortSql = $sortMap[$sort] ?? 's.ID';

        $where = [
            "NOT EXISTS (
                SELECT 1
                FROM UniversoSquadre us
                WHERE us.IDUniverso = :idUniverso
                  AND us.IDSquadra = s.ID
            )"
        ];

        $params = [
            'idUniverso' => $idUniverso,
        ];

        if ($q !== '') {
            $where[] = 's.Nome LIKE :q';
            $params['q'] = '%' . $q . '%';
        }

        if ($paese !== '') {
            $where[] = 's.Paese = :paese';
            $params['paese'] = $paese;
        }

        if ($tipo !== '') {
            $where[] = 's.Tipo = :tipo';
            $params['tipo'] = $tipo;
        }

        $whereSql = implode(' AND ', $where);

        $countStatement = $pdo->prepare("
            SELECT COUNT(*)
            FROM Squadre s
            WHERE $whereSql
        ");
        $countStatement->execute($params);
        $totale = (int) $countStatement->fetchColumn();

        $pagineTotali = max(1, (int) ceil($totale / $perPage));
        $page = min($page, $pagineTotali);
        $offset = ($page - 1) * $perPage;

        $righeStatement = $pdo->prepare("
            SELECT s.*
            FROM Squadre s
            WHERE $whereSql
            ORDER BY $sortSql $dir, s.ID ASC
            LIMIT :limit OFFSET :offset
        ");

        foreach ($params as $chiave => $valore) {
            $righeStatement->bindValue(':' . $chiave, $valore);
        }

        $righeStatement->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $righeStatement->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $righeStatement->execute();

        return [
            'righe' => $righeStatement->fetchAll(),
            'totale' => $totale,
            'page' => $page,
            'per_page' => $perPage,
            'pagine_totali' => $pagineTotali,
        ];
    }

    public function cercaGiocatoriDisponibili(int $idUniverso, array $filtri = []): array
    {
        $pdo = Database::getConnessione();

        $q = trim((string) ($filtri['q'] ?? ''));
        $paese = trim((string) ($filtri['paese'] ?? ''));
        $posizione = trim((string) ($filtri['posizione'] ?? ''));
        $sort = (string) ($filtri['sort'] ?? 'ID');
        $dir = strtolower((string) ($filtri['dir'] ?? 'asc')) === 'desc' ? 'DESC' : 'ASC';
        $page = max(1, (int) ($filtri['page'] ?? 1));
        $perPage = (int) ($filtri['per_page'] ?? 25);

        if (!in_array($perPage, [10, 25, 50, 100, 200, 500, 1000], true)) {
            $perPage = 25;
        }

        $sortMap = [
            'ID' => 'g.ID',
            'Nome' => 'g.Nome',
            'Posizione' => 'g.Posizione',
            'Attacco' => 'g.Attacco',
            'Difesa' => 'g.Difesa',
            'Paese' => 'g.Paese',
            'Nascita' => 'g.Nascita',
            'Creato' => 'g.Creato',
        ];

        $sortSql = $sortMap[$sort] ?? 'g.ID';

        $where = [
            "NOT EXISTS (
                SELECT 1
                FROM UniversoGiocatori ug
                WHERE ug.IDUniverso = :idUniverso
                  AND ug.IDGiocatore = g.ID
            )"
        ];

        $params = [
            'idUniverso' => $idUniverso,
        ];

        if ($q !== '') {
            $where[] = 'g.Nome LIKE :q';
            $params['q'] = '%' . $q . '%';
        }

        if ($paese !== '') {
            $where[] = 'g.Paese = :paese';
            $params['paese'] = $paese;
        }

        if ($posizione !== '') {
            $where[] = 'g.Posizione = :posizione';
            $params['posizione'] = $posizione;
        }

        $whereSql = implode(' AND ', $where);

        $countStatement = $pdo->prepare("
            SELECT COUNT(*)
            FROM Giocatori g
            WHERE $whereSql
        ");
        $countStatement->execute($params);
        $totale = (int) $countStatement->fetchColumn();

        $pagineTotali = max(1, (int) ceil($totale / $perPage));
        $page = min($page, $pagineTotali);
        $offset = ($page - 1) * $perPage;

        $righeStatement = $pdo->prepare("
            SELECT g.*
            FROM Giocatori g
            WHERE $whereSql
            ORDER BY $sortSql $dir, g.ID ASC
            LIMIT :limit OFFSET :offset
        ");

        foreach ($params as $chiave => $valore) {
            $righeStatement->bindValue(':' . $chiave, $valore);
        }

        $righeStatement->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $righeStatement->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $righeStatement->execute();

        return [
            'righe' => $righeStatement->fetchAll(),
            'totale' => $totale,
            'page' => $page,
            'per_page' => $perPage,
            'pagine_totali' => $pagineTotali,
        ];
    }

    public function competizioni(int $idUniverso): array
    {
        $pdo = Database::getConnessione();

        $statement = $pdo->prepare("
        SELECT *
        FROM Competizioni
        WHERE IDUniverso = :idUniverso
        ORDER BY ID ASC
    ");

        $statement->execute([
            'idUniverso' => $idUniverso,
        ]);

        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function totalePartecipantiCompetizioni(int $idUniverso): int
    {
        $pdo = Database::getConnessione();

        $statement = $pdo->prepare("
            SELECT COALESCE(SUM(NumeroPartecipanti), 0)
            FROM Competizioni
            WHERE IDUniverso = :idUniverso
        ");

        $statement->execute([
            'idUniverso' => $idUniverso,
        ]);

        return (int) $statement->fetchColumn();
    }

    public function verificaRoseMinime(int $idUniverso): array
    {
        $squadre = $this->squadre($idUniverso);
        $giocatori = $this->giocatori($idUniverso);

        $numeroSquadre = count($squadre);

        $conteggi = [
            'totale' => count($giocatori),
            'POR' => 0,
            'difensivi' => 0,
            'centrocampo' => 0,
            'offensivi' => 0,
        ];

        foreach ($giocatori as $giocatore) {
            $posizione = strtoupper(trim((string) ($giocatore['Posizione'] ?? '')));

            if ($posizione === 'POR') {
                $conteggi['POR']++;
            }

            if (in_array($posizione, ['TD', 'TS', 'DC'], true)) {
                $conteggi['difensivi']++;
            }

            if (in_array($posizione, ['CC', 'MED', 'CS', 'CD', 'TRQ'], true)) {
                $conteggi['centrocampo']++;
            }

            if (in_array($posizione, ['AS', 'AD', 'ATT'], true)) {
                $conteggi['offensivi']++;
            }
        }

        $minimiRichiesti = [
            'totale' => $numeroSquadre * 18,
            'POR' => $numeroSquadre * 2,
            'difensivi' => $numeroSquadre * 5,
            'centrocampo' => $numeroSquadre * 6,
            'offensivi' => $numeroSquadre * 5,
        ];

        $mancanze = [
            'totale' => max(0, $minimiRichiesti['totale'] - $conteggi['totale']),
            'POR' => max(0, $minimiRichiesti['POR'] - $conteggi['POR']),
            'difensivi' => max(0, $minimiRichiesti['difensivi'] - $conteggi['difensivi']),
            'centrocampo' => max(0, $minimiRichiesti['centrocampo'] - $conteggi['centrocampo']),
            'offensivi' => max(0, $minimiRichiesti['offensivi'] - $conteggi['offensivi']),
        ];

        $ok = $mancanze['totale'] === 0
            && $mancanze['POR'] === 0
            && $mancanze['difensivi'] === 0
            && $mancanze['centrocampo'] === 0
            && $mancanze['offensivi'] === 0;

        return [
            'ok' => $ok,
            'numero_squadre' => $numeroSquadre,
            'conteggi' => $conteggi,
            'richiesti' => $minimiRichiesti,
            'mancanze' => $mancanze,
        ];
    }
}
