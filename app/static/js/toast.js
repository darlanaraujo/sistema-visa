// app/static/js/toast.js
// Uso global:
//   Toast.show("Salvo com sucesso");
//   Toast.success("Pago confirmado");
//   Toast.danger("Erro ao salvar");
//   Toast.warning("Atenção...");

(function () {
  if (window.Toast) return; // evita sobrescrita acidental

  let hideTimer = null;

  function ensureEl() {
    let el = document.getElementById('sysToast');
    if (el) return el;

    el = document.createElement('div');
    el.id = 'sysToast';
    el.className = 'sys-toast';
    el.setAttribute('role', 'status');
    el.setAttribute('aria-live', 'polite');
    el.setAttribute('aria-atomic', 'true');
    document.body.appendChild(el);
    return el;
  }

  function show(message, kind = '') {
    const el = ensureEl();

    el.classList.remove(
      'is-success',
      'is-danger',
      'is-warning'
    );

    if (kind) {
      el.classList.add(`is-${kind}`);
    }

    el.textContent = String(message ?? '');

    el.classList.add('is-on');

    if (hideTimer) {
      clearTimeout(hideTimer);
      hideTimer = null;
    }

    hideTimer = setTimeout(() => {
      el.classList.remove('is-on');
    }, 2600);
  }

  window.Toast = {
    show(message) {
      show(message);
    },
    success(message) {
      show(message, 'success');
    },
    danger(message) {
      show(message, 'danger');
    },
    warning(message) {
      show(message, 'warning');
    },
  };
})();