DELIMITER //
CREATE PROCEDURE add_qrs_column_if_missing(IN col_name VARCHAR(64), IN col_def TEXT)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'questionario_risultati_servizi'
          AND COLUMN_NAME = col_name
    ) THEN
        SET @sql = CONCAT('ALTER TABLE questionario_risultati_servizi ADD COLUMN ', col_name, ' ', col_def);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END//
DELIMITER ;

CALL add_qrs_column_if_missing('manuale', 'TINYINT NOT NULL DEFAULT 0 AFTER applicabile');
CALL add_qrs_column_if_missing('note', 'TEXT NULL AFTER manuale');

DROP PROCEDURE add_qrs_column_if_missing;
