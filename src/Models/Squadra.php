<?php

namespace App\Models;

use App\Config\Database;
use PDO;
use App\Support\Countries;
use App\Support\Names;

class Squadra
{
    public static function tutti(): array
    {
        $db = Database::getConnessione();
        $stmt = $db->query('SELECT * FROM Squadre ORDER BY ID ASC');
        return $stmt->fetchAll();
    }

    public static function trovaPerId(int $id): ?array
    {
        $db = Database::getConnessione();
        $stmt = $db->prepare('SELECT * FROM Squadre WHERE ID = :id');
        $stmt->execute(['id' => $id]);
        $riga = $stmt->fetch();

        return $riga ?: null;
    }

    public static function crea(
        string $nome,
        ?string $paese,
        string $tipo,
        ?float $valore,
        ?float $fattoreCasa,
        ?array $colori
    ): int {
        $db = Database::getConnessione();

        $stmt = $db->prepare('
            INSERT INTO Squadre (Nome, Paese, Tipo, Valore, FattoreCasa, Colori)
            VALUES (:nome, :paese, :tipo, :valore, :fattore_casa, :colori)
        ');

        $stmt->execute([
            'nome' => $nome,
            'paese' => $paese,
            'tipo' => $tipo,
            'valore' => $valore ?? 0,
            'fattore_casa' => $fattoreCasa ?? 0,
            'colori' => $colori ? json_encode($colori, JSON_UNESCAPED_UNICODE) : null,
        ]);

        return (int) $db->lastInsertId();
    }

    public static function aggiorna(
        int $id,
        string $nome,
        ?string $paese,
        string $tipo,
        ?float $valore,
        ?float $fattoreCasa,
        ?array $colori
    ): bool {
        $db = Database::getConnessione();

        $stmt = $db->prepare('
            UPDATE Squadre
            SET Nome = :nome,
                Paese = :paese,
                Tipo = :tipo,
                Valore = :valore,
                FattoreCasa = :fattore_casa,
                Colori = :colori
            WHERE ID = :id
        ');

        return $stmt->execute([
            'id' => $id,
            'nome' => $nome,
            'paese' => $paese,
            'tipo' => $tipo,
            'valore' => $valore ?? 0,
            'fattore_casa' => $fattoreCasa ?? 0,
            'colori' => $colori ? json_encode($colori, JSON_UNESCAPED_UNICODE) : null,
        ]);
    }

    public static function elimina(int $id): bool
    {
        $db = Database::getConnessione();
        $stmt = $db->prepare('DELETE FROM Squadre WHERE ID = :id');
        return $stmt->execute(['id' => $id]);
    }

    public static function duplica(int $id): ?int
    {
        $squadra = self::trovaPerId($id);

        if (!$squadra) {
            return null;
        }

        $nomeDuplicato = $squadra['Nome'] . ' (Copia)';
        $colori = null;

        if (!empty($squadra['Colori'])) {
            $decoded = json_decode($squadra['Colori'], true);
            $colori = is_array($decoded) ? $decoded : null;
        }

        return self::crea(
            $nomeDuplicato,
            $squadra['Paese'] ?? null,
            $squadra['Tipo'] ?? 'Club',
            isset($squadra['Valore']) ? (float) $squadra['Valore'] : 0,
            isset($squadra['FattoreCasa']) ? (float) $squadra['FattoreCasa'] : 0,
            $colori
        );
    }

    public static function generaRandom(): int
    {

        $codiciPaese = array_keys(Countries::all());

        $nome = Names::randomTeamName();
        $paese = $codiciPaese[array_rand($codiciPaese)];
        $tipo = 'Club';
        $valore = rand(1, 99999) / 100;
        $fattoreCasa = rand(1, 99999) / 100;
        $colori = [
            'sfondo' => self::coloreRandom(),
            'testo' => self::coloreRandom(),
            'bordo' => self::coloreRandom(),
        ];

        return self::crea($nome, $paese, $tipo, $valore, $fattoreCasa, $colori);
    }

    private static function coloreRandom(): string
    {
        return sprintf('#%06X', mt_rand(0, 0xFFFFFF));
    }

    public static function eliminaMultiplo(array $ids): int
    {
        $ids = array_values(array_filter(array_map('intval', $ids), fn(int $id) => $id > 0));

        if ($ids === []) {
            return 0;
        }

        $db = Database::getConnessione();
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $db->prepare("DELETE FROM Squadre WHERE ID IN ($placeholders)");
        $stmt->execute($ids);

        return $stmt->rowCount();
    }

    public static function generaRandomMultiplo(int $quantita): int
    {
        $quantita = max(0, min($quantita, 500));

        for ($i = 0; $i < $quantita; $i++) {
            self::generaRandom();
        }

        return $quantita;
    }

    public static function cerca(array $filtri = []): array
    {
        $db = Database::getConnessione();

        $q = trim((string) ($filtri['q'] ?? ''));
        $paese = trim((string) ($filtri['paese'] ?? ''));
        $tipo = trim((string) ($filtri['tipo'] ?? ''));
        $sort = (string) ($filtri['sort'] ?? 'ID');
        $dir = strtolower((string) ($filtri['dir'] ?? 'asc'));
        $page = max(1, (int) ($filtri['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($filtri['per_page'] ?? 25)));
        $offset = ($page - 1) * $perPage;

        $where = [];
        $params = [];

        if ($q !== '') {
            $where[] = 'Nome LIKE :q';
            $params['q'] = '%' . $q . '%';
        }

        if ($paese !== '') {
            $where[] = 'Paese = :paese';
            $params['paese'] = $paese;
        }

        if ($tipo !== '') {
            $where[] = 'Tipo = :tipo';
            $params['tipo'] = $tipo;
        }

        $allowedSort = ['ID', 'Nome', 'Paese', 'Tipo', 'Valore', 'FattoreCasa', 'Creato'];
        if (!in_array($sort, $allowedSort, true)) {
            $sort = 'ID';
        }

        $dir = $dir === 'desc' ? 'DESC' : 'ASC';

        $sqlWhere = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        $stmtCount = $db->prepare('SELECT COUNT(*) FROM Squadre' . $sqlWhere);
        $stmtCount->execute($params);
        $totale = (int) $stmtCount->fetchColumn();

        $sql = 'SELECT * FROM Squadre' . $sqlWhere . " ORDER BY {$sort} {$dir} LIMIT {$perPage} OFFSET {$offset}";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        return [
            'righe' => $stmt->fetchAll(),
            'totale' => $totale,
            'page' => $page,
            'per_page' => $perPage,
            'pagine_totali' => max(1, (int) ceil($totale / $perPage)),
        ];
    }

    public static function inUsoInUniversi(int $idSquadra): bool
    {
        $pdo = Database::getConnessione();

        $statement = $pdo->prepare("
            SELECT 1
            FROM UniversoSquadre
            WHERE IDSquadra = :idSquadra
            LIMIT 1
        ");

        $statement->execute([
            'idSquadra' => $idSquadra,
        ]);

        return (bool) $statement->fetchColumn();
    }
}
