<?php

declare(strict_types=1);

namespace App\Services\Edizioni;

use App\Models\EdizioneGiocatore;
use App\Models\EdizioneSquadra;

final class RoseUpdateService
{
    private EdizioneGiocatore $edizioneGiocatori;
    private EdizioneSquadra $edizioneSquadre;
    private RosaValidatorService $rosaValidatorService;

    public function __construct()
    {
        $this->edizioneGiocatori = new EdizioneGiocatore();
        $this->edizioneSquadre = new EdizioneSquadra();
        $this->rosaValidatorService = new RosaValidatorService();
    }

    public function aggiorna(int $idEdizione, int $idSquadra, array $idsGiocatori): array
    {
        $squadra = $this->edizioneSquadre->findEdizioneSquadra($idEdizione, $idSquadra);

        if ($squadra === null) {
            return [
                'ok' => false,
                'messaggio' => 'Squadra non trovata nell\'edizione',
                'codice' => 404,
            ];
        }

        $idsGiocatori = $this->normalizzaIds($idsGiocatori);

        $giocatoriDisponibili = $this->edizioneGiocatori->giocatoriDisponibiliPerSquadra($idEdizione, $idSquadra);
        $giocatoriAssegnatiCorrenti = $this->edizioneGiocatori->giocatoriAssegnatiASquadra($idEdizione, $idSquadra);

        $mappaConsentiti = [];

        foreach ($giocatoriDisponibili as $giocatore) {
            $mappaConsentiti[(int) ($giocatore['IDGiocatore'] ?? 0)] = true;
        }

        foreach ($giocatoriAssegnatiCorrenti as $giocatore) {
            $mappaConsentiti[(int) ($giocatore['IDGiocatore'] ?? 0)] = true;
        }

        foreach ($idsGiocatori as $idGiocatore) {
            if (!isset($mappaConsentiti[$idGiocatore])) {
                return [
                    'ok' => false,
                    'messaggio' => 'Uno o più giocatori selezionati non sono validi per questa squadra.',
                    'codice' => 422,
                    'squadra' => $squadra,
                    'giocatori_assegnati' => $this->edizioneGiocatori->giocatoriAssegnatiASquadra($idEdizione, $idSquadra),
                    'giocatori_disponibili' => $this->edizioneGiocatori->giocatoriDisponibiliPerSquadra($idEdizione, $idSquadra),
                    'verifica_rosa' => $this->rosaValidatorService->verificaRosaSquadra($idEdizione, $idSquadra),
                ];
            }
        }

        try {
            $this->edizioneGiocatori->salvaRosaSquadra($idEdizione, $idSquadra, $idsGiocatori);
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'messaggio' => 'Errore durante il salvataggio della rosa: ' . $e->getMessage(),
                'codice' => 500,
                'squadra' => $squadra,
                'giocatori_assegnati' => $this->edizioneGiocatori->giocatoriAssegnatiASquadra($idEdizione, $idSquadra),
                'giocatori_disponibili' => $this->edizioneGiocatori->giocatoriDisponibiliPerSquadra($idEdizione, $idSquadra),
                'verifica_rosa' => $this->rosaValidatorService->verificaRosaSquadra($idEdizione, $idSquadra),
            ];
        }

        return [
            'ok' => true,
            'codice' => 200,
            'squadra' => $squadra,
        ];
    }

    private function normalizzaIds(array $ids): array
    {
        return array_values(
            array_unique(
                array_filter(
                    array_map('intval', $ids),
                    fn (int $id): bool => $id > 0
                )
            )
        );
    }
}