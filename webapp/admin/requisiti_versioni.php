<?php
require_once __DIR__ . "/../includes/functions.php";

$type = (string)($_GET["type"] ?? post("type", "catalogo"));
$entityId = (int)($_GET["id"] ?? post("entity_id", 0));
$permission = $type === "specifico" ? "requisiti_specifici" : "requisiti";
require_permission($permission, "read");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf();
    require_permission($permission, "update");
    $versionId = (int)post("version_id");
    $result = restore_requisito_version($versionId);
    flash($result["ok"] ? "success" : "error", $result["message"]);
    redirect(APP_BASE_URL . "/admin/requisiti_versioni.php?" . http_build_query(["type" => $type, "id" => $entityId]));
}

$entity = $type === "specifico" ? get_requisito_specifico($entityId) : get_requisito($entityId);
if (!$entity) {
    $page_title = "Versioni requisito";
    require __DIR__ . "/../includes/header.php";
    ?>
    <div class="alert alert-warning">Requisito non trovato.</div>
    <a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/admin/requisiti.php">Torna ai requisiti</a>
    <?php
    require __DIR__ . "/../includes/footer.php";
    exit;
}

$versioni = get_requisito_versioni($type, $entityId);
$page_title = "Versioni requisito";
require __DIR__ . "/../includes/header.php";
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h1 class="h4 mb-0">Versioni requisito</h1>
    <div class="text-muted">
      <?= $type === "specifico" ? "Specifico" : "Catalogo" ?> ·
      <code><?= h($entity["codice"] ?: ($type === "specifico" ? "SPEC-" . $entityId : "")) ?></code>
      <?= h($entity["titolo"] ?? "") ?>
    </div>
  </div>
  <div class="d-flex gap-2">
    <?php if ($type === "specifico"): ?>
      <a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/admin/requisiti_specifici.php">Requisiti specifici</a>
    <?php else: ?>
      <a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/admin/requisiti.php">Requisiti catalogo</a>
    <?php endif; ?>
  </div>
</div>

<div class="alert alert-info">
  Ogni versione contiene copia completa del requisito e delle correlazioni:
  categorie<?= $type === "catalogo" ? ", gruppi regole e condizioni" : " e questionari collegati" ?>.
</div>

<div class="card shadow-sm">
  <div class="table-responsive">
    <table class="table table-sm table-hover align-middle mb-0">
      <thead>
        <tr>
          <th>Versione</th>
          <th>Azione</th>
          <th>Data</th>
          <th>Modificato da</th>
          <th>Snapshot</th>
          <th>Correlazioni</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($versioni as $v): ?>
        <?php
          $snapshot = requisito_version_decode((string)$v["snapshot_json"]);
          $correlations = requisito_version_decode((string)$v["correlations_json"]);
          $categorieCount = count($correlations["categorie"] ?? []);
          $linksLabel = "";
          if ($type === "catalogo") {
              $groups = $correlations["regole_gruppi"] ?? [];
              $rulesCount = 0;
              foreach ($groups as $group) {
                  $rulesCount += count($group["regole"] ?? []);
              }
              $linksLabel = $categorieCount . " categorie · " . count($groups) . " gruppi · " . $rulesCount . " regole";
          } else {
              $linksLabel = $categorieCount . " categorie · " . count($correlations["questionari"] ?? []) . " questionari";
          }
        ?>
        <tr>
          <td><span class="badge text-bg-secondary">v<?= (int)$v["version_no"] ?></span></td>
          <td><?= h($v["action"]) ?></td>
          <td class="small"><?= h($v["changed_at"]) ?></td>
          <td class="small"><?= h($v["changed_by_label"] ?: "N/D") ?></td>
          <td>
            <div><code><?= h($snapshot["codice"] ?? "") ?></code></div>
            <div class="small"><?= h(short_text((string)($snapshot["titolo"] ?? ""), 90)) ?></div>
          </td>
          <td class="small"><?= h($linksLabel) ?></td>
          <td class="text-end">
            <details class="d-inline-block text-start me-2">
              <summary class="btn btn-sm btn-outline-secondary">Dettaglio</summary>
              <pre class="small bg-light border rounded p-2 mt-2" style="max-width: 680px; max-height: 360px; overflow:auto;"><?= h(requisito_version_json(["snapshot" => $snapshot, "correlazioni" => $correlations])) ?></pre>
            </details>
            <form method="post" class="d-inline" onsubmit="return confirm('Ripristinare questa versione? Verranno ripristinate anche le correlazioni salvate.')">
              <?= csrf_field() ?>
              <input type="hidden" name="version_id" value="<?= (int)$v["id"] ?>">
              <input type="hidden" name="type" value="<?= h($type) ?>">
              <input type="hidden" name="entity_id" value="<?= (int)$entityId ?>">
              <button class="btn btn-sm btn-outline-warning">Ripristina</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$versioni): ?>
        <tr><td colspan="7" class="text-center text-muted py-4">Nessuna versione salvata.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . "/../includes/footer.php"; ?>
