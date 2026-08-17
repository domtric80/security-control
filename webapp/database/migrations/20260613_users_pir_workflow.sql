CREATE TABLE IF NOT EXISTS utenti (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    username        VARCHAR(100) NOT NULL UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,
    nome            VARCHAR(150) NOT NULL,
    cognome         VARCHAR(150) NOT NULL,
    email           VARCHAR(250),
    telefono        VARCHAR(100),
    reparto         VARCHAR(250),
    ruolo           VARCHAR(250),
    is_admin        TINYINT(1) NOT NULL DEFAULT 0,
    attivo          TINYINT(1) NOT NULL DEFAULT 1,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET @schema_name = DATABASE();

SET @sql = (
  SELECT IF(COUNT(*) = 0,
    'ALTER TABLE questionari ADD COLUMN analista_questionario_id INT NULL AFTER task_jira',
    'SELECT 1'
  )
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'questionari' AND COLUMN_NAME = 'analista_questionario_id'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(COUNT(*) = 0,
    'ALTER TABLE questionari ADD COLUMN pir_analista_id INT NULL AFTER analista_questionario_id',
    'SELECT 1'
  )
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'questionari' AND COLUMN_NAME = 'pir_analista_id'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(COUNT(*) = 0,
    'ALTER TABLE questionari ADD COLUMN pir_stato ENUM(''in_corso'',''completata'') NOT NULL DEFAULT ''in_corso'' AFTER pir_analista_id',
    'SELECT 1'
  )
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'questionari' AND COLUMN_NAME = 'pir_stato'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(COUNT(*) = 0,
    'ALTER TABLE pir_requirement_reviews ADD COLUMN referente_tipo ENUM(''analista'',''partecipante'') NULL AFTER rientro_eccezione',
    'SELECT 1'
  )
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'pir_requirement_reviews' AND COLUMN_NAME = 'referente_tipo'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(COUNT(*) = 0,
    'ALTER TABLE pir_requirement_reviews ADD COLUMN referente_user_id INT NULL AFTER referente_tipo',
    'SELECT 1'
  )
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'pir_requirement_reviews' AND COLUMN_NAME = 'referente_user_id'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(COUNT(*) = 0,
    'ALTER TABLE pir_requirement_reviews ADD COLUMN referente_participant_id INT NULL AFTER referente_user_id',
    'SELECT 1'
  )
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'pir_requirement_reviews' AND COLUMN_NAME = 'referente_participant_id'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(COUNT(*) = 0,
    'ALTER TABLE pir_requirement_reviews ADD COLUMN referente_nome VARCHAR(300) NULL AFTER referente_participant_id',
    'SELECT 1'
  )
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'pir_requirement_reviews' AND COLUMN_NAME = 'referente_nome'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
