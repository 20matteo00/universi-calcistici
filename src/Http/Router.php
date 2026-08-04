<?php

namespace App\Http;

/**
 * Router molto semplice: associa "METODO + path" a una funzione (Controller).
 * Niente magie: una riga per ogni endpoint, cosi' resta facile da leggere.
 *
 * Esempio d'uso in public/index.php:
 *
 *   $router->get('/api/universi', [UniversoController::class, 'lista']);
 *   $router->get('/api/universi/{id}', [UniversoController::class, 'dettaglio']);
 *   $router->post('/api/universi', [UniversoController::class, 'crea']);
 */
class Router
{
    private array $rotte = [];

    public function get(string $path, callable|array $azione): void
    {
        $this->aggiungi('GET', $path, $azione);
    }

    public function post(string $path, callable|array $azione): void
    {
        $this->aggiungi('POST', $path, $azione);
    }

    public function put(string $path, callable|array $azione): void
    {
        $this->aggiungi('PUT', $path, $azione);
    }

    public function delete(string $path, callable|array $azione): void
    {
        $this->aggiungi('DELETE', $path, $azione);
    }

    private function aggiungi(string $metodo, string $path, callable|array $azione): void
    {
        // Trasforma {id} in una regex nominata, cosi' si possono avere parametri nel path.
        $pattern = preg_replace('#\{([a-zA-Z_]+)\}#', '(?P<$1>[^/]+)', $path);
        $pattern = '#^' . $pattern . '$#';

        $this->rotte[] = [
            'metodo' => $metodo,
            'pattern' => $pattern,
            'azione' => $azione,
        ];
    }

    public function gestisci(Request $request): void
    {
        foreach ($this->rotte as $rotta) {
            if ($rotta['metodo'] !== $request->metodo) {
                continue;
            }

            if (preg_match($rotta['pattern'], $request->path, $match)) {
                $parametri = array_filter(
                    $match,
                    fn($chiave) => is_string($chiave),
                    ARRAY_FILTER_USE_KEY
                );

                $azione = $rotta['azione'];

                if (is_array($azione)) {
                    [$classe, $metodo] = $azione;
                    $controller = new $classe();
                    $controller->$metodo($request, $parametri);
                } else {
                    $azione($request, $parametri);
                }
                return;
            }
        }

        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['errore' => 'Endpoint non trovato']);
    }
}
