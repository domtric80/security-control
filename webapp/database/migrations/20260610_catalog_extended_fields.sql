DROP PROCEDURE IF EXISTS add_requisiti_column_if_missing;
DELIMITER //
CREATE PROCEDURE add_requisiti_column_if_missing(
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

CALL add_requisiti_column_if_missing('requisiti', 'fase', 'VARCHAR(100) NULL AFTER owner');
CALL add_requisiti_column_if_missing('requisiti', 'funzionale_tecnologico', 'VARCHAR(100) NULL AFTER fase');
CALL add_requisiti_column_if_missing('requisiti', 'data_protection', 'VARCHAR(100) NULL AFTER funzionale_tecnologico');
CALL add_requisiti_column_if_missing('requisiti', 'rif_iso', 'TEXT NULL AFTER data_protection');
CALL add_requisiti_column_if_missing('requisiti', 'rif_fncs', 'VARCHAR(250) NULL AFTER rif_iso');
CALL add_requisiti_column_if_missing('requisiti', 'software_selection', 'VARCHAR(250) NULL AFTER rif_fncs');
CALL add_requisiti_column_if_missing('requisiti', 'riferimento_hld', 'TEXT NULL AFTER software_selection');
CALL add_requisiti_column_if_missing('requisiti', 'pubblicato_lga', 'VARCHAR(100) NULL AFTER riferimento_hld');
CALL add_requisiti_column_if_missing('requisiti', 'rif_std_config_dc', 'TEXT NULL AFTER pubblicato_lga');
CALL add_requisiti_column_if_missing('requisiti', 'standardizzazione_controllo_task', 'TEXT NULL AFTER rif_std_config_dc');
CALL add_requisiti_column_if_missing('requisiti', 'rif_procedura_controllo', 'TEXT NULL AFTER standardizzazione_controllo_task');
CALL add_requisiti_column_if_missing('requisiti', 'ultimo_update', 'VARCHAR(100) NULL AFTER rif_procedura_controllo');
CALL add_requisiti_column_if_missing('requisiti', 'catalogo_source', 'VARCHAR(255) NULL AFTER ultimo_update');

CALL add_requisiti_column_if_missing('questionario_requisiti_specifici', 'fase', 'VARCHAR(100) NULL AFTER owner');
CALL add_requisiti_column_if_missing('questionario_requisiti_specifici', 'funzionale_tecnologico', 'VARCHAR(100) NULL AFTER fase');
CALL add_requisiti_column_if_missing('questionario_requisiti_specifici', 'data_protection', 'VARCHAR(100) NULL AFTER funzionale_tecnologico');
CALL add_requisiti_column_if_missing('questionario_requisiti_specifici', 'rif_iso', 'TEXT NULL AFTER data_protection');
CALL add_requisiti_column_if_missing('questionario_requisiti_specifici', 'rif_fncs', 'VARCHAR(250) NULL AFTER rif_iso');
CALL add_requisiti_column_if_missing('questionario_requisiti_specifici', 'software_selection', 'VARCHAR(250) NULL AFTER rif_fncs');
CALL add_requisiti_column_if_missing('questionario_requisiti_specifici', 'riferimento_hld', 'TEXT NULL AFTER software_selection');
CALL add_requisiti_column_if_missing('questionario_requisiti_specifici', 'pubblicato_lga', 'VARCHAR(100) NULL AFTER riferimento_hld');
CALL add_requisiti_column_if_missing('questionario_requisiti_specifici', 'rif_std_config_dc', 'TEXT NULL AFTER pubblicato_lga');
CALL add_requisiti_column_if_missing('questionario_requisiti_specifici', 'standardizzazione_controllo_task', 'TEXT NULL AFTER rif_std_config_dc');
CALL add_requisiti_column_if_missing('questionario_requisiti_specifici', 'rif_procedura_controllo', 'TEXT NULL AFTER standardizzazione_controllo_task');
CALL add_requisiti_column_if_missing('questionario_requisiti_specifici', 'ultimo_update', 'VARCHAR(100) NULL AFTER rif_procedura_controllo');

DROP PROCEDURE IF EXISTS add_requisiti_column_if_missing;

CREATE TABLE IF NOT EXISTS catalogo_allegati (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    source_file VARCHAR(255) NOT NULL,
    filename    VARCHAR(255) NOT NULL,
    mime_type   VARCHAR(150),
    path        VARCHAR(500) NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_catalogo_allegato (source_file, filename)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
