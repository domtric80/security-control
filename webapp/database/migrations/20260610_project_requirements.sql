SET @add_task_jira := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE questionari ADD COLUMN task_jira VARCHAR(200) NULL AFTER tipologia_progetto',
        'SELECT 1'
    )
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'questionari'
      AND column_name = 'task_jira'
);
PREPARE stmt_add_task_jira FROM @add_task_jira;
EXECUTE stmt_add_task_jira;
DEALLOCATE PREPARE stmt_add_task_jira;

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
    owner           VARCHAR(250),
    attivo          TINYINT NOT NULL DEFAULT 1,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (questionario_id) REFERENCES questionari(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
