# Changelog

## v1.0.0 - Versione completa (2026-05-12)

### Nuove funzionalita

- **Dashboard admin completa**: widget con contatori condomini, unita, persone, ticket aperti, rate in scadenza, morosita totale, saldo movimenti, prossime assemblee, documenti recenti
- **Dettaglio condominio**: scheda anagrafica + 6 tab (unita, documenti, esercizi, rate, ticket, assemblee)
- **Categorie spesa**: CRUD completo con 10 categorie di default nel seed
- **Esercizi contabili**: CRUD + dettaglio con riepilogo entrate/uscite/saldo + lista movimenti
- **Movimenti/Prima nota**: filtri per condominio/esercizio/tipo, dropdown categorie/persone/unita, saldo progressivo, eliminazione
- **Rate**: filtri per condominio/stato, pagamento tramite modal, aggiornamento automatico stato scaduta, creazione manuale
- **Pagamenti**: pagina standalone con storico completo, filtro per condominio
- **Morosita**: elenco rate scadute, calcolo residuo e giorni ritardo, esportazione CSV, stampa
- **Ticket dettaglio**: messaggi, note interne (visibili solo admin), cambio stato/priorita/categoria/assegnazione
- **Assemblee dettaglio**: modifica, registrazione presenze con millesimi, cambio stato, verbale
- **Report**: 6 tipi di report (condomini, unita, persone, movimenti, rate, pagamenti) con visualizzazione e esportazione CSV
- **Area condomino**: dashboard completa (condomini, rate, documenti, ticket, assemblee), profilo con cambio password, vista ticket con messaggi, creazione ticket
- **Persone**: ricerca, dettaglio/modifica, eliminazione, unita collegate
- **Audit log**: tracciamento login, creazioni, modifiche, eliminazioni, pagamenti
- **Menu header**: dropdown organizzati (Anagrafiche, Contabilita, Pagamenti) + nome utente visibile
- **Seed SQL**: categorie spesa base + admin demo

### Miglioramenti

- `Helpers.php`: funzioni centralizzate (h(), format_euro(), stato_badge(), audit_log(), current_user(), csrf_field(), csrf_verify())
- Login aggiornato con tracciamento last_login_at e audit log
- Auth.php: aggiunta change_password(), get_user(), update_user_profile()
- Tutte le classi app/* potenziate con metodi completi (find, create, update, delete, filtri)

### Sicurezza

- CSRF token su tutti i form POST
- Prepared statements in tutte le query
- htmlspecialchars() su tutti gli output
- Audit log per azioni principali
- Controllo permessi su ogni pagina admin

## v0.3.0 - Fase 4: Riparti e millesimi (2026-05-10)

- Millesimi: vista/modifica bulk per condominio con controllo somma 1000
- Riparti: creazione, calcolo quote, approvazione, generazione automatica rate
- Workflow stati: bozza -> calcolato -> approvato -> rate_generate

## v0.2.0 - Fase 2-3: Moduli base (2026-05-08)

- Persone CRUD
- Unita immobiliari CRUD + associazioni
- Ticket base
- Assemblee base
- Utenti admin (approvazione, ruoli)
- Area condomino dashboard base

## v0.1.0 - Setup iniziale (2026-05-06)

- Struttura progetto (config, app, admin, area-condomino, includes, assets, database, storage)
- Autenticazione (login, logout, registrazione)
- Condomini CRUD
- Documenti upload/download
- Schema SQL base
- Fix sicurezza (smart quotes, CSRF, .htaccess)
- CI GitHub Actions (PHP syntax check)
