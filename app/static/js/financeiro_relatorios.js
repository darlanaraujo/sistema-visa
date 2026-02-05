// app/static/js/financeiro_relatorios.js
(function(){
  const root = document.getElementById('frPage');
  if(!root) return;

  const mock = window.__FR_MOCK__ || {};
  const DEFAULT_FAV_LIMIT = 4;
  const FAV_LIMIT = Number(mock.fav_limit || DEFAULT_FAV_LIMIT);
  const LS_KEY = 'fin_reports_favorites_v1';

  // Elements
  const periodEl = document.getElementById('frPeriod');
  const typeEl   = document.getElementById('frType');
  const searchEl = document.getElementById('frSearch');
  const btnClear = document.getElementById('frClear');
  const btnRun   = document.getElementById('frRun');

  const favGrid  = document.getElementById('frFavGrid');
  const favEmpty = document.getElementById('frFavEmpty');

  const toastEl  = document.getElementById('frToast');

  const modal    = document.getElementById('frModal');
  const modalClose = document.getElementById('frModalClose');
  const modalTitle = document.getElementById('frModalTitle');
  const modalPeriod = document.getElementById('frModalPeriod');
  const modalType = document.getElementById('frModalType');
  const modalReport = document.getElementById('frModalReport');
  const modalRun = document.getElementById('frModalRun');
  const modalExport = document.getElementById('frModalGhost');

  // Filters wrap (mobile accordion)
  const wrap = document.getElementById('frFiltersWrap');
  const toggle = document.getElementById('frFiltersToggle');
  const icon = document.getElementById('frFiltersIcon');

  // State
  let favorites = loadFavorites();
  let lastOpened = null;

  // -----------------------------
  // Helpers
  // -----------------------------
  function showToast(msg, kind){
    // Preferência: Toast global do sistema
    try{
      if(window.Toast){
        if(kind && typeof window.Toast[kind] === 'function'){
          window.Toast[kind](msg);
          return;
        }
        if(typeof window.Toast.show === 'function'){
          window.Toast.show(msg);
          return;
        }
      }
    }catch(_){}

    // Fallback: toast local do relatório
    if(!toastEl) return;
    toastEl.textContent = msg;
    toastEl.classList.add('is-on');
    clearTimeout(showToast._t);
    showToast._t = setTimeout(() => toastEl.classList.remove('is-on'), 2600);
  }

  function loadFavorites(){
    try{
      const raw = localStorage.getItem(LS_KEY);
      if(raw){
        const arr = JSON.parse(raw);
        if(Array.isArray(arr)) return arr.slice(0, FAV_LIMIT);
      }
    }catch(e){}
    // fallback do PHP/mock
    return Array.isArray(mock.favorites) ? mock.favorites.slice(0, FAV_LIMIT) : [];
  }

  function saveFavorites(){
    try{
      localStorage.setItem(LS_KEY, JSON.stringify(favorites.slice(0, FAV_LIMIT)));
    }catch(e){}
  }

  function isFav(id){
    return favorites.indexOf(id) !== -1;
  }

  function setFav(id, on){
    if(on){
      if(isFav(id)) return true;
      if(favorites.length >= FAV_LIMIT){
        showToast(`Limite de favoritos atingido (${FAV_LIMIT}). Remova um favorito para adicionar outro.`, 'warning');
        return false;
      }
      favorites.push(id);
      favorites = favorites.slice(0, FAV_LIMIT);
      saveFavorites();
      showToast('Adicionado aos favoritos.', 'success');
      return true;
    }else{
      const idx = favorites.indexOf(id);
      if(idx !== -1){
        favorites.splice(idx, 1);
        saveFavorites();
        showToast('Removido dos favoritos.', 'warning');
      }
      return true;
    }
  }

  function normalizeStr(s){
    return String(s || '').toLowerCase().trim();
  }

  function getFilterState(){
    return {
      period: periodEl ? periodEl.value : 'this_month',
      type: typeEl ? typeEl.value : '',
      search: normalizeStr(searchEl ? searchEl.value : ''),
    };
  }

  function matchesFilters(card, st){
    const group = (card.getAttribute('data-group') || '').toLowerCase();
    const name  = normalizeStr(card.getAttribute('data-name'));
    const desc  = normalizeStr(card.getAttribute('data-desc'));
    const tags  = normalizeStr(card.getAttribute('data-tags'));

    // Tipo (grupo)
    if(st.type && group !== st.type) return false;

    // Busca
    if(st.search){
      const hay = `${name} ${desc} ${tags} ${group}`;
      if(hay.indexOf(st.search) === -1) return false;
    }

    // Período no mock não filtra cards (só passa pro modal/execução)
    return true;
  }

  function applyFilters(){
    const st = getFilterState();
    const cards = root.querySelectorAll('.fin-rep-card[data-report-id]');
    let shown = 0;
    cards.forEach(card => {
      const ok = matchesFilters(card, st);
      card.style.display = ok ? '' : 'none';
      if(ok) shown++;
    });
    return shown;
  }

  function updateStarButtons(){
    const buttons = root.querySelectorAll('.fin-rep-card__fav');
    buttons.forEach(btn => {
      const card = btn.closest('.fin-rep-card');
      if(!card) return;
      const id = card.getAttribute('data-report-id');
      const on = isFav(id);
      btn.classList.toggle('is-on', on);
      btn.setAttribute('aria-label', on ? 'Remover dos favoritos' : 'Adicionar aos favoritos');
      btn.setAttribute('title', on ? 'Favorito' : 'Favoritar');
    });
  }

  function renderFavorites(){
    if(!favGrid) return;

    favGrid.innerHTML = '';

    const allCards = Array.from(root.querySelectorAll('.fin-rep-card[data-report-id]'));
    const byId = new Map();
    allCards.forEach(c => byId.set(c.getAttribute('data-report-id'), c));

    favorites.slice(0, FAV_LIMIT).forEach(id => {
      const src = byId.get(id);
      if(!src) return;

      const clone = src.cloneNode(true);
      clone.style.display = '';

      const favBtn = clone.querySelector('.fin-rep-card__fav');
      if(favBtn){
        favBtn.classList.add('is-on');
        favBtn.setAttribute('aria-label', 'Remover dos favoritos');
        favBtn.setAttribute('title', 'Favorito');
      }
      favGrid.appendChild(clone);
    });

    if(favEmpty){
      favEmpty.hidden = favorites.length !== 0;
    }
  }

  function openModalFromCard(card){
    if(!card || !modal) return;

    const st = getFilterState();

    const rid = card.getAttribute('data-report-id') || '—';
    const name = card.getAttribute('data-name') || 'Relatório';
    const group = card.getAttribute('data-group') || '—';

    lastOpened = { rid, name, group };

    modalTitle.textContent = name;
    modalPeriod.textContent = periodEl ? periodEl.options[periodEl.selectedIndex].text : st.period;
    modalType.textContent = typeEl ? (typeEl.options[typeEl.selectedIndex].text || 'Todos') : 'Todos';
    modalReport.textContent = `${name} (${rid})`;

    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
  }

  function closeModal(){
    if(!modal) return;
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
  }

  function isMobile(){
    return window.matchMedia('(max-width: 700px)').matches;
  }

  function applyFilterAccordion(){
    if(!wrap || !toggle) return;
    if(isMobile()){
      wrap.classList.add('is-open');
    }else{
      wrap.classList.remove('is-open');
    }
  }

  // -----------------------------
  // Events
  // -----------------------------
  root.addEventListener('click', (e) => {
    const favBtn = e.target.closest('.fin-rep-card__fav');
    if(favBtn){
      e.preventDefault();
      e.stopPropagation();

      const card = favBtn.closest('.fin-rep-card');
      if(!card) return;
      const id = card.getAttribute('data-report-id');
      const turnOn = !isFav(id);

      const ok = setFav(id, turnOn);
      if(!ok) return;

      updateStarButtons();
      renderFavorites();
      return;
    }

    const card = e.target.closest('.fin-rep-card');
    if(card){
      openModalFromCard(card);
      return;
    }
  });

  root.addEventListener('keydown', (e) => {
    const card = e.target.closest && e.target.closest('.fin-rep-card');
    if(!card) return;
    if(e.key === 'Enter' || e.key === ' '){
      e.preventDefault();
      openModalFromCard(card);
    }
  });

  if(btnClear){
    btnClear.addEventListener('click', () => {
      if(periodEl) periodEl.value = 'this_month';
      if(typeEl) typeEl.value = '';
      if(searchEl) searchEl.value = '';
      applyFilters();
      showToast('Filtros limpos.', 'warning');
    });
  }

  if(btnRun){
    btnRun.addEventListener('click', () => {
      const shown = applyFilters();
      showToast(`Executado (mock): ${shown} relatórios encontrados pelo filtro.`, 'success');
    });
  }

  if(searchEl){
    searchEl.addEventListener('input', () => {
      applyFilters();
    });
  }
  if(typeEl){
    typeEl.addEventListener('change', () => {
      applyFilters();
    });
  }
  if(periodEl){
    periodEl.addEventListener('change', () => {
      showToast('Período atualizado (mock).', 'success');
    });
  }

  if(modalClose) modalClose.addEventListener('click', closeModal);

  if(modal){
    modal.addEventListener('click', (e) => {
      if(e.target === modal) closeModal();
    });
  }

  document.addEventListener('keydown', (e) => {
    if(e.key === 'Escape') closeModal();
  });

  if(modalRun){
    modalRun.addEventListener('click', () => {
      if(!lastOpened) return;
      showToast(`Executar (mock): ${lastOpened.name}`, 'success');
    });
  }

  if(modalExport){
    modalExport.addEventListener('click', () => {
      if(!lastOpened) return;
      showToast('Exportação (mock): ainda sem geração de arquivo nesta etapa.', 'warning');
    });
  }

  if(toggle){
    toggle.addEventListener('click', () => {
      if(!isMobile()) return;
      wrap.classList.toggle('is-open');
      if(icon){
        icon.style.transform = wrap.classList.contains('is-open') ? 'rotate(180deg)' : 'rotate(0deg)';
      }
    });
  }

  window.addEventListener('resize', applyFilterAccordion);

  // -----------------------------
  // Init
  // -----------------------------
  favorites = favorites.slice(0, FAV_LIMIT);
  saveFavorites();
  updateStarButtons();
  renderFavorites();
  applyFilters();
  applyFilterAccordion();
})();