<?php
require_once __DIR__ . '/../includes/functions.php';
require_permission('requisiti_specifici', 'read');

$questionario_id = get_int('questionario_id');
$questionario = get_questionario($questionario_id);
if (!$questionario) {
    http_response_code(404);
    exit('Questionario non trovato.');
}

$edit = isset($_GET['edit']) ? get_requisito_specifico((int)$_GET['edit']) : null;
if ($edit && (int)$edit['questionario_id'] !== $questionario_id) {
    $edit = null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)post('action', 'save');
    if ($action === 'delete') {
        require_permission('requisiti_specifici', 'delete');
        delete_requisito_specifico((int)post('id'), $questionario_id);
        flash('success', 'Requisito specifico disattivato.');
    } else {
        require_permission('requisiti_specifici', (int)post('id') > 0 ? 'update' : 'create');
        $_POST['questionario_id'] = $questionario_id;
        if (trim((string)post('task_jira', '')) === '') {
            $_POST['task_jira'] = $questionario['task_jira'] ?? '';
        }
        save_requisito_specifico($_POST);
        flash('success', 'Requisito specifico salvato.');
    }
    redirect(APP_BASE_URL . '/questionario/requisiti_specifici.php?questionario_id=' . $questionario_id);
}

$specifici = get_requisiti_specifici($questionario_id, true);
$categorie = get_requisito_categorie(true);
$edit_categoria_id = $edit ? get_requisito_specifico_categoria_id((int)$edit['id']) : 0;
$edit_questionari_collegati = $edit ? get_questionari_for_requisito_specifico((int)$edit['id']) : [];
$form = $edit ?: [
    'id' => 0,
    'task_jira' => $questionario['task_jira'] ?? '',
    'codice' => '',
    'versione' => '1',
    'importanza' => 'MUST',
    'owner' => '',
    'categoria' => 'Requisito specifico di progetto',
    'sottocategoria' => '',
    'titolo' => '',
    'descrizione' => '',
    'contesto' => '',
    'note' => '',
    'standard' => 0,
    'standard_dove' => '',
];
$page_title = 'Requisiti specifici';
require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h1 class="h4 mb-0">Requisiti specifici di progetto</h1>
    <div class="text-muted"><?= h($questionario['nome_progetto']) ?> · Task <?= acronym_help('JIRA', 'Identificativo del task su Jira') ?>: <?= h($questionario['task_jira'] ?? '') ?></div>
  </div>
  <a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/questionario/risultati.php?id=<?= $questionario_id ?>">Torna ai risultati</a>
</div>

<div class="row g-4">
  <div class="col-xl-5">
    <div class="card shadow-sm">
      <div class="card-header fw-semibold"><?= $edit ? 'Modifica requisito specifico' : 'Nuovo requisito specifico' ?></div>
      <div class="card-body">
        <form method="post" class="row g-3">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int)$form['id'] ?>">
          <input type="hidden" name="attivo" value="1">
          <div class="col-md-6">
            <label class="form-label">Task <?= acronym_help('JIRA', 'Identificativo del task su Jira') ?></label>
            <input class="form-control" name="task_jira" value="<?= h($form['task_jira'] ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Codice</label>
            <input class="form-control" name="codice" value="<?= h($form['codice'] ?? '') ?>" placeholder="Es. PRJ-SEC-001">
          </div>
          <div class="col-md-4">
            <label class="form-label">Versione</label>
            <input class="form-control" name="versione" value="<?= h($form['versione'] ?? '1') ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">Importanza</label>
            <select class="form-select" name="importanza">
              <?php foreach (["MUST","SHOULD","MAY"] as $v): ?>
              <option <?= ($form['importanza'] ?? '') === $v ? 'selected' : '' ?>><?= $v ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Owner</label>
            <input class="form-control" name="owner" value="<?= h($form['owner'] ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Categoria anagrafica</label>
            <select class="form-select" name="categoria_id">
              <option value="">-- usa testo libero --</option>
              <?php foreach ($categorie as $c): ?>
              <option value="<?= (int)$c['id'] ?>" <?= (int)$c['id'] === $edit_categoria_id ? 'selected' : '' ?>><?= h($c['nome']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Categoria testo</label>
            <input class="form-control" name="categoria" value="<?= h($form['categoria'] ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Sottocategoria</label>
            <input class="form-control" name="sottocategoria" value="<?= h($form['sottocategoria'] ?? '') ?>">
          </div>
          <div class="col-md-6 d-flex align-items-end">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="standard" value="1" <?= requirement_is_standard($form) ? 'checked' : '' ?>>
              <label class="form-check-label">STANDARD</label>
            </div>
          </div>
          <div class="col-12">
            <label class="form-label">Dove è standardizzato</label>
            <textarea class="form-control" name="standard_dove" rows="2"><?= h($form['standard_dove'] ?? $form['std'] ?? '') ?></textarea>
            <input type="hidden" name="std" value="<?= h($form['std'] ?? '') ?>">
          </div>
          <div class="col-12">
            <label class="form-label">Titolo</label>
            <input class="form-control" name="titolo" value="<?= h($form['titolo'] ?? '') ?>" required>
          </div>
          <div class="col-12">
            <label class="form-label">Descrizione</label>
            <textarea class="form-control" name="descrizione" rows="4" required><?= h($form['descrizione'] ?? '') ?></textarea>
          </div>
          <div class="col-12">
            <label class="form-label">Contesto di applicabilità</label>
            <textarea class="form-control" name="contesto" rows="2"><?= h($form['contesto'] ?? '') ?></textarea>
          </div>
          <div class="col-12">
            <details>
              <summary class="fw-semibold">Dettagli catalogo / riferimenti</summary>
              <div class="row g-3 mt-1">
                <?php
                $extended = [
                  "fase" => "Fase", "framework_function" => "Framework function", "funzionale_tecnologico" => "Funzionale / Tecnologico",
                  "data_protection" => "Data protection", "rif_iso" => "Rif. ISO", "rif_fncs" => "Rif. FNCS",
                  "software_selection" => "Software selection", "riferimento_hld" => "Riferimento HLD", "pubblicato_lga" => "Pubblicato su LGA",
                  "rif_std_config_dc" => "Rif. STD config DC", "standardizzazione_controllo_task" => "Standardizzazione controllo (Task)",
                  "rif_procedura_controllo" => "Procedura di controllo / collaudo", "ultimo_update" => "Ultimo update",
                ];
                foreach ($extended as $field => $label):
                  $textarea = in_array($field, ["rif_iso","riferimento_hld","rif_std_config_dc","standardizzazione_controllo_task","rif_procedura_controllo"], true);
                ?>
                <div class="col-md-6">
                  <label class="form-label"><?= h($label) ?></label>
                  <?php if ($textarea): ?>
                  <textarea class="form-control" name="<?= h($field) ?>" rows="2"><?= h($form[$field] ?? '') ?></textarea>
                  <?php else: ?>
                  <input class="form-control" name="<?= h($field) ?>" value="<?= h($form[$field] ?? '') ?>">
                  <?php endif; ?>
                </div>
                <?php endforeach; ?>
              </div>
            </details>
          </div>
          <div class="col-12">
            <label class="form-label">Note</label>
            <textarea class="form-control" name="note" rows="2"><?= h($form['note'] ?? '') ?></textarea>
          </div>
          <div class="col-12">
            <button class="btn btn-primary"><?= $edit ? 'Aggiorna requisito specifico' : 'Salva requisito specifico' ?></button>
            <?php if ($edit): ?><a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/questionario/requisiti_specifici.php?questionario_id=<?= $questionario_id ?>">Annulla</a><?php endif; ?>
          </div>
        </form>
        <?php if ($edit): ?>
        <hr>
        <h6>Questionari collegati</h6>
        <?php if ($edit_questionari_collegati): ?>
          <ul class="small mb-0">
            <?php foreach ($edit_questionari_collegati as $q): ?>
              <li>
                <a href="<?= APP_BASE_URL ?>/questionario/risultati.php?id=<?= (int)$q['id'] ?>">#<?= (int)$q['id'] ?> - <?= h($q['nome_progetto']) ?></a>
                <?php if (!empty($q['task_jira'])): ?> · <?= h($q['task_jira']) ?><?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <div class="text-muted small">Questo requisito specifico non è collegato ad altri questionari.</div>
        <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-xl-7">
    <div class="card shadow-sm">
      <div class="card-header fw-semibold">Requisiti specifici salvati</div>
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
          <thead><tr><th>Codice</th><th>Titolo</th><th>Task</th><th>Questionari collegati</th><th>Importanza</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($specifici as $r): ?>
            <tr>
              <td><code><?= h($r['codice']) ?></code></td>
              <td><strong><?= h($r['titolo']) ?></strong><div class="small text-muted"><?= h(short_text($r['descrizione'], 120)) ?></div></td>
              <td><?= h($r['task_jira']) ?></td>
              <td class="small">
                <?php $linkedQuestionari = get_questionari_for_requisito_specifico((int)$r['id']); ?>
                <?php foreach ($linkedQuestionari as $linked): ?>
                  <div><a href="<?= APP_BASE_URL ?>/questionario/risultati.php?id=<?= (int)$linked['id'] ?>">#<?= (int)$linked['id'] ?> <?= h(short_text($linked['nome_progetto'], 45)) ?></a></div>
                <?php endforeach; ?>
              </td>
              <td><?= h($r['importanza']) ?></td>
              <td class="text-end text-nowrap">
                <a class="btn btn-sm btn-outline-primary" href="<?= APP_BASE_URL ?>/questionario/requisiti_specifici.php?questionario_id=<?= $questionario_id ?>&edit=<?= (int)$r['id'] ?>">Modifica</a>
                <form method="post" class="d-inline" onsubmit="return confirm('Disattivare il requisito specifico?')">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <button class="btn btn-sm btn-outline-secondary">Disattiva</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$specifici): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">Nessun requisito specifico inserito.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
