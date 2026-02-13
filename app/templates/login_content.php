<div class="login-page">
  <div class="login-card">
    <div class="sidebar__brand">
        <div class="sidebar__logo-wrap logo-wrap ">
          <img
          id="sidebarLogo"
          src="/sistema-visa/app/static/img/logo.png"
          data-logo="/sistema-visa/app/static/img/logo.png"
          data-favicon="/sistema-visa/app/static/img/favicon.png"
          data-logo-default="/sistema-visa/app/static/img/logo.png"
          data-favicon-default="/sistema-visa/app/static/img/favicon.png"
          alt="Sistema Visa"
          class="sidebar__logo"
        >
        </div>

    <div class="login-separator"></div>

    <h1 class="title">Acessar o Sistema</h1>


    <?php if ($error !== ''): ?>
      <div class="alert-error">
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="/sistema-visa/app/modules/auth/login_action.php" class="login-form">
      <div class="field">
        <label for="email">E-mail</label>
        <div class="input-icon">
          <i class="fa-solid fa-envelope"></i>
          <input id="email" name="email" type="email" placeholder="email@dominio.com" required>
        </div>
      </div>

      <div class="field">
        <label for="password">Senha</label>
        <div class="input-icon">
          <i class="fa-solid fa-lock"></i>
          <input id="password" name="password" type="password" placeholder="••••••••" required>
        </div>
      </div>

      <button type="submit" class="btn-primary">
        <i class="fa-solid fa-right-to-bracket"></i>
        Entrar
      </button>
    </form>
  </div>

  <div class="login-footer">
    Desenvolvido por <a href="https://grupoi9.com.br" target="_blank" rel="noopener">Darlan P. Araujo</a>
  </div>
</div>
