<?php

namespace App\Controllers;

use App\Http\Request;
use App\Models\Squadra;
use App\Support\Countries;

class SquadraController
{
    public function lista(Request $request, array $parametri): void
    {
        $filtri = [
            'q' => $request->query['q'] ?? '',
            'paese' => $request->query['paese'] ?? '',
            'tipo' => $request->query['tipo'] ?? '',
            'sort' => $request->query['sort'] ?? 'ID',
            'dir' => $request->query['dir'] ?? 'asc',
            'page' => $request->query['page'] ?? 1,
            'per_page' => $request->query['per_page'] ?? 25,
        ];

        $risultato = Squadra::cerca($filtri);

        $squadre = $risultato['righe'];
        $totale = $risultato['totale'];
        $page = $risultato['page'];
        $perPage = $risultato['per_page'];
        $pagineTotali = $risultato['pagine_totali'];
        $paesi = Countries::all();

        require __DIR__ . '/../Views/squadre/index.php';
    }

    public function crea(Request $request, array $parametri): void
    {
        $errori = [];
        $vecchiDati = [
            'nome' => '',
            'paese' => '',
            'tipo' => 'Club',
            'valore' => '0',
            'fattore_casa' => '0',
            'colore_sfondo' => '',
            'colore_testo' => '',
            'colore_bordo' => '',
        ];

        $paesi = Countries::all();

        require __DIR__ . '/../Views/squadre/create.php';
    }

    public function salva(Request $request, array $parametri): void
    {
        $nome = trim($request->body['nome'] ?? '');
        $paese = trim($request->body['paese'] ?? '');
        $tipo = trim($request->body['tipo'] ?? 'Club');
        $valore = $request->body['valore'] ?? '0';
        $fattoreCasa = $request->body['fattore_casa'] ?? '0';
        $coloreSfondo = trim($request->body['colore_sfondo'] ?? '');
        $coloreTesto = trim($request->body['colore_testo'] ?? '');
        $coloreBordo = trim($request->body['colore_bordo'] ?? '');

        $errori = [];
        $paesi = Countries::all();

        if ($nome === '') {
            $errori[] = 'Il nome è obbligatorio.';
        }

        if ($paese !== '' && !array_key_exists($paese, $paesi)) {
            $errori[] = 'Il paese selezionato non è valido.';
        }

        if (!in_array($tipo, ['Club', 'Nazionale'], true)) {
            $errori[] = 'Il tipo selezionato non è valido.';
        }

        if ($valore !== '' && !is_numeric($valore)) {
            $errori[] = 'Il valore deve essere numerico.';
        }

        if ($fattoreCasa !== '' && !is_numeric($fattoreCasa)) {
            $errori[] = 'Il fattore casa deve essere numerico.';
        }

        $vecchiDati = [
            'nome' => $nome,
            'paese' => $paese,
            'tipo' => $tipo,
            'valore' => $valore,
            'fattore_casa' => $fattoreCasa,
            'colore_sfondo' => $coloreSfondo,
            'colore_testo' => $coloreTesto,
            'colore_bordo' => $coloreBordo,
        ];

        if ($errori !== []) {
            require __DIR__ . '/../Views/squadre/create.php';
            return;
        }

        $colori = null;

        if ($coloreSfondo !== '' || $coloreTesto !== '' || $coloreBordo !== '') {
            $colori = [
                'sfondo' => $coloreSfondo !== '' ? $coloreSfondo : null,
                'testo' => $coloreTesto !== '' ? $coloreTesto : null,
                'bordo' => $coloreBordo !== '' ? $coloreBordo : null,
            ];
        }

        Squadra::crea(
            $nome,
            $paese !== '' ? $paese : null,
            $tipo,
            $valore !== '' ? (float) $valore : 0,
            $fattoreCasa !== '' ? (float) $fattoreCasa : 0,
            $colori
        );

        header('Location: /squadre');
        exit;
    }

    public function modifica(Request $request, array $parametri): void
    {
        $id = (int) ($parametri['id'] ?? 0);
        $squadra = Squadra::trovaPerId($id);

        if (!$squadra) {
            http_response_code(404);
            echo 'Squadra non trovata';
            return;
        }

        $colori = [];

        if (!empty($squadra['Colori'])) {
            $decoded = json_decode($squadra['Colori'], true);
            $colori = is_array($decoded) ? $decoded : [];
        }

        $errori = [];
        $paesi = Countries::all();

        $vecchiDati = [
            'nome' => $squadra['Nome'] ?? '',
            'paese' => $squadra['Paese'] ?? '',
            'tipo' => $squadra['Tipo'] ?? 'Club',
            'valore' => $squadra['Valore'] ?? '0',
            'fattore_casa' => $squadra['FattoreCasa'] ?? '0',
            'colore_sfondo' => $colori['sfondo'] ?? '',
            'colore_testo' => $colori['testo'] ?? '',
            'colore_bordo' => $colori['bordo'] ?? '',
        ];

        require __DIR__ . '/../Views/squadre/edit.php';
    }

    public function aggiorna(Request $request, array $parametri): void
    {
        $id = (int) ($parametri['id'] ?? 0);
        $squadra = Squadra::trovaPerId($id);

        if (!$squadra) {
            http_response_code(404);
            echo 'Squadra non trovata';
            return;
        }

        $nome = trim($request->body['nome'] ?? '');
        $paese = trim($request->body['paese'] ?? '');
        $tipo = trim($request->body['tipo'] ?? 'Club');
        $valore = $request->body['valore'] ?? '0';
        $fattoreCasa = $request->body['fattore_casa'] ?? '0';
        $coloreSfondo = trim($request->body['colore_sfondo'] ?? '');
        $coloreTesto = trim($request->body['colore_testo'] ?? '');
        $coloreBordo = trim($request->body['colore_bordo'] ?? '');

        $errori = [];
        $paesi = Countries::all();

        if ($nome === '') {
            $errori[] = 'Il nome è obbligatorio.';
        }

        if ($paese !== '' && !array_key_exists($paese, $paesi)) {
            $errori[] = 'Il paese selezionato non è valido.';
        }

        if (!in_array($tipo, ['Club', 'Nazionale'], true)) {
            $errori[] = 'Il tipo selezionato non è valido.';
        }

        if ($valore !== '' && !is_numeric($valore)) {
            $errori[] = 'Il valore deve essere numerico.';
        }

        if ($fattoreCasa !== '' && !is_numeric($fattoreCasa)) {
            $errori[] = 'Il fattore casa deve essere numerico.';
        }

        $vecchiDati = [
            'nome' => $nome,
            'paese' => $paese,
            'tipo' => $tipo,
            'valore' => $valore,
            'fattore_casa' => $fattoreCasa,
            'colore_sfondo' => $coloreSfondo,
            'colore_testo' => $coloreTesto,
            'colore_bordo' => $coloreBordo,
        ];

        if ($errori !== []) {
            require __DIR__ . '/../Views/squadre/edit.php';
            return;
        }

        $colori = null;

        if ($coloreSfondo !== '' || $coloreTesto !== '' || $coloreBordo !== '') {
            $colori = [
                'sfondo' => $coloreSfondo !== '' ? $coloreSfondo : null,
                'testo' => $coloreTesto !== '' ? $coloreTesto : null,
                'bordo' => $coloreBordo !== '' ? $coloreBordo : null,
            ];
        }

        Squadra::aggiorna(
            $id,
            $nome,
            $paese !== '' ? $paese : null,
            $tipo,
            $valore !== '' ? (float) $valore : 0,
            $fattoreCasa !== '' ? (float) $fattoreCasa : 0,
            $colori
        );

        header('Location: /squadre');
        exit;
    }

    public function elimina(Request $request, array $parametri): void
    {
        $id = (int) ($parametri['id'] ?? 0);

        if ($id > 0) {
            if (Squadra::inUsoInUniversi($id)) {
                header('Location: /squadre');
                exit;
            }

            Squadra::elimina($id);
        }

        header('Location: /squadre');
        exit;
    }



    public function duplica(Request $request, array $parametri): void
    {
        $id = (int) ($parametri['id'] ?? 0);

        if ($id > 0) {
            Squadra::duplica($id);
        }

        header('Location: /squadre');
        exit;
    }

    public function genera(Request $request, array $parametri): void
    {
        Squadra::generaRandom();

        header('Location: /squadre');
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

            if (Squadra::inUsoInUniversi($id)) {
                continue;
            }

            $idsEliminabili[] = $id;
        }

        Squadra::eliminaMultiplo($idsEliminabili);

        header('Location: /squadre');
        exit;
    }

    public function generaMultiplo(Request $request, array $parametri): void
    {
        $quantita = (int) ($request->body['quantita'] ?? 0);

        if ($quantita > 0) {
            Squadra::generaRandomMultiplo($quantita);
        }

        header('Location: /squadre');
        exit;
    }
}
