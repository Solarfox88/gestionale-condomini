-- Schema SQL definitivo progressivo per Gestionale Condomini
-- Compatibile con MySQL/MariaDB su hosting cPanel/SupportHost.
-- Include base anagrafica, documentale, contabile, ticket, assemblee e audit.

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NULL,
    fiscal_code VARCHAR(32) NULL,
    role ENUM('admin','condomino','fornitore') NOT NULL DEFAULT 'condomino',
    status ENUM('pending','active','inactive') NOT NULL DEFAULT 'pending',
    last_login_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS condomini (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(190) NOT NULL,
    codice_fiscale VARCHAR(32) NULL,
    indirizzo VARCHAR(255) NULL,
    comune VARCHAR(120) NULL,
    provincia VARCHAR(10) NULL,
    cap VARCHAR(10) NULL,
    iban VARCHAR(34) NULL,
    banca VARCHAR(190) NULL,
    email VARCHAR(190) NULL,
    pec VARCHAR(190) NULL,
    note TEXT NULL,
    status ENUM('active','inactive','archived') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS unita_immobiliari (
    id INT AUTO_INCREMENT PRIMARY KEY,
    condominio_id INT NOT NULL,
    scala VARCHAR(50) NULL,
    piano VARCHAR(50) NULL,
    interno VARCHAR(50) NULL,
    subalterno VARCHAR(50) NULL,
    descrizione VARCHAR(255) NULL,
    mq DECIMAL(10,2) NULL,
    millesimi_proprieta DECIMAL(10,4) NOT NULL DEFAULT 0,
    millesimi_scale DECIMAL(10,4) NOT NULL DEFAULT 0,
    millesimi_ascensore DECIMAL(10,4) NOT NULL DEFAULT 0,
    millesimi_riscaldamento DECIMAL(10,4) NOT NULL DEFAULT 0,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (condominio_id) REFERENCES condomini(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS persone (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    cognome VARCHAR(150) NOT NULL,
    ragione_sociale VARCHAR(190) NULL,
    tipo ENUM('persona','azienda','fornitore') NOT NULL DEFAULT 'persona',
    codice_fiscale VARCHAR(32) NULL,
    partita_iva VARCHAR(32) NULL,
    email VARCHAR(190) NULL,
    pec VARCHAR(190) NULL,
    telefono VARCHAR(50) NULL,
    indirizzo VARCHAR(255) NULL,
    note TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS unita_persone (
    id INT AUTO_INCREMENT PRIMARY KEY,
    unita_id INT NOT NULL,
    persona_id INT NOT NULL,
    user_id INT NULL,
    ruolo ENUM('proprietario','comproprietario','inquilino','usufruttuario','delegato','altro') NOT NULL DEFAULT 'proprietario',
    percentuale DECIMAL(5,2) NOT NULL DEFAULT 100,
    data_inizio DATE NULL,
    data_fine DATE NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (unita_id) REFERENCES unita_immobiliari(id) ON DELETE CASCADE,
    FOREIGN KEY (persona_id) REFERENCES persone(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS condomini_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    condominio_id INT NOT NULL,
    unita_id INT NULL,
    relation_type VARCHAR(50) NOT NULL DEFAULT 'proprietario',
    approved_by INT NULL,
    approved_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (condominio_id) REFERENCES condomini(id) ON DELETE CASCADE,
    FOREIGN KEY (unita_id) REFERENCES unita_immobiliari(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS esercizi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    condominio_id INT NOT NULL,
    nome VARCHAR(150) NOT NULL,
    data_inizio DATE NOT NULL,
    data_fine DATE NOT NULL,
    stato ENUM('bozza','aperto','chiuso') NOT NULL DEFAULT 'bozza',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (condominio_id) REFERENCES condomini(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS categorie_spesa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    descrizione TEXT NULL,
    tipo_default ENUM('entrata','uscita','entrambi') NOT NULL DEFAULT 'uscita',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS movimenti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    esercizio_id INT NOT NULL,
    condominio_id INT NOT NULL,
    unita_id INT NULL,
    persona_id INT NULL,
    categoria_id INT NULL,
    tipo ENUM('entrata','uscita') NOT NULL,
    descrizione TEXT NULL,
    importo DECIMAL(10,2) NOT NULL,
    data_movimento DATE NOT NULL,
    metodo_pagamento VARCHAR(80) NULL,
    riferimento VARCHAR(190) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (esercizio_id) REFERENCES esercizi(id) ON DELETE CASCADE,
    FOREIGN KEY (condominio_id) REFERENCES condomini(id) ON DELETE CASCADE,
    FOREIGN KEY (unita_id) REFERENCES unita_immobiliari(id) ON DELETE SET NULL,
    FOREIGN KEY (persona_id) REFERENCES persone(id) ON DELETE SET NULL,
    FOREIGN KEY (categoria_id) REFERENCES categorie_spesa(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rate (
    id INT AUTO_INCREMENT PRIMARY KEY,
    esercizio_id INT NOT NULL,
    condominio_id INT NOT NULL,
    unita_id INT NOT NULL,
    descrizione VARCHAR(190) NOT NULL,
    importo DECIMAL(10,2) NOT NULL,
    scadenza DATE NOT NULL,
    stato ENUM('da_pagare','parziale','pagata','scaduta') NOT NULL DEFAULT 'da_pagare',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (esercizio_id) REFERENCES esercizi(id) ON DELETE CASCADE,
    FOREIGN KEY (condominio_id) REFERENCES condomini(id) ON DELETE CASCADE,
    FOREIGN KEY (unita_id) REFERENCES unita_immobiliari(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pagamenti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rata_id INT NOT NULL,
    persona_id INT NULL,
    data_pagamento DATE NOT NULL,
    importo DECIMAL(10,2) NOT NULL,
    metodo VARCHAR(80) NULL,
    riferimento VARCHAR(190) NULL,
    note TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rata_id) REFERENCES rate(id) ON DELETE CASCADE,
    FOREIGN KEY (persona_id) REFERENCES persone(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS documenti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    condominio_id INT NOT NULL,
    unita_id INT NULL,
    esercizio_id INT NULL,
    titolo VARCHAR(190) NOT NULL,
    categoria VARCHAR(100) NOT NULL,
    descrizione TEXT NULL,
    file_path VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NULL,
    mime_type VARCHAR(120) NULL,
    visibility ENUM('pubblico','condominio','unita','privato') NOT NULL DEFAULT 'condominio',
    uploaded_by INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (condominio_id) REFERENCES condomini(id) ON DELETE CASCADE,
    FOREIGN KEY (unita_id) REFERENCES unita_immobiliari(id) ON DELETE SET NULL,
    FOREIGN KEY (esercizio_id) REFERENCES esercizi(id) ON DELETE SET NULL,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ticket (
    id INT AUTO_INCREMENT PRIMARY KEY,
    condominio_id INT NOT NULL,
    unita_id INT NULL,
    aperto_da INT NOT NULL,
    assegnato_a INT NULL,
    titolo VARCHAR(190) NOT NULL,
    categoria VARCHAR(100) NULL,
    descrizione TEXT NOT NULL,
    priorita ENUM('bassa','media','alta','urgente') NOT NULL DEFAULT 'media',
    stato ENUM('aperto','preso_in_carico','in_attesa','in_lavorazione','risolto','chiuso','respinto') NOT NULL DEFAULT 'aperto',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (condominio_id) REFERENCES condomini(id) ON DELETE CASCADE,
    FOREIGN KEY (unita_id) REFERENCES unita_immobiliari(id) ON DELETE SET NULL,
    FOREIGN KEY (aperto_da) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assegnato_a) REFERENCES persone(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ticket_messaggi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    user_id INT NOT NULL,
    messaggio TEXT NOT NULL,
    interno TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES ticket(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS assemblee (
    id INT AUTO_INCREMENT PRIMARY KEY,
    condominio_id INT NOT NULL,
    titolo VARCHAR(190) NOT NULL,
    data_prima_convocazione DATETIME NULL,
    data_seconda_convocazione DATETIME NOT NULL,
    luogo VARCHAR(255) NULL,
    ordine_giorno TEXT NOT NULL,
    verbale TEXT NULL,
    stato ENUM('bozza','convocata','svolta','annullata') NOT NULL DEFAULT 'bozza',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (condominio_id) REFERENCES condomini(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS assemblee_presenze (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assemblea_id INT NOT NULL,
    persona_id INT NOT NULL,
    unita_id INT NULL,
    presente TINYINT(1) NOT NULL DEFAULT 0,
    delegato_da INT NULL,
    millesimi_presenti DECIMAL(10,4) NOT NULL DEFAULT 0,
    FOREIGN KEY (assemblea_id) REFERENCES assemblee(id) ON DELETE CASCADE,
    FOREIGN KEY (persona_id) REFERENCES persone(id) ON DELETE CASCADE,
    FOREIGN KEY (unita_id) REFERENCES unita_immobiliari(id) ON DELETE SET NULL,
    FOREIGN KEY (delegato_da) REFERENCES persone(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS riparti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    condominio_id INT NOT NULL,
    esercizio_id INT NOT NULL,
    descrizione VARCHAR(255) NOT NULL,
    tipo_millesimi ENUM('proprieta','scale','ascensore','riscaldamento','personalizzato') NOT NULL DEFAULT 'proprieta',
    importo_totale DECIMAL(12,2) NOT NULL,
    tipo_spesa ENUM('ordinaria','straordinaria') NOT NULL DEFAULT 'ordinaria',
    stato ENUM('bozza','calcolato','approvato','rate_generate') NOT NULL DEFAULT 'bozza',
    num_rate TINYINT NOT NULL DEFAULT 1,
    note TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (condominio_id) REFERENCES condomini(id) ON DELETE CASCADE,
    FOREIGN KEY (esercizio_id) REFERENCES esercizi(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS riparti_dettaglio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    riparto_id INT NOT NULL,
    unita_id INT NOT NULL,
    millesimi DECIMAL(10,4) NOT NULL DEFAULT 0,
    importo DECIMAL(12,2) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (riparto_id) REFERENCES riparti(id) ON DELETE CASCADE,
    FOREIGN KEY (unita_id) REFERENCES unita_immobiliari(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS preventivi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    esercizio_id INT NOT NULL,
    condominio_id INT NOT NULL,
    titolo VARCHAR(190) NOT NULL,
    stato ENUM('bozza','approvato') NOT NULL DEFAULT 'bozza',
    note TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (esercizio_id) REFERENCES esercizi(id) ON DELETE CASCADE,
    FOREIGN KEY (condominio_id) REFERENCES condomini(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS preventivo_voci (
    id INT AUTO_INCREMENT PRIMARY KEY,
    preventivo_id INT NOT NULL,
    categoria_id INT NULL,
    descrizione VARCHAR(255) NOT NULL,
    tipo ENUM('entrata','uscita') NOT NULL DEFAULT 'uscita',
    importo_previsto DECIMAL(12,2) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (preventivo_id) REFERENCES preventivi(id) ON DELETE CASCADE,
    FOREIGN KEY (categoria_id) REFERENCES categorie_spesa(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS conguagli (
    id INT AUTO_INCREMENT PRIMARY KEY,
    esercizio_id INT NOT NULL,
    condominio_id INT NOT NULL,
    unita_id INT NOT NULL,
    importo_previsto DECIMAL(12,2) NOT NULL DEFAULT 0,
    importo_consuntivo DECIMAL(12,2) NOT NULL DEFAULT 0,
    importo_conguaglio DECIMAL(12,2) NOT NULL DEFAULT 0,
    rata_id INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (esercizio_id) REFERENCES esercizi(id) ON DELETE CASCADE,
    FOREIGN KEY (condominio_id) REFERENCES condomini(id) ON DELETE CASCADE,
    FOREIGN KEY (unita_id) REFERENCES unita_immobiliari(id) ON DELETE CASCADE,
    FOREIGN KEY (rata_id) REFERENCES rate(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(100) NULL,
    entity_id INT NULL,
    ip_address VARCHAR(64) NULL,
    user_agent TEXT NULL,
    details TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
