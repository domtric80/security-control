CREATE TABLE IF NOT EXISTS threat_analysis_sections (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    analysis_id     INT NOT NULL,
    section_order   INT NOT NULL DEFAULT 0,
    section_number  VARCHAR(20) NOT NULL DEFAULT '',
    title           VARCHAR(500) NOT NULL DEFAULT '',
    content_html    MEDIUMTEXT NOT NULL,
    content_text    MEDIUMTEXT NOT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (analysis_id) REFERENCES threat_analyses(id) ON DELETE CASCADE,
    KEY idx_threat_analysis_sections_analysis (analysis_id, section_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
