<?php
require_once __DIR__ . '/../includes/functions.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit('Not found.');
}

$jsonPath = $argv[1] ?? (__DIR__ . '/../database/catalogo_requisiti_doc.json');
if (!is_file($jsonPath)) {
    fwrite(STDERR, "File JSON non trovato: {$jsonPath}\n");
    exit(1);
}

$payload = json_decode((string)file_get_contents($jsonPath), true);
if (!is_array($payload) || !isset($payload['requirements']) || !is_array($payload['requirements'])) {
    fwrite(STDERR, "JSON catalogo non valido.\n");
    exit(1);
}

$db = get_db();
$sourceFile = (string)($payload['source_file'] ?? basename($jsonPath));
$fields = [
    'codice','versione','categoria','sottocategoria','titolo','descrizione','contesto','note','importanza','owner',
    'fase','framework_function','funzionale_tecnologico','data_protection','rif_iso','rif_fncs','software_selection','riferimento_hld',
    'pubblicato_lga','rif_std_config_dc','standardizzazione_controllo_task','rif_procedura_controllo','ultimo_update','catalogo_source'
];

$select = $db->prepare('SELECT id, categoria, sottocategoria, std FROM requisiti WHERE codice = ?');
$inserted = 0;
$updated = 0;

$db->beginTransaction();
try {
    foreach ($payload['requirements'] as $item) {
        $codice = trim((string)($item['codice'] ?? ''));
        if ($codice === '') {
            continue;
        }

        $select->execute([$codice]);
        $existing = $select->fetch();
        $item['catalogo_source'] = $sourceFile;

        if ($existing) {
            $updateFields = array_filter($fields, fn($field) => $field !== 'codice');
            $sets = [];
            $values = [];
            foreach ($updateFields as $field) {
                $value = trim((string)($item[$field] ?? ''));
                if (($field === 'categoria' || $field === 'sottocategoria') && $value === '') {
                    $value = (string)($existing[$field] ?? '');
                }
                $sets[] = "{$field} = ?";
                $values[] = $value;
            }
            $values[] = (int)$existing['id'];
            $db->prepare('UPDATE requisiti SET ' . implode(', ', $sets) . ', attivo = 1 WHERE id = ?')->execute($values);
            $categoriaId = ensure_requisito_categoria(
                (string)($item['categoria'] ?? ''),
                (string)($item['framework_function'] ?? ''),
                (string)($item['rif_fncs'] ?? '')
            );
            sync_requisito_categoria((int)$existing['id'], $categoriaId, (string)($item['categoria'] ?? ''));
            $updated++;
            continue;
        }

        $insertFields = [...$fields, 'std', 'attivo'];
        $placeholders = implode(',', array_fill(0, count($insertFields), '?'));
        $values = [];
        foreach ($fields as $field) {
            $values[] = trim((string)($item[$field] ?? ''));
        }
        $values[] = '';
        $values[] = 1;
        $db->prepare('INSERT INTO requisiti (' . implode(',', $insertFields) . ") VALUES ({$placeholders})")->execute($values);
        $newId = (int)$db->lastInsertId();
        $categoriaId = ensure_requisito_categoria(
            (string)($item['categoria'] ?? ''),
            (string)($item['framework_function'] ?? ''),
            (string)($item['rif_fncs'] ?? '')
        );
        sync_requisito_categoria($newId, $categoriaId, (string)($item['categoria'] ?? ''));
        $inserted++;
    }

    if (isset($payload['attachments']) && is_array($payload['attachments'])) {
        $attachmentStmt = $db->prepare(
            'INSERT INTO catalogo_allegati (source_file, filename, mime_type, path)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE mime_type = VALUES(mime_type), path = VALUES(path)'
        );
        foreach ($payload['attachments'] as $attachment) {
            $attachmentStmt->execute([
                $sourceFile,
                (string)($attachment['filename'] ?? ''),
                (string)($attachment['mime_type'] ?? ''),
                (string)($attachment['path'] ?? ''),
            ]);
        }
    }

    $db->commit();
} catch (Throwable $e) {
    $db->rollBack();
    throw $e;
}

echo "Catalogo importato.\n";
echo "Aggiornati: {$updated}\n";
echo "Inseriti: {$inserted}\n";
echo "Totale JSON: " . count($payload['requirements']) . "\n";
