<?php
// app/modules/dashboard/home.php

$ultimos_lotes = [
  [
    'processo' => 'PRC-2026-001',
    'seguradora' => 'Seguradora Alpha',
    'material' => 'Chapa de aço',
    'origem' => 'Goiânia/GO',
    'status' => 'Em transporte',
    'data_compra' => '18/01/2026',
  ],
  [
    'processo' => 'PRC-2026-002',
    'seguradora' => 'Seguradora Beta',
    'material' => 'Eletrônicos',
    'origem' => 'Anápolis/GO',
    'status' => 'Aguardando retirada',
    'data_compra' => '12/01/2026',
  ],
  [
    'processo' => 'PRC-2026-003',
    'seguradora' => 'Seguradora Gama',
    'material' => 'MDF',
    'origem' => 'Brasília/DF',
    'status' => 'Em conferência',
    'data_compra' => '05/01/2026',
  ],
];

$fin_pagar = [
  ['conta' => 'Aluguel galpão', 'imovel' => 'Galpão A', 'valor' => 'R$ 3.800,00', 'data' => '25/01/2026'],
  ['conta' => 'Energia', 'imovel' => 'Galpão B', 'valor' => 'R$ 1.240,00', 'data' => '28/01/2026'],
  ['conta' => 'Internet', 'imovel' => 'Escritório', 'valor' => 'R$ 189,90', 'data' => '02/02/2026'],
];

$fin_receber = [
  ['nome' => 'Cliente X', 'valor' => 'R$ 12.500,00', 'tipo' => 'PIX', 'data' => '26/01/2026', 'status' => 'A receber'],
  ['nome' => 'Cliente Y', 'valor' => 'R$ 8.900,00', 'tipo' => 'Boleto', 'data' => '30/01/2026', 'status' => 'Recebido'],
  ['nome' => 'Parceiro Z', 'valor' => 'R$ 2.200,00', 'tipo' => 'Depósito', 'data' => '05/02/2026', 'status' => 'A receber'],
];

// indicadores mock
$pagar_pago = 1; $pagar_total = 3;
$receber_recebido = 1; $receber_total = 3;

function pct($a, $t){
  if ($t <= 0) return 0;
  $p = (int)round(($a / $t) * 100);
  if ($p < 0) $p = 0;
  if ($p > 100) $p = 100;
  return $p;
}
$pct_pagar = pct($pagar_pago, $pagar_total);
$pct_receber = pct($receber_recebido, $receber_total);

$relatorios_favoritos = [
  ['icon' => 'fa-solid fa-calendar-days', 'nome' => 'Resumo mensal', 'desc' => 'Visão do mês por módulos'],
  ['icon' => 'fa-solid fa-hourglass-half', 'nome' => 'Vencimentos', 'desc' => 'A pagar e a receber por data'],
  ['icon' => 'fa-solid fa-route', 'nome' => 'Status de processos', 'desc' => 'Etapas e pendências por lote'],
  ['icon' => 'fa-solid fa-file-invoice-dollar', 'nome' => 'Fluxo de caixa', 'desc' => 'Entradas x saídas (período)'],
];
?>

<div class="dash-app">
  <div class="dash-app__panel">
    <div class="dash2__header">
      <h1>Dashboard</h1>
      <p>Resumo rápido do que importa agora. (Dados temporários até entrar BD)</p>
    </div>

    <div class="dash2__sections">

      <!-- LOTES -->
      <section class="dash2__section">
        <div class="dash2__section-head">
          <div class="dash2__section-title">
            <i class="fa-solid fa-boxes-stacked"></i>
            <span>Gestão de Lotes</span>
          </div>
          <a class="dash2__link" href="/sistema-visa/app/templates/lotes.php">Ver módulo</a>
        </div>

        <div class="dash2__box">
          <div class="dash2__box-title">Últimos lotes adquiridos</div>

          <div class="lotes-list">
            <?php foreach ($ultimos_lotes as $l): ?>
              <?php $href = '/sistema-visa/app/templates/lotes.php?processo=' . urlencode($l['processo']); ?>

              <!-- Desktop/tablet: “tabela” 2 linhas -->
              <a class="lote-card lote-card--desktop" href="<?= htmlspecialchars($href) ?>">
                <div class="lote-card__labels">
                  <div>Nº Processo</div>
                  <div>Seguradora</div>
                  <div>Produtos</div>
                  <div>Cidade de Origem</div>
                  <div>Data de Compra</div>
                  <div>Status</div>
                </div>

                <div class="lote-card__values">
                  <div><?= htmlspecialchars($l['processo']) ?></div>
                  <div><?= htmlspecialchars($l['seguradora']) ?></div>
                  <div><?= htmlspecialchars($l['material']) ?></div>
                  <div><?= htmlspecialchars($l['origem']) ?></div>
                  <div><?= htmlspecialchars($l['data_compra']) ?></div>
                  <div><?= htmlspecialchars($l['status']) ?></div>
                </div>
              </a>

              <!-- Mobile: pares título/dado em ordem (evita bagunça) -->
              <a class="lote-card lote-card--mobile" href="<?= htmlspecialchars($href) ?>">
                <div class="kv-stack">
                  <div class="kv">
                    <div class="kv__k">Nº Processo</div>
                    <div class="kv__v"><?= htmlspecialchars($l['processo']) ?></div>
                  </div>
                  <div class="kv">
                    <div class="kv__k">Seguradora</div>
                    <div class="kv__v"><?= htmlspecialchars($l['seguradora']) ?></div>
                  </div>
                  <div class="kv">
                    <div class="kv__k">Produtos</div>
                    <div class="kv__v"><?= htmlspecialchars($l['material']) ?></div>
                  </div>
                  <div class="kv">
                    <div class="kv__k">Cidade de Origem</div>
                    <div class="kv__v"><?= htmlspecialchars($l['origem']) ?></div>
                  </div>
                  <div class="kv">
                    <div class="kv__k">Data de Compra</div>
                    <div class="kv__v"><?= htmlspecialchars($l['data_compra']) ?></div>
                  </div>
                  <div class="kv">
                    <div class="kv__k">Status</div>
                    <div class="kv__v"><?= htmlspecialchars($l['status']) ?></div>
                  </div>
                </div>
              </a>
            <?php endforeach; ?>
          </div>

          <div class="dash2__hint">
            * Registro: no desenvolvimento final, esses dados devem ser carregados em tabela com distribuição correta de título/dado.
          </div>
        </div>
      </section>

      <!-- FINANCEIRO -->
      <section class="dash2__section">
        <div class="dash2__section-head">
          <div class="dash2__section-title">
            <i class="fa-solid fa-coins"></i>
            <span>Gestão Financeira</span>
          </div>
          <a class="dash2__link" href="/sistema-visa/app/templates/financeiro.php">Ver módulo</a>
        </div>

        <div class="fin-grid">
          <!-- A pagar -->
          <div class="fin-col" data-dash-fin="pagar">
            <div class="fin-col__title">Contas a pagar</div>

            <div class="mini-list">
              <?php foreach ($fin_pagar as $c): ?>
                <div class="mini-card">
                  <div class="mini-table">
                    <div class="mini-row mini-row--head">
                      <div>Conta</div><div>Valor</div>
                    </div>
                    <div class="mini-row mini-row--val">
                      <div><?= htmlspecialchars($c['conta']) ?></div><div class="t-right"><?= htmlspecialchars($c['valor']) ?></div>
                    </div>

                    <div class="mini-row mini-row--head">
                      <div>Imóvel</div><div>Data</div>
                    </div>
                    <div class="mini-row mini-row--val">
                      <div><?= htmlspecialchars($c['imovel']) ?></div><div class="t-right"><?= htmlspecialchars($c['data']) ?></div>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Indicadores (duas barras no mesmo) -->
          <div class="fin-col">
            <div class="fin-col__title">Indicadores</div>

            <div class="fin-metric">
              <div class="fin-metric__block">
                <div class="fin-metric__label">Pagas x a pagar</div>
                <div class="fin-bar"><div class="fin-bar__fill" style="width: <?= $pct_pagar ?>%;"></div></div>
                <div class="fin-metric__hint"><?= $pagar_pago ?>/<?= $pagar_total ?> pagas</div>
              </div>

              <div class="fin-metric__sep"></div>

              <div class="fin-metric__block">
                <div class="fin-metric__label">Recebidas x a receber</div>
                <div class="fin-bar"><div class="fin-bar__fill" style="width: <?= $pct_receber ?>%;"></div></div>
                <div class="fin-metric__hint"><?= $receber_recebido ?>/<?= $receber_total ?> recebidas</div>
              </div>
            </div>
          </div>

          <!-- A receber -->
          <div class="fin-col">
            <div class="fin-col__title">Contas a receber</div>

            <div class="mini-list">
              <?php foreach ($fin_receber as $r): ?>
                <div class="mini-card">
                  <div class="mini-table">
                    <div class="mini-row mini-row--head">
                      <div>Nome</div><div>Valor</div>
                    </div>
                    <div class="mini-row mini-row--val">
                      <div><?= htmlspecialchars($r['nome']) ?></div><div class="t-right"><?= htmlspecialchars($r['valor']) ?></div>
                    </div>

                    <div class="mini-row mini-row--head">
                      <div>Cobrança</div><div>Data</div>
                    </div>
                    <div class="mini-row mini-row--val">
                      <div><?= htmlspecialchars($r['tipo']) ?></div><div class="t-right"><?= htmlspecialchars($r['data']) ?></div>
                    </div>

                    <div class="mini-row mini-row--head">
                      <div>Status</div><div></div>
                    </div>
                    <div class="mini-row mini-row--val">
                      <div><?= htmlspecialchars($r['status']) ?></div><div></div>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Pizza -->
          <div class="fin-col">
            <div class="fin-col__title">Despesas por imóveis</div>

            <div class="fin-pie-wrap">
              <div class="fin-pie fin-pie--imoveis" aria-hidden="true"></div>

              <div class="fin-pie-legend">
                <div><span class="dot dot--a"></span> Galpão A</div>
                <div><span class="dot dot--b"></span> Galpão B</div>
                <div><span class="dot dot--c"></span> Escritório</div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- RELATÓRIOS -->
      <section class="dash2__section">
        <div class="dash2__section-head">
          <div class="dash2__section-title">
            <i class="fa-solid fa-chart-line"></i>
            <span>Relatórios</span>
          </div>
          <a class="dash2__link" href="javascript:void(0)">Ver módulo</a>
        </div>

        <div class="rep-grid">
          <?php foreach ($relatorios_favoritos as $rep): ?>
            
            <?php $hrefRel = '/sistema-visa/app/templates/relatorios.php?rel=' . urlencode($rep['nome']); ?>
              <a class="rep-card" href="<?= htmlspecialchars($hrefRel) ?>">
                <div class="rep-card__icon"><i class="<?= htmlspecialchars($rep['icon']) ?>"></i></div>
                <div class="rep-card__title"><?= htmlspecialchars($rep['nome']) ?></div>
                <div class="rep-card__desc"><?= htmlspecialchars($rep['desc']) ?></div>
                <div class="rep-card__cta">
                  <i class="fa-solid fa-print"></i>
                  <span>Gerar relatório</span>
                </div>
              </a>

          <?php endforeach; ?>
        </div>
      </section>

    </div>
  </div>
</div>