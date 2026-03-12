<?php
// app/templates/base_private.php

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

/**
 * Helpers globais do sistema (única fonte).
 * - Este arquivo deve conter funções utilitárias como h().
 * - require_once evita múltiplas inclusões.
 * - O caminho usa __DIR__ porque este arquivo está em app/templates.
 */
require_once __DIR__ . '/../../public_php/src/Support/helpers.php';
require_once __DIR__ . '/../core/url.php';

// ✅ Fonte única (server-side)
require_once __DIR__ . '/../core/company.php';
$corp = company_get();

/**
 * Fallback de segurança:
 * - Se por algum motivo o helpers.php não carregar (path errado, arquivo ausente),
 *   evitamos “tela branca” e garantimos que h() exista.
 * - Não conflita com o helper, pois só define se ainda não existir.
 */
if (!function_exists('h')) {
  function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
  }
}

/**
 * AuthService grava a sessão em $_SESSION['auth_user'].
 */
if (!isset($_SESSION['auth_user'])) {
  header('Location: ' . app_url('/app/templates/login.php'));
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

  <title><?= h($page_title) ?> • Sistema Visa</title>

  <link rel="icon" href="<?= h(app_url('/app/static/img/favicon.png')) ?>">

  <!-- Fonte -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <!-- CSS base (ordem obrigatória) -->
  <link rel="stylesheet" href="<?= h(app_url('/app/static/css/theme.css')) ?>">
  <link rel="stylesheet" href="<?= h(app_url('/app/static/css/global.css')) ?>">

  <!-- ✅ Layout privado (Sidebar/Topo/Rodapé/Tooltip) -->
  <link rel="stylesheet" href="<?= h(app_url('/app/static/css/base_private.css')) ?>">

  <!-- Toast (global no ambiente privado) -->
  <link rel="stylesheet" href="<?= h(app_url('/app/static/css/toast.css')) ?>">
 
  <!-- Componentes (global no ambiente privado) -->
  <link rel="stylesheet" href="<?= h(app_url('/app/static/css/ui_components.css')) ?>">

  <script>
    window.__APP_BASE__ = <?= json_encode(app_base_path(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    window.appUrl = window.appUrl || function (path) {
      var base = String(window.__APP_BASE__ || '');
      var normalized = String(path || '/');
      if (normalized.slice(0, 1) !== '/') normalized = '/' + normalized;
      return (base + normalized) || '/';
    };
  </script>

  <!-- CSS específico da página/módulo -->
  <?php if (!empty($extra_css) && is_array($extra_css)): ?>
    <?php foreach ($extra_css as $css): ?>
      <link rel="stylesheet" href="<?= h($css) ?>">
    <?php endforeach; ?>
  <?php endif; ?>

  <!-- Personalização -->
  <script src="<?= h(app_url('/app/static/js/system/sys_bootstrap_ui.js')) ?>"></script>
  
  <!-- Componentes (global no ambiente privado) -->
  <script defer src="<?= h(app_url('/app/static/js/ui_components.js')) ?>"></script>

  <!-- JS de conexão com BD (LocalStorage) -->
  <script src="<?= h(app_url('/app/static/js/core/sys_store.js')) ?>"></script>

  <!-- Store global do ambiente privado -->
  <script src="<?= h(app_url('/app/static/js/data/base_store.js')) ?>"></script>

  <!-- ✅ JS base do layout privado (sempre) -->
  <script defer src="<?= h(app_url('/app/static/js/base_private.js')) ?>"></script>

  <!-- Toast (global no ambiente privado) -->
  <script defer src="<?= h(app_url('/app/static/js/toast.js')) ?>"></script>

  <!-- JS personalização da empresa -->
  <script defer src="<?= h(app_url('/app/static/js/system/sys_personalizacao.js')) ?>"></script>

  <!-- JS no HEAD (use somente se for estritamente necessário) -->
  <?php if (!empty($extra_head_js) && is_array($extra_head_js)): ?>
    <?php foreach ($extra_head_js as $js): ?>
      <script defer src="<?= h($js) ?>"></script>
    <?php endforeach; ?>
  <?php endif; ?>
</head>
<body>

  <div class="private-layout" id="privateLayout">
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <aside class="sidebar" id="sidebar">
      <div class="sidebar__brand">
        <!-- Etapa 6: usuário ocupa a área da antiga marca na sidebar -->
        <button class="sidebar__user-card sidebar__user-card--brand" id="userCardSidebar" type="button" aria-label="Abrir ficha do usuário">
          <span class="sidebar__user-avatar" aria-hidden="true"></span>
          <span class="sidebar__user-meta">
            <span class="sidebar__user-name">Usuário</span>
            <span class="sidebar__user-role">Perfil</span>
          </span>
        </button>
      </div>

      <nav class="sidebar__nav">
        <a class="sidebar__item" href="<?= h(app_url('/app/templates/dashboard.php')) ?>" data-nav="dashboard">
          <i class="fa-solid fa-gauge-high"></i>
          <span>Dashboard</span>
        </a>

        <a class="sidebar__item" href="<?= h(app_url('/app/templates/lotes.php')) ?>" data-nav="lotes">
          <i class="fa-solid fa-boxes-stacked"></i>
          <span>Lotes</span>
        </a>

        <a class="sidebar__item" href="<?= h(app_url('/app/templates/financeiro.php')) ?>" data-nav="financeiro">
          <i class="fa-solid fa-coins"></i>
          <span>Financeiro</span>
        </a>

        <a class="sidebar__item" href="<?= h(app_url('/app/templates/relatorios.php')) ?>" data-nav="relatorios">
          <i class="fa-solid fa-chart-line"></i>
          <span>Relatórios</span>
        </a>

        <a class="sidebar__item" href="<?= h(app_url('/app/templates/ferramentas.php')) ?>" data-nav="ferramentas">
          <i class="fa-solid fa-screwdriver-wrench"></i>
          <span>Ferramentas</span>
        </a>
      </nav>

      <div class="sidebar__footer">
        <button class="sidebar__logout" id="btnLogout" type="button" data-tip="Sair">
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

        <!-- Topbar mantém a logo; o user card ficará apenas na sidebar nesta parte -->
        <div class="topbar__logo" aria-label="Logo do sistema">
          <img
            id="topbarLogo"
            data-brand="logo"
            src="<?= h(app_url('/app/static/img/logo.png')) ?>"
            data-logo="<?= h(app_url('/app/static/img/logo.png')) ?>"
            data-favicon="<?= h(app_url('/app/static/img/favicon.png')) ?>"
            data-logo-default="<?= h(app_url('/app/static/img/logo.png')) ?>"
            data-favicon-default="<?= h(app_url('/app/static/img/favicon.png')) ?>"
            alt="<?= h($corp['system_name'] ?? 'Sistema Visa Remoções') ?>"
          >
        </div>

        <div class="topbar__divider" aria-hidden="true"></div>

        <div class="topbar__title">
          <i class="<?= h($page_icon) ?>"></i>
          <span><?= h($page_title) ?></span>
        </div>

        <div class="topbar__right">
          <div class="topbar__actions" aria-label="Ações do topo">
            <button class="topbar__icon-btn" id="btnThemeToggle" type="button" aria-label="Alternar tema" title="Alternar tema">
              <i class="fa-solid fa-moon" id="themeToggleIcon"></i>
            </button>

            <button class="topbar__icon-btn is-alert" id="btnAlerts" type="button" aria-label="Alertas" title="Alertas">
              <i class="fa-solid fa-bell"></i>
              <span class="topbar__badge" id="alertsBadge" aria-hidden="true" style="display:none;">0</span>
            </button>
          </div>

          <div class="topbar-popover" id="alertsPopover" aria-hidden="true">
            <div class="topbar-popover__card">
              <div class="topbar-popover__head">
                <div class="topbar-popover__title">Alertas</div>
                <button class="topbar__icon-btn is-ghost" id="btnAlertsClose" type="button" aria-label="Fechar alertas" title="Fechar">
                  <i class="fa-solid fa-xmark"></i>
                </button>
              </div>
              <div class="topbar-popover__body" id="alertsBody">
                <div class="topbar-popover__empty">Sem alertas no momento.</div>
              </div>
            </div>
          </div>
        </div>
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
          <span>
            <strong><?= h($corp['system_name'] ?? 'Sistema Visa Remoções') ?></strong> • © <?= date('Y') ?> • Desenvolvido por
            <a href="https://grupoi9.com.br" target="_blank" rel="noopener noreferrer">Darlan P. Araujo</a>
          </span>
        </div>
      </footer>
    </div>
  </div>

  <!-- JS específico da página/módulo -->
  <?php if (!empty($extra_js) && is_array($extra_js)): ?>
    <?php foreach ($extra_js as $js): ?>
      <script src="<?= h($js) ?>"></script>
    <?php endforeach; ?>
  <?php endif; ?>

  <!-- Etapa 6 - Parte 2.1
       Modal de ficha do usuário (somente estrutura).
       Regras de abertura/fechamento e estilos entram nas próximas partes. -->
  <div
    class="user-modal"
    id="userModal"
    role="dialog"
    aria-modal="true"
    aria-hidden="true"
    aria-labelledby="userModalTitle"
  >
    <div class="user-modal__overlay" id="userModalOverlay"></div>

    <section class="user-modal__card" id="userModalBody">
      <header class="user-modal__head">
        <h2 class="user-modal__title" id="userModalTitle">Ficha do Usuário</h2>
        <button
          type="button"
          class="user-modal__close"
          id="userModalClose"
          aria-label="Fechar ficha do usuário"
        >
          <i class="fa-solid fa-xmark" aria-hidden="true"></i>
        </button>
      </header>

      <div class="user-modal__content">
        <aside class="user-modal__avatar-col">
          <div class="user-modal__avatar" id="userModalAvatar" aria-hidden="true">US</div>
        </aside>

        <div class="user-modal__info-col">
          <div class="user-modal__identity">
            <div class="user-modal__name" id="userModalName">Usuário</div>
            <div class="user-modal__role" id="userModalRole">Perfil</div>
          </div>

          <dl class="user-modal__grid">
            <div class="user-modal__row">
              <dt>Nome</dt>
              <dd id="userModalFieldName">—</dd>
            </div>
            <div class="user-modal__row">
              <dt>Cargo/Função</dt>
              <dd id="userModalFieldRole">—</dd>
            </div>
            <div class="user-modal__row">
              <dt>Email</dt>
              <dd id="userModalFieldEmail">—</dd>
            </div>
            <div class="user-modal__row">
              <dt>Telefone</dt>
              <dd id="userModalFieldPhone">—</dd>
            </div>
            <div class="user-modal__row">
              <dt>Tema</dt>
              <dd id="userModalFieldTheme">—</dd>
            </div>
            <div class="user-modal__row">
              <dt>Acento</dt>
              <dd id="userModalFieldAccent">—</dd>
            </div>
            <div class="user-modal__row">
              <dt>Atualizado em</dt>
              <dd id="userModalFieldUpdatedAt">—</dd>
            </div>
          </dl>
        </div>
      </div>

      <footer class="user-modal__foot">
        <button type="button" class="fin-btn fin-btn--ghost" id="userModalCloseFoot">Fechar</button>
        <button
          type="button"
          class="fin-btn"
          id="userModalEdit"
          disabled
          title="Disponível quando módulo de usuários for criado"
          aria-disabled="true"
        >
          Editar
        </button>
      </footer>
    </section>
  </div>
</body>
</html>
