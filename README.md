# Universi Calcistici

Applicazione web in PHP per creare e simulare universi calcistici personalizzati, con squadre, giocatori, stagioni, competizioni e regole di collegamento tra competizioni.

## Indice

- [Panoramica](#panoramica)
- [Obiettivo del progetto](#obiettivo-del-progetto)
- [Stack tecnico](#stack-tecnico)
- [Stato attuale](#stato-attuale)
- [Funzionalità disponibili](#funzionalità-disponibili)
- [Funzionalità in corso o previste](#funzionalità-in-corso-o-previste)
- [Architettura del progetto](#architettura-del-progetto)
- [Struttura cartelle](#struttura-cartelle)
- [Modello dati](#modello-dati)
- [Regole e decisioni architetturali](#regole-e-decisioni-architetturali)
- [Setup locale](#setup-locale)
- [Flusso corretto di lavoro](#flusso-corretto-di-lavoro)
- [Roadmap tecnica](#roadmap-tecnica)
- [Task corrente](#task-corrente)

## Panoramica

**Universi Calcistici** è una web app locale pensata per simulare ecosistemi calcistici completi.  
Ogni universo contiene squadre e, opzionalmente, giocatori; dentro ogni universo si susseguono edizioni stagionali, e dentro ogni edizione vivono le competizioni stagionali con le rispettive partecipanti, partite, risultati e sviluppi futuri.

L’idea centrale è separare:
- anagrafiche globali;
- dati dell’universo;
- dati della singola stagione;
- dati della singola competizione stagionale.

Questo permette di avere continuità tra le stagioni senza sporcare i dati base e senza duplicare logica inutile.

## Obiettivo del progetto

L’obiettivo è costruire un simulatore calcistico modulare, leggibile e progressivo, dove:

- la **prima edizione** di un universo viene configurata manualmente;
- dalla **seconda edizione in poi** il sistema può proporre o generare automaticamente i partecipanti alle competizioni in base ai risultati precedenti;
- il progetto resta gestibile con PHP vanilla, senza framework, mantenendo la logica complessa concentrata nei `Services`.

## Stack tecnico

- PHP vanilla
- MySQL / MariaDB
- HTML / CSS / JavaScript
- Bootstrap per la UI
- Composer solo per:
  - autoload PSR-4
  - lettura del file `.env` tramite `vlucas/phpdotenv`
- Ambiente locale:
  - Laragon
  - HeidiSQL

## Stato attuale

Il progetto ha ormai una base architetturale stabile e un primo blocco stagionale funzionante.

Attualmente risultano operativi nel flusso principale:
- gestione anagrafiche globali;
- gestione universi;
- gestione edizioni;
- inizializzazione dati stagionali;
- assegnazione giocatori alle squadre stagionali;
- CRUD base delle competizioni;
- CRUD base delle regole di collegamento tra competizioni;
- primo flusso di competizione stagionale con partite generate e gestione risultati;
- supporto sia a competizioni di tipo **lega** sia a competizioni di tipo **eliminazione diretta**.

Negli ultimi aggiornamenti è stata migliorata in particolare la vista competizione:
- distinzione più chiara tra dati del **blocco** e dati della **singola partita**;
- uso corretto di `fase + giornata` nei blocchi a eliminazione diretta;
- migliore organizzazione delle info secondarie dentro il collapse della singola partita;
- correzione del controllo tipo competizione da `eliminazione` a `eliminazione_diretta`.

## Funzionalità disponibili

### Anagrafiche globali
- CRUD `Squadre`
- CRUD `Giocatori`

### Universi
- CRUD `Universi`
- associazione `UniversoSquadre`
- associazione `UniversoGiocatori`

### Edizioni
- creazione e gestione `Edizioni`
- inizializzazione dati stagionali da universo:
  - `UniversoSquadre -> EdizioneSquadra`
  - `UniversoGiocatori -> EdizioneGiocatore`

### Rose stagionali
- gestione `EdizioneSquadraGiocatore`
- associazione manuale giocatori -> squadra della stagione
- auto-assegnazione singola squadra
- auto-assegnazione globale
- controllo rosa completa / incompleta

### Competizioni astratte
- CRUD `Competizioni`
- struttura competizione salvata in JSON (`Struttura`)
- supporto ai tipi principali:
  - lega
  - eliminazione diretta

### Avanzamenti
- CRUD `CompetizioneCollegamento`
- regole di passaggio salvate in JSON (`Dettagli`)

### Competizioni stagionali
- gestione `EdizioneCompetizione`
- gestione `EdizioneCompetizioneSquadra`
- iscrizione squadre a una competizione stagionale
- validazione del numero partecipanti
- generazione partite
- gestione risultati partita
- simulazione base partita / blocco / competizione
- reset risultati
- distinzione tra blocchi di lega e blocchi a eliminazione diretta

## Funzionalità in corso o previste

- consolidamento finale dei vincoli sulle edizioni non più in bozza;
- miglioramento UI e UX delle pagine competizione;
- classifica completa e stabile per le leghe;
- rifinitura della simulazione automatica;
- collegamenti automatici tra stagioni;
- generazione automatica partecipanti dalla seconda edizione in poi;
- gestione completa di podio, qualificazioni, promozioni e retrocessioni;
- uso futuro della tabella `users`.

## Architettura del progetto

Il progetto usa un’architettura MVC leggera, senza framework.

### Principi usati
- router minimale fatto in casa;
- controller sottili;
- logica di dominio spostata nei `Services`;
- model semplici orientati a PDO;
- viste PHP server-rendered.

La logica importante non viene “nascosta” nel database ma gestita nel codice PHP, soprattutto nei servizi dedicati.

## Struttura cartelle

```text
```
universi-calcistici
├──database
│   ├──seeds
│   ├──competitiontype.json
│   └──schema.sql
├──public
│   ├──assets
│   │   ├──css
│   │   │   └──style.css
│   │   ├──img
│   │   └──js
│   │   │   └──app.js
│   ├──index.php
│   └──.htaccess
├──src
│   ├──Config
│   │   └──Database.php
│   ├──Controllers
│   │   ├──CompetizioneCollegamentoController.php
│   │   ├──CompetizioneController.php
│   │   ├──DevController.php
│   │   ├──EdizioneController.php
│   │   ├──GiocatoreController.php
│   │   ├──PartitaController.php
│   │   ├──SquadraController.php
│   │   └──UniversoController.php
│   ├──Http
│   │   ├──Request.php
│   │   └──Router.php
│   ├──Models
│   │   ├──Competizione.php
│   │   ├──CompetizioneCollegamento.php
│   │   ├──Edizione.php
│   │   ├──EdizioneCompetizione.php
│   │   ├──EdizioneGiocatore.php
│   │   ├──EdizioneSquadra.php
│   │   ├──Giocatore.php
│   │   ├──Partita.php
│   │   ├──PartitaEvento.php
│   │   ├──PartitaQuery.php
│   │   ├──Squadra.php
│   │   └──Universo.php
│   ├──Services
│   │   ├──Competizioni
│   │   │   ├──CompetizioneCalendarioService.php
│   │   │   ├──CompetizioneClassificaCalculator.php
│   │   │   ├──CompetizioneClassificaService.php
│   │   │   ├──CompetizioneCollegamentoService.php
│   │   │   ├──CompetizioneEliminazioneDirettaService.php
│   │   │   └──CompetizioneShowService.php
│   │   ├──Edizioni
│   │   │   ├──CompetizioneIscrizioneService.php
│   │   │   ├──CompetizioneUpdateService.php
│   │   │   ├──EdizioneContextService.php
│   │   │   ├──EdizioneCreateService.php
│   │   │   ├──EdizioneFinalizeService.php
│   │   │   ├──RosaAutoAssignService.php
│   │   │   ├──RosaValidatorService.php
│   │   │   └──RoseUpdateService.php
│   │   └──Partite
│   │   │   ├──PartitaContextService.php
│   │   │   ├──PartitaEventGeneratorService.php
│   │   │   ├──PartitaLockService.php
│   │   │   ├──PartitaResetService.php
│   │   │   ├──PartitaResultService.php
│   │   │   └──PartitaSimulationService.php
│   ├──Support
│   │   ├──CompetitionTypes.php
│   │   ├──Countries.php
│   │   ├──Icons.php
│   │   ├──Names.php
│   │   └──Positions.php
│   └──Views
│   │   ├──competizioni
│   │   │   ├──collegamenti
│   │   │   │   ├──create.php
│   │   │   │   └──edit.php
│   │   │   ├──create.php
│   │   │   ├──edit.php
│   │   │   ├──index.php
│   │   │   └──show.php
│   │   ├──edizioni
│   │   │   ├──competizioni
│   │   │   │   ├──classifica.php
│   │   │   │   ├──edit.php
│   │   │   │   ├──index.php
│   │   │   │   └──show.php
│   │   │   ├──rose
│   │   │   │   ├──edit.php
│   │   │   │   ├──index.php
│   │   │   │   └──show.php
│   │   │   ├──create.php
│   │   │   ├──index.php
│   │   │   └──show.php
│   │   ├──giocatori
│   │   │   ├──create.php
│   │   │   ├──edit.php
│   │   │   └──index.php
│   │   ├──partials
│   │   │   ├──link.php
│   │   │   └──script.php
│   │   ├──squadre
│   │   │   ├──create.php
│   │   │   ├──edit.php
│   │   │   └──index.php
│   │   ├──universi
│   │   │   ├──create.php
│   │   │   ├──edit.php
│   │   │   ├──giocatori.php
│   │   │   ├──index.php
│   │   │   ├──show.php
│   │   │   └──squadre.php
│   │   └──home.php
├──composer.json
├──composer.lock
├──README.md
└──.gitignore
```
```

## Modello dati

### Anagrafiche globali
- `Squadre`, `Giocatori`: entità base riutilizzabili in più universi.

### Universo
- `Universi`: contenitore principale.
- `UniversoSquadre`, `UniversoGiocatori`: definiscono quali squadre e giocatori appartengono a quel mondo simulato.

### Stagione
- `Edizioni`: stagione di un universo, con stato come `bozza`, `in_corso`, `conclusa`.
- `EdizioneSquadra`, `EdizioneGiocatore`: copie stagionali modificabili dei dati base.
- `EdizioneSquadreGiocatori`: assegna i giocatori alle squadre di quella stagione.

### Competizioni
- `Competizioni`: entità astratte e stabili, come “Serie A” o “Coppa Italia”.
- `CompetizioneCollegamento`: regole di passaggio tra competizioni.

### Competizioni stagionali
- `EdizioneCompetizione`: concretizzazione stagionale di una competizione dentro un’edizione.
- `EdizioneCompetizioneSquadra`: partecipanti della competizione stagionale, con stato e motivo.

### Partite
- `Partite`: contiene fase, giornata, eventuale girone, risultato e dettagli JSON.
- `PartitaEventi`: eventi granulari della partita, opzionali se il mondo usa anche i giocatori.

## Regole e decisioni architetturali

- Nessun framework PHP.
- Router leggero custom.
- Composer solo per autoload e `.env`.
- Logica complessa nei `Services`.
- JSON per strutture flessibili:
  - `Competizioni.Struttura`
  - `CompetizioneCollegamento.Dettagli`
  - `Partite.Dettagli`
  - `PartitaEventi.Dettagli`
- La prima stagione si prepara manualmente.
- Le stagioni successive devono poter derivare dai risultati della precedente.
- Le anagrafiche globali non devono essere mutate per rappresentare dati stagionali.
- Nelle competizioni a eliminazione diretta la chiave logica dei blocchi è **fase + giornata**, non solo `giornata`.
- Informazioni comuni al blocco, come `fase` e `giro`, vanno mostrate a livello blocco e non ripetute inutilmente su ogni partita. [cite:4]

## Setup locale

### Requisiti
- PHP 8.x
- MySQL o MariaDB
- Composer
- Laragon consigliato
- HeidiSQL consigliato

### Installazione
```bash
git clone <repo>
cd universi-calcistici
composer install
```

### Configurazione ambiente
Crea un file `.env` nella root del progetto con variabili simili a queste:

```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=universi_calcistici
DB_USERNAME=root
DB_PASSWORD=
```

### Database
- crea il database vuoto;
- importa `database/schema.sql`;
- carica eventuali seed se necessari.

### Avvio
Configura Laragon o Apache per puntare a `public/` come document root.

## Flusso corretto di lavoro

L’ordine corretto del progetto è questo:

1. Creare anagrafiche globali (`Squadre`, `Giocatori`).
2. Creare un `Universo`.
3. Associare squadre e giocatori all’universo.
4. Creare una `Edizione`.
5. Inizializzare i dati stagionali.
6. Completare le rose stagionali.
7. Creare o collegare le competizioni dell’edizione.
8. Iscrivere le squadre alla competizione stagionale.
9. Validare il numero partecipanti.
10. Generare le partite.
11. Inserire o simulare risultati.
12. Calcolare classifiche o avanzamenti.
13. Preparare la stagione successiva.

## Roadmap tecnica

### Blocco 1 - Consolidamento edizioni
- blocchi modifica quando l’edizione non è più in bozza;
- controlli di coerenza più chiari;
- rifinitura controller e viste.

### Blocco 2 - Competizione stagionale stabile
- rifinitura `EdizioneCompetizione`;
- rifinitura `EdizioneCompetizioneSquadra`;
- gestione pulita partecipanti;
- validazioni più robuste.

### Blocco 3 - Flusso partite completo
- generazione calendario/turni;
- inserimento risultati;
- reset risultati;
- simulazione manuale e massiva;
- UI competizione più ordinata.

### Blocco 4 - Logiche sportive
- classifica completa;
- spareggi e criteri;
- gestione coppe più avanzata;
- podio e chiusura competizione.

### Blocco 5 - Continuità stagionale
- collegamenti automatici;
- qualificazioni;
- promozioni e retrocessioni;
- auto-generazione partecipanti per edizioni successive.

## Task corrente

### Obiettivo immediato
Chiudere il primo flusso completo e stabile:

**Edizione -> Competizione stagionale -> Iscrizione squadre -> Generazione partite -> Gestione risultati**

### Prossime implementazioni concrete
- rifinire la vista `competizioni/show.php`;
- separare in modo definitivo dati del blocco e dati della singola partita;
- consolidare la generazione dei blocchi per:
  - lega
  - eliminazione diretta;
- rifinire le action:
  - salva singola partita
  - salva blocco
  - simula partita
  - simula blocco
  - reset partita
  - reset blocco;
- verificare coerenza tra `Competizione.Tipo` e logica usata nei servizi.

### Criterio di completamento
Questo blocco si considera chiuso quando:
- posso aprire una edizione;
- posso vedere le competizioni stagionali dell’edizione;
- posso associare squadre a una competizione stagionale;
- posso validare il numero partecipanti;
- posso generare le partite;
- posso salvare e simulare risultati in modo coerente;
- nel caso di coppa vedo correttamente `fase + giornata`;
- la competizione è pronta per classifica e collegamenti.