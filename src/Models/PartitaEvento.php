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

        // Cache nomi giocatori per assist
        $nomiGiocatori = [];

        foreach ($rows as &$row) {
            $row['DettagliArray'] = [];

            if (!empty($row['Dettagli'])) {
                $decoded = json_decode((string) $row['Dettagli'], true);
                if (is_array($decoded)) {
                    $row['DettagliArray'] = $decoded;
                }
            }

            $nome = trim((string) ($row['NomeGiocatore'] ?? ''));
            $row['NomeGiocatoreCompleto'] = $nome;

            // Risolvi nome assist se presente
            $row['NomeAssist'] = null;
            $assistId = (int) ($row['DettagliArray']['assist_id'] ?? 0);
            if ($assistId > 0) {
                if (!isset($nomiGiocatori[$assistId])) {
                    $stmtAssist = $this->pdo->prepare("
                    SELECT Nome
                    FROM Giocatori
                    WHERE ID = :id
                    LIMIT 1
                ");
                    $stmtAssist->execute(['id' => $assistId]);
                    $assistRow = $stmtAssist->fetch(PDO::FETCH_ASSOC);
                    $nomiGiocatori[$assistId] = $assistRow ? trim((string) ($assistRow['Nome'] ?? '')) : null;
                }
                $row['NomeAssist'] = $nomiGiocatori[$assistId];
            }
        }
        unset($row);

        return $rows;
    }

    public function statisticheGiocatoriPerCompetizioneEIntervallo(
        int $idEdizioneCompetizione,
        int $giornataDa,
        int $giornataA
    ): array {
        $sql = "
        SELECT
            base.IDGiocatore,
            base.NomeGiocatore,
            base.IDSquadra,
            base.NomeSquadra,
            base.ColoriSquadra,
            SUM(base.Gol) AS Gol,
            SUM(base.GolRigore) AS GolRigore,
            SUM(base.Autogol) AS Autogol,
            SUM(base.Assist) AS Assist,
            SUM(base.Ammonizioni) AS Ammonizioni,
            SUM(base.Espulsioni) AS Espulsioni,
            SUM(base.RigoriSbagliati) AS RigoriSbagliati,
            SUM(base.EventiTotali) AS EventiTotali
        FROM (
            SELECT
                pe.IDGiocatore AS IDGiocatore,
                g.Nome AS NomeGiocatore,
                pe.IDSquadra AS IDSquadra,
                s.Nome AS NomeSquadra,
                s.Colori AS ColoriSquadra,
                CASE
                    WHEN pe.Tipo = 'gol'
                     AND COALESCE(JSON_UNQUOTE(JSON_EXTRACT(pe.Dettagli, '$.autogol')), 'false') <> 'true'
                    THEN 1 ELSE 0
                END AS Gol,
                CASE
                    WHEN pe.Tipo = 'gol'
                     AND COALESCE(JSON_UNQUOTE(JSON_EXTRACT(pe.Dettagli, '$.rigore')), 'false') = 'true'
                    THEN 1 ELSE 0
                END AS GolRigore,
                CASE
                    WHEN pe.Tipo = 'gol'
                     AND COALESCE(JSON_UNQUOTE(JSON_EXTRACT(pe.Dettagli, '$.autogol')), 'false') = 'true'
                    THEN 1 ELSE 0
                END AS Autogol,
                0 AS Assist,
                CASE WHEN pe.Tipo = 'ammonizione' THEN 1 ELSE 0 END AS Ammonizioni,
                CASE WHEN pe.Tipo = 'espulsione' THEN 1 ELSE 0 END AS Espulsioni,
                CASE WHEN pe.Tipo = 'rigore_sbagliato' THEN 1 ELSE 0 END AS RigoriSbagliati,
                1 AS EventiTotali
            FROM PartitaEventi pe
            INNER JOIN Partite p ON p.ID = pe.IDPartita
            LEFT JOIN Giocatori g ON g.ID = pe.IDGiocatore
            INNER JOIN Squadre s ON s.ID = pe.IDSquadra
            WHERE p.IDEdizioneCompetizione = :idEdizioneCompetizione_1
              AND p.Giornata BETWEEN :giornataDa_1 AND :giornataA_1
              AND pe.IDGiocatore IS NOT NULL

            UNION ALL

            SELECT
                CAST(JSON_UNQUOTE(JSON_EXTRACT(pe.Dettagli, '$.assist_id')) AS UNSIGNED) AS IDGiocatore,
                ga.Nome AS NomeGiocatore,
                pe.IDSquadra AS IDSquadra,
                s.Nome AS NomeSquadra,
                s.Colori AS ColoriSquadra,
                0 AS Gol,
                0 AS GolRigore,
                0 AS Autogol,
                1 AS Assist,
                0 AS Ammonizioni,
                0 AS Espulsioni,
                0 AS RigoriSbagliati,
                1 AS EventiTotali
            FROM PartitaEventi pe
            INNER JOIN Partite p ON p.ID = pe.IDPartita
            INNER JOIN Squadre s ON s.ID = pe.IDSquadra
            INNER JOIN Giocatori ga
                ON ga.ID = CAST(JSON_UNQUOTE(JSON_EXTRACT(pe.Dettagli, '$.assist_id')) AS UNSIGNED)
            WHERE p.IDEdizioneCompetizione = :idEdizioneCompetizione_2
              AND p.Giornata BETWEEN :giornataDa_2 AND :giornataA_2
              AND pe.Tipo = 'gol'
              AND JSON_EXTRACT(pe.Dettagli, '$.assist_id') IS NOT NULL
        ) AS base
        WHERE base.IDGiocatore IS NOT NULL
          AND base.IDGiocatore > 0
        GROUP BY
            base.IDGiocatore,
            base.NomeGiocatore,
            base.IDSquadra,
            base.NomeSquadra,
            base.ColoriSquadra
        ORDER BY
            Gol DESC,
            Assist DESC,
            EventiTotali DESC,
            NomeGiocatore ASC
    ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'idEdizioneCompetizione_1' => $idEdizioneCompetizione,
            'giornataDa_1' => $giornataDa,
            'giornataA_1' => $giornataA,
            'idEdizioneCompetizione_2' => $idEdizioneCompetizione,
            'giornataDa_2' => $giornataDa,
            'giornataA_2' => $giornataA,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
