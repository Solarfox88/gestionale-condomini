# Changelog

## v1.8.0 - Audit legale, tracciabilita, blocchi e cancellazione logica (2026-05-12)

### Nuove funzionalita

- **Audit log avanzato** (`admin/audit-log.php`): Visualizzazione completa di tutti gli eventi (login, CRUD, upload/download, export, import, backup, cancellazioni) con filtri per utente, azione, entita e date
- **Cancellazione logica**: Movimenti, rate, pagamenti, documenti, persone, unita usano soft delete (`deleted_at`) invece di DELETE distruttivi
- **Tracciamento azioni critiche**: Audit log per creazione/modifica condomini, approvazione utenti, upload/download documenti, cancellazione movimenti/pagamenti
- **Schema DB aggiornato**: Colonna `deleted_at` aggiunta a movimenti, rate, pagamenti, documenti, persone, unita_immobiliari
- **Funzioni restore**: Possibilita di ripristinare record cancellati logicamente
- **Badge colorati azioni**: Visualizzazione con colori diversi per tipo azione nel log

## v1.3.0 - Riparti millesimali avanzati (2026-05-13)

### Nuove funzionalita

- **Tabelle millesimali personalizzate**: Creazione di tabelle millesimali custom per condominio (es. giardino, garage), con valori per unita e totale quadratura
- **Riparti avanzati**: Categoria spesa, filtro per scala/palazzina, tabella personalizzata, spese individuali (100% su una unita)
- **Esclusione unita da riparto**: Possibilita di escludere/includere singole unita dal calcolo con ricalcolo automatico
- **Rettifica manuale quote**: Modifica importo per singola unita prima dell'approvazione, con possibilita di reset
- **Controllo differenza**: Avviso visivo se somma quote effettive non quadra con importo totale
- **Report/CSV riparto**: Esportazione CSV dettaglio quote con millesimi, importi calcolati, rettifiche, esclusioni
- **Rate collegate a riparto**: Le rate generate da riparto mantengono il riferimento (`riparto_id`) per tracciabilita
- **Pagina millesimi potenziata**: Tab standard + tab tabelle personalizzate con CRUD completo e quadratura live

### Database

- Nuove tabelle: `tabelle_millesimali`, `millesimi_personalizzati`
- Colonne aggiunte a `riparti`: `tabella_personalizzata_id`, `categoria_id`, `scala_filtro`
- Colonne aggiunte a `riparti_dettaglio`: `esclusa`, `importo_rettificato`
- Colonna aggiunta a `rate`: `riparto_id`
- Tipo spesa esteso con `individuale`

## v1.2.0 - Contabilita professionale (2026-05-13)

### Nuove funzionalita

- **Preventivi**: CRUD bilancio preventivo per esercizio con voci per categoria (entrate/uscite previste), approvazione, totali automatici
- **Consuntivi**: Calcolo automatico da movimenti reali raggruppati per categoria, confronto preventivo vs consuntivo con scostamenti, quadrature contabili (entrate, uscite, saldo, rate emesse, incassato, residuo), esportazione CSV
- **Chiusura esercizio**: Workflow aperto→chiuso con conferma, blocco modifiche su movimenti/rate/pagamenti per esercizi chiusi, riapertura solo admin
- **Conguagli**: Calcolo automatico conguagli per unita (quota spettante vs pagato), generazione rate di conguaglio con scadenza personalizzabile
- **Quadrature contabili**: Widget con totali entrate/uscite/saldo/rate/incassato/residuo in esercizio-detail e consuntivi
- **Dashboard admin potenziata**: Esercizi aperti con link consuntivo, sezione contabilita con link rapidi preventivi/consuntivi

### Sicurezza

- Blocco creazione/eliminazione movimenti su esercizi chiusi
- Blocco creazione/pagamento/eliminazione rate su esercizi chiusi
- Blocco eliminazione pagamenti su esercizi chiusi
- Audit log per chiusura/riapertura esercizio, approvazione preventivo

### Database

- Nuove tabelle: `preventivi`, `preventivo_voci`, `conguagli`

## v1.1.0 - Pre-produzione SupportHost (2026-05-13)

### Hardening sicurezza

- Sessioni: aggiunto `use_strict_mode`, `cookie_httponly`, `cookie_samesite=Lax`
- Tutti i redirect e link usano `url()` helper per supporto sottocartella (BASE_URL)
- `h()` reso null-safe (`?string` con null coalescing)
- Download documenti usa nome file originale

### Fix

- Tutti i percorsi hardcoded `/admin/...` sostituiti con `url('/admin/...')`
- Redirect login/logout/register usano `url()` per compatibilita sottocartella
- Header navbar completamente aggiornato con `url()` su tutti i link

### Configurazione

- Aggiunta costante `BASE_URL` in `config/config.php` per installazione in sottocartella
- Aggiunta funzione `url()` helper per generare percorsi corretti
- Documentazione aggiornata con istruzioni BASE_URL

### Test E2E

- 56 test E2E automatizzati passano (login, CRUD, pagamenti, area condomino, sicurezza, audit log)
- Copertura: homepage, login, 17 pagine admin, condominio, persona, unita, esercizio, movimento, rata, pagamento, ticket, assemblea, morosita, report, CSV export, registrazione, approvazione utente, area condomino, controllo accessi

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
