<?php
require_once __DIR__ . '/../includes/functions.php';
require_permission('pir', 'read');

$questionari = get_questionari();
$page_title = 'PIR - Progetti';
require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h1 class="h4 mb-0">PIR - Post Implementation Review</h1>
    <div class="text-muted">Elenco progetti da verificare dopo lâ€™implementazione.</div>
  </div>
  <a class="btn btn-outline-danger" href="<?= APP_BASE_URL ?>/pir/eccezioni.php">
    <i class="bi bi-calendar-x me-1"></i>Eccezioni sicurezza
  </a>
</div>

<div class="card shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead>
        <tr>
          <th>ID</th>
          <th>Progetto</th>
          <th>Business line</th>
          <th>Task <?= acronym_help('JIRA', 'Identificativo del task su Jira') ?></th>
          <th>Analista PIR</th>
          <th>Stato PIR</th>
          <th>Stato questionario</th>
          <th class="text-end">Azioni</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($questionari as $q): ?>
        <tr>
          <td><?= (int)$q['id'] ?></td>
          <td>
            <strong><?= h($q['nome_progetto']) ?></strong><br>
            <span class="text-muted small"><?= h($q['codice_progetto']) ?> Â· <?= h($q['nome_servizio']) ?></span>
          </td>
          <td><?= h($q['business_line'] ?? '') ?></td>
          <td><?= h($q['task_jira'] ?? '') ?></td>
          <td><?= h($q['pir_analista_nome'] ?? '') ?: '<span class="text-muted">Non assegnato</span>' ?></td>
          <td><span class="badge text-bg-<?= ($q['pir_stato'] ?? 'in_corso') === 'completata' ? 'success' : 'warning' ?>"><?= ($q['pir_stato'] ?? 'in_corso') === 'completata' ? 'COMPLETATA' : 'IN CORSO' ?></span></td>
          <td><span class="badge text-bg-<?= $q['stato'] === 'completato' ? 'success' : 'secondary' ?>"><?= h($q['stato']) ?></span></td>
          <td class="text-end">
            <a class="btn btn-sm btn-outline-primary" href="<?= APP_BASE_URL ?>/pir/progetto.php?id=<?= (int)$q['id'] ?>">Apri scheda progetto</a>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$questionari): ?>
        <tr><td colspan="8" class="text-center text-muted py-4">Nessun progetto disponibile.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>


