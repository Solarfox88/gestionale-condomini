# Roadmap sviluppo Gestionale Condomini

Questo documento descrive la roadmap funzionale del gestionale condominiale e lo stato progressivo dei moduli.

## Stato repository

Il progetto nasce come applicazione PHP/MySQL compatibile con hosting cPanel/SupportHost. La struttura è volutamente semplice: PHP procedurale/classi leggere, PDO, Bootstrap, MySQL/MariaDB e storage documentale locale.

## Fase 1 - Base tecnica e anagrafica

Obiettivo: rendere disponibile una base installabile e navigabile.

Completato nel repository:

- [x] configurazione PDO centralizzata;
- [x] login, logout e registrazione;
- [x] ruoli base `admin`, `condomino`, `fornitore`;
- [x] gestione condomini (CRUD);
- [x] gestione unità immobiliari (CRUD + dettaglio);
- [x] gestione persone/anagrafiche (CRUD);
- [x] gestione documenti (upload con validazione);
- [x] area condòmino base (dashboard, documenti, ticket);
- [x] gestione utenti (approvazione, ruoli, stati);
- [x] audit log a schema;
- [x] menu amministratore completo.

## Fase 2 - Relazioni unità/persone

Obiettivo: collegare ogni unità immobiliare a proprietari, inquilini, delegati o altri soggetti.

Completato:

- [x] tabella `unita_persone`;
- [x] ruoli: proprietario, comproprietario, inquilino, usufruttuario, delegato, altro;
- [x] percentuale di possesso/rapporto;
- [x] date di inizio/fine rapporto;
- [x] pagina dettaglio unità con gestione associazioni persone;
- [x] base per controllo documenti e rate per singola unità.

## Fase 3 - Contabilità base

Obiettivo: gestire esercizi, prima nota, rate e pagamenti.

Stato:

- [x] `esercizi` (CRUD);
- [ ] `categorie_spesa` (solo schema DB);
- [x] `movimenti` (CRUD);
- [x] `rate` (CRUD + registrazione pagamenti);
- [x] `pagamenti` (registrazione da pagina rate);
- [ ] riepilogo saldi per condominio, unità e persona;
- [ ] morosità.

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

Stato:

- [x] apertura ticket da condòmino (area condomino);
- [x] apertura ticket da admin;
- [x] gestione stati del ticket;
- [ ] assegnazione a fornitore;
- [ ] messaggi interni/pubblici;
- [ ] collegamento a documenti, foto e movimenti contabili.

## Fase 6 - Assemblee e verbali

Obiettivo: gestire convocazioni, presenze, deleghe, quorum, votazioni e verbali.

Stato:

- [x] assemblee (CRUD con convocazioni);
- [x] ordine del giorno;
- [ ] presenze;
- [ ] deleghe;
- [ ] millesimi presenti;
- [ ] verbale;
- [ ] pubblicazione documento finale.

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

- [x] CSRF token nei form;
- [x] validazione upload con whitelist MIME;
- [x] .htaccess per protezione directory sensibili;
- [x] .gitignore;
- [x] gestione errori senza dettagli sensibili;
- [x] approvazione utenti (pagina admin/utenti.php);
- [ ] log attività (schema pronto, implementazione pendente);
- [ ] backup DB e documenti;
- [ ] HTTPS obbligatorio;
- [ ] policy password;
- [ ] recupero password;
- [ ] separazione ambienti staging/produzione.

## Priorità consigliata

1. Consolidare autenticazione, utenti e permessi.
2. Completare unità/persone e associazioni.
3. Aggiungere contabilità base.
4. Aggiungere rate e pagamenti.
5. Aggiungere ticket.
6. Aggiungere assemblee.
7. Aggiungere PDF/report.
8. Hardening produzione.
