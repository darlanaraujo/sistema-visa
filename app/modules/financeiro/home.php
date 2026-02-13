<?php
// app/modules/financeiro/home.php

// ---------------------------
// MOCKS
// ---------------------------
$kpis = [
  'pagar_open'   => 'R$ 6.430,00',
  'pagar_done'   => 'R$ 9.120,00',
  'receber_open' => 'R$ 18.900,00',
  'receber_done' => 'R$ 22.450,00',
];

/**
 * IMPORTANTE (dashboard):
 * - lista mostra SOMENTE pendentes
 * - atrasadas devem ficar visualmente mais fortes
 */
$pagar_list = [
  ['conta'=>'Aluguel Galpão A', 'valor'=>'R$ 3.800,00', 'imovel'=>'Galpão A', 'venc'=>'2026-01-25', 'overdue'=>true],
  ['conta'=>'Energia Galpão B', 'valor'=>'R$ 1.240,00', 'imovel'=>'Galpão B', 'venc'=>'2026-02-02', 'overdue'=>false],
  ['conta'=>'Internet Escritório', 'valor'=>'R$ 189,90', 'imovel'=>'Escritório', 'venc'=>'2026-02-05', 'overdue'=>false],
  ['conta'=>'Material limpeza', 'valor'=>'R$ 80,00', 'imovel'=>'Galpão B', 'venc'=>'2026-02-08', 'overdue'=>false],
  ['conta'=>'Serviço manutenção', 'valor'=>'R$ 320,00', 'imovel'=>'Galpão A', 'venc'=>'2026-02-10', 'overdue'=>false],
];

$receber_list = [
  ['nome'=>'Cliente X', 'valor'=>'R$ 12.500,00', 'tipo'=>'Boleto', 'venc'=>'2026-01-28', 'overdue'=>true],
  ['nome'=>'Cliente Y', 'valor'=>'R$ 8.900,00', 'tipo'=>'Boleto', 'venc'=>'2026-02-03', 'overdue'=>false],
  ['nome'=>'Cliente W', 'valor'=>'R$ 1.450,00', 'tipo'=>'Cheque', 'venc'=>'2026-02-10', 'overdue'=>false],
  ['nome'=>'Cliente K', 'valor'=>'R$ 980,00', 'tipo'=>'Depósito', 'venc'=>'2026-02-12', 'overdue'=>false],
  ['nome'=>'Cliente M', 'valor'=>'R$ 2.300,00', 'tipo'=>'PIX futuro', 'venc'=>'2026-02-14', 'overdue'=>false],
];

// ---------------------------
// AGENDA: Vencidas / Hoje / Amanhã / Próximos 14 dias
// ---------------------------
$agenda = [
  [
    'key'   => 'overdue',
    'label' => 'Vencidas',
    'open'  => true,
    'items' => [
      ['kind'=>'pagar','title'=>'Aluguel Galpão A','amount'=>'R$ 3.800,00','meta'=>'Venceu em 25/01'],
      ['kind'=>'receber','title'=>'Cliente X • Boleto','amount'=>'R$ 12.500,00','meta'=>'Venceu em 28/01'],
    ],
  ],
  [
    'key'   => 'today',
    'label' => 'Hoje (3 eventos)',
    'open'  => true,
    'items' => [
      ['kind'=>'pagar','title'=>'Energia Galpão B','amount'=>'R$ 1.240,00','meta'=>'Vence hoje'],
      ['kind'=>'receber','title'=>'Cliente Y • Boleto','amount'=>'R$ 8.900,00','meta'=>'Vence hoje'],
      ['kind'=>'pagar','title'=>'Material limpeza','amount'=>'R$ 80,00','meta'=>'Vence hoje'],
    ],
  ],
  [
    'key'   => 'tomorrow',
    'label' => 'Amanhã (1 evento)',
    'open'  => false,
    'items' => [
      ['kind'=>'pagar','title'=>'Internet Escritório','amount'=>'R$ 189,90','meta'=>'Vence amanhã'],
    ],
  ],
  [
    'key'   => 'next14',
    'label' => 'Próximos 14 dias',
    'open'  => false,
    'items' => [
      ['kind'=>'receber','title'=>'Cliente W • Cheque','amount'=>'R$ 1.450,00','meta'=>'10/02'],
      ['kind'=>'pagar','title'=>'Serviço manutenção','amount'=>'R$ 320,00','meta'=>'10/02'],
      ['kind'=>'receber','title'=>'Cliente K • Depósito','amount'=>'R$ 980,00','meta'=>'12/02'],
      ['kind'=>'receber','title'=>'Cliente M • PIX futuro','amount'=>'R$ 2.300,00','meta'=>'14/02'],
      ['kind'=>'pagar','title'=>'Material limpeza','amount'=>'R$ 80,00','meta'=>'08/02'],
      ['kind'=>'pagar','title'=>'Conta extra exemplo','amount'=>'R$ 210,00','meta'=>'15/02'],
    ],
  ],
];

// Relatórios (cards clicáveis)
$relatorios = [
  ['icon'=>'fa-solid fa-calendar-days','nome'=>'Resumo mensal','desc'=>'Entradas x saídas e pendências por mês','href'=>'/sistema-visa/app/templates/financeiro_relatorios.php'],
  ['icon'=>'fa-solid fa-hourglass-half','nome'=>'Vencimentos','desc'=>'A pagar e a receber por data','href'=>'/sistema-visa/app/templates/financeiro_relatorios.php'],
  ['icon'=>'fa-solid fa-warehouse','nome'=>'Despesas por imóvel','desc'=>'Distribuição por centro de custo','href'=>'/sistema-visa/app/templates/financeiro_relatorios.php'],
  ['icon'=>'fa-solid fa-tags','nome'=>'Despesas por categoria','desc'=>'Distribuição por categoria','href'=>'/sistema-visa/app/templates/financeiro_relatorios.php'],
];

function br_md($iso){
  // yyyy-mm-dd -> dd/mm
  if (!$iso || strlen($iso) < 10) return '';
  return substr($iso,8,2) . '/' . substr($iso,5,2);
}
?>

<div class="fin-page">
  <div class="fin-head">
    <h1>Dashboard Financeiro</h1>
    <p>Visão rápida do mês, pendências e agenda (mock até BD).</p>
  </div>

  <?php include __DIR__ . '/_nav.php'; ?>

  <!-- KPIs (4) sem rótulos e com separador Pagar | Receber -->
  <div class="fin-toolbar-panel">
    <div class="fin-toolbar fin-dash-kpis">

      <!-- Pagar: Pendente -->
      <a class="fin-toolbar__block fin-toolbar__block--kpi fin-dash-kpi"
         href="/sistema-visa/app/templates/financeiro_contas_pagar.php">
        <div class="fin-kpi fin-kpi--two-col">
          <div class="fin-kpi__iconcol fin-kpi__iconcol--danger" aria-hidden="true">
            <i class="fa-solid fa-receipt"></i>
          </div>
          <div class="fin-kpi__text">
            <div class="fin-kpi__title">
              <span class="fin-kpi__iconinline fin-kpi__iconinline--danger" aria-hidden="true">
                <i class="fa-solid fa-receipt"></i>
              </span>
              Total a pagar
            </div>
            <div class="fin-kpi__value fin-kpi__value--danger" id="dashKpiPagarOpen"><?= h($kpis['pagar_open']) ?></div>
          </div>
        </div>
      </a>

      <!-- Pagar: Pago -->
      <a class="fin-toolbar__block fin-toolbar__block--kpi fin-dash-kpi"
         href="/sistema-visa/app/templates/financeiro_contas_pagar.php">
        <div class="fin-kpi fin-kpi--two-col">
          <div class="fin-kpi__iconcol fin-kpi__iconcol--success" aria-hidden="true">
            <i class="fa-solid fa-file-invoice-dollar"></i>
          </div>
          <div class="fin-kpi__text">
            <div class="fin-kpi__title">
              <span class="fin-kpi__iconinline fin-kpi__iconinline--success" aria-hidden="true">
                <i class="fa-solid fa-file-invoice-dollar"></i>
              </span>
              Total pago
            </div>
            <div class="fin-kpi__value fin-kpi__value--success" id="dashKpiPagarDone"><?= h($kpis['pagar_done']) ?></div>
          </div>
        </div>
      </a>

      <!-- Separador (entre pagar e receber) -->
      <div class="fin-dash-kpi-sep" aria-hidden="true"></div>

      <!-- Receber: A receber -->
      <a class="fin-toolbar__block fin-toolbar__block--kpi fin-dash-kpi"
         href="/sistema-visa/app/templates/financeiro_contas_receber.php">
        <div class="fin-kpi fin-kpi--two-col">
          <div class="fin-kpi__iconcol fin-kpi__iconcol--danger" aria-hidden="true">
            <i class="fa-solid fa-receipt"></i>
          </div>
          <div class="fin-kpi__text">
            <div class="fin-kpi__title">
              <span class="fin-kpi__iconinline fin-kpi__iconinline--danger" aria-hidden="true">
                <i class="fa-solid fa-receipt"></i>
              </span>
              Total a receber
            </div>
            <div class="fin-kpi__value fin-kpi__value--danger" id="dashKpiReceberOpen"><?= h($kpis['receber_open']) ?></div>
          </div>
        </div>
      </a>

      <!-- Receber: Recebido -->
      <a class="fin-toolbar__block fin-toolbar__block--kpi fin-dash-kpi"
         href="/sistema-visa/app/templates/financeiro_contas_receber.php">
        <div class="fin-kpi fin-kpi--two-col">
          <div class="fin-kpi__iconcol fin-kpi__iconcol--success" aria-hidden="true">
            <i class="fa-solid fa-file-invoice-dollar"></i>
          </div>
          <div class="fin-kpi__text">
            <div class="fin-kpi__title">
              <span class="fin-kpi__iconinline fin-kpi__iconinline--success" aria-hidden="true">
                <i class="fa-solid fa-file-invoice-dollar"></i>
              </span>
              Total recebido
            </div>
            <div class="fin-kpi__value fin-kpi__value--success" id="dashKpiReceberDone"><?= h($kpis['receber_done']) ?></div>
          </div>
        </div>
      </a>

    </div>
  </div>

  <!-- GRÁFICOS (3 no desktop; quebra inteligente no intermediário; 1 no mobile) -->
  <div class="fin-grid fin-dash-grid fin-dash-grid--charts">
    <section class="fin-panel fin-dash-panel fin-dash-chart">
      <div class="fin-panel__head">
        <div class="fin-panel__title"><i class="fa-solid fa-chart-line"></i><span>Fluxo do período</span></div>
        <a class="fin-badge fin-badge--pt fin-badge--link" href="/sistema-visa/app/templates/financeiro_relatorios.php">ver</a>
      </div>

      <div class="fin-dash-mockbars">
        <div class="fin-dash-mockbar is-in" style="width:75%"></div>
        <div class="fin-dash-mockbar is-out" style="width:55%"></div>
        <div class="fin-dash-mockbar is-in" style="width:62%"></div>
        <div class="fin-dash-mockbar is-out" style="width:40%"></div>
        <div class="fin-dash-mockhint">* Exemplo: entradas x saídas no período selecionado.</div>
      </div>
    </section>

    <section class="fin-panel fin-dash-panel fin-dash-chart">
      <div class="fin-panel__head">
        <div class="fin-panel__title"><i class="fa-solid fa-warehouse"></i><span>Despesas por imóveis</span></div>
        <a class="fin-badge fin-badge--pt fin-badge--link" href="/sistema-visa/app/templates/financeiro_imoveis.php">ver</a>
      </div>

      <div class="fin-pie-wrap">
        <div class="fin-pie fin-pie--imoveis" aria-hidden="true"></div>

        <div class="fin-pie-legend">
          <div><span class="dot dot--a"></span> Galpão A <strong>38%</strong></div>
          <div><span class="dot dot--b"></span> Galpão B <strong>32%</strong></div>
          <div><span class="dot dot--c"></span> Escritório <strong>30%</strong></div>
        </div>
      </div>
    </section>

    <section class="fin-panel fin-dash-panel fin-dash-chart">
      <div class="fin-panel__head">
        <div class="fin-panel__title"><i class="fa-solid fa-tags"></i><span>Despesas por categorias</span></div>
        <a class="fin-badge fin-badge--pt fin-badge--link" href="/sistema-visa/app/templates/financeiro_categorias.php">ver</a>
      </div>

      <div class="fin-pie-wrap">
        <div class="fin-pie fin-pie--categorias" aria-hidden="true"></div>

        <div class="fin-pie-legend">
          <div><span class="dot dot--d"></span> Operacional <strong>45%</strong></div>
          <div><span class="dot dot--e"></span> Fixas <strong>35%</strong></div>
          <div><span class="dot dot--f"></span> Variáveis <strong>20%</strong></div>
        </div>
      </div>
    </section>
  </div>

  <!-- AGENDA + ATALHOS (juntos) -->
  <div class="fin-grid fin-dash-grid fin-dash-grid--agenda">
    <section class="fin-panel fin-dash-panel fin-dash-agenda">
      <div class="fin-panel__head">
        <div class="fin-panel__title"><i class="fa-solid fa-calendar-days"></i><span>Agenda (14 dias)</span></div>
        <span class="fin-badge fin-badge--pt">prévia</span>
      </div>

      <div class="fin-dash-acc fin-dash-acc--agenda" id="dashAgenda">
        <?php foreach ($agenda as $g): ?>
          <details class="fin-dash-acc__item" <?= $g['open'] ? 'open' : '' ?>>
            <summary class="fin-dash-acc__sum">
              <span><?= h($g['label']) ?></span>
              <i class="fa-solid fa-chevron-down"></i>
            </summary>

            <div class="fin-dash-acc__body <?= $g['key']==='next14' ? 'is-scroll' : '' ?>">
              <?php foreach ($g['items'] as $it): ?>
                <?php $isPay = $it['kind'] === 'pagar'; ?>
                <div class="fin-dash-agenda-row <?= $isPay ? 'is-pay' : 'is-rec' ?>">
                  <div class="fin-dash-agenda-row__left">
                    <i class="<?= $isPay ? 'fa-solid fa-receipt' : 'fa-solid fa-file-invoice-dollar' ?>"></i>
                    <div>
                      <div class="fin-dash-agenda-row__title"><?= h($it['title']) ?></div>
                      <div class="fin-dash-agenda-row__meta"><?= h($it['meta']) ?></div>
                    </div>
                  </div>
                  <div class="fin-dash-agenda-row__amt"><?= h($it['amount']) ?></div>
                </div>
              <?php endforeach; ?>
            </div>
          </details>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="fin-panel fin-dash-panel fin-dash-shortcuts-panel">
      <div class="fin-panel__head">
        <div class="fin-panel__title"><i class="fa-solid fa-bolt"></i><span>Atalhos</span></div>
        <span class="fin-badge fin-badge--pt">ações</span>
      </div>

      <!-- Desktop: visível (lista). Mobile: vira sanfona via CSS -->
      <details class="fin-dash-acc__item fin-dash-shortcuts" open>
        <summary class="fin-dash-acc__sum">
          <span>Mostrar ações</span>
          <i class="fa-solid fa-chevron-down"></i>
        </summary>

        <div class="fin-dash-shortcuts__body">
          <a class="fin-dash-shortcut is-pay" href="/sistema-visa/app/templates/financeiro_contas_pagar.php">
            <i class="fa-solid fa-file-invoice-dollar"></i><span>Contas a pagar</span>
          </a>
          <a class="fin-dash-shortcut is-rec" href="/sistema-visa/app/templates/financeiro_contas_receber.php">
            <i class="fa-solid fa-hand-holding-dollar"></i><span>Contas a receber</span>
          </a>
          <a class="fin-dash-shortcut" href="#">
            <i class="fa-solid fa-warehouse"></i><span>Imóveis</span>
          </a>
          <a class="fin-dash-shortcut" href="#">
            <i class="fa-solid fa-tags"></i><span>Categorias</span>
          </a>
          <a class="fin-dash-shortcut" href="#">
            <i class="fa-solid fa-users"></i><span>Clientes / Fornecedores</span>
          </a>
          <a class="fin-dash-shortcut" href="/sistema-visa/app/templates/financeiro_relatorios.php">
            <i class="fa-solid fa-chart-pie"></i><span>Relatórios</span>
          </a>
        </div>
      </details>
    </section>
  </div>

  <!-- LISTAS (uma seção só com as duas) -->
  <section class="fin-panel fin-dash-panel">
    <div class="fin-panel__head">
      <div class="fin-panel__title"><i class="fa-solid fa-list-check"></i><span>Pendências do mês</span></div>
      <span class="fin-badge fin-badge--pt">prévia</span>
    </div>

    <div class="fin-dash-lists">
      <!-- Pagar -->
      <div class="fin-dash-listbox">
        <div class="fin-dash-listbox__head">
          <i class="fa-solid fa-file-invoice-dollar"></i><span>Contas a pagar</span>
          <a class="fin-dash-listbox__link" href="/sistema-visa/app/templates/financeiro_contas_pagar.php">abrir</a>
        </div>

        <div class="fin-dash-mini" id="dashPagarList">
          <?php foreach ($pagar_list as $c): ?>
            <div class="fin-dash-mini__row is-pay <?= $c['overdue'] ? 'is-overdue' : '' ?>">
              <div class="fin-dash-mini__main">
                <div class="fin-dash-mini__title"><?= h($c['conta']) ?></div>
                <div class="fin-dash-mini__meta"><?= h($c['imovel']) ?> • Venc: <?= h(br_md($c['venc'])) ?></div>
              </div>
              <div class="fin-dash-mini__right">
                <div class="fin-dash-mini__amt"><?= h($c['valor']) ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Receber -->
      <div class="fin-dash-listbox">
        <div class="fin-dash-listbox__head">
          <i class="fa-solid fa-hand-holding-dollar"></i><span>Contas a receber</span>
          <a class="fin-dash-listbox__link" href="/sistema-visa/app/templates/financeiro_contas_receber.php">abrir</a>
        </div>

        <div class="fin-dash-mini" id="dashReceberList">
          <?php foreach ($receber_list as $r): ?>
            <div class="fin-dash-mini__row is-rec <?= $r['overdue'] ? 'is-overdue' : '' ?>">
              <div class="fin-dash-mini__main">
                <div class="fin-dash-mini__title"><?= h($r['nome']) ?> • <?= h($r['tipo']) ?></div>
                <div class="fin-dash-mini__meta">Venc: <?= h(br_md($r['venc'])) ?></div>
              </div>
              <div class="fin-dash-mini__right">
                <div class="fin-dash-mini__amt"><?= h($r['valor']) ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </section>

  <!-- Relatórios favoritos (cards) -->
  <section class="fin-panel fin-dash-panel">
    <div class="fin-panel__head">
      <div class="fin-panel__title"><i class="fa-solid fa-print"></i><span>Relatórios</span></div>
      <a class="fin-badge fin-badge--pt fin-badge--link" href="/sistema-visa/app/templates/financeiro_relatorios.php">ver</a>
    </div>

    <div class="rep-grid" id="dashReportsGrid">
      <?php foreach ($relatorios as $r): ?>
        <a class="rep-card" href="<?= h($r['href']) ?>">
          <div class="rep-card__icon"><i class="<?= h($r['icon']) ?>"></i></div>
          <div class="rep-card__title"><?= h($r['nome']) ?></div>
          <div class="rep-card__desc"><?= h($r['desc']) ?></div>
          <div class="rep-card__cta"><i class="fa-solid fa-arrow-right"></i><span>Abrir</span></div>
        </a>
      <?php endforeach; ?>
    </div>
  </section>

<script>
  window.__FIN_DASH_MOCK__ = {
    kpis: <?= json_encode($kpis, JSON_UNESCAPED_UNICODE) ?>,
    pagar_list: <?= json_encode($pagar_list, JSON_UNESCAPED_UNICODE) ?>,
    receber_list: <?= json_encode($receber_list, JSON_UNESCAPED_UNICODE) ?>,
    agenda: <?= json_encode($agenda, JSON_UNESCAPED_UNICODE) ?>,
    relatorios: <?= json_encode($relatorios, JSON_UNESCAPED_UNICODE) ?>,
  };
</script>
</div>