# Gestionale Condomini

Gestionale condominiale completo scritto in PHP/MySQL con Bootstrap 5.  
Pensato per amministratori di condominio, installabile su hosting condiviso (SupportHost/cPanel).

## Funzionalita

### Amministratore
- **Dashboard**: panoramica condomini, unita, persone, ticket, rate, morosita, assemblee
- **Condomini**: CRUD completo con scheda dettaglio e tab (unita, documenti, esercizi, rate, ticket, assemblee)
- **Unita immobiliari**: CRUD con millesimi (4 tabelle), associazioni persone, filtri
- **Persone/Anagrafiche**: persone fisiche, aziende, fornitori con ricerca e dettaglio
- **Documenti**: upload sicuro, visibilita (pubblico/condominio/unita/privato), 12 categorie
- **Esercizi contabili**: CRUD con riepilogo entrate/uscite/saldo
- **Categorie spesa**: 10 categorie predefinite + personalizzabili
- **Movimenti/Prima nota**: entrate/uscite con filtri, saldo progressivo, categorie, persone
- **Millesimi**: modifica bulk con controllo somma = 1000
- **Riparti**: distribuzione spese con calcolo quote e generazione automatica rate
- **Rate**: filtri per stato, pagamento diretto tramite modal, auto-scaduta
- **Pagamenti**: storico completo con filtri
- **Morosita**: rate scadute con giorni ritardo, CSV export, stampa
- **Ticket**: messaggi, note interne, cambio stato/priorita/assegnazione, 7 stati
- **Assemblee**: presenze con millesimi, deleghe, verbale, 4 stati
- **Report**: 6 tipi con visualizzazione e CSV export
- **Gestione utenti**: approvazione, ruoli, stati

### Area Condomino
- Dashboard con condomini, rate, documenti, ticket, assemblee
- Apertura e gestione ticket con messaggi
- Profilo e cambio password
- Visualizzazione documenti accessibili

### Sicurezza
- Prepared statements (PDO) su tutte le query
- CSRF token su tutti i form POST
- htmlspecialchars() su tutti gli output
- Audit log per login, CRUD, pagamenti
- Storage protetto con .htaccess
- Session regenerate su login

## Requisiti

- PHP 8.0+ con estensione PDO MySQL
- MySQL 5.7+ / MariaDB 10.3+
- Apache con mod_rewrite (per .htaccess)

## Installazione rapida

1. Caricare i file sul server (FTP/cPanel File Manager)
2. Creare database MySQL e importare `database/schema.sql`
3. (Opzionale) Importare `database/seed.sql` per categorie spesa e admin demo
4. Configurare `config/config.php` con i parametri del database
5. Impostare permessi 755 su `storage/`
6. Accedere con admin demo: `admin@gestionale.local` / `password`

Guida dettagliata: [docs/INSTALL_SUPPORTHOST.md](docs/INSTALL_SUPPORTHOST.md)

## Struttura

```
config/          Configurazione database e costanti
app/             Classi PHP (Auth, Condomini, Rate, Tickets, Assemblee, ecc.)
admin/           Pagine amministratore
area-condomino/  Pagine area riservata condomino
includes/        Header e footer comuni
assets/          CSS, JS
database/        schema.sql + seed.sql
storage/         Documenti caricati (protetto)
docs/            Documentazione
```

## Documentazione

- [Installazione SupportHost](docs/INSTALL_SUPPORTHOST.md)
- [Guida Admin](docs/USER_GUIDE_ADMIN.md)
- [Guida Condomino](docs/USER_GUIDE_CONDOMINO.md)
- [Changelog](docs/CHANGELOG.md)
- [Roadmap](docs/ROADMAP.md)

## Licenza

Progetto privato. Tutti i diritti riservati.
