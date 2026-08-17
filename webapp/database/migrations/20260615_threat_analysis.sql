CREATE TABLE IF NOT EXISTS threat_analyses (
    id                   INT AUTO_INCREMENT PRIMARY KEY,
    questionario_id      INT NOT NULL,
    created_by_user_id   INT NULL,
    model_name           VARCHAR(200) NOT NULL DEFAULT '',
    ollama_base_url      VARCHAR(500) NOT NULL DEFAULT '',
    user_prompt          MEDIUMTEXT NOT NULL,
    request_context_json JSON NOT NULL,
    response_text        MEDIUMTEXT NOT NULL,
    status               ENUM('ok','error') NOT NULL DEFAULT 'ok',
    error_message        TEXT,
    created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (questionario_id) REFERENCES questionari(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by_user_id) REFERENCES utenti(id) ON DELETE SET NULL,
    KEY idx_threat_analyses_questionario (questionario_id, created_at),
    KEY idx_threat_analyses_user (created_by_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO rbac_funzioni (codice, nome, descrizione, ordine, attiva)
VALUES ('threat_analysis', 'Threat Analysis', 'Analisi minacce tramite AI/Ollama sui questionari', 50, 1)
ON DUPLICATE KEY UPDATE
    nome = VALUES(nome),
    descrizione = VALUES(descrizione),
    ordine = VALUES(ordine),
    attiva = VALUES(attiva);

INSERT INTO rbac_permessi (ruolo_id, funzione_id, can_create, can_read, can_update, can_delete)
SELECT r.id, f.id,
       0,
       1,
       0,
       0
FROM rbac_ruoli r
JOIN rbac_funzioni f ON f.codice = 'threat_analysis'
WHERE r.codice = 'utente'
ON DUPLICATE KEY UPDATE
    can_create = VALUES(can_create),
    can_read = VALUES(can_read),
    can_update = VALUES(can_update),
    can_delete = VALUES(can_delete);

INSERT INTO rbac_permessi (ruolo_id, funzione_id, can_create, can_read, can_update, can_delete)
SELECT r.id, f.id,
       1,
       1,
       1,
       0
FROM rbac_ruoli r
JOIN rbac_funzioni f ON f.codice = 'threat_analysis'
WHERE r.codice = 'analista'
ON DUPLICATE KEY UPDATE
    can_create = VALUES(can_create),
    can_read = VALUES(can_read),
    can_update = VALUES(can_update),
    can_delete = VALUES(can_delete);

INSERT INTO rbac_permessi (ruolo_id, funzione_id, can_create, can_read, can_update, can_delete)
SELECT r.id, f.id, 1, 1, 1, 1
FROM rbac_ruoli r
JOIN rbac_funzioni f ON f.codice = 'threat_analysis'
WHERE r.codice IN ('manager', 'admin')
ON DUPLICATE KEY UPDATE
    can_create = 1,
    can_read = 1,
    can_update = 1,
    can_delete = 1;
