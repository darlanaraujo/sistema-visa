<div class="login-page">
  <div class="login-card">

    <div class="sidebar__brand">
      <div class="sidebar__logo-wrap logo-wrap">
        <img
          id="sidebarLogo"
          src="<?= htmlspecialchars(app_url('/app/static/img/logo.png')) ?>"
          data-logo="<?= htmlspecialchars(app_url('/app/static/img/logo.png')) ?>"
          data-favicon="<?= htmlspecialchars(app_url('/app/static/img/favicon.png')) ?>"
          data-logo-default="<?= htmlspecialchars(app_url('/app/static/img/logo.png')) ?>"
          data-favicon-default="<?= htmlspecialchars(app_url('/app/static/img/favicon.png')) ?>"
          alt="Logo do sistema"
          class="sidebar__logo"
        >
      </div>
    </div>

    <div class="login-separator"></div>

    <h1 class="title">Acessar o Sistema</h1>

    <?php if ($error !== ''): ?>
      <div class="alert-error">
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="<?= htmlspecialchars(app_url('/app/modules/auth/login_action.php')) ?>" class="login-form">
      <div class="field">
        <label for="email">E-mail</label>
        <div class="input-icon">
          <i class="fa-solid fa-envelope" aria-hidden="true"></i>
          <input id="email" name="email" type="email" placeholder="email@dominio.com" required autocomplete="username">
        </div>
      </div>

      <div class="field">
        <label for="password">Senha</label>
        <div class="input-icon">
          <i class="fa-solid fa-lock" aria-hidden="true"></i>
          <input id="password" name="password" type="password" placeholder="••••••••" required autocomplete="current-password">
        </div>
      </div>

      <button type="submit" class="btn-primary">
        <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i>
        Entrar
      </button>
    </form>

  </div>

  <div class="login-footer">
    Desenvolvido por <a href="https://grupoi9.com.br" target="_blank" rel="noopener">Darlan P. Araujo</a>
  </div>
</div>
