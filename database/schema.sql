-- Schema SQL per Condomini Gestionale

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NULL,
    fiscal_code VARCHAR(32) NULL,
    role VARCHAR(50) NOT NULL DEFAULT 'condomino',
    status VARCHAR(30) NOT NULL DEFAULT 'pending',
    last_login_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
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
    status VARCHAR(30) NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
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
    status VARCHAR(30) NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    FOREIGN KEY (condominio_id) REFERENCES condomini(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS condomini_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    condominio_id INT NOT NULL,
    unita_id INT NULL,
    relation_type VARCHAR(50) NOT NULL DEFAULT 'proprietario',
    approved_by INT NULL,
    approved_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (condominio_id) REFERENCES condomini(id) ON DELETE CASCADE,
    FOREIGN KEY (unita_id) REFERENCES unita_immobiliari(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS documenti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    condominio_id INT NOT NULL,
    unita_id INT NULL,
    titolo VARCHAR(190) NOT NULL,
    categoria VARCHAR(100) NOT NULL,
    descrizione TEXT NULL,
    file_path VARCHAR(255) NOT NULL,
    visibility VARCHAR(50) NOT NULL DEFAULT 'condominio',
    uploaded_by INT NOT NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (condominio_id) REFERENCES condomini(id) ON DELETE CASCADE,
    FOREIGN KEY (unita_id) REFERENCES unita_immobiliari(id) ON DELETE SET NULL,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
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
    created_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
