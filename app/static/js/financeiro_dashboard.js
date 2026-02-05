// app/static/js/financeiro_dashboard.js
(function(){
  function ready(fn){
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', fn);
    else fn();
  }

  ready(function(){
    const root = document.getElementById('finAgenda');
    if(!root) return;

    root.addEventListener('click', (e) => {
      const btn = e.target.closest('button.fin-acc__head');
      if(!btn) return;

      const acc = btn.closest('.fin-acc');
      if(!acc) return;

      // comportamento simples: abre/fecha o próprio dia
      acc.classList.toggle('is-open');
    });
  });
})();