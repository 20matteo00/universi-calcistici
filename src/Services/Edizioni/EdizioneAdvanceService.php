<?php

declare(strict_types=1);

namespace App\Services\Edizioni;

use App\Config\Database;
use App\Models\CompetizioneCollegamento;
use App\Models\Edizione;
use App\Models\EdizioneCompetizione;
use App\Models\EdizioneGiocatore;
use App\Models\EdizioneSquadra;
use App\Models\Universo;
use Throwable;

final class EdizioneAdvanceService
{
    private Universo $universi;
    private Edizione $edizioni;
    private EdizioneSquadra $edizioneSquadre;
    private EdizioneGiocatore $edizioneGiocatori;
    private EdizioneCompetizione $edizioneCompetizioni;
    private CompetizioneCollegamento $competizioneCollegamenti;
    private EdizioneCreateService $edizioneCreateService;
    private EdizioneTransitionResolverService $transitionResolver;

    public function __construct()
    {
        $this->universi = new Universo();
        $this->edizioni = new Edizione();
        $this->edizioneSquadre = new EdizioneSquadra();
        $this->edizioneGiocatori = new EdizioneGiocatore();
        $this->edizioneCompetizioni = new EdizioneCompetizione();
        $this->competizioneCollegamenti = new CompetizioneCollegamento();
        $this->edizioneCreateService = new EdizioneCreateService();
        $this->transitionResolver = new EdizioneTransitionResolverService();
    }

    public function puoAvanzare(int $idUniverso, int $idEdizione): array
    {
        $universo = $this->universi->find($idUniverso);
        $edizione = $this->edizioni->find($idEdizione);

        if (!$this->edizioni->isUltimaEdizione($idUniverso, $idEdizione)) {
            return [
                'ok' => false,
                'messaggio' => 'Puoi avanzare solo dall’ultima edizione disponibile.',
            ];
        }

        if ($universo === null || $edizione === null) {
            return [
                'ok' => false,
                'messaggio' => 'Edizione o universo non trovato.',
            ];
        }

        if ((int) ($edizione['IDUniverso'] ?? 0) !== $idUniverso) {
            return [
                'ok' => false,
                'messaggio' => 'L’edizione non appartiene all’universo indicato.',
            ];
        }

        $competizioni = $this->edizioneCompetizioni->competizioniEdizione($idEdizione);
        if ($competizioni === []) {
            return [
                'ok' => false,
                'messaggio' => 'Nessuna competizione presente nell’edizione.',
            ];
        }

        foreach ($competizioni as $competizione) {
            $stato = mb_strtolower(trim((string) ($competizione['Stato'] ?? 'in_corso')));

            if ($stato !== 'conclusa') {
                return [
                    'ok' => false,
                    'messaggio' => 'Non tutte le competizioni dell’edizione sono concluse.',
                ];
            }
        }

        return [
            'ok' => true,
            'messaggio' => null,
        ];
    }

    public function avanza(int $idUniverso, int $idEdizione): array
    {
        $check = $this->puoAvanzare($idUniverso, $idEdizione);
        if (!(bool) ($check['ok'] ?? false)) {
            return $check;
        }

        $pdo = Database::getConnessione();
        $pdo->beginTransaction();

        try {
            $edizioneCorrente = $this->edizioni->find($idEdizione);
            if ($edizioneCorrente === null) {
                throw new \RuntimeException('Edizione corrente non trovata.');
            }

            $numeroNuovaEdizione = ((int) ($edizioneCorrente['Numero'] ?? 1)) + 1;
            $nomeNuovaEdizione = 'Stagione ' . $numeroNuovaEdizione;

            $verificaRose = $this->universi->verificaRoseMinime($idUniverso);
            $roseMinimeOk = (bool) ($verificaRose['ok'] ?? false);

            $idNuovaEdizione = $this->edizioneCreateService->creaPrimaEdizione(
                $idUniverso,
                $numeroNuovaEdizione,
                $nomeNuovaEdizione,
                'bozza',
                true
            );

            $competizioniCorrenti = $this->edizioneCompetizioni->competizioniEdizione($idEdizione);
            $competizioniNuove = $this->edizioneCompetizioni->competizioniEdizione($idNuovaEdizione);
            $collegamenti = $this->competizioneCollegamenti->allByUniverso($idUniverso);

            $movimenti = $this->transitionResolver->risolviMovimenti(
                $competizioniCorrenti,
                $competizioniNuove,
                $collegamenti
            );

            $this->applicaMovimenti($idNuovaEdizione, $movimenti);
            $this->edizioni->aggiornaStato($idEdizione, 'conclusa');

            $pdo->commit();

            return [
                'ok' => true,
                'id_nuova_edizione' => $idNuovaEdizione,
                'movimenti' => $movimenti,
            ];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            return [
                'ok' => false,
                'messaggio' => $e->getMessage(),
            ];
        }
    }

    private function applicaMovimenti(int $idNuovaEdizione, array $movimenti): void
    {
        foreach ($movimenti as $movimento) {
            $idEdizioneCompetizioneDestinazione = (int) ($movimento['id_edizione_competizione_destinazione'] ?? 0);
            $idSquadra = (int) ($movimento['id_squadra'] ?? 0);

            if ($idEdizioneCompetizioneDestinazione <= 0 || $idSquadra <= 0) {
                continue;
            }

            $giaIscritta = false;
            $iscritte = $this->edizioneCompetizioni->squadreIscritteACompetizione($idEdizioneCompetizioneDestinazione);

            foreach ($iscritte as $squadra) {
                if ((int) ($squadra['IDSquadra'] ?? 0) === $idSquadra) {
                    $giaIscritta = true;
                    break;
                }
            }

            if ($giaIscritta) {
                continue;
            }

            $this->edizioneCompetizioni->iscriviSquadraACompetizione(
                $idEdizioneCompetizioneDestinazione,
                $idSquadra,
                'Iscritta',
                'Avanzamento stagione'
            );
        }
    }
}
