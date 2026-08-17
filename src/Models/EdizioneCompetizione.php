<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\Database;
use PDO;

class EdizioneCompetizione
{
    public function competizioniEdizione(int $idEdizione): array
    {
        $pdo = Database::getConnessione();

        $statement = $pdo->prepare("
            SELECT
                ec.ID,
                ec.IDEdizione,
                ec.IDCompetizione,
                ec.Podio,
                ec.Creato,
                ec.Modificato,
                c.NomeCompetizione,
                c.Tipo,
                c.NumeroPartecipanti,
                c.Giri,
                c.InizialmenteVuota,
                c.Struttura
            FROM EdizioneCompetizione ec
            INNER JOIN Competizioni c ON c.ID = ec.IDCompetizione
            WHERE ec.IDEdizione = :idEdizione
            ORDER BY c.ID ASC
        ");

        $statement->execute([
            'idEdizione' => $idEdizione,
        ]);

        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findEdizioneCompetizione(int $idEdizioneCompetizione): ?array
    {
        $pdo = Database::getConnessione();

        $statement = $pdo->prepare("
            SELECT
                ec.ID,
                ec.IDEdizione,
                ec.IDCompetizione,
                ec.Podio,
                ec.Creato,
                ec.Modificato,
                c.NomeCompetizione,
                c.Tipo,
                c.NumeroPartecipanti,
                c.Giri,
                c.InizialmenteVuota,
                c.Struttura
            FROM EdizioneCompetizione ec
            INNER JOIN Competizioni c ON c.ID = ec.IDCompetizione
            WHERE ec.ID = :id
            LIMIT 1
        ");

        $statement->execute([
            'id' => $idEdizioneCompetizione,
        ]);

        $riga = $statement->fetch(PDO::FETCH_ASSOC);

        return $riga !== false ? $riga : null;
    }

    public function squadreIscritteACompetizione(int $idEdizioneCompetizione): array
    {
        $pdo = Database::getConnessione();

        $statement = $pdo->prepare("
            SELECT
                ecs.IDEdizioneCompetizione,
                ecs.IDSquadra,
                ecs.Stato,
                ecs.Motivo,
                ecs.Creato,
                ecs.Modificato,
                s.Nome,
                s.Paese,
                s.Tipo
            FROM EdizioneCompetizioneSquadra ecs
            INNER JOIN Squadre s ON s.ID = ecs.IDSquadra
            WHERE ecs.IDEdizioneCompetizione = :idEdizioneCompetizione
            ORDER BY s.Nome ASC, s.ID ASC
        ");

        $statement->execute([
            'idEdizioneCompetizione' => $idEdizioneCompetizione,
        ]);

        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function squadreDisponibiliPerCompetizione(int $idEdizione): array
    {
        $squadraModel = new EdizioneSquadra();
        return $squadraModel->squadreEdizione($idEdizione);
    }

    public function salvaSquadreCompetizione(
        int $idEdizioneCompetizione,
        array $idsSquadre,
        string $stato = 'Iscritta',
        ?string $motivo = null
    ): void {
        $pdo = Database::getConnessione();

        $idsSquadre = array_values(
            array_unique(
                array_filter(
                    array_map('intval', $idsSquadre),
                    fn(int $id): bool => $id > 0
                )
            )
        );

        try {
            $pdo->beginTransaction();

            $delete = $pdo->prepare("
                DELETE FROM EdizioneCompetizioneSquadra
                WHERE IDEdizioneCompetizione = :idEdizioneCompetizione
            ");

            $delete->execute([
                'idEdizioneCompetizione' => $idEdizioneCompetizione,
            ]);

            if ($idsSquadre !== []) {
                $insert = $pdo->prepare("
                    INSERT INTO EdizioneCompetizioneSquadra (IDEdizioneCompetizione, IDSquadra, Stato, Motivo)
                    VALUES (:idEdizioneCompetizione, :idSquadra, :stato, :motivo)
                ");

                foreach ($idsSquadre as $idSquadra) {
                    $insert->execute([
                        'idEdizioneCompetizione' => $idEdizioneCompetizione,
                        'idSquadra' => $idSquadra,
                        'stato' => $stato,
                        'motivo' => $motivo !== '' ? $motivo : null,
                    ]);
                }
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $e;
        }
    }

    public function squadreConAltreCompetizioni(int $idEdizione, int $idEdizioneCompetizioneCorrente): array
    {
        $pdo = Database::getConnessione();

        $statement = $pdo->prepare("
            SELECT
                ecs.IDSquadra,
                s.Nome AS NomeSquadra,
                ec.ID AS IDEdizioneCompetizione,
                c.ID AS IDCompetizione,
                c.NomeCompetizione,
                ecs.Stato
            FROM EdizioneCompetizioneSquadra ecs
            INNER JOIN EdizioneCompetizione ec
                ON ec.ID = ecs.IDEdizioneCompetizione
            INNER JOIN Competizioni c
                ON c.ID = ec.IDCompetizione
            INNER JOIN Squadre s
                ON s.ID = ecs.IDSquadra
            WHERE ec.IDEdizione = :idEdizione
              AND ecs.IDEdizioneCompetizione <> :idEdizioneCompetizioneCorrente
            ORDER BY s.Nome ASC, c.NomeCompetizione ASC
        ");

        $statement->execute([
            'idEdizione' => $idEdizione,
            'idEdizioneCompetizioneCorrente' => $idEdizioneCompetizioneCorrente,
        ]);

        $righe = $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $mappa = [];

        foreach ($righe as $riga) {
            $idSquadra = (int) ($riga['IDSquadra'] ?? 0);

            if (!isset($mappa[$idSquadra])) {
                $mappa[$idSquadra] = [];
            }

            $mappa[$idSquadra][] = [
                'IDEdizioneCompetizione' => (int) ($riga['IDEdizioneCompetizione'] ?? 0),
                'IDCompetizione' => (int) ($riga['IDCompetizione'] ?? 0),
                'NomeCompetizione' => (string) ($riga['NomeCompetizione'] ?? ''),
                'Stato' => (string) ($riga['Stato'] ?? ''),
            ];
        }

        return $mappa;
    }
}