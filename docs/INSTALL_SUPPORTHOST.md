# Installazione su SupportHost / cPanel

## Requisiti

- PHP 8.0+ con estensione PDO MySQL
- MySQL 5.7+ / MariaDB 10.3+
- Apache con .htaccess abilitato

## 1. Preparazione hosting

1. Accedi al pannello cPanel del tuo hosting SupportHost
2. Crea un sottodominio o usa il dominio principale
3. Crea un database MySQL da **Database MySQL** in cPanel
4. Crea un utente database e assegnagli **tutti i permessi** al database creato
5. Annota: nome database, utente, password

## 2. Upload file

1. Accedi al **File Manager** di cPanel (o usa FTP)
2. Naviga nella cartella pubblica del dominio/sottodominio (es. `public_html/gestionale/`)
3. Carica tutti i file del progetto mantenendo la struttura delle cartelle

**Oppure via FTP:**
```bash
# Con FileZilla o altro client FTP
# Host: il tuo dominio
# Utente/password: quelli di cPanel
# Caricare nella cartella pubblica
```

## 3. Importa database

Da **phpMyAdmin** in cPanel:

1. Seleziona il database creato
2. Clicca su **Importa**
3. Carica il file `database/schema.sql`
4. (Opzionale) Importa anche `database/seed.sql` per categorie spesa base e admin demo

**Oppure da terminale (se disponibile):**
```bash
mysql -u utente_db -p nome_db < database/schema.sql
mysql -u utente_db -p nome_db < database/seed.sql
```

## 4. Configurazione

Modifica `config/config.php` con i parametri del tuo database:

```php
$host = 'localhost';
$dbname = 'nome_del_tuo_database';
$user = 'utente_database';
$password = 'password_database';
```

## 5. Permessi cartelle

Imposta i permessi corretti:

```
storage/             -> 755 o 775
storage/documents/   -> 755 o 775
```

Da cPanel File Manager: tasto destro sulla cartella > Permessi > 755.

Verifica che i file `.htaccess` in `storage/`, `app/`, `config/` siano presenti (impediscono l'accesso diretto dal browser).

## 6. Primo accesso

**Se hai importato seed.sql:**
- Email: `admin@gestionale.local`
- Password: `password`
- **Cambia la password subito dopo il primo accesso!**

**Senza seed.sql:**
1. Registra un nuovo utente da `/register.php`
2. Imposta manualmente il ruolo admin nel database:
```sql
UPDATE users SET role='admin', status='active' WHERE email='tua-email@example.com';
```

## 7. Checklist produzione

- [ ] HTTPS attivo (Let's Encrypt da cPanel)
- [ ] Password admin cambiata da quella demo
- [ ] Backup database automatico configurato (cPanel > Backup Wizard)
- [ ] Permessi storage corretti
- [ ] File `.htaccess` presenti in storage/, app/, config/
- [ ] Verificato caricamento documenti
- [ ] Verificato login/logout

## 8. Moduli inclusi

### Amministratore
- Dashboard con panoramica completa
- Condomini (CRUD + dettaglio con tab)
- Unita immobiliari con millesimi
- Persone/anagrafiche
- Documenti con upload sicuro
- Esercizi contabili con riepilogo
- Categorie spesa
- Movimenti/prima nota
- Millesimi e riparti
- Rate con pagamento diretto
- Pagamenti
- Morosita con CSV export
- Ticket con messaggi e note interne
- Assemblee con presenze
- Report con CSV export
- Gestione utenti

### Area Condomino
- Dashboard personale
- Ticket (apertura e messaggi)
- Profilo e cambio password
- Documenti, rate, assemblee

## 9. Limiti noti

- Nessuna generazione PDF nativa (usare la stampa del browser)
- Nessun invio email integrato (da implementare con servizio SMTP)
- Non richiede Composer
- Non richiede Docker

## 10. Supporto

Per problemi di installazione verificare:
1. Versione PHP (8.0+) da cPanel > Seleziona versione PHP
2. Estensioni PHP attive: pdo_mysql, mbstring, fileinfo
3. Permessi cartelle storage
4. Parametri database in config.php
