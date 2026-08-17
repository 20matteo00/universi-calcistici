<?php

declare(strict_types=1);

namespace App\Services\Edizioni;

use App\Config\Database;
use App\Models\EdizioneGiocatore;
use App\Models\EdizioneSquadra;

class RosaAutoAssignService
{
    private EdizioneSquadra $edizioneSquadre;
    private EdizioneGiocatore $edizioneGiocatori;

    public function __construct()
    {
        $this->edizioneSquadre = new EdizioneSquadra();
        $this->edizioneGiocatori = new EdizioneGiocatore();
    }

    public function autoAssegnaRose(int $idEdizione, ?int $soloIdSquadra = null): array
    {
        $pdo = Database::getConnessione();
        $squadre = $this->edizioneSquadre->squadreEdizione($idEdizione);

        if ($soloIdSquadra !== null) {
            $squadre = array_values(array_filter(
                $squadre,
                fn(array $squadra): bool => (int) ($squadra['IDSquadra'] ?? 0) === $soloIdSquadra
            ));

            if ($squadre === []) {
                throw new \RuntimeException('Squadra non trovata nell’edizione');
            }
        }

        $giocatori = $this->edizioneGiocatori->trovaGiocatoriNonAssegnati($idEdizione);
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

                $correnti = $this->edizioneGiocatori->giocatoriAssegnatiASquadra($idEdizione, $idSquadra);
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
}