CREATE TABLE IF NOT EXISTS requisito_versioni (
    id                 INT AUTO_INCREMENT PRIMARY KEY,
    entity_type        ENUM('catalogo','specifico') NOT NULL,
    entity_id          INT NOT NULL,
    version_no         INT NOT NULL,
    action             VARCHAR(50) NOT NULL DEFAULT 'snapshot',
    snapshot_json      JSON NOT NULL,
    correlations_json  JSON NOT NULL,
    changed_by_user_id INT NULL,
    changed_by_label   VARCHAR(300) NOT NULL DEFAULT '',
    changed_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_requisito_versione (entity_type, entity_id, version_no),
    KEY idx_requisito_versioni_entity (entity_type, entity_id, changed_at),
    KEY idx_requisito_versioni_user (changed_by_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO requisito_versioni (
    entity_type, entity_id, version_no, action, snapshot_json, correlations_json, changed_by_user_id, changed_by_label
)
SELECT
    'catalogo',
    r.id,
    1,
    'baseline',
    JSON_OBJECT(
        'id', r.id,
        'codice', r.codice,
        'versione', r.versione,
        'categoria', r.categoria,
        'sottocategoria', r.sottocategoria,
        'titolo', r.titolo,
        'descrizione', r.descrizione,
        'contesto', r.contesto,
        'note', r.note,
        'importanza', r.importanza,
        'std', r.std,
        'standard', r.standard,
        'standard_dove', r.standard_dove,
        'owner', r.owner,
        'fase', r.fase,
        'framework_function', r.framework_function,
        'funzionale_tecnologico', r.funzionale_tecnologico,
        'data_protection', r.data_protection,
        'rif_iso', r.rif_iso,
        'rif_fncs', r.rif_fncs,
        'software_selection', r.software_selection,
        'riferimento_hld', r.riferimento_hld,
        'pubblicato_lga', r.pubblicato_lga,
        'rif_std_config_dc', r.rif_std_config_dc,
        'standardizzazione_controllo_task', r.standardizzazione_controllo_task,
        'rif_procedura_controllo', r.rif_procedura_controllo,
        'ultimo_update', r.ultimo_update,
        'catalogo_source', r.catalogo_source,
        'appl_dc_ingegneria', r.appl_dc_ingegneria,
        'appl_dc_change', r.appl_dc_change,
        'appl_dc_run', r.appl_dc_run,
        'appl_sviluppo', r.appl_sviluppo,
        'regole_operatore_logico', r.regole_operatore_logico,
        'attivo', r.attivo
    ),
    JSON_OBJECT(
        'categorie',
        (
            SELECT COALESCE(JSON_ARRAYAGG(JSON_OBJECT('categoria_id', rc.categoria_id, 'nome', c.nome)), JSON_ARRAY())
            FROM requisito_categoria rc
            LEFT JOIN requisito_categorie c ON c.id = rc.categoria_id
            WHERE rc.requisito_id = r.id
        ),
        'regole_gruppi',
        (
            SELECT COALESCE(JSON_ARRAYAGG(JSON_OBJECT(
                'id', g.id,
                'gruppo_logico_id', g.gruppo_logico_id,
                'nome', g.nome,
                'operatore_logico', g.operatore_logico,
                'ordine', g.ordine,
                'attivo', g.attivo,
                'regole', (
                    SELECT COALESCE(JSON_ARRAYAGG(JSON_OBJECT(
                        'id', rr.id,
                        'domanda_id', rr.domanda_id,
                        'valore_atteso', rr.valore_atteso,
                        'operatore_logico', rr.operatore_logico
                    )), JSON_ARRAY())
                    FROM regole_requisiti rr
                    WHERE rr.gruppo_id = g.id AND rr.requisito_id = r.id
                )
            )), JSON_ARRAY())
            FROM regole_requisiti_gruppi g
            WHERE g.requisito_id = r.id
        )
    ),
    NULL,
    'Baseline automatica'
FROM requisiti r
WHERE NOT EXISTS (
    SELECT 1 FROM requisito_versioni v
    WHERE v.entity_type = 'catalogo' AND v.entity_id = r.id AND v.version_no = 1
);

INSERT INTO requisito_versioni (
    entity_type, entity_id, version_no, action, snapshot_json, correlations_json, changed_by_user_id, changed_by_label
)
SELECT
    'specifico',
    s.id,
    1,
    'baseline',
    JSON_OBJECT(
        'id', s.id,
        'questionario_id', s.questionario_id,
        'task_jira', s.task_jira,
        'codice', s.codice,
        'versione', s.versione,
        'categoria', s.categoria,
        'sottocategoria', s.sottocategoria,
        'titolo', s.titolo,
        'descrizione', s.descrizione,
        'contesto', s.contesto,
        'note', s.note,
        'importanza', s.importanza,
        'std', s.std,
        'standard', s.standard,
        'standard_dove', s.standard_dove,
        'owner', s.owner,
        'fase', s.fase,
        'framework_function', s.framework_function,
        'funzionale_tecnologico', s.funzionale_tecnologico,
        'data_protection', s.data_protection,
        'rif_iso', s.rif_iso,
        'rif_fncs', s.rif_fncs,
        'software_selection', s.software_selection,
        'riferimento_hld', s.riferimento_hld,
        'pubblicato_lga', s.pubblicato_lga,
        'rif_std_config_dc', s.rif_std_config_dc,
        'standardizzazione_controllo_task', s.standardizzazione_controllo_task,
        'rif_procedura_controllo', s.rif_procedura_controllo,
        'ultimo_update', s.ultimo_update,
        'attivo', s.attivo,
        'requisito_catalogo_id', s.requisito_catalogo_id,
        'promosso_at', s.promosso_at
    ),
    JSON_OBJECT(
        'categorie',
        (
            SELECT COALESCE(JSON_ARRAYAGG(JSON_OBJECT('categoria_id', sc.categoria_id, 'nome', c.nome)), JSON_ARRAY())
            FROM requisito_specifico_categoria sc
            LEFT JOIN requisito_categorie c ON c.id = sc.categoria_id
            WHERE sc.requisito_specifico_id = s.id
        ),
        'questionari',
        (
            SELECT COALESCE(JSON_ARRAYAGG(JSON_OBJECT('questionario_id', l.questionario_id)), JSON_ARRAY())
            FROM questionario_requisiti_specifici_link l
            WHERE l.requisito_specifico_id = s.id
        )
    ),
    NULL,
    'Baseline automatica'
FROM questionario_requisiti_specifici s
WHERE NOT EXISTS (
    SELECT 1 FROM requisito_versioni v
    WHERE v.entity_type = 'specifico' AND v.entity_id = s.id AND v.version_no = 1
);
