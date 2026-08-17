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

CREATE TABLE IF NOT EXISTS regole_gruppi (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nome        VARCHAR(250) NOT NULL UNIQUE,
    descrizione TEXT,
    attivo      TINYINT NOT NULL DEFAULT 1,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CALL add_column_if_missing('regole_requisiti_gruppi', 'gruppo_logico_id', 'INT NULL AFTER id');
CALL add_column_if_missing('regole_servizi_gruppi', 'gruppo_logico_id', 'INT NULL AFTER id');

INSERT IGNORE INTO regole_gruppi (nome)
SELECT DISTINCT nome FROM regole_requisiti_gruppi WHERE COALESCE(TRIM(nome), '') <> '';

INSERT IGNORE INTO regole_gruppi (nome)
SELECT DISTINCT nome FROM regole_servizi_gruppi WHERE COALESCE(TRIM(nome), '') <> '';

UPDATE regole_requisiti_gruppi g
JOIN regole_gruppi a ON a.nome = g.nome
SET g.gruppo_logico_id = a.id
WHERE g.gruppo_logico_id IS NULL;

UPDATE regole_servizi_gruppi g
JOIN regole_gruppi a ON a.nome = g.nome
SET g.gruppo_logico_id = a.id
WHERE g.gruppo_logico_id IS NULL;

DROP PROCEDURE IF EXISTS add_column_if_missing;
