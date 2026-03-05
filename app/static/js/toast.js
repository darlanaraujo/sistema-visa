// app/static/js/toast.js
// Uso global:
//   Toast.show("Salvo com sucesso");
//   Toast.info("Informação");
//   Toast.success("Pago confirmado");
//   Toast.error("Erro ao salvar");
//   Toast.danger("Erro ao salvar"); // alias legado
//   Toast.warning("Atenção...");

(function () {
  if (window.Toast) return; // evita sobrescrita acidental

  let hideTimer = null;
  let closeTimer = null;
  const TOAST_ANIM_MS = 260;

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
      'is-neutral',
      'is-info',
      'is-success',
      'is-error',
      'is-danger',
      'is-warning'
    );

    if (kind) {
      el.classList.add(`is-${kind}`);
    } else {
      el.classList.add('is-neutral');
    }

    el.textContent = String(message ?? '');
    // Reinicia o ciclo de animação para manter entrada perceptível.
    el.classList.remove('is-on', 'is-closing');
    void el.offsetWidth; // força reflow
    requestAnimationFrame(() => {
      el.classList.add('is-on');
    });

    if (hideTimer) {
      clearTimeout(hideTimer);
      hideTimer = null;
    }
    if (closeTimer) {
      clearTimeout(closeTimer);
      closeTimer = null;
    }

    hideTimer = setTimeout(() => {
      el.classList.add('is-closing');
      closeTimer = setTimeout(() => {
        el.classList.remove('is-on', 'is-closing');
      }, TOAST_ANIM_MS);
    }, 2600);
  }

  window.Toast = {
    show(message) {
      show(message);
    },
    info(message) {
      show(message, 'info');
    },
    success(message) {
      show(message, 'success');
    },
    error(message) {
      show(message, 'error');
    },
    danger(message) {
      show(message, 'danger'); // compat
    },
    warning(message) {
      show(message, 'warning');
    },
  };
})();
