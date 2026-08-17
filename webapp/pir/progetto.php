<?php
require_once __DIR__ . '/../includes/functions.php';
require_permission('pir', 'read');

$id = get_int('id');
$questionario = get_questionario($id);
if (!$questionario) {
    http_response_code(404);
    exit('Questionario non trovato.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)post('action');
    if (in_array($action, ['delete_meeting', 'delete_attachment'], true)) {
        require_permission('pir', 'delete');
    } elseif (in_array($action, ['add_meeting', 'add_attachment', 'add_link', 'add_file'], true)) {
        require_permission('pir', 'create');
    } else {
        require_permission('pir', 'update');
    }
    $returnAnchor = preg_replace('/[^A-Za-z0-9_-]/', '', (string)post('return_anchor', ''));
    if ($action === 'save_requirement') {
        $result = save_pir_requirement_review(
            $id,
            (string)post('requisito_tipo'),
            (int)post('requisito_ref_id'),
            $_POST
        );
        flash($result['ok'] ? 'success' : 'error', $result['message']);
    } elseif ($action === 'update_pir_settings') {
        $result = update_pir_settings($id, (int)post('pir_analista_id'), (string)post('pir_stato', 'in_corso'));
        flash($result['ok'] ? 'success' : 'error', $result['message']);
    } elseif ($action === 'add_meeting') {
        $participants = is_array(post('participants', [])) ? post('participants', []) : [];
        $result = save_pir_meeting($id, (string)post('data_riunione'), (string)post('note'), $participants);
        flash($result['ok'] ? 'success' : 'error', $result['message']);
    } elseif ($action === 'update_meeting') {
        $participants = is_array(post('participants', [])) ? post('participants', []) : [];
        $result = update_pir_meeting((int)post('meeting_id'), $id, (string)post('data_riunione'), (string)post('note'), $participants);
        flash($result['ok'] ? 'success' : 'error', $result['message']);
    } elseif ($action === 'delete_meeting') {
        delete_pir_meeting((int)post('meeting_id'), $id);
        flash('success', 'Riunione PIR eliminata.');
    } elseif ($action === 'delete_attachment') {
        delete_pir_attachment((int)post('attachment_id'), $id);
        flash('success', 'Allegato eliminato.');
    } elseif ($action === 'add_attachment') {
        $attachmentType = (string)post('attachment_type', 'file');
        if ($attachmentType === 'link') {
            $result = add_pir_link_attachment((int)post('meeting_id'), (string)post('titolo'), (string)post('url'));
        } else {
            $result = add_pir_file_attachment((int)post('meeting_id'), $_FILES['file_allegato'] ?? [], (string)post('titolo'));
        }
        flash($result['ok'] ? 'success' : 'error', $result['message']);
    } elseif ($action === 'add_link') {
        $result = add_pir_link_attachment((int)post('meeting_id'), (string)post('titolo'), (string)post('url'));
        flash($result['ok'] ? 'success' : 'error', $result['message']);
    } elseif ($action === 'add_file') {
        $result = add_pir_file_attachment((int)post('meeting_id'), $_FILES['file_allegato'] ?? [], (string)post('titolo'));
        flash($result['ok'] ? 'success' : 'error', $result['message']);
    }
    redirect(APP_BASE_URL . '/pir/progetto.php?id=' . $id . ($returnAnchor !== '' ? '#' . $returnAnchor : ''));
}

$requirements = pir_project_requirements($id);
$reviews = get_pir_reviews_map($id);
$meetings = get_pir_meetings($id);
$utenti = get_utenti(true);
$pirParticipants = get_pir_all_participants($id);
$pendingRequirements = pir_pending_requirements_count($id);
$page_title = 'PIR - ' . ($questionario['nome_progetto'] ?: 'Progetto');
require __DIR__ . '/../includes/header.php';

function pir_status_label(?string $status): string {
    return match ($status) {
        'OK' => 'OK',
        'KO' => 'KO',
        'non_applicabile' => 'Non applicabile',
        'parziale' => 'Parziale',
        default => 'Da valutare',
    };
}

function pir_blank_participant(): array {
    return [
        'nome' => '',
        'ruolo' => '',
        'reparto' => '',
        'email' => '',
        'telefono' => '',
        'partecipato' => 1,
    ];
}

function pir_youtube_embed_url(string $url): string {
    $parts = parse_url($url);
    $host = strtolower((string)($parts['host'] ?? ''));
    $path = trim((string)($parts['path'] ?? ''), '/');
    if (str_contains($host, 'youtu.be') && $path !== '') {
        return 'https://www.youtube.com/embed/' . h(explode('/', $path)[0]);
    }
    if (str_contains($host, 'youtube.com')) {
        if (str_starts_with($path, 'embed/')) {
            return $url;
        }
        parse_str((string)($parts['query'] ?? ''), $query);
        if (!empty($query['v'])) {
            return 'https://www.youtube.com/embed/' . h((string)$query['v']);
        }
    }
    return '';
}

function pir_vimeo_embed_url(string $url): string {
    $parts = parse_url($url);
    $host = strtolower((string)($parts['host'] ?? ''));
    $path = trim((string)($parts['path'] ?? ''), '/');
    if (str_contains($host, 'player.vimeo.com') && str_starts_with($path, 'video/')) {
        return $url;
    }
    if (str_contains($host, 'vimeo.com') && preg_match('/^\d+$/', $path)) {
        return 'https://player.vimeo.com/video/' . $path;
    }
    return '';
}

function pir_attachment_preview(array $attachment): array {
    $isLink = ($attachment['tipo'] ?? '') === 'link';
    $source = $isLink ? (string)($attachment['url'] ?? '') : APP_BASE_URL . '/' . (string)($attachment['file_path'] ?? '');
    $title = (string)($attachment['titolo'] ?: ($attachment['original_name'] ?? $source));
    $mime = strtolower((string)($attachment['mime_type'] ?? ''));
    $path = strtolower((string)($attachment['original_name'] ?? $attachment['file_path'] ?? $source));
    $extension = pathinfo(parse_url($path, PHP_URL_PATH) ?: $path, PATHINFO_EXTENSION);

    if ($isLink) {
        $youtube = pir_youtube_embed_url($source);
        if ($youtube !== '') {
            return ['preview' => true, 'kind' => 'iframe', 'source' => $youtube, 'title' => $title, 'note' => 'Video YouTube'];
        }
        $vimeo = pir_vimeo_embed_url($source);
        if ($vimeo !== '') {
            return ['preview' => true, 'kind' => 'iframe', 'source' => $vimeo, 'title' => $title, 'note' => 'Video Vimeo'];
        }
    }

    if (str_starts_with($mime, 'image/') || in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg'], true)) {
        return ['preview' => true, 'kind' => 'image', 'source' => $source, 'title' => $title, 'note' => 'Immagine'];
    }
    if ($mime === 'application/pdf' || $extension === 'pdf') {
        return ['preview' => true, 'kind' => 'iframe', 'source' => $source, 'title' => $title, 'note' => 'PDF'];
    }
    if (str_starts_with($mime, 'video/') || in_array($extension, ['mp4', 'webm', 'ogg', 'mov'], true)) {
        return ['preview' => true, 'kind' => 'video', 'source' => $source, 'title' => $title, 'note' => 'Video'];
    }
    if (str_starts_with($mime, 'audio/') || in_array($extension, ['mp3', 'wav', 'ogg', 'm4a'], true)) {
        return ['preview' => true, 'kind' => 'audio', 'source' => $source, 'title' => $title, 'note' => 'Audio'];
    }
    if (in_array($extension, ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'], true)) {
        return ['preview' => true, 'kind' => 'office', 'source' => $source, 'title' => $title, 'note' => 'Documento Office'];
    }
    return ['preview' => false, 'kind' => 'link', 'source' => $source, 'title' => $title, 'note' => 'Apri'];
}
?>

<div class="d-flex justify-content-between align-items-start mb-3">
  <div>
    <h1 class="h4 mb-1">PIR progetto: <?= h($questionario['nome_progetto']) ?></h1>
    <div class="text-muted">
      <?= h($questionario['nome_servizio']) ?>
      <?php if (!empty($questionario['task_jira'])): ?> · Task <?= acronym_help('JIRA', 'Identificativo del task su Jira') ?>: <?= h($questionario['task_jira']) ?><?php endif; ?>
      · <?= count($requirements) ?> requisiti da verificare
    </div>
  </div>
  <div class="d-flex gap-2">
    <?php if (has_permission('ai_assistant', 'read')): ?>
      <a class="btn btn-outline-info" href="<?= APP_BASE_URL ?>/ai/index.php?questionario_id=<?= $id ?>&type=pir_support"><i class="bi bi-stars me-1"></i>Supporto IA PIR</a>
    <?php endif; ?>
    <a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/pir/lista.php">Torna alla lista PIR</a>
  </div>
</div>

<div class="card shadow-sm mb-4">
  <div class="card-header fw-semibold">Gestione PIR sicurezza</div>
  <div class="card-body">
    <form method="post" class="row g-3 align-items-end">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="update_pir_settings">
      <div class="col-md-5">
        <label class="form-label">Analista sicurezza PIR</label>
        <select class="form-select" name="pir_analista_id">
          <option value="">Non assegnato</option>
          <?php foreach ($utenti as $utente): ?>
            <option value="<?= (int)$utente['id'] ?>" <?= (int)($questionario['pir_analista_id'] ?? 0) === (int)$utente['id'] ? 'selected' : '' ?>>
              <?= h(user_label($utente)) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Stato PIR</label>
        <select class="form-select" name="pir_stato">
          <option value="in_corso" <?= ($questionario['pir_stato'] ?? 'in_corso') !== 'completata' ? 'selected' : '' ?>>IN CORSO</option>
          <option value="completata" <?= ($questionario['pir_stato'] ?? '') === 'completata' ? 'selected' : '' ?>>COMPLETATA</option>
        </select>
      </div>
      <div class="col-md-2">
        <button class="btn btn-primary w-100">Salva PIR</button>
      </div>
      <div class="col-md-2">
        <?php if (($questionario['pir_stato'] ?? '') === 'completata'): ?>
          <a class="btn btn-outline-danger w-100" href="<?= APP_BASE_URL ?>/pir/report.php?id=<?= $id ?>">Report PDF</a>
        <?php else: ?>
          <div class="small text-muted"><?= $pendingRequirements ?> requisiti da valutare</div>
        <?php endif; ?>
      </div>
      <?php if (has_permission('ai_assistant', 'read')): ?>
        <div class="col-12">
          <a class="btn btn-sm btn-outline-info" href="<?= APP_BASE_URL ?>/ai/index.php?questionario_id=<?= $id ?>&type=executive_report">Sintesi executive IA</a>
          <a class="btn btn-sm btn-outline-info" href="<?= APP_BASE_URL ?>/ai/index.php?questionario_id=<?= $id ?>&type=normalization">Normalizzazione requisiti IA</a>
        </div>
      <?php endif; ?>
    </form>
    <?php if ($pendingRequirements > 0): ?>
      <div class="form-text mt-2">La PIR può essere impostata su COMPLETATA solo quando tutti i requisiti hanno uno stato valorizzato.</div>
    <?php endif; ?>
  </div>
</div>

<div class="row g-4 mb-4">
  <div class="col-xl-4">
    <div class="card shadow-sm">
      <div class="card-header fw-semibold">Nuova riunione PIR</div>
      <div class="card-body">
        <form method="post" class="row g-3">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="add_meeting">
          <div class="col-12">
            <label class="form-label">Data riunione</label>
            <input class="form-control" type="date" name="data_riunione" value="<?= h(date('Y-m-d')) ?>" required>
          </div>
          <div class="col-12">
            <label class="form-label">Partecipanti</label>
            <div class="table-responsive">
              <table class="table table-sm align-middle mb-1">
                <thead>
                  <tr>
                    <th>Nome e cognome</th>
                    <th>Ruolo</th>
                    <th>Reparto</th>
                    <th>Email</th>
                    <th>Telefono</th>
                    <th>Partecipato</th>
                  </tr>
                </thead>
                <tbody class="pir-participants-body">
                <?php for ($i = 0; $i < 2; $i++): ?>
                  <tr>
                    <td><input class="form-control form-control-sm" name="participants[nome][]" placeholder="Mario Rossi"></td>
                    <td><input class="form-control form-control-sm" name="participants[ruolo][]" placeholder="PM"></td>
                    <td><input class="form-control form-control-sm" name="participants[reparto][]" placeholder="Security"></td>
                    <td><input class="form-control form-control-sm" type="email" name="participants[email][]" placeholder="nome@intranet.example"></td>
                    <td><input class="form-control form-control-sm" name="participants[telefono][]" placeholder="+39 ..."></td>
                    <td>
                      <select class="form-select form-select-sm" name="participants[partecipato][]">
                        <option value="1">Sì</option>
                        <option value="0">No</option>
                      </select>
                    </td>
                  </tr>
                <?php endfor; ?>
                </tbody>
              </table>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary pir-add-participant">
              <i class="bi bi-person-plus me-1"></i>Aggiungi partecipante
            </button>
            <div class="form-text">Di default sono presenti due righe; usa Aggiungi partecipante per inserirne altre.</div>
          </div>
          <div class="col-12">
            <label class="form-label">Note / minuta</label>
            <textarea class="form-control" name="note" rows="5"></textarea>
          </div>
          <div class="col-12">
            <button class="btn btn-primary w-100">Aggiungi riunione</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-xl-8">
    <div class="card shadow-sm">
      <div class="card-header fw-semibold">Riunioni PIR organizzate per data</div>
      <div class="card-body">
        <?php if (!$meetings): ?>
          <div class="text-muted">Nessuna riunione PIR registrata.</div>
        <?php endif; ?>
        <?php if ($meetings): ?>
        <div class="accordion" id="pirMeetingsAccordion">
        <?php endif; ?>
        <?php foreach ($meetings as $meeting): ?>
          <?php
            $participants = get_pir_meeting_participants((int)$meeting['id']);
            $attachments = get_pir_meeting_attachments((int)$meeting['id']);
            $participantRows = $participants ?: [pir_blank_participant(), pir_blank_participant()];
            $collapseId = 'pirMeeting' . (int)$meeting['id'];
            $attendedCount = count(array_filter($participants, fn($p) => (int)($p['partecipato'] ?? 1) === 1));
          ?>
          <div class="accordion-item mb-2">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#<?= h($collapseId) ?>">
                <span class="fw-semibold me-2"><?= h(date('d/m/Y', strtotime((string)$meeting['data_riunione']))) ?></span>
                <span class="text-muted small">
                  <?= count($participants) ?> invitati · <?= $attendedCount ?> presenti · <?= count($attachments) ?> allegati
                </span>
              </button>
            </h2>
            <div id="<?= h($collapseId) ?>" class="accordion-collapse collapse" data-bs-parent="#pirMeetingsAccordion">
              <div class="accordion-body">
                <form method="post" class="row g-3">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="update_meeting">
                  <input type="hidden" name="meeting_id" value="<?= (int)$meeting['id'] ?>">
                  <div class="col-md-3">
                    <label class="form-label">Data</label>
                    <input class="form-control form-control-sm" type="date" name="data_riunione" value="<?= h($meeting['data_riunione']) ?>" required>
                  </div>
                  <div class="col-12">
                    <label class="form-label">Partecipanti invitati / presenti</label>
                    <div class="table-responsive">
                      <table class="table table-sm align-middle mb-1">
                        <thead>
                          <tr>
                            <th>Nome e cognome</th>
                            <th>Ruolo</th>
                            <th>Reparto</th>
                            <th>Email</th>
                            <th>Telefono</th>
                            <th>Partecipato</th>
                          </tr>
                        </thead>
                        <tbody class="pir-participants-body">
                        <?php foreach ($participantRows as $participant): ?>
                          <tr>
                            <td><input class="form-control form-control-sm" name="participants[nome][]" value="<?= h($participant['nome'] ?? '') ?>"></td>
                            <td><input class="form-control form-control-sm" name="participants[ruolo][]" value="<?= h($participant['ruolo'] ?? '') ?>"></td>
                            <td><input class="form-control form-control-sm" name="participants[reparto][]" value="<?= h($participant['reparto'] ?? '') ?>"></td>
                            <td><input class="form-control form-control-sm" type="email" name="participants[email][]" value="<?= h($participant['email'] ?? '') ?>"></td>
                            <td><input class="form-control form-control-sm" name="participants[telefono][]" value="<?= h($participant['telefono'] ?? '') ?>"></td>
                            <td>
                              <select class="form-select form-select-sm" name="participants[partecipato][]">
                                <option value="1" <?= (int)($participant['partecipato'] ?? 1) === 1 ? 'selected' : '' ?>>Sì</option>
                                <option value="0" <?= (int)($participant['partecipato'] ?? 1) === 0 ? 'selected' : '' ?>>No</option>
                              </select>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                        </tbody>
                      </table>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary pir-add-participant">
                      <i class="bi bi-person-plus me-1"></i>Aggiungi partecipante
                    </button>
                    <div class="form-text">Per aggiungere persone, usa il pulsante e salva la riunione.</div>
                  </div>
                  <div class="col-12">
                    <label class="form-label">Note / minuta</label>
                    <textarea class="form-control form-control-sm" name="note" rows="3"><?= h($meeting['note'] ?? '') ?></textarea>
                  </div>
                  <div class="col-12 d-flex gap-2">
                    <button class="btn btn-sm btn-outline-primary">Salva riunione</button>
                    <button class="btn btn-sm btn-outline-danger" name="action" value="delete_meeting" onclick="return confirm('Eliminare questa riunione PIR?')">Elimina</button>
                  </div>
                </form>

                <div class="mt-3">
                  <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="fw-semibold small">Allegati, link, video, audio</div>
                    <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#addAttachment<?= (int)$meeting['id'] ?>">
                      <i class="bi bi-paperclip me-1"></i>Aggiungi allegato
                    </button>
                  </div>
                  <?php if ($attachments): ?>
                    <div class="list-group list-group-flush small">
                    <?php foreach ($attachments as $attachment): ?>
                      <?php
                        $preview = pir_attachment_preview($attachment);
                        $modalId = 'attachmentPreview' . (int)$attachment['id'];
                      ?>
                      <div class="list-group-item px-0 d-flex justify-content-between align-items-center gap-2">
                        <div>
                          <i class="bi bi-<?= $attachment['tipo'] === 'link' ? 'link-45deg' : 'file-earmark' ?> me-1"></i>
                          <span class="fw-semibold"><?= h($preview['title']) ?></span>
                          <span class="text-muted ms-1"><?= h($preview['note']) ?></span>
                        </div>
                        <div class="text-nowrap">
                          <?php if ($preview['preview']): ?>
                            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#<?= h($modalId) ?>">Anteprima</button>
                          <?php endif; ?>
                          <a class="btn btn-sm btn-outline-secondary" href="<?= h($preview['source']) ?>" target="_blank" rel="noopener">Apri</a>
                          <form method="post" class="d-inline" onsubmit="return confirm('Eliminare questo allegato?')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete_attachment">
                            <input type="hidden" name="attachment_id" value="<?= (int)$attachment['id'] ?>">
                            <button class="btn btn-sm btn-outline-danger" title="Elimina allegato">
                              <i class="bi bi-trash"></i>
                            </button>
                          </form>
                        </div>
                      </div>

                      <?php if ($preview['preview']): ?>
                      <div class="modal fade" id="<?= h($modalId) ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-xl modal-dialog-scrollable">
                          <div class="modal-content">
                            <div class="modal-header">
                              <h5 class="modal-title"><?= h($preview['title']) ?></h5>
                              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                              <?php if ($preview['kind'] === 'image'): ?>
                                <img class="img-fluid rounded border" src="<?= h($preview['source']) ?>" alt="<?= h($preview['title']) ?>">
                              <?php elseif ($preview['kind'] === 'video'): ?>
                                <video class="w-100 rounded border" controls src="<?= h($preview['source']) ?>"></video>
                              <?php elseif ($preview['kind'] === 'audio'): ?>
                                <audio class="w-100" controls src="<?= h($preview['source']) ?>"></audio>
                              <?php elseif ($preview['kind'] === 'office'): ?>
                                <div class="alert alert-info mb-0">
                                  L'anteprima diretta dei file Word, Excel e PowerPoint locali dipende dal browser e spesso non è disponibile.
                                  Usa <strong>Apri</strong> per visualizzare o scaricare il documento.
                                </div>
                              <?php else: ?>
                                <div class="ratio ratio-16x9">
                                  <iframe src="<?= h($preview['source']) ?>" allowfullscreen></iframe>
                                </div>
                              <?php endif; ?>
                            </div>
                            <div class="modal-footer">
                              <a class="btn btn-outline-secondary" href="<?= h($preview['source']) ?>" target="_blank" rel="noopener">Apri in nuova scheda</a>
                              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
                            </div>
                          </div>
                        </div>
                      </div>
                      <?php endif; ?>
                    <?php endforeach; ?>
                    </div>
                  <?php else: ?>
                    <div class="text-muted small mb-2">Nessun allegato.</div>
                  <?php endif; ?>

                  <div class="modal fade" id="addAttachment<?= (int)$meeting['id'] ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                      <form method="post" enctype="multipart/form-data" class="modal-content pir-attachment-form" data-title-endpoint="<?= APP_BASE_URL ?>/pir/link_title.php">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="add_attachment">
                        <input type="hidden" name="meeting_id" value="<?= (int)$meeting['id'] ?>">
                        <div class="modal-header">
                          <h5 class="modal-title">Aggiungi allegato</h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                          <div class="mb-3">
                            <label class="form-label">Tipo allegato</label>
                            <select class="form-select pir-attachment-type" name="attachment_type" onchange="window.syncPirAttachmentForm && window.syncPirAttachmentForm(this.form)">
                              <option value="file">File</option>
                              <option value="link">URL / video online</option>
                            </select>
                          </div>
                          <div class="mb-3">
                            <label class="form-label">Titolo</label>
                            <input class="form-control pir-attachment-title" name="titolo" placeholder="Titolo visibile nella riunione" data-auto-title="1">
                            <div class="form-text">Viene proposto dal nome file o dal titolo della pagina, ma puoi modificarlo.</div>
                          </div>
                          <div class="mb-3 pir-file-field" data-attachment-mode="file">
                            <label class="form-label">File</label>
                            <input class="form-control pir-attachment-file" type="file" name="file_allegato" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.png,.jpg,.jpeg,.gif,.webp,.mp3,.wav,.mp4,.webm,.mov,.avi,audio/*,video/*,image/*">
                            <div class="form-text">Anteprima disponibile per immagini, PDF, audio e video compatibili col browser.</div>
                          </div>
                          <div class="mb-3 pir-link-field d-none" data-attachment-mode="link">
                            <label class="form-label">URL</label>
                            <div class="input-group">
                              <input class="form-control pir-attachment-url" name="url" placeholder="https://...">
                              <button class="btn btn-outline-secondary pir-fetch-title" type="button">Recupera titolo</button>
                            </div>
                            <div class="form-text pir-link-title-status">YouTube e Vimeo vengono riconosciuti automaticamente e mostrati in modale.</div>
                          </div>
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annulla</button>
                          <button class="btn btn-primary">Salva allegato</button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
        <?php if ($meetings): ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="card shadow-sm">
  <div class="card-header fw-semibold">Scheda progetto - verifica requisiti assegnati</div>
  <div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
      <thead>
        <tr>
          <th>Codice</th>
          <th>Tipo</th>
          <th>Evoluzione catalogo</th>
          <th>Requisito</th>
          <th>Stato</th>
          <th>Note</th>
          <th>Applicazione / motivazione obbligatoria</th>
          <th>Rientro / eccezione</th>
          <th>Referente cambio stato</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($requirements as $req): ?>
        <?php
          $reviewKey = $req['pir_tipo'] . ':' . $req['pir_ref_id'];
          $review = $reviews[$reviewKey] ?? [];
          $status = (string)($review['stato'] ?? '');
          $modalId = 'pirRefModal' . preg_replace('/[^A-Za-z0-9]/', '', $reviewKey);
          $rowId = 'pirReqRow' . preg_replace('/[^A-Za-z0-9]/', '', $reviewKey);
          $formId = 'pirReviewForm' . preg_replace('/[^A-Za-z0-9]/', '', $reviewKey);
          $catalogStatus = $req['pir_catalog_status'] ?? ['tone' => 'secondary', 'label' => 'N/D', 'detail' => ''];
        ?>
        <tr id="<?= h($rowId) ?>" class="<?= !empty($req['pir_new_candidate']) ? 'table-info' : ($req['pir_tipo'] === 'default_design' ? 'table-secondary' : '') ?>">
          <form id="<?= h($formId) ?>" method="post" class="pir-review-form" data-has-analyst="<?= (int)($questionario['pir_analista_id'] ?? 0) > 0 ? '1' : '0' ?>">
            <input form="<?= h($formId) ?>" type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <input form="<?= h($formId) ?>" type="hidden" name="action" value="save_requirement">
            <input form="<?= h($formId) ?>" type="hidden" name="return_anchor" value="<?= h($rowId) ?>">
            <input form="<?= h($formId) ?>" type="hidden" name="requisito_tipo" value="<?= h($req['pir_tipo']) ?>">
            <input form="<?= h($formId) ?>" type="hidden" name="requisito_ref_id" value="<?= (int)$req['pir_ref_id'] ?>">
            <td class="text-nowrap"><code><?= h($req['codice'] ?? '') ?></code></td>
            <td><span class="badge text-bg-<?= $req['pir_tipo'] === 'specifico' ? 'primary' : ($req['pir_tipo'] === 'default_design' ? 'secondary' : 'success') ?>"><?= h($req['pir_tipo_label']) ?></span></td>
            <td style="min-width: 230px;">
              <span class="badge text-bg-<?= h($catalogStatus['tone'] ?? 'secondary') ?>"><?= h($catalogStatus['label'] ?? 'N/D') ?></span>
              <div class="small text-muted mt-1"><?= h($catalogStatus['detail'] ?? '') ?></div>
            </td>
            <td>
              <strong><?= h($req['titolo'] ?? '') ?></strong>
              <div class="small text-muted"><?= h(short_text($req['descrizione'] ?? '', 130)) ?></div>
            </td>
            <td>
              <select form="<?= h($formId) ?>" class="form-select form-select-sm pir-review-status" name="stato">
                <option value="" <?= $status === '' ? 'selected' : '' ?>>Da valutare</option>
                <?php foreach (['OK' => 'OK', 'KO' => 'KO', 'non_applicabile' => 'Non applicabile', 'parziale' => 'Parziale'] as $value => $label): ?>
                  <option value="<?= h($value) ?>" <?= $status === $value ? 'selected' : '' ?>><?= h($label) ?></option>
                <?php endforeach; ?>
              </select>
              <?php if ($status): ?><div class="small text-muted mt-1"><?= h(pir_status_label($status)) ?></div><?php endif; ?>
            </td>
            <td><textarea form="<?= h($formId) ?>" class="form-control form-control-sm" name="note" rows="3"><?= h($review['note'] ?? '') ?></textarea></td>
            <td>
              <textarea form="<?= h($formId) ?>" class="form-control form-control-sm pir-review-applicazione" name="applicazione" rows="3" placeholder="Come è stato applicato, oppure perché non applicabile / KO / parziale"><?= h($review['applicazione'] ?? '') ?></textarea>
              <div class="form-text">Obbligatorio quando selezioni uno stato.</div>
            </td>
            <td>
              <textarea form="<?= h($formId) ?>" class="form-control form-control-sm pir-review-rientro" name="rientro_eccezione" rows="3" placeholder="Rientro previsto, eccezione approvata, motivazione"><?= h($review['rientro_eccezione'] ?? '') ?></textarea>
              <div class="form-text">Obbligatorio per KO.</div>
            </td>
            <td class="small">
              <?= h($review['referente_nome'] ?? '') ?: '<span class="text-muted">Non indicato</span>' ?>
            </td>
            <td>
              <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#<?= h($modalId) ?>">Salva riga</button>
              <div class="modal fade" id="<?= h($modalId) ?>" data-form-id="<?= h($formId) ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title">Referente cambio stato requisito</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                      <div class="alert alert-danger d-none pir-review-errors" role="alert"></div>
                      <p class="small text-muted">Indica chi sta confermando o motivando il cambio stato di questo requisito.</p>
                      <div class="form-check mb-2">
                        <input form="<?= h($formId) ?>" class="form-check-input pir-review-ref-analyst" type="radio" name="referente_tipo" value="analista" id="<?= h($modalId) ?>Analista" <?= ($review['referente_tipo'] ?? '') !== 'partecipante' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="<?= h($modalId) ?>Analista">
                          Analista sicurezza PIR: <?= h($questionario['pir_analista_nome'] ?? 'non assegnato') ?>
                        </label>
                      </div>
                      <div class="form-check mb-2">
                        <input form="<?= h($formId) ?>" class="form-check-input pir-review-ref-participant" type="radio" name="referente_tipo" value="partecipante" id="<?= h($modalId) ?>Partecipante" <?= ($review['referente_tipo'] ?? '') === 'partecipante' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="<?= h($modalId) ?>Partecipante">Partecipante a una riunione PIR</label>
                      </div>
                      <select form="<?= h($formId) ?>" class="form-select pir-review-participant" name="referente_participant_id">
                        <option value="">Seleziona partecipante...</option>
                        <?php foreach ($pirParticipants as $participant): ?>
                          <option value="<?= (int)$participant['id'] ?>" <?= (int)($review['referente_participant_id'] ?? 0) === (int)$participant['id'] ? 'selected' : '' ?>>
                            <?= h($participant['nome']) ?><?= !empty($participant['ruolo']) ? ' - ' . h($participant['ruolo']) : '' ?> (<?= h($participant['data_riunione']) ?>)
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annulla</button>
                      <button form="<?= h($formId) ?>" class="btn btn-primary pir-review-submit">Conferma e salva</button>
                    </div>
                  </div>
                </div>
              </div>
            </td>
          </form>
        </tr>
      <?php endforeach; ?>
      <?php if (!$requirements): ?>
        <tr><td colspan="10" class="text-center text-muted py-4">Nessun requisito assegnato al progetto.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
