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

    public function squadreEdizione(int $idEdizione): array
    {
        $pdo = Database::getConnessione();

        $statement = $pdo->prepare("
        SELECT
            es.IDEdizione,
            es.IDSquadra,
            es.Valore,
            es.FattoreCasa,
            s.Nome,
            s.Paese,
            s.Tipo
        FROM EdizioneSquadra es
        INNER JOIN Squadre s ON s.ID = es.IDSquadra
        WHERE es.IDEdizione = :idEdizione
        ORDER BY s.Nome ASC, s.ID ASC
    ");

        $statement->execute([
            'idEdizione' => $idEdizione,
        ]);

        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function giocatoriEdizione(int $idEdizione): array
    {
        $pdo = Database::getConnessione();

        $statement = $pdo->prepare("
        SELECT
            eg.IDEdizione,
            eg.IDGiocatore,
            eg.Attacco,
            eg.Difesa,
            g.Nome,
            g.Paese,
            g.Posizione,
            g.Nascita
        FROM EdizioneGiocatore eg
        INNER JOIN Giocatori g ON g.ID = eg.IDGiocatore
        WHERE eg.IDEdizione = :idEdizione
        ORDER BY g.Nome ASC, g.ID ASC
    ");

        $statement->execute([
            'idEdizione' => $idEdizione,
        ]);

        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function haGiocatoriEdizione(int $idEdizione): bool
    {
        $pdo = Database::getConnessione();

        $statement = $pdo->prepare("
        SELECT 1
        FROM EdizioneGiocatore
        WHERE IDEdizione = :idEdizione
        LIMIT 1
    ");

        $statement->execute([
            'idEdizione' => $idEdizione,
        ]);

        return (bool) $statement->fetchColumn();
    }

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

    public function giocatoriAssegnatiASquadra(int $idEdizione, int $idSquadra): array
    {
        $pdo = Database::getConnessione();

        $statement = $pdo->prepare("
        SELECT
            esg.IDEdizione,
            esg.IDSquadra,
            esg.IDGiocatore,
            g.Nome,
            g.Paese,
            g.Posizione,
            eg.Attacco,
            eg.Difesa
        FROM EdizioneSquadraGiocatore esg
        INNER JOIN Giocatori g ON g.ID = esg.IDGiocatore
        INNER JOIN EdizioneGiocatore eg
            ON eg.IDEdizione = esg.IDEdizione
           AND eg.IDGiocatore = esg.IDGiocatore
        WHERE esg.IDEdizione = :idEdizione
          AND esg.IDSquadra = :idSquadra
        ORDER BY g.Nome ASC, g.ID ASC
    ");

        $statement->execute([
            'idEdizione' => $idEdizione,
            'idSquadra' => $idSquadra,
        ]);

        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function giocatoriDisponibiliPerSquadra(int $idEdizione, int $idSquadra): array
    {
        $pdo = Database::getConnessione();

        $statement = $pdo->prepare("
        SELECT
            eg.IDEdizione,
            eg.IDGiocatore,
            eg.Attacco,
            eg.Difesa,
            g.Nome,
            g.Paese,
            g.Posizione,
            g.Nascita
        FROM EdizioneGiocatore eg
        INNER JOIN Giocatori g ON g.ID = eg.IDGiocatore
        WHERE eg.IDEdizione = :idEdizione
          AND NOT EXISTS (
              SELECT 1
              FROM EdizioneSquadraGiocatore esg
              WHERE esg.IDEdizione = eg.IDEdizione
                AND esg.IDGiocatore = eg.IDGiocatore
                AND esg.IDSquadra <> :idSquadra
          )
        ORDER BY g.Nome ASC, g.ID ASC
    ");

        $statement->execute([
            'idEdizione' => $idEdizione,
            'idSquadra' => $idSquadra,
        ]);

        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function salvaRosaSquadra(int $idEdizione, int $idSquadra, array $idsGiocatori): void
    {
        $pdo = Database::getConnessione();

        $idsGiocatori = array_values(array_unique(array_filter(array_map('intval', $idsGiocatori), fn(int $id) => $id > 0)));

        try {
            $pdo->beginTransaction();

            $delete = $pdo->prepare("
            DELETE FROM EdizioneSquadraGiocatore
            WHERE IDEdizione = :idEdizione
              AND IDSquadra = :idSquadra
        ");

            $delete->execute([
                'idEdizione' => $idEdizione,
                'idSquadra' => $idSquadra,
            ]);

            if ($idsGiocatori !== []) {
                $insert = $pdo->prepare("
                INSERT INTO EdizioneSquadraGiocatore (IDEdizione, IDSquadra, IDGiocatore)
                VALUES (:idEdizione, :idSquadra, :idGiocatore)
            ");

                foreach ($idsGiocatori as $idGiocatore) {
                    $insert->execute([
                        'idEdizione' => $idEdizione,
                        'idSquadra' => $idSquadra,
                        'idGiocatore' => $idGiocatore,
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
        return $this->squadreEdizione($idEdizione);
    }

    public function salvaSquadreCompetizione(
        int $idEdizioneCompetizione,
        array $idsSquadre,
        string $stato = 'Iscritta',
        ?string $motivo = null
    ): void {
        $pdo = Database::getConnessione();

        $idsSquadre = array_values(array_unique(array_filter(array_map('intval', $idsSquadre), fn(int $id) => $id > 0)));

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

    public function verificaRosaSquadra(int $idEdizione, int $idSquadra): array
    {
        $giocatori = $this->giocatoriAssegnatiASquadra($idEdizione, $idSquadra);

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

        $richiesti = [
            'totale' => 18,
            'POR' => 2,
            'difensivi' => 5,
            'centrocampo' => 6,
            'offensivi' => 5,
        ];

        $mancanze = [
            'totale' => max(0, $richiesti['totale'] - $conteggi['totale']),
            'POR' => max(0, $richiesti['POR'] - $conteggi['POR']),
            'difensivi' => max(0, $richiesti['difensivi'] - $conteggi['difensivi']),
            'centrocampo' => max(0, $richiesti['centrocampo'] - $conteggi['centrocampo']),
            'offensivi' => max(0, $richiesti['offensivi'] - $conteggi['offensivi']),
        ];

        $ok = $mancanze['totale'] === 0
            && $mancanze['POR'] === 0
            && $mancanze['difensivi'] === 0
            && $mancanze['centrocampo'] === 0
            && $mancanze['offensivi'] === 0;

        return [
            'ok' => $ok,
            'conteggi' => $conteggi,
            'richiesti' => $richiesti,
            'mancanze' => $mancanze,
        ];
    }

    public function tutteLeRoseComplete(int $idEdizione): bool
    {
        $squadre = $this->squadreEdizione($idEdizione);

        foreach ($squadre as $squadra) {
            $verifica = $this->verificaRosaSquadra($idEdizione, (int) $squadra['IDSquadra']);

            if (!(bool) ($verifica['ok'] ?? false)) {
                return false;
            }
        }

        return true;
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

    public function riepilogoDuplicatiCompetizione(int $idEdizione, int $idEdizioneCompetizioneCorrente, array $idsSquadre): array
    {
        $altre = $this->squadreConAltreCompetizioni($idEdizione, $idEdizioneCompetizioneCorrente);
        $risultato = [];

        foreach ($idsSquadre as $idSquadra) {
            $idSquadra = (int) $idSquadra;

            if (isset($altre[$idSquadra])) {
                $risultato[$idSquadra] = $altre[$idSquadra];
            }
        }

        return $risultato;
    }

    public function trovaEdizioneSquadre(int $idEdizione): array
    {
        return $this->squadreEdizione($idEdizione);
    }

    public function trovaGiocatoriNonAssegnati(int $idEdizione): array
    {
        $pdo = Database::getConnessione();

        $statement = $pdo->prepare("
        SELECT
            eg.IDEdizione,
            eg.IDGiocatore,
            eg.Attacco,
            eg.Difesa,
            g.Nome,
            g.Paese,
            g.Posizione
        FROM EdizioneGiocatore eg
        INNER JOIN Giocatori g ON g.ID = eg.IDGiocatore
        WHERE eg.IDEdizione = :idEdizione
          AND NOT EXISTS (
              SELECT 1
              FROM EdizioneSquadraGiocatore esg
              WHERE esg.IDEdizione = eg.IDEdizione
                AND esg.IDGiocatore = eg.IDGiocatore
          )
        ORDER BY g.Nome ASC, g.ID ASC
    ");

        $statement->execute([
            'idEdizione' => $idEdizione,
        ]);

        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function raggruppaPerRuolo(array $giocatori): array
    {
        $gruppi = [
            'POR' => [],
            'DIF' => [],
            'CEN' => [],
            'OFF' => [],
            'ALT' => [],
        ];

        foreach ($giocatori as $giocatore) {
            $posizione = strtoupper(trim((string) ($giocatore['Posizione'] ?? '')));
            if ($posizione === 'POR') {
                $gruppi['POR'][] = $giocatore;
            } elseif (in_array($posizione, ['TD', 'TS', 'DC'], true)) {
                $gruppi['DIF'][] = $giocatore;
            } elseif (in_array($posizione, ['CC', 'MED', 'CS', 'CD', 'TRQ'], true)) {
                $gruppi['CEN'][] = $giocatore;
            } elseif (in_array($posizione, ['AS', 'AD', 'ATT'], true)) {
                $gruppi['OFF'][] = $giocatore;
            } else {
                $gruppi['ALT'][] = $giocatore;
            }
        }

        return $gruppi;
    }

    public function autoAssegnaRose(int $idEdizione, ?int $soloIdSquadra = null): array
    {
        $pdo = Database::getConnessione();
        $squadre = $this->squadreEdizione($idEdizione);

        if ($soloIdSquadra !== null) {
            $squadre = array_values(array_filter(
                $squadre,
                fn(array $squadra): bool => (int) ($squadra['IDSquadra'] ?? 0) === $soloIdSquadra
            ));

            if ($squadre === []) {
                throw new \RuntimeException('Squadra non trovata nell’edizione');
            }
        }

        $giocatori = $this->trovaGiocatoriNonAssegnati($idEdizione);
        $gruppi = $this->raggruppaPerRuolo($giocatori);
        $assegnati = [];

        $target = ['POR' => 2, 'DIF' => 5, 'CEN' => 6, 'OFF' => 5];

        try {
            $pdo->beginTransaction();

            $insert = $pdo->prepare("
            INSERT INTO EdizioneSquadraGiocatore (IDEdizione, IDSquadra, IDGiocatore)
            VALUES (:idEdizione, :idSquadra, :idGiocatore)
        ");

            foreach ($squadre as $squadra) {
                $idSquadra = (int) ($squadra['IDSquadra'] ?? 0);

                if ($idSquadra <= 0) {
                    throw new \RuntimeException('Squadra non valida nell’edizione');
                }

                $correnti = $this->giocatoriAssegnatiASquadra($idEdizione, $idSquadra);
                $presenti = [];
                $conteggi = ['POR' => 0, 'DIF' => 0, 'CEN' => 0, 'OFF' => 0];

                foreach ($correnti as $g) {
                    $idGiocatore = (int) ($g['IDGiocatore'] ?? 0);
                    if ($idGiocatore > 0) {
                        $presenti[$idGiocatore] = true;
                    }

                    $posizione = strtoupper(trim((string) ($g['Posizione'] ?? '')));
                    if ($posizione === 'POR') {
                        $conteggi['POR']++;
                    } elseif (in_array($posizione, ['TD', 'TS', 'DC'], true)) {
                        $conteggi['DIF']++;
                    } elseif (in_array($posizione, ['CC', 'MED', 'CS', 'CD', 'TRQ'], true)) {
                        $conteggi['CEN']++;
                    } elseif (in_array($posizione, ['AS', 'AD', 'ATT'], true)) {
                        $conteggi['OFF']++;
                    }
                }

                foreach ($target as $ruolo => $necessari) {
                    $mancanti = $necessari - $conteggi[$ruolo];
                    if ($mancanti <= 0) {
                        continue;
                    }

                    if (count($gruppi[$ruolo]) < $mancanti) {
                        throw new \RuntimeException("Giocatori insufficienti per completare il ruolo {$ruolo} della squadra ID {$idSquadra}.");
                    }

                    for ($i = 0; $i < $mancanti; $i++) {
                        $giocatore = array_shift($gruppi[$ruolo]);
                        $idGiocatore = (int) ($giocatore['IDGiocatore'] ?? 0);

                        if ($idGiocatore <= 0 || isset($presenti[$idGiocatore])) {
                            $i--;
                            continue;
                        }

                        $insert->execute([
                            'idEdizione' => $idEdizione,
                            'idSquadra' => $idSquadra,
                            'idGiocatore' => $idGiocatore,
                        ]);

                        $presenti[$idGiocatore] = true;
                        $assegnati[] = $idGiocatore;
                    }
                }
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }

        return $assegnati;
    }

    public function contaPartitePerCompetizione(int $idEdizioneCompetizione): int
    {
        $pdo = Database::getConnessione();

        $statement = $pdo->prepare("
        SELECT COUNT(*)
        FROM Partite
        WHERE IDEdizioneCompetizione = :idEdizioneCompetizione
    ");

        $statement->execute([
            'idEdizioneCompetizione' => $idEdizioneCompetizione,
        ]);

        return (int) $statement->fetchColumn();
    }

    public function creaPartiteBatch(array $partite): void
    {
        if ($partite === []) {
            return;
        }

        $pdo = Database::getConnessione();

        $statement = $pdo->prepare("
        INSERT INTO Partite (
            IDEdizioneCompetizione,
            IDSquadraCasa,
            IDSquadraTrasferta,
            GoalCasa,
            GoalTrasferta,
            Fase,
            Giornata,
            Girone,
            Data,
            Stato,
            Dettagli
        ) VALUES (
            :idEdizioneCompetizione,
            :idSquadraCasa,
            :idSquadraTrasferta,
            NULL,
            NULL,
            :fase,
            :giornata,
            :girone,
            NULL,
            :stato,
            :dettagli
        )
    ");

        try {
            $pdo->beginTransaction();

            foreach ($partite as $partita) {
                $statement->execute([
                    'idEdizioneCompetizione' => (int) $partita['id_edizione_competizione'],
                    'idSquadraCasa' => (int) $partita['id_squadra_casa'],
                    'idSquadraTrasferta' => (int) $partita['id_squadra_trasferta'],
                    'fase' => (string) $partita['fase'],
                    'giornata' => $partita['giornata'] !== null ? (int) $partita['giornata'] : null,
                    'girone' => $partita['girone'],
                    'stato' => (string) $partita['stato'],
                    'dettagli' => $partita['dettagli'],
                ]);
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $e;
        }
    }

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
            st.Nome AS NomeSquadraTrasferta
        FROM Partite p
        INNER JOIN Squadre sc ON sc.ID = p.IDSquadraCasa
        INNER JOIN Squadre st ON st.ID = p.IDSquadraTrasferta
        WHERE p.IDEdizioneCompetizione = :idEdizioneCompetizione
        ORDER BY
            p.Giornata ASC,
            p.ID ASC
    ");

        $statement->execute([
            'idEdizioneCompetizione' => $idEdizioneCompetizione,
        ]);

        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function aggiornaRisultatoPartita(
        int $idPartita,
        ?int $goalCasa,
        ?int $goalTrasferta,
        string $stato = 'giocata'
    ): bool {
        $pdo = Database::getConnessione();

        $statement = $pdo->prepare("
        UPDATE Partite
        SET
            GoalCasa = :goalCasa,
            GoalTrasferta = :goalTrasferta,
            Stato = :stato
        WHERE ID = :id
        LIMIT 1
    ");

        return $statement->execute([
            'id' => $idPartita,
            'goalCasa' => $goalCasa,
            'goalTrasferta' => $goalTrasferta,
            'stato' => $stato,
        ]);
    }

    public function findPartita(int $idPartita): ?array
    {
        $pdo = Database::getConnessione();

        $statement = $pdo->prepare("
        SELECT *
        FROM Partite
        WHERE ID = :id
        LIMIT 1
    ");

        $statement->execute([
            'id' => $idPartita,
        ]);

        $riga = $statement->fetch(PDO::FETCH_ASSOC);

        return $riga !== false ? $riga : null;
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
}
