<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Models\Competizione;
use App\Models\CompetizioneAvanzamento;
use App\Models\Universo;

class CompetizioneAvanzamentoController
{
    private CompetizioneAvanzamento $collegamenti;
    private Competizione $competizioni;

    public function __construct()
    {
        $this->collegamenti = new CompetizioneAvanzamento();
        $this->competizioni = new Competizione();
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
        $vecchiDati = [
            'id_competizione_partenza' => '',
            'id_competizione_arrivo' => '',
            'ordine' => 1,
            'criterio' => 'posizione',
            'posizione_tipo' => 'promozione',
            'posizione_da' => 1,
            'posizione_a' => 1,
            'numero' => 1,
            'dettagli' => '',
        ];

        require __DIR__ . '/../Views/competizioni_avanzamento/create.php';
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

        $vecchiDati = [
            'id_competizione_partenza' => (int) ($request->body['id_competizione_partenza'] ?? 0),
            'id_competizione_arrivo' => (int) ($request->body['id_competizione_arrivo'] ?? 0),
            'ordine' => (int) ($request->body['ordine'] ?? 1),
            'dettagli' => trim((string) ($request->body['dettagli'] ?? '')),
            'criterio' => 'posizione',
            'posizione_tipo' => 'promozione',
            'posizione_da' => 1,
            'posizione_a' => 1,
            'numero' => 1,
        ];

        $this->estraiCampiDaDettagli($vecchiDati);
        $errori = $this->validaByUniverso($idUniverso, $vecchiDati);

        if (!empty($errori)) {
            require __DIR__ . '/../Views/competizioni_avanzamento/create.php';
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
        $vecchiDati = [
            'id_competizione_partenza' => (int) ($collegamento['IDCompetizionePartenza'] ?? 0),
            'id_competizione_arrivo' => (int) ($collegamento['IDCompetizioneArrivo'] ?? 0),
            'ordine' => (int) ($collegamento['Ordine'] ?? 1),
            'dettagli' => $this->formattaJson((string) ($collegamento['Dettagli'] ?? '')),
        ];

        $vecchiDati['criterio'] = 'posizione';
        $vecchiDati['posizione_tipo'] = 'promozione';
        $vecchiDati['posizione_da'] = 1;
        $vecchiDati['posizione_a'] = 1;
        $vecchiDati['numero'] = 1;

        $this->estraiCampiDaDettagli($vecchiDati);

        require __DIR__ . '/../Views/competizioni_avanzamento/edit.php';
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

        $vecchiDati = [
            'id_competizione_partenza' => (int) ($request->body['id_competizione_partenza'] ?? 0),
            'id_competizione_arrivo' => (int) ($request->body['id_competizione_arrivo'] ?? 0),
            'ordine' => (int) ($request->body['ordine'] ?? 1),
            'dettagli' => trim((string) ($request->body['dettagli'] ?? '')),
            'criterio' => 'posizione',
            'posizione_tipo' => 'promozione',
            'posizione_da' => 1,
            'posizione_a' => 1,
            'numero' => 1,
        ];

        $this->estraiCampiDaDettagli($vecchiDati);
        $errori = $this->validaByUniverso($idUniverso, $vecchiDati);

        if (!empty($errori)) {
            require __DIR__ . '/../Views/competizioni_avanzamento/edit.php';
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

    private function validaByUniverso(int $idUniverso, array $dati): array
    {
        $errori = [];

        $idPartenza = (int) ($dati['id_competizione_partenza'] ?? 0);
        $idArrivo = (int) ($dati['id_competizione_arrivo'] ?? 0);
        $ordine = (int) ($dati['ordine'] ?? 0);
        $dettagli = trim((string) ($dati['dettagli'] ?? ''));

        if ($idPartenza <= 0) {
            $errori[] = 'La competizione di partenza è obbligatoria.';
        }

        if ($idArrivo <= 0) {
            $errori[] = 'La competizione di arrivo è obbligatoria.';
        }

        if ($idPartenza > 0 && $idArrivo > 0 && $idPartenza === $idArrivo) {
            $errori[] = 'La competizione di partenza e quella di arrivo devono essere diverse.';
        }

        if ($ordine < 1) {
            $errori[] = 'L’ordine deve essere almeno 1.';
        }

        if ($dettagli !== '') {
            $decoded = json_decode($dettagli, true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
                $errori[] = 'I dettagli devono essere un JSON valido oppure vuoti.';
            } else {
                $criterio = (string) ($decoded['criterio'] ?? '');

                if (!in_array($criterio, ['posizione', 'migliori_n'], true)) {
                    $errori[] = 'Il criterio di avanzamento non è valido.';
                }

                if ($criterio === 'posizione') {
                    $tipoPosizione = (string) ($decoded['tipo'] ?? '');

                    if (!in_array($tipoPosizione, ['promozione', 'retrocessione', 'qualificazione', 'playoff', 'playout'], true)) {
                        $errori[] = 'Il tipo della regola posizione non è valido.';
                    }

                    $da = (int) ($decoded['da'] ?? 0);
                    $a = (int) ($decoded['a'] ?? 0);

                    if ($da < 1 || $a < 1) {
                        $errori[] = 'Le posizioni devono essere almeno 1.';
                    }

                    if ($da > $a) {
                        $errori[] = 'La posizione iniziale non può essere maggiore di quella finale.';
                    }
                }

                if ($criterio === 'migliori_n') {
                    $numero = (int) ($decoded['numero'] ?? 0);

                    if ($numero < 1) {
                        $errori[] = 'Il numero dei migliori qualificati deve essere almeno 1.';
                    }
                }
            }
        }

        if ($idPartenza > 0 && $this->competizioni->findByUniverso($idUniverso, $idPartenza) === null) {
            $errori[] = 'La competizione di partenza non appartiene a questo universo.';
        }

        if ($idArrivo > 0 && $this->competizioni->findByUniverso($idUniverso, $idArrivo) === null) {
            $errori[] = 'La competizione di arrivo non appartiene a questo universo.';
        }

        return $errori;
    }

    private function formattaJson(?string $json): string
    {
        $json = trim((string) $json);

        if ($json === '') {
            return '';
        }

        $decoded = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $json;
        }

        return (string) json_encode(
            $decoded,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }

    private function estraiCampiDaDettagli(array &$vecchiDati): void
    {
        $dettagli = json_decode((string) ($vecchiDati['dettagli'] ?? ''), true);
        if (!is_array($dettagli)) {
            return;
        }

        $vecchiDati['criterio'] = $dettagli['criterio'] ?? ($vecchiDati['criterio'] ?? 'posizione');
        $vecchiDati['posizione_tipo'] = $dettagli['tipo'] ?? ($vecchiDati['posizione_tipo'] ?? 'promozione');
        $vecchiDati['posizione_da'] = $dettagli['da'] ?? ($vecchiDati['posizione_da'] ?? 1);
        $vecchiDati['posizione_a'] = $dettagli['a'] ?? ($vecchiDati['posizione_a'] ?? 1);
        $vecchiDati['numero'] = $dettagli['numero'] ?? ($vecchiDati['numero'] ?? 1);
    }
}
