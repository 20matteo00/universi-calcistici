<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\Database;
use PDO;

final class PartitaEvento
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnessione();
    }

    public function create(array $data): int
    {
        $sql = "
            INSERT INTO PartitaEventi
            (IDPartita, IDGiocatore, IDSquadra, Tipo, Minuto, Dettagli)
            VALUES
            (:id_partita, :id_giocatore, :id_squadra, :tipo, :minuto, :dettagli)
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id_partita' => $data['IDPartita'],
            'id_giocatore' => $data['IDGiocatore'],
            'id_squadra' => $data['IDSquadra'],
            'tipo' => $data['Tipo'],
            'minuto' => $data['Minuto'],
            'dettagli' => $data['Dettagli'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function deleteByPartita(int $idPartita): void
    {
        $stmt = $this->pdo->prepare("
            DELETE FROM PartitaEventi
            WHERE IDPartita = :id_partita
        ");
        $stmt->execute([
            'id_partita' => $idPartita,
        ]);
    }

    public function findByPartita(int $idPartita): array
    {
        $sql = "
            SELECT
                pe.*,
                g.Nome AS NomeGiocatore,
                s.Nome AS NomeSquadra
            FROM PartitaEventi pe
            LEFT JOIN Giocatori g ON g.ID = pe.IDGiocatore
            INNER JOIN Squadre s ON s.ID = pe.IDSquadra
            WHERE pe.IDPartita = :id_partita
            ORDER BY pe.Minuto ASC, pe.ID ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id_partita' => $idPartita,
        ]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($rows as &$row) {
            $row['DettagliArray'] = [];

            if (!empty($row['Dettagli'])) {
                $decoded = json_decode((string) $row['Dettagli'], true);
                if (is_array($decoded)) {
                    $row['DettagliArray'] = $decoded;
                }
            }

            $nome = trim((string) ($row['NomeGiocatore'] ?? ''));
            $row['NomeGiocatoreCompleto'] = trim($nome);
        }
        unset($row);

        return $rows;
    }
}