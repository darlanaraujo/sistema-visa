// app/static/js/ui_components.js
// Componentes globais (UI) do sistema:
// - Tooltip premium unificado (sidebar + topbar)
//   * Sidebar: mostra SOMENTE quando colapsado no desktop
//   * Topbar: mostra NO desktop abaixo do botão, com seta em cima
//   * Outros: mostra NO desktop ao lado (direita)
// - Render de alertas (payload plano ou blocks)
//
// Uso (base_private.js):
//   UIComponents.initTooltips({ sidebarEl });

(function () {
  'use strict';

  if (window.UIComponents) return; // evita sobrescrita acidental

  /* =========================
     Utilitários internos
  ========================= */
  function isMobile() {
    try { return window.matchMedia('(max-width: 980px)').matches; }
    catch (_) { return false; }
  }

  function hasCoarsePointer() {
    try { return window.matchMedia('(pointer: coarse)').matches; }
    catch (_) { return false; }
  }

  function escapeHtml(s) {
    return String(s ?? '')
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  function clamp(n, min, max) {
    return Math.max(min, Math.min(max, n));
  }

  function ensureConfirmEl() {
    let modal = document.getElementById('svConfirmModal');
    if (modal) return modal;

    modal = document.createElement('div');
    modal.id = 'svConfirmModal';
    modal.className = 'sv-confirm';
    modal.setAttribute('aria-hidden', 'true');
    modal.innerHTML = `
      <div class="sv-confirm__overlay" data-sv-confirm-close></div>
      <section class="sv-confirm__card" role="dialog" aria-modal="true" aria-labelledby="svConfirmTitle" aria-describedby="svConfirmMessage">
        <header class="sv-confirm__head">
          <div>
            <div class="sv-confirm__eyebrow" id="svConfirmEyebrow">Confirmação</div>
            <h2 class="sv-confirm__title" id="svConfirmTitle">Confirmar ação</h2>
          </div>
          <button class="sv-confirm__close" type="button" aria-label="Fechar" data-sv-confirm-close>
            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
          </button>
        </header>
        <div class="sv-confirm__body">
          <p class="sv-confirm__message" id="svConfirmMessage">Tem certeza?</p>
        </div>
        <footer class="sv-confirm__foot">
          <button class="fin-btn fin-btn--ghost" type="button" data-sv-confirm-cancel>Cancelar</button>
          <button class="fin-btn" type="button" data-sv-confirm-confirm>Confirmar</button>
        </footer>
      </section>
    `;
    document.body.appendChild(modal);
    return modal;
  }

  function confirm(options = {}) {
    const modal = ensureConfirmEl();
    const eyebrowEl = modal.querySelector('#svConfirmEyebrow');
    const titleEl = modal.querySelector('#svConfirmTitle');
    const messageEl = modal.querySelector('#svConfirmMessage');
    const confirmBtn = modal.querySelector('[data-sv-confirm-confirm]');
    const cancelBtn = modal.querySelector('[data-sv-confirm-cancel]');
    const closeEls = modal.querySelectorAll('[data-sv-confirm-close]');

    if (eyebrowEl) eyebrowEl.textContent = String(options.eyebrow || 'Confirmação');
    if (titleEl) titleEl.textContent = String(options.title || 'Confirmar ação');
    if (messageEl) messageEl.textContent = String(options.message || 'Tem certeza?');
    if (confirmBtn) confirmBtn.textContent = String(options.confirmLabel || 'Confirmar');
    if (cancelBtn) cancelBtn.textContent = String(options.cancelLabel || 'Cancelar');

    return new Promise((resolve) => {
      let closed = false;

      function finish(result) {
        if (closed) return;
        closed = true;
        modal.classList.add('is-closing');
        window.setTimeout(() => {
          modal.classList.remove('is-open', 'is-closing');
          modal.setAttribute('aria-hidden', 'true');
          document.removeEventListener('keydown', onKeydown);
          closeEls.forEach((el) => el.removeEventListener('click', onCancel));
          cancelBtn?.removeEventListener('click', onCancel);
          confirmBtn?.removeEventListener('click', onConfirm);
          resolve(result);
        }, 180);
      }

      function onCancel() {
        finish(false);
      }

      function onConfirm() {
        finish(true);
      }

      function onKeydown(event) {
        if (event.key === 'Escape') {
          finish(false);
        }
      }

      closeEls.forEach((el) => el.addEventListener('click', onCancel));
      cancelBtn?.addEventListener('click', onCancel);
      confirmBtn?.addEventListener('click', onConfirm);
      document.addEventListener('keydown', onKeydown);

      modal.classList.remove('is-closing');
      modal.classList.add('is-open');
      modal.setAttribute('aria-hidden', 'false');
    });
  }

  /* =========================================================
     TOOLTIP PREMIUM (singleton)
========================================================= */
  function ensureTooltipEl() {
    let tip = document.querySelector('.sv-sidebar-tip');
    if (tip) return tip;

    tip = document.createElement('div');
    tip.className = 'sv-sidebar-tip';
    tip.innerHTML = `
      <div class="sv-sidebar-tip__arrow" aria-hidden="true"></div>
      <div class="sv-sidebar-tip__card" id="svSidebarTipCard"></div>
    `;
    document.body.appendChild(tip);
    return tip;
  }

  // Lê label de:
  // 1) data-tip
  // 2) aria-label
  // 3) title
  // 4) span interno (caso sidebar)
  // 5) texto do elemento
  function getTipLabel(el) {
    try {
      const dt = (el.getAttribute('data-tip') || '').trim();
      if (dt) return dt;

      const aria = (el.getAttribute('aria-label') || '').trim();
      if (aria) return aria;

      const title = (el.getAttribute('title') || '').trim();
      if (title) return title;

      const sp = el.querySelector?.('span');
      const t = (sp?.textContent || '').trim();
      if (t) return t;

      const self = (el.textContent || '').trim();
      return self || '';
    } catch (_) {
      return '';
    }
  }

  function shouldShowTooltipFor(el, sidebarEl) {
    // Evitar tooltip em mobile/coarse (experiência ruim)
    if (isMobile() || hasCoarsePointer()) return false;

    // Se estiver dentro do sidebar: só em desktop + colapsado
    if (sidebarEl && sidebarEl.contains(el)) {
      return sidebarEl.classList.contains('is-collapsed');
    }

    // Fora do sidebar: permitido no desktop
    return true;
  }

  function getPlacement(el) {
    const explicit = (el.getAttribute('data-tip-place') || '').trim().toLowerCase();
    if (explicit === 'left') return 'left';
    if (explicit === 'bottom') return 'bottom';

    // Dentro da topbar => abaixo
    if (el.closest?.('.topbar')) return 'bottom';
    return 'right';
  }

  function positionTooltipRight(tip, el) {
    const r = el.getBoundingClientRect();
    const gap = 12;

    const top = Math.round(r.top + (r.height / 2));
    const left = Math.round(r.right + gap);

    tip.style.left = `${left}px`;
    tip.style.top = `${top}px`;
    tip.style.marginTop = `-${Math.round(tip.offsetHeight / 2)}px`;
    tip.style.marginLeft = `0px`;
  }

  function positionTooltipBottom(tip, el) {
    const r = el.getBoundingClientRect();
    const gap = 10;
    const vw = window.innerWidth || document.documentElement.clientWidth || 0;

    // Centro do botão
    let cx = Math.round(r.left + (r.width / 2));
    const top = Math.round(r.bottom + gap);

    // Ajuste horizontal para não vazar (depende da largura do tooltip)
    const pad = 12;
    const half = Math.round((tip.offsetWidth || 240) / 2);
    cx = clamp(cx, pad + half, vw - pad - half);

    tip.style.left = `${cx}px`;
    tip.style.top = `${top}px`;
    tip.style.marginTop = `0px`;
    tip.style.marginLeft = `0px`;
  }

  function positionTooltipLeft(tip, el) {
    const r = el.getBoundingClientRect();
    const gap = 12;

    const top = Math.round(r.top + (r.height / 2));
    const left = Math.round(r.left - gap);

    tip.style.left = `${left}px`;
    tip.style.top = `${top}px`;
    tip.style.marginTop = `-${Math.round(tip.offsetHeight / 2)}px`;
    tip.style.marginLeft = `-${Math.round(tip.offsetWidth)}px`;
  }

  function initTooltips({ sidebarEl } = {}) {
    const tip = ensureTooltipEl();
    const card = tip.querySelector('#svSidebarTipCard');

    let currentEl = null;
    let hideTimer = null;

    function clearHide() {
      if (hideTimer) window.clearTimeout(hideTimer);
      hideTimer = null;
    }

    function hideNow() {
      clearHide();
      tip.classList.remove('is-show', 'is-below', 'is-left');
      currentEl = null;
    }

    function scheduleHide() {
      clearHide();
      hideTimer = window.setTimeout(hideNow, 90);
    }

    function showFor(el) {
      if (!el) return;
      if (!shouldShowTooltipFor(el, sidebarEl)) return;

      const label = getTipLabel(el);
      if (!label) return;

      clearHide();

      if (card) card.textContent = label;

      const placement = getPlacement(el);
      tip.classList.toggle('is-below', placement === 'bottom');
      tip.classList.toggle('is-left', placement === 'left');

      currentEl = el;

      // Primeiro mostra para medir width/height corretamente em alguns browsers
      tip.classList.add('is-show');

      // Posiciona
      if (placement === 'bottom') {
        positionTooltipBottom(tip, el);
      } else if (placement === 'left') {
        positionTooltipLeft(tip, el);
      } else {
        positionTooltipRight(tip, el);
      }

      // Reposiciona leve depois (corrige width após texto)
      window.setTimeout(() => {
        if (!currentEl || currentEl !== el) return;
        if (placement === 'bottom') positionTooltipBottom(tip, el);
        else if (placement === 'left') positionTooltipLeft(tip, el);
        else positionTooltipRight(tip, el);
      }, 0);
    }

    // Detecta alvo:
    // - Qualquer elemento com [data-tip]
    // - Sidebar (quando colapsado): a.sidebar__item e button.sidebar__logout mesmo sem [data-tip]
    function resolveTargetFromEvent(e) {
      const dt = e.target.closest?.('[data-tip]');
      if (dt) return dt;

      if (sidebarEl && sidebarEl.contains(e.target)) {
        const a = e.target.closest?.('a.sidebar__item');
        if (a) return a;

        const b = e.target.closest?.('button.sidebar__logout');
        if (b) return b;
      }

      return null;
    }

    document.addEventListener('mouseenter', (e) => {
      const el = resolveTargetFromEvent(e);
      if (!el) return;
      showFor(el);
    }, true);

    document.addEventListener('mousemove', () => {
      if (!currentEl) return;
      if (!shouldShowTooltipFor(currentEl, sidebarEl)) { hideNow(); return; }

      const placement = getPlacement(currentEl);
      if (placement === 'bottom') positionTooltipBottom(tip, currentEl);
      else if (placement === 'left') positionTooltipLeft(tip, currentEl);
      else positionTooltipRight(tip, currentEl);
    }, true);

    document.addEventListener('mouseleave', (e) => {
      const leaving = resolveTargetFromEvent(e);
      if (!leaving) return;
      scheduleHide();
    }, true);

    document.addEventListener('click', hideNow);

    window.addEventListener('scroll', () => {
      if (!currentEl) return;
      if (!shouldShowTooltipFor(currentEl, sidebarEl)) { hideNow(); return; }

      const placement = getPlacement(currentEl);
      if (placement === 'bottom') positionTooltipBottom(tip, currentEl);
      else if (placement === 'left') positionTooltipLeft(tip, currentEl);
      else positionTooltipRight(tip, currentEl);
    }, true);

    window.addEventListener('resize', () => {
      if (!currentEl) return;
      if (!shouldShowTooltipFor(currentEl, sidebarEl)) { hideNow(); return; }

      const placement = getPlacement(currentEl);
      if (placement === 'bottom') positionTooltipBottom(tip, currentEl);
      else if (placement === 'left') positionTooltipLeft(tip, currentEl);
      else positionTooltipRight(tip, currentEl);
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') hideNow();
    });

    // Se sidebar alternar colapso, tooltip pode ficar “perdido”
    window.addEventListener('sidebar:toggle', () => hideNow());

    return { hideNow };
  }

  /* =========================================================
     ALERTS RENDER (payload)
========================================================= */
  function renderAlerts(alertsBodyEl, payload, opts = {}) {
    const emptyHtml = String(opts.emptyHtml || '').trim()
      || `<div class="topbar-popover__empty">Sem alertas no momento.</div>`;

    if (!alertsBodyEl) return;

    const blocks = Array.isArray(payload?.blocks) ? payload.blocks : null;

    // ✅ Modelo-regra
    if (blocks && blocks.length) {
      const pill = (type, value) => {
        const t = String(type || 'info').toLowerCase();
        const v = Number(value || 0);
        return `<span class="sv-alertpill sv-alertpill--${escapeHtml(t)}">${v}</span>`;
      };

      const blocksHtml = blocks.map((b) => {
        const title = escapeHtml(b?.title || 'Bloco');
        const href = String(b?.href || '').trim();
        const sum = b?.summary || {};

        const sSuccess = pill('success', sum.success);
        const sInfo    = pill('info',    sum.info);
        const sWarning = pill('warning', sum.warning);
        const sDanger  = pill('danger',  sum.danger);

        const items = Array.isArray(b?.items) ? b.items : [];
        const itemsHtml = items.slice(0, 4).map((a) => {
          const type = String(a?.type || 'info').toLowerCase();
          const itTitle = escapeHtml(a?.title || 'Alerta');
          const msg = escapeHtml(a?.message || '');
          const itHref = String(a?.href || href || '').trim();

          const tag = itHref ? 'a' : 'div';
          const hrefAttr = itHref ? ` href="${escapeHtml(itHref)}"` : '';

          return `
            <${tag} class="sv-alertitem sv-alertitem--${escapeHtml(type)}"${hrefAttr}>
              <span class="sv-alertitem__dot" aria-hidden="true"></span>
              <div class="sv-alertitem__txt">
                <div class="sv-alertitem__t">${itTitle}</div>
                <div class="sv-alertitem__m">${msg}</div>
              </div>
            </${tag}>
          `;
        }).join('');

        const cta = href ? `<a class="sv-alertblock__cta" href="${escapeHtml(href)}">Abrir</a>` : '';

        return `
          <section class="sv-alertblock">
            <header class="sv-alertblock__head">
              <div class="sv-alertblock__title">${title}</div>
              <div class="sv-alertblock__right">${cta}</div>
            </header>

            <div class="sv-alertblock__meta" aria-label="Resumo de severidade">
              <div class="sv-alertblock__pills">
                ${sSuccess}${sInfo}${sWarning}${sDanger}
              </div>
              <div class="sv-alertblock__legend">
                <span class="sv-legend">OK</span>
                <span class="sv-legend">Info</span>
                <span class="sv-legend">Atenção</span>
                <span class="sv-legend">Crítico</span>
              </div>
            </div>

            <div class="sv-alertblock__list">
              ${itemsHtml || emptyHtml}
            </div>
          </section>
        `;
      }).join('');

      alertsBodyEl.innerHTML = `<div class="sv-alertblocks">${blocksHtml}</div>`;
      return;
    }

    // ✅ Fallback lista plana
    const list = Array.isArray(payload?.alerts) ? payload.alerts : [];
    if (!list.length) {
      alertsBodyEl.innerHTML = emptyHtml;
      return;
    }

    const itemsHtml = list.map((a) => {
      const type = String(a?.type || 'info').toLowerCase();
      const title = escapeHtml(a?.title || 'Alerta');
      const message = escapeHtml(a?.message || '');
      const href = String(a?.href || '').trim();

      const tag = href ? 'a' : 'div';
      const hrefAttr = href ? ` href="${escapeHtml(href)}"` : '';

      return `
        <${tag} class="sv-alert sv-alert--${escapeHtml(type)}"${hrefAttr} data-href="${escapeHtml(href)}">
          <div class="sv-alert__row">
            <div class="sv-alert__dot" aria-hidden="true"></div>
            <div class="sv-alert__text">
              <div class="sv-alert__title">${title}</div>
              <div class="sv-alert__msg">${message}</div>
            </div>
          </div>
        </${tag}>
      `;
    }).join('');

    alertsBodyEl.innerHTML = `<div class="sv-alerts">${itemsHtml}</div>`;
  }

  window.UIComponents = {
    confirm,
    initTooltips,
    renderAlerts,
    escapeHtml,
  };
})();
