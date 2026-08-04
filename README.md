# Progetto: Universi Calcistici

## Cos'è
App web per creare "universi calcistici" simulati: dentro ogni universo si creano Squadre e Giocatori (facoltativi), poi Competizioni di vario tipo (campionato, eliminazione diretta, gironi+eliminazione, playoff/out) con le proprie regole (promozioni, retrocessioni, qualificazioni ad altre competizioni). Le Edizioni (stagioni) si susseguono nel tempo: la prima è configurata a mano, dalla seconda in poi il sistema deve poter auto-generare le squadre partecipanti in base alle regole di passaggio (`CompetizioneAvanzamento`) applicate ai risultati dell'edizione precedente.

Stack: PHP (vanilla, no framework) + MySQL/MariaDB + JS/CSS/HTML, ambiente locale Laragon + HeidiSQL. Composer usato solo per autoload PSR-4 e per leggere il file `.env` (pacchetto `vlucas/phpdotenv`).

## Struttura cartelle
```text
universi-calcistici/
├── database/
│   ├── seeds/
│   ├── competitiontype.json
│   └── schema.sql
├── public/
│   ├── assets/
│   │   ├── css/
│   │   │   └── style.css
│   │   ├── img/
│   │   └── js/
│   │       └── app.js
│   ├── index.php
│   └── .htaccess
├── src/
│   ├── Config/
│   │   └── Database.php
│   ├── Controllers/
│   │   ├── CompetizioneAvanzamentoController.php
│   │   ├── CompetizioneController.php
│   │   ├── DevController.php
│   │   ├── GiocatoreController.php
│   │   ├── PartitaController.php
│   │   ├── SquadraController.php
│   │   └── UniversoController.php
│   ├── Http/
│   │   ├── Request.php
│   │   └── Router.php
│   ├── Models/
│   │   ├── Competizione.php
│   │   ├── CompetizioneAvanzamento.php
│   │   ├── Edizione.php
│   │   ├── Giocatore.php
│   │   ├── Partita.php
│   │   ├── Squadra.php
│   │   └── Universo.php
│   ├── Services/
│   │   ├── AvanzamentoService.php
│   │   ├── CalendarioService.php
│   │   ├── ClassificaService.php
│   │   └── SimulazioneService.php
│   ├── Support/
│   │   ├── CompetitionTypes.php
│   │   ├── Countries.php
│   │   ├── Names.php
│   │   └── Positions.php
│   └── Views/
│       ├── competizioni/
│       │   ├── create.php
│       │   ├── edit.php
│       │   ├── index.php
│       │   └── show.php
│       ├── competizioni_avanzamento/
│       │   ├── create.php
│       │   └── edit.php
│       ├── giocatori/
│       │   ├── create.php
│       │   ├── edit.php
│       │   └── index.php
│       ├── partials/
│       │   ├── link.php
│       │   └── script.php
│       ├── squadre/
│       │   ├── create.php
│       │   ├── edit.php
│       │   └── index.php
│       ├── universi/
│       │   ├── create.php
│       │   ├── edit.php
│       │   ├── giocatori.php
│       │   ├── index.php
│       │   ├── show.php
│       │   └── squadre.php
│       └── home.php
├── vendor/
├── composer.json
├── composer.lock
├── README.md
└── .gitignore
```

## Schema DB (riassunto concettuale)
- `Squadre`, `Giocatori`: anagrafiche GLOBALI, riutilizzabili su più Universi.
- `Universi` + `UniversoSquadre`/`UniversoGiocatori`: quali Squadre/Giocatori esistono in un dato Universo.
- `Edizioni`: le stagioni di un Universo (Stato: bozza/in_corso/conclusa).
- `EdizioneSquadra`/`EdizioneGiocatore`: valori (Valore, FattoreCasa, Attacco, Difesa) ereditati dall'anagrafica base ma sovrascrivibili per singola edizione.
- `EdizioneSquadreGiocatori`: in quale Squadra gioca ogni Giocatore, in quella Edizione.
- `Competizioni`: entità stabile (es. "Serie A"), con `Struttura` JSON per le regole di formato (punti vittoria, andata/ritorno, n. gruppi, ecc).
- `CompetizioneAvanzamento`: regole di passaggio tra Competizioni (promozioni, retrocessioni, qualificazioni), con `Dettagli` JSON per i parametri (posizione_da/posizione_a, tipo regola, ecc). Il caso "miglior 4° tra più campionati" si gestisce marcando i candidati con Stato='Candidata' in `EdizioneCompetizioneSquadra` e lasciando che una funzione PHP scelga il migliore.
- `EdizioneCompetizione`: una Competizione concretizzata in una specifica Edizione (contiene anche `Podio` JSON a fine stagione).
- `EdizioneCompetizioneSquadra`: quali Squadre partecipano a quella Competizione/Edizione, con Stato (Iscritta/Qualificata/Candidata/Eliminata/Promossa/Retrocessa) e Motivo (perché sono lì).
- `Partite`: Fase (ENUM ordinabile: Girone, Sessantaquattresimo... Finale), Giornata, Girone, punteggi, `Dettagli` JSON (supplementari, rigori, tie_id, simulata/manuale).
- `PartitaEventi`: un evento = una riga (gol/autogol/cartellini/sostituzioni), con `IDSquadra` esplicito (fondamentale per gli autogol) e `Dettagli` JSON per assist/sostituito/ecc. Se l'universo non usa i giocatori, questa tabella resta vuota e tutto il resto funziona comunque solo a livello squadre.

Lo schema completo con tutte le colonne/tipi è nel file `database/schema.sql`.

## Decisioni già prese
- Niente framework PHP: vanilla PHP + PDO + piccolo Router fatto in casa.
- Composer usato solo per autoload PSR-4 (`App\` → `src/`) e `phpdotenv`.
- Le regole complesse (formato competizione, regole di avanzamento, dettagli partita/eventi) stanno in colonne JSON, non in tabelle rigide: la complessità vive nel codice PHP (`Services`), non nello schema.
- Autogol/assist: risolti con `IDSquadra` esplicito in `PartitaEventi` + JSON per i dettagli secondari.
- Fase delle partite: ENUM ordinabile alfabeticamente per progressione naturale del torneo (`Girone < Sessantaquattresimo < ... < Finale`).
- Multi-utente non serve ora ma la tabella `users` è già prevista per il futuro (per ora si lavora con un solo utente/owner implicito).

## Come procedere (stato aggiornato)

### Stato reale del progetto
Il blocco base del dominio è ormai costruito e il blocco `Edizioni` è stato avviato in modo concreto. Oggi il progetto permette già di creare stagioni, inizializzare i dati stagionali e gestire manualmente o automaticamente le rose squadra della singola edizione. [cite:4]

Il prossimo blocco logico non è più “iniziare Edizioni”, ma **consolidare le competizioni stagionali** e preparare il primo flusso completo che porta da edizione a competizione, poi a calendario, risultati e classifica. [cite:4]

### Cose già fatte
- Struttura progetto definita e stabilizzata.
- Schema database consolidato in `database/schema.sql`.
- Anagrafiche globali complete: `Squadre` e `Giocatori`.
- Gestione `Universi` completata.
- Gestione `UniversoSquadre` completata.
- Gestione `UniversoGiocatori` completata.
- CRUD base per:
  - `Squadre`
  - `Giocatori`
  - `Universi`
  - `Competizioni`
  - `CompetizioneAvanzamento`
- CRUD base di `Edizioni` avviato.
- Inizializzazione dati stagionali avviata:
  - copia `UniversoSquadre` -> `EdizioneSquadra`
  - copia `UniversoGiocatori` -> `EdizioneGiocatore`
- Gestione `EdizioneSquadraGiocatore` avviata:
  - associazione manuale giocatori -> squadra
  - verifica rosa completa/incompleta
  - auto-assegnazione singola squadra
  - auto-assegnazione globale
- Prime viste e controller del blocco stagionale già presenti.
- Base architetturale pronta con:
  - `public/index.php`
  - router minimale
  - `Database.php`
  - model PDO
  - controller MVC leggero
  - services dedicati per logica complessa.

### Cose già impostate bene
- La logica complessa vive nei **Services**, non nello schema.
- Le regole di formato competizione stanno in `Competizioni.Struttura`.
- Le regole di passaggio stanno in `CompetizioneAvanzamento.Dettagli`.
- La prima stagione resta configurata in modo manuale.
- Dalla seconda stagione in poi il sistema dovrà poter auto-generare i partecipanti in base ai risultati precedenti.
- Le rose stagionali sono già trattate come dati dell’edizione e non come modifica delle anagrafiche globali.

### Stato dei file nel progetto
La struttura del progetto è ormai coerente: `Controllers`, `Models`, `Services`, `Support`, `Views`, `public`, `database`, `vendor`. Questo significa che i nuovi blocchi possono essere aggiunti senza ripensare l’architettura, ma solo estendendo il flusso applicativo già esistente. [cite:4]

### Cosa manca ancora
- Consolidamento finale del blocco `Edizioni`:
  - rifinitura rotte/controller/view;
  - blocchi modifica quando l’edizione non è più in bozza;
  - controlli di coerenza più chiari.
- Gestione completa di `EdizioneCompetizione`.
- Gestione completa di `EdizioneCompetizioneSquadra`.
- Primo flusso stabile di competizione stagionale manuale.
- Generazione `Partite`.
- Inserimento e modifica risultati.
- Calcolo classifica.
- Simulazione automatica.
- Avanzamento automatico tra stagioni.

## Prossimi passi

### 1. Chiudere davvero il blocco Edizioni
Prima di andare avanti, il blocco `Edizioni` va considerato stabile in modo esplicito:
- creare, visualizzare e navigare un’edizione;
- gestire squadre e giocatori stagionali;
- completare e verificare le rose;
- impedire modifiche quando l’edizione viene finalizzata.

Questo è il primo punto da chiudere bene, perché tutto il resto dipende dalla solidità dello stato stagionale.

### 2. Aprire il blocco Competizioni stagionali
Il prossimo vero blocco di sviluppo è:
- `EdizioneCompetizione`
- `EdizioneCompetizioneSquadra`

Obiettivo: prendere una `Competizione` astratta e renderla concreta dentro una specifica `Edizione`, con le relative squadre iscritte.

### 3. Rendere giocabile una competizione semplice
Prima di supportare formule complesse, serve un flusso completo per il caso più semplice:
- una competizione di tipo campionato;
- iscrizione manuale squadre;
- generazione calendario round-robin;
- inserimento risultati;
- classifica affidabile.

### 4. Rimandare ancora i Services pesanti
`SimulazioneService` e `AvanzamentoService` vanno introdotti solo dopo che:
- la competizione stagionale è stabile;
- il calendario è generabile;
- la classifica è corretta.

È giusto rimandarli, perché altrimenti si automatizza un flusso che non è ancora completamente consolidato.

## Appunti tecnici da ricordare
- `Squadre` e `Giocatori` sono anagrafiche globali.
- `UniversoSquadre` e `UniversoGiocatori` definiscono cosa esiste in un universo.
- `Edizioni` rappresentano le stagioni dell’universo.
- `EdizioneSquadra` e `EdizioneGiocatore` sono copie stagionali modificabili.
- `EdizioneSquadraGiocatore` assegna i giocatori alle squadre nella stagione.
- `Competizioni` sono entità astratte e stabili.
- `CompetizioneAvanzamento` definisce le regole di passaggio tra competizioni.
- `EdizioneCompetizione` rappresenta la competizione dentro una specifica stagione.
- `EdizioneCompetizioneSquadra` definisce le squadre partecipanti a quella competizione stagionale.
- `Partite` e `PartitaEventi` arrivano solo dopo il consolidamento del blocco stagionale e competitivo.

## Ordine consigliato di sviluppo
1. Chiudere e rifinire il blocco `Edizioni`.
2. Consolidare `EdizioneSquadraGiocatore`.
3. Completare `EdizioneCompetizione`.
4. Completare `EdizioneCompetizioneSquadra`.
5. Stabilizzare una competizione stagionale manuale.
6. Generare `Partite` per il campionato semplice.
7. Inserire e modificare risultati.
8. Calcolare classifica e verificare i criteri base.
9. Introdurre simulazione.
10. Automatizzare gli avanzamenti stagionali.

## Task corrente
### Obiettivo immediato
Portare a termine il primo flusso completo:
**Edizione -> Competizione stagionale -> Iscrizione squadre -> Calendario semplice**

### Prossima implementazione concreta
- rifinire `EdizioneCompetizione`;
- rifinire `EdizioneCompetizioneSquadra`;
- permettere di iscrivere manualmente le squadre di edizione a una competizione stagionale;
- verificare che il numero partecipanti sia coerente con `Competizioni.NumeroPartecipanti`.

### Criterio di completamento
Questo blocco è considerato chiuso quando:
- posso aprire una edizione;
- posso vedere le competizioni stagionali dell’edizione;
- posso associare squadre a una competizione stagionale;
- posso validare che il numero squadre sia corretto;
- la competizione è pronta per la generazione del calendario.

## Criterio di avanzamento
Si passa al blocco successivo solo quando quello precedente è stabile nel flusso, leggibile nel codice, chiaro nelle viste e coerente nei dati salvati. In questo progetto la priorità non è la quantità di feature, ma la continuità del flusso stagionale senza zone ambigue.