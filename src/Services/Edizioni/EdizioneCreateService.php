<?php

declare(strict_types=1);

namespace App\Services\Edizioni;

use App\Config\Database;
use App\Models\Edizione;
use Throwable;

class EdizioneCreateService
{
    public function creaPrimaEdizione(
        int $idUniverso,
        int $anno,
        string $nome,
        string $stato,
        bool $copiaGiocatori
    ): int {
        $pdo = Database::getConnessione();
        $edizioni = new Edizione();

        try {
            $pdo->beginTransaction();

            $idEdizione = $edizioni->create([
                'id_universo' => $idUniverso,
                'anno' => $anno,
                'nome' => $nome,
                'stato' => $stato,
            ]);

            $this->copiaSquadreInEdizione($idUniverso, $idEdizione);

            if ($copiaGiocatori) {
                $this->copiaGiocatoriInEdizione($idUniverso, $idEdizione);
            }

            $this->creaEdizioniCompetizione($idUniverso, $idEdizione);

            $pdo->commit();

            return $idEdizione;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $e;
        }
    }

    private function copiaSquadreInEdizione(int $idUniverso, int $idEdizione): void
    {
        $pdo = Database::getConnessione();

        $statement = $pdo->prepare("
            INSERT INTO EdizioneSquadra (IDEdizione, IDSquadra, Valore, FattoreCasa)
            SELECT
                :idEdizione,
                s.ID,
                s.Valore,
                s.FattoreCasa
            FROM UniversoSquadre us
            INNER JOIN Squadre s ON s.ID = us.IDSquadra
            WHERE us.IDUniverso = :idUniverso
        ");

        $statement->execute([
            'idEdizione' => $idEdizione,
            'idUniverso' => $idUniverso,
        ]);
    }

    private function copiaGiocatoriInEdizione(int $idUniverso, int $idEdizione): void
    {
        $pdo = Database::getConnessione();

        $statement = $pdo->prepare("
            INSERT INTO EdizioneGiocatore (IDEdizione, IDGiocatore, Attacco, Difesa)
            SELECT
                :idEdizione,
                g.ID,
                g.Attacco,
                g.Difesa
            FROM UniversoGiocatori ug
            INNER JOIN Giocatori g ON g.ID = ug.IDGiocatore
            WHERE ug.IDUniverso = :idUniverso
        ");

        $statement->execute([
            'idEdizione' => $idEdizione,
            'idUniverso' => $idUniverso,
        ]);
    }

    private function creaEdizioniCompetizione(int $idUniverso, int $idEdizione): void
    {
        $pdo = Database::getConnessione();

        $statement = $pdo->prepare("
            INSERT INTO EdizioneCompetizione (IDEdizione, IDCompetizione)
            SELECT
                :idEdizione,
                c.ID
            FROM Competizioni c
            WHERE c.IDUniverso = :idUniverso
        ");

        $statement->execute([
            'idEdizione' => $idEdizione,
            'idUniverso' => $idUniverso,
        ]);
    }
}