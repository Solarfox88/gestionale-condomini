# Guida Utente - Amministratore

## Accesso

1. Accedere alla pagina di login (`/login.php`)
2. Inserire email e password dell'account admin
3. Si viene reindirizzati alla **Dashboard Admin**

## Dashboard

La dashboard mostra una panoramica completa:

- Numero condomini, unita immobiliari, persone registrate
- Ticket aperti e rate in scadenza
- Morosita totale e saldo movimenti
- Documenti recenti e prossime assemblee

## Gestione Condomini

**Menu: Anagrafiche > Condomini**

- **Lista**: tutti i condomini con nome, indirizzo, stato
- **Crea**: form con nome, CF, indirizzo, comune, provincia, CAP, IBAN, banca, email, PEC, note
- **Dettaglio**: scheda anagrafica + tab per Unita, Documenti, Esercizi, Rate, Ticket, Assemblee
- **Modifica**: dalla pagina dettaglio, cliccare "Modifica"

## Unita Immobiliari

**Menu: Anagrafiche > Unita immobiliari**

- **Lista**: filtrabile per condominio
- **Dettaglio**: modifica dati + gestione associazioni persone (proprietario, inquilino, ecc.)
- **Millesimi**: 4 tabelle millesimali (proprieta, scale, ascensore, riscaldamento) modificabili da Menu > Contabilita > Millesimi

## Persone e Anagrafiche

**Menu: Anagrafiche > Persone**

- Tipi: persona fisica, azienda, fornitore
- Ricerca per nome, cognome, CF, email
- Dettaglio con unita collegate
- Modifica ed eliminazione dal dettaglio

## Documenti

**Menu: Anagrafiche > Documenti**

- Upload con titolo, categoria, visibilita (pubblico/condominio/unita/privato)
- Categorie: verbali, bilanci, fatture, contratti, manutenzioni, ecc.
- Download sicuro tramite script protetto
- Filtri per condominio e categoria

## Esercizi Contabili

**Menu: Contabilita > Esercizi**

- Crea esercizi per ogni condominio con date inizio/fine
- Stati: bozza, aperto, chiuso
- Dettaglio con riepilogo entrate/uscite/saldo e lista movimenti

## Categorie Spesa

**Menu: Contabilita > Categorie spesa**

- 10 categorie predefinite (pulizia, energia, acqua, ecc.)
- Possibilita di creare nuove categorie

## Movimenti / Prima Nota

**Menu: Contabilita > Movimenti**

- Registra entrate e uscite per condominio/esercizio
- Associa a categoria, persona, unita
- Filtri per condominio, esercizio, tipo (entrata/uscita)
- Saldo progressivo visibile in tabella

## Riparti e Millesimi

**Menu: Contabilita > Millesimi / Riparti**

- **Millesimi**: modifica bulk tabelle millesimali con controllo somma = 1000
- **Riparti**: crea distribuzione spese, calcola quote proporzionali, genera rate automaticamente
- Workflow: bozza -> calcolato -> approvato -> rate generate

## Rate

**Menu: Pagamenti > Rate**

- Lista con filtri per condominio e stato
- Creazione manuale o automatica da riparti
- Pagamento diretto dalla lista con modal (importo, data, metodo, persona)
- Aggiornamento automatico stato (da_pagare/parziale/pagata/scaduta)

## Pagamenti

**Menu: Pagamenti > Pagamenti**

- Storico completo tutti i pagamenti registrati
- Filtro per condominio
- Eliminazione pagamento con ricalcolo stato rata

## Morosita

**Menu: Pagamenti > Morosita**

- Elenco rate scadute con calcolo residuo e giorni di ritardo
- Filtro per condominio
- **Esporta CSV**: scarica report in formato CSV
- **Stampa**: stampa diretta dal browser

## Ticket

**Menu: Ticket**

- Lista con filtri per condominio, stato, priorita
- **Dettaglio**: messaggi, note interne (non visibili al condomino), cambio stato/priorita/assegnazione
- Stati: aperto, preso in carico, in attesa, in lavorazione, risolto, chiuso, respinto
- Creazione ticket dall'admin

## Assemblee

**Menu: Assemblee**

- Lista con filtro per condominio
- **Dettaglio**: modifica dati, registrazione presenze con millesimi, cambio stato, verbale
- Stati: bozza, convocata, svolta, annullata

## Report

**Menu: Report**

- 6 tipi: condomini, unita, persone, movimenti, rate, pagamenti
- Visualizzazione con tabella ordinata
- Esportazione CSV per ogni tipo

## Gestione Utenti

**Menu: Utenti**

- Approva/rifiuta registrazioni in attesa
- Cambia ruolo (admin/condomino/fornitore)
- Cambia stato (pending/active/inactive)
