<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Models\Competizione;
use App\Models\CompetizioneCollegamento;
use App\Support\CompetitionTypes;

class CompetizioneController
{
    private Competizione $competizioni;

    public function __construct()
    {
        $this->competizioni = new Competizione();
    }

    public function indexByUniverso(Request $request, array $parametri = []): void
    {
        $idUniverso = (int) ($parametri['id'] ?? 0);
        $universoModel = new \App\Models\Universo();
        $universo = $universoModel->find($idUniverso);

        if ($universo === null) {
            http_response_code(404);
            echo 'Universo non trovato';
            return;
        }

        $filtri = [
            'q' => trim((string) ($request->query['q'] ?? '')),
            'tipo' => trim((string) ($request->query['tipo'] ?? '')),
            'sort' => (string) ($request->query['sort'] ?? 'ID'),
            'dir' => (string) ($request->query['dir'] ?? 'asc'),
        ];

        $competizioni = $this->competizioni->allByUniverso($idUniverso, $filtri);

        $collegamentiModel = new CompetizioneCollegamento();
        $collegamenti = $collegamentiModel->allByUniverso($idUniverso);

        require __DIR__ . '/../Views/competizioni/index.php';
    }

    public function createByUniverso(Request $request, array $parametri = []): void
    {
        $idUniverso = (int) ($parametri['id'] ?? 0);
        $universoModel = new \App\Models\Universo();
        $universo = $universoModel->find($idUniverso);

        if ($universo === null) {
            http_response_code(404);
            echo 'Universo non trovato';
            return;
        }

        $errori = [];
        $vecchiDati = [
            'nome_competizione' => '',
            'tipo' => 'lega',
            'numero_partecipanti' => 20,
            'giri' => 1,
            'inizialmente_vuota' => 0,
            'eredita_partecipanti' => 1,
            'struttura' => '',
        ];

        require __DIR__ . '/../Views/competizioni/create.php';
    }

    public function storeByUniverso(Request $request, array $parametri = []): void
    {
        $idUniverso = (int) ($parametri['id'] ?? 0);
        $universoModel = new \App\Models\Universo();
        $universo = $universoModel->find($idUniverso);

        if ($universo === null) {
            http_response_code(404);
            echo 'Universo non trovato';
            return;
        }
        $vecchiDati = [
            'nome_competizione' => trim((string) ($request->body['nome_competizione'] ?? '')),
            'tipo' => trim((string) ($request->body['tipo'] ?? '')),
            'numero_partecipanti' => (int) ($request->body['numero_partecipanti'] ?? 0),
            'giri' => (int) ($request->body['giri'] ?? 1),
            'inizialmente_vuota' => (int) ($request->body['inizialmente_vuota'] ?? 0),
            'eredita_partecipanti' => (int) ($request->body['eredita_partecipanti'] ?? 1),
            'struttura' => trim((string) ($request->body['struttura'] ?? '')),
        ];

        $errori = $this->validaByUniverso($vecchiDati);
        $this->estraiCampiDaStruttura($vecchiDati);

        if (!empty($errori)) {
            require __DIR__ . '/../Views/competizioni/create.php';
            return;
        }

        $idCompetizione = $this->competizioni->create([
            'id_universo' => $idUniverso,
            'nome_competizione' => $vecchiDati['nome_competizione'],
            'tipo' => $vecchiDati['tipo'],
            'numero_partecipanti' => $vecchiDati['numero_partecipanti'],
            'giri' => $vecchiDati['giri'],
            'inizialmente_vuota' => $vecchiDati['inizialmente_vuota'],
            'eredita_partecipanti' => $vecchiDati['eredita_partecipanti'],
            'struttura' => $vecchiDati['struttura'] !== '' ? $vecchiDati['struttura'] : null,
        ]);

        header('Location: /universi/' . $idUniverso . '/competizioni/' . $idCompetizione);
        exit;
    }

    public function editByUniverso(Request $request, array $parametri): void
    {
        $idUniverso = (int) ($parametri['id'] ?? 0);
        $idCompetizione = (int) ($parametri['idCompetizione'] ?? 0);

        $universoModel = new \App\Models\Universo();
        $universo = $universoModel->find($idUniverso);

        if ($universo === null) {
            http_response_code(404);
            echo 'Universo non trovato';
            return;
        }

        $competizione = $this->competizioni->findByUniverso($idUniverso, $idCompetizione);

        if ($competizione === null) {
            http_response_code(404);
            echo 'Competizione non trovata';
            return;
        }

        $errori = [];
        $vecchiDati = [
            'nome_competizione' => (string) ($competizione['NomeCompetizione'] ?? ''),
            'tipo' => (string) ($competizione['Tipo'] ?? 'lega'),
            'numero_partecipanti' => (int) ($competizione['NumeroPartecipanti'] ?? 0),
            'giri' => (int) ($competizione['Giri'] ?? 1),
            'inizialmente_vuota' => (int) ($competizione['InizialmenteVuota'] ?? 0),
            'eredita_partecipanti' => (int) ($competizione['EreditaPartecipanti'] ?? 1),
            'struttura' => $this->formattaJson((string) ($competizione['Struttura'] ?? '')),
        ];

        $this->estraiCampiDaStruttura($vecchiDati);

        require __DIR__ . '/../Views/competizioni/edit.php';
    }

    public function updateByUniverso(Request $request, array $parametri): void
    {
        $idUniverso = (int) ($parametri['id'] ?? 0);
        $idCompetizione = (int) ($parametri['idCompetizione'] ?? 0);

        $competizione = $this->competizioni->findByUniverso($idUniverso, $idCompetizione);

        if ($competizione === null) {
            http_response_code(404);
            echo 'Competizione non trovata';
            return;
        }

        $vecchiDati = [
            'nome_competizione' => trim((string) ($request->body['nome_competizione'] ?? '')),
            'tipo' => trim((string) ($request->body['tipo'] ?? '')),
            'numero_partecipanti' => (int) ($request->body['numero_partecipanti'] ?? 0),
            'giri' => (int) ($request->body['giri'] ?? 1),
            'inizialmente_vuota' => (int) ($request->body['inizialmente_vuota'] ?? 0),
            'eredita_partecipanti' => (int) ($request->body['eredita_partecipanti'] ?? 1),
            'struttura' => trim((string) ($request->body['struttura'] ?? '')),
        ];

        $errori = $this->validaByUniverso($vecchiDati);
        $this->estraiCampiDaStruttura($vecchiDati);

        if (!empty($errori)) {
            require __DIR__ . '/../Views/competizioni/edit.php';
            return;
        }

        $this->competizioni->update($idCompetizione, [
            'id_universo' => $idUniverso,
            'nome_competizione' => $vecchiDati['nome_competizione'],
            'tipo' => $vecchiDati['tipo'],
            'numero_partecipanti' => $vecchiDati['numero_partecipanti'],
            'giri' => $vecchiDati['giri'],
            'inizialmente_vuota' => $vecchiDati['inizialmente_vuota'],
            'eredita_partecipanti' => $vecchiDati['eredita_partecipanti'],
            'struttura' => $vecchiDati['struttura'] !== '' ? $vecchiDati['struttura'] : null,
        ]);

        header('Location: /universi/' . $idUniverso . '/competizioni/' . $idCompetizione);
        exit;
    }

    public function deleteByUniverso(Request $request, array $parametri): void
    {
        $idUniverso = (int) ($parametri['id'] ?? 0);
        $idCompetizione = (int) ($parametri['idCompetizione'] ?? 0);

        $competizione = $this->competizioni->findByUniverso($idUniverso, $idCompetizione);

        if ($competizione === null) {
            http_response_code(404);
            echo 'Competizione non trovata';
            return;
        }

        $this->competizioni->delete($idCompetizione);

        header('Location: /universi/' . $idUniverso . '/competizioni');
        exit;
    }

    private function validaByUniverso(array $dati): array
    {
        $errori = [];

        $nomeCompetizione = trim((string) ($dati['nome_competizione'] ?? ''));
        $tipo = trim((string) ($dati['tipo'] ?? ''));
        $numeroPartecipanti = (int) ($dati['numero_partecipanti'] ?? 0);
        $giri = (int) ($dati['giri'] ?? 1);
        $struttura = trim((string) ($dati['struttura'] ?? ''));

        if ($nomeCompetizione === '') {
            $errori[] = 'Il nome competizione è obbligatorio.';
        } elseif (mb_strlen($nomeCompetizione) > 150) {
            $errori[] = 'Il nome competizione non può superare 150 caratteri.';
        }

        if ($tipo === '') {
            $errori[] = 'Il tipo competizione è obbligatorio.';
        } elseif (!in_array($tipo, ['lega', 'eliminazione_diretta'], true)) {
            $errori[] = 'Il tipo competizione non è valido.';
        }

        if ($numeroPartecipanti < 2) {
            $errori[] = 'Il numero partecipanti deve essere almeno 2.';
        }

        if ($giri < 1) {
            $errori[] = 'I giri devono essere almeno 1.';
        }

        if ($struttura !== '') {
            $decoded = json_decode($struttura, true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
                $errori[] = 'La struttura deve essere un JSON valido oppure vuota.';
            } else {
                if ($tipo === 'lega') {
                    $ordinamento = $decoded['classifica']['ordinamento'] ?? [];

                    if (!is_array($ordinamento) || $ordinamento === []) {
                        $errori[] = 'L’ordinamento classifica è obbligatorio.';
                    }

                    $ammessi = ['punti', 'differenza_reti', 'gol_fatti', 'gol_subiti', 'scontri_diretti', 'nome'];

                    foreach ($ordinamento as $criterio) {
                        if (!in_array($criterio, $ammessi, true)) {
                            $errori[] = 'L’ordinamento classifica contiene criteri non validi.';
                            break;
                        }
                    }

                    if (count($ordinamento) !== count(array_unique($ordinamento))) {
                        $errori[] = 'L’ordinamento classifica non può contenere duplicati.';
                    }
                }
            }
        }

        return $errori;
    }

    public function showByUniverso(Request $request, array $parametri): void
    {
        $idUniverso = (int) ($parametri['id'] ?? 0);
        $idCompetizione = (int) ($parametri['idCompetizione'] ?? 0);

        $universoModel = new \App\Models\Universo();
        $universo = $universoModel->find($idUniverso);

        if ($universo === null) {
            http_response_code(404);
            echo 'Universo non trovato';
            return;
        }

        $competizione = $this->competizioni->findByUniverso($idUniverso, $idCompetizione);

        if ($competizione === null) {
            http_response_code(404);
            echo 'Competizione non trovata';
            return;
        }

        $strutturaFormattata = $this->formattaJson((string) ($competizione['Struttura'] ?? ''));

        require __DIR__ . '/../Views/competizioni/show.php';
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

    private function estraiCampiDaStruttura(array &$vecchiDati): void
    {
        $struttura = json_decode((string) ($vecchiDati['struttura'] ?? ''), true);

        if (!is_array($struttura)) {
            return;
        }

        $vecchiDati['lega_livello'] = $struttura['livello'] ?? '';
        $vecchiDati['lega_girone'] = $struttura['girone'] ?? '';
        $vecchiDati['punti_vittoria'] = $struttura['punti']['vittoria'] ?? 3;
        $vecchiDati['punti_pareggio'] = $struttura['punti']['pareggio'] ?? 1;
        $vecchiDati['punti_sconfitta'] = $struttura['punti']['sconfitta'] ?? 0;
        $vecchiDati['ordinamento_classifica'] = $struttura['classifica']['ordinamento'] ?? ['punti', 'differenza_reti', 'gol_fatti'];

        $vecchiDati['elim_finale_secca'] = $struttura['finale_secca'] ?? 1;
        $vecchiDati['elim_finale_terzo_posto'] = $struttura['finale_terzo_posto'] ?? 0;
    }
}
