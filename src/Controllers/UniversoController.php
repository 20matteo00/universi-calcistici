<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Models\Universo;
use App\Support\Countries;
use App\Support\Positions;

class UniversoController
{
    private Universo $universi;

    public function __construct()
    {
        $this->universi = new Universo();
    }

    public function index(Request $request, array $parametri = []): void
    {
        $filtri = [
            'q' => trim((string) ($request->query['q'] ?? '')),
            'sort' => (string) ($request->query['sort'] ?? 'Creato'),
            'dir' => (string) ($request->query['dir'] ?? 'desc'),
        ];

        $universi = $this->universi->all($filtri);

        require __DIR__ . '/../Views/universi/index.php';
    }

    public function create(Request $request, array $parametri = []): void
    {
        $errori = [];
        $vecchiDati = [
            'nome' => '',
            'descrizione' => '',
        ];

        require __DIR__ . '/../Views/universi/create.php';
    }

    public function store(Request $request, array $parametri = []): void
    {
        $vecchiDati = [
            'nome' => trim((string) ($request->body['nome'] ?? '')),
            'descrizione' => trim((string) ($request->body['descrizione'] ?? '')),
        ];

        $errori = $this->valida($vecchiDati);

        if (!empty($errori)) {
            require __DIR__ . '/../Views/universi/create.php';
            return;
        }

        $id = $this->universi->create($vecchiDati);

        header('Location: /universi/' . $id);
        exit;
    }

    public function show(Request $request, array $parametri): void
    {
        $id = (int) ($parametri['id'] ?? 0);
        $universo = $this->universi->find($id);

        if ($universo === null) {
            http_response_code(404);
            echo 'Universo non trovato';
            return;
        }

        $haEdizioni = $this->universi->haEdizioni($id);
        $squadre = $this->universi->squadre($id);
        $giocatori = $this->universi->giocatori($id);
        $squadreDisponibili = $this->universi->squadreDisponibili($id);
        $giocatoriDisponibili = $this->universi->giocatoriDisponibili($id);

        $competizioni = $this->universi->competizioni($id);

        $numeroSquadreUniverso = count($squadre);

        $verificaRose = $this->universi->verificaRoseMinime($id);
        $roseMinimeOk = (bool) ($verificaRose['ok'] ?? false);

        $totalePartecipantiCompetizioni = $this->universi->totalePartecipantiCompetizioni($id);
        $coperturaCompetizioniOk = $totalePartecipantiCompetizioni >= $numeroSquadreUniverso;

        $dettaglioRose = $verificaRose;

        require __DIR__ . '/../Views/universi/show.php';
    }

    public function edit(Request $request, array $parametri): void
    {
        $id = (int) ($parametri['id'] ?? 0);
        $universo = $this->universi->find($id);

        if ($universo === null) {
            http_response_code(404);
            echo 'Universo non trovato';
            return;
        }

        $errori = [];
        $vecchiDati = [
            'nome' => (string) ($universo['Nome'] ?? ''),
            'descrizione' => (string) ($universo['Descrizione'] ?? ''),
        ];

        require __DIR__ . '/../Views/universi/edit.php';
    }

    public function update(Request $request, array $parametri): void
    {
        $id = (int) ($parametri['id'] ?? 0);
        $universo = $this->universi->find($id);

        if ($universo === null) {
            http_response_code(404);
            echo 'Universo non trovato';
            return;
        }

        $vecchiDati = [
            'nome' => trim((string) ($request->body['nome'] ?? '')),
            'descrizione' => trim((string) ($request->body['descrizione'] ?? '')),
        ];

        $errori = $this->valida($vecchiDati);

        if (!empty($errori)) {
            require __DIR__ . '/../Views/universi/edit.php';
            return;
        }

        $this->universi->update($id, $vecchiDati);

        header('Location: /universi/' . $id);
        exit;
    }

    public function delete(Request $request, array $parametri): void
    {
        $id = (int) ($parametri['id'] ?? 0);
        $universo = $this->universi->find($id);

        if ($universo === null) {
            http_response_code(404);
            echo 'Universo non trovato';
            return;
        }

        $this->universi->delete($id);

        header('Location: /universi');
        exit;
    }

    public function aggiungiSquadra(Request $request, array $parametri): void
    {
        $idUniverso = (int) ($parametri['id'] ?? 0);
        $idSquadra = (int) ($request->body['id_squadra'] ?? 0);

        $universo = $this->universi->find($idUniverso);

        if ($universo === null) {
            http_response_code(404);
            echo 'Universo non trovato';
            return;
        }

        if ($this->universi->haEdizioni($idUniverso)) {
            header('Location: /universi/' . $idUniverso);
            exit;
        }

        if ($idSquadra > 0) {
            $this->universi->aggiungiSquadra($idUniverso, $idSquadra);
        }

        header('Location: /universi/' . $idUniverso);
        exit;
    }

    public function rimuoviSquadra(Request $request, array $parametri): void
    {
        $idUniverso = (int) ($parametri['id'] ?? 0);
        $idSquadra = (int) ($parametri['idSquadra'] ?? 0);

        $universo = $this->universi->find($idUniverso);

        if ($universo === null) {
            http_response_code(404);
            echo 'Universo non trovato';
            return;
        }

        if ($this->universi->haEdizioni($idUniverso)) {
            header('Location: /universi/' . $idUniverso);
            exit;
        }

        if ($idSquadra > 0) {
            $this->universi->rimuoviSquadra($idUniverso, $idSquadra);
        }

        header('Location: /universi/' . $idUniverso);
        exit;
    }

    public function aggiungiGiocatore(Request $request, array $parametri): void
    {
        $idUniverso = (int) ($parametri['id'] ?? 0);
        $idGiocatore = (int) ($request->body['id_giocatore'] ?? 0);

        $universo = $this->universi->find($idUniverso);

        if ($universo === null) {
            http_response_code(404);
            echo 'Universo non trovato';
            return;
        }

        if ($this->universi->haEdizioni($idUniverso)) {
            header('Location: /universi/' . $idUniverso);
            exit;
        }

        if ($idGiocatore > 0) {
            $this->universi->aggiungiGiocatore($idUniverso, $idGiocatore);
        }

        header('Location: /universi/' . $idUniverso);
        exit;
    }

    public function rimuoviGiocatore(Request $request, array $parametri): void
    {
        $idUniverso = (int) ($parametri['id'] ?? 0);
        $idGiocatore = (int) ($parametri['idGiocatore'] ?? 0);

        $universo = $this->universi->find($idUniverso);

        if ($universo === null) {
            http_response_code(404);
            echo 'Universo non trovato';
            return;
        }

        if ($this->universi->haEdizioni($idUniverso)) {
            header('Location: /universi/' . $idUniverso);
            exit;
        }

        if ($idGiocatore > 0) {
            $this->universi->rimuoviGiocatore($idUniverso, $idGiocatore);
        }

        header('Location: /universi/' . $idUniverso);
        exit;
    }

    private function valida(array $dati): array
    {
        $errori = [];

        $nome = trim((string) ($dati['nome'] ?? ''));
        $descrizione = trim((string) ($dati['descrizione'] ?? ''));

        if ($nome === '') {
            $errori[] = 'Il nome è obbligatorio.';
        } elseif (mb_strlen($nome) > 150) {
            $errori[] = 'Il nome non può superare 150 caratteri.';
        }

        if ($descrizione !== '' && mb_strlen($descrizione) > 65535) {
            $errori[] = 'La descrizione è troppo lunga.';
        }

        return $errori;
    }

    public function gestisciSquadre(Request $request, array $parametri): void
    {
        $id = (int) ($parametri['id'] ?? 0);
        $universo = $this->universi->find($id);

        if ($universo === null) {
            http_response_code(404);
            echo 'Universo non trovato';
            return;
        }

        $filtri = [
            'q' => trim((string) ($request->query['q'] ?? '')),
            'paese' => trim((string) ($request->query['paese'] ?? '')),
            'tipo' => trim((string) ($request->query['tipo'] ?? '')),
            'sort' => (string) ($request->query['sort'] ?? 'ID'),
            'dir' => (string) ($request->query['dir'] ?? 'asc'),
            'page' => max(1, (int) ($request->query['page'] ?? 1)),
            'per_page' => (int) ($request->query['per_page'] ?? 25),
        ];

        $haEdizioni = $this->universi->haEdizioni($id);
        $disponibili = $this->universi->cercaSquadreDisponibili($id, $filtri);
        $squadreUniverso = $this->universi->squadre($id);
        $paesi = Countries::all();

        require __DIR__ . '/../Views/universi/squadre.php';
    }

    public function aggiungiSquadreSelezionate(Request $request, array $parametri): void
    {
        $id = (int) ($parametri['id'] ?? 0);
        $universo = $this->universi->find($id);

        if ($universo === null) {
            http_response_code(404);
            echo 'Universo non trovato';
            return;
        }

        if ($this->universi->haEdizioni($id)) {
            header('Location: /universi/' . $id . '/squadre');
            exit;
        }

        $ids = $request->body['ids'] ?? [];

        if (!is_array($ids)) {
            $ids = [];
        }

        foreach ($ids as $idSquadra) {
            $idSquadra = (int) $idSquadra;

            if ($idSquadra > 0) {
                $this->universi->aggiungiSquadra($id, $idSquadra);
            }
        }

        header('Location: /universi/' . $id . '/squadre');
        exit;
    }

    public function gestisciGiocatori(Request $request, array $parametri): void
    {
        $id = (int) ($parametri['id'] ?? 0);
        $universo = $this->universi->find($id);

        if ($universo === null) {
            http_response_code(404);
            echo 'Universo non trovato';
            return;
        }

        $filtri = [
            'q' => trim((string) ($request->query['q'] ?? '')),
            'paese' => trim((string) ($request->query['paese'] ?? '')),
            'posizione' => trim((string) ($request->query['posizione'] ?? '')),
            'sort' => (string) ($request->query['sort'] ?? 'ID'),
            'dir' => (string) ($request->query['dir'] ?? 'asc'),
            'page' => max(1, (int) ($request->query['page'] ?? 1)),
            'per_page' => (int) ($request->query['per_page'] ?? 25),
        ];

        $haEdizioni = $this->universi->haEdizioni($id);
        $disponibili = $this->universi->cercaGiocatoriDisponibili($id, $filtri);
        $giocatoriUniverso = $this->universi->giocatori($id);
        $paesi = Countries::all();
        $posizioni = Positions::all();

        require __DIR__ . '/../Views/universi/giocatori.php';
    }

    public function aggiungiGiocatoriSelezionati(Request $request, array $parametri): void
    {
        $id = (int) ($parametri['id'] ?? 0);
        $universo = $this->universi->find($id);

        if ($universo === null) {
            http_response_code(404);
            echo 'Universo non trovato';
            return;
        }

        if ($this->universi->haEdizioni($id)) {
            header('Location: /universi/' . $id . '/giocatori');
            exit;
        }

        $ids = $request->body['ids'] ?? [];

        if (!is_array($ids)) {
            $ids = [];
        }

        foreach ($ids as $idGiocatore) {
            $idGiocatore = (int) $idGiocatore;

            if ($idGiocatore > 0) {
                $this->universi->aggiungiGiocatore($id, $idGiocatore);
            }
        }

        header('Location: /universi/' . $id . '/giocatori');
        exit;
    }

    public function rimuoviSquadreSelezionate(Request $request, array $parametri): void
    {
        $id = (int) ($parametri['id'] ?? 0);
        $universo = $this->universi->find($id);

        if ($universo === null) {
            http_response_code(404);
            echo 'Universo non trovato';
            return;
        }

        if ($this->universi->haEdizioni($id)) {
            header('Location: /universi/' . $id . '/squadre');
            exit;
        }

        $ids = $request->body['ids'] ?? [];

        if (!is_array($ids)) {
            $ids = [];
        }

        foreach ($ids as $idSquadra) {
            $idSquadra = (int) $idSquadra;

            if ($idSquadra > 0) {
                $this->universi->rimuoviSquadra($id, $idSquadra);
            }
        }

        header('Location: /universi/' . $id . '/squadre');
        exit;
    }

    public function rimuoviGiocatoriSelezionati(Request $request, array $parametri): void
    {
        $id = (int) ($parametri['id'] ?? 0);
        $universo = $this->universi->find($id);

        if ($universo === null) {
            http_response_code(404);
            echo 'Universo non trovato';
            return;
        }

        if ($this->universi->haEdizioni($id)) {
            header('Location: /universi/' . $id . '/giocatori');
            exit;
        }

        $ids = $request->body['ids'] ?? [];

        if (!is_array($ids)) {
            $ids = [];
        }

        foreach ($ids as $idGiocatore) {
            $idGiocatore = (int) $idGiocatore;

            if ($idGiocatore > 0) {
                $this->universi->rimuoviGiocatore($id, $idGiocatore);
            }
        }

        header('Location: /universi/' . $id . '/giocatori');
        exit;
    }
}
