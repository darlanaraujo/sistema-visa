<?php
// app/templates/base_public.php

$title = $title ?? 'Sistema Visa';
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($title) ?></title>

  <link rel="icon" type="image/png" href="/sistema-visa/app/static/img/favicon.png">

  <!-- =========================================================
       JS BOOTSTRAP (FIRST PAINT)
       - Aplica tema, cor, sidebar state antes do CSS pintar
       - Evita flash visual no login
  ========================================================== -->
  <script src="/sistema-visa/app/static/js/system/sys_bootstrap_ui.js"></script>

  <!-- Fonte (Inter) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <!-- CSS base (ordem obrigatória) -->
  <link rel="stylesheet" href="/sistema-visa/app/static/css/theme.css">
  <link rel="stylesheet" href="/sistema-visa/app/static/css/global.css">
  <link rel="stylesheet" href="/sistema-visa/app/static/css/login.css">

  <!-- CSS extra da página pública (opcional) -->
  <?php if (!empty($extra_css) && is_array($extra_css)): ?>
    <?php foreach ($extra_css as $css): ?>
      <?php if (!empty($css)): ?>
        <link rel="stylesheet" href="<?= htmlspecialchars($css) ?>">
      <?php endif; ?>
    <?php endforeach; ?>
  <?php endif; ?>

  <!-- JS extra no HEAD (opcional e raro) -->
  <?php if (!empty($extra_js_head) && is_array($extra_js_head)): ?>
    <?php foreach ($extra_js_head as $js): ?>
      <?php if (!empty($js)): ?>
        <script src="<?= htmlspecialchars($js) ?>"></script>
      <?php endif; ?>
    <?php endforeach; ?>
  <?php endif; ?>
</head>

<body>
  <?php
    if (!isset($contentFile)) {
      echo '<p style="padding:16px;">Conteúdo não definido.</p>';
    } else {
      require $contentFile;
    }
  ?>

  <!-- =========================================================
       JS PERSONALIZAÇÃO
       - Aplica logo, favicon, cor, identidade
       - Atualiza DOM após carregamento
  ========================================================== -->
  <script src="/sistema-visa/app/static/js/system/sys_personalizacao.js"></script>

  <!-- JS extra (defer) -->
  <?php if (!empty($extra_js) && is_array($extra_js)): ?>
    <?php foreach ($extra_js as $js): ?>
      <?php if (!empty($js)): ?>
        <script defer src="<?= htmlspecialchars($js) ?>"></script>
      <?php endif; ?>
    <?php endforeach; ?>
  <?php endif; ?>

</body>
</html>