<?php
require_once __DIR__ . '/../includes/functions.php';

if (PHP_SAPI !== 'cli') {
    exit("Questo script deve essere eseguito da CLI.\n");
}

$db = get_db();
$questionnaireId = create_questionario([
    'nome_progetto' => 'TEST DOCKER',
    'codice_progetto' => 'TST',
    'nome_servizio' => 'Servizio test',
    'business_line' => '',
    'pm' => '',
    'po' => '',
    'tpo' => '',
    'tipologia_progetto' => '',
    'descrizione' => '',
    'note' => '',
]);

$answers = [];
$stmt = $db->query(
    "SELECT id, codice
     FROM domande
     WHERE codice IN ('nuovi_infrastrutturali', 'dati_pers_clienti')"
);
foreach ($stmt->fetchAll() as $row) {
    $answers[(int)$row['id']] = '1';
}

save_risposte($questionnaireId, $answers, []);
calcola_risultati($questionnaireId);

$requirements = (int)$db
    ->query("SELECT COUNT(*) FROM questionario_risultati_requisiti WHERE questionario_id = {$questionnaireId} AND applicabile = 'si'")
    ->fetchColumn();

$services = (int)$db
    ->query("SELECT COUNT(*) FROM questionario_risultati_servizi WHERE questionario_id = {$questionnaireId} AND applicabile = 1")
    ->fetchColumn();

$db->prepare('DELETE FROM questionari WHERE id = ?')->execute([$questionnaireId]);

echo "Smoke test OK\n";
echo "Questionario temporaneo: {$questionnaireId}\n";
echo "Requisiti applicabili: {$requirements}\n";
echo "Servizi applicabili: {$services}\n";

