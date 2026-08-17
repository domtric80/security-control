SET @has_allowed_hosts := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'ai_providers'
      AND COLUMN_NAME = 'allowed_hosts'
);
SET @ddl_allowed_hosts := IF(
    @has_allowed_hosts = 0,
    'ALTER TABLE ai_providers ADD COLUMN allowed_hosts TEXT NULL AFTER timeout_seconds',
    'SELECT 1'
);
PREPARE stmt FROM @ddl_allowed_hosts;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_allowed_cidrs := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'ai_providers'
      AND COLUMN_NAME = 'allowed_cidrs'
);
SET @ddl_allowed_cidrs := IF(
    @has_allowed_cidrs = 0,
    'ALTER TABLE ai_providers ADD COLUMN allowed_cidrs TEXT NULL AFTER allowed_hosts',
    'SELECT 1'
);
PREPARE stmt FROM @ddl_allowed_cidrs;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
