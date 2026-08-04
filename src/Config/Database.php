<?php

namespace App\Config;

use PDO;
use PDOException;

/**
 * Gestisce un'unica connessione PDO condivisa in tutta l'applicazione.
 * Le credenziali arrivano dal file .env (caricato in public/index.php).
 */
class Database
{
    private static ?PDO $connessione = null;

    public static function getConnessione(): PDO
    {
        if (self::$connessione === null) {
            $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
            $port = $_ENV['DB_PORT'] ?? '3306';
            $nome = $_ENV['DB_NAME'] ?? 'universi_calcistici';
            $user = $_ENV['DB_USER'] ?? 'root';
            $pass = $_ENV['DB_PASS'] ?? 'Matteo00';

            $dsn = "mysql:host={$host};port={$port};dbname={$nome};charset=utf8mb4";

            try {
                self::$connessione = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $e) {
                // In sviluppo va bene mostrare l'errore; in produzione andrebbe loggato soltanto.
                die('Errore di connessione al database: ' . $e->getMessage());
            }
        }

        return self::$connessione;
    }
}
