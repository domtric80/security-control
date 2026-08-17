<?php
require_once __DIR__ . '/../includes/functions.php';
require_permission('business_lines', 'read');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $db = get_db();
    $action = (string)post('action', 'save');
    $id = (int)post('id');

    if ($action === 'delete') {
        require_permission('business_lines', 'delete');
        $db->prepare('UPDATE business_lines SET attiva = 0 WHERE id = ?')->execute([$id]);
        flash('success', 'Business line disattivata.');
    } else {
        require_permission('business_lines', $id > 0 ? 'update' : 'create');
        $nome = trim((string)post('nome', ''));
        $ordine = (int)post('ordine', 0);
        $attiva = bool_value(post('attiva', 0));
        if ($id > 0) {
            $db->prepare('UPDATE business_lines SET nome = ?, ordine = ?, attiva = ? WHERE id = ?')
               ->execute([$nome, $ordine, $attiva, $id]);
        } else {
            $db->prepare('INSERT INTO business_lines (nome, ordine, attiva) VALUES (?, ?, ?)')
               ->execute([$nome, $ordine, $attiva]);
        }
        flash('success', 'Business line salvata.');
    }
    redirect(APP_BASE_URL . '/admin/business_lines.php');
}

$db = get_db();
$edit = null;
if (isset($_GET['edit'])) {
    $st = $db->prepare('SELECT * FROM business_lines WHERE id = ?');
    $st->execute([(int)$_GET['edit']]);
    $edit = $st->fetch();
}
$form = $edit ?: ['id' => 0, 'nome' => '', 'ordine' => 0, 'attiva' => 1];
$business_lines = get_business_lines(false);

$page_title = 'Business line';
require __DIR__ . '/../includes/header.php';
?>

<div class="row g-4">
  <div class="col-lg-4">
    <div class="card shadow-sm">
      <div class="card-header fw-semibold"><?= $edit ? 'Modifica business line' : 'Nuova business line' ?></div>
      <div class="card-body">
        <form method="post" class="row g-3">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int)$form['id'] ?>">
          <div class="col-12">
            <label class="form-label">Nome</label>
            <input class="form-control" name="nome" value="<?= h($form['nome']) ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Ordine</label>
            <input class="form-control" name="ordine" type="number" value="<?= (int)$form['ordine'] ?>">
          </div>
          <div class="col-md-6 d-flex align-items-end">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="attiva" value="1" <?= (int)$form['attiva'] ? 'checked' : '' ?>>
              <label class="form-check-label">Attiva</label>
            </div>
          </div>
          <div class="col-12">
            <button class="btn btn-primary">Salva</button>
            <?php if ($edit): ?>
              <a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/admin/business_lines.php">Nuova</a>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="card shadow-sm">
      <div class="card-header fw-semibold">Business line censite</div>
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
          <thead><tr><th>Ordine</th><th>Nome</th><th>Stato</th><th class="text-end">Azioni</th></tr></thead>
          <tbody>
          <?php foreach ($business_lines as $business_line): ?>
            <tr>
              <td><?= (int)$business_line['ordine'] ?></td>
              <td><?= h($business_line['nome']) ?></td>
              <td>
                <span class="badge text-bg-<?= (int)$business_line['attiva'] ? 'success' : 'secondary' ?>">
                  <?= (int)$business_line['attiva'] ? 'Attiva' : 'Non attiva' ?>
                </span>
              </td>
              <td class="text-end">
                <a class="btn btn-sm btn-outline-primary" href="?edit=<?= (int)$business_line['id'] ?>">Modifica</a>
                <?php if ((int)$business_line['attiva']): ?>
                  <form method="post" class="d-inline" onsubmit="return confirm('Disattivare la business line?')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int)$business_line['id'] ?>">
                    <button class="btn btn-sm btn-outline-secondary">Disattiva</button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
