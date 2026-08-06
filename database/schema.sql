-- ============================================================
-- SCHEMA DB - "UNIVERSI CALCISTICI" (versione definitiva)
-- Motore: MySQL / MariaDB (utf8mb4)
-- ============================================================
SET
    NAMES utf8mb4;

SET
    FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- ANAGRAFICHE GLOBALI (riutilizzabili su piu' universi)
-- ============================================================
CREATE TABLE Squadre (
    ID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    Nome VARCHAR(150) NOT NULL,
    Colori JSON NULL,
    Valore DECIMAL(5, 2) NULL DEFAULT 0,
    FattoreCasa DECIMAL(5, 2) NULL DEFAULT 0,
    -- bonus prestazionale in casa
    Paese VARCHAR(80) NULL,
    Tipo ENUM('Club', 'Nazionale') NOT NULL DEFAULT 'Club',
    Creato DATETIME DEFAULT CURRENT_TIMESTAMP,
    Modificato DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE = InnoDB;

CREATE TABLE Giocatori (
    ID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    Nome VARCHAR(150) NOT NULL,
    Posizione ENUM(
        'POR',
        'TD',
        'TS',
        'DC',
        'CC',
        'MED',
        'CL',
        'CR',
        'TRQ',
        'AS',
        'AD',
        'ATT'
    ) NOT NULL,
    Attacco DECIMAL(5, 2) NULL DEFAULT 0,
    Difesa DECIMAL(5, 2) NULL DEFAULT 0,
    Paese VARCHAR(80) NULL,
    Nascita DATE NULL,
    Creato DATETIME DEFAULT CURRENT_TIMESTAMP,
    Modificato DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE = InnoDB;

-- ============================================================
-- UNIVERSI
-- ============================================================
CREATE TABLE Universi (
    ID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    Nome VARCHAR(150) NOT NULL,
    Descrizione TEXT NULL,
    Creato DATETIME DEFAULT CURRENT_TIMESTAMP,
    Modificato DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE = InnoDB;

-- Squadre/Giocatori "arruolati" in un dato Universo
CREATE TABLE UniversoSquadre (
    IDUniverso INT UNSIGNED NOT NULL,
    IDSquadra INT UNSIGNED NOT NULL,
    PRIMARY KEY (IDUniverso, IDSquadra),
    FOREIGN KEY (IDUniverso) REFERENCES Universi(ID) ON DELETE CASCADE,
    FOREIGN KEY (IDSquadra) REFERENCES Squadre(ID) ON DELETE CASCADE
) ENGINE = InnoDB;

CREATE TABLE UniversoGiocatori (
    IDUniverso INT UNSIGNED NOT NULL,
    IDGiocatore INT UNSIGNED NOT NULL,
    PRIMARY KEY (IDUniverso, IDGiocatore),
    FOREIGN KEY (IDUniverso) REFERENCES Universi(ID) ON DELETE CASCADE,
    FOREIGN KEY (IDGiocatore) REFERENCES Giocatori(ID) ON DELETE CASCADE
) ENGINE = InnoDB;

-- ============================================================
-- EDIZIONI (stagioni dell'Universo)
-- ============================================================
CREATE TABLE Edizioni (
    ID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    IDUniverso INT UNSIGNED NOT NULL,
    Anno INT NOT NULL,
    Nome VARCHAR(100) NULL,
    Stato ENUM('bozza', 'in_corso', 'conclusa') NOT NULL DEFAULT 'bozza',
    Creato DATETIME DEFAULT CURRENT_TIMESTAMP,
    Modificato DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (IDUniverso) REFERENCES Universi(ID) ON DELETE CASCADE,
    INDEX idx_edizioni_universo (IDUniverso)
) ENGINE = InnoDB;

-- Valori "ereditati e mutati" per l'edizione (override rispetto ad anagrafica base)
CREATE TABLE EdizioneSquadra (
    IDEdizione INT UNSIGNED NOT NULL,
    IDSquadra INT UNSIGNED NOT NULL,
    Valore DECIMAL(5, 2) NULL,
    FattoreCasa DECIMAL(5, 2) NULL,
    PRIMARY KEY (IDEdizione, IDSquadra),
    FOREIGN KEY (IDEdizione) REFERENCES Edizioni(ID) ON DELETE CASCADE,
    FOREIGN KEY (IDSquadra) REFERENCES Squadre(ID) ON DELETE CASCADE
) ENGINE = InnoDB;

CREATE TABLE EdizioneGiocatore (
    IDEdizione INT UNSIGNED NOT NULL,
    IDGiocatore INT UNSIGNED NOT NULL,
    Attacco DECIMAL(5, 2) NULL DEFAULT 0,
    Difesa DECIMAL(5, 2) NULL DEFAULT 0,
    PRIMARY KEY (IDEdizione, IDGiocatore),
    FOREIGN KEY (IDEdizione) REFERENCES Edizioni(ID) ON DELETE CASCADE,
    FOREIGN KEY (IDGiocatore) REFERENCES Giocatori(ID) ON DELETE CASCADE
) ENGINE = InnoDB;

-- Rosa: quale giocatore gioca in quale squadra, in quella edizione
CREATE TABLE EdizioneSquadraGiocatore (
    IDEdizione INT UNSIGNED NOT NULL,
    IDSquadra INT UNSIGNED NOT NULL,
    IDGiocatore INT UNSIGNED NOT NULL,
    PRIMARY KEY (IDEdizione, IDSquadra, IDGiocatore),
    FOREIGN KEY (IDEdizione) REFERENCES Edizioni(ID) ON DELETE CASCADE,
    FOREIGN KEY (IDSquadra) REFERENCES Squadre(ID) ON DELETE CASCADE,
    FOREIGN KEY (IDGiocatore) REFERENCES Giocatori(ID) ON DELETE CASCADE,
    -- un giocatore non puo' stare in due squadre nella stessa edizione
    UNIQUE KEY uq_giocatore_per_edizione (IDEdizione, IDGiocatore)
) ENGINE = InnoDB;

-- ============================================================
-- COMPETIZIONI
-- ============================================================
CREATE TABLE Competizioni (
    ID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    IDUniverso INT UNSIGNED NOT NULL,
    NomeCompetizione VARCHAR(150) NOT NULL,
    Tipo VARCHAR(100) NOT NULL,
    -- es. lega, eliminazione_diretta,
    NumeroPartecipanti INT UNSIGNED NOT NULL DEFAULT 0,
    Giri INT UNSIGNED NOT NULL DEFAULT 1,
    InizialmenteVuota BOOLEAN NOT NULL DEFAULT 0,
    Struttura JSON NULL,
    -- regole effettive: punti vittoria/pareggio, andata/ritorno,
    -- n. gruppi, tie-break, playoff/out, ecc.
    Creato DATETIME DEFAULT CURRENT_TIMESTAMP,
    Modificato DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (IDUniverso) REFERENCES Universi(ID) ON DELETE CASCADE,
    INDEX idx_competizioni_universo (IDUniverso),
    INDEX idx_competizioni_tipo (Tipo)
) ENGINE = InnoDB;

-- Regole di passaggio tra competizioni (promozioni/retrocessioni/qualificazioni)
CREATE TABLE CompetizioneAvanzamento (
    ID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    IDCompetizionePartenza INT UNSIGNED NOT NULL,
    IDCompetizioneArrivo INT UNSIGNED NOT NULL,
    Ordine TINYINT UNSIGNED NOT NULL DEFAULT 1,
    Dettagli JSON NULL,
    -- es. {"posizione_da":1,"posizione_a":3,"tipo":"diretta"}
    -- oppure {"posizione":4,"tipo":"candidato_miglior_quarto"}
    Creato DATETIME DEFAULT CURRENT_TIMESTAMP,
    Modificato DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (IDCompetizionePartenza) REFERENCES Competizioni(ID) ON DELETE CASCADE,
    FOREIGN KEY (IDCompetizioneArrivo) REFERENCES Competizioni(ID) ON DELETE CASCADE
) ENGINE = InnoDB;

-- Una competizione "concretizzata" in una specifica edizione
CREATE TABLE EdizioneCompetizione (
    ID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    IDEdizione INT UNSIGNED NOT NULL,
    IDCompetizione INT UNSIGNED NOT NULL,
    Podio JSON NULL,
    -- es. {"1":IDSquadra,"2":IDSquadra,"3":IDSquadra,"4":IDSquadra}
    Creato DATETIME DEFAULT CURRENT_TIMESTAMP,
    Modificato DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (IDEdizione) REFERENCES Edizioni(ID) ON DELETE CASCADE,
    FOREIGN KEY (IDCompetizione) REFERENCES Competizioni(ID) ON DELETE CASCADE,
    UNIQUE KEY uq_edizione_competizione (IDEdizione, IDCompetizione)
) ENGINE = InnoDB;

-- Squadre partecipanti a quella competizione, in quella edizione
CREATE TABLE EdizioneCompetizioneSquadra (
    IDEdizioneCompetizione INT UNSIGNED NOT NULL,
    IDSquadra INT UNSIGNED NOT NULL,
    Stato ENUM(
        'Iscritta',
        'Qualificata',
        'Candidata',
        'Eliminata',
        'Promossa',
        'Retrocessa'
    ) NULL,
    Motivo VARCHAR(150) NULL,
    -- es. 'Iscrizione manuale', 'Promossa da Serie B', '3° posto Bundesliga'
    Creato DATETIME DEFAULT CURRENT_TIMESTAMP,
    Modificato DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (IDEdizioneCompetizione, IDSquadra),
    FOREIGN KEY (IDEdizioneCompetizione) REFERENCES EdizioneCompetizione(ID) ON DELETE CASCADE,
    FOREIGN KEY (IDSquadra) REFERENCES Squadre(ID) ON DELETE CASCADE
) ENGINE = InnoDB;

-- ============================================================
-- PARTITE ED EVENTI
-- ============================================================
CREATE TABLE Partite (
    ID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    IDEdizioneCompetizione INT UNSIGNED NOT NULL,
    IDSquadraCasa INT UNSIGNED NOT NULL,
    IDSquadraTrasferta INT UNSIGNED NOT NULL,
    GoalCasa TINYINT UNSIGNED NULL,
    GoalTrasferta TINYINT UNSIGNED NULL,
    Fase ENUM(
        'Girone',
        'Sessantaquattresimo',
        'Trentaduesimo',
        'Sedicesimo',
        'Ottavo',
        'Quarto',
        'Semifinale',
        'Finale'
    ) NULL,
    Giornata SMALLINT NULL,
    -- giornata di lega, o leg 1/2 per elim. diretta
    Girone VARCHAR(10) NULL,
    -- es. 'A','B' per fase a gruppi
    Data DATETIME NULL,
    Stato ENUM(
        'programmata',
        'giocata',
        'rinviata',
        'annullata'
    ) NOT NULL DEFAULT 'programmata',
    Dettagli JSON NULL,
    -- es. supplementari, rigori, simulata/manuale, tie_id, ecc.
    Creato DATETIME DEFAULT CURRENT_TIMESTAMP,
    Modificato DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (IDEdizioneCompetizione) REFERENCES EdizioneCompetizione(ID) ON DELETE CASCADE,
    FOREIGN KEY (IDSquadraCasa) REFERENCES Squadre(ID),
    FOREIGN KEY (IDSquadraTrasferta) REFERENCES Squadre(ID),
    INDEX idx_partite_edizionecompetizione (IDEdizioneCompetizione),
    INDEX idx_partite_giornata (IDEdizioneCompetizione, Giornata),
    INDEX idx_partite_squadre (IDSquadraCasa, IDSquadraTrasferta)
) ENGINE = InnoDB;

CREATE TABLE PartitaEventi (
    ID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    IDPartita INT UNSIGNED NOT NULL,
    IDGiocatore INT UNSIGNED NULL,
    -- NULL se l'universo non usa i giocatori
    IDSquadra INT UNSIGNED NOT NULL,
    -- squadra a cui va attribuito l'evento (punteggio/classifica)
    Tipo ENUM('gol','rigore_sbagliato','ammonizione','espulsione') NOT NULL,
    Minuto TINYINT UNSIGNED NOT NULL,
    Dettagli JSON NULL,
    -- es. {"assist_id":IDGiocatore}, {"autogol_squadra_beneficio":IDSquadra},
    -- {"sostituito_id":IDGiocatore}, {"minuto_recupero":2}
    Creato DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (IDPartita) REFERENCES Partite(ID) ON DELETE CASCADE,
    FOREIGN KEY (IDGiocatore) REFERENCES Giocatori(ID) ON DELETE
    SET
        NULL,
        FOREIGN KEY (IDSquadra) REFERENCES Squadre(ID),
        INDEX idx_eventi_partita (IDPartita),
        INDEX idx_eventi_giocatore (IDGiocatore)
) ENGINE = InnoDB;

SET
    FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- NOTE D'USO
-- ============================================================
-- 1) Crei l'Universo -> crei/associ Squadre e Giocatori (UniversoSquadre/UniversoGiocatori).
-- 2) Crei le Competizioni dell'Universo (Serie A, Coppa Italia, Champions...) con
--    Struttura JSON (regole di formato) e le CompetizioneAvanzamento (regole di
--    passaggio tra competizioni, es. Serie A pos.1-3 -> Champions diretta,
--    Serie A pos.4 -> Champions "candidata" per il confronto miglior-4°, gestito poi via PHP).
-- 3) Crei l'Edizione 1 -> per ogni Competizione coinvolta crei una riga in
--    EdizioneCompetizione -> assegni manualmente le squadre in EdizioneCompetizioneSquadra
--    (la 1a edizione e' sempre manuale, Motivo = "Iscrizione manuale").
-- 4) Generi le Partite (manuali o simulate, tracciato in Dettagli) e, se l'universo
--    usa i giocatori, popoli PartitaEventi.
-- 5) Classifiche, marcatori, forma, andamento, scontri diretti, classifica perpetua,
--    albo d'oro: tutte query di aggregazione su Partite/PartitaEventi/EdizioneCompetizioneSquadra,
--    nessuna tabella aggiuntiva necessaria per ora.
-- 6) A fine Edizione (Stato = 'conclusa'): calcoli il Podio su EdizioneCompetizione,
--    poi applichi le CompetizioneAvanzamento sulla classifica finale per popolare
--    automaticamente EdizioneCompetizioneSquadra della EdizioneCompetizione successiva
--    (creando prima il "contenitore" edizione N+1). I casi tipo "miglior 4° tra piu'
--    campionati" si gestiscono marcando tutti i candidati come Stato='candidata'
--    e lasciando che un confronto in PHP promuova solo il migliore.