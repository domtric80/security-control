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
