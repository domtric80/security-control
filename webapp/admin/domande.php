<?php
require_once __DIR__ . '/../includes/functions.php';
require_permission('domande', 'read');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (post('action') === 'delete') {
        require_permission('domande', 'delete');
        delete_domanda((int)post('id'));
        flash('success', 'Domanda eliminata.');
    } else {
        require_permission('domande', (int)post('id') > 0 ? 'update' : 'create');
        save_domanda($_POST);
        flash('success', 'Domanda salvata.');
    }
    redirect(APP_BASE_URL . '/admin/domande.php');
}

$edit = isset($_GET['edit']) ? get_domanda((int)$_GET['edit']) : null;
$domande = get_domande(false);
$page_title = 'Admin domande';
require __DIR__ . '/../includes/header.php';
?>

<div class="row g-4">
  <div class="col-lg-5">
    <div class="card shadow-sm">
      <div class="card-header fw-semibold"><?= $edit ? 'Modifica domanda' : 'Nuova domanda' ?></div>
      <div class="card-body">
        <form method="post" class="row g-3">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
          <div class="col-md-6">
            <label class="form-label">Codice</label>
            <input class="form-control" name="codice" value="<?= h($edit['codice'] ?? '') ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Categoria</label>
            <input class="form-control" name="categoria" value="<?= h($edit['categoria'] ?? '') ?>" required>
          </div>
          <div class="col-12">
            <label class="form-label">Testo domanda</label>
            <textarea class="form-control" name="testo" rows="3" required><?= h($edit['testo'] ?? '') ?></textarea>
          </div>
          <div class="col-md-4">
            <label class="form-label">Tipo</label>
            <select class="form-select" name="tipo">
              <?php foreach (['bool', 'text', 'select', 'multi'] as $type): ?>
                <option value="<?= $type ?>" <?= ($edit['tipo'] ?? 'bool') === $type ? 'selected' : '' ?>><?= $type ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Ordine</label>
            <input class="form-control" name="ordine" type="number" value="<?= h($edit['ordine'] ?? '0') ?>">
          </div>
          <div class="col-md-4 d-flex align-items-end gap-3">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="obbligatoria" value="1" <?= ((int)($edit['obbligatoria'] ?? 1)) ? 'checked' : '' ?>>
              <label class="form-check-label">Obbligatoria</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="attiva" value="1" <?= ((int)($edit['attiva'] ?? 1)) ? 'checked' : '' ?>>
              <label class="form-check-label">Attiva</label>
            </div>
          </div>
          <div class="col-12">
            <button class="btn btn-primary">Salva</button>
            <?php if ($edit): ?>
              <a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/admin/domande.php">Nuova</a>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-7">
    <div class="card shadow-sm">
      <div class="card-header fw-semibold">Domande censite</div>
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
          <thead><tr><th>Ordine</th><th>Codice</th><th>Domanda</th><th>Tipo</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($domande as $d): ?>
            <tr>
              <td><?= (int)$d['ordine'] ?></td>
              <td><code><?= h($d['codice']) ?></code><div class="small text-muted"><?= h($d['categoria']) ?></div></td>
              <td><?= h($d['testo']) ?></td>
              <td><?= h($d['tipo']) ?></td>
              <td class="text-end text-nowrap">
                <a class="btn btn-sm btn-outline-primary" href="?edit=<?= (int)$d['id'] ?>">Modifica</a>
                <form method="post" class="d-inline" onsubmit="return confirm('Eliminare la domanda?')">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                  <button class="btn btn-sm btn-outline-danger">Elimina</button>
                </form>
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
