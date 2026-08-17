<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\Database;
use PDO;

class PartitaQuery
{
    public function partitePerCompetizione(int $idEdizioneCompetizione): array
    {
        $pdo = Database::getConnessione();

        $statement = $pdo->prepare("
            SELECT
                p.ID,
                p.IDEdizioneCompetizione,
                p.IDSquadraCasa,
                p.IDSquadraTrasferta,
                p.GoalCasa,
                p.GoalTrasferta,
                p.Fase,
                p.Giornata,
                p.Girone,
                p.Data,
                p.Stato,
                p.Dettagli,
                p.Creato,
                p.Modificato,
                sc.Nome AS NomeSquadraCasa,
                sc.Colori AS ColoriSquadraCasa,
                st.Nome AS NomeSquadraTrasferta,
                st.Colori AS ColoriSquadraTrasferta
            FROM Partite p
            INNER JOIN Squadre sc ON sc.ID = p.IDSquadraCasa
            INNER JOIN Squadre st ON st.ID = p.IDSquadraTrasferta
            WHERE p.IDEdizioneCompetizione = :idEdizioneCompetizione
            ORDER BY p.Giornata ASC, p.ID ASC
        ");

        $statement->execute([
            'idEdizioneCompetizione' => $idEdizioneCompetizione,
        ]);

        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function partitePerCompetizioneEIntervallo(
        int $idEdizioneCompetizione,
        int $giornataDa,
        int $giornataA
    ): array {
        $pdo = Database::getConnessione();

        $statement = $pdo->prepare("
            SELECT
                p.ID,
                p.IDEdizioneCompetizione,
                p.IDSquadraCasa,
                p.IDSquadraTrasferta,
                p.GoalCasa,
                p.GoalTrasferta,
                p.Fase,
                p.Giornata,
                p.Girone,
                p.Dettagli,
                sc.Nome AS NomeSquadraCasa,
                sc.Colori AS ColoriSquadraCasa,
                st.Nome AS NomeSquadraTrasferta,
                st.Colori AS ColoriSquadraTrasferta
            FROM Partite p
            INNER JOIN Squadre sc ON sc.ID = p.IDSquadraCasa
            INNER JOIN Squadre st ON st.ID = p.IDSquadraTrasferta
            WHERE p.IDEdizioneCompetizione = :idEdizioneCompetizione
              AND p.Giornata BETWEEN :giornataDa AND :giornataA
            ORDER BY p.Giornata ASC, p.ID ASC
        ");

        $statement->execute([
            'idEdizioneCompetizione' => $idEdizioneCompetizione,
            'giornataDa' => $giornataDa,
            'giornataA' => $giornataA,
        ]);

        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function partiteEliminazionePerCompetizione(int $idEdizioneCompetizione): array
    {
        $pdo = Database::getConnessione();

        $statement = $pdo->prepare("
            SELECT
                p.ID,
                p.IDEdizioneCompetizione,
                p.IDSquadraCasa,
                p.IDSquadraTrasferta,
                p.GoalCasa,
                p.GoalTrasferta,
                p.Fase,
                p.Giornata,
                p.Girone,
                p.Data,
                p.Stato,
                p.Dettagli,
                p.Creato,
                p.Modificato,
                sc.Nome AS NomeSquadraCasa,
                sc.Colori AS ColoriSquadraCasa,
                st.Nome AS NomeSquadraTrasferta,
                st.Colori AS ColoriSquadraTrasferta
            FROM Partite p
            INNER JOIN Squadre sc ON sc.ID = p.IDSquadraCasa
            INNER JOIN Squadre st ON st.ID = p.IDSquadraTrasferta
            WHERE p.IDEdizioneCompetizione = :idEdizioneCompetizione
            ORDER BY p.Fase ASC, p.Giornata ASC, p.ID ASC
        ");

        $statement->execute([
            'idEdizioneCompetizione' => $idEdizioneCompetizione,
        ]);

        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function partiteRaggruppatePerGiornata(int $idEdizioneCompetizione): array
    {
        $partite = $this->partitePerCompetizione($idEdizioneCompetizione);
        $giornate = [];

        foreach ($partite as $partita) {
            $giornata = (int) ($partita['Giornata'] ?? 0);

            if (!isset($giornate[$giornata])) {
                $giornate[$giornata] = [];
            }

            $giornate[$giornata][] = $partita;
        }

        ksort($giornate);

        return $giornate;
    }

    public function giornatePerCompetizione(int $idEdizioneCompetizione): array
    {
        $pdo = Database::getConnessione();

        $statement = $pdo->prepare("
            SELECT DISTINCT Giornata
            FROM Partite
            WHERE IDEdizioneCompetizione = :idEdizioneCompetizione
              AND Giornata IS NOT NULL
            ORDER BY Giornata ASC
        ");

        $statement->execute([
            'idEdizioneCompetizione' => $idEdizioneCompetizione,
        ]);

        $righe = $statement->fetchAll(PDO::FETCH_COLUMN) ?: [];

        return array_map('intval', $righe);
    }

    public function partiteRaggruppatePerFaseEGiornata(int $idEdizioneCompetizione): array
    {
        $partite = $this->partiteEliminazionePerCompetizione($idEdizioneCompetizione);
        $gruppi = [];

        foreach ($partite as $partita) {
            $fase = (string) ($partita['Fase'] ?? '');
            $giornata = (int) ($partita['Giornata'] ?? 0);

            $chiave = mb_strtolower($fase) . '-' . $giornata;
            $anchor = 'fase-' . $this->slugFase($fase) . '-giornata-' . $giornata;

            if (!isset($gruppi[$chiave])) {
                $gruppi[$chiave] = [
                    'chiave' => $chiave,
                    'anchor' => $anchor,
                    'fase' => $fase,
                    'giornata' => $giornata,
                    'titolo' => $fase . ' - Giornata ' . $giornata,
                    'partite' => [],
                ];
            }

            $gruppi[$chiave]['partite'][] = $partita;
        }

        return $gruppi;
    }

    public function findByEdizioneCompetizione(int $idEdizioneCompetizione): array
    {
        $pdo = Database::getConnessione();

        $statement = $pdo->prepare("
            SELECT
                p.*,
                sc.Nome AS NomeSquadraCasa,
                st.Nome AS NomeSquadraTrasferta
            FROM Partite p
            INNER JOIN Squadre sc ON sc.ID = p.IDSquadraCasa
            INNER JOIN Squadre st ON st.ID = p.IDSquadraTrasferta
            WHERE p.IDEdizioneCompetizione = :idEdizioneCompetizione
            ORDER BY
                CASE p.Fase
                    WHEN 'Sessantaquattresimo' THEN 1
                    WHEN 'Trentaduesimo' THEN 2
                    WHEN 'Sedicesimo' THEN 3
                    WHEN 'Ottavo' THEN 4
                    WHEN 'Quarto' THEN 5
                    WHEN 'Semifinale' THEN 6
                    WHEN 'Finale3Posto' THEN 7
                    WHEN 'Finale' THEN 8
                    ELSE 99
                END ASC,
                p.Giornata ASC,
                p.ID ASC
        ");

        $statement->execute([
            'idEdizioneCompetizione' => $idEdizioneCompetizione,
        ]);

        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function slugFase(string $fase): string
    {
        $slug = mb_strtolower(trim($fase));
        $slug = str_replace(
            ['à', 'è', 'é', 'ì', 'ò', 'ù'],
            ['a', 'e', 'e', 'i', 'o', 'u'],
            $slug
        );
        $slug = preg_replace('/[^a-z0-9]+/u', '-', $slug) ?? '';
        return trim($slug, '-');
    }
}
