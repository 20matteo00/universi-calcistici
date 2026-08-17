<?php

declare(strict_types=1);

namespace App\Services\Edizioni;

use App\Models\EdizioneCompetizione;
use App\Models\EdizioneSquadra;

final class CompetizioneUpdateService
{
    public function __construct(
        private readonly EdizioneCompetizione $edizioneCompetizioni = new EdizioneCompetizione(),
        private readonly EdizioneSquadra $edizioneSquadre = new EdizioneSquadra(),
        private readonly CompetizioneIscrizioneService $competizioneIscrizioneService = new CompetizioneIscrizioneService(),
    ) {
    }

    public function aggiorna(
        int $idEdizione,
        int $idEdizioneCompetizione,
        array $idsSquadre,
        string $stato,
        string $motivo
    ): array {
        $edizioneCompetizione = $this->edizioneCompetizioni->findEdizioneCompetizione($idEdizioneCompetizione);

        if ($edizioneCompetizione === null || (int) ($edizioneCompetizione['IDEdizione'] ?? 0) !== $idEdizione) {
            return [
                'ok' => false,
                'messaggio' => 'Competizione stagionale non trovata',
            ];
        }

        $idsSquadre = array_values(array_unique(array_filter(array_map('intval', $idsSquadre), fn(int $id): bool => $id > 0)));

        if (!in_array($stato, ['Iscritta', 'Qualificata', 'Candidata', 'Eliminata', 'Promossa', 'Retrocessa'], true)) {
            return ['ok' => false, 'messaggio' => 'Lo stato selezionato non è valido.'];
        }

        $squadreEdizione = $this->edizioneSquadre->squadreEdizione($idEdizione);
        $mappaSquadre = [];
        foreach ($squadreEdizione as $squadra) {
            $mappaSquadre[(int) $squadra['IDSquadra']] = true;
        }

        foreach ($idsSquadre as $idSquadra) {
            if (!isset($mappaSquadre[$idSquadra])) {
                return ['ok' => false, 'messaggio' => 'Una o più squadre selezionate non appartengono all\'edizione.'];
            }
        }

        $numeroPartecipanti = (int) ($edizioneCompetizione['NumeroPartecipanti'] ?? 0);
        if ($numeroPartecipanti > 0 && count($idsSquadre) > $numeroPartecipanti) {
            return ['ok' => false, 'messaggio' => 'Hai selezionato più squadre del numero partecipanti previsto.'];
        }

        $warningDuplicati = $this->competizioneIscrizioneService->riepilogoDuplicatiCompetizione(
            $idEdizione,
            $idEdizioneCompetizione,
            $idsSquadre
        );

        $this->edizioneCompetizioni->salvaSquadreCompetizione(
            $idEdizioneCompetizione,
            $idsSquadre,
            $stato,
            $motivo
        );

        return [
            'ok' => true,
            'warning_duplicati' => $warningDuplicati,
            'squadre_iscritte' => $this->edizioneCompetizioni->squadreIscritteACompetizione($idEdizioneCompetizione),
            'altre_competizioni_per_squadra' => $this->edizioneCompetizioni->squadreConAltreCompetizioni($idEdizione, $idEdizioneCompetizione),
        ];
    }
}