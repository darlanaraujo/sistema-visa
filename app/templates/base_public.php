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

  <!-- CSS específico da página pública (opcional) -->
  <?php if (!empty($extra_css) && is_array($extra_css)): ?>
    <?php foreach ($extra_css as $css): ?>
      <link rel="stylesheet" href="<?= htmlspecialchars($css) ?>">
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
</body>
</html>