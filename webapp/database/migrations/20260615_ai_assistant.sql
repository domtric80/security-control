CREATE TABLE IF NOT EXISTS ai_analysis_runs (
    id                 INT AUTO_INCREMENT PRIMARY KEY,
    questionario_id    INT NOT NULL,
    provider_id        INT NULL,
    created_by_user_id INT NULL,
    analysis_type      VARCHAR(80) NOT NULL,
    model_name         VARCHAR(200) NOT NULL DEFAULT '',
    prompt_text        MEDIUMTEXT NOT NULL,
    context_json       JSON NOT NULL,
    response_text      MEDIUMTEXT NOT NULL,
    parsed_json        JSON NULL,
    status             ENUM('ok','error') NOT NULL DEFAULT 'ok',
    error_message      TEXT,
    duration_ms        INT NOT NULL DEFAULT 0,
    created_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (questionario_id) REFERENCES questionari(id) ON DELETE CASCADE,
    FOREIGN KEY (provider_id) REFERENCES ai_providers(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by_user_id) REFERENCES utenti(id) ON DELETE SET NULL,
    KEY idx_ai_runs_questionario (questionario_id, analysis_type, created_at),
    KEY idx_ai_runs_status (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ai_suggestions (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    run_id              INT NOT NULL,
    questionario_id     INT NOT NULL,
    suggestion_type     VARCHAR(80) NOT NULL,
    title               VARCHAR(500) NOT NULL DEFAULT '',
    body                MEDIUMTEXT,
    priority            VARCHAR(30) NOT NULL DEFAULT '',
    confidence          DECIMAL(5,2) NULL,
    rationale           TEXT,
    payload_json        JSON NOT NULL,
    status              ENUM('proposto','approvato','scartato','applicato') NOT NULL DEFAULT 'proposto',
    decision_note       TEXT,
    decided_by_user_id  INT NULL,
    decided_at          TIMESTAMP NULL,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (run_id) REFERENCES ai_analysis_runs(id) ON DELETE CASCADE,
    FOREIGN KEY (questionario_id) REFERENCES questionari(id) ON DELETE CASCADE,
    FOREIGN KEY (decided_by_user_id) REFERENCES utenti(id) ON DELETE SET NULL,
    KEY idx_ai_suggestions_questionario (questionario_id, suggestion_type, status),
    KEY idx_ai_suggestions_run (run_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO rbac_funzioni (codice, nome, descrizione, ordine, attiva)
VALUES ('ai_assistant', 'Assistente IA', 'Suggerimenti IA su questionari, requisiti, servizi e PIR', 55, 1)
ON DUPLICATE KEY UPDATE
    nome = VALUES(nome),
    descrizione = VALUES(descrizione),
    ordine = VALUES(ordine),
    attiva = VALUES(attiva);

INSERT INTO rbac_permessi (ruolo_id, funzione_id, can_create, can_read, can_update, can_delete)
SELECT r.id, f.id, 0, 1, 0, 0
FROM rbac_ruoli r
JOIN rbac_funzioni f ON f.codice = 'ai_assistant'
WHERE r.codice = 'utente'
ON DUPLICATE KEY UPDATE can_read = 1;

INSERT INTO rbac_permessi (ruolo_id, funzione_id, can_create, can_read, can_update, can_delete)
SELECT r.id, f.id, 1, 1, 1, 0
FROM rbac_ruoli r
JOIN rbac_funzioni f ON f.codice = 'ai_assistant'
WHERE r.codice = 'analista'
ON DUPLICATE KEY UPDATE can_create = 1, can_read = 1, can_update = 1;

INSERT INTO rbac_permessi (ruolo_id, funzione_id, can_create, can_read, can_update, can_delete)
SELECT r.id, f.id, 1, 1, 1, 1
FROM rbac_ruoli r
JOIN rbac_funzioni f ON f.codice = 'ai_assistant'
WHERE r.codice IN ('manager', 'admin')
ON DUPLICATE KEY UPDATE can_create = 1, can_read = 1, can_update = 1, can_delete = 1;
