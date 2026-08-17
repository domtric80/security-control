<?php
require_once __DIR__ . '/../includes/functions.php';
require_permission('servizi', 'read');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)post('action', 'save');
    if ($action === 'deactivate') {
        require_permission('servizi', 'delete');
        set_servizio_active((int)post('id'), false);
        flash('success', 'Servizio disattivato. Le relazioni storiche restano conservate.');
    } elseif ($action === 'activate') {
        require_permission('servizi', 'update');
        set_servizio_active((int)post('id'), true);
        flash('success', 'Servizio riattivato.');
    } else {
        require_permission('servizi', (int)post('id') > 0 ? 'update' : 'create');
        save_servizio($_POST);
        flash('success', 'Servizio salvato.');
    }
    redirect(APP_BASE_URL . '/admin/servizi.php');
}

$edit = isset($_GET['edit']) ? get_servizio((int)$_GET['edit']) : null;
$servizi = get_servizi(false);
$empty = [
    'id' => 0,
    'reparto_owner' => '',
    'tipo_canone_ci' => '',
    'portfolio_category' => '',
    'macro_service' => '',
    'categoria' => '',
    'servizio_elementare' => '',
    'descrizione' => '',
    'tipo_attivita' => '',
    'misurabilita' => '',
    'commessa' => '',
    'check_component' => '',
    'asset_primario' => '',
    'software' => '',
    'orario_servizio' => '',
    'note' => '',
    'attivo' => 1,
];
$form = $edit ?: $empty;

$page_title = 'Admin servizi';
require __DIR__ . '/../includes/header.php';
?>

<div class="row g-4">
  <div class="col-xl-5">
    <div class="card shadow-sm">
      <div class="card-header fw-semibold">
        <?= $edit ? 'Modifica servizio #' . (int)$edit['id'] : 'Nuovo servizio' ?>
      </div>
      <div class="card-body">
        <form method="post" class="row g-3">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int)$form['id'] ?>">

          <div class="col-md-6">
            <label class="form-label">Reparto owner</label>
            <input class="form-control" name="reparto_owner" value="<?= h($form['reparto_owner']) ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label">Canone/CI</label>
            <input class="form-control" name="tipo_canone_ci" value="<?= h($form['tipo_canone_ci']) ?>">
          </div>
          <div class="col-md-3 d-flex align-items-end">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="attivo" value="1" <?= (int)$form['attivo'] ? 'checked' : '' ?>>
              <label class="form-check-label">Attivo</label>
            </div>
          </div>

          <div class="col-md-6">
            <label class="form-label">Portfolio category</label>
            <input class="form-control" name="portfolio_category" value="<?= h($form['portfolio_category']) ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Macro service</label>
            <input class="form-control" name="macro_service" value="<?= h($form['macro_service']) ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Categoria</label>
            <input class="form-control" name="categoria" value="<?= h($form['categoria']) ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Servizio elementare</label>
            <input class="form-control" name="servizio_elementare" value="<?= h($form['servizio_elementare']) ?>" required>
          </div>

          <div class="col-12">
            <label class="form-label">Descrizione</label>
            <textarea class="form-control" name="descrizione" rows="3"><?= h($form['descrizione']) ?></textarea>
          </div>

          <div class="col-md-4">
            <label class="form-label">Tipo attività</label>
            <input class="form-control" name="tipo_attivita" value="<?= h($form['tipo_attivita']) ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">Misurabilità</label>
            <input class="form-control" name="misurabilita" value="<?= h($form['misurabilita']) ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">Commessa</label>
            <input class="form-control" name="commessa" value="<?= h($form['commessa']) ?>">
          </div>

          <div class="col-md-4">
            <label class="form-label">Check component</label>
            <input class="form-control" name="check_component" value="<?= h($form['check_component']) ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">Asset primario</label>
            <input class="form-control" name="asset_primario" value="<?= h($form['asset_primario']) ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">Orario servizio</label>
            <input class="form-control" name="orario_servizio" value="<?= h($form['orario_servizio']) ?>">
          </div>

          <div class="col-12">
            <label class="form-label">Software</label>
            <input class="form-control" name="software" value="<?= h($form['software']) ?>">
          </div>
          <div class="col-12">
            <label class="form-label">Note</label>
            <textarea class="form-control" name="note" rows="2"><?= h($form['note']) ?></textarea>
          </div>

          <div class="col-12 d-flex flex-wrap gap-2">
            <button class="btn btn-primary">Salva servizio</button>
            <?php if ($edit): ?>
              <a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/admin/servizi.php">Nuovo servizio</a>
              <a class="btn btn-outline-warning" href="<?= APP_BASE_URL ?>/admin/regole_servizi.php?servizio_id=<?= (int)$edit['id'] ?>">Gestisci regole</a>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-xl-7">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1 class="h4 mb-0"><i class="bi bi-grid me-2"></i>Servizi</h1>
      <span class="badge text-bg-secondary"><?= count($servizi) ?> servizi</span>
    </div>

    <div class="card shadow-sm">
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
          <thead>
            <tr>
              <th>Reparto</th>
              <th>Macro service</th>
              <th>Servizio elementare</th>
              <th>Stato</th>
              <th class="text-end">Azioni</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($servizi as $servizio): ?>
            <tr class="<?= (int)$servizio['attivo'] ? '' : 'table-secondary' ?>">
              <td class="small"><?= h($servizio['reparto_owner']) ?></td>
              <td class="small"><?= h(short_text($servizio['macro_service'], 55)) ?></td>
              <td>
                <strong><?= h(short_text($servizio['servizio_elementare'], 75)) ?></strong>
                <div class="small text-muted"><?= h(short_text($servizio['categoria'], 80)) ?></div>
              </td>
              <td>
                <span class="badge text-bg-<?= (int)$servizio['attivo'] ? 'success' : 'secondary' ?>">
                  <?= (int)$servizio['attivo'] ? 'Attivo' : 'Non attivo' ?>
                </span>
              </td>
              <td class="text-end text-nowrap">
                <a class="btn btn-sm btn-outline-primary" href="?edit=<?= (int)$servizio['id'] ?>">Modifica</a>
                <a class="btn btn-sm btn-outline-warning" href="<?= APP_BASE_URL ?>/admin/regole_servizi.php?servizio_id=<?= (int)$servizio['id'] ?>">Regole</a>
                <form method="post" class="d-inline">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int)$servizio['id'] ?>">
                  <?php if ((int)$servizio['attivo']): ?>
                    <input type="hidden" name="action" value="deactivate">
                    <button class="btn btn-sm btn-outline-secondary" onclick="return confirm('Disattivare il servizio?')">Disattiva</button>
                  <?php else: ?>
                    <input type="hidden" name="action" value="activate">
                    <button class="btn btn-sm btn-outline-success">Riattiva</button>
                  <?php endif; ?>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$servizi): ?>
            <tr><td colspan="5" class="text-center text-muted py-4">Nessun servizio censito.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
