# Installazione su SupportHost / cPanel

## 1. Preparazione hosting

1. Crea un account cPanel separato per il dominio del gestionale.
2. Crea un database MySQL/MariaDB.
3. Crea un utente database e assegna tutti i permessi al database.
4. Carica i file del repository nella cartella pubblica del dominio o sottodominio.

## 2. Database

Importa il file:

```sql
database/schema.sql
```

Puoi importarlo da phpMyAdmin oppure da terminale se disponibile.

## 3. Configurazione

Apri:

```text
config/config.php
```

Aggiorna:

```php
$host = 'localhost';
$dbname = 'nome_database';
$user = 'utente_database';
$password = 'password_database';
```

## 4. Storage documenti

La cartella:

```text
storage/documents
```

deve essere scrivibile dal server web.

Permessi consigliati:

```text
755 o 775
```

Per produzione è preferibile spostare lo storage fuori dalla public root e aggiornare `STORAGE_PATH`.

## 5. Primo accesso admin

Crea un utente dal form di registrazione, poi imposta manualmente nel database:

```sql
UPDATE users SET role='admin', status='active' WHERE email='tua-email@example.com';
```

## 6. Checklist produzione

Prima dell'uso reale:

- Abilitare HTTPS.
- Proteggere `storage` da accesso diretto.
- Configurare backup database.
- Configurare backup documenti.
- Verificare upload file.
- Inserire CSRF token nei form.
- Configurare una policy password.
- Verificare ruoli e permessi.

## 7. Moduli inclusi

- Login/logout/registrazione.
- Condomini.
- Unità immobiliari.
- Persone.
- Documenti.
- Esercizi contabili.
- Movimenti.
- Rate e pagamenti.
- Ticket, assemblee e log a livello schema e classi base.
