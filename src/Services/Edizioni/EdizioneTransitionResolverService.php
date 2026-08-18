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
        if ($da <= 0 || $a <= 0 || $a < $da) {
            return [];
        }

        $rankingFinale = $this->estraiRankingFinale($competizioneCorrente);
        if ($rankingFinale === []) {
            return [];
        }

        usort(
            $rankingFinale,
            fn(array $x, array $y): int => ((int) ($x['posizione'] ?? 0) <=> (int) ($y['posizione'] ?? 0))
                ?: ((int) ($x['id_squadra'] ?? 0) <=> (int) ($y['id_squadra'] ?? 0))
        );

        $slice = array_slice($rankingFinale, $da - 1, $a - $da + 1);

        $ids = [];
        foreach ($slice as $voce) {
            $idSquadra = (int) ($voce['id_squadra'] ?? 0);
            if ($idSquadra > 0) {
                $ids[] = $idSquadra;
            }
        }

        return array_values(array_unique($ids));
    }

    private function miglioriN(array $competizioneCorrente, int $numero): array
    {
        return $this->squadrePerPosizione($competizioneCorrente, 1, $numero);
    }

    private function estraiRankingFinale(array $competizioneCorrente): array
    {
        $podioSalvato = $this->decodificaJson((string) ($competizioneCorrente['Podio'] ?? ''));

        if ($podioSalvato !== []) {
            $ranking = [];

            foreach ($podioSalvato as $voce) {
                if (!is_array($voce)) {
                    continue;
                }

                $posizione = (int) ($voce['posizione'] ?? 0);
                $idSquadra = (int) ($voce['id_squadra'] ?? 0);

                if ($posizione <= 0 || $idSquadra <= 0) {
                    continue;
                }

                $ranking[] = [
                    'posizione' => $posizione,
                    'id_squadra' => $idSquadra,
                    'tipo' => (string) ($voce['tipo'] ?? 'univoca'),
                ];
            }

            if ($ranking !== []) {
                usort(
                    $ranking,
                    fn(array $a, array $b): int => ((int) $a['posizione'] <=> (int) $b['posizione'])
                        ?: ((int) $a['id_squadra'] <=> (int) $b['id_squadra'])
                );

                return $ranking;
            }
        }

        return $this->fallbackRankingDaClassificaLive($competizioneCorrente);
    }

    private function fallbackRankingDaClassificaLive(array $competizioneCorrente): array
    {
        $idEdizione = (int) ($competizioneCorrente['IDEdizione'] ?? 0);
        $idEdizioneCompetizione = (int) ($competizioneCorrente['ID'] ?? 0);
        $idUniverso = (int) ($competizioneCorrente['IDUniverso'] ?? 0);

        if ($idUniverso <= 0 || $idEdizione <= 0 || $idEdizioneCompetizione <= 0) {
            return [];
        }

        $pagina = $this->classificaService->build(
            $idUniverso,
            $idEdizione,
            $idEdizioneCompetizione,
            []
        );

        if ($pagina === null) {
            return [];
        }

        $righe = $pagina['visteClassifica']['generale'] ?? [];
        if (!is_array($righe) || $righe === []) {
            return [];
        }

        $ranking = [];

        foreach ($righe as $riga) {
            $posizione = (int) ($riga['Posizione'] ?? 0);
            $idSquadra = (int) ($riga['IDSquadra'] ?? 0);

            if ($posizione <= 0 || $idSquadra <= 0) {
                continue;
            }

            $ranking[] = [
                'posizione' => $posizione,
                'id_squadra' => $idSquadra,
                'tipo' => 'univoca',
            ];
        }

        return $ranking;
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
