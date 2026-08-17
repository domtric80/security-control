CREATE INDEX idx_rr_domanda ON regole_requisiti (domanda_id);
CREATE INDEX idx_rr_requisito ON regole_requisiti (requisito_id);
CREATE INDEX idx_rr_gruppo ON regole_requisiti (gruppo_id);

SET @has_uq_rr := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'regole_requisiti'
      AND index_name = 'uq_rr'
);
SET @drop_uq_rr := IF(@has_uq_rr > 0, 'ALTER TABLE regole_requisiti DROP INDEX uq_rr', 'SELECT 1');
PREPARE stmt FROM @drop_uq_rr;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE regole_requisiti
    ADD UNIQUE KEY uq_rr (gruppo_id, domanda_id, valore_atteso, requisito_id);

CREATE INDEX idx_rs_domanda ON regole_servizi (domanda_id);
CREATE INDEX idx_rs_servizio ON regole_servizi (servizio_id);
CREATE INDEX idx_rs_gruppo ON regole_servizi (gruppo_id);

SET @has_uq_rs := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'regole_servizi'
      AND index_name = 'uq_rs'
);
SET @drop_uq_rs := IF(@has_uq_rs > 0, 'ALTER TABLE regole_servizi DROP INDEX uq_rs', 'SELECT 1');
PREPARE stmt FROM @drop_uq_rs;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE regole_servizi
    ADD UNIQUE KEY uq_rs (gruppo_id, domanda_id, valore_atteso, servizio_id);
