DELIMITER //
CREATE PROCEDURE add_questionari_column_if_missing(IN col_name VARCHAR(64), IN col_def TEXT)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'questionari'
          AND COLUMN_NAME = col_name
    ) THEN
        SET @sql = CONCAT('ALTER TABLE questionari ADD COLUMN ', col_name, ' ', col_def);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END//
DELIMITER ;

CALL add_questionari_column_if_missing('pm_product_manager', 'VARCHAR(200) NULL AFTER pm');

DROP PROCEDURE add_questionari_column_if_missing;
