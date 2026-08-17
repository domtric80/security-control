CREATE TABLE IF NOT EXISTS questionario_requisiti_specifici_link (
    questionario_id INT NOT NULL,
    requisito_specifico_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (questionario_id, requisito_specifico_id),
    FOREIGN KEY (questionario_id) REFERENCES questionari(id) ON DELETE CASCADE,
    FOREIGN KEY (requisito_specifico_id) REFERENCES questionario_requisiti_specifici(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO questionario_requisiti_specifici_link (questionario_id, requisito_specifico_id)
SELECT questionario_id, id
FROM questionario_requisiti_specifici
WHERE questionario_id IS NOT NULL;

SET @fk_name := (
    SELECT CONSTRAINT_NAME
    FROM information_schema.REFERENTIAL_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'questionario_requisiti_specifici'
      AND REFERENCED_TABLE_NAME = 'questionari'
    LIMIT 1
);
SET @drop_fk := IF(@fk_name IS NULL, 'SELECT 1', CONCAT('ALTER TABLE questionario_requisiti_specifici DROP FOREIGN KEY ', @fk_name));
PREPARE stmt FROM @drop_fk;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE questionario_requisiti_specifici MODIFY questionario_id INT NULL;

ALTER TABLE questionario_requisiti_specifici
    ADD CONSTRAINT fk_qrs_origin_questionario
    FOREIGN KEY (questionario_id) REFERENCES questionari(id) ON DELETE SET NULL;
