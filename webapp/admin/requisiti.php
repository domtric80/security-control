<?php
require_once __DIR__ . "/../includes/functions.php";
require_permission("requisiti", "read");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf();
    if (post("action") === "delete") {
        require_permission("requisiti", "delete");
        delete_requisito((int)post("id"));
        flash("success", "Requisito eliminato.");
    } else {
        require_permission("requisiti", (int)post("id") > 0 ? "update" : "create");
        save_requisito($_POST);
        flash("success", "Requisito salvato.");
    }
    redirect(APP_BASE_URL . "/admin/requisiti.php");
}

$edit = isset($_GET["edit"]) ? get_requisito((int)$_GET["edit"]) : null;
$edit_categoria_id = $edit ? get_requisito_categoria_id((int)$edit["id"]) : 0;
$requisiti = get_requisiti(false);
$categorie = get_requisito_categorie(true);
$page_title = "Admin Requisiti";
require __DIR__ . "/../includes/header.php";
?>

<?php if ($edit): ?>
<div class="card shadow-sm mb-4">
  <div class="card-header fw-semibold">Modifica requisito: <?= h($edit["codice"]) ?></div>
  <div class="card-body">
    <form method="post" class="row g-3">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int)$edit["id"] ?>">
      <div class="col-md-3"><label class="form-label">Codice</label>
        <input class="form-control" name="codice" value="<?= h($edit["codice"]) ?>" required></div>
      <div class="col-md-2"><label class="form-label">Versione</label>
        <input class="form-control" name="versione" value="<?= h($edit["versione"]) ?>"></div>
      <div class="col-md-3"><label class="form-label">Importanza</label>
        <select class="form-select" name="importanza">
          <?php foreach (["MUST","SHOULD","MAY"] as $v): ?>
          <option <?= $edit["importanza"] === $v ? "selected" : "" ?>><?= $v ?></option>
          <?php endforeach; ?>
        </select></div>
      <div class="col-md-2 d-flex align-items-end">
        <div class="form-check"><input class="form-check-input" type="checkbox" name="standard" value="1" <?= requirement_is_standard($edit) ? "checked" : "" ?>>
          <label class="form-check-label">STANDARD</label></div>
      </div>
      <div class="col-md-2 d-flex align-items-end">
        <div class="form-check"><input class="form-check-input" type="checkbox" name="attivo" value="1" <?= $edit["attivo"] ? "checked" : "" ?>>
          <label class="form-check-label">Attivo</label></div>
      </div>
      <div class="col-md-6"><label class="form-label">Categoria anagrafica</label>
        <select class="form-select" name="categoria_id">
          <option value="">-- usa testo libero sotto --</option>
          <?php foreach ($categorie as $c): ?>
          <option value="<?= (int)$c["id"] ?>" <?= (int)$c["id"] === $edit_categoria_id ? "selected" : "" ?>><?= h($c["nome"]) ?></option>
          <?php endforeach; ?>
        </select></div>
      <div class="col-md-6"><label class="form-label">Categoria testo</label>
        <input class="form-control" name="categoria" value="<?= h($edit["categoria"]) ?>"></div>
      <div class="col-md-6"><label class="form-label">Sottocategoria / Rif. FNCS</label>
        <input class="form-control" name="sottocategoria" value="<?= h($edit["sottocategoria"] ?? "") ?>"></div>
      <div class="col-md-6"><label class="form-label">Owner</label>
        <input class="form-control" name="owner" value="<?= h($edit["owner"]) ?>"></div>
      <div class="col-12"><label class="form-label">Dove è standardizzato</label>
        <textarea class="form-control" name="standard_dove" rows="2"><?= h($edit["standard_dove"] ?? $edit["std"] ?? "") ?></textarea>
        <input type="hidden" name="std" value="<?= h($edit["std"] ?? "") ?>">
      </div>
      <div class="col-12"><label class="form-label">Titolo</label>
        <input class="form-control" name="titolo" value="<?= h($edit["titolo"]) ?>"></div>
      <div class="col-12"><label class="form-label">Descrizione</label>
        <textarea class="form-control" name="descrizione" rows="4"><?= h($edit["descrizione"]) ?></textarea></div>
      <div class="col-12"><label class="form-label">Contesto di applicabilità</label>
        <textarea class="form-control" name="contesto" rows="2"><?= h($edit["contesto"] ?? "") ?></textarea></div>
      <div class="col-12"><label class="form-label">Note</label>
        <textarea class="form-control" name="note" rows="2"><?= h($edit["note"]) ?></textarea></div>

      <div class="col-12">
        <details>
          <summary class="fw-semibold">Campi estesi da catalogo HTML</summary>
          <div class="row g-3 mt-1">
            <?php
            $extended = [
              "fase" => "Fase", "framework_function" => "Framework function", "funzionale_tecnologico" => "Funzionale / Tecnologico",
              "data_protection" => "Data protection", "rif_iso" => "Rif. ISO", "rif_fncs" => "Rif. FNCS",
              "software_selection" => "Software selection", "riferimento_hld" => "Riferimento HLD", "pubblicato_lga" => "Pubblicato su LGA",
              "rif_std_config_dc" => "Rif. STD config DC", "standardizzazione_controllo_task" => "Standardizzazione controllo (Task)",
              "rif_procedura_controllo" => "Procedura controllo / collaudo", "ultimo_update" => "Ultimo update", "catalogo_source" => "Origine catalogo",
              "appl_dc_ingegneria" => "Appl. DC ingegneria", "appl_dc_change" => "Appl. DC change", "appl_dc_run" => "Appl. DC run", "appl_sviluppo" => "Appl. sviluppo",
            ];
            foreach ($extended as $field => $label):
              $textarea = in_array($field, ["rif_iso","riferimento_hld","rif_std_config_dc","standardizzazione_controllo_task","rif_procedura_controllo"], true);
            ?>
            <div class="col-md-6">
              <label class="form-label"><?= h($label) ?></label>
              <?php if ($textarea): ?>
              <textarea class="form-control" name="<?= h($field) ?>" rows="2"><?= h($edit[$field] ?? "") ?></textarea>
              <?php else: ?>
              <input class="form-control" name="<?= h($field) ?>" value="<?= h($edit[$field] ?? "") ?>">
              <?php endif; ?>
            </div>
            <?php endforeach; ?>
          </div>
        </details>
      </div>
      <div class="col-12">
        <button class="btn btn-primary">Salva</button>
        <a class="btn btn-outline-info" href="<?= APP_BASE_URL ?>/admin/requisiti_versioni.php?type=catalogo&id=<?= (int)$edit["id"] ?>">Versioni</a>
        <a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/admin/requisiti.php">Annulla</a>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h3><i class="bi bi-journal-text me-2"></i>Requisiti (<?= count($requisiti) ?>)</h3>
  <a class="btn btn-outline-primary" href="<?= APP_BASE_URL ?>/admin/requisiti_specifici.php">Requisiti specifici</a>
</div>
<div class="card shadow-sm">
  <div class="table-responsive">
    <table class="table table-sm table-hover align-middle mb-0">
      <thead><tr><th>Codice</th><th>Titolo</th><th>Categoria</th><th>Import.</th><th>STD</th><th>Attivo</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($requisiti as $r): ?>
        <tr class="<?= requirement_is_standard($r) ? "table-secondary" : "" ?>">
          <td><code><?= h($r["codice"]) ?></code></td>
          <td><?= h(short_text($r["titolo"], 80)) ?></td>
          <td class="small"><?= h(short_text($r["categoria"], 55)) ?></td>
          <td><span class="badge bg-<?= $r["importanza"] === "MUST" ? "danger" : ($r["importanza"] === "SHOULD" ? "warning" : "secondary") ?>"><?= h($r["importanza"]) ?></span></td>
          <td><?= requirement_is_standard($r) ? "Si" : "No" ?></td>
          <td><?= $r["attivo"] ? "Si" : "No" ?></td>
          <td class="text-end text-nowrap">
            <a class="btn btn-sm btn-outline-primary" href="?edit=<?= (int)$r["id"] ?>">Modifica</a>
            <a class="btn btn-sm btn-outline-info" href="<?= APP_BASE_URL ?>/admin/requisiti_versioni.php?type=catalogo&id=<?= (int)$r["id"] ?>">Versioni</a>
            <a class="btn btn-sm btn-outline-warning" href="<?= APP_BASE_URL ?>/admin/regole_requisiti.php?requisito_id=<?= (int)$r["id"] ?>">Regole</a>
            <form method="post" class="d-inline" onsubmit="return confirm('Eliminare?')">
              <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r["id"] ?>">
              <button class="btn btn-sm btn-outline-danger">Elimina</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . "/../includes/footer.php"; ?>
