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

document.addEventListener('DOMContentLoaded', () => {
  const btnLogout = document.getElementById('btnLogout');
  const btnToggle = document.getElementById('btnToggleSidebar');
  const sidebar = document.getElementById('sidebar');

  if (btnToggle && sidebar) {
    btnToggle.addEventListener('click', () => {
      sidebar.classList.toggle('is-collapsed');
    });
  }

  if (btnLogout) {
    btnLogout.addEventListener('click', async () => {
      try {
        const res = await apiPost('/sistema-visa/public_php/api/logout.php', {});
        if (res.ok) {
          window.location.href = '/sistema-visa/app/templates/login.php';
          return;
        }
        window.location.href = '/sistema-visa/app/templates/login.php';
      } catch (e) {
        window.location.href = '/sistema-visa/app/templates/login.php';
      }
    });
  }
});
