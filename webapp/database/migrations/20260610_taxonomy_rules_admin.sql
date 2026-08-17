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

CALL add_column_if_missing('requisiti', 'standard', 'TINYINT NOT NULL DEFAULT 0 AFTER std');
CALL add_column_if_missing('requisiti', 'standard_dove', 'TEXT NULL AFTER standard');
CALL add_column_if_missing('requisiti', 'framework_function', 'VARCHAR(250) NULL AFTER fase');
CALL add_column_if_missing('questionario_requisiti_specifici', 'standard', 'TINYINT NOT NULL DEFAULT 0 AFTER std');
CALL add_column_if_missing('questionario_requisiti_specifici', 'standard_dove', 'TEXT NULL AFTER standard');
CALL add_column_if_missing('questionario_requisiti_specifici', 'framework_function', 'VARCHAR(250) NULL AFTER fase');
CALL add_column_if_missing('questionario_requisiti_specifici', 'requisito_catalogo_id', 'INT NULL AFTER attivo');
CALL add_column_if_missing('questionario_requisiti_specifici', 'promosso_at', 'TIMESTAMP NULL AFTER requisito_catalogo_id');
CALL add_column_if_missing('regole_requisiti', 'operatore_logico', 'ENUM(''OR'',''AND'') NOT NULL DEFAULT ''OR'' AFTER valore_atteso');
CALL add_column_if_missing('regole_servizi', 'operatore_logico', 'ENUM(''OR'',''AND'') NOT NULL DEFAULT ''OR'' AFTER valore_atteso');

DROP PROCEDURE IF EXISTS add_column_if_missing;

UPDATE requisiti
SET standard = CASE WHEN COALESCE(std, '') <> '' THEN 1 ELSE standard END,
    standard_dove = CASE WHEN COALESCE(standard_dove, '') = '' THEN std ELSE standard_dove END;

CREATE TABLE IF NOT EXISTS requisito_categorie (
    id                 INT AUTO_INCREMENT PRIMARY KEY,
    nome               VARCHAR(250) NOT NULL UNIQUE,
    framework_function VARCHAR(250),
    rif_fncs           VARCHAR(250),
    attiva             TINYINT NOT NULL DEFAULT 1,
    created_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS requisito_categoria (
    requisito_id INT NOT NULL,
    categoria_id INT NOT NULL,
    PRIMARY KEY (requisito_id, categoria_id),
    FOREIGN KEY (requisito_id) REFERENCES requisiti(id) ON DELETE CASCADE,
    FOREIGN KEY (categoria_id) REFERENCES requisito_categorie(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS requisito_specifico_categoria (
    requisito_specifico_id INT NOT NULL,
    categoria_id           INT NOT NULL,
    PRIMARY KEY (requisito_specifico_id, categoria_id),
    FOREIGN KEY (requisito_specifico_id) REFERENCES questionario_requisiti_specifici(id) ON DELETE CASCADE,
    FOREIGN KEY (categoria_id) REFERENCES requisito_categorie(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO requisito_categorie (nome, framework_function, rif_fncs)
SELECT DISTINCT TRIM(categoria), MAX(framework_function), MAX(rif_fncs)
FROM requisiti
WHERE COALESCE(TRIM(categoria), '') <> ''
GROUP BY TRIM(categoria);

INSERT IGNORE INTO requisito_categorie (nome)
SELECT DISTINCT TRIM(categoria)
FROM questionario_requisiti_specifici
WHERE COALESCE(TRIM(categoria), '') <> '';

INSERT IGNORE INTO requisito_categoria (requisito_id, categoria_id)
SELECT r.id, c.id
FROM requisiti r
JOIN requisito_categorie c ON c.nome = r.categoria
WHERE COALESCE(TRIM(r.categoria), '') <> '';

INSERT IGNORE INTO requisito_specifico_categoria (requisito_specifico_id, categoria_id)
SELECT s.id, c.id
FROM questionario_requisiti_specifici s
JOIN requisito_categorie c ON c.nome = s.categoria
WHERE COALESCE(TRIM(s.categoria), '') <> '';
