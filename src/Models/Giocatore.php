<?php

namespace App\Models;

use App\Config\Database;
use App\Support\Countries;
use App\Support\Positions;
use App\Support\Names;

class Giocatore
{
    public static function tutti(): array
    {
        $db = Database::getConnessione();
        $stmt = $db->query('SELECT * FROM Giocatori ORDER BY ID ASC');
        return $stmt->fetchAll();
    }

    public static function trovaPerId(int $id): ?array
    {
        $db = Database::getConnessione();
        $stmt = $db->prepare('SELECT * FROM Giocatori WHERE ID = :id');
        $stmt->execute(['id' => $id]);
        $riga = $stmt->fetch();

        return $riga ?: null;
    }

    public static function crea(
        string $nome,
        string $posizione,
        ?float $attacco,
        ?float $difesa,
        ?string $paese,
        ?string $nascita
    ): int {
        $db = Database::getConnessione();

        $stmt = $db->prepare('
            INSERT INTO Giocatori (Nome, Posizione, Attacco, Difesa, Paese, Nascita)
            VALUES (:nome, :posizione, :attacco, :difesa, :paese, :nascita)
        ');

        $stmt->execute([
            'nome' => $nome,
            'posizione' => $posizione,
            'attacco' => $attacco ?? 0,
            'difesa' => $difesa ?? 0,
            'paese' => $paese,
            'nascita' => $nascita !== '' ? $nascita : null,
        ]);

        return (int) $db->lastInsertId();
    }

    public static function aggiorna(
        int $id,
        string $nome,
        string $posizione,
        ?float $attacco,
        ?float $difesa,
        ?string $paese,
        ?string $nascita
    ): bool {
        $db = Database::getConnessione();

        $stmt = $db->prepare('
            UPDATE Giocatori
            SET Nome = :nome,
                Posizione = :posizione,
                Attacco = :attacco,
                Difesa = :difesa,
                Paese = :paese,
                Nascita = :nascita
            WHERE ID = :id
        ');

        return $stmt->execute([
            'id' => $id,
            'nome' => $nome,
            'posizione' => $posizione,
            'attacco' => $attacco ?? 0,
            'difesa' => $difesa ?? 0,
            'paese' => $paese,
            'nascita' => $nascita !== '' ? $nascita : null,
        ]);
    }

    public static function elimina(int $id): bool
    {
        $db = Database::getConnessione();
        $stmt = $db->prepare('DELETE FROM Giocatori WHERE ID = :id');
        return $stmt->execute(['id' => $id]);
    }

    public static function duplica(int $id): ?int
    {
        $giocatore = self::trovaPerId($id);

        if (!$giocatore) {
            return null;
        }

        $nomeDuplicato = $giocatore['Nome'] . ' (Copia)';

        return self::crea(
            $nomeDuplicato,
            $giocatore['Posizione'] ?? 'CC',
            isset($giocatore['Attacco']) ? (float) $giocatore['Attacco'] : 0,
            isset($giocatore['Difesa']) ? (float) $giocatore['Difesa'] : 0,
            $giocatore['Paese'] ?? null,
            $giocatore['Nascita'] ?? null
        );
    }

    public static function generaRandom(): int
    {

        $codiciPaese = array_keys(Countries::all());
        $posizioni = Positions::all();

        $nome = Names::randomFullName();
        $posizione = array_rand($posizioni);

        if (!Positions::exists($posizione)) {
            $posizione = 'CC';
        }

        $attacco = self::generaAttaccoPerPosizione($posizione);
        $difesa = self::generaDifesaPerPosizione($posizione);
        $paese = $codiciPaese[array_rand($codiciPaese)];
        $nascita = rand(1985, 2010) . '-' . str_pad((string) rand(1, 12), 2, '0', STR_PAD_LEFT) . '-' . str_pad((string) rand(1, 28), 2, '0', STR_PAD_LEFT);

        return self::crea($nome, $posizione, $attacco, $difesa, $paese, $nascita);
    }

    private static function generaAttaccoPerPosizione(string $posizione): float
    {
        return match ($posizione) {
            'POR' => rand(0, 25000) / 100,
            'TD', 'TS' => rand(10000, 50000) / 100,
            'DC' => rand(5000, 35000) / 100,
            'CC' => rand(15000, 65000) / 100,
            'MED' => rand(10000, 45000) / 100,
            'CL', 'CR' => rand(20000, 70000) / 100,
            'TRQ' => rand(30000, 85000) / 100,
            'AS', 'AD' => rand(25000, 85000) / 100,
            'ATT' => rand(35000, 95000) / 100,
            default => rand(10000, 60000) / 100,
        };
    }

    private static function generaDifesaPerPosizione(string $posizione): float
    {
        return match ($posizione) {
            'POR' => rand(50000, 95000) / 100,
            'TD', 'TS' => rand(30000, 75000) / 100,
            'DC' => rand(45000, 95000) / 100,
            'CC' => rand(20000, 70000) / 100,
            'MED' => rand(35000, 80000) / 100,
            'CL', 'CR' => rand(15000, 50000) / 100,
            'TRQ' => rand(5000, 35000) / 100,
            'AS', 'AD' => rand(5000, 30000) / 100,
            'ATT' => rand(2000, 25000) / 100,
            default => rand(10000, 60000) / 100,
        };
    }

    public static function eliminaMultiplo(array $ids): int
    {
        $ids = array_values(array_filter(array_map('intval', $ids), fn(int $id) => $id > 0));

        if ($ids === []) {
            return 0;
        }

        $db = Database::getConnessione();
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $db->prepare("DELETE FROM Giocatori WHERE ID IN ($placeholders)");
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
        $posizione = trim((string) ($filtri['posizione'] ?? ''));
        $sort = (string) ($filtri['sort'] ?? 'ID');
        $dir = strtolower((string) ($filtri['dir'] ?? 'asc'));
        $page = max(1, (int) ($filtri['page'] ?? 1));
        $perPage = max(1, min(1000, (int) ($filtri['per_page'] ?? 25)));
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

        if ($posizione !== '') {
            $where[] = 'Posizione = :posizione';
            $params['posizione'] = $posizione;
        }

        $allowedSort = ['ID', 'Nome', 'Attacco', 'Difesa', 'Nascita', 'Posizione', 'Paese', 'Creato'];
        if (!in_array($sort, $allowedSort, true)) {
            $sort = 'ID';
        }

        $dir = $dir === 'desc' ? 'DESC' : 'ASC';

        $sqlWhere = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        $stmtCount = $db->prepare('SELECT COUNT(*) FROM Giocatori' . $sqlWhere);
        $stmtCount->execute($params);
        $totale = (int) $stmtCount->fetchColumn();

        $sql = 'SELECT * FROM Giocatori' . $sqlWhere . " ORDER BY {$sort} {$dir} LIMIT {$perPage} OFFSET {$offset}";
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

    public static function inUsoInUniversi(int $idGiocatore): bool
    {
        $pdo = Database::getConnessione();

        $statement = $pdo->prepare("
            SELECT 1
            FROM UniversoGiocatori
            WHERE IDGiocatore = :idGiocatore
            LIMIT 1
        ");

        $statement->execute([
            'idGiocatore' => $idGiocatore,
        ]);

        return (bool) $statement->fetchColumn();
    }
}
