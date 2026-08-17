<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Controllers\CompetizioneCollegamentoController;
use App\Controllers\CompetizioneController;
use App\Controllers\DevController;
use App\Controllers\EdizioneController;
use App\Controllers\GiocatoreController;
use App\Controllers\PartitaController;
use App\Controllers\SquadraController;
use App\Controllers\UniversoController;
use App\Http\Request;
use App\Http\Router;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$router = new Router();

$router->get('/', function () {
    require __DIR__ . '/../src/Views/home.php';
});

/*
|--------------------------------------------------------------------------
| DEV
|--------------------------------------------------------------------------
*/
$router->post('/dev/reset-database', [DevController::class, 'resetDatabase']);

/*
|--------------------------------------------------------------------------
| SQUADRE
|--------------------------------------------------------------------------
*/
$router->get('/squadre', [SquadraController::class, 'lista']);
$router->get('/squadre/crea', [SquadraController::class, 'crea']);
$router->post('/squadre/crea', [SquadraController::class, 'salva']);
$router->get('/squadre/modifica/{id}', [SquadraController::class, 'modifica']);
$router->post('/squadre/modifica/{id}', [SquadraController::class, 'aggiorna']);
$router->post('/squadre/elimina/{id}', [SquadraController::class, 'elimina']);
$router->post('/squadre/duplica/{id}', [SquadraController::class, 'duplica']);
$router->post('/squadre/genera', [SquadraController::class, 'genera']);
$router->post('/squadre/elimina-selezionate', [SquadraController::class, 'eliminaSelezionate']);
$router->post('/squadre/genera-multiple', [SquadraController::class, 'generaMultiplo']);

/*
|--------------------------------------------------------------------------
| GIOCATORI
|--------------------------------------------------------------------------
*/
$router->get('/giocatori', [GiocatoreController::class, 'lista']);
$router->get('/giocatori/crea', [GiocatoreController::class, 'crea']);
$router->post('/giocatori/crea', [GiocatoreController::class, 'salva']);
$router->get('/giocatori/modifica/{id}', [GiocatoreController::class, 'modifica']);
$router->post('/giocatori/modifica/{id}', [GiocatoreController::class, 'aggiorna']);
$router->post('/giocatori/elimina/{id}', [GiocatoreController::class, 'elimina']);
$router->post('/giocatori/duplica/{id}', [GiocatoreController::class, 'duplica']);
$router->post('/giocatori/genera', [GiocatoreController::class, 'genera']);
$router->post('/giocatori/elimina-selezionate', [GiocatoreController::class, 'eliminaSelezionate']);
$router->post('/giocatori/genera-multiple', [GiocatoreController::class, 'generaMultiplo']);

/*
|--------------------------------------------------------------------------
| UNIVERSI
|--------------------------------------------------------------------------
*/
$router->get('/universi', [UniversoController::class, 'index']);
$router->get('/universi/crea', [UniversoController::class, 'create']);
$router->post('/universi/crea', [UniversoController::class, 'store']);
$router->get('/universi/modifica/{id}', [UniversoController::class, 'edit']);
$router->post('/universi/modifica/{id}', [UniversoController::class, 'update']);
$router->post('/universi/elimina/{id}', [UniversoController::class, 'delete']);

$router->get('/universi/{id}', [UniversoController::class, 'show']);

$router->get('/universi/{id}/squadre', [UniversoController::class, 'gestisciSquadre']);
$router->post('/universi/{id}/squadre', [UniversoController::class, 'aggiungiSquadra']);
$router->post('/universi/{id}/squadre/aggiungi-selezionate', [UniversoController::class, 'aggiungiSquadreSelezionate']);
$router->post('/universi/{id}/squadre/rimuovi-selezionate', [UniversoController::class, 'rimuoviSquadreSelezionate']);
$router->post('/universi/{id}/squadre/{idSquadra}/rimuovi', [UniversoController::class, 'rimuoviSquadra']);

$router->get('/universi/{id}/giocatori', [UniversoController::class, 'gestisciGiocatori']);
$router->post('/universi/{id}/giocatori', [UniversoController::class, 'aggiungiGiocatore']);
$router->post('/universi/{id}/giocatori/aggiungi-selezionati', [UniversoController::class, 'aggiungiGiocatoriSelezionati']);
$router->post('/universi/{id}/giocatori/rimuovi-selezionati', [UniversoController::class, 'rimuoviGiocatoriSelezionati']);
$router->post('/universi/{id}/giocatori/{idGiocatore}/rimuovi', [UniversoController::class, 'rimuoviGiocatore']);

/*
|--------------------------------------------------------------------------
| COMPETIZIONI BASE
|--------------------------------------------------------------------------
*/
$router->get('/universi/{id}/competizioni', [CompetizioneController::class, 'indexByUniverso']);
$router->get('/universi/{id}/competizioni/create', [CompetizioneController::class, 'createByUniverso']);
$router->post('/universi/{id}/competizioni', [CompetizioneController::class, 'storeByUniverso']);

/*
|--------------------------------------------------------------------------
| COLLEGAMENTI COMPETIZIONI
|--------------------------------------------------------------------------
*/
$router->get('/universi/{id}/competizioni/collegamenti/create', [CompetizioneCollegamentoController::class, 'createByUniverso']);
$router->post('/universi/{id}/competizioni/collegamenti', [CompetizioneCollegamentoController::class, 'storeByUniverso']);
$router->get('/universi/{id}/competizioni/collegamenti/{idCollegamento}/edit', [CompetizioneCollegamentoController::class, 'editByUniverso']);
$router->post('/universi/{id}/competizioni/collegamenti/{idCollegamento}/update', [CompetizioneCollegamentoController::class, 'updateByUniverso']);
$router->post('/universi/{id}/competizioni/collegamenti/{idCollegamento}/delete', [CompetizioneCollegamentoController::class, 'deleteByUniverso']);

/*
|--------------------------------------------------------------------------
| COMPETIZIONE SINGOLA
|--------------------------------------------------------------------------
*/
$router->get('/universi/{id}/competizioni/{idCompetizione}/edit', [CompetizioneController::class, 'editByUniverso']);
$router->post('/universi/{id}/competizioni/{idCompetizione}/update', [CompetizioneController::class, 'updateByUniverso']);
$router->post('/universi/{id}/competizioni/{idCompetizione}/delete', [CompetizioneController::class, 'deleteByUniverso']);
$router->get('/universi/{id}/competizioni/{idCompetizione}', [CompetizioneController::class, 'showByUniverso']);

/*
|--------------------------------------------------------------------------
| EDIZIONI
|--------------------------------------------------------------------------
*/
$router->get('/universi/{id}/edizioni', [EdizioneController::class, 'indexByUniverso']);
$router->get('/universi/{id}/edizioni/crea', [EdizioneController::class, 'crea']);
$router->post('/universi/{id}/edizioni/salva', [EdizioneController::class, 'salva']);
$router->get('/universi/{id}/edizioni/{idEdizione}', [EdizioneController::class, 'showByUniverso']);
$router->post('/universi/{id}/edizioni/{idEdizione}/finalizza', [EdizioneController::class, 'finalizzaEdizione']);

/*
|--------------------------------------------------------------------------
| ROSE EDIZIONE
|--------------------------------------------------------------------------
*/
$router->get('/universi/{id}/edizioni/{idEdizione}/rose', [EdizioneController::class, 'roseIndex']);
$router->post('/universi/{id}/edizioni/{idEdizione}/rose/auto', [EdizioneController::class, 'roseAutoTutte']);
$router->get('/universi/{id}/edizioni/{idEdizione}/rose/{idSquadra}/show', [EdizioneController::class, 'roseShow']);
$router->get('/universi/{id}/edizioni/{idEdizione}/rose/{idSquadra}', [EdizioneController::class, 'roseEdit']);
$router->post('/universi/{id}/edizioni/{idEdizione}/rose/{idSquadra}', [EdizioneController::class, 'roseUpdate']);
$router->post('/universi/{id}/edizioni/{idEdizione}/rose/{idSquadra}/auto', [EdizioneController::class, 'roseAutoSquadra']);

/*
|--------------------------------------------------------------------------
| COMPETIZIONI DELL'EDIZIONE
|--------------------------------------------------------------------------
*/
$router->get('/universi/{id}/edizioni/{idEdizione}/competizioni', [EdizioneController::class, 'competizioniIndex']);
$router->get('/universi/{id}/edizioni/{idEdizione}/competizioni/{idEdizioneCompetizione}/edit', [EdizioneController::class, 'competizioniEdit']);
$router->post('/universi/{id}/edizioni/{idEdizione}/competizioni/{idEdizioneCompetizione}/edit', [EdizioneController::class, 'competizioniUpdate']);
$router->get('/universi/{id}/edizioni/{idEdizione}/competizioni/{idEdizioneCompetizione}/classifica', [EdizioneController::class, 'classificaCompetizione']);
$router->get('/universi/{id}/edizioni/{idEdizione}/competizioni/{idEdizioneCompetizione}', [EdizioneController::class, 'showCompetizione']);
$router->post('/universi/{id}/edizioni/{idEdizione}/competizioni/{idEdizioneCompetizione}', [EdizioneController::class, 'competizioniUpdate']);
$router->post('/universi/{id}/edizioni/{idEdizione}/competizioni/{idEdizioneCompetizione}/eliminazione/avanza', [EdizioneController::class, 'avanzaEliminazioneDiretta']);

/*
|--------------------------------------------------------------------------
| PARTITE - SINGOLA PARTITA
|--------------------------------------------------------------------------
*/
$router->post('/universi/{id}/edizioni/{idEdizione}/competizioni/{idEdizioneCompetizione}/partite/risultato', [PartitaController::class, 'salvaRisultato']);
$router->post('/universi/{id}/edizioni/{idEdizione}/competizioni/{idEdizioneCompetizione}/partite/{idPartita}/simula', [PartitaController::class, 'simulaPartita']);
$router->post('/universi/{id}/edizioni/{idEdizione}/competizioni/{idEdizioneCompetizione}/partite/{idPartita}/reset', [PartitaController::class, 'resetPartita']);

/*
|--------------------------------------------------------------------------
| PARTITE - GIORNATA
|--------------------------------------------------------------------------
*/
$router->post('/universi/{id}/edizioni/{idEdizione}/competizioni/{idEdizioneCompetizione}/giornate/{giornata}/salva', [PartitaController::class, 'salvaGiornata']);
$router->post('/universi/{id}/edizioni/{idEdizione}/competizioni/{idEdizioneCompetizione}/giornate/{giornata}/simula', [PartitaController::class, 'simulaGiornata']);
$router->post('/universi/{id}/edizioni/{idEdizione}/competizioni/{idEdizioneCompetizione}/giornate/{giornata}/reset', [PartitaController::class, 'resetGiornata']);
$router->post('/universi/{id}/edizioni/{idEdizione}/competizioni/{idEdizioneCompetizione}/fasi/{fase}/giornate/{giornata}/salva', [PartitaController::class, 'salvaFaseGiornata']);
$router->post('/universi/{id}/edizioni/{idEdizione}/competizioni/{idEdizioneCompetizione}/fasi/{fase}/giornate/{giornata}/simula', [PartitaController::class, 'simulaFaseGiornata']);
$router->post('/universi/{id}/edizioni/{idEdizione}/competizioni/{idEdizioneCompetizione}/fasi/{fase}/giornate/{giornata}/reset', [PartitaController::class, 'resetFaseGiornata']);

/*
|--------------------------------------------------------------------------
| PARTITE - INTERA COMPETIZIONE
|--------------------------------------------------------------------------
*/
$router->post('/universi/{id}/edizioni/{idEdizione}/competizioni/{idEdizioneCompetizione}/partite/salva-tutte', [PartitaController::class, 'salvaTutte']);
$router->post('/universi/{id}/edizioni/{idEdizione}/competizioni/{idEdizioneCompetizione}/partite/simula-tutte', [PartitaController::class, 'simulaTutte']);
$router->post('/universi/{id}/edizioni/{idEdizione}/competizioni/{idEdizioneCompetizione}/partite/reset-tutte', [PartitaController::class, 'resetTutte']);

$request = new Request();
$router->gestisci($request);
