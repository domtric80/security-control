<?php
require_once __DIR__ . "/../includes/functions.php";
require_permission("requisito_categorie", "read");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf();
    $id = (int)post("id", 0);
    $attiva = isset($_POST["attiva"]) ? 1 : 0;
    if (post("action") === "delete") {
        require_permission("requisito_categorie", "delete");
        get_db()->prepare("UPDATE requisito_categorie SET attiva = 0 WHERE id = ?")->execute([$id]);
        flash("success", "Categoria disattivata.");
    } elseif ($id > 0) {
        require_permission("requisito_categorie", "update");
        get_db()->prepare("UPDATE requisito_categorie SET nome=?, framework_function=?, rif_fncs=?, attiva=? WHERE id=?")
            ->execute([trim((string)post("nome")), trim((string)post("framework_function")), trim((string)post("rif_fncs")), $attiva, $id]);
        flash("success", "Categoria aggiornata.");
    } else {
        require_permission("requisito_categorie", "create");
        ensure_requisito_categoria((string)post("nome"), (string)post("framework_function"), (string)post("rif_fncs"));
        flash("success", "Categoria salvata.");
    }
    redirect(APP_BASE_URL . "/admin/requisito_categorie.php");
}

$categorie = get_requisito_categorie(false);
$page_title = "Categorie requisiti";
require __DIR__ . "/../includes/header.php";
?>
<div class="row g-4">
  <div class="col-lg-4">
    <div class="card shadow-sm">
      <div class="card-header fw-semibold">Nuova categoria</div>
      <div class="card-body">
        <form method="post" class="row g-3">
          <?= csrf_field() ?>
          <input type="hidden" name="attiva" value="1">
          <div class="col-12"><label class="form-label">Nome</label><input class="form-control" name="nome" required></div>
          <div class="col-12"><label class="form-label">Framework function</label><input class="form-control" name="framework_function"></div>
          <div class="col-12"><label class="form-label">Rif. FNCS</label><input class="form-control" name="rif_fncs"></div>
          <div class="col-12"><button class="btn btn-primary w-100">Salva categoria</button></div>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="card shadow-sm">
      <div class="card-header fw-semibold">Categorie requisiti (<?= count($categorie) ?>)</div>
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
          <thead><tr><th>Nome</th><th>Function</th><th>Rif. FNCS</th><th>Attiva</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($categorie as $c): ?>
          <tr>
            <form method="post">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int)$c["id"] ?>">
              <td><input class="form-control form-control-sm" name="nome" value="<?= h($c["nome"]) ?>"></td>
              <td><input class="form-control form-control-sm" name="framework_function" value="<?= h($c["framework_function"] ?? "") ?>"></td>
              <td><input class="form-control form-control-sm" name="rif_fncs" value="<?= h($c["rif_fncs"] ?? "") ?>"></td>
              <td><input class="form-check-input" type="checkbox" name="attiva" value="1" <?= $c["attiva"] ? "checked" : "" ?>></td>
              <td class="text-end text-nowrap">
                <button class="btn btn-sm btn-outline-primary">Salva</button>
                <button class="btn btn-sm btn-outline-secondary" name="action" value="delete">Disattiva</button>
              </td>
            </form>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . "/../includes/footer.php"; ?>
