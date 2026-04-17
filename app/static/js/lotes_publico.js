(function () {
  function showPublicToast(kind, message) {
    let el = document.getElementById("lotPublicToast");
    if (!el) {
      el = document.createElement("div");
      el.id = "lotPublicToast";
      el.className = "sys-toast";
      document.body.appendChild(el);
    }

    el.textContent = String(message || "");
    el.className = `sys-toast is-${String(kind || "info")}`;
    requestAnimationFrame(() => el.classList.add("is-on"));
    window.clearTimeout(showPublicToast._t);
    showPublicToast._t = window.setTimeout(() => {
      el.classList.remove("is-on");
      el.classList.add("is-closing");
      window.setTimeout(() => el.classList.remove("is-closing"), 220);
    }, 2600);
  }

  function readJsonScript(id) {
    const node = document.getElementById(id);
    if (!node) return null;
    try {
      return JSON.parse(node.textContent || "null");
    } catch (_) {
      return null;
    }
  }

  function bindPublicGallery() {
    const items = readJsonScript("lotPublicGalleryPayload");
    if (!Array.isArray(items) || !items.length) return;

    const modal = document.getElementById("lotPublicImageViewer");
    const stage = document.getElementById("lotPublicImageViewerStage");
    const name = document.getElementById("lotPublicImageViewerName");
    const prev = document.getElementById("lotPublicImageViewerPrev");
    const next = document.getElementById("lotPublicImageViewerNext");
    const close = document.getElementById("lotPublicImageViewerClose");
    const download = document.getElementById("lotPublicImageViewerDownload");
    const triggers = Array.from(document.querySelectorAll("[data-lot-public-gallery-open]"));

    if (!modal || !stage || !name || !prev || !next || !close || !download || !triggers.length) {
      return;
    }

    let index = 0;

    function render() {
      const current = items[index];
      if (!current) return;

      stage.innerHTML = "";
      name.textContent = String(current.name || "Imagem");
      download.href = String(current.downloadUrl || current.previewUrl || "#");

      const img = document.createElement("img");
      img.src = String(current.previewUrl || "");
      img.alt = String(current.name || "Imagem");
      stage.appendChild(img);

      prev.disabled = index <= 0;
      next.disabled = index >= items.length - 1;
    }

    function open(startIndex) {
      index = Math.max(0, Math.min(startIndex, items.length - 1));
      render();
      modal.classList.remove("is-closing");
      modal.classList.add("is-open");
      modal.setAttribute("aria-hidden", "false");
    }

    function closeModal() {
      modal.classList.add("is-closing");
      window.setTimeout(() => {
        modal.classList.remove("is-open", "is-closing");
        modal.setAttribute("aria-hidden", "true");
      }, 240);
    }

    triggers.forEach((trigger) => {
      trigger.addEventListener("click", () => {
        const raw = Number(trigger.getAttribute("data-lot-public-gallery-open") || 0);
        open(Number.isFinite(raw) ? raw : 0);
      });
    });

    prev.addEventListener("click", () => {
      if (index <= 0) return;
      index -= 1;
      render();
    });

    next.addEventListener("click", () => {
      if (index >= items.length - 1) return;
      index += 1;
      render();
    });

    close.addEventListener("click", closeModal);
    modal.addEventListener("click", (event) => {
      if (event.target === modal) {
        closeModal();
      }
    });
  }

  function bindPublicShare() {
    const triggers = Array.from(document.querySelectorAll("[data-lot-public-share]"));
    if (!triggers.length) return;

    triggers.forEach((trigger) => {
      trigger.addEventListener("click", async () => {
        const url = String(trigger.getAttribute("data-lot-public-share") || "").trim();
        if (!url) return;

        const title = document.title || "Ficha pública do lote";
        try {
          if (navigator.clipboard && typeof navigator.clipboard.writeText === "function") {
            await navigator.clipboard.writeText(url);
            showPublicToast("success", "Link da ficha copiado para compartilhamento.");
            return;
          }

          const field = document.createElement("textarea");
          field.value = url;
          field.setAttribute("readonly", "readonly");
          field.style.position = "fixed";
          field.style.opacity = "0";
          field.style.pointerEvents = "none";
          document.body.appendChild(field);
          field.select();
          const copied = document.execCommand("copy");
          document.body.removeChild(field);
          if (copied) {
            showPublicToast("success", "Link da ficha copiado para compartilhamento.");
            return;
          }
        } catch (_) {
          showPublicToast("danger", "Não foi possível compartilhar a ficha.");
          return;
        }

        showPublicToast("warning", "Este navegador não permitiu copiar o link automaticamente.");
      });
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", () => {
      bindPublicGallery();
      bindPublicShare();
    }, { once: true });
  } else {
    bindPublicGallery();
    bindPublicShare();
  }
})();
