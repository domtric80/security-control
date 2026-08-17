CREATE TABLE IF NOT EXISTS ai_providers (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    nome            VARCHAR(150) NOT NULL,
    provider_type   ENUM('ollama','openai_compatible') NOT NULL DEFAULT 'ollama',
    base_url        VARCHAR(500) NOT NULL,
    api_key         TEXT NULL,
    default_model   VARCHAR(200) NOT NULL DEFAULT '',
    model_list      TEXT NULL,
    timeout_seconds INT NOT NULL DEFAULT 300,
    enabled         TINYINT(1) NOT NULL DEFAULT 1,
    is_default      TINYINT(1) NOT NULL DEFAULT 0,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_ai_providers_enabled (enabled, is_default)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO ai_providers (nome, provider_type, base_url, default_model, model_list, timeout_seconds, enabled, is_default)
SELECT 'Ollama locale', 'ollama', 'http://host.docker.internal:11434', '', '', 300, 1, 1
WHERE NOT EXISTS (SELECT 1 FROM ai_providers);

INSERT INTO rbac_funzioni (codice, nome, descrizione, ordine, attiva)
VALUES ('ai_settings', 'Configurazione IA', 'Configurazione provider IA e modelli', 906, 1)
ON DUPLICATE KEY UPDATE
    nome = VALUES(nome),
    descrizione = VALUES(descrizione),
    ordine = VALUES(ordine),
    attiva = VALUES(attiva);

INSERT INTO rbac_permessi (ruolo_id, funzione_id, can_create, can_read, can_update, can_delete)
SELECT r.id, f.id, 1, 1, 1, 1
FROM rbac_ruoli r
JOIN rbac_funzioni f ON f.codice = 'ai_settings'
WHERE r.codice = 'admin'
ON DUPLICATE KEY UPDATE
    can_create = 1,
    can_read = 1,
    can_update = 1,
    can_delete = 1;

INSERT INTO rbac_permessi (ruolo_id, funzione_id, can_create, can_read, can_update, can_delete)
SELECT r.id, f.id, 0, 1, 0, 0
FROM rbac_ruoli r
JOIN rbac_funzioni f ON f.codice = 'ai_settings'
WHERE r.codice = 'manager'
ON DUPLICATE KEY UPDATE
    can_read = 1;
