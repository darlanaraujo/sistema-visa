<?php
// app/modules/financeiro/home.php

$cards = [
  ['title' => 'Despesas em aberto (mês)', 'value' => 'R$ 0,00', 'hint' => 'Soma do que está “A pagar” no mês (mock)'],
  ['title' => 'Receitas em aberto (mês)', 'value' => 'R$ 0,00', 'hint' => 'Soma do que está “A receber” no mês (mock)'],
  ['title' => 'Indicadores', 'value' => 'Pagas x A pagar / Recebidas x A receber', 'hint' => 'Mock nesta etapa (virará gráfico)'],
];

$pagar = [
  ['conta' => 'Aluguel galpão', 'valor' => 'R$ 3.800,00', 'imovel' => 'Galpão A', 'data' => '25/01/2026'],
  ['conta' => 'Energia', 'valor' => 'R$ 1.240,00', 'imovel' => 'Galpão B', 'data' => '28/01/2026'],
  ['conta' => 'Internet', 'valor' => 'R$ 189,90', 'imovel' => 'Escritório', 'data' => '02/02/2026'],
];

$receber = [
  ['nome' => 'Cliente X', 'valor' => 'R$ 12.500,00', 'tipo' => 'PIX', 'data' => '26/01/2026'],
  ['nome' => 'Cliente Y', 'valor' => 'R$ 8.900,00', 'tipo' => 'Boleto', 'data' => '30/01/2026'],
];
?>

<!-- CSS do módulo carregado sem depender do base_private -->
<style>
  @import url("/sistema-visa/app/static/css/financeiro.css");
</style>

<div class="fin-page">
  <div class="fin-head">
    <h1>Dashboard Financeiro</h1>
    <p>Visão operacional rápida (mock nesta etapa).</p>
  </div>

  <?php include __DIR__ . '/_nav.php'; ?>

  <div class="fin-grid fin-grid--cards">
    <?php foreach ($cards as $c): ?>
      <div class="fin-card">
        <div class="fin-card__title"><?= htmlspecialchars($c['title']) ?></div>
        <div class="fin-card__value"><?= htmlspecialchars($c['value']) ?></div>
        <div class="fin-card__hint"><?= htmlspecialchars($c['hint']) ?></div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="fin-grid fin-grid--dash">
    <div class="fin-panel">
      <div class="fin-panel__head">
        <div class="fin-panel__title">Operação • Contas a pagar</div>
        <a class="fin-panel__link" href="/sistema-visa/app/templates/financeiro_contas_pagar.php">Abrir</a>
      </div>

      <!-- 1 tabela para a lista inteira (1 scroll por lista) -->
      <div class="fin-mini-table-wrap">
        <table class="fin-mini-table">
          <thead>
            <tr>
              <th>Conta</th>
              <th>Valor</th>
              <th>Imóvel</th>
              <th>Data</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($pagar as $c): ?>
              <tr>
                <td class="t-left"><?= htmlspecialchars($c['conta']) ?></td>
                <td class="t-right"><?= htmlspecialchars($c['valor']) ?></td>
                <td class="t-center"><?= htmlspecialchars($c['imovel']) ?></td>
                <td class="t-center"><?= htmlspecialchars($c['data']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="fin-panel">
      <div class="fin-panel__head">
        <div class="fin-panel__title">Operação • Contas a receber</div>
        <a class="fin-panel__link" href="/sistema-visa/app/templates/financeiro_contas_receber.php">Abrir</a>
      </div>

      <!-- 1 tabela para a lista inteira (1 scroll por lista) -->
      <div class="fin-mini-table-wrap">
        <table class="fin-mini-table">
          <thead>
            <tr>
              <th>Nome</th>
              <th>Valor</th>
              <th>Cobrança</th>
              <th>Data</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($receber as $r): ?>
              <tr>
                <td class="t-left"><?= htmlspecialchars($r['nome']) ?></td>
                <td class="t-right"><?= htmlspecialchars($r['valor']) ?></td>
                <td class="t-center"><?= htmlspecialchars($r['tipo']) ?></td>
                <td class="t-center"><?= htmlspecialchars($r['data']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="fin-panel">
      <div class="fin-panel__head">
        <div class="fin-panel__title">Gráficos (mock)</div>
        <span class="fin-badge">placeholder</span>
      </div>

      <div class="fin-mock-chart">
        <div class="fin-mock-chart__bar" style="width: 65%"></div>
        <div class="fin-mock-chart__bar" style="width: 40%"></div>
        <div class="fin-mock-chart__bar" style="width: 85%"></div>
        <div class="fin-mock-chart__hint">Virará gráfico real com BD e filtros.</div>
      </div>
    </div>

    <div class="fin-panel">
      <div class="fin-panel__head">
        <div class="fin-panel__title">Atalhos</div>
        <span class="fin-badge">ações</span>
      </div>

      <div class="fin-actions">
        <a class="fin-action" href="/sistema-visa/app/templates/financeiro_contas_pagar.php">
          <i class="fa-solid fa-file-invoice-dollar"></i><span>Acessar Contas a Pagar</span>
        </a>
        <a class="fin-action" href="/sistema-visa/app/templates/financeiro_contas_receber.php">
          <i class="fa-solid fa-hand-holding-dollar"></i><span>Acessar Contas a Receber</span>
        </a>
        <a class="fin-action" href="/sistema-visa/app/templates/financeiro_relatorios.php">
          <i class="fa-solid fa-print"></i><span>Acessar Relatórios</span>
        </a>
      </div>
    </div>
  </div>
</div>