<?php
require_once __DIR__ . "/../includes/functions.php";
require_permission("threat_analysis", "read");

$questionari = get_questionari();
$selectedQuestionarioId = (int)($_GET["questionario_id"] ?? post("questionario_id", ($questionari[0]["id"] ?? 0)));
$selectedQuestionario = $selectedQuestionarioId > 0 ? get_questionario($selectedQuestionarioId) : false;
$analysis = isset($_GET["analysis_id"]) ? get_threat_analysis((int)$_GET["analysis_id"]) : false;
$aiProviders = get_ai_providers(true);
$selectedProviderId = (int)($_GET["provider_id"] ?? post("provider_id", (get_default_ai_provider()["id"] ?? 0)));
$selectedProvider = get_ai_provider($selectedProviderId) ?: get_default_ai_provider();
$models = ai_provider_models($selectedProvider);
$defaultModel = (string)($selectedProvider["default_model"] ?? "") ?: ($models[0] ?? "");
$promptValue = (string)post("prompt", default_threat_analysis_prompt());
$modelValue = (string)post("model", $defaultModel);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf();
    $action = (string)post("action", "generate");
    $selectedQuestionarioId = (int)post("questionario_id");
    $selectedQuestionario = get_questionario($selectedQuestionarioId);

    if ($action === "save_sections") {
        require_permission("threat_analysis", "update");
        $analysisId = (int)post("analysis_id");
        $analysisToSave = get_threat_analysis($analysisId);
        if (!$analysisToSave) {
            flash("error", "Analisi non trovata.");
            redirect(APP_BASE_URL . "/threat_analysis/index.php?questionario_id=" . $selectedQuestionarioId);
        }
        $titles = $_POST["section_title"] ?? [];
        $numbers = $_POST["section_number"] ?? [];
        $contents = $_POST["section_content_html"] ?? [];
        $sectionsPayload = [];
        foreach ((array)$titles as $index => $title) {
            $sectionsPayload[] = [
                "section_number" => (string)($numbers[$index] ?? ""),
                "title" => (string)$title,
                "content_html" => (string)($contents[$index] ?? ""),
            ];
        }
        save_threat_analysis_sections($analysisId, $sectionsPayload);
        flash("success", "Threat Analysis aggiornata.");
        redirect(APP_BASE_URL . "/threat_analysis/index.php?questionario_id=" . (int)$analysisToSave["questionario_id"] . "&analysis_id=" . $analysisId);
    }

    if ($action === "reparse_sections") {
        require_permission("threat_analysis", "update");
        $analysisId = (int)post("analysis_id");
        $analysisToParse = get_threat_analysis($analysisId);
        if (!$analysisToParse) {
            flash("error", "Analisi non trovata.");
            redirect(APP_BASE_URL . "/threat_analysis/index.php?questionario_id=" . $selectedQuestionarioId);
        }
        reparse_threat_analysis_sections($analysisId);
        flash("success", "Threat Analysis rinormalizzata dal testo IA originale.");
        redirect(APP_BASE_URL . "/threat_analysis/index.php?questionario_id=" . (int)$analysisToParse["questionario_id"] . "&analysis_id=" . $analysisId);
    }

    if ($action === "include_service") {
        require_permission("threat_analysis", "update");
        $analysisId = (int)post("analysis_id");
        $servizioId = (int)post("servizio_id");
        if (!$selectedQuestionario || $servizioId <= 0) {
            flash("error", "Seleziona un servizio valido.");
        } else {
            include_servizio_manuale($selectedQuestionarioId, $servizioId);
            flash("success", "Servizio aggiunto ai risultati del questionario.");
        }
        redirect(APP_BASE_URL . "/threat_analysis/index.php?questionario_id=" . $selectedQuestionarioId . "&analysis_id=" . $analysisId . "#section-services");
    }

    if ($action === "add_specific_requirement") {
        require_permission("requisiti_specifici", "create");
        $analysisId = (int)post("analysis_id");
        if (!$selectedQuestionario) {
            flash("error", "Questionario non valido.");
            redirect(APP_BASE_URL . "/threat_analysis/index.php");
        }
        $title = trim((string)post("candidate_title", ""));
        $description = trim((string)post("candidate_description", ""));
        if ($title === "") {
            flash("error", "Titolo requisito specifico obbligatorio.");
            redirect(APP_BASE_URL . "/threat_analysis/index.php?questionario_id=" . $selectedQuestionarioId . "&analysis_id=" . $analysisId . "#section-requirements");
        }
        $categoriaId = (int)post("categoria_id");
        if ($categoriaId <= 0) {
            $candidateCategory = trim((string)post("candidate_categoria", ""));
            $categoriaId = ensure_requisito_categoria($candidateCategory !== "" ? $candidateCategory : "Threat Analysis");
        }
        $newId = save_requisito_specifico([
            "questionario_id" => $selectedQuestionarioId,
            "task_jira" => (string)($selectedQuestionario["task_jira"] ?? ""),
            "codice" => "",
            "versione" => "1.0",
            "categoria_id" => $categoriaId,
            "titolo" => $title,
            "descrizione" => $description,
            "contesto" => "Generato da Threat Analysis #" . $analysisId,
            "note" => "Origine: selezione manuale da output IA.",
            "importanza" => (string)post("importanza", "Media"),
            "owner" => (string)post("candidate_owner", "Security"),
            "attivo" => 1,
        ]);
        get_db()->prepare("UPDATE questionario_requisiti_specifici SET codice = ? WHERE id = ? AND (codice IS NULL OR codice = '')")
            ->execute(["TA-SPEC-" . $newId, $newId]);
        flash("success", "Requisito specifico aggiunto al questionario.");
        redirect(APP_BASE_URL . "/threat_analysis/index.php?questionario_id=" . $selectedQuestionarioId . "&analysis_id=" . $analysisId . "#section-requirements");
    }

    @set_time_limit(max(60, OLLAMA_TIMEOUT_SECONDS + 30));
    require_permission("threat_analysis", "create");
    $selectedProvider = get_ai_provider((int)post("provider_id")) ?: get_default_ai_provider();
    $promptValue = trim((string)post("prompt", ""));
    $modelValue = trim((string)post("model", ""));

    if (!$selectedQuestionario) {
        flash("error", "Seleziona un questionario valido.");
        redirect(APP_BASE_URL . "/threat_analysis/index.php");
    }
    if ($promptValue === "") {
        flash("error", "Inserisci un prompt per la Threat Analysis.");
        redirect(APP_BASE_URL . "/threat_analysis/index.php?questionario_id=" . $selectedQuestionarioId);
    }

    $context = build_questionario_ai_context($selectedQuestionarioId);
    if (!$context) {
        flash("error", "Impossibile costruire il contesto del questionario.");
        redirect(APP_BASE_URL . "/threat_analysis/index.php?questionario_id=" . $selectedQuestionarioId);
    }
    $fullPrompt = threat_analysis_full_prompt($promptValue, $context);
    $result = ai_generate($selectedProvider, $modelValue, $fullPrompt);
    if ($result["ok"]) {
        $analysisId = save_threat_analysis($selectedQuestionarioId, (string)$result["model"], ai_provider_base_url($selectedProvider), $promptValue, $context, (string)$result["response"], "ok");
        flash("success", "Threat Analysis generata.");
        redirect(APP_BASE_URL . "/threat_analysis/index.php?questionario_id=" . $selectedQuestionarioId . "&analysis_id=" . $analysisId);
    }

    $analysisId = save_threat_analysis($selectedQuestionarioId, $modelValue, ai_provider_base_url($selectedProvider), $promptValue, $context, "", "error", (string)$result["error"]);
    flash("error", "Errore IA: " . (string)$result["error"]);
    redirect(APP_BASE_URL . "/threat_analysis/index.php?questionario_id=" . $selectedQuestionarioId . "&analysis_id=" . $analysisId);
}

$history = get_threat_analyses($selectedQuestionarioId);
$canCreate = has_permission("threat_analysis", "create");
$canUpdate = has_permission("threat_analysis", "update");
$sections = ($analysis && (string)$analysis["status"] === "ok") ? ensure_threat_analysis_sections((int)$analysis["id"]) : [];
$serviziCatalogo = get_servizi(true);
$categorieRequisiti = get_requisito_categorie(true);
$page_title = "Threat Analysis";
require __DIR__ . "/../includes/header.php";
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h1 class="h4 mb-0"><i class="bi bi-cpu me-2"></i>Threat Analysis</h1>
    <div class="text-muted">Invia questionario e prompt al provider IA scelto per produrre una Threat Analysis operativa.</div>
  </div>
</div>

<div class="row g-4">
  <div class="col-xl-5">
    <div class="card shadow-sm">
      <div class="card-header fw-semibold">Nuova analisi</div>
      <div class="card-body">
        <form method="post" class="row g-3" id="threatAnalysisForm">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="generate">
          <div class="col-12">
            <label class="form-label">Questionario</label>
            <select class="form-select" name="questionario_id" onchange="location.href='<?= APP_BASE_URL ?>/threat_analysis/index.php?provider_id=<?= (int)($selectedProvider['id'] ?? 0) ?>&questionario_id=' + this.value" required>
              <?php foreach ($questionari as $q): ?>
                <option value="<?= (int)$q["id"] ?>" <?= (int)$q["id"] === $selectedQuestionarioId ? "selected" : "" ?>>
                  #<?= (int)$q["id"] ?> · <?= h($q["nome_progetto"] ?: "Senza nome") ?><?= $q["task_jira"] ? " · " . h($q["task_jira"]) : "" ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Provider IA</label>
            <select class="form-select" name="provider_id" onchange="location.href='<?= APP_BASE_URL ?>/threat_analysis/index.php?questionario_id=<?= (int)$selectedQuestionarioId ?>&provider_id=' + this.value" <?= $canCreate ? "" : "disabled" ?>>
              <?php foreach ($aiProviders as $provider): ?>
                <option value="<?= (int)$provider["id"] ?>" <?= (int)$provider["id"] === (int)($selectedProvider["id"] ?? 0) ? "selected" : "" ?>>
                  <?= h($provider["nome"]) ?> · <?= h(ai_provider_types()[$provider["provider_type"]] ?? $provider["provider_type"]) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <div class="form-text">Endpoint lato container PHP: <code><?= h(ai_provider_base_url($selectedProvider)) ?></code></div>
          </div>
          <div class="col-md-6">
            <label class="form-label">Modello IA</label>
            <?php if ($models): ?>
              <select class="form-select" name="model" <?= $canCreate ? "" : "disabled" ?>>
                <?php foreach ($models as $model): ?>
                  <option value="<?= h($model) ?>" <?= $modelValue === $model ? "selected" : "" ?>><?= h($model) ?></option>
                <?php endforeach; ?>
              </select>
            <?php else: ?>
              <input class="form-control" name="model" value="<?= h($modelValue) ?>" placeholder="es. cybersec:latest / gpt-4.1" <?= $canCreate ? "" : "disabled" ?>>
            <?php endif; ?>
          </div>
          <div class="col-12">
            <label class="form-label">Stato provider IA</label>
            <input class="form-control <?= $models ? "is-valid" : "is-warning" ?>" value="<?= $models ? count($models) . " modelli disponibili" : "Modelli non letti: configura lista modelli o verifica endpoint" ?>" disabled>
          </div>
          <div class="col-12">
            <label class="form-label">Prompt IA</label>
            <textarea class="form-control font-monospace" name="prompt" rows="20" <?= $canCreate ? "" : "disabled" ?>><?= h($promptValue) ?></textarea>
            <div class="form-text">Il sistema aggiunge automaticamente sotto al prompt il contesto JSON del questionario: risposte, requisiti, requisiti standard, specifici e servizi.</div>
          </div>
          <div class="col-12 d-grid">
            <?php if ($canCreate): ?>
              <button class="btn btn-primary" id="generateThreatBtn">
                <i class="bi bi-magic me-1"></i>Genera Threat Analysis
              </button>
            <?php else: ?>
              <span class="badge text-bg-secondary">Sola lettura</span>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-xl-7">
    <?php if ($selectedQuestionario): ?>
      <div class="card shadow-sm mb-4">
        <div class="card-header fw-semibold">Contesto selezionato</div>
        <div class="card-body small">
          <div class="row g-2">
            <div class="col-md-6"><strong>Progetto:</strong> <?= h($selectedQuestionario["nome_progetto"] ?? "") ?></div>
            <div class="col-md-3"><strong>Task <?= acronym_help('JIRA', 'Identificativo del task su Jira') ?>:</strong> <?= h($selectedQuestionario["task_jira"] ?? "") ?></div>
            <div class="col-md-3"><strong>Business line:</strong> <?= h($selectedQuestionario["business_line"] ?? "") ?></div>
            <div class="col-md-6"><strong>Servizio:</strong> <?= h($selectedQuestionario["nome_servizio"] ?? "") ?></div>
            <div class="col-md-6"><strong>Analista:</strong> <?= h($selectedQuestionario["analista_questionario_nome"] ?? "") ?></div>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <div class="card shadow-sm mb-4 d-none" id="threatProgressCard">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span class="fw-semibold">Generazione in corso</span>
        <span class="badge text-bg-info" id="threatProgressBadge">In attesa</span>
      </div>
      <div class="card-body">
        <div class="progress mb-3" role="progressbar" aria-label="Generazione Threat Analysis">
          <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
        </div>
        <div class="small text-muted mb-2">
          <span id="threatProgressStatus">Preparazione...</span>
          <span class="ms-2" id="threatProgressElapsed"></span>
        </div>
        <div class="small text-muted mb-2">Il testo viene mostrato appena il provider IA invia i token: se il modello è freddo potresti vedere solo lo stato per qualche minuto.</div>
        <pre class="border rounded bg-light p-3 mb-3" id="threatLiveOutput" style="white-space: pre-wrap; max-height: 520px; overflow:auto;"></pre>
        <a class="btn btn-sm btn-outline-primary d-none" id="threatOpenResult" href="#">Apri analisi salvata</a>
      </div>
    </div>

    <?php if ($analysis): ?>
      <div class="card shadow-sm mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span class="fw-semibold">Analisi #<?= (int)$analysis["id"] ?> · <?= h($analysis["model_name"]) ?></span>
          <div class="d-flex gap-2 align-items-center">
            <?php if ($analysis["status"] === "ok"): ?>
              <a class="btn btn-sm btn-outline-danger" href="<?= APP_BASE_URL ?>/threat_analysis/export.php?analysis_id=<?= (int)$analysis["id"] ?>">
                <i class="bi bi-file-earmark-pdf me-1"></i>PDF
              </a>
            <?php endif; ?>
            <span class="badge text-bg-<?= $analysis["status"] === "ok" ? "success" : "danger" ?>"><?= h($analysis["status"]) ?></span>
          </div>
        </div>
        <div class="card-body">
          <?php if ($analysis["status"] === "error"): ?>
            <div class="alert alert-danger"><?= h($analysis["error_message"] ?? "Errore non specificato") ?></div>
          <?php else: ?>
            <form method="post" id="threatSectionsForm">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="save_sections">
              <input type="hidden" name="analysis_id" value="<?= (int)$analysis["id"] ?>">
              <input type="hidden" name="questionario_id" value="<?= (int)$analysis["questionario_id"] ?>">
            </form>
              <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="small text-muted">Output normalizzato in sezioni modificabili. Le sezioni requisiti e servizi possono alimentare direttamente il questionario.</div>
                <div class="d-flex gap-2">
                  <?php if ($canUpdate): ?>
                    <form method="post" onsubmit="return confirm('Rinormalizzare dal testo IA originale? Le modifiche manuali alle sezioni verranno perse.');">
                      <?= csrf_field() ?>
                      <input type="hidden" name="action" value="reparse_sections">
                      <input type="hidden" name="analysis_id" value="<?= (int)$analysis["id"] ?>">
                      <input type="hidden" name="questionario_id" value="<?= (int)$analysis["questionario_id"] ?>">
                      <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-repeat me-1"></i>Rinormalizza</button>
                    </form>
                    <button class="btn btn-sm btn-primary" form="threatSectionsForm"><i class="bi bi-save me-1"></i>Salva documento</button>
                  <?php endif; ?>
                </div>
              </div>
              <div class="accordion" id="threatSectionsAccordion">
                <?php foreach ($sections as $index => $section): ?>
                  <?php
                    $kind = threat_analysis_section_kind($section);
                    $collapseId = "threatSection" . (int)$section["id"];
                    $headingId = "headingThreatSection" . (int)$section["id"];
                    $isReq = $kind === "requirements";
                    $isSrv = $kind === "services";
                    $reqCandidates = $isReq ? threat_analysis_requirement_candidates($section) : [];
                    $srvCandidates = $isSrv ? threat_analysis_service_candidates($section) : [];
                  ?>
                  <div class="accordion-item" id="<?= $isReq ? "section-requirements" : ($isSrv ? "section-services" : "section-" . (int)$section["id"]) ?>">
                    <h2 class="accordion-header" id="<?= h($headingId) ?>">
                      <button class="accordion-button <?= $index === 0 ? "" : "collapsed" ?>" type="button" data-bs-toggle="collapse" data-bs-target="#<?= h($collapseId) ?>" aria-expanded="<?= $index === 0 ? "true" : "false" ?>" aria-controls="<?= h($collapseId) ?>">
                        <span class="badge text-bg-secondary me-2"><?= h($section["section_number"] ?: (string)($index + 1)) ?></span>
                        <?= h($section["title"]) ?>
                        <?php if ($isReq): ?><span class="badge text-bg-warning ms-2">requisiti specifici</span><?php endif; ?>
                        <?php if ($isSrv): ?><span class="badge text-bg-info ms-2">servizi</span><?php endif; ?>
                      </button>
                    </h2>
                    <div id="<?= h($collapseId) ?>" class="accordion-collapse collapse <?= $index === 0 ? "show" : "" ?>" data-bs-parent="#threatSectionsAccordion">
                      <div class="accordion-body">
                        <input type="hidden" name="section_number[]" value="<?= h($section["section_number"]) ?>" form="threatSectionsForm">
                        <div class="mb-2">
                          <label class="form-label small text-muted">Titolo sezione</label>
                          <input class="form-control" name="section_title[]" value="<?= h($section["title"]) ?>" form="threatSectionsForm" <?= $canUpdate ? "" : "disabled" ?>>
                        </div>
                        <textarea class="form-control threat-tinymce" name="section_content_html[]" form="threatSectionsForm" rows="12" <?= $canUpdate ? "" : "disabled" ?>><?= h(threat_analysis_sanitize_html((string)$section["content_html"])) ?></textarea>

                        <?php if ($isReq): ?>
                          <div class="border rounded bg-light p-3 mt-3">
                            <div class="fw-semibold mb-2"><i class="bi bi-stars me-1"></i>Seleziona requisiti da aggiungere ai requisiti specifici di progetto</div>
                            <?php foreach ($reqCandidates as $candidate): ?>
                              <form method="post" class="row g-2 align-items-end border-top pt-2 mt-2">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="add_specific_requirement">
                                <input type="hidden" name="analysis_id" value="<?= (int)$analysis["id"] ?>">
                                <input type="hidden" name="questionario_id" value="<?= (int)$analysis["questionario_id"] ?>">
                                <div class="col-md-4">
                                  <label class="form-label small">Titolo</label>
                                  <input class="form-control form-control-sm" name="candidate_title" value="<?= h($candidate["title"]) ?>" required>
                                </div>
                                <div class="col-md-3">
                                  <label class="form-label small">Categoria anagrafica</label>
                                  <select class="form-select form-select-sm" name="categoria_id">
                                    <option value="0">Threat Analysis</option>
                                    <?php foreach ($categorieRequisiti as $categoria): ?>
                                      <option value="<?= (int)$categoria["id"] ?>"><?= h($categoria["nome"]) ?></option>
                                    <?php endforeach; ?>
                                  </select>
                                </div>
                                <div class="col-md-2">
                                  <label class="form-label small">Importanza</label>
                                  <select class="form-select form-select-sm" name="importanza">
                                    <?php foreach (["MUST", "SHOULD", "MAY", "Alta", "Media", "Bassa"] as $importance): ?>
                                      <option value="<?= h($importance) ?>" <?= strcasecmp((string)($candidate["importanza"] ?? ""), $importance) === 0 ? "selected" : "" ?>><?= h($importance) ?></option>
                                    <?php endforeach; ?>
                                  </select>
                                </div>
                                <div class="col-md-3">
                                  <label class="form-label small">Owner</label>
                                  <input class="form-control form-control-sm" name="candidate_owner" value="<?= h(($candidate["owner"] ?? "") ?: "Security") ?>">
                                </div>
                                <div class="col-md-4">
                                  <label class="form-label small">Categoria IA</label>
                                  <input class="form-control form-control-sm" name="candidate_categoria" value="<?= h($candidate["categoria"] ?? "") ?>" placeholder="Usata se non scegli una categoria anagrafica">
                                </div>
                                <div class="col-md-8 d-grid">
                                  <button class="btn btn-sm btn-outline-primary" <?= has_permission("requisiti_specifici", "create") ? "" : "disabled" ?>>Aggiungi</button>
                                </div>
                                <div class="col-12">
                                  <textarea class="form-control form-control-sm" name="candidate_description" rows="2"><?= h($candidate["description"]) ?></textarea>
                                </div>
                              </form>
                            <?php endforeach; ?>
                            <?php if (!$reqCandidates): ?>
                              <div class="text-muted small">Nessun requisito candidato riconosciuto automaticamente in questa sezione.</div>
                            <?php endif; ?>
                          </div>
                        <?php endif; ?>

                        <?php if ($isSrv): ?>
                          <div class="border rounded bg-light p-3 mt-3">
                            <div class="fw-semibold mb-2"><i class="bi bi-diagram-3 me-1"></i>Seleziona servizi da aggiungere ai risultati del questionario</div>
                            <?php foreach ($srvCandidates as $candidate): ?>
                              <form method="post" class="row g-2 align-items-end border-top pt-2 mt-2">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="include_service">
                                <input type="hidden" name="analysis_id" value="<?= (int)$analysis["id"] ?>">
                                <input type="hidden" name="questionario_id" value="<?= (int)$analysis["questionario_id"] ?>">
                                <div class="col-md-5 small text-muted"><?= h($candidate["text"]) ?></div>
                                <div class="col-md-5">
                                  <label class="form-label small">Servizio catalogo</label>
                                  <select class="form-select form-select-sm" name="servizio_id" required>
                                    <option value="">Seleziona servizio...</option>
                                    <?php foreach ($serviziCatalogo as $servizio): ?>
                                      <option value="<?= (int)$servizio["id"] ?>" <?= (int)$candidate["servizio_id"] === (int)$servizio["id"] ? "selected" : "" ?>>
                                        <?= h($servizio["servizio_elementare"]) ?><?= $servizio["macro_service"] ? " · " . h($servizio["macro_service"]) : "" ?>
                                      </option>
                                    <?php endforeach; ?>
                                  </select>
                                </div>
                                <div class="col-md-2 d-grid">
                                  <button class="btn btn-sm btn-outline-primary" <?= $canUpdate ? "" : "disabled" ?>>Aggiungi</button>
                                </div>
                              </form>
                            <?php endforeach; ?>
                            <?php if (!$srvCandidates): ?>
                              <div class="text-muted small">Nessun servizio candidato riconosciuto automaticamente in questa sezione.</div>
                            <?php endif; ?>
                          </div>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

    <div class="card shadow-sm">
      <div class="card-header fw-semibold">Storico analisi</div>
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
          <thead><tr><th>Data</th><th>Questionario</th><th>Modello</th><th>Stato</th><th>Utente</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($history as $item): ?>
            <tr>
              <td class="small"><?= h($item["created_at"]) ?></td>
              <td><code>#<?= (int)$item["questionario_id"] ?></code> <?= h(short_text((string)$item["nome_progetto"], 50)) ?></td>
              <td><?= h($item["model_name"]) ?></td>
              <td><span class="badge text-bg-<?= $item["status"] === "ok" ? "success" : "danger" ?>"><?= h($item["status"]) ?></span></td>
              <td class="small"><?= h($item["creato_da"] ?: "N/D") ?></td>
              <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?= APP_BASE_URL ?>/threat_analysis/index.php?questionario_id=<?= (int)$item["questionario_id"] ?>&analysis_id=<?= (int)$item["id"] ?>">Apri</a></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$history): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">Nessuna analisi presente per questo questionario.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.tiny.cloud/1/5xc88k7aeeqzopd0r7coy0i1y3h0tvfr3klt143qd9wl8itp/tinymce/8/tinymce.min.js" referrerpolicy="origin" crossorigin="anonymous"></script>
<script>
(() => {
  if (window.tinymce) {
    tinymce.init({
      selector: 'textarea.threat-tinymce',
      language: 'it',
      height: 360,
      menubar: false,
      branding: false,
      promotion: false,
      plugins: 'autolink link lists table searchreplace visualblocks wordcount code codesample autoresize',
      toolbar: 'undo redo | blocks | bold italic underline strikethrough | bullist numlist outdent indent | link table | searchreplace visualblocks code removeformat',
      table_default_attributes: { class: 'table table-sm table-bordered align-middle' },
      table_class_list: [
        { title: 'Tabella Bootstrap compatta', value: 'table table-sm table-bordered align-middle' },
        { title: 'Tabella semplice', value: 'table' }
      ],
      content_style: 'body{font-family:system-ui,-apple-system,Segoe UI,sans-serif;font-size:14px} table{width:100%;border-collapse:collapse} th,td{border:1px solid #dee2e6;padding:.35rem} th{background:#f8f9fa}'
    });
  }
  const sectionsForm = document.getElementById('threatSectionsForm');
  if (sectionsForm) {
    sectionsForm.addEventListener('submit', () => {
      if (window.tinymce) {
        tinymce.triggerSave();
      }
    });
  }

  const form = document.getElementById('threatAnalysisForm');
  if (!form || !window.fetch || !window.ReadableStream) return;

  const card = document.getElementById('threatProgressCard');
  const badge = document.getElementById('threatProgressBadge');
  const statusBox = document.getElementById('threatProgressStatus');
  const elapsedBox = document.getElementById('threatProgressElapsed');
  const output = document.getElementById('threatLiveOutput');
  const resultLink = document.getElementById('threatOpenResult');
  const button = document.getElementById('generateThreatBtn');
  let elapsedTimer = null;

  function setStatus(message, tone = 'info') {
    statusBox.textContent = message;
    badge.className = 'badge text-bg-' + tone;
    badge.textContent = message;
  }

  function startElapsedTimer() {
    const startedAt = Date.now();
    clearInterval(elapsedTimer);
    elapsedTimer = setInterval(() => {
      const seconds = Math.floor((Date.now() - startedAt) / 1000);
      const minutes = Math.floor(seconds / 60);
      elapsedBox.textContent = 'Tempo trascorso: ' + minutes + ':' + String(seconds % 60).padStart(2, '0');
    }, 1000);
  }

  function stopElapsedTimer() {
    clearInterval(elapsedTimer);
    elapsedTimer = null;
  }

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    card.classList.remove('d-none');
    resultLink.classList.add('d-none');
    output.textContent = '';
    button.disabled = true;
    elapsedBox.textContent = '';
    startElapsedTimer();
    setStatus('Preparo richiesta...', 'info');

    try {
      const response = await fetch('<?= APP_BASE_URL ?>/threat_analysis/generate_stream.php', {
        method: 'POST',
        body: new FormData(form),
        credentials: 'same-origin'
      });
      if (!response.ok || !response.body) {
        throw new Error('Risposta HTTP non valida: ' + response.status);
      }

      const reader = response.body.getReader();
      const decoder = new TextDecoder();
      let buffer = '';
      while (true) {
        const { value, done } = await reader.read();
        if (done) break;
        buffer += decoder.decode(value, { stream: true });
        const lines = buffer.split('\n');
        buffer = lines.pop() || '';
        for (const line of lines) {
          if (!line.trim()) continue;
          const event = JSON.parse(line);
          if (event.type === 'status') {
            setStatus(event.message || 'Generazione in corso...', 'info');
          } else if (event.type === 'chunk') {
            output.textContent += event.text || '';
            output.scrollTop = output.scrollHeight;
            setStatus('La IA sta rispondendo...', 'primary');
          } else if (event.type === 'done') {
            setStatus('Completata', 'success');
            stopElapsedTimer();
            if (event.analysis_id) {
              resultLink.href = '<?= APP_BASE_URL ?>/threat_analysis/index.php?questionario_id=' + encodeURIComponent(form.questionario_id.value) + '&analysis_id=' + encodeURIComponent(event.analysis_id);
              resultLink.classList.remove('d-none');
            }
          } else if (event.type === 'error') {
            setStatus('Errore', 'danger');
            stopElapsedTimer();
            output.textContent += '\n\n[ERRORE] ' + (event.message || 'Errore non specificato');
          }
        }
      }
    } catch (error) {
      setStatus('Errore', 'danger');
      stopElapsedTimer();
      output.textContent += '\n\n[ERRORE] ' + error.message;
    } finally {
      button.disabled = false;
    }
  });
})();
</script>

<?php require __DIR__ . "/../includes/footer.php"; ?>
