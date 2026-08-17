DROP PROCEDURE IF EXISTS add_column_if_missing;
DELIMITER //
CREATE PROCEDURE add_column_if_missing(
    IN table_name_in VARCHAR(64),
    IN column_name_in VARCHAR(64),
    IN definition_in TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = table_name_in
          AND column_name = column_name_in
    ) THEN
        SET @ddl = CONCAT('ALTER TABLE ', table_name_in, ' ADD COLUMN ', column_name_in, ' ', definition_in);
        PREPARE stmt FROM @ddl;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END//
DELIMITER ;

CREATE TABLE IF NOT EXISTS regole_requisiti_gruppi (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    requisito_id    INT NOT NULL,
    nome            VARCHAR(250) NOT NULL DEFAULT 'Default',
    operatore_logico ENUM('OR','AND') NOT NULL DEFAULT 'OR',
    ordine          INT NOT NULL DEFAULT 0,
    attivo          TINYINT NOT NULL DEFAULT 1,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (requisito_id) REFERENCES requisiti(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS regole_servizi_gruppi (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    servizio_id     INT NOT NULL,
    nome            VARCHAR(250) NOT NULL DEFAULT 'Default',
    operatore_logico ENUM('OR','AND') NOT NULL DEFAULT 'OR',
    ordine          INT NOT NULL DEFAULT 0,
    attivo          TINYINT NOT NULL DEFAULT 1,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (servizio_id) REFERENCES servizi(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CALL add_column_if_missing('regole_requisiti', 'gruppo_id', 'INT NULL AFTER id');
CALL add_column_if_missing('regole_servizi', 'gruppo_id', 'INT NULL AFTER id');

INSERT INTO regole_requisiti_gruppi (requisito_id, nome, operatore_logico, ordine)
SELECT r.id, 'Default', r.regole_operatore_logico, 0
FROM requisiti r
WHERE EXISTS (SELECT 1 FROM regole_requisiti rr WHERE rr.requisito_id = r.id)
  AND NOT EXISTS (SELECT 1 FROM regole_requisiti_gruppi g WHERE g.requisito_id = r.id);

UPDATE regole_requisiti rr
JOIN regole_requisiti_gruppi g ON g.requisito_id = rr.requisito_id
SET rr.gruppo_id = g.id,
    rr.operatore_logico = g.operatore_logico
WHERE rr.gruppo_id IS NULL;

INSERT INTO regole_servizi_gruppi (servizio_id, nome, operatore_logico, ordine)
SELECT s.id, 'Default', s.regole_operatore_logico, 0
FROM servizi s
WHERE EXISTS (SELECT 1 FROM regole_servizi rs WHERE rs.servizio_id = s.id)
  AND NOT EXISTS (SELECT 1 FROM regole_servizi_gruppi g WHERE g.servizio_id = s.id);

UPDATE regole_servizi rs
JOIN regole_servizi_gruppi g ON g.servizio_id = rs.servizio_id
SET rs.gruppo_id = g.id,
    rs.operatore_logico = g.operatore_logico
WHERE rs.gruppo_id IS NULL;

DROP PROCEDURE IF EXISTS add_column_if_missing;
