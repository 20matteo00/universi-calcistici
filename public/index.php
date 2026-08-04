<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Http\Request;
use App\Http\Router;
use App\Controllers\SquadraController;
use App\Controllers\GiocatoreController;
use App\Controllers\UniversoController;
use App\Controllers\CompetizioneController;
use App\Controllers\CompetizioneAvanzamentoController;
use App\Controllers\DevController;

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
| Pagine Squadre
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
| Pagine Giocatori
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
| Pagine Universi
|--------------------------------------------------------------------------
*/
$router->get('/universi', [UniversoController::class, 'index']);
$router->get('/universi/crea', [UniversoController::class, 'create']);
$router->post('/universi/crea', [UniversoController::class, 'store']);
$router->get('/universi/{id}', [UniversoController::class, 'show']);
$router->get('/universi/modifica/{id}', [UniversoController::class, 'edit']);
$router->post('/universi/modifica/{id}', [UniversoController::class, 'update']);
$router->post('/universi/elimina/{id}', [UniversoController::class, 'delete']);
$router->post('/universi/{id}/squadre', [UniversoController::class, 'aggiungiSquadra']);
$router->post('/universi/{id}/squadre/{idSquadra}/rimuovi', [UniversoController::class, 'rimuoviSquadra']);
$router->post('/universi/{id}/giocatori', [UniversoController::class, 'aggiungiGiocatore']);
$router->post('/universi/{id}/giocatori/{idGiocatore}/rimuovi', [UniversoController::class, 'rimuoviGiocatore']);
$router->get('/universi/{id}/squadre', [UniversoController::class, 'gestisciSquadre']);
$router->post('/universi/{id}/squadre/aggiungi-selezionate', [UniversoController::class, 'aggiungiSquadreSelezionate']);
$router->get('/universi/{id}/giocatori', [UniversoController::class, 'gestisciGiocatori']);
$router->post('/universi/{id}/giocatori/aggiungi-selezionati', [UniversoController::class, 'aggiungiGiocatoriSelezionati']);
$router->post('/universi/{id}/squadre/rimuovi-selezionate', [UniversoController::class, 'rimuoviSquadreSelezionate']);
$router->post('/universi/{id}/giocatori/rimuovi-selezionati', [UniversoController::class, 'rimuoviGiocatoriSelezionati']);

/*
|--------------------------------------------------------------------------
| Pagine Competizioni
|--------------------------------------------------------------------------
*/
$router->get('/universi/{id}/competizioni', [CompetizioneController::class, 'indexByUniverso']);
$router->get('/universi/{id}/competizioni/create', [CompetizioneController::class, 'createByUniverso']);
$router->post('/universi/{id}/competizioni', [CompetizioneController::class, 'storeByUniverso']);
$router->get('/universi/{id}/competizioni/{idCompetizione}/edit', [CompetizioneController::class, 'editByUniverso']);
$router->post('/universi/{id}/competizioni/{idCompetizione}/update', [CompetizioneController::class, 'updateByUniverso']);
$router->post('/universi/{id}/competizioni/{idCompetizione}/delete', [CompetizioneController::class, 'deleteByUniverso']);
$router->get('/universi/{id}/competizioni/{idCompetizione}', [CompetizioneController::class, 'showByUniverso']);
$router->get('/universi/{id}/competizioni/collegamenti/create', [CompetizioneAvanzamentoController::class, 'createByUniverso']);
$router->post('/universi/{id}/competizioni/collegamenti', [CompetizioneAvanzamentoController::class, 'storeByUniverso']);
$router->get('/universi/{id}/competizioni/collegamenti/{idCollegamento}/edit', [CompetizioneAvanzamentoController::class, 'editByUniverso']);
$router->post('/universi/{id}/competizioni/collegamenti/{idCollegamento}/update', [CompetizioneAvanzamentoController::class, 'updateByUniverso']);
$router->post('/universi/{id}/competizioni/collegamenti/{idCollegamento}/delete', [CompetizioneAvanzamentoController::class, 'deleteByUniverso']);

/*
|--------------------------------------------------------------------------
| Pagine Edizioni
|--------------------------------------------------------------------------
*/
$router->get('/universi/{id}/edizioni', [App\Controllers\EdizioneController::class, 'indexByUniverso']);
$router->get('/universi/{id}/edizioni/crea', [App\Controllers\EdizioneController::class, 'crea']);
$router->post('/universi/{id}/edizioni/salva', [App\Controllers\EdizioneController::class, 'salva']);
$router->get('/universi/{id}/edizioni/{idEdizione}', [App\Controllers\EdizioneController::class, 'showByUniverso']);

$router->get('/universi/{id}/edizioni/{idEdizione}/rose', [App\Controllers\EdizioneController::class, 'roseIndex']);
$router->post('/universi/{id}/edizioni/{idEdizione}/rose/auto', [App\Controllers\EdizioneController::class, 'roseAutoTutte']);
$router->post('/universi/{id}/edizioni/{idEdizione}/rose/{idSquadra}/auto', [App\Controllers\EdizioneController::class, 'roseAutoSquadra']);
$router->get('/universi/{id}/edizioni/{idEdizione}/rose/{idSquadra}', [App\Controllers\EdizioneController::class, 'roseEdit']);
$router->post('/universi/{id}/edizioni/{idEdizione}/rose/{idSquadra}', [App\Controllers\EdizioneController::class, 'roseUpdate']);

$router->get('/universi/{id}/edizioni/{idEdizione}/competizioni', [App\Controllers\EdizioneController::class, 'competizioniIndex']);
$router->get('/universi/{id}/edizioni/{idEdizione}/competizioni/{idEdizioneCompetizione}', [App\Controllers\EdizioneController::class, 'competizioniEdit']);
$router->post('/universi/{id}/edizioni/{idEdizione}/competizioni/{idEdizioneCompetizione}', [App\Controllers\EdizioneController::class, 'competizioniUpdate']);

$router->post('/universi/{id}/edizioni/{idEdizione}/finalizza', [App\Controllers\EdizioneController::class, 'finalizzaEdizione']);

$request = new Request();
$router->gestisci($request);