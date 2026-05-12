# Roadmap sviluppo Gestionale Condomini

Questo documento descrive la roadmap funzionale del gestionale condominiale e lo stato progressivo dei moduli.

## Stato repository

Il progetto nasce come applicazione PHP/MySQL compatibile con hosting cPanel/SupportHost. La struttura è volutamente semplice: PHP procedurale/classi leggere, PDO, Bootstrap, MySQL/MariaDB e storage documentale locale.

## Fase 1 - Base tecnica e anagrafica

Obiettivo: rendere disponibile una base installabile e navigabile.

Completato nel repository:

- configurazione PDO centralizzata;
- login, logout e registrazione;
- ruoli base `admin`, `condomino`, `fornitore`;
- gestione condomini;
- gestione unità immobiliari;
- gestione persone/anagrafiche;
- gestione documenti;
- area condòmino base;
- audit log a schema;
- menu amministratore.

## Fase 2 - Relazioni unità/persone

Obiettivo: collegare ogni unità immobiliare a proprietari, inquilini, delegati o altri soggetti.

Completato/previsto:

- tabella `unita_persone`;
- ruoli: proprietario, comproprietario, inquilino, usufruttuario, delegato, altro;
- percentuale di possesso/rapporto;
- date di inizio/fine rapporto;
- base per controllo documenti e rate per singola unità.

## Fase 3 - Contabilità base

Obiettivo: gestire esercizi, prima nota, rate e pagamenti.

Moduli previsti:

- `esercizi`;
- `categorie_spesa`;
- `movimenti`;
- `rate`;
- `pagamenti`;
- riepilogo saldi per condominio, unità e persona;
- morosità.

## Fase 4 - Riparti e millesimi

Obiettivo: calcolare quote e rate da tabelle millesimali.

Moduli previsti:

- millesimi proprietà;
- millesimi scale;
- millesimi ascensore;
- millesimi riscaldamento;
- riparto spese ordinarie e straordinarie;
- generazione automatica rate.

## Fase 5 - Ticket e manutenzioni

Obiettivo: sostituire chat e messaggi informali con un tracciamento ordinato.

Moduli previsti:

- apertura ticket da condòmino;
- assegnazione a fornitore;
- messaggi interni/pubblici;
- stati del ticket;
- collegamento a documenti, foto e movimenti contabili.

## Fase 6 - Assemblee e verbali

Obiettivo: gestire convocazioni, presenze, deleghe, quorum, votazioni e verbali.

Moduli previsti:

- assemblee;
- ordine del giorno;
- presenze;
- deleghe;
- millesimi presenti;
- verbale;
- pubblicazione documento finale.

## Fase 7 - Report, PDF ed esportazioni

Obiettivo: rendere il gestionale usabile professionalmente.

Report previsti:

- elenco condomini;
- elenco unità;
- elenco persone;
- documenti per condominio;
- prima nota;
- rate e pagamenti;
- morosità;
- convocazioni e verbali;
- esportazioni CSV/Excel.

## Fase 8 - Sicurezza e produzione

Obiettivo: hardening per uso reale.

Checklist:

- CSRF token nei form;
- validazione upload con whitelist MIME;
- storage fuori public root;
- gestione errori senza dettagli sensibili;
- log attività;
- backup DB e documenti;
- HTTPS obbligatorio;
- policy password;
- recupero password;
- approvazione utenti;
- separazione ambienti staging/produzione.

## Priorità consigliata

1. Consolidare autenticazione, utenti e permessi.
2. Completare unità/persone e associazioni.
3. Aggiungere contabilità base.
4. Aggiungere rate e pagamenti.
5. Aggiungere ticket.
6. Aggiungere assemblee.
7. Aggiungere PDF/report.
8. Hardening produzione.
