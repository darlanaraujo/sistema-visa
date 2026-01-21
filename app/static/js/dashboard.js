// app/static/js/dashboard.js

async function apiPost(url, data = {}) {
  const res = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    credentials: 'include',
    body: JSON.stringify(data),
  });
  return res;
}

function isMobile() {
  return window.matchMedia('(max-width: 980px)').matches;
}

document.addEventListener('DOMContentLoaded', () => {
  const layout = document.getElementById('privateLayout');
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebarOverlay');

  // Mobile (hambúrguer)
  const btnToggle = document.getElementById('btnToggleSidebar');

  // Desktop (seta na divisória)
  const btnEdgeToggle = document.getElementById('btnEdgeToggle');
  const edgeIcon = document.getElementById('edgeToggleIcon');

  // Logo swap
  const sidebarLogo = document.getElementById('sidebarLogo');

  const btnLogout = document.getElementById('btnLogout');

  function openMobileSidebar() {
    // FIX: no mobile, sempre abrir EXPANDIDO
    if (sidebar) sidebar.classList.remove('is-collapsed');
    setEdgeIconByState();
    setLogoByState();

    layout?.classList.add('is-sidebar-open');
  }

  function closeMobileSidebar() {
    layout?.classList.remove('is-sidebar-open');
  }

  function setEdgeIconByState() {
    if (!edgeIcon || !sidebar) return;
    const collapsed = sidebar.classList.contains('is-collapsed');

    edgeIcon.classList.remove('fa-chevron-left', 'fa-chevron-right');
    edgeIcon.classList.add(collapsed ? 'fa-chevron-right' : 'fa-chevron-left');
  }

  function setLogoByState() {
    if (!sidebarLogo || !sidebar) return;

    const logo = sidebarLogo.getAttribute('data-logo');
    const fav = sidebarLogo.getAttribute('data-favicon');
    const collapsed = sidebar.classList.contains('is-collapsed');

    // No mobile sempre logo normal
    if (isMobile()) {
      sidebarLogo.src = logo;
      return;
    }

    sidebarLogo.src = collapsed ? fav : logo;
  }

  function nudgeEdgeByState() {
    if (!btnEdgeToggle || !sidebar) return;
    const collapsed = sidebar.classList.contains('is-collapsed');

    btnEdgeToggle.classList.remove('is-nudge-left', 'is-nudge-right');
    btnEdgeToggle.classList.add(collapsed ? 'is-nudge-right' : 'is-nudge-left');

    window.setTimeout(() => {
      btnEdgeToggle.classList.remove('is-nudge-left', 'is-nudge-right');
    }, 220);
  }

  function toggleDesktopCollapse() {
    if (!sidebar) return;
    sidebar.classList.toggle('is-collapsed');
    setEdgeIconByState();
    setLogoByState();
    nudgeEdgeByState();
  }

  // Mobile: hambúrguer abre/fecha drawer
  if (btnToggle) {
    btnToggle.addEventListener('click', () => {
      if (!isMobile()) return;

      if (layout?.classList.contains('is-sidebar-open')) closeMobileSidebar();
      else openMobileSidebar();
    });
  }

  // Desktop: seta colapsa/expande
  if (btnEdgeToggle) {
    btnEdgeToggle.addEventListener('click', () => {
      if (isMobile()) return;
      toggleDesktopCollapse();
    });
  }

  // overlay fecha no mobile
  if (overlay) {
    overlay.addEventListener('click', () => closeMobileSidebar());
  }

  // clicar em item do menu fecha no mobile
  if (sidebar) {
    sidebar.addEventListener('click', (e) => {
      const link = e.target.closest('a.sidebar__item');
      if (link && isMobile()) closeMobileSidebar();
    });
  }

  // resize:
  // - se entrar em mobile, garantir que não esteja colapsado (evita menu “quebrado”)
  // - se sair do mobile, fecha drawer e re-aplica logo/ícone
  window.addEventListener('resize', () => {
    if (isMobile()) {
      if (sidebar) sidebar.classList.remove('is-collapsed');
      setEdgeIconByState();
      setLogoByState();
      return;
    }

    closeMobileSidebar();
    setEdgeIconByState();
    setLogoByState();
  });

  // inicialização
  setEdgeIconByState();
  setLogoByState();

  // Logout
  if (btnLogout) {
    btnLogout.addEventListener('click', async () => {
      try {
        await apiPost('/sistema-visa/public_php/api/logout.php', {});
        window.location.href = '/sistema-visa/app/templates/login.php';
      } catch (e) {
        window.location.href = '/sistema-visa/app/templates/login.php';
      }
    });
  }
});
