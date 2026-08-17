<?php
require_once __DIR__ . "/../includes/functions.php";
require_permission("requisiti_specifici", "read");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf();
    $id = (int)post("id");
    if (post("action") === "promote") {
        require_permission("requisiti_specifici", "update");
        $newId = promote_requisito_specifico_to_catalogo($id);
        flash($newId ? "success" : "error", $newId ? "Requisito promosso a catalogo." : "Requisito specifico non trovato.");
    } elseif (post("action") === "delete") {
        require_permission("requisiti_specifici", "delete");
        $specifico = get_requisito_specifico($id);
        if ($specifico) {
            deactivate_requisito_specifico($id);
            flash("success", "Requisito specifico disattivato.");
        }
    }
    redirect(APP_BASE_URL . "/admin/requisiti_specifici.php");
}

$specifici = get_all_requisiti_specifici(false);
$page_title = "Admin Requisiti Specifici";
require __DIR__ . "/../includes/header.php";
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3><i class="bi bi-stars me-2"></i>Requisiti specifici di progetto</h3>
  <a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/admin/requisiti.php">Catalogo requisiti</a>
</div>
<div class="card shadow-sm">
  <div class="table-responsive">
    <table class="table table-sm table-hover align-middle mb-0">
      <thead><tr><th>Codice</th><th>Titolo</th><th>Origine</th><th>Questionari</th><th>Task JIRA</th><th>Categoria</th><th>Stato</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($specifici as $r): ?>
      <tr class="<?= $r["attivo"] ? "" : "table-light text-muted" ?>">
        <td><code><?= h($r["codice"] ?: ("SPEC-" . $r["id"])) ?></code></td>
        <td><strong><?= h(short_text($r["titolo"], 90)) ?></strong><div class="small text-muted"><?= h(short_text($r["descrizione"], 120)) ?></div></td>
        <td class="small"><?= $r["questionario_id"] ? "#" . (int)$r["questionario_id"] . " · " . h(short_text($r["nome_progetto"] ?? "", 55)) : "N/D" ?></td>
        <td><?= (int)($r["questionari_collegati"] ?? 0) ?></td>
        <td><?= h($r["task_jira"] ?? "") ?></td>
        <td class="small"><?= h(short_text($r["categoria"], 55)) ?></td>
        <td>
          <?php if (!empty($r["requisito_catalogo_id"])): ?>
            <span class="badge bg-success">Catalogo #<?= (int)$r["requisito_catalogo_id"] ?></span>
          <?php elseif ($r["attivo"]): ?>
            <span class="badge bg-primary">Specifico</span>
          <?php else: ?>
            <span class="badge bg-secondary">Disattivo</span>
          <?php endif; ?>
        </td>
        <td class="text-end text-nowrap">
          <?php if (!empty($r["questionario_id"])): ?>
          <a class="btn btn-sm btn-outline-primary" href="<?= APP_BASE_URL ?>/questionario/requisiti_specifici.php?questionario_id=<?= (int)$r["questionario_id"] ?>&edit=<?= (int)$r["id"] ?>">Modifica</a>
          <?php endif; ?>
          <a class="btn btn-sm btn-outline-info" href="<?= APP_BASE_URL ?>/admin/requisiti_versioni.php?type=specifico&id=<?= (int)$r["id"] ?>">Versioni</a>
          <?php if (empty($r["requisito_catalogo_id"])): ?>
          <form method="post" class="d-inline" onsubmit="return confirm('Spostare questo requisito nel catalogo?')">
            <?= csrf_field() ?><input type="hidden" name="action" value="promote"><input type="hidden" name="id" value="<?= (int)$r["id"] ?>">
            <button class="btn btn-sm btn-outline-success">Sposta a catalogo</button>
          </form>
          <?php endif; ?>
          <?php if ($r["attivo"]): ?>
          <form method="post" class="d-inline" onsubmit="return confirm('Disattivare?')">
            <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r["id"] ?>">
            <button class="btn btn-sm btn-outline-secondary">Disattiva</button>
          </form>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$specifici): ?>
      <tr><td colspan="8" class="text-center text-muted py-4">Nessun requisito specifico presente.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . "/../includes/footer.php"; ?>
