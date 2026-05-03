# Condomini Gestionale

Condomini Gestionale è una base per un gestionale condominiale scritto in PHP.  
Questa versione fornisce una struttura iniziale per creare un software di gestione di condomini, unità immobiliari, persone e documenti.  
È pensata per essere installata in un ambiente di hosting condiviso come **SupportHost** e può essere facilmente estesa secondo le proprie esigenze.

## Funzionalità incluse

- **Autenticazione**: login e registrazione con password hash sicura.
- **Ruoli**: ruoli di base `admin` e `condomino` con permessi limitati.
- **Gestione condomini**: elenco dei condomini con possibilità di aggiungere e modificare.
- **Gestione unità immobiliari**: elenco delle unità per ogni condominio.
- **Anagrafiche persone**: registrazione e gestione dei proprietari/condomini.
- **Archivio documenti**: upload dei documenti condominiali con controllo della visibilità.

Molti moduli sono solo abbozzati e vanno completati in base alle necessità del vostro progetto.  
Per esempio, mancano la ripartizione millesimale, la contabilità, le rate e le assemblee.  
L'architettura è pensata per facilitare l'aggiunta di ulteriori funzionalità.

## Requisiti

- PHP 8.0 o superiore con estensione PDO per MySQL abilitata.
- Server web (Apache, Nginx o simili).
- Database MySQL/MariaDB.

## Installazione

1. Copiare l'intera cartella `condomini` nel proprio spazio web (ad esempio in `/public_html/condomini`).
2. Creare un database MySQL, ad esempio `condomini_db`.
3. Importare lo schema dal file `database/schema.sql` all’interno del nuovo database.  
   Potete farlo da riga di comando (`mysql -u utente -p condomini_db < schema.sql`) oppure tramite phpMyAdmin.
4. Modificare il file `config/config.php` impostando i parametri di connessione al database (`$host`, `$dbname`, `$user`, `$password`).
5. Assicurarsi che la directory `storage/documents` sia scrivibile dal server web (permessi 755 o 775).
6. Accedere alla pagina di login e creare un nuovo utente amministratore tramite il modulo di registrazione.  
   Nella tabella `users` potete modificare il campo `role` in `admin` direttamente nel database se necessario.

## Sicurezza

- Tutte le password sono memorizzate usando `password_hash()` con l’algoritmo predefinito.
- Le sessioni PHP sono inizializzate in `config/config.php` e vengono rigenerate dopo il login.
- È presente un controllo di permesso minimo per distinguere tra amministratori e condomini.
- I file caricati vengono salvati nella cartella `storage/documents` al di fuori della root pubblica e sono serviti tramite lo script `download.php` che verifica i permessi.

## Struttura del progetto

- `index.php` – pagina iniziale, reindirizza alla dashboard corretta se l’utente è loggato oppure alla pagina di login.
- `login.php` – modulo di autenticazione.
- `register.php` – modulo di registrazione (gli account sono creati come `condomino` e necessitano l’approvazione di un amministratore per l’accesso completo).
- `logout.php` – effettua il logout distruggendo la sessione.
- `admin/` – contiene tutte le pagine destinate all’amministratore (dashboard, condomini, unità, documenti, utenti).
- `area-condomino/` – contiene le pagine riservate ai condomini.
- `app/` – funzioni PHP riutilizzabili (autenticazione, database, helpers).
- `config/` – file di configurazione (connessione database e costanti).
- `database/` – contiene lo script SQL per la creazione delle tabelle.
- `storage/` – directory protetta per i file caricati (documenti).
- `assets/` – file statici come CSS e JavaScript.

## Note

Questo progetto è un punto di partenza e non un prodotto finito.  
Per un'applicazione completa dovrete integrare funzionalità come la gestione dei millesimi, la ripartizione delle spese, la generazione di PDF per bilanci e verbali, le comunicazioni via email e molto altro.  
L’architettura modulare vi consente di estendere il sistema senza stravolgere la struttura di base.
