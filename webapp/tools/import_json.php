<?php
require_once __DIR__ . '/../includes/functions.php';

if (PHP_SAPI !== 'cli') {
    exit("Questo script deve essere eseguito da CLI.\n");
}

$requirementsFile = $argv[1] ?? '/var/www/input/requisiti_data.json';
$servicesFile = $argv[2] ?? '/var/www/input/servizi_data.json';

function load_json_file(string $path): array {
    if (!is_file($path)) {
        throw new RuntimeException("File non trovato: {$path}");
    }
    $json = file_get_contents($path);
    $data = json_decode($json, true);
    if (!is_array($data)) {
        throw new RuntimeException("JSON non valido: {$path}");
    }
    return $data;
}

function import_requirements(PDO $db, string $path): int {
    $rows = load_json_file($path);
    $db->beginTransaction();
    try {
        $db->exec('DELETE FROM questionario_risultati_requisiti');
        $db->exec('DELETE FROM regole_requisiti');
        $db->exec('DELETE FROM requisiti');
        $stmt = $db->prepare(
            'INSERT INTO requisiti
             (codice, versione, categoria, sottocategoria, titolo, descrizione, contesto, note, importanza, std, owner,
              appl_dc_ingegneria, appl_dc_change, appl_dc_run, appl_sviluppo)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        foreach ($rows as $row) {
            $stmt->execute([
                $row['id'] ?? '',
                $row['versione'] ?? '',
                $row['categoria'] ?? '',
                $row['sottocategoria'] ?? '',
                $row['titolo'] ?? '',
                $row['descrizione'] ?? '',
                $row['contesto'] ?? '',
                $row['note'] ?? '',
                $row['importanza'] ?? '',
                $row['std'] ?? '',
                $row['owner'] ?? '',
                $row['appl_dc_ing'] ?? '',
                $row['appl_dc_chg'] ?? '',
                $row['appl_dc_run'] ?? '',
                $row['appl_svi'] ?? '',
            ]);
        }
        $db->commit();
        return count($rows);
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}

function import_requirement_rules(PDO $db, string $path): int {
    $rows = load_json_file($path);
    $questions = $db->query('SELECT id, codice, categoria, ordine FROM domande ORDER BY ordine, id')->fetchAll();
    $questionIds = array_map(static fn(array $row): int => (int)$row['id'], $questions);
    $requirements = [];
    foreach ($db->query('SELECT id, codice FROM requisiti')->fetchAll() as $row) {
        $requirements[(string)$row['codice']] = (int)$row['id'];
    }

    $groupStmt = $db->prepare(
        'INSERT INTO regole_requisiti_gruppi (requisito_id, nome, operatore_logico, ordine)
         VALUES (?, ?, ?, ?)'
    );
    $findGroupStmt = $db->prepare(
        'SELECT id FROM regole_requisiti_gruppi WHERE requisito_id = ? AND nome = ? LIMIT 1'
    );
    $stmt = $db->prepare(
        'INSERT INTO regole_requisiti (gruppo_id, domanda_id, valore_atteso, operatore_logico, requisito_id)
         VALUES (?, ?, ?, ?, ?)'
    );
    $count = 0;
    $questionByIndex = array_values($questions);
    foreach ($rows as $row) {
        $requirementId = $requirements[(string)($row['id'] ?? '')] ?? null;
        if (!$requirementId) {
            continue;
        }
        $criteria = explode('|', (string)($row['criteri'] ?? ''));
        foreach ($criteria as $index => $value) {
            if (strtolower(trim($value)) !== 'x' || !isset($questionIds[$index])) {
                continue;
            }
            $question = $questionByIndex[$index] ?? null;
            $logicName = trim((string)($question['categoria'] ?? 'Logica'));
            $findGroupStmt->execute([$requirementId, $logicName]);
            $groupId = (int)$findGroupStmt->fetchColumn();
            if (!$groupId) {
                $groupStmt->execute([$requirementId, $logicName, 'OR', (int)($question['ordine'] ?? 0)]);
                $groupId = (int)$db->lastInsertId();
            }
            $stmt->execute([$groupId, $questionIds[$index], '1', 'OR', $requirementId]);
            $count++;
        }
    }
    return $count;
}

function import_services(PDO $db, string $path): int {
    $rows = load_json_file($path);
    $db->beginTransaction();
    try {
        $db->exec('DELETE FROM questionario_risultati_servizi');
        $db->exec('DELETE FROM regole_servizi');
        $db->exec('DELETE FROM servizi');
        $stmt = $db->prepare(
            'INSERT INTO servizi
             (reparto_owner, tipo_canone_ci, portfolio_category, macro_service, categoria, servizio_elementare,
              descrizione, tipo_attivita, misurabilita, commessa, check_component, asset_primario, software, orario_servizio, note)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        foreach ($rows as $row) {
            $stmt->execute([
                $row['reparto_owner'] ?? '',
                $row['tipo_canone_ci'] ?? '',
                $row['portfolio_category'] ?? '',
                $row['macro_service'] ?? '',
                $row['categoria'] ?? '',
                $row['servizio_elementare'] ?? '',
                $row['descrizione'] ?? '',
                $row['tipo_attivita'] ?? '',
                $row['misurabilita'] ?? '',
                $row['commessa'] ?? '',
                $row['check_component'] ?? '',
                $row['asset_primario'] ?? '',
                $row['software'] ?? '',
                $row['orario_servizio'] ?? '',
                $row['note'] ?? '',
            ]);
        }
        $db->commit();
        return count($rows);
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}

$db = get_db();
$requirements = import_requirements($db, $requirementsFile);
$requirementRules = import_requirement_rules($db, $requirementsFile);
$services = import_services($db, $servicesFile);

echo "Import completato.\n";
echo "Requisiti: {$requirements}\n";
echo "Regole requisiti: {$requirementRules}\n";
echo "Servizi: {$services}\n";
