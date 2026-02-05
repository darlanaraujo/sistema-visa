<?php
// app/modules/financeiro/_nav.php

function fin_is_active(string $needle): bool {
  $path = (string)($_SERVER['REQUEST_URI'] ?? '');
  return strpos($path, $needle) !== false;
}

/**
 * MENU DO MÓDULO FINANCEIRO (apenas o que pertence ao módulo)
 * - Dashboard
 * - Contas a Pagar
 * - Contas a Receber
 * - Relatórios
 *
 * Categorias / Imóveis / Clientes saíram do módulo (não devem aparecer aqui).
 */
$items = [
  ['href' => '/sistema-visa/app/templates/financeiro.php', 'label' => 'Dashboard', 'icon' => 'fa-solid fa-gauge', 'key' => 'financeiro.php'],
  ['href' => '/sistema-visa/app/templates/financeiro_contas_pagar.php', 'label' => 'Contas a Pagar', 'icon' => 'fa-solid fa-file-invoice-dollar', 'key' => 'financeiro_contas_pagar.php'],
  ['href' => '/sistema-visa/app/templates/financeiro_contas_receber.php', 'label' => 'Contas a Receber', 'icon' => 'fa-solid fa-hand-holding-dollar', 'key' => 'financeiro_contas_receber.php'],
  ['href' => '/sistema-visa/app/templates/financeiro_relatorios.php', 'label' => 'Relatórios', 'icon' => 'fa-solid fa-chart-pie', 'key' => 'financeiro_relatorios.php'],
];

$activeLabel = 'Menu do Financeiro';
$activeIcon  = 'fa-solid fa-list';

foreach ($items as $it) {
  if (fin_is_active($it['key'])) {
    $activeLabel = $it['label'];
    $activeIcon  = $it['icon'];
    break;
  }
}
?>

<div class="fin-nav-wrap is-collapsed" id="finNavWrap">
  <div class="fin-nav-summary" id="finNavSummary">
    <span><i class="<?= htmlspecialchars($activeIcon) ?>"></i> <?= htmlspecialchars($activeLabel) ?></span>
    <i class="fa-solid fa-chevron-down"></i>
  </div>

  <div class="fin-nav">
    <?php foreach ($items as $it): ?>
      <?php $active = fin_is_active($it['key']); ?>
      <a class="fin-nav__item <?= $active ? 'is-active' : '' ?>" href="<?= htmlspecialchars($it['href']) ?>">
        <i class="<?= htmlspecialchars($it['icon']) ?>"></i>
        <span><?= htmlspecialchars($it['label']) ?></span>
      </a>
    <?php endforeach; ?>
  </div>
</div>

<script>
(function(){
  const wrap = document.getElementById('finNavWrap');
  const summary = document.getElementById('finNavSummary');
  if(!wrap || !summary) return;

  function isMobile(){
    return window.matchMedia('(max-width: 700px)').matches;
  }

  function apply(){
    if(isMobile()){
      wrap.classList.add('is-collapsed');
    }else{
      wrap.classList.remove('is-collapsed');
    }
  }

  summary.addEventListener('click', () => {
    if(!isMobile()) return;
    wrap.classList.toggle('is-collapsed');
  });

  window.addEventListener('resize', apply);
  apply();
})();
</script>