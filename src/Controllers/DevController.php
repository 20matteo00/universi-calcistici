<?php

namespace App\Controllers;

use App\Config\Database;
use App\Http\Request;
use PDOException;

class DevController
{
    public function resetDatabase(Request $request, array $parametri): void
    {
        $env = $_ENV['APP_ENV'] ?? 'production';

        if ($env !== 'local') {
            http_response_code(403);
            echo 'Operazione non consentita fuori da ambiente locale.';
            return;
        }

        $conferma = trim((string) ($request->body['conferma'] ?? ''));

        if ($conferma !== 'RESET') {
            http_response_code(400);
            echo 'Conferma non valida. Scrivi RESET.';
            return;
        }

        $db = Database::getConnessione();

        try {
            $db->exec('SET FOREIGN_KEY_CHECKS = 0');

            $tables = [
                'PartitaEventi',
                'Partite',
                'EdizioneCompetizioneSquadra',
                'EdizioneCompetizione',
                'CompetizioneAvanzamento',
                'EdizioneSquadreGiocatori',
                'EdizioneGiocatore',
                'EdizioneSquadra',
                'Edizioni',
                'UniversoGiocatori',
                'UniversoSquadre',
                'Competizioni',
                'Giocatori',
                'Squadre',
                'Universi',
            ];

            foreach ($tables as $table) {
                $db->exec("TRUNCATE TABLE {$table}");
            }

            $db->exec('SET FOREIGN_KEY_CHECKS = 1');

            header('Location: /?reset=ok');
            exit;
        } catch (PDOException $e) {
            $db->exec('SET FOREIGN_KEY_CHECKS = 1');
            http_response_code(500);
            echo 'Errore reset database: ' . $e->getMessage();
        }
    }
}