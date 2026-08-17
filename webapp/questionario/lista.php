<?php
require_once __DIR__ . '/../includes/functions.php';
require_permission('questionari', 'read');

$questionari = get_questionari();
$page_title = 'Questionari';
require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Questionari</h1>
  <div class="d-flex gap-2">
    <a class="btn btn-outline-primary" href="<?= APP_BASE_URL ?>/questionario/importa.php"><i class="bi bi-upload me-1"></i>Importa XLSX</a>
    <a class="btn btn-primary" href="<?= APP_BASE_URL ?>/questionario/nuovo.php">Nuovo questionario</a>
  </div>
</div>

<div class="card shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead>
        <tr>
          <th>ID</th>
          <th>Progetto</th>
          <th>Servizio</th>
          <th>Analista SEC</th>
          <th>Stato</th>
          <th>Creato</th>
          <th class="text-end">Azioni</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($questionari as $q): ?>
        <tr>
          <td><?= (int)$q['id'] ?></td>
          <td>
            <strong><?= h($q['nome_progetto']) ?></strong><br>
            <span class="text-muted small">
              <?= h($q['codice_progetto']) ?>
              <?php if (!empty($q['business_line'])): ?>
                · <?= h($q['business_line']) ?>
              <?php endif; ?>
            </span>
          </td>
          <td><?= h($q['nome_servizio']) ?></td>
          <td><?= h($q['analista_questionario_nome'] ?? '') ?: '<span class="text-muted">Non assegnato</span>' ?></td>
          <td><span class="badge text-bg-<?= $q['stato'] === 'completato' ? 'success' : 'secondary' ?>"><?= h($q['stato']) ?></span></td>
          <td><?= h($q['created_at']) ?></td>
          <td class="text-end">
            <a class="btn btn-sm btn-outline-secondary" href="<?= APP_BASE_URL ?>/questionario/modifica.php?id=<?= (int)$q['id'] ?>">Dati iniziali</a>
            <a class="btn btn-sm btn-outline-primary" href="<?= APP_BASE_URL ?>/questionario/compila.php?id=<?= (int)$q['id'] ?>">Compila</a>
            <a class="btn btn-sm btn-outline-success" href="<?= APP_BASE_URL ?>/questionario/risultati.php?id=<?= (int)$q['id'] ?>">Risultati</a>
            <a class="btn btn-sm btn-outline-danger" href="<?= APP_BASE_URL ?>/questionario/elimina.php?id=<?= (int)$q['id'] ?>">Elimina</a>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$questionari): ?>
        <tr><td colspan="7" class="text-center text-muted py-4">Nessun questionario presente.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>

