// app/static/js/financeiro_contas_pagar.js
(function(){
  function ready(fn){
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', fn);
    else fn();
  }

  ready(function(){
    const data = Array.isArray(window.__CP_MOCK__) ? window.__CP_MOCK__ : [];
    let rows = [...data];
    let viewMonth = new Date();
    let pendingDeleteId = null;

    const el = (id) => document.getElementById(id);

    const els = {
      monthLabel: el('cpMonthLabel'),
      prev: el('cpPrev'),
      next: el('cpNext'),

      totalOpen: el('cpTotalOpen'),
      totalDone: el('cpTotalDone'),
      count: el('cpCount'),

      filterImovel: el('cpFilterImovel'),
      filterStatus: el('cpFilterStatus'),
      filterCategoria: el('cpFilterCategoria'),
      filterFixa: el('cpFilterFixa'),
      filterSearch: el('cpFilterSearch'),
      clear: el('cpClear'),

      tbody: el('cpTbody'),

      newBtn: el('cpNew'),

      modal: el('cpModal'),
      modalTitle: el('cpModalTitle'),
      modalClose: el('cpModalClose'),
      cancel: el('cpCancel'),
      form: el('cpForm'),

      id: el('cpId'),
      conta: el('cpConta'),
      valor: el('cpValor'),
      imovel: el('cpImovel'),
      categoria: el('cpCategoria'),
      data: el('cpData'),
      fixa: el('cpFixa'),

      delModal: el('cpDelModal'),
      delClose: el('cpDelClose'),
      delCancel: el('cpDelCancel'),
      delConfirm: el('cpDelConfirm'),

      filtersWrap: el('cpFiltersWrap'),
      filtersToggle: el('cpFiltersToggle'),
      filtersIcon: el('cpFiltersIcon'),
    };

    if(!els.tbody || !els.prev || !els.next || !els.monthLabel) return;

    function moneyBR(v){
      const n = Number(v || 0);
      return n.toLocaleString('pt-BR', { style:'currency', currency:'BRL' });
    }

    function formatMonthLabel(dt){
      const m = dt.toLocaleString('pt-BR', { month:'long' });
      const y = dt.getFullYear();
      const mm = m.charAt(0).toUpperCase() + m.slice(1);
      return `${mm} / ${y}`;
    }

    function sameMonth(dateStr, base){
      const d = new Date(dateStr + 'T00:00:00');
      return d.getMonth() === base.getMonth() && d.getFullYear() === base.getFullYear();
    }

    function applyFilters(list){
      const imovel = (els.filterImovel?.value || '').trim();
      const status = (els.filterStatus?.value || '').trim();
      const categoria = (els.filterCategoria?.value || '').trim();
      const fixa = (els.filterFixa?.value || '').trim();
      const search = (els.filterSearch?.value || '').trim().toLowerCase();

      return list
        .filter(r => sameMonth(r.data, viewMonth))
        .filter(r => !imovel || r.imovel === imovel)
        .filter(r => !status || r.status === status)
        .filter(r => !categoria || r.categoria === categoria)
        .filter(r => fixa === '' ? true : (String(Number(Boolean(r.fixa))) === fixa))
        .filter(r => !search || String(r.conta || '').toLowerCase().includes(search));
    }

    function calcTotals(list){
      let open = 0;
      let done = 0;
      for (const r of list){
        if (r.status === 'done') done += Number(r.valor || 0);
        else open += Number(r.valor || 0);
      }
      if(els.totalOpen) els.totalOpen.textContent = moneyBR(open);
      if(els.totalDone) els.totalDone.textContent = moneyBR(done);
    }

    function toBRDate(iso){
      if(!iso) return '';
      const [y,m,d] = iso.split('-');
      return `${d}/${m}/${y}`;
    }

    function escapeHtml(s){
      return String(s ?? '')
        .replaceAll('&','&amp;')
        .replaceAll('<','&lt;')
        .replaceAll('>','&gt;')
        .replaceAll('"','&quot;')
        .replaceAll("'","&#039;");
    }

    function render(){
      els.monthLabel.textContent = formatMonthLabel(viewMonth);

      const filtered = applyFilters(rows);
      if(els.count) els.count.textContent = `${filtered.length} itens`;

      calcTotals(filtered);

      els.tbody.innerHTML = filtered.map(r => {
        const fixaTxt = r.fixa ? 'Sim' : 'Não';
        const statusIcon = r.status === 'done'
          ? '<span class="fin-status is-done" title="Pago"><i class="fa-solid fa-circle-check"></i></span>'
          : '<span class="fin-status is-open" title="A pagar"><i class="fa-solid fa-circle-dot"></i></span>';

        const toggleTip = r.status === 'done' ? 'Reabrir' : 'Baixar';
        const toggleIcon = r.status === 'done' ? 'fa-rotate-left' : 'fa-check';

        return `
          <tr data-id="${r.id}">
            <td class="t-left">${escapeHtml(r.conta)}</td>
            <td class="t-right">${moneyBR(r.valor)}</td>
            <td class="t-center">${escapeHtml(r.imovel)}</td>
            <td class="t-center">${escapeHtml(toBRDate(r.data))}</td>
            <td class="t-center">${escapeHtml(r.categoria)}</td>
            <td class="t-center">${fixaTxt}</td>
            <td class="t-center">${statusIcon}</td>
            <td class="t-center">
              <div class="fin-actions-row">
                <button class="fin-action-ico" data-act="edit" data-tip="Editar" type="button"><i class="fa-solid fa-pen"></i></button>
                <button class="fin-action-ico" data-act="toggle" data-tip="${toggleTip}" type="button"><i class="fa-solid ${toggleIcon}"></i></button>
                <button class="fin-action-ico" data-act="del" data-tip="Excluir" type="button"><i class="fa-solid fa-trash"></i></button>
              </div>
            </td>
          </tr>
        `;
      }).join('');
    }

    function getById(id){
      return rows.find(r => String(r.id) === String(id));
    }

    function upsert(item){
      const idx = rows.findIndex(r => String(r.id) === String(item.id));
      if(idx >= 0) rows[idx] = item;
      else rows.unshift(item);
    }

    function openModal(mode, item){
      if(!els.modal) return;
      if(els.modalTitle) els.modalTitle.textContent = mode === 'edit' ? 'Editar lançamento' : 'Novo lançamento';

      if(els.id) els.id.value = item?.id ?? '';
      if(els.conta) els.conta.value = item?.conta ?? '';
      if(els.valor) els.valor.value = item?.valor ?? '';
      if(els.imovel) els.imovel.value = item?.imovel ?? '';
      if(els.categoria) els.categoria.value = item?.categoria ?? '';
      if(els.data) els.data.value = item?.data ?? '';
      if(els.fixa) els.fixa.value = String(Number(Boolean(item?.fixa ?? true)));

      els.modal.classList.add('is-open');
      els.modal.setAttribute('aria-hidden','false');

      setTimeout(() => { if(els.conta) els.conta.focus(); }, 50);
    }

    function closeModal(){
      if(!els.modal) return;
      els.modal.classList.remove('is-open');
      els.modal.setAttribute('aria-hidden','true');
    }

    function openDelModal(id){
      pendingDeleteId = id;
      if(!els.delModal) return;
      els.delModal.classList.add('is-open');
      els.delModal.setAttribute('aria-hidden','false');
    }

    function closeDelModal(){
      pendingDeleteId = null;
      if(!els.delModal) return;
      els.delModal.classList.remove('is-open');
      els.delModal.setAttribute('aria-hidden','true');
    }

    // Navegação mês
    els.prev.addEventListener('click', () => {
      viewMonth = new Date(viewMonth.getFullYear(), viewMonth.getMonth() - 1, 1);
      render();
    });

    els.next.addEventListener('click', () => {
      viewMonth = new Date(viewMonth.getFullYear(), viewMonth.getMonth() + 1, 1);
      render();
    });

    // Novo
    if(els.newBtn) els.newBtn.addEventListener('click', () => openModal('new', null));

    // Fechar modal
    if(els.modalClose) els.modalClose.addEventListener('click', closeModal);
    if(els.cancel) els.cancel.addEventListener('click', closeModal);

    // Filtros (desktop)
    ['filterImovel','filterStatus','filterCategoria','filterFixa'].forEach(k => {
      if(els[k]) els[k].addEventListener('change', render);
    });
    if(els.filterSearch) els.filterSearch.addEventListener('input', render);

    if(els.clear) els.clear.addEventListener('click', () => {
      if(els.filterImovel) els.filterImovel.value = '';
      if(els.filterStatus) els.filterStatus.value = '';
      if(els.filterCategoria) els.filterCategoria.value = '';
      if(els.filterFixa) els.filterFixa.value = '';
      if(els.filterSearch) els.filterSearch.value = '';
      render();
    });

    // Sanfona filtros (mobile) — usa is-open
    if(els.filtersToggle && els.filtersWrap){
      els.filtersToggle.addEventListener('click', () => {
        const open = els.filtersWrap.classList.toggle('is-open');
        if(els.filtersIcon){
          els.filtersIcon.classList.toggle('fa-chevron-down', !open);
          els.filtersIcon.classList.toggle('fa-chevron-up', open);
        }
      });
    }

    // Ações da tabela
    els.tbody.addEventListener('click', (e) => {
      const btn = e.target.closest('button[data-act]');
      if(!btn) return;

      const tr = e.target.closest('tr[data-id]');
      if(!tr) return;

      const id = tr.getAttribute('data-id');
      const act = btn.getAttribute('data-act');
      const item = getById(id);
      if(!item) return;

      if(act === 'edit') openModal('edit', item);

      if(act === 'toggle'){
        item.status = item.status === 'done' ? 'open' : 'done';
        upsert(item);
        render();
      }

      if(act === 'del') openDelModal(id);
    });

    // Submit modal
    if(els.form){
      els.form.addEventListener('submit', () => {
        const isEdit = Boolean(els.id && els.id.value);
        const id = isEdit ? Number(els.id.value) : Date.now();

        const payload = {
          id,
          conta: (els.conta?.value || '').trim(),
          valor: Number(String(els.valor?.value || '').replace(',','.')) || 0,
          imovel: els.imovel?.value || '',
          categoria: els.categoria?.value || '',
          data: els.data?.value || '',
          fixa: (els.fixa?.value || '1') === '1',
          status: isEdit ? (getById(id)?.status || 'open') : 'open'
        };

        upsert(payload);
        closeModal();
        render();
      });
    }

    // Delete modal
    if(els.delClose) els.delClose.addEventListener('click', closeDelModal);
    if(els.delCancel) els.delCancel.addEventListener('click', closeDelModal);

    if(els.delConfirm){
      els.delConfirm.addEventListener('click', () => {
        if(pendingDeleteId == null) return;
        rows = rows.filter(r => String(r.id) !== String(pendingDeleteId));
        closeDelModal();
        render();
      });
    }

    // Inicia
    viewMonth = new Date();
    render();
  });
})();