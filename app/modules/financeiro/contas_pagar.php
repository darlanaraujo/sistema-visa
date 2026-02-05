<?php
// app/modules/financeiro/contas_pagar.php

$mock = [
  ['id'=>1,'conta'=>'Energia','valor'=>1240.00,'imovel'=>'Galpão B','data'=>'2026-01-28','categoria'=>'Energia','fixa'=>true,'status'=>'open'],
  ['id'=>2,'conta'=>'Aluguel','valor'=>3800.00,'imovel'=>'Galpão A','data'=>'2026-01-25','categoria'=>'Aluguel','fixa'=>true,'status'=>'done'],
  ['id'=>3,'conta'=>'Internet','valor'=>189.90,'imovel'=>'Escritório','data'=>'2026-02-02','categoria'=>'Serviços','fixa'=>true,'status'=>'open'],
];
?>

<div class="fin-page" id="cpPage">
  <div class="fin-head">
    <h1>Contas a Pagar</h1>
    <p>Controle operacional de vencimentos. (Mock nesta etapa — sem banco de dados)</p>
  </div>

  <?php include __DIR__ . '/_nav.php'; ?>

  <!-- KPIs + Logo + CTA -->
  <div class="fin-toolbar-panel">
    <div class="fin-toolbar">

      <!-- Logo na 1ª coluna (esquerda) -->
      <div class="fin-toolbar__block fin-toolbar__block--logo" aria-label="Identidade visual">
        <div class="fin-kpi-logo">
          <img src="/sistema-visa/app/static/img/logo.png" alt="Visa Remoções">
        </div>
      </div>

      <!-- Total Pendente -->
      <div class="fin-toolbar__block fin-toolbar__block--kpi">
        <div class="fin-kpi fin-kpi--two-col">
          <div class="fin-kpi__iconcol fin-kpi__iconcol--danger" aria-hidden="true">
            <i class="fa-solid fa-receipt"></i>
          </div>

          <div class="fin-kpi__text">
            <div class="fin-kpi__title">
              <span class="fin-kpi__iconinline fin-kpi__iconinline--danger" aria-hidden="true">
                 <i class="fa-solid fa-receipt"></i>
              </span>
              Total Pendente
            </div>

            <div class="fin-kpi__value fin-kpi__value--danger" id="cpTotalOpen">R$ 0,00</div>
          </div>
        </div>
      </div>

      <!-- Total Pago -->
      <div class="fin-toolbar__block fin-toolbar__block--kpi">
        <div class="fin-kpi fin-kpi--two-col">
          <div class="fin-kpi__iconcol fin-kpi__iconcol--success" aria-hidden="true">
            <i class="fa-solid fa-file-invoice-dollar"></i>
          </div>

          <div class="fin-kpi__text">
            <div class="fin-kpi__title">
              <span class="fin-kpi__iconinline fin-kpi__iconinline--success" aria-hidden="true">
                <i class="fa-solid fa-file-invoice-dollar"></i>
              </span>
              Total Pago
            </div>

            <div class="fin-kpi__value fin-kpi__value--success" id="cpTotalDone">R$ 0,00</div>
          </div>
        </div>
      </div>

      <!-- CTA -->
      <div class="fin-toolbar__block fin-toolbar__block--cta">
        <button class="fin-btn" id="cpNew" type="button" title="Novo lançamento">
          <i class="fa-solid fa-plus"></i><span>Novo lançamento</span>
        </button>
      </div>

    </div>
  </div>

  <!-- Filtros -->
  <div class="fin-filters-wrap" id="cpFiltersWrap">
    <div class="fin-filters-summary" id="cpFiltersToggle">
      <div><i class="fa-solid fa-filter"></i> Filtros</div>
      <i class="fa-solid fa-chevron-down" id="cpFiltersIcon"></i>
    </div>

    <div class="fin-filters" id="cpFiltersGrid">
      <div class="fin-filter">
        <label>Imóvel</label>
        <select id="cpFilterImovel">
          <option value="">Todos</option>
          <option>Galpão A</option>
          <option>Galpão B</option>
          <option>Escritório</option>
        </select>
      </div>

      <div class="fin-filter">
        <label>Status</label>
        <select id="cpFilterStatus">
          <option value="">Todos</option>
          <option value="open">A pagar</option>
          <option value="done">Pago</option>
        </select>
      </div>

      <div class="fin-filter">
        <label>Categoria</label>
        <select id="cpFilterCategoria">
          <option value="">Todas</option>
          <option>Energia</option>
          <option>Aluguel</option>
          <option>Serviços</option>
        </select>
      </div>

      <div class="fin-filter">
        <label>Fixa</label>
        <select id="cpFilterFixa">
          <option value="">Todas</option>
          <option value="1">Sim</option>
          <option value="0">Não</option>
        </select>
      </div>

      <div class="fin-filter">
        <label>Buscar (Conta)</label>
        <input id="cpFilterSearch" type="text" placeholder="Ex: Energia" />
      </div>

      <div class="fin-filter" style="min-width:160px">
        <label>&nbsp;</label>
        <button class="fin-btn fin-btn--ghost" id="cpClear" type="button" title="Limpar filtros">
          <i class="fa-solid fa-eraser"></i><span>Limpar</span>
        </button>
      </div>
    </div>
  </div>

  <!-- Listagem -->
  <div class="fin-panel">
    <div class="fin-panel__head" style="margin-bottom:10px;">
      <div class="fin-panel__title">
        <i class="fa-solid fa-list-check"></i>
        <span>Listagem</span>
      </div>

      <div class="fin-monthline" aria-label="Filtro de mês">
        <button class="fin-icon-btn fin-icon-btn--sm" id="cpPrev" type="button" title="Mês anterior">
          <i class="fa-solid fa-chevron-left"></i>
        </button>
        <div class="fin-monthline__label" id="cpMonthLabel">—</div>
        <button class="fin-icon-btn fin-icon-btn--sm" id="cpNext" type="button" title="Próximo mês">
          <i class="fa-solid fa-chevron-right"></i>
        </button>
      </div>

      <span class="fin-badge" id="cpCount">0 itens</span>
    </div>

    <div class="fin-table-wrap is-scroll-y" id="cpTableWrap">
      <table class="fin-table" id="cpTable">
        <thead>
          <tr>
            <th>Conta</th>
            <th>Valor</th>
            <th>Imóvel</th>
            <th>Venc.</th>
            <th>Categoria</th>
            <th>Fixa</th>
            <th>Status</th>
            <th>Ações</th>
          </tr>
        </thead>
        <tbody id="cpTbody"></tbody>
      </table>
    </div>
  </div>

  <script>
    window.__CP_MOCK__ = <?= json_encode($mock, JSON_UNESCAPED_UNICODE) ?>;
  </script>

  <!-- Modal: Novo/Editar -->
  <div class="fin-modal" id="cpModal" aria-hidden="true">
    <div class="fin-modal__card">
      <div class="fin-modal__head">
        <div class="fin-modal__title" id="cpModalTitle">Novo lançamento</div>
        <button class="fin-modal__close" id="cpModalClose" type="button" aria-label="Fechar">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <div class="fin-modal__body">
        <form class="fin-form" id="cpForm" action="javascript:void(0)">
          <input type="hidden" id="cpId" />

          <div class="fin-form__row">
            <div class="fin-field">
              <label>Conta</label>
              <input id="cpConta" type="text" placeholder="Ex: Energia" required />
            </div>
            <div class="fin-field">
              <label>Valor</label>
              <input id="cpValor" type="text" placeholder="Ex: 1240.00" required />
            </div>
          </div>

          <div class="fin-form__row">
            <div class="fin-field">
              <label>Imóvel</label>
              <select id="cpImovel" required>
                <option value="">Selecione</option>
                <option>Galpão A</option>
                <option>Galpão B</option>
                <option>Escritório</option>
              </select>
            </div>
            <div class="fin-field">
              <label>Categoria</label>
              <select id="cpCategoria" required>
                <option value="">Selecione</option>
                <option>Energia</option>
                <option>Aluguel</option>
                <option>Serviços</option>
              </select>
            </div>
          </div>

          <div class="fin-form__row">
            <div class="fin-field">
              <label>Vencimento</label>
              <input id="cpData" type="date" required />
            </div>
            <div class="fin-field">
              <label>Fixa</label>
              <select id="cpFixa" required>
                <option value="1">Sim</option>
                <option value="0">Não</option>
              </select>
            </div>
          </div>

          <div class="fin-modal__actions">
            <button class="fin-btn fin-btn--ghost" id="cpCancel" type="button">
              <i class="fa-solid fa-xmark"></i><span>Cancelar</span>
            </button>
            <button class="fin-btn" id="cpSave" type="submit">
              <i class="fa-solid fa-floppy-disk"></i><span>Salvar</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal: Confirmar Exclusão -->
  <div class="fin-modal" id="cpDelModal" aria-hidden="true">
    <div class="fin-modal__card" style="max-width:520px">
      <div class="fin-modal__head">
        <div class="fin-modal__title">Confirmar exclusão</div>
        <button class="fin-modal__close" id="cpDelClose" type="button" aria-label="Fechar">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <div class="fin-modal__body">
        <p style="margin:0 0 12px 0; color:#0b1220; font-weight:800; font-size:13px;">
          Tem certeza que deseja excluir este lançamento?
        </p>
        <div class="fin-modal__actions">
          <button class="fin-btn fin-btn--ghost" id="cpDelCancel" type="button">
            <i class="fa-solid fa-xmark"></i><span>Cancelar</span>
          </button>
          <button class="fin-btn" id="cpDelConfirm" type="button" style="border-color:var(--fin-danger); background:var(--fin-danger);">
            <i class="fa-solid fa-trash"></i><span>Excluir</span>
          </button>
        </div>
      </div>
    </div>
  </div>

  <script src="/sistema-visa/app/static/js/financeiro_contas_pagar.js"></script>
</div>