<?php
// app/templates/cadastros_ficha_embed.php

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

require_once __DIR__ . '/../../public_php/src/Support/helpers.php';
require_once __DIR__ . '/../core/url.php';
require_once __DIR__ . '/../core/company.php';

if (!isset($_SESSION['auth_user'])) {
  header('Location: ' . app_url('/app/templates/login.php'));
  exit;
}

$corp = company_get();
$modo = trim((string)($_GET['modo'] ?? ''));
$pageTitle = $modo === 'cadastro' ? 'Cadastro inline' : 'Ficha inline';
$content = __DIR__ . '/../modules/cadastros/ficha.php';
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h($pageTitle) ?> • Sistema Visa</title>

  <link rel="icon" href="<?= h($corp['favicon'] ?? app_url('/app/static/img/favicon.png')) ?>">

  <script>
    window.__APP_BASE__ = <?= json_encode(app_base_path(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    window.appUrl = window.appUrl || function (path) {
      var base = String(window.__APP_BASE__ || '');
      var normalized = String(path || '/');
      if (normalized.slice(0, 1) !== '/') normalized = '/' + normalized;
      return (base + normalized) || '/';
    };
  </script>

  <script src="<?= h(app_url('/app/static/js/system/sys_bootstrap_ui.js')) ?>"></script>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <link rel="stylesheet" href="<?= h(app_url('/app/static/css/theme.css')) ?>">
  <link rel="stylesheet" href="<?= h(app_url('/app/static/css/global.css')) ?>">
  <link rel="stylesheet" href="<?= h(app_url('/app/static/css/base_private.css')) ?>">
  <link rel="stylesheet" href="<?= h(app_url('/app/static/css/toast.css')) ?>">
  <link rel="stylesheet" href="<?= h(app_url('/app/static/css/ui_components.css')) ?>">
  <link rel="stylesheet" href="<?= h(app_url('/app/static/css/financeiro.css')) ?>">
  <link rel="stylesheet" href="<?= h(app_url('/app/static/css/cadastros.css')) ?>">
  <link rel="stylesheet" href="<?= h(app_url('/app/static/css/ui_attachments.css')) ?>">

  <script defer src="<?= h(app_url('/app/static/js/ui_components.js')) ?>"></script>
  <script defer src="<?= h(app_url('/app/static/js/toast.js')) ?>"></script>
  <script defer src="<?= h(app_url('/app/static/js/cadastros/cadastros_form.js')) ?>"></script>
  <script defer src="<?= h(app_url('/app/static/js/ui_attachments.js')) ?>"></script>
</head>
<body class="cad-embed-body">
  <?php include $content; ?>
</body>
</html>
