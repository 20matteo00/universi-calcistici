<?php

namespace App\Controllers;

use App\Http\Request;
use App\Models\Giocatore;
use App\Support\Countries;
use App\Support\Positions;

class GiocatoreController
{
    public function lista(Request $request, array $parametri): void
    {
        $filtri = [
            'q' => $request->query['q'] ?? '',
            'paese' => $request->query['paese'] ?? '',
            'posizione' => $request->query['posizione'] ?? '',
            'sort' => $request->query['sort'] ?? 'ID',
            'dir' => $request->query['dir'] ?? 'asc',
            'page' => $request->query['page'] ?? 1,
            'per_page' => $request->query['per_page'] ?? 25,
        ];

        $risultato = Giocatore::cerca($filtri);

        $giocatori = $risultato['righe'];
        $totale = $risultato['totale'];
        $page = $risultato['page'];
        $perPage = $risultato['per_page'];
        $pagineTotali = $risultato['pagine_totali'];

        $paesi = Countries::all();
        $posizioni = Positions::all();

        require __DIR__ . '/../Views/giocatori/index.php';
    }

    public function crea(Request $request, array $parametri): void
    {
        $errori = [];
        $vecchiDati = [
            'nome' => '',
            'posizione' => 'CC',
            'attacco' => '0',
            'difesa' => '0',
            'paese' => '',
            'nascita' => '',
        ];

        $paesi = Countries::all();
        $posizioni = Positions::all();

        require __DIR__ . '/../Views/giocatori/create.php';
    }

    public function salva(Request $request, array $parametri): void
    {
        $nome = trim($request->body['nome'] ?? '');
        $posizione = trim($request->body['posizione'] ?? 'CC');
        $attacco = $request->body['attacco'] ?? '0';
        $difesa = $request->body['difesa'] ?? '0';
        $paese = trim($request->body['paese'] ?? '');
        $nascita = trim($request->body['nascita'] ?? '');

        $errori = [];
        $paesi = Countries::all();
        $posizioni = Positions::all();

        if ($nome === '') {
            $errori[] = 'Il nome è obbligatorio.';
        }

        if (!array_key_exists($posizione, $posizioni)) {
            $errori[] = 'La posizione selezionata non è valida.';
        }

        if ($attacco !== '' && !is_numeric($attacco)) {
            $errori[] = 'Il valore attacco deve essere numerico.';
        }

        if ($difesa !== '' && !is_numeric($difesa)) {
            $errori[] = 'Il valore difesa deve essere numerico.';
        }

        if ($paese !== '' && !array_key_exists($paese, $paesi)) {
            $errori[] = 'Il paese selezionato non è valido.';
        }

        if ($nascita !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $nascita)) {
            $errori[] = 'La data di nascita deve essere nel formato YYYY-MM-DD.';
        }

        $vecchiDati = [
            'nome' => $nome,
            'posizione' => $posizione,
            'attacco' => $attacco,
            'difesa' => $difesa,
            'paese' => $paese,
            'nascita' => $nascita,
        ];

        if ($errori !== []) {
            require __DIR__ . '/../Views/giocatori/create.php';
            return;
        }

        Giocatore::crea(
            $nome,
            $posizione,
            $attacco !== '' ? (float) $attacco : 0,
            $difesa !== '' ? (float) $difesa : 0,
            $paese !== '' ? $paese : null,
            $nascita !== '' ? $nascita : null
        );

        header('Location: /giocatori');
        exit;
    }

    public function modifica(Request $request, array $parametri): void
    {
        $id = (int) ($parametri['id'] ?? 0);
        $giocatore = Giocatore::trovaPerId($id);

        if (!$giocatore) {
            http_response_code(404);
            echo 'Giocatore non trovato';
            return;
        }

        $errori = [];
        $paesi = Countries::all();
        $posizioni = Positions::all();

        $vecchiDati = [
            'nome' => $giocatore['Nome'] ?? '',
            'posizione' => $giocatore['Posizione'] ?? 'CC',
            'attacco' => $giocatore['Attacco'] ?? '0',
            'difesa' => $giocatore['Difesa'] ?? '0',
            'paese' => $giocatore['Paese'] ?? '',
            'nascita' => $giocatore['Nascita'] ?? '',
        ];

        require __DIR__ . '/../Views/giocatori/edit.php';
    }

    public function aggiorna(Request $request, array $parametri): void
    {
        $id = (int) ($parametri['id'] ?? 0);
        $giocatore = Giocatore::trovaPerId($id);

        if (!$giocatore) {
            http_response_code(404);
            echo 'Giocatore non trovato';
            return;
        }

        $nome = trim($request->body['nome'] ?? '');
        $posizione = trim($request->body['posizione'] ?? 'CC');
        $attacco = $request->body['attacco'] ?? '0';
        $difesa = $request->body['difesa'] ?? '0';
        $paese = trim($request->body['paese'] ?? '');
        $nascita = trim($request->body['nascita'] ?? '');

        $errori = [];
        $paesi = Countries::all();
        $posizioni = Positions::all();

        if ($nome === '') {
            $errori[] = 'Il nome è obbligatorio.';
        }

        if (!array_key_exists($posizione, $posizioni)) {
            $errori[] = 'La posizione selezionata non è valida.';
        }

        if ($attacco !== '' && !is_numeric($attacco)) {
            $errori[] = 'Il valore attacco deve essere numerico.';
        }

        if ($difesa !== '' && !is_numeric($difesa)) {
            $errori[] = 'Il valore difesa deve essere numerico.';
        }

        if ($paese !== '' && !array_key_exists($paese, $paesi)) {
            $errori[] = 'Il paese selezionato non è valido.';
        }

        if ($nascita !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $nascita)) {
            $errori[] = 'La data di nascita deve essere nel formato YYYY-MM-DD.';
        }

        $vecchiDati = [
            'nome' => $nome,
            'posizione' => $posizione,
            'attacco' => $attacco,
            'difesa' => $difesa,
            'paese' => $paese,
            'nascita' => $nascita,
        ];

        if ($errori !== []) {
            require __DIR__ . '/../Views/giocatori/edit.php';
            return;
        }

        Giocatore::aggiorna(
            $id,
            $nome,
            $posizione,
            $attacco !== '' ? (float) $attacco : 0,
            $difesa !== '' ? (float) $difesa : 0,
            $paese !== '' ? $paese : null,
            $nascita !== '' ? $nascita : null
        );

        header('Location: /giocatori');
        exit;
    }

    public function elimina(Request $request, array $parametri): void
    {
        $id = (int) ($parametri['id'] ?? 0);

        if ($id > 0) {
            if (Giocatore::inUsoInUniversi($id)) {
                header('Location: /giocatori');
                exit;
            }

            Giocatore::elimina($id);
        }

        header('Location: /giocatori');
        exit;
    }

    public function duplica(Request $request, array $parametri): void
    {
        $id = (int) ($parametri['id'] ?? 0);

        if ($id > 0) {
            Giocatore::duplica($id);
        }

        header('Location: /giocatori');
        exit;
    }

    public function genera(Request $request, array $parametri): void
    {
        Giocatore::generaRandom();

        header('Location: /giocatori');
        exit;
    }

    public function eliminaSelezionate(Request $request, array $parametri): void
    {
        $ids = $request->body['ids'] ?? [];

        if (!is_array($ids)) {
            $ids = [];
        }

        $idsEliminabili = [];

        foreach ($ids as $id) {
            $id = (int) $id;

            if ($id <= 0) {
                continue;
            }

            if (Giocatore::inUsoInUniversi($id)) {
                continue;
            }

            $idsEliminabili[] = $id;
        }

        Giocatore::eliminaMultiplo($idsEliminabili);

        header('Location: /giocatori');
        exit;
    }

    public function generaMultiplo(Request $request, array $parametri): void
    {
        $quantita = (int) ($request->body['quantita'] ?? 0);

        if ($quantita > 0) {
            Giocatore::generaRandomMultiplo($quantita);
        }

        header('Location: /giocatori');
        exit;
    }
}
