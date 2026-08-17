CREATE TABLE IF NOT EXISTS rbac_funzioni (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    codice      VARCHAR(100) NOT NULL UNIQUE,
    nome        VARCHAR(150) NOT NULL,
    descrizione TEXT,
    ordine      INT NOT NULL DEFAULT 0,
    attiva      TINYINT(1) NOT NULL DEFAULT 1,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rbac_ruoli (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    codice      VARCHAR(100) NOT NULL UNIQUE,
    nome        VARCHAR(150) NOT NULL,
    descrizione TEXT,
    sistema     TINYINT(1) NOT NULL DEFAULT 0,
    attivo      TINYINT(1) NOT NULL DEFAULT 1,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rbac_permessi (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    ruolo_id    INT NOT NULL,
    funzione_id INT NOT NULL,
    can_create  TINYINT(1) NOT NULL DEFAULT 0,
    can_read    TINYINT(1) NOT NULL DEFAULT 0,
    can_update  TINYINT(1) NOT NULL DEFAULT 0,
    can_delete  TINYINT(1) NOT NULL DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_rbac_permesso (ruolo_id, funzione_id),
    FOREIGN KEY (ruolo_id) REFERENCES rbac_ruoli(id) ON DELETE CASCADE,
    FOREIGN KEY (funzione_id) REFERENCES rbac_funzioni(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS utente_ruoli (
    utente_id INT NOT NULL,
    ruolo_id  INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (utente_id, ruolo_id),
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE,
    FOREIGN KEY (ruolo_id) REFERENCES rbac_ruoli(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO rbac_funzioni (codice, nome, descrizione, ordine, attiva) VALUES
('dashboard', 'Dashboard', 'Accesso alla dashboard iniziale', 10, 1),
('questionari', 'Questionari', 'Creazione, compilazione e gestione questionari', 20, 1),
('risultati', 'Risultati requisiti', 'Lettura e revisione dei requisiti prodotti', 30, 1),
('pir', 'PIR', 'Post Implementation Review', 40, 1),
('domande', 'Domande', 'Anagrafica domande questionario', 100, 1),
('requisiti', 'Requisiti catalogo', 'Catalogo requisiti di sicurezza', 110, 1),
('requisiti_specifici', 'Requisiti specifici', 'Requisiti specifici di progetto', 120, 1),
('servizi', 'Servizi', 'Catalogo servizi', 130, 1),
('regole_requisiti', 'Regole requisiti', 'Regole di assegnazione requisiti', 140, 1),
('regole_servizi', 'Regole servizi', 'Regole di assegnazione servizi', 150, 1),
('business_lines', 'Business line', 'Anagrafica business line', 160, 1),
('requisito_categorie', 'Categorie requisiti', 'Tassonomia categorie requisiti', 170, 1),
('utenti', 'Utenti', 'Anagrafica utenti', 900, 1),
('auth_settings', 'Autenticazione', 'Configurazione LDAP e OIDC', 905, 1),
('ruoli_permessi', 'Ruoli e permessi', 'RBAC e permessi CRUD', 910, 1)
ON DUPLICATE KEY UPDATE
    nome = VALUES(nome),
    descrizione = VALUES(descrizione),
    ordine = VALUES(ordine),
    attiva = VALUES(attiva);

INSERT INTO rbac_ruoli (codice, nome, descrizione, sistema, attivo) VALUES
('utente', 'Utente', 'Compila questionari e legge requisiti e PIR.', 1, 1),
('analista', 'Analista', 'Compila questionari, produce requisiti e gestisce PIR.', 1, 1),
('manager', 'Manager', 'Tutte le funzionalità tranne utenti, ruoli e permessi.', 1, 1),
('admin', 'Admin', 'Tutte le funzionalità.', 1, 1)
ON DUPLICATE KEY UPDATE
    nome = VALUES(nome),
    descrizione = VALUES(descrizione),
    sistema = 1,
    attivo = 1;

INSERT INTO rbac_permessi (ruolo_id, funzione_id, can_create, can_read, can_update, can_delete)
SELECT r.id, f.id,
       CASE WHEN f.codice IN ('questionari') THEN 1 ELSE 0 END,
       CASE WHEN f.codice IN ('dashboard','questionari','risultati','pir') THEN 1 ELSE 0 END,
       CASE WHEN f.codice IN ('questionari') THEN 1 ELSE 0 END,
       0
FROM rbac_ruoli r
JOIN rbac_funzioni f
WHERE r.codice = 'utente'
ON DUPLICATE KEY UPDATE
    can_create = VALUES(can_create),
    can_read = VALUES(can_read),
    can_update = VALUES(can_update),
    can_delete = VALUES(can_delete);

INSERT INTO rbac_permessi (ruolo_id, funzione_id, can_create, can_read, can_update, can_delete)
SELECT r.id, f.id,
       CASE WHEN f.codice IN ('questionari','risultati','pir','requisiti_specifici') THEN 1 ELSE 0 END,
       CASE WHEN f.codice IN ('dashboard','questionari','risultati','pir','requisiti_specifici') THEN 1 ELSE 0 END,
       CASE WHEN f.codice IN ('questionari','risultati','pir','requisiti_specifici') THEN 1 ELSE 0 END,
       CASE WHEN f.codice IN ('questionari','requisiti_specifici') THEN 1 ELSE 0 END
FROM rbac_ruoli r
JOIN rbac_funzioni f
WHERE r.codice = 'analista'
ON DUPLICATE KEY UPDATE
    can_create = VALUES(can_create),
    can_read = VALUES(can_read),
    can_update = VALUES(can_update),
    can_delete = VALUES(can_delete);

INSERT INTO rbac_permessi (ruolo_id, funzione_id, can_create, can_read, can_update, can_delete)
SELECT r.id, f.id,
       CASE WHEN f.codice NOT IN ('utenti','ruoli_permessi') THEN 1 ELSE 0 END,
       CASE WHEN f.codice NOT IN ('utenti','ruoli_permessi') THEN 1 ELSE 0 END,
       CASE WHEN f.codice NOT IN ('utenti','ruoli_permessi') THEN 1 ELSE 0 END,
       CASE WHEN f.codice NOT IN ('utenti','ruoli_permessi') THEN 1 ELSE 0 END
FROM rbac_ruoli r
JOIN rbac_funzioni f
WHERE r.codice = 'manager'
ON DUPLICATE KEY UPDATE
    can_create = VALUES(can_create),
    can_read = VALUES(can_read),
    can_update = VALUES(can_update),
    can_delete = VALUES(can_delete);

INSERT INTO rbac_permessi (ruolo_id, funzione_id, can_create, can_read, can_update, can_delete)
SELECT r.id, f.id, 1, 1, 1, 1
FROM rbac_ruoli r
JOIN rbac_funzioni f
WHERE r.codice = 'admin'
ON DUPLICATE KEY UPDATE
    can_create = 1,
    can_read = 1,
    can_update = 1,
    can_delete = 1;

INSERT IGNORE INTO utente_ruoli (utente_id, ruolo_id)
SELECT u.id, r.id
FROM utenti u
JOIN rbac_ruoli r ON r.codice = 'admin'
WHERE u.is_admin = 1;

INSERT IGNORE INTO utente_ruoli (utente_id, ruolo_id)
SELECT u.id, r.id
FROM utenti u
JOIN rbac_ruoli r ON r.codice = 'utente'
WHERE u.is_admin = 0
  AND NOT EXISTS (SELECT 1 FROM utente_ruoli ur WHERE ur.utente_id = u.id);
