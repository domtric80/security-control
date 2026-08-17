CREATE TABLE IF NOT EXISTS pir_requirement_reviews (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    questionario_id     INT NOT NULL,
    requisito_tipo      ENUM('catalogo','default_design','specifico') NOT NULL,
    requisito_ref_id    INT NOT NULL,
    stato               ENUM('OK','KO','non_applicabile','parziale') NULL,
    note                TEXT,
    applicazione        TEXT,
    rientro_eccezione   TEXT,
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
