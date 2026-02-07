<?php
// app/templates/base_private.php

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

/**
 * IMPORTANTÍSSIMO:
 * Seu AuthService grava a sessão em $_SESSION['auth_user'] (já validado por você).
 * Então o dashboard precisa validar essa chave, não $_SESSION['user'].
 */
if (!isset($_SESSION['auth_user'])) {
  header('Location: /sistema-visa/app/templates/login.php');
  exit;
}

// Variáveis esperadas do "conteúdo"
$page_title = $page_title ?? 'Dashboard';
$page_icon  = $page_icon  ?? 'fa-solid fa-gauge-high';
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($page_title) ?> • Sistema Visa</title>

  <link rel="icon" href="/sistema-visa/app/static/img/favicon.png">

  <!-- Fonte -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <!-- CSS base (ordem obrigatória) -->
  <link rel="stylesheet" href="/sistema-visa/app/static/css/theme.css">
  <link rel="stylesheet" href="/sistema-visa/app/static/css/global.css">
  <link rel="stylesheet" href="/sistema-visa/app/static/css/dashboard.css">

  <!-- Toast (global no ambiente privado) -->
  <link rel="stylesheet" href="/sistema-visa/app/static/css/toast.css">

</head>
  <!-- CSS específico da página/módulo -->
  <?php if (!empty($extra_css) && is_array($extra_css)): ?>
    <?php foreach ($extra_css as $css): ?>
      <link rel="stylesheet" href="<?= htmlspecialchars($css) ?>">
    <?php endforeach; ?>
  <?php endif; ?>

  <!-- JS no HEAD (use somente se for estritamente necessário) -->
  <?php if (!empty($extra_head_js) && is_array($extra_head_js)): ?>
    <?php foreach ($extra_head_js as $js): ?>
      <script src="<?= htmlspecialchars($js) ?>"></script>
    <?php endforeach; ?>
  <?php endif; ?>
</head>
<body>

  <div class="private-layout" id="privateLayout">
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <aside class="sidebar" id="sidebar">
      <div class="sidebar__brand">
        <div class="sidebar__logo-wrap">
          <img
            id="sidebarLogo"
            src="/sistema-visa/app/static/img/logo.png"
            data-logo="/sistema-visa/app/static/img/logo.png"
            data-favicon="/sistema-visa/app/static/img/favicon.png"
            alt="Sistema Visa"
            class="sidebar__logo"
          >
        </div>
      </div>

      <nav class="sidebar__nav">
        <a class="sidebar__item" href="/sistema-visa/app/templates/dashboard.php" data-nav="dashboard">
          <i class="fa-solid fa-gauge-high"></i>
          <span>Dashboard</span>
        </a>

        <a class="sidebar__item" href="/sistema-visa/app/templates/lotes.php" data-nav="lotes">
          <i class="fa-solid fa-boxes-stacked"></i>
          <span>Lotes</span>
        </a>

        <a class="sidebar__item" href="/sistema-visa/app/templates/financeiro.php" data-nav="financeiro">
          <i class="fa-solid fa-coins"></i>
          <span>Financeiro</span>
        </a>

        <a class="sidebar__item" href="/sistema-visa/app/templates/relatorios.php" data-nav="relatorios">
          <i class="fa-solid fa-chart-line"></i>
          <span>Relatórios</span>
        </a>
      </nav>

      <div class="sidebar__footer">
        <button class="sidebar__logout" id="btnLogout" type="button">
          <i class="fa-solid fa-right-from-bracket"></i>
          <span>Sair</span>
        </button>
      </div>
    </aside>

    <button class="sidebar-edge-toggle" id="btnEdgeToggle" type="button" aria-label="Recolher sidebar">
      <i class="fa-solid fa-chevron-left" id="edgeToggleIcon"></i>
    </button>

    <!-- Conteúdo -->
    <div class="private-content">
      <!-- Topbar -->
      <header class="topbar">
        <button class="topbar__menu" id="btnToggleSidebar" type="button" aria-label="Abrir menu"></button>

        <div class="topbar__divider" aria-hidden="true"></div>

        <div class="topbar__title">
          <i class="<?= htmlspecialchars($page_icon) ?>"></i>
          <span><?= htmlspecialchars($page_title) ?></span>
        </div>

        <div class="topbar__right"></div>
      </header>

      <!-- Área rolável (interna) -->
      <main class="main">
        <?php
          if (!isset($content)) {
            echo '<p style="padding:16px;">Conteúdo não definido.</p>';
          } else {
            include $content;
          }
        ?>
      </main>

      <!-- Rodapé -->
      <footer class="private-footer">
        <div class="private-footer__inner">
          <span><strong>Sistema Visa Remoções</strong> • © <?= date('Y') ?> • Desenvolvido por
            <a href="https://grupoi9.com.br" target="_blank" rel="noopener noreferrer">Darlan P. Araujo</a>
          </span>
        </div>
      </footer>
    </div>
  </div>

  <!-- JS base do layout privado (sempre) -->
  <script src="/sistema-visa/app/static/js/dashboard.js"></script>

  <!-- Toast (global no ambiente privado) -->
  <script src="/sistema-visa/app/static/js/toast.js"></script>

  <!-- JS específico da página/módulo (carregado por tela, mantendo ordem declarada) -->
  <?php if (!empty($extra_js) && is_array($extra_js)): ?>
    <?php foreach ($extra_js as $js): ?>
      <script src="<?= htmlspecialchars($js) ?>"></script>
    <?php endforeach; ?>
  <?php endif; ?>
</body>
</html>