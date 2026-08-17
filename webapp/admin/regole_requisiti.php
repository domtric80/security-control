<?php
require_once __DIR__ . "/../includes/functions.php";
require_permission("regole_requisiti", "read");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf();
    $filter_dom = (int)post("domanda_id_filter");
    $filter_req = (int)post("requisito_id_filter");
    $action = (string)post("action", "add_rule");

    if ($action === "delete_rule") {
        require_permission("regole_requisiti", "delete");
        delete_regola_requisito((int)post("id"));
        flash("success", "Regola eliminata.");
    } elseif ($action === "update_rule") {
        require_permission("regole_requisiti", "update");
        update_regola_requisito(
            (int)post("id"),
            (int)post("gruppo_id"),
            (int)post("domanda_id"),
            (string)post("valore_atteso", "1")
        );
        flash("success", "Regola aggiornata.");
    } elseif ($action === "save_group") {
        require_permission("regole_requisiti", (int)post("gruppo_id") > 0 ? "update" : "create");
        save_regole_requisiti_gruppo(
            (int)post("requisito_id"),
            (string)post("nome"),
            (string)post("operatore_logico", "OR"),
            (int)post("ordine"),
            (int)post("gruppo_id")
        );
        flash("success", "Gruppo regole salvato.");
    } elseif ($action === "delete_group") {
        require_permission("regole_requisiti", "delete");
        delete_regole_requisiti_gruppo((int)post("gruppo_id"), (int)post("requisito_id"));
        flash("success", "Gruppo regole eliminato con le sue condizioni.");
    } else {
        require_permission("regole_requisiti", "create");
        $dom_id = (int)post("domanda_id");
        $req_id = (int)post("requisito_id");
        $gruppo_id = (int)post("gruppo_id");
        $val = trim((string)post("valore_atteso", "1"));
        if ($dom_id && $req_id && $gruppo_id) {
            save_regola_requisito($dom_id, $val, $req_id, "OR", $gruppo_id);
            flash("success", "Regola aggiunta al gruppo.");
        }
        $filter_req = $req_id ?: $filter_req;
    }
    $qs = http_build_query(array_filter(["domanda_id" => $filter_dom, "requisito_id" => $filter_req]));
    redirect(APP_BASE_URL . "/admin/regole_requisiti.php" . ($qs ? "?$qs" : ""));
}

$filter_dom = isset($_GET["domanda_id"]) ? (int)$_GET["domanda_id"] : null;
$filter_req = isset($_GET["requisito_id"]) ? (int)$_GET["requisito_id"] : null;
$filter_gruppo = trim((string)($_GET["gruppo"] ?? ""));

$regole = get_regole_requisiti($filter_dom, $filter_req);
$domande = get_domande(true);
$requisiti = get_requisiti(true);
$selected_requisito = $filter_req ? get_requisito($filter_req) : null;
$gruppi = $selected_requisito ? get_regole_requisiti_gruppi((int)$selected_requisito["id"]) : get_all_regole_requisiti_gruppi($filter_gruppo);
$page_title = "Regole Requisiti";
require __DIR__ . "/../includes/header.php";
?>

<div class="row g-4">
  <div class="col-lg-4">
    <div class="card shadow-sm">
      <div class="card-header fw-semibold">Filtra requisito</div>
      <div class="card-body">
        <form method="get" class="row g-2">
          <div class="col-12">
            <label class="form-label small">Requisito</label>
            <select class="form-select form-select-sm" name="requisito_id">
              <option value="">-- seleziona requisito --</option>
              <?php foreach ($requisiti as $r): ?>
              <option value="<?= (int)$r["id"] ?>" <?= (int)$r["id"] === $filter_req ? "selected" : "" ?>>
                <?= h($r["codice"]) ?> - <?= h(short_text($r["titolo"], 70)) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label small">Domanda</label>
            <select class="form-select form-select-sm" name="domanda_id">
              <option value="">-- tutte --</option>
              <?php foreach ($domande as $d): ?>
              <option value="<?= (int)$d["id"] ?>" <?= (int)$d["id"] === $filter_dom ? "selected" : "" ?>>
                <?= h($d["codice"]) ?> — <?= h(short_text($d["testo"], 95)) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label small">Nome gruppo</label>
            <input class="form-control form-control-sm" name="gruppo" value="<?= h($filter_gruppo) ?>" placeholder="Es. Interfacce esposte">
          </div>
          <div class="col-12"><button class="btn btn-outline-secondary w-100 btn-sm">Apri gruppi</button></div>
        </form>
      </div>
    </div>

    <?php if ($selected_requisito): ?>
    <div class="card shadow-sm mt-3">
      <div class="card-header fw-semibold">Nuovo gruppo regole</div>
      <div class="card-body">
        <form method="post" class="row g-3">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="save_group">
          <input type="hidden" name="requisito_id" value="<?= (int)$selected_requisito["id"] ?>">
          <input type="hidden" name="requisito_id_filter" value="<?= (int)$selected_requisito["id"] ?>">
          <input type="hidden" name="domanda_id_filter" value="<?= (int)$filter_dom ?>">
          <div class="col-12"><label class="form-label">Nome gruppo</label><input class="form-control" name="nome" placeholder="Es. Web pubblico + dati personali" required></div>
          <div class="col-md-8"><label class="form-label">Operatore nel gruppo</label>
            <select class="form-select" name="operatore_logico">
              <option value="AND">AND - tutte le condizioni vere</option>
              <option value="OR">OR - almeno una condizione vera</option>
            </select>
          </div>
          <div class="col-md-4"><label class="form-label">Ordine</label><input class="form-control" name="ordine" type="number" value="<?= count($gruppi) + 1 ?>"></div>
          <div class="col-12"><button class="btn btn-primary w-100">Crea gruppo</button></div>
        </form>
      </div>
    </div>

    <div class="card shadow-sm mt-3">
      <div class="card-header fw-semibold">Aggiungi condizione</div>
      <div class="card-body">
        <?php if (!$gruppi): ?>
          <div class="text-muted small">Crea prima almeno un gruppo.</div>
        <?php else: ?>
        <form method="post" class="row g-3">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="add_rule">
          <input type="hidden" name="requisito_id" value="<?= (int)$selected_requisito["id"] ?>">
          <input type="hidden" name="requisito_id_filter" value="<?= (int)$selected_requisito["id"] ?>">
          <input type="hidden" name="domanda_id_filter" value="<?= (int)$filter_dom ?>">
          <div class="col-12"><label class="form-label">Gruppo</label>
            <select class="form-select" name="gruppo_id" required>
              <?php foreach ($gruppi as $g): ?><option value="<?= (int)$g["id"] ?>"><?= h($g["nome"]) ?> (<?= h($g["operatore_logico"]) ?>)</option><?php endforeach; ?>
            </select>
          </div>
          <div class="col-12"><label class="form-label">Domanda</label>
            <select class="form-select" name="domanda_id" required>
              <?php foreach ($domande as $d): ?>
                <option value="<?= (int)$d["id"] ?>"><?= h($d["codice"]) ?> — <?= h(short_text($d["testo"], 95)) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12"><label class="form-label">Valore atteso</label><input class="form-control" name="valore_atteso" value="1"></div>
          <div class="col-12"><button class="btn btn-primary w-100">Aggiungi al gruppo</button></div>
        </form>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <div class="col-lg-8">
    <?php if (!$selected_requisito): ?>
      <div class="alert alert-info">
        Sto mostrando <strong>tutti i gruppi di regole requisiti</strong>. Seleziona un requisito a sinistra per creare nuovi gruppi o aggiungere condizioni.
      </div>
    <?php else: ?>
      <div class="alert alert-light border">
        <strong><?= h($selected_requisito["codice"]) ?></strong> - <?= h($selected_requisito["titolo"]) ?><br>
        Il requisito viene assegnato se <strong>almeno uno</strong> dei gruppi sotto risulta vero.
      </div>
    <?php endif; ?>

      <div class="accordion" id="accordionRegoleRequisiti">
      <?php foreach ($gruppi as $gruppo): ?>
        <?php $groupRules = get_regole_requisiti_by_gruppo((int)$gruppo["id"]); ?>
        <?php $collapseId = 'reqGroup' . (int)$gruppo["id"]; ?>
        <div class="accordion-item mb-2 shadow-sm">
          <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#<?= h($collapseId) ?>">
              <span class="me-2 badge text-bg-<?= $gruppo["operatore_logico"] === "AND" ? "warning" : "info" ?>"><?= h($gruppo["operatore_logico"]) ?></span>
              <span class="fw-semibold me-2"><?= h($gruppo["nome"]) ?></span>
              <span class="text-muted small">
                <?php if (!$selected_requisito): ?>
                  <code><?= h($gruppo["req_codice"] ?? '') ?></code> ·
                <?php endif; ?>
                <?= count($groupRules) ?> condizioni
              </span>
            </button>
          </h2>
          <div id="<?= h($collapseId) ?>" class="accordion-collapse collapse" data-bs-parent="#accordionRegoleRequisiti">
          <div class="accordion-body">
            <?php if (!$selected_requisito): ?>
              <div class="small text-muted mb-2">
                Requisito: <code><?= h($gruppo["req_codice"] ?? '') ?></code> - <?= h(short_text($gruppo["req_titolo"] ?? '', 110)) ?>
              </div>
            <?php endif; ?>
            <form method="post" class="row g-2 align-items-end">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="save_group">
              <input type="hidden" name="gruppo_id" value="<?= (int)$gruppo["id"] ?>">
              <input type="hidden" name="requisito_id" value="<?= (int)$gruppo["requisito_id"] ?>">
              <input type="hidden" name="requisito_id_filter" value="<?= $selected_requisito ? (int)$selected_requisito["id"] : '' ?>">
              <input type="hidden" name="domanda_id_filter" value="<?= (int)$filter_dom ?>">
              <div class="col-md-5"><label class="form-label small">Gruppo</label><input class="form-control form-control-sm" name="nome" value="<?= h($gruppo["nome"]) ?>"></div>
              <div class="col-md-3"><label class="form-label small">Operatore</label>
                <select class="form-select form-select-sm" name="operatore_logico">
                  <option value="AND" <?= $gruppo["operatore_logico"] === "AND" ? "selected" : "" ?>>AND</option>
                  <option value="OR" <?= $gruppo["operatore_logico"] === "OR" ? "selected" : "" ?>>OR</option>
                </select>
              </div>
              <div class="col-md-2"><label class="form-label small">Ordine</label><input class="form-control form-control-sm" type="number" name="ordine" value="<?= (int)$gruppo["ordine"] ?>"></div>
              <div class="col-md-2 d-flex gap-1">
                <button class="btn btn-sm btn-outline-primary">Salva</button>
                <button class="btn btn-sm btn-outline-danger" name="action" value="delete_group" onclick="return confirm('Eliminare il gruppo e tutte le sue condizioni?')">Elimina</button>
              </div>
            </form>

            <form method="post" class="row g-2 align-items-end mt-3 mb-3 p-2 bg-light border rounded">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="add_rule">
              <input type="hidden" name="requisito_id" value="<?= (int)$gruppo["requisito_id"] ?>">
              <input type="hidden" name="gruppo_id" value="<?= (int)$gruppo["id"] ?>">
              <input type="hidden" name="requisito_id_filter" value="<?= $selected_requisito ? (int)$selected_requisito["id"] : '' ?>">
              <input type="hidden" name="domanda_id_filter" value="<?= (int)$filter_dom ?>">
              <div class="col-md-7">
                <label class="form-label small">Aggiungi domanda al gruppo</label>
                <select class="form-select form-select-sm" name="domanda_id" required>
                  <?php foreach ($domande as $d): ?>
                    <option value="<?= (int)$d["id"] ?>"><?= h($d["codice"]) ?> — <?= h(short_text($d["testo"], 95)) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label small">Valore atteso</label>
                <input class="form-control form-control-sm" name="valore_atteso" value="1">
              </div>
              <div class="col-md-2">
                <button class="btn btn-sm btn-primary w-100">Aggiungi</button>
              </div>
            </form>

          <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
              <thead><tr><th>Domanda</th><th>Valore atteso</th><th>Gruppo</th><th></th></tr></thead>
              <tbody>
              <?php foreach ($groupRules as $reg): ?>
              <?php $updateFormId = "updReqRule" . (int)$reg["id"]; ?>
              <tr>
                <td>
                  <form id="<?= h($updateFormId) ?>" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update_rule">
                    <input type="hidden" name="id" value="<?= (int)$reg["id"] ?>">
                    <input type="hidden" name="requisito_id_filter" value="<?= $selected_requisito ? (int)$selected_requisito["id"] : '' ?>">
                    <input type="hidden" name="domanda_id_filter" value="<?= (int)$filter_dom ?>">
                    <select class="form-select form-select-sm" name="domanda_id">
                      <?php foreach ($domande as $d): ?>
                        <option value="<?= (int)$d["id"] ?>" <?= (int)$d["id"] === (int)$reg["domanda_id"] ? "selected" : "" ?>>
                          <?= h($d["codice"]) ?> — <?= h(short_text($d["testo"], 95)) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </form>
                </td>
                <td><input class="form-control form-control-sm" form="<?= h($updateFormId) ?>" name="valore_atteso" value="<?= h($reg["valore_atteso"]) ?>"></td>
                <td>
                    <select class="form-select form-select-sm" form="<?= h($updateFormId) ?>" name="gruppo_id">
                      <?php foreach ($gruppi as $g): ?>
                        <?php if ((int)$g["requisito_id"] === (int)$gruppo["requisito_id"]): ?>
                        <option value="<?= (int)$g["id"] ?>" <?= (int)$g["id"] === (int)$gruppo["id"] ? "selected" : "" ?>><?= h($g["nome"]) ?></option>
                        <?php endif; ?>
                      <?php endforeach; ?>
                    </select>
                </td>
                <td class="text-end text-nowrap">
                    <button class="btn btn-sm btn-outline-primary" form="<?= h($updateFormId) ?>">Salva</button>
                    <form method="post" class="d-inline" onsubmit="return confirm('Eliminare questa condizione?')">
                      <?= csrf_field() ?>
                      <input type="hidden" name="action" value="delete_rule">
                      <input type="hidden" name="id" value="<?= (int)$reg["id"] ?>">
                      <input type="hidden" name="requisito_id_filter" value="<?= $selected_requisito ? (int)$selected_requisito["id"] : '' ?>">
                      <input type="hidden" name="domanda_id_filter" value="<?= (int)$filter_dom ?>">
                      <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (!$groupRules): ?><tr><td colspan="4" class="text-center text-muted py-3">Nessuna condizione in questo gruppo.</td></tr><?php endif; ?>
              </tbody>
            </table>
          </div>
          </div>
          </div>
        </div>
      <?php endforeach; ?>
      </div>
      <?php if (!$gruppi): ?><div class="text-muted">Nessun gruppo presente per questo requisito.</div><?php endif; ?>
  </div>
</div>

<?php require __DIR__ . "/../includes/footer.php"; ?>
