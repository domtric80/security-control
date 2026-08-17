-- ============================================================
-- Schema: Security Control
-- Compatible with MySQL 8+ and PostgreSQL 14+
-- ============================================================

CREATE TABLE IF NOT EXISTS domande (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    codice      VARCHAR(80)  NOT NULL UNIQUE,
    categoria   VARCHAR(150) NOT NULL DEFAULT '',
    testo       TEXT         NOT NULL,
    tipo        ENUM('bool','text','select','multi') NOT NULL DEFAULT 'bool',
    obbligatoria TINYINT     NOT NULL DEFAULT 1,
    ordine      INT          NOT NULL DEFAULT 0,
    attiva      TINYINT      NOT NULL DEFAULT 1,
    created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS opzioni_risposta (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    domanda_id  INT          NOT NULL,
    testo       VARCHAR(255) NOT NULL,
    valore      VARCHAR(100) NOT NULL,
    ordine      INT          NOT NULL DEFAULT 0,
    FOREIGN KEY (domanda_id) REFERENCES domande(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS requisiti (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    codice              VARCHAR(50)  NOT NULL UNIQUE,
    versione            VARCHAR(10)  NOT NULL DEFAULT '',
    categoria           VARCHAR(250) NOT NULL DEFAULT '',
    sottocategoria      TEXT,
    titolo              VARCHAR(500) NOT NULL DEFAULT '',
    descrizione         TEXT,
    contesto            TEXT,
    note                TEXT,
    importanza          VARCHAR(20)  NOT NULL DEFAULT '',
    std                 VARCHAR(250),
    standard            TINYINT      NOT NULL DEFAULT 0,
    standard_dove       TEXT,
    owner               VARCHAR(250),
    fase                VARCHAR(100),
    framework_function  VARCHAR(250),
    funzionale_tecnologico VARCHAR(100),
    data_protection     VARCHAR(100),
    rif_iso             TEXT,
    rif_fncs            VARCHAR(250),
    software_selection  VARCHAR(250),
    riferimento_hld     TEXT,
    pubblicato_lga      VARCHAR(100),
    rif_std_config_dc   TEXT,
    standardizzazione_controllo_task TEXT,
    rif_procedura_controllo TEXT,
    ultimo_update       VARCHAR(100),
    catalogo_source     VARCHAR(255),
    appl_dc_ingegneria  VARCHAR(10),
    appl_dc_change      VARCHAR(10),
    appl_dc_run         VARCHAR(10),
    appl_sviluppo       VARCHAR(10),
    regole_operatore_logico ENUM('OR','AND') NOT NULL DEFAULT 'OR',
    attivo              TINYINT      NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS catalogo_allegati (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    source_file VARCHAR(255) NOT NULL,
    filename    VARCHAR(255) NOT NULL,
    mime_type   VARCHAR(150),
    path        VARCHAR(500) NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_catalogo_allegato (source_file, filename)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS requisito_categorie (
    id                 INT AUTO_INCREMENT PRIMARY KEY,
    nome               VARCHAR(250) NOT NULL UNIQUE,
    framework_function VARCHAR(250),
    rif_fncs           VARCHAR(250),
    attiva             TINYINT NOT NULL DEFAULT 1,
    created_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS requisito_categoria (
    requisito_id INT NOT NULL,
    categoria_id INT NOT NULL,
    PRIMARY KEY (requisito_id, categoria_id),
    FOREIGN KEY (requisito_id) REFERENCES requisiti(id) ON DELETE CASCADE,
    FOREIGN KEY (categoria_id) REFERENCES requisito_categorie(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS servizi (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    reparto_owner       VARCHAR(200),
    tipo_canone_ci      VARCHAR(50),
    portfolio_category  VARCHAR(200),
    macro_service       VARCHAR(200),
    categoria           VARCHAR(200),
    servizio_elementare VARCHAR(500),
    descrizione         TEXT,
    tipo_attivita       VARCHAR(150),
    misurabilita        VARCHAR(50),
    commessa            VARCHAR(200),
    check_component     VARCHAR(200),
    asset_primario      VARCHAR(200),
    software            TEXT,
    orario_servizio     VARCHAR(100),
    note                TEXT,
    regole_operatore_logico ENUM('OR','AND') NOT NULL DEFAULT 'OR',
    attivo              TINYINT      NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS business_lines (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nome        VARCHAR(200) NOT NULL UNIQUE,
    ordine      INT          NOT NULL DEFAULT 0,
    attiva      TINYINT      NOT NULL DEFAULT 1,
    created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS questionari (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    nome_progetto   VARCHAR(500),
    codice_progetto      VARCHAR(100),
    nome_servizio   VARCHAR(500),
    business_line   VARCHAR(200),
    pm              VARCHAR(200),
    pm_product_manager VARCHAR(200),
    po              VARCHAR(200),
    tpo             VARCHAR(200),
    tipologia_progetto VARCHAR(100),
    task_jira       VARCHAR(200),
    analista_questionario_id INT NULL,
    pir_analista_id INT NULL,
    pir_stato       ENUM('in_corso','completata') NOT NULL DEFAULT 'in_corso',
    descrizione     TEXT,
    note            TEXT,
    stato           ENUM('bozza','completato','archiviato') NOT NULL DEFAULT 'bozza',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS questionario_risposte (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    questionario_id INT NULL,
    domanda_id      INT NOT NULL,
    valore          TEXT,
    note            TEXT,
    FOREIGN KEY (questionario_id) REFERENCES questionari(id) ON DELETE CASCADE,
    FOREIGN KEY (domanda_id)      REFERENCES domande(id),
    UNIQUE KEY uq_qr (questionario_id, domanda_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS regole_requisiti (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    gruppo_id       INT NULL,
    domanda_id      INT          NOT NULL,
    valore_atteso   VARCHAR(200) NOT NULL DEFAULT '1',
    operatore_logico ENUM('OR','AND') NOT NULL DEFAULT 'OR',
    requisito_id    INT          NOT NULL,
    FOREIGN KEY (domanda_id)   REFERENCES domande(id)   ON DELETE CASCADE,
    FOREIGN KEY (requisito_id) REFERENCES requisiti(id) ON DELETE CASCADE,
    UNIQUE KEY uq_rr (gruppo_id, domanda_id, valore_atteso, requisito_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS regole_gruppi (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nome        VARCHAR(250) NOT NULL UNIQUE,
    descrizione TEXT,
    attivo      TINYINT NOT NULL DEFAULT 1,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS regole_requisiti_gruppi (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    gruppo_logico_id INT NULL,
    requisito_id    INT NOT NULL,
    nome            VARCHAR(250) NOT NULL DEFAULT 'Default',
    operatore_logico ENUM('OR','AND') NOT NULL DEFAULT 'OR',
    ordine          INT NOT NULL DEFAULT 0,
    attivo          TINYINT NOT NULL DEFAULT 1,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (gruppo_logico_id) REFERENCES regole_gruppi(id) ON DELETE SET NULL,
    FOREIGN KEY (requisito_id) REFERENCES requisiti(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS regole_servizi (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    gruppo_id       INT NULL,
    domanda_id      INT          NOT NULL,
    valore_atteso   VARCHAR(200) NOT NULL DEFAULT '1',
    operatore_logico ENUM('OR','AND') NOT NULL DEFAULT 'OR',
    servizio_id     INT          NOT NULL,
    FOREIGN KEY (domanda_id) REFERENCES domande(id)  ON DELETE CASCADE,
    FOREIGN KEY (servizio_id) REFERENCES servizi(id) ON DELETE CASCADE,
    UNIQUE KEY uq_rs (gruppo_id, domanda_id, valore_atteso, servizio_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS regole_servizi_gruppi (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    gruppo_logico_id INT NULL,
    servizio_id     INT NOT NULL,
    nome            VARCHAR(250) NOT NULL DEFAULT 'Default',
    operatore_logico ENUM('OR','AND') NOT NULL DEFAULT 'OR',
    ordine          INT NOT NULL DEFAULT 0,
    attivo          TINYINT NOT NULL DEFAULT 1,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (gruppo_logico_id) REFERENCES regole_gruppi(id) ON DELETE SET NULL,
    FOREIGN KEY (servizio_id) REFERENCES servizi(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS questionario_risultati_requisiti (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    questionario_id     INT NOT NULL,
    requisito_id        INT NOT NULL,
    applicabile         ENUM('si','no','da_valutare') NOT NULL DEFAULT 'no',
    valutazione_manuale TEXT,
    FOREIGN KEY (questionario_id) REFERENCES questionari(id) ON DELETE CASCADE,
    FOREIGN KEY (requisito_id)    REFERENCES requisiti(id),
    UNIQUE KEY uq_qrr (questionario_id, requisito_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS questionario_requisiti_calcoli (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    questionario_id     INT NOT NULL,
    created_by_user_id  INT NULL,
    risposte_hash       CHAR(64) NOT NULL DEFAULT '',
    note                VARCHAR(255) NOT NULL DEFAULT '',
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (questionario_id) REFERENCES questionari(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by_user_id) REFERENCES utenti(id) ON DELETE SET NULL,
    KEY idx_qrc_questionario (questionario_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS questionario_requisiti_snapshot (
    id                       INT AUTO_INCREMENT PRIMARY KEY,
    calcolo_id               INT NOT NULL,
    questionario_id          INT NOT NULL,
    requisito_id             INT NOT NULL,
    applicabile              ENUM('si','no','da_valutare') NOT NULL DEFAULT 'si',
    assegnazione_tipo        ENUM('catalogo','default_design') NOT NULL DEFAULT 'catalogo',
    requisito_version_id     INT NULL,
    requisito_version_no     INT NULL,
    requisito_versione_label VARCHAR(50) NOT NULL DEFAULT '',
    codice                   VARCHAR(50) NOT NULL DEFAULT '',
    titolo                   VARCHAR(500) NOT NULL DEFAULT '',
    categoria                VARCHAR(250) NOT NULL DEFAULT '',
    snapshot_json            JSON NOT NULL,
    correlations_json        JSON NOT NULL,
    created_at               TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (calcolo_id) REFERENCES questionario_requisiti_calcoli(id) ON DELETE CASCADE,
    FOREIGN KEY (questionario_id) REFERENCES questionari(id) ON DELETE CASCADE,
    FOREIGN KEY (requisito_id) REFERENCES requisiti(id),
    FOREIGN KEY (requisito_version_id) REFERENCES requisito_versioni(id) ON DELETE SET NULL,
    UNIQUE KEY uq_qrsnap (calcolo_id, requisito_id),
    KEY idx_qrsnap_questionario (questionario_id, requisito_id),
    KEY idx_qrsnap_requisito (requisito_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS questionario_risultati_servizi (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    questionario_id INT NOT NULL,
    servizio_id     INT NOT NULL,
    applicabile     TINYINT NOT NULL DEFAULT 0,
    manuale         TINYINT NOT NULL DEFAULT 0,
    note            TEXT NULL,
    FOREIGN KEY (questionario_id) REFERENCES questionari(id) ON DELETE CASCADE,
    FOREIGN KEY (servizio_id)     REFERENCES servizi(id),
    UNIQUE KEY uq_qrs (questionario_id, servizio_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS questionario_requisiti_override (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    questionario_id INT NOT NULL,
    requisito_id    INT NOT NULL,
    azione          ENUM('include','exclude') NOT NULL,
    note            TEXT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (questionario_id) REFERENCES questionari(id) ON DELETE CASCADE,
    FOREIGN KEY (requisito_id)    REFERENCES requisiti(id) ON DELETE CASCADE,
    UNIQUE KEY uq_qro (questionario_id, requisito_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS questionario_requisiti_specifici (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    questionario_id INT NOT NULL,
    task_jira       VARCHAR(200),
    codice          VARCHAR(80),
    versione        VARCHAR(20),
    categoria       VARCHAR(250),
    sottocategoria  TEXT,
    titolo          VARCHAR(500) NOT NULL,
    descrizione     TEXT,
    contesto        TEXT,
    note            TEXT,
    importanza      VARCHAR(20),
    std             VARCHAR(250),
    standard        TINYINT NOT NULL DEFAULT 0,
    standard_dove   TEXT,
    owner           VARCHAR(250),
    fase            VARCHAR(100),
    framework_function VARCHAR(250),
    funzionale_tecnologico VARCHAR(100),
    data_protection VARCHAR(100),
    rif_iso         TEXT,
    rif_fncs        VARCHAR(250),
    software_selection VARCHAR(250),
    riferimento_hld TEXT,
    pubblicato_lga  VARCHAR(100),
    rif_std_config_dc TEXT,
    standardizzazione_controllo_task TEXT,
    rif_procedura_controllo TEXT,
    ultimo_update   VARCHAR(100),
    attivo          TINYINT NOT NULL DEFAULT 1,
    requisito_catalogo_id INT NULL,
    promosso_at     TIMESTAMP NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (questionario_id) REFERENCES questionari(id) ON DELETE SET NULL,
    FOREIGN KEY (requisito_catalogo_id) REFERENCES requisiti(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS questionario_requisiti_specifici_link (
    questionario_id INT NOT NULL,
    requisito_specifico_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (questionario_id, requisito_specifico_id),
    FOREIGN KEY (questionario_id) REFERENCES questionari(id) ON DELETE CASCADE,
    FOREIGN KEY (requisito_specifico_id) REFERENCES questionario_requisiti_specifici(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS utenti (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    auth_provider   VARCHAR(50) NOT NULL DEFAULT 'local',
    external_id     VARCHAR(255) NULL,
    username        VARCHAR(100) NOT NULL UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,
    nome            VARCHAR(150) NOT NULL,
    cognome         VARCHAR(150) NOT NULL,
    email           VARCHAR(250),
    telefono        VARCHAR(100),
    reparto         VARCHAR(250),
    ruolo           VARCHAR(250),
    is_admin        TINYINT(1) NOT NULL DEFAULT 0,
    attivo          TINYINT(1) NOT NULL DEFAULT 1,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login_at   TIMESTAMP NULL,
    KEY idx_utenti_external_identity (auth_provider, external_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS requisito_specifico_categoria (
    requisito_specifico_id INT NOT NULL,
    categoria_id           INT NOT NULL,
    PRIMARY KEY (requisito_specifico_id, categoria_id),
    FOREIGN KEY (requisito_specifico_id) REFERENCES questionario_requisiti_specifici(id) ON DELETE CASCADE,
    FOREIGN KEY (categoria_id) REFERENCES requisito_categorie(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS requisito_versioni (
    id                 INT AUTO_INCREMENT PRIMARY KEY,
    entity_type        ENUM('catalogo','specifico') NOT NULL,
    entity_id          INT NOT NULL,
    version_no         INT NOT NULL,
    action             VARCHAR(50) NOT NULL DEFAULT 'snapshot',
    snapshot_json      JSON NOT NULL,
    correlations_json  JSON NOT NULL,
    changed_by_user_id INT NULL,
    changed_by_label   VARCHAR(300) NOT NULL DEFAULT '',
    changed_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_requisito_versione (entity_type, entity_id, version_no),
    KEY idx_requisito_versioni_entity (entity_type, entity_id, changed_at),
    KEY idx_requisito_versioni_user (changed_by_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pir_requirement_reviews (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    questionario_id     INT NOT NULL,
    requisito_tipo      ENUM('catalogo','default_design','specifico') NOT NULL,
    requisito_ref_id    INT NOT NULL,
    stato               ENUM('OK','KO','non_applicabile','parziale') NULL,
    note                TEXT,
    applicazione        TEXT,
    rientro_eccezione   TEXT,
    referente_tipo      ENUM('analista','partecipante') NULL,
    referente_user_id   INT NULL,
    referente_participant_id INT NULL,
    referente_nome      VARCHAR(300),
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (questionario_id) REFERENCES questionari(id) ON DELETE CASCADE,
    UNIQUE KEY uq_pir_req (questionario_id, requisito_tipo, requisito_ref_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pir_meetings (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    questionario_id INT NOT NULL,
    data_riunione   DATE NOT NULL,
    note            TEXT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (questionario_id) REFERENCES questionari(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pir_meeting_participants (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    meeting_id  INT NOT NULL,
    nome        VARCHAR(250) NOT NULL,
    ruolo       VARCHAR(250),
    reparto     VARCHAR(250),
    email       VARCHAR(250),
    telefono    VARCHAR(100),
    partecipato TINYINT(1) NOT NULL DEFAULT 1,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (meeting_id) REFERENCES pir_meetings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pir_meeting_attachments (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    meeting_id      INT NOT NULL,
    tipo            ENUM('file','link') NOT NULL,
    titolo          VARCHAR(500),
    url             TEXT,
    file_path       VARCHAR(700),
    original_name   VARCHAR(500),
    mime_type       VARCHAR(200),
    size_bytes      BIGINT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (meeting_id) REFERENCES pir_meetings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS security_exceptions (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    questionario_id     INT NOT NULL,
    source              ENUM('pir','manuale') NOT NULL DEFAULT 'pir',
    requisito_tipo      ENUM('catalogo','default_design','specifico','manuale') NOT NULL DEFAULT 'manuale',
    requisito_ref_id    INT NULL,
    pir_review_id       INT NULL,
    codice              VARCHAR(120),
    titolo              VARCHAR(500) NOT NULL,
    categoria           VARCHAR(250),
    motivo              TEXT,
    data_rientro        DATE NULL,
    approvato_da        VARCHAR(300),
    stato               ENUM('aperta','rientrata','annullata') NOT NULL DEFAULT 'aperta',
    note                TEXT,
    created_by_user_id  INT NULL,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (questionario_id) REFERENCES questionari(id) ON DELETE CASCADE,
    FOREIGN KEY (pir_review_id) REFERENCES pir_requirement_reviews(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by_user_id) REFERENCES utenti(id) ON DELETE SET NULL,
    UNIQUE KEY uq_security_exception_pir (questionario_id, requisito_tipo, requisito_ref_id),
    KEY idx_security_exceptions_due (data_rientro, stato),
    KEY idx_security_exceptions_project (questionario_id, stato)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
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

CREATE TABLE IF NOT EXISTS auth_login_attempts (
    id          BIGINT AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(190) NOT NULL,
    ip_address  VARCHAR(45) NOT NULL,
    success     TINYINT(1) NOT NULL DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_auth_attempts_lookup (username, ip_address, success, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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

CREATE TABLE IF NOT EXISTS threat_analysis_sections (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    analysis_id     INT NOT NULL,
    section_order   INT NOT NULL DEFAULT 0,
    section_number  VARCHAR(20) NOT NULL DEFAULT '',
    title           VARCHAR(500) NOT NULL DEFAULT '',
    content_html    MEDIUMTEXT NOT NULL,
    content_text    MEDIUMTEXT NOT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (analysis_id) REFERENCES threat_analyses(id) ON DELETE CASCADE,
    KEY idx_threat_analysis_sections_analysis (analysis_id, section_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS auth_settings (
    setting_key   VARCHAR(100) PRIMARY KEY,
    setting_value TEXT NULL,
    is_secret     TINYINT(1) NOT NULL DEFAULT 0,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ai_providers (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    nome            VARCHAR(150) NOT NULL,
    provider_type   ENUM('ollama','openai_compatible') NOT NULL DEFAULT 'ollama',
    base_url        VARCHAR(500) NOT NULL,
    api_key         TEXT NULL,
    default_model   VARCHAR(200) NOT NULL DEFAULT '',
    model_list      TEXT NULL,
    timeout_seconds INT NOT NULL DEFAULT 300,
    allowed_hosts   TEXT NULL,
    allowed_cidrs   TEXT NULL,
    enabled         TINYINT(1) NOT NULL DEFAULT 1,
    is_default      TINYINT(1) NOT NULL DEFAULT 0,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_ai_providers_enabled (enabled, is_default)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
