<?php
// app/modules/financeiro/relatorios.php

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// ---------------------------
// MOCKS (sem BD nesta etapa)
// ---------------------------

$report_groups = [
  [
    'title' => 'Operacional',
    'desc'  => 'Relatórios do dia a dia para tomada de decisão rápida.',
    'items' => [
      [
        'id'    => 'rep_resumo_mes',
        'icon'  => 'fa-solid fa-calendar-days',
        'name'  => 'Resumo do mês',
        'desc'  => 'Entradas x saídas, saldo e pendências do mês.',
        'tags'  => ['Mensal', 'KPIs'],
      ],
      [
        'id'    => 'rep_vencimentos',
        'icon'  => 'fa-solid fa-hourglass-half',
        'name'  => 'Vencimentos',
        'desc'  => 'A pagar e a receber por data (alertas e atrasos).',
        'tags'  => ['Agenda', 'Atrasos'],
      ],
      [
        'id'    => 'rep_pagar_aberto',
        'icon'  => 'fa-solid fa-file-invoice-dollar',
        'name'  => 'Contas a pagar (abertas)',
        'desc'  => 'Lista de contas pendentes com filtros por mês.',
        'tags'  => ['Pagar', 'Pendentes'],
      ],
      [
        'id'    => 'rep_receber_aberto',
        'icon'  => 'fa-solid fa-hand-holding-dollar',
        'name'  => 'Contas a receber (abertas)',
        'desc'  => 'Recebíveis pendentes com filtro por status e mês.',
        'tags'  => ['Receber', 'Pendentes'],
      ],
    ],
  ],
  [
    'title' => 'Conferência',
    'desc'  => 'Relatórios para auditoria, conferência e rastreio de lançamentos.',
    'items' => [
      [
        'id'    => 'rep_lancamentos',
        'icon'  => 'fa-solid fa-list-check',
        'name'  => 'Lançamentos',
        'desc'  => 'Lista completa com filtros (mock nesta etapa).',
        'tags'  => ['Lista', 'Filtro'],
      ],
      [
        'id'    => 'rep_status',
        'icon'  => 'fa-solid fa-circle-check',
        'name'  => 'Status (Pago x Pendente)',
        'desc'  => 'Distribuição por status e evolução no período.',
        'tags'  => ['Status', 'Comparativo'],
      ],
      [
        'id'    => 'rep_exportacao',
        'icon'  => 'fa-solid fa-file-export',
        'name'  => 'Exportação (CSV/PDF)',
        'desc'  => 'Gerar arquivos para contabilidade (mock).',
        'tags'  => ['Exportar', 'Arquivo'],
      ],
    ],
  ],
];

// Favoritos (mock) — ids do array acima (JS pode sobrescrever via localStorage)
$favorites = ['rep_resumo_mes', 'rep_vencimentos'];

// Períodos (mock)
$period_options = [
  ['value' => 'this_month', 'label' => 'Este mês'],
  ['value' => 'last_month', 'label' => 'Mês passado'],
  ['value' => 'last_30',    'label' => 'Últimos 30 dias'],
  ['value' => 'custom',     'label' => 'Personalizado'],
];

// Tipos (mock)
$type_options = [
  ['value' => '', 'label' => 'Todos'],
  ['value' => 'operacional', 'label' => 'Operacional'],
  ['value' => 'conferencia', 'label' => 'Conferência'],
];

function group_key_from_title($title){
  $t = mb_strtolower((string)$title);
  if (strpos($t, 'operacional') !== false) return 'operacional';
  if (strpos($t, 'confer') !== false) return 'conferencia';
  return 'outros';
}

?>

<div class="fin-page" id="frPage">
  <div class="fin-head">
    <h1>Relatórios</h1>
    <p>Relatórios do financeiro para visão rápida e conferência. (Mock nesta etapa — sem banco de dados)</p>
  </div>

  <?php include __DIR__ . '/_nav.php'; ?>

  <!-- Filtros (PADRÃO do módulo: mesmo bloco de Pagar/Receber) -->
  <div class="fin-filters-wrap fin-rep-filters-wrap" id="frFiltersWrap">
    <div class="fin-filters-summary" id="frFiltersToggle">
      <div><i class="fa-solid fa-filter"></i> Filtros</div>
      <i class="fa-solid fa-chevron-down" id="frFiltersIcon"></i>
    </div>

    <div class="fin-filters" id="frFiltersGrid">
      <div class="fin-filter">
        <label>Período</label>
        <select id="frPeriod">
          <?php foreach($period_options as $op): ?>
            <option value="<?= h($op['value']) ?>"><?= h($op['label']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="fin-filter">
        <label>Tipo</label>
        <select id="frType">
          <?php foreach($type_options as $op): ?>
            <option value="<?= h($op['value']) ?>"><?= h($op['label']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="fin-filter">
        <label>Buscar</label>
        <input id="frSearch" type="text" placeholder="Ex: vencimentos, pagar, exportação..." />
      </div>

      <div class="fin-filter fin-rep-filter-btn">
        <label>&nbsp;</label>
        <button class="fin-btn fin-btn--ghost" id="frClear" type="button" title="Limpar filtros">
          <i class="fa-solid fa-eraser"></i><span>Limpar</span>
        </button>
      </div>

      <div class="fin-filter fin-rep-filter-btn">
        <label>&nbsp;</label>
        <button class="fin-btn" id="frRun" type="button" title="Executar (mock)">
          <i class="fa-solid fa-bolt"></i><span>Executar</span>
        </button>
      </div>
    </div>
  </div>

  <!-- Favoritos -->
  <section class="fin-panel fin-rep-section" aria-label="Relatórios favoritos">
    <div class="fin-panel__head">
      <div class="fin-panel__title">
        <i class="fa-solid fa-star"></i><span>Favoritos</span>
      </div>
      <span class="fin-badge fin-badge--pt">atalho</span>
    </div>

    <div class="fin-rep-grid" id="frFavGrid"></div>

    <div class="fin-rep-empty" id="frFavEmpty" hidden>
      Nenhum favorito ainda. Clique na estrela de um relatório para fixar aqui.
    </div>
  </section>

  <!-- Catálogo -->
  <?php foreach ($report_groups as $g): ?>
    <?php $gkey = group_key_from_title($g['title']); ?>
    <section class="fin-panel fin-rep-section" data-group="<?= h($gkey) ?>">
      <div class="fin-panel__head">
        <div class="fin-panel__title">
          <i class="fa-solid fa-folder-open"></i><span><?= h($g['title']) ?></span>
        </div>
        <span class="fin-badge fin-badge--pt"><?= h($g['desc']) ?></span>
      </div>

      <div class="fin-rep-grid">
        <?php foreach ($g['items'] as $it): ?>
          <?php
            $isFav = in_array($it['id'], $favorites, true);
            $tags  = implode(' • ', $it['tags'] ?? []);
          ?>
          <article
            class="fin-rep-card"
            role="button"
            tabindex="0"
            data-report-id="<?= h($it['id']) ?>"
            data-group="<?= h($gkey) ?>"
            data-name="<?= h($it['name']) ?>"
            data-desc="<?= h($it['desc']) ?>"
            data-icon="<?= h($it['icon']) ?>"
            data-tags="<?= h($tags) ?>"
          >
            <div class="fin-rep-card__top">
              <div class="fin-rep-card__icon"><i class="<?= h($it['icon']) ?>"></i></div>
              <button
                class="fin-rep-card__fav <?= $isFav ? 'is-on' : '' ?>"
                type="button"
                aria-label="<?= $isFav ? 'Remover dos favoritos' : 'Adicionar aos favoritos' ?>"
                title="<?= $isFav ? 'Favorito' : 'Favoritar' ?>"
              >
                <i class="fa-solid fa-star"></i>
              </button>
            </div>

            <div class="fin-rep-card__title"><?= h($it['name']) ?></div>
            <div class="fin-rep-card__desc"><?= h($it['desc']) ?></div>

            <?php if ($tags): ?>
              <div class="fin-rep-card__meta"><?= h($tags) ?></div>
            <?php endif; ?>

            <div class="fin-rep-card__cta">
              <i class="fa-solid fa-arrow-right"></i><span>Abrir</span>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endforeach; ?>

  <!-- Modal (mock de execução do relatório) -->
  <div class="fin-modal" id="frModal" aria-hidden="true">
    <div class="fin-modal__card" style="max-width:760px">
      <div class="fin-modal__head">
        <div class="fin-modal__title" id="frModalTitle">Relatório</div>
        <button class="fin-modal__close" id="frModalClose" type="button" aria-label="Fechar">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <div class="fin-modal__body">
        <div class="fin-rep-modal">
          <div class="fin-rep-modal__row">
            <div class="fin-rep-modal__k">Período</div>
            <div class="fin-rep-modal__v" id="frModalPeriod">—</div>
          </div>
          <div class="fin-rep-modal__row">
            <div class="fin-rep-modal__k">Tipo</div>
            <div class="fin-rep-modal__v" id="frModalType">—</div>
          </div>
          <div class="fin-rep-modal__row">
            <div class="fin-rep-modal__k">Relatório</div>
            <div class="fin-rep-modal__v" id="frModalReport">—</div>
          </div>

          <div class="fin-rep-modal__hint">
            Mock nesta etapa: aqui entrará a renderização real (tabela/gráfico/exportação) quando conectar ao banco.
          </div>

          <div class="fin-modal__actions">
            <button class="fin-btn fin-btn--ghost" id="frModalGhost" type="button">
              <i class="fa-solid fa-file-export"></i><span>Exportar (mock)</span>
            </button>
            <button class="fin-btn" id="frModalRun" type="button">
              <i class="fa-solid fa-play"></i><span>Executar (mock)</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Toast local (mantido como fallback) -->
  <div class="fin-rep-toast" id="frToast" role="status" aria-live="polite" aria-atomic="true"></div>

  <script>
    window.__FR_MOCK__ = <?= json_encode([
      'favorites' => $favorites,
      'groups'    => $report_groups,
      'periods'   => $period_options,
      'types'     => $type_options,
      'fav_limit' => 4,
    ], JSON_UNESCAPED_UNICODE) ?>;
  </script>

  <script src="/sistema-visa/app/static/js/financeiro_relatorios.js"></script>
</div>