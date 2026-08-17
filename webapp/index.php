<?php
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    require_permission('dashboard', 'read');
}

$page_title = 'Dashboard';
$counts = null;
$db_error = null;
try {
    $counts = stats();
} catch (Throwable $e) {
    $db_error = $e->getMessage();
}

require __DIR__ . '/includes/header.php';
?>

<div class="row g-4">
  <div class="col-lg-8">
    <div class="card shadow-sm">
      <div class="card-body p-4">
        <h1 class="h3 mb-3">Web app requisiti SEC</h1>
        <p class="lead mb-4">
          Compila un questionario, salva le risposte e calcola i requisiti e i servizi applicabili.
        </p>
        <div class="d-flex flex-wrap gap-2">
          <?php if (has_permission('questionari', 'create')): ?>
          <a class="btn btn-primary" href="<?= APP_BASE_URL ?>/questionario/nuovo.php">
            <i class="bi bi-plus-circle me-1"></i>Nuovo questionario
          </a>
          <?php endif; ?>
          <?php if (has_permission('questionari', 'read')): ?>
          <a class="btn btn-outline-primary" href="<?= APP_BASE_URL ?>/questionario/lista.php">
            <i class="bi bi-list-check me-1"></i>Questionari salvati
          </a>
          <?php endif; ?>
          <?php if (has_permission('pir', 'read')): ?>
          <a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/pir/lista.php">
            <i class="bi bi-clipboard-check me-1"></i>PIR
          </a>
          <?php endif; ?>
          <?php if (!is_logged_in()): ?>
          <a class="btn btn-primary" href="<?= APP_BASE_URL ?>/login.php">
            <i class="bi bi-box-arrow-in-right me-1"></i>Login
          </a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card shadow-sm">
      <div class="card-header fw-semibold">Stato dati</div>
      <div class="card-body">
        <?php if ($db_error): ?>
          <div class="alert alert-warning mb-0">
            Database non raggiungibile.<br>
            <span class="small"><?= h($db_error) ?></span>
          </div>
        <?php else: ?>
          <div class="row text-center g-3">
            <?php foreach ($counts as $label => $value): ?>
              <div class="col-6">
                <div class="metric">
                  <div class="metric-value"><?= (int)$value ?></div>
                  <div class="metric-label"><?= h(ucfirst($label)) ?></div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="card mt-4">
  <div class="card-body">
    <h2 class="h5">Come iniziare</h2>
    <ol class="mb-0">
      <li>Creare il database e importare `database/schema.sql`.</li>
      <li>Importare i seed `seed_domande.sql`, `seed_requisiti.sql`, `seed_servizi.sql`.</li>
      <li>Accedere all'admin per censire o correggere domande e regole.</li>
      <li>Compilare un questionario e calcolare i risultati.</li>
    </ol>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
