<?php

declare(strict_types=1);

namespace App\Services\Edizioni;

use App\Models\PartitaQuery;
use App\Services\Competizioni\CompetizioneClassificaService;
use App\Services\Competizioni\CompetizioneEliminazioneDirettaService;

final class EdizioneTransitionResolverService
{
    private CompetizioneClassificaService $classificaService;
    private CompetizioneEliminazioneDirettaService $eliminazioneService;
    private PartitaQuery $partiteQuery;

    public function __construct()
    {
        $this->classificaService = new CompetizioneClassificaService();
        $this->eliminazioneService = new CompetizioneEliminazioneDirettaService();
        $this->partiteQuery = new PartitaQuery();
    }

    public function risolviMovimenti(
        array $competizioniCorrenti,
        array $competizioniNuove,
        array $collegamenti
    ): array {
        $mappaCorrenti = $this->mappaCompetizioniPerCompetizioneBase($competizioniCorrenti);
        $mappaNuove = $this->mappaCompetizioniPerCompetizioneBase($competizioniNuove);

        $movimenti = [];

        foreach ($collegamenti as $collegamento) {
            $idCompetizionePartenza = (int) ($collegamento['IDCompetizionePartenza'] ?? 0);
            $idCompetizioneArrivo = (int) ($collegamento['IDCompetizioneArrivo'] ?? 0);

            if (!isset($mappaCorrenti[$idCompetizionePartenza], $mappaNuove[$idCompetizioneArrivo])) {
                continue;
            }

            $competizioneCorrente = $mappaCorrenti[$idCompetizionePartenza];
            $competizioneDestinazioneNuova = $mappaNuove[$idCompetizioneArrivo];
            $dettagli = $this->decodificaJson((string) ($collegamento['Dettagli'] ?? ''));

            $criterio = (string) ($dettagli['criterio'] ?? '');
            $tipo = (string) ($dettagli['tipo'] ?? '');

            if ($criterio === 'posizione') {
                $da = (int) ($dettagli['da'] ?? 0);
                $a = (int) ($dettagli['a'] ?? 0);

                foreach ($this->squadrePerPosizione($competizioneCorrente, $da, $a) as $idSquadra) {
                    $movimenti[] = [
                        'id_squadra' => $idSquadra,
                        'azione' => $this->azioneDaTipo($tipo),
                        'motivo' => $tipo,
                        'id_edizione_competizione_destinazione' => (int) ($competizioneDestinazioneNuova['ID'] ?? 0),
                    ];
                }
            }

            if ($criterio === 'migliori_n') {
                $numero = (int) ($dettagli['numero'] ?? 0);

                foreach ($this->miglioriN($competizioneCorrente, $numero) as $idSquadra) {
                    $movimenti[] = [
                        'id_squadra' => $idSquadra,
                        'azione' => $this->azioneDaTipo($tipo),
                        'motivo' => $tipo,
                        'id_edizione_competizione_destinazione' => (int) ($competizioneDestinazioneNuova['ID'] ?? 0),
                    ];
                }
            }
        }

        return $this->deduplicaMovimenti($movimenti);
    }

    private function squadrePerPosizione(array $competizioneCorrente, int $da, int $a): array
    {
        $idEdizione = (int) ($competizioneCorrente['IDEdizione'] ?? 0);
        $idEdizioneCompetizione = (int) ($competizioneCorrente['ID'] ?? 0);

        if ($idEdizione <= 0 || $idEdizioneCompetizione <= 0 || $da <= 0 || $a <= 0) {
            return [];
        }

        $pagina = $this->classificaService->build(
            (int) ($competizioneCorrente['IDUniverso'] ?? 0),
            $idEdizione,
            $idEdizioneCompetizione,
            []
        );

        if ($pagina === null) {
            return [];
        }

        $righe = $pagina['visteClassifica']['generale'] ?? [];
        $ids = [];

        foreach ($righe as $riga) {
            $posizione = (int) ($riga['Posizione'] ?? 0);
            if ($posizione >= $da && $posizione <= $a) {
                $ids[] = (int) ($riga['IDSquadra'] ?? 0);
            }
        }

        return array_values(array_filter($ids));
    }

    private function miglioriN(array $competizioneCorrente, int $numero): array
    {
        return $this->squadrePerPosizione($competizioneCorrente, 1, $numero);
    }

    private function azioneDaTipo(string $tipo): string
    {
        return match ($tipo) {
            'promozione', 'retrocessione' => 'spostamento',
            'qualificazione' => 'copia',
            'playoff', 'playout' => 'copia_temporanea',
            default => 'copia',
        };
    }

    private function mappaCompetizioniPerCompetizioneBase(array $competizioni): array
    {
        $mappa = [];

        foreach ($competizioni as $competizione) {
            $idCompetizioneBase = (int) ($competizione['IDCompetizione'] ?? 0);
            if ($idCompetizioneBase > 0) {
                $mappa[$idCompetizioneBase] = $competizione;
            }
        }

        return $mappa;
    }

    private function deduplicaMovimenti(array $movimenti): array
    {
        $visti = [];
        $output = [];

        foreach ($movimenti as $movimento) {
            $chiave = implode(':', [
                (int) ($movimento['id_squadra'] ?? 0),
                (string) ($movimento['azione'] ?? ''),
                (int) ($movimento['id_edizione_competizione_destinazione'] ?? 0),
            ]);

            if (isset($visti[$chiave])) {
                continue;
            }

            $visti[$chiave] = true;
            $output[] = $movimento;
        }

        return $output;
    }

    private function decodificaJson(string $json): array
    {
        $json = trim($json);
        if ($json === '') {
            return [];
        }

        $data = json_decode($json, true);
        return is_array($data) ? $data : [];
    }
}