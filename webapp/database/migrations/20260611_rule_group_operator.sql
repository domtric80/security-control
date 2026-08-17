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

CALL add_column_if_missing('requisiti', 'regole_operatore_logico', 'ENUM(''OR'',''AND'') NOT NULL DEFAULT ''OR'' AFTER appl_sviluppo');
CALL add_column_if_missing('servizi', 'regole_operatore_logico', 'ENUM(''OR'',''AND'') NOT NULL DEFAULT ''OR'' AFTER note');

UPDATE requisiti r
JOIN (
    SELECT requisito_id,
           CASE WHEN SUM(CASE WHEN operatore_logico = 'AND' THEN 1 ELSE 0 END) > 0 THEN 'AND' ELSE 'OR' END AS operatore
    FROM regole_requisiti
    GROUP BY requisito_id
) x ON x.requisito_id = r.id
SET r.regole_operatore_logico = x.operatore;

UPDATE servizi s
JOIN (
    SELECT servizio_id,
           CASE WHEN SUM(CASE WHEN operatore_logico = 'AND' THEN 1 ELSE 0 END) > 0 THEN 'AND' ELSE 'OR' END AS operatore
    FROM regole_servizi
    GROUP BY servizio_id
) x ON x.servizio_id = s.id
SET s.regole_operatore_logico = x.operatore;

DROP PROCEDURE IF EXISTS add_column_if_missing;
