<?php
require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/functions.php";
// Ensure CSRF token is generated early
csrf_token();
$navUser = current_user();
$aiStatus = is_logged_in() ? ai_runtime_status() : null;
$adminItems = [
    ["domande", "read", "/admin/domande.php", "bi-question-circle", "Domande"],
    ["utenti", "read", "/admin/utenti.php", "bi-people", "Utenti"],
    ["ruoli_permessi", "read", "/admin/ruoli.php", "bi-shield-lock", "Ruoli e permessi"],
    ["business_lines", "read", "/admin/business_lines.php", "bi-diagram-3", "Business line"],
    ["requisito_categorie", "read", "/admin/requisito_categorie.php", "bi-tags", "Categorie requisiti"],
    ["requisiti", "read", "/admin/requisiti.php", "bi-journal-text", "Requisiti"],
    ["requisiti_specifici", "read", "/admin/requisiti_specifici.php", "bi-stars", "Requisiti specifici"],
    ["servizi", "read", "/admin/servizi.php", "bi-grid", "Servizi"],
    ["regole_requisiti", "read", "/admin/regole_requisiti.php", "bi-link-45deg", "Regole Requisiti"],
    ["regole_servizi", "read", "/admin/regole_servizi.php", "bi-link", "Regole Servizi"],
    ["ai_settings", "read", "/admin/ai_providers.php", "bi-cpu", "Configurazione IA"],
    ["auth_settings", "read", "/admin/auth_settings.php", "bi-key", "Autenticazione"],
];
$visibleAdminItems = array_values(array_filter($adminItems, fn($item) => has_permission($item[0], $item[1])));
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($page_title ?? APP_NAME) ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="<?= APP_BASE_URL ?>/public/css/style.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-brand mb-4">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="<?= APP_BASE_URL ?>/index.php">
      <i class="bi bi-shield-check me-2"></i><?= h(APP_NAME) ?>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMain">
      <ul class="navbar-nav me-auto">
        <li class="nav-item">
          <a class="nav-link" href="<?= APP_BASE_URL ?>/index.php"><i class="bi bi-house me-1"></i>Home</a>
        </li>
        <?php if (has_permission('questionari', 'read')): ?>
        <li class="nav-item">
          <a class="nav-link" href="<?= APP_BASE_URL ?>/questionario/lista.php"><i class="bi bi-list-check me-1"></i>Questionari</a>
        </li>
        <?php endif; ?>
        <?php if (has_permission('questionari', 'create')): ?>
        <li class="nav-item">
          <a class="nav-link" href="<?= APP_BASE_URL ?>/questionario/importa.php" title="Importa questionario XLSX" aria-label="Importa questionario XLSX"><i class="bi bi-upload"></i></a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="<?= APP_BASE_URL ?>/questionario/nuovo.php" title="Nuovo questionario" aria-label="Nuovo questionario"><i class="bi bi-plus-circle"></i></a>
        </li>
        <?php endif; ?>
        <?php if (has_permission('pir', 'read')): ?>
        <li class="nav-item">
          <a class="nav-link" href="<?= APP_BASE_URL ?>/pir/lista.php"><i class="bi bi-clipboard-check me-1"></i>PIR</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="<?= APP_BASE_URL ?>/pir/eccezioni.php"><i class="bi bi-calendar-x me-1"></i>Eccezioni</a>
        </li>
        <?php endif; ?>
        <?php if (has_permission('threat_analysis', 'read')): ?>
        <li class="nav-item">
          <a class="nav-link" href="<?= APP_BASE_URL ?>/threat_analysis/index.php"><i class="bi bi-cpu me-1"></i>Threat Analysis</a>
        </li>
        <?php endif; ?>
        <?php if (has_permission('ai_assistant', 'read')): ?>
        <li class="nav-item">
          <a class="nav-link" href="<?= APP_BASE_URL ?>/ai/index.php"><i class="bi bi-stars me-1"></i>Suggerimenti IA</a>
        </li>
        <?php endif; ?>
      </ul>
      <ul class="navbar-nav">
        <?php if ($aiStatus): ?>
        <li class="nav-item d-flex align-items-center me-2">
          <a class="nav-link py-1" href="<?= APP_BASE_URL ?>/admin/ai_providers.php" title="<?= h($aiStatus['message'] . ' Â· ' . $aiStatus['base_url']) ?>">
            <span class="badge rounded-pill text-bg-<?= h($aiStatus['tone']) ?>">
              <i class="bi bi-circle-fill me-1"></i><?= h($aiStatus['label']) ?>
            </span>
          </a>
        </li>
        <?php endif; ?>
        <?php if ($visibleAdminItems): ?>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
            <i class="bi bi-gear me-1"></i>Gestione
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <?php foreach ($visibleAdminItems as $index => $item): ?>
              <?php if ($index === 8): ?><li><hr class="dropdown-divider"></li><?php endif; ?>
              <li><a class="dropdown-item" href="<?= APP_BASE_URL . $item[2] ?>"><i class="bi <?= h($item[3]) ?> me-2"></i><?= h($item[4]) ?></a></li>
            <?php endforeach; ?>
          </ul>
        </li>
        <?php endif; ?>
        <?php if ($navUser || is_admin()): ?>
        <li class="nav-item">
          <?php if ($navUser): ?>
            <a class="nav-link" href="<?= APP_BASE_URL ?>/profilo.php"><i class="bi bi-person-circle me-1"></i><?= h(user_label($navUser)) ?></a>
          <?php else: ?>
            <a class="nav-link" href="<?= APP_BASE_URL ?>/profilo.php" title="Admin tecnico configurato da Docker, non censito nel database"><i class="bi bi-person-gear me-1"></i>Admin tecnico</a>
          <?php endif; ?>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="<?= APP_BASE_URL ?>/admin/logout.php"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
        </li>
        <?php else: ?>
        <li class="nav-item">
          <a class="nav-link" href="<?= APP_BASE_URL ?>/login.php"><i class="bi bi-box-arrow-in-right me-1"></i>Login</a>
        </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
<div class="container-fluid px-4">
<?php
$flash = get_flash();
if ($flash): ?>
<div class="alert alert-<?= $flash["type"] === "error" ? "danger" : h($flash["type"]) ?> alert-dismissible fade show" role="alert">
  <?= h($flash["msg"]) ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

