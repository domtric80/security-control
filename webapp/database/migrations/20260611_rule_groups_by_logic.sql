INSERT INTO regole_requisiti_gruppi (requisito_id, nome, operatore_logico, ordine)
SELECT
    rr.requisito_id,
    d.categoria,
    COALESCE(MAX(g.operatore_logico), MAX(r.regole_operatore_logico), 'OR') AS operatore_logico,
    MIN(d.ordine) AS ordine
FROM regole_requisiti rr
JOIN domande d ON d.id = rr.domanda_id
JOIN requisiti r ON r.id = rr.requisito_id
LEFT JOIN regole_requisiti_gruppi g ON g.id = rr.gruppo_id
WHERE rr.gruppo_id IS NULL OR g.nome = 'Default'
GROUP BY rr.requisito_id, d.categoria
HAVING NOT EXISTS (
    SELECT 1
    FROM regole_requisiti_gruppi existing_group
    WHERE existing_group.requisito_id = rr.requisito_id
      AND existing_group.nome = d.categoria
);

UPDATE regole_requisiti rr
JOIN domande d ON d.id = rr.domanda_id
LEFT JOIN regole_requisiti_gruppi old_group ON old_group.id = rr.gruppo_id
JOIN regole_requisiti_gruppi new_group
  ON new_group.requisito_id = rr.requisito_id
 AND new_group.nome = d.categoria
SET rr.gruppo_id = new_group.id,
    rr.operatore_logico = new_group.operatore_logico
WHERE rr.gruppo_id IS NULL OR old_group.nome = 'Default';

DELETE g
FROM regole_requisiti_gruppi g
LEFT JOIN regole_requisiti rr ON rr.gruppo_id = g.id
WHERE g.nome = 'Default'
  AND rr.id IS NULL;

INSERT INTO regole_servizi_gruppi (servizio_id, nome, operatore_logico, ordine)
SELECT
    rs.servizio_id,
    d.categoria,
    COALESCE(MAX(g.operatore_logico), MAX(s.regole_operatore_logico), 'OR') AS operatore_logico,
    MIN(d.ordine) AS ordine
FROM regole_servizi rs
JOIN domande d ON d.id = rs.domanda_id
JOIN servizi s ON s.id = rs.servizio_id
LEFT JOIN regole_servizi_gruppi g ON g.id = rs.gruppo_id
WHERE rs.gruppo_id IS NULL OR g.nome = 'Default'
GROUP BY rs.servizio_id, d.categoria
HAVING NOT EXISTS (
    SELECT 1
    FROM regole_servizi_gruppi existing_group
    WHERE existing_group.servizio_id = rs.servizio_id
      AND existing_group.nome = d.categoria
);

UPDATE regole_servizi rs
JOIN domande d ON d.id = rs.domanda_id
LEFT JOIN regole_servizi_gruppi old_group ON old_group.id = rs.gruppo_id
JOIN regole_servizi_gruppi new_group
  ON new_group.servizio_id = rs.servizio_id
 AND new_group.nome = d.categoria
SET rs.gruppo_id = new_group.id,
    rs.operatore_logico = new_group.operatore_logico
WHERE rs.gruppo_id IS NULL OR old_group.nome = 'Default';

DELETE g
FROM regole_servizi_gruppi g
LEFT JOIN regole_servizi rs ON rs.gruppo_id = g.id
WHERE g.nome = 'Default'
  AND rs.id IS NULL;
