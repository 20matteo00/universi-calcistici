<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Models\Competizione;
use App\Models\CompetizioneCollegamento;
use App\Models\Universo;
use App\Services\Competizioni\CompetizioneCollegamentoService;

class CompetizioneCollegamentoController
{
    private CompetizioneCollegamento $collegamenti;
    private Competizione $competizioni;
    private CompetizioneCollegamentoService $service;

    public function __construct()
    {
        $this->collegamenti = new CompetizioneCollegamento();
        $this->competizioni = new Competizione();
        $this->service = new CompetizioneCollegamentoService();
    }

    public function createByUniverso(Request $request, array $parametri = []): void
    {
        $idUniverso = (int) ($parametri['id'] ?? 0);

        $universoModel = new Universo();
        $universo = $universoModel->find($idUniverso);

        if ($universo === null) {
            http_response_code(404);
            echo 'Universo non trovato';
            return;
        }

        $competizioni = $this->competizioni->allByUniverso($idUniverso);
        $errori = [];
        $vecchiDati = $this->service->defaultFormData();

        require __DIR__ . '/../Views/competizioni/collegamenti/create.php';
    }

    public function storeByUniverso(Request $request, array $parametri = []): void
    {
        $idUniverso = (int) ($parametri['id'] ?? 0);

        $universoModel = new Universo();
        $universo = $universoModel->find($idUniverso);

        if ($universo === null) {
            http_response_code(404);
            echo 'Universo non trovato';
            return;
        }

        $competizioni = $this->competizioni->allByUniverso($idUniverso);
        $vecchiDati = $this->service->formDataFromRequest($request->body);
        $errori = $this->service->validateByUniverso($idUniverso, $vecchiDati);

        if (!empty($errori)) {
            require __DIR__ . '/../Views/competizioni/collegamenti/create.php';
            return;
        }

        $this->collegamenti->create([
            'id_competizione_partenza' => $vecchiDati['id_competizione_partenza'],
            'id_competizione_arrivo' => $vecchiDati['id_competizione_arrivo'],
            'ordine' => $vecchiDati['ordine'],
            'dettagli' => $vecchiDati['dettagli'] !== '' ? $vecchiDati['dettagli'] : null,
        ]);

        header('Location: /universi/' . $idUniverso . '/competizioni');
        exit;
    }

    public function editByUniverso(Request $request, array $parametri = []): void
    {
        $idUniverso = (int) ($parametri['id'] ?? 0);
        $idCollegamento = (int) ($parametri['idCollegamento'] ?? 0);

        $universoModel = new Universo();
        $universo = $universoModel->find($idUniverso);

        if ($universo === null) {
            http_response_code(404);
            echo 'Universo non trovato';
            return;
        }

        $collegamento = $this->collegamenti->findByUniverso($idUniverso, $idCollegamento);

        if ($collegamento === null) {
            http_response_code(404);
            echo 'Collegamento non trovato';
            return;
        }

        $competizioni = $this->competizioni->allByUniverso($idUniverso);
        $errori = [];
        $vecchiDati = $this->service->formDataFromRecord($collegamento);

        require __DIR__ . '/../Views/competizioni/collegamenti/edit.php';
    }

    public function updateByUniverso(Request $request, array $parametri = []): void
    {
        $idUniverso = (int) ($parametri['id'] ?? 0);
        $idCollegamento = (int) ($parametri['idCollegamento'] ?? 0);

        $universoModel = new Universo();
        $universo = $universoModel->find($idUniverso);

        if ($universo === null) {
            http_response_code(404);
            echo 'Universo non trovato';
            return;
        }

        $collegamento = $this->collegamenti->findByUniverso($idUniverso, $idCollegamento);

        if ($collegamento === null) {
            http_response_code(404);
            echo 'Collegamento non trovato';
            return;
        }

        $competizioni = $this->competizioni->allByUniverso($idUniverso);
        $vecchiDati = $this->service->formDataFromRequest($request->body);
        $errori = $this->service->validateByUniverso($idUniverso, $vecchiDati);

        if (!empty($errori)) {
            require __DIR__ . '/../Views/competizioni/collegamenti/edit.php';
            return;
        }

        $this->collegamenti->update($idCollegamento, [
            'id_competizione_partenza' => $vecchiDati['id_competizione_partenza'],
            'id_competizione_arrivo' => $vecchiDati['id_competizione_arrivo'],
            'ordine' => $vecchiDati['ordine'],
            'dettagli' => $vecchiDati['dettagli'] !== '' ? $vecchiDati['dettagli'] : null,
        ]);

        header('Location: /universi/' . $idUniverso . '/competizioni');
        exit;
    }

    public function deleteByUniverso(Request $request, array $parametri = []): void
    {
        $idUniverso = (int) ($parametri['id'] ?? 0);
        $idCollegamento = (int) ($parametri['idCollegamento'] ?? 0);

        $collegamento = $this->collegamenti->findByUniverso($idUniverso, $idCollegamento);

        if ($collegamento === null) {
            http_response_code(404);
            echo 'Collegamento non trovato';
            return;
        }

        $this->collegamenti->delete($idCollegamento);

        header('Location: /universi/' . $idUniverso . '/competizioni');
        exit;
    }
}