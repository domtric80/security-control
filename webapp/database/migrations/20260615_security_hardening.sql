CREATE TABLE IF NOT EXISTS auth_login_attempts (
    id          BIGINT AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(190) NOT NULL,
    ip_address  VARCHAR(45) NOT NULL,
    success     TINYINT(1) NOT NULL DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_auth_attempts_lookup (username, ip_address, success, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
