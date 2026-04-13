// app/static/js/financeiro/financeiro_cadastro_inline.js
(function () {
  function appUrl(path) {
    return (typeof window.appUrl === "function") ? window.appUrl(path) : path;
  }

  function bindInlineModal() {
    const modal = document.getElementById("finCadastroInlineModal");
    const frame = document.getElementById("finCadastroInlineFrame");
    const title = document.getElementById("finCadastroInlineTitle");
    const closeBtn = document.getElementById("finCadastroInlineClose");
    if (!modal || !frame || !title || !closeBtn) return;

    let hasDirtyChanges = false;

    function closeModal(force = false) {
      if (!force && hasDirtyChanges) return;
      modal.setAttribute("aria-hidden", "true");
      modal.classList.remove("is-open");
      frame.setAttribute("src", "about:blank");
      hasDirtyChanges = false;
    }

    async function requestClose() {
      if (hasDirtyChanges && window.UIComponents && typeof window.UIComponents.confirm === "function") {
        const confirmed = await window.UIComponents.confirm({
          eyebrow: "Alterações pendentes",
          title: "Fechar cadastro sem salvar?",
          message: "Há alterações não salvas neste cadastro. Se continuar, os dados preenchidos serão descartados.",
          confirmLabel: "Fechar sem salvar",
          cancelLabel: "Continuar editando",
        });
        if (!confirmed) return;
      }
      closeModal(true);
    }

    window.FinCadastroInline = {
      open(tipo = "cliente", modalTitle = "Novo cadastro") {
        title.textContent = modalTitle;
        frame.setAttribute("src", appUrl(`/app/templates/cadastros_ficha_embed.php?modo=cadastro&tipo=${encodeURIComponent(tipo)}&embed=1`));
        modal.setAttribute("aria-hidden", "false");
        modal.classList.add("is-open");
      },
      close() {
        requestClose().catch(() => {});
      },
    };

    closeBtn.addEventListener("click", () => {
      requestClose().catch(() => {});
    });

    modal.addEventListener("click", (event) => {
      if (event.target === modal) {
        requestClose().catch(() => {});
      }
    });

    window.addEventListener("message", (event) => {
      if (event.origin !== window.location.origin) return;
      const payload = event.data || null;
      if (!payload || typeof payload.type !== "string") return;

      if (payload.type === "sv:inline-cadastro-state") {
        hasDirtyChanges = Boolean(payload.dirty);
        return;
      }

      if (payload.type === "sv:inline-cadastro-request-close") {
        closeModal(true);
        return;
      }

      if (payload.type !== "sv:inline-cadastro-saved") return;

      window.dispatchEvent(new CustomEvent("fin:inline-cadastro-saved", {
        detail: payload.cadastro || {},
      }));

      try {
        if (window.Toast?.success) {
          window.Toast.success(payload.saved === "updated" ? "Cadastro atualizado com sucesso." : "Cadastro criado com sucesso.");
        }
      } catch (_) {}

      closeModal(true);
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", bindInlineModal);
  } else {
    bindInlineModal();
  }
})();
