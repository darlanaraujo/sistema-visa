<?php
// app/modules/financeiro/relatorios.php

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// ---------------------------
// CATÁLOGO FINAL (sem BD nesta etapa)
// ---------------------------

$report_groups = [
  [
    'title' => 'Operacional',
    'desc'  => 'Relatórios do dia a dia para tomada de decisão rápida.',
    'items' => [
      [
        'id'    => 'rep_resumo_mes',
        'icon'  => 'fa-solid fa-calendar-days',
        'name'  => 'Resumo do período',
        'desc'  => 'Entradas x saídas, saldo e pendências no período.',
        'tags'  => ['KPIs', 'Resumo'],
      ],
      [
        'id'    => 'rep_fluxo',
        'icon'  => 'fa-solid fa-chart-column',
        'name'  => 'Fluxo (Entradas x Saídas)',
        'desc'  => 'Comparativo por dia/mês para visão rápida do caixa.',
        'tags'  => ['Barras', 'Fluxo'],
      ],
      [
        'id'    => 'rep_imoveis',
        'icon'  => 'fa-solid fa-building',
        'name'  => 'Despesas por Imóvel',
        'desc'  => 'Totais e distribuição de despesas por imóvel.',
        'tags'  => ['Imóveis', 'Obrigatório'],
      ],
      [
        'id'    => 'rep_categorias',
        'icon'  => 'fa-solid fa-tags',
        'name'  => 'Despesas por Categoria',
        'desc'  => 'Totais e distribuição de despesas por categoria.',
        'tags'  => ['Categoria', 'Obrigatório'],
      ],
    ],
  ],
  [
    'title' => 'Conferência',
    'desc'  => 'Relatórios para auditoria, conferência e rastreio de lançamentos.',
    'items' => [
      [
        'id'    => 'rep_vencimentos',
        'icon'  => 'fa-solid fa-hourglass-half',
        'name'  => 'Vencimentos (A pagar/A receber)',
        'desc'  => 'Eventos em aberto por data (alertas e atrasos).',
        'tags'  => ['Agenda', 'Atrasos'],
      ],
      [
        'id'    => 'rep_status',
        'icon'  => 'fa-solid fa-circle-check',
        'name'  => 'Status (Concluído x Pendente)',
        'desc'  => 'Distribuição por status para pagar/receber.',
        'tags'  => ['Status', 'Comparativo'],
      ],
      [
        'id'    => 'rep_recorrentes',
        'icon'  => 'fa-solid fa-repeat',
        'name'  => 'Recorrentes (Fixas)',
        'desc'  => 'Contas fixas no período (lista e totais).',
        'tags'  => ['Fixas', 'Recorrência'],
      ],
      [
        'id'    => 'rep_lancamentos',
        'icon'  => 'fa-solid fa-list-check',
        'name'  => 'Lançamentos (CP + CR)',
        'desc'  => 'Lista consolidada com filtros (para conferência).',
        'tags'  => ['Lista', 'Conferência'],
      ],
    ],
  ],
];

// Favoritos (mock)
$favorites = ['rep_resumo_mes', 'rep_imoveis'];

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

// Identidade corporativa
$corp = [
  'company' => 'Visa Remoções',
  'cnpj'    => '17.686.570/0001-80',
  'tagline' => 'Sistema Financeiro • Relatórios',
  'logo'    => '/sistema-visa/app/static/img/favicon.png',
  'site'    => 'visaremocoes.com.br',
];

// Payload para o JS
$fr_mock_payload = [
  'favorites' => $favorites,
  'groups'    => $report_groups,
  'periods'   => $period_options,
  'types'     => $type_options,
  'fav_limit' => 4,
  'corp'      => $corp,
];
$fr_mock_json = h(json_encode($fr_mock_payload, JSON_UNESCAPED_UNICODE));

?>

<?php
  $corp_json_attr = h(json_encode($corp, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
?>
<div class="fin-page" id="frPage"
    data-fr-mock="<?= $fr_mock_json ?>"
    data-fr-corp-json="<?= $corp_json_attr ?>">
  <div class="fin-head">
    <h1>Relatórios</h1>
    <p>Relatórios do financeiro para visão rápida e conferência. (Etapa sem banco de dados — usando localStorage)</p>
  </div>

  <?php include __DIR__ . '/_nav.php'; ?>

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
        <input id="frSearch" type="text" placeholder="Ex: imóvel, categoria, fluxo, vencimentos..." />
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

  <!-- Visão rápida (obrigatório) -->
  <section class="fin-panel fin-rep-section" aria-label="Visão rápida — principais gráficos">
    <div class="fin-panel__head">
      <div class="fin-panel__title">
        <i class="fa-solid fa-chart-pie"></i><span>Visão rápida</span>
      </div>
      <span class="fin-badge fin-badge--pt">Imóveis e Categorias (obrigatório)</span>
    </div>
    <div class="ui-chart" id="frQuickTop"></div>
  </section>

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

  <!-- Visão rápida (Operacional) -->
  <section class="fin-panel fin-rep-section" aria-label="Visão rápida — operacional">
    <div class="fin-panel__head">
      <div class="fin-panel__title">
        <i class="fa-solid fa-bolt"></i><span>Visão rápida (Operacional)</span>
      </div>
      <span class="fin-badge fin-badge--pt">evite impressão quando possível</span>
    </div>
    <div class="ui-chart" id="frQuickMid"></div>
  </section>

  <!-- Catálogo -->
  <?php foreach ($report_groups as $g): ?>
    <?php $gkey = group_key_from_title($g['title']); ?>

    <?php if ($gkey === 'operacional'): ?>
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
    <?php endif; ?>

    <?php if ($gkey === 'conferencia'): ?>
      <!-- Visão rápida (Conferência) -->
      <section class="fin-panel fin-rep-section" aria-label="Visão rápida — conferência">
        <div class="fin-panel__head">
          <div class="fin-panel__title">
            <i class="fa-solid fa-circle-check"></i><span>Visão rápida (Conferência)</span>
          </div>
          <span class="fin-badge fin-badge--pt">auditoria e controle</span>
        </div>
        <div class="ui-chart" id="frQuickBottom"></div>
      </section>

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
    <?php endif; ?>

  <?php endforeach; ?>

  <!-- Modal -->
  <div class="fin-modal" id="frModal" aria-hidden="true">
    <div class="fin-modal__card" style="max-width:980px">
      <div class="fin-modal__head">
        <div class="fin-modal__title" id="frModalTitle">Relatório</div>
        <button class="fin-modal__close" id="frModalClose" type="button" aria-label="Fechar">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <div class="fin-modal__body">
        <div class="fr-print" id="frPrintArea">
          <header class="fr-print__head">
            <div class="fr-print__brand">
              <img class="fr-print__logo" src="<?= h($corp['logo']) ?>" alt="<?= h($corp['company']) ?>">
              <div class="fr-print__brandtxt">
                <div class="fr-print__company"><?= h($corp['company']) ?></div>
                <div class="fr-print__sub">CNPJ: <?= h($corp['cnpj']) ?> • <?= h($corp['tagline']) ?></div>
              </div>
            </div>
            <div class="fr-print__meta">
              <div><span>Gerado em:</span> <strong id="frPrintGeneratedAt">—</strong></div>
              <div><span>Período:</span> <strong id="frPrintPeriod">—</strong></div>
              <div><span>Tipo:</span> <strong id="frPrintType">—</strong></div>
            </div>
          </header>

          <div class="fr-print__title">
            <div class="fr-print__h" id="frPrintTitle">Relatório</div>
            <div class="fr-print__desc" id="frPrintDesc">—</div>
          </div>

          <section class="fr-print__kpis" id="frPrintKpis" aria-label="Resumo"></section>

          <section class="fr-print__chart" id="frPrintChartSection" aria-label="Gráfico" hidden>
            <div class="fr-print__sectiontitle"><i class="fa-solid fa-chart-pie"></i> Gráfico</div>

            <!-- PADRÃO APROVADO: gráfico + dados lado a lado -->
            <div class="fr-print__chartgrid">
              <div class="fr-print__chartwrap">
                <canvas id="frPrintChart" height="180" aria-label="Gráfico do relatório"></canvas>
                <img id="frPrintChartImg" alt="Gráfico" style="display:none; width:100%; height:auto;" />
              </div>

              <div class="fr-print__chartsum" aria-label="Dados do gráfico">
                <div class="fr-print__sumtitle">Dados</div>
                <div class="fr-print__sumgrid" id="frPrintChartSum"></div>
                <div class="fr-print__sumtotal" id="frPrintChartTotal"></div>
                <div class="fr-print__hint" id="frPrintChartHint" hidden>
                  * Chart.js não disponível nesta etapa. O relatório será impresso sem gráfico.
                </div>
              </div>
            </div>
          </section>

          <section class="fr-print__table" aria-label="Tabela">
            <div class="fr-print__sectiontitle"><i class="fa-solid fa-table"></i> Dados</div>
            <div class="fr-print__tablewrap">
              <table class="fr-print__t" id="frPrintTable">
                <thead id="frPrintThead"></thead>
                <tbody id="frPrintTbody"></tbody>
              </table>
            </div>
            <div class="fr-print__footnote" id="frPrintFootnote"></div>
          </section>

          <footer class="fr-print__footer">
            <div>Documento gerado automaticamente pelo Sistema Visa.</div>
            <div class="fr-print__pagenum">Página <span class="fr-page"></span></div>
          </footer>
        </div>

        <div class="fin-modal__actions">
          <button class="fin-btn fin-btn--ghost" id="frModalGhost" type="button">
            <i class="fa-solid fa-file-export"></i><span>Exportar (CSV)</span>
          </button>
          <button class="fin-btn fin-btn--ghost" id="frModalPrint" type="button">
            <i class="fa-solid fa-print"></i><span>Imprimir / PDF</span>
          </button>
          <button class="fin-btn" id="frModalRun" type="button">
            <i class="fa-solid fa-play"></i><span>Executar</span>
          </button>
        </div>
      </div>
    </div>
  </div>

  <div class="fin-rep-toast" id="frToast" role="status" aria-live="polite" aria-atomic="true"></div>
</div>