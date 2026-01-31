<?php
// app/modules/financeiro/contas_receber.php

$lista = [
  ['nome' => 'Cliente X', 'produto' => 'Lote PRC-2026-001', 'valor' => 'R$ 12.500,00', 'tipo' => 'PIX', 'venc' => '26/01/2026', 'status' => 'A receber'],
  ['nome' => 'Cliente Y', 'produto' => 'Lote PRC-2026-002', 'valor' => 'R$ 8.900,00', 'tipo' => 'Boleto', 'venc' => '30/01/2026', 'status' => 'Recebido'],
];
?>

<div class="fin-page">
  <div class="fin-head">
    <h1>Contas a Receber</h1>
    <p>Listagem e lançamento (mock). Campos seguem a entidade prevista.</p>
  </div>

  <?php include __DIR__ . '/_nav.php'; ?>

  <div class="fin-grid fin-grid--two">
    <div class="fin-panel">
      <div class="fin-panel__head">
        <div class="fin-panel__title">Listagem</div>
        <span class="fin-badge">mock</span>
      </div>

      <div class="fin-table-wrap">
        <table class="fin-table">
          <thead>
            <tr>
              <th>Cliente</th>
              <th>Produto</th>
              <th class="t-right">Valor</th>
              <th>Cobrança</th>
              <th class="t-right">Venc.</th>
              <th>Status</th>
              <th>Ações</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($lista as $it): ?>
              <tr>
                <td><?= htmlspecialchars($it['nome']) ?></td>
                <td><?= htmlspecialchars($it['produto']) ?></td>
                <td class="t-right"><?= htmlspecialchars($it['valor']) ?></td>
                <td><?= htmlspecialchars($it['tipo']) ?></td>
                <td class="t-right"><?= htmlspecialchars($it['venc']) ?></td>
                <td><?= htmlspecialchars($it['status']) ?></td>
                <td><button class="fin-btn fin-btn--ghost" type="button">Receber</button></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="fin-note">* “Receber” será modal mock na próxima parte.</div>
    </div>

    <div class="fin-panel">
      <div class="fin-panel__head">
        <div class="fin-panel__title">Novo lançamento</div>
        <span class="fin-badge">form</span>
      </div>

      <form class="fin-form" method="POST" action="javascript:void(0)">
        <div class="fin-form__row">
          <div class="fin-field">
            <label>Cliente</label>
            <input type="text" placeholder="Nome do cliente" />
          </div>
          <div class="fin-field">
            <label>Valor</label>
            <input type="text" placeholder="Ex: R$ 0,00" />
          </div>
        </div>

        <div class="fin-form__row">
          <div class="fin-field">
            <label>Produto</label>
            <input type="text" placeholder="Ex: Lote / Serviço" />
          </div>
          <div class="fin-field">
            <label>Tipo de cobrança</label>
            <input type="text" placeholder="Boleto / Cheque / Depósito / PIX" />
          </div>
        </div>

        <div class="fin-form__row">
          <div class="fin-field">
            <label>Data de vencimento</label>
            <input type="text" placeholder="DD/MM/AAAA" />
          </div>
          <div class="fin-field">
            <label>Status</label>
            <input type="text" placeholder="A receber / Recebido" />
          </div>
        </div>

        <div class="fin-form__actions">
          <button class="fin-btn" type="button">Salvar (mock)</button>
        </div>
      </form>
    </div>
  </div>
</div>