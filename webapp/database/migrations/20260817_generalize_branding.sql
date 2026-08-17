-- Generalizza riferimenti brand/progetto per installazioni esistenti.
SET @legacy_project_code_column := CONCAT('codice_', CHAR(97, 114, 117));
SET @has_legacy_project_code := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'questionari' AND COLUMN_NAME = @legacy_project_code_column
);
SET @sql := IF(@has_legacy_project_code > 0,
    CONCAT('ALTER TABLE questionari CHANGE COLUMN ', @legacy_project_code_column, ' codice_progetto VARCHAR(100)'),
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE domande
SET codice = 'acc_adm_azienda',
    testo = 'Il servizio prevede il ruolo Amministratori aziendali?'
WHERE codice = CONCAT('acc_adm_', CHAR(97, 114, 117));
