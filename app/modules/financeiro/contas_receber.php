<?php
// app/modules/financeiro/contas_receber.php
?>

<div class="fin-page" id="crPage">
  <div class="fin-head">
    <h1>Contas a Receber</h1>
    <p>Agenda de recebimentos e lançamentos com vínculo real à base de Cadastros do sistema.</p>
  </div>

  <?php include __DIR__ . '/_nav.php'; ?>

  <!-- KPIs + CTA -->
  <div class="fin-toolbar-panel">
    <div class="fin-toolbar">

      <!-- Logo (primeira coluna) -->
      <div class="fin-toolbar__block fin-toolbar__block--logo">
        <div class="fin-kpi-logo">
          <img src="<?= h(app_url('/app/static/img/logo.png')) ?>" alt="Sistema Visa" />
        </div>
      </div>

      <!-- Total a Receber -->
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
              Total a Receber
            </div>

            <div class="fin-kpi__value fin-kpi__value--danger" id="crTotalOpen">R$ 0,00</div>
          </div>
        </div>
      </div>

      <!-- Total Recebido -->
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
              Total Recebido
            </div>

            <div class="fin-kpi__value fin-kpi__value--success" id="crTotalDone">R$ 0,00</div>
          </div>
        </div>
      </div>

      <!-- CTA -->
      <div class="fin-toolbar__block fin-toolbar__block--cta">
        <button class="fin-btn" id="crNew" type="button" title="Novo lançamento">
          <i class="fa-solid fa-plus"></i><span>Novo lançamento</span>
        </button>
      </div>

    </div>
  </div>

  <!-- Filtros -->
  <div class="fin-filters-wrap" id="crFiltersWrap">
    <div class="fin-filters-summary" id="crFiltersToggle">
      <div><i class="fa-solid fa-filter"></i> Filtros</div>
      <i class="fa-solid fa-chevron-down" id="crFiltersIcon"></i>
    </div>

    <div class="fin-filters" id="crFiltersGrid">
      <div class="fin-filter">
        <label>Cliente</label>
        <div class="cr-lookup" data-cr-lookup="filter">
          <input id="crFilterCliente" type="search" placeholder="Nome ou documento" autocomplete="off" />
          <div class="cr-lookup__menu" id="crFilterClienteMenu" hidden></div>
        </div>
        <input id="crFilterCadastroId" type="hidden" />
      </div>

      <div class="fin-filter">
        <label>Status</label>
        <select id="crFilterStatus">
          <option value="">Todos</option>
          <option value="open">A receber</option>
          <option value="done">Recebido</option>
        </select>
      </div>

      <div class="fin-filter">
        <label>Forma</label>
        <select id="crFilterForma" data-fin-catalog="formas_recebimento">
          <option value="">Todas</option>
          <option value="Boleto">Boleto</option>
          <option value="Cheque">Cheque</option>
          <option value="PIX Futuro">PIX Futuro</option>
          <option value="Depósito">Depósito</option>
          <option value="Transferência">Transferência</option>
          <option value="Outro">Outro</option>
        </select>
      </div>

      <div class="fin-filter">
        <label>Processo</label>
        <input id="crFilterProcesso" type="text" placeholder="Ex: PRC-2026-001" />
      </div>

      <div class="fin-filter">
        <label>Buscar (Cliente / Obs.)</label>
        <input id="crFilterSearch" type="text" placeholder="Ex: contrato em aberto" />
      </div>

      <div class="fin-filter" style="min-width:160px">
        <label>&nbsp;</label>
        <button class="fin-btn fin-btn--ghost" id="crClear" type="button" title="Limpar filtros">
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
        <button class="fin-icon-btn fin-icon-btn--sm" id="crPrev" type="button" title="Mês anterior">
          <i class="fa-solid fa-chevron-left"></i>
        </button>
        <div class="fin-monthline__label" id="crMonthLabel">—</div>
        <button class="fin-icon-btn fin-icon-btn--sm" id="crNext" type="button" title="Próximo mês">
          <i class="fa-solid fa-chevron-right"></i>
        </button>
      </div>

      <span class="fin-badge" id="crCount">0 itens</span>
    </div>

    <div class="fin-table-wrap is-scroll-y" id="crTableWrap">
      <table class="fin-table" id="crTable">
        <thead>
          <tr>
            <th>Cliente</th>
            <th>Valor</th>
            <th>Venc.</th>
            <th>Forma</th>
            <th>Processo</th>
            <th>Status</th>
            <th>Ações</th>
          </tr>
        </thead>
        <tbody id="crTbody"></tbody>
      </table>
    </div>
  </div>

  <!-- Modal: Novo/Editar -->
  <div class="fin-modal" id="crModal" aria-hidden="true">
    <div class="fin-modal__card">
      <div class="fin-modal__head">
        <div class="fin-modal__title" id="crModalTitle">Novo lançamento</div>
        <button class="fin-modal__close" id="crModalClose" type="button" aria-label="Fechar">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <div class="fin-modal__body">
        <form class="fin-form" id="crForm" action="javascript:void(0)">
          <input type="hidden" id="crId" />

          <div class="fin-form__row">
            <div class="fin-field">
              <label>Cliente</label>
              <div class="cr-lookup" data-cr-lookup="form">
                <input id="crCliente" type="search" placeholder="Nome ou documento" autocomplete="off" required />
                <div class="cr-lookup__menu" id="crClienteMenu" hidden></div>
              </div>
              <input id="crCadastroId" type="hidden" />
              <div class="cr-client-meta">
                <div class="cr-client-meta__field">
                  <label for="crClienteDocumento">CPF/CNPJ</label>
                  <input id="crClienteDocumento" type="text" readonly placeholder="Preenchido ao selecionar o cadastro" />
                </div>
              </div>
            </div>
            <div class="fin-field">
              <label>Valor</label>
              <input id="crValor" type="text" placeholder="Ex: 12500.00" required />
            </div>
          </div>

          <div class="fin-form__row">
            <div class="fin-field">
              <label>Vencimento / Previsão</label>
              <input id="crData" type="date" required />
            </div>
            <div class="fin-field">
              <label>Meio de Pagamento</label>
              <select id="crForma" data-fin-catalog="formas_recebimento" required>
                <option value="">Selecione</option>
                <option value="Boleto">Boleto</option>
                <option value="Cheque">Cheque</option>
                <option value="PIX Futuro">PIX Futuro</option>
                <option value="Depósito">Depósito</option>
                <option value="Transferência">Transferência</option>
                <option value="Outro">Outro</option>
              </select>
            </div>
          </div>

          <div class="fin-form__row">
            <div class="fin-field">
              <label>Processo (opcional)</label>
              <input id="crProcesso" type="text" placeholder="Ex: PRC-2026-001" />
            </div>
            <div class="fin-field">
              <label>Parcelas</label>
              <select id="crParcelas">
                <option value="1">1x</option>
                <option value="2">2x</option>
                <option value="3">3x</option>
                <option value="4">4x</option>
                <option value="5">5x</option>
                <option value="6">6x</option>
                <option value="7">7x</option>
                <option value="8">8x</option>
                <option value="9">9x</option>
                <option value="10">10x</option>
                <option value="11">11x</option>
                <option value="12">12x</option>
              </select>
            </div>
          </div>

          <div class="fin-modal__actions">
            <button class="fin-btn fin-btn--ghost" id="crCancel" type="button">Cancelar</button>
            <button class="fin-btn" id="crSave" type="submit">
              <i class="fa-solid fa-floppy-disk"></i><span>Salvar</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal: Confirmar Exclusão -->
  <div class="fin-modal" id="crDelModal" aria-hidden="true">
    <div class="fin-modal__card" style="max-width:520px">
      <div class="fin-modal__head">
        <div class="fin-modal__title">Confirmar exclusão</div>
        <button class="fin-modal__close" id="crDelClose" type="button" aria-label="Fechar">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <div class="fin-modal__body">
        <p style="margin:0 0 12px 0; color:#0b1220; font-weight:800; font-size:13px;">
          Tem certeza que deseja excluir este lançamento?
        </p>
        <div class="fin-modal__actions">
          <button class="fin-btn fin-btn--ghost" id="crDelCancel" type="button">Cancelar</button>
          <button class="fin-btn" id="crDelConfirm" type="button" style="border-color:var(--fin-danger); background:var(--fin-danger);">
            <i class="fa-solid fa-trash"></i><span>Excluir</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</div>
