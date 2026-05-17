-- Seed dati iniziali per Gestionale Condomini
-- Eseguire dopo schema.sql

-- Categorie spesa base
INSERT IGNORE INTO categorie_spesa (nome, descrizione, tipo_default) VALUES
('Pulizia scale', 'Spese per pulizia parti comuni e scale', 'uscita'),
('Energia elettrica', 'Consumi energia elettrica parti comuni', 'uscita'),
('Acqua', 'Consumi idrici parti comuni', 'uscita'),
('Manutenzioni', 'Manutenzione ordinaria e straordinaria', 'uscita'),
('Assicurazione', 'Polizze assicurative edificio', 'uscita'),
('Compenso amministratore', 'Compenso annuale amministratore', 'uscita'),
('Spese bancarie', 'Spese di gestione conto corrente', 'uscita'),
('Fondo cassa', 'Accantonamento fondo cassa', 'uscita'),
('Lavori straordinari', 'Lavori straordinari deliberati', 'uscita'),
('Altro', 'Altre spese non classificate', 'entrambi');

-- Utente admin demo - password: password (hash bcrypt)
-- NOTA: modificare la password dopo il primo accesso
INSERT INTO users (name, email, password_hash, role, status, created_at, updated_at) VALUES
('Admin Demo', 'admin@gestionale.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active', NOW(), NOW())
ON DUPLICATE KEY UPDATE name=name;

-- Condominio demo (opzionale)
-- Decommentare le righe seguenti per inserire dati di esempio

-- INSERT INTO condomini (nome, codice_fiscale, indirizzo, comune, provincia, cap, email, status, created_at, updated_at) VALUES
-- ('Condominio Via Roma 1', '12345678901', 'Via Roma 1', 'Roma', 'RM', '00100', 'condominio@esempio.it', 'active', NOW(), NOW());

-- SET @cond_id = LAST_INSERT_ID();

-- INSERT INTO unita_immobiliari (condominio_id, scala, piano, interno, mq, millesimi_proprieta, millesimi_scale, millesimi_ascensore, millesimi_riscaldamento, status, created_at, updated_at) VALUES
-- (@cond_id, 'A', '1', '1', 80.00, 200.0000, 200.0000, 200.0000, 200.0000, 'active', NOW(), NOW()),
-- (@cond_id, 'A', '2', '2', 100.00, 300.0000, 300.0000, 300.0000, 300.0000, 'active', NOW(), NOW()),
-- (@cond_id, 'A', '3', '3', 90.00, 250.0000, 250.0000, 250.0000, 250.0000, 'active', NOW(), NOW()),
-- (@cond_id, 'A', '4', '4', 110.00, 250.0000, 250.0000, 250.0000, 250.0000, 'active', NOW(), NOW());

-- INSERT INTO persone (nome, cognome, tipo, codice_fiscale, email, telefono, created_at, updated_at) VALUES
-- ('Mario', 'Rossi', 'persona', 'RSSMRA80A01H501A', 'mario.rossi@email.it', '3331234567', NOW(), NOW()),
-- ('Anna', 'Bianchi', 'persona', 'BNCNNA85B41H501B', 'anna.bianchi@email.it', '3339876543', NOW(), NOW());

-- Template comunicazioni base
INSERT IGNORE INTO template_comunicazioni (nome, oggetto, corpo, tipo) VALUES
('Sollecito pagamento', 'Sollecito di pagamento rate condominiali', 'Gentile {nome_destinatario},\n\ncon la presente Le comunichiamo che risultano non pagate le seguenti rate condominiali:\n\n{dettaglio_rate}\n\nTotale dovuto: {totale_dovuto}\n\nLa invitiamo a provvedere al pagamento entro 15 giorni dalla ricezione della presente comunicazione tramite bonifico bancario:\nIBAN: {iban_condominio}\nCausale: {causale}\n\nDistinti saluti,\nL''Amministratore', 'sollecito'),
('Convocazione assemblea', 'Convocazione assemblea condominiale', 'Gentile Condomino/a,\n\ncon la presente si comunica che e stata convocata l''assemblea condominiale:\n\nData prima convocazione: {data_prima}\nData seconda convocazione: {data_seconda}\nLuogo: {luogo}\n\nOrdine del giorno:\n{ordine_giorno}\n\nSi prega di voler partecipare personalmente o tramite delega scritta.\n\nDistinti saluti,\nL''Amministratore', 'convocazione'),
('Invio verbale', 'Verbale assemblea condominiale del {data_assemblea}', 'Gentile Condomino/a,\n\nsi trasmette in allegato il verbale dell''assemblea condominiale svoltasi in data {data_assemblea}.\n\nRestiamo a disposizione per eventuali chiarimenti.\n\nDistinti saluti,\nL''Amministratore', 'verbale'),
('Comunicazione generica', '{oggetto}', 'Gentile Condomino/a,\n\n{corpo_messaggio}\n\nDistinti saluti,\nL''Amministratore', 'generico');
