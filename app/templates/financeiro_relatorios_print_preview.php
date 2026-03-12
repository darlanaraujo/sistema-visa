<?php
// app/templates/financeiro_relatorios_print_preview.php
// Preview de impressão do relatório (tela separada / janela nova).
// ⚠️ Esta página NÃO usa base_private.php, então precisa carregar helpers manualmente.

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Proteção (mesmo padrão do base_private.php)
if (!isset($_SESSION['auth_user'])) {
  require_once __DIR__ . '/../core/url.php';
  header('Location: ' . app_url('/app/templates/login.php'));
  exit;
}

// ---------------------------------------------------------
// Helpers globais (fonte única)
// - Necessário porque esta página usa h() e não passa pelo base_private.php
// ---------------------------------------------------------
require_once __DIR__ . '/../../public_php/src/Support/helpers.php';
require_once __DIR__ . '/../core/url.php';

// ---------------------------------------------------------
// Company (fonte única dos dados da empresa)
// ---------------------------------------------------------
require_once __DIR__ . '/../core/company.php'; // app/core/company.php

/**
 * Classe de alinhamento das colunas da tabela
 * @param array $alignArr
 * @param int $i
 * @return string
 */
function alignClass($alignArr, $i){
  $a = strtolower((string)($alignArr[$i] ?? 'left'));
  if ($a === 'right') return 't-right';
  if ($a === 'center') return 't-center';
  return '';
}

function toneClass($label, $value = '', $fallback = ''){
  $l = strtolower((string)$label);
  $v = strtolower((string)$value);
  $f = strtolower((string)$fallback);
  $text = trim($l . ' ' . $v . ' ' . $f);

  if (preg_match('/saldo/u', $text)) {
    $raw = preg_replace('/[^0-9,.-]/', '', (string)$value);
    if (strpos($raw, ',') !== false) {
      $raw = str_replace('.', '', $raw);
      $raw = str_replace(',', '.', $raw);
    }
    $num = (float)$raw;
    if ($num > 0) return 'is-balance-pos';
    if ($num < 0) return 'is-balance-neg';
    return 'is-balance-neutral';
  }
  if (preg_match('/(entrada|entradas|receber|recebimento|receita|\bcr\b)/u', $text)) return 'is-in';
  if (preg_match('/(saida|saídas|saidas|pagar|despesa|custo|\bcp\b)/u', $text)) return 'is-out';
  return '';
}

// ---------------------------------------------------------
// Payload vindo do POST
// ---------------------------------------------------------
$raw = $_POST['payload'] ?? '';
$data = null;

if (is_string($raw) && $raw !== '') {
  $decoded = json_decode($raw, true);
  if (is_array($decoded)) $data = $decoded;
}

if (!$data) {
  http_response_code(400);
  echo '<p style="font-family:Inter,system-ui; padding:16px;">Payload inválido.</p>';
  exit;
}

// ---------------------------------------------------------
// Identidade corporativa (fonte única)
// ---------------------------------------------------------
$corp = company_get();

$meta  = is_array($data['meta'] ?? null) ? $data['meta'] : [];
$title = (string)($data['title'] ?? 'Relatório');
$desc  = (string)($data['desc'] ?? '');

$kpis = is_array($data['kpis'] ?? null) ? $data['kpis'] : [];

$table = is_array($data['table'] ?? null) ? $data['table'] : [];
$align = is_array($table['align'] ?? null) ? $table['align'] : [];
$thead = is_array($table['head'] ?? null) ? $table['head'] : [];
$rows  = is_array($table['rows'] ?? null) ? $table['rows'] : [];
$total = is_array($table['total'] ?? null) ? $table['total'] : null;
$totalTones = is_array($table['totalTones'] ?? null) ? $table['totalTones'] : [];

$chart = is_array($data['chart'] ?? null) ? $data['chart'] : [];
$chartImg = (string)($chart['img'] ?? '');
$chartSum = is_array($chart['sum'] ?? null) ? $chart['sum'] : [];

$layout = (string)($data['layout'] ?? '');
$splitData = is_array($data['splitData'] ?? null) ? $data['splitData'] : null;
$prefs = is_array($data['prefs'] ?? null) ? $data['prefs'] : [];

$footnote = (string)($data['footnote'] ?? '');
?>
<!doctype html>
<html lang="pt-br" data-theme="light" class="theme-light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h($title) ?> • Preview</title>

  <!-- Favicon (usa fonte única; fallback mantém padrão) -->
  <link rel="icon" type="image/png" sizes="32x32" href="<?= h($corp['favicon'] ?? app_url('/app/static/img/favicon.png')) ?>">
  <link rel="icon" type="image/png" sizes="16x16" href="<?= h($corp['favicon'] ?? app_url('/app/static/img/favicon.png')) ?>">
  <link rel="apple-touch-icon" sizes="180x180" href="<?= h($corp['favicon'] ?? app_url('/app/static/img/favicon.png')) ?>">

  <!-- Fonte -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <!-- CSS do módulo -->
  <link rel="stylesheet" href="<?= h(app_url('/app/static/css/theme.css')) ?>">
  <link rel="stylesheet" href="<?= h(app_url('/app/static/css/global.css')) ?>">

  <link rel="stylesheet" href="<?= h(app_url('/app/static/css/financeiro.css')) ?>">
  <link rel="stylesheet" href="<?= h(app_url('/app/static/css/financeiro_relatorios.css')) ?>">
  <link rel="stylesheet" href="<?= h(app_url('/app/static/css/financeiro_relatorios_print.css')) ?>">
  <link rel="stylesheet" href="<?= h(app_url('/app/static/css/financeiro_relatorios_print_preview.css')) ?>">
</head>
<body>

  <div class="fr-prevbar" role="region" aria-label="Ações do preview">
    <div class="fr-prevbar__left">
      <div class="fr-prevbar__title"><?= h($title) ?></div>
      <div class="fr-prevbar__hint">
        Para salvar: <strong>Cmd+P</strong> (Mac) / <strong>Ctrl+P</strong> (Windows) → Destino: <strong>Salvar como PDF</strong>
      </div>
    </div>

    <div class="fr-prevbar__actions">
      <button type="button" class="fin-btn fin-btn--ghost" onclick="window.close()">
        <i class="fa-solid fa-xmark"></i><span>Fechar</span>
      </button>

      <button type="button" class="fin-btn" onclick="window.print()">
        <i class="fa-solid fa-print"></i><span>Imprimir / Salvar PDF</span>
      </button>
    </div>
  </div>

  <div class="page">
    <div class="fr-print">

      <header class="fr-print__head">
        <div class="fr-print__brand">
          <img
            id="frPreviewReportLogo"
            data-brand="report-logo"
            data-favicon-default="<?= h($corp['favicon'] ?? app_url('/app/static/img/favicon.png')) ?>"
            class="fr-print__logo"
            src="<?= h($corp['report_logo'] ?? $corp['favicon'] ?? app_url('/app/static/img/favicon.png')) ?>"
            alt="<?= h($corp['company'] ?? 'Empresa') ?>"
          >

          <div class="fr-print__brandtxt">
            <div class="fr-print__company"><?= h($corp['company'] ?? '—') ?></div>
            <div class="fr-print__sub">
              <?php
                $cnpj = $corp['cnpj'] ?? '';
                $tag  = $corp['tagline'] ?? '';
                $subParts = [];
                if ($cnpj) $subParts[] = 'CNPJ: ' . $cnpj;
                if ($tag)  $subParts[] = $tag;
                echo h(implode(' • ', $subParts));
              ?>
            </div>
          </div>
        </div>

        <div class="fr-print__meta">
          <div><span>Gerado em:</span> <strong><?= h($meta['generatedAt'] ?? '—') ?></strong></div>
          <div><span>Período:</span> <strong><?= h($meta['period'] ?? '—') ?></strong></div>
          <div><span>Tipo:</span> <strong><?= h($meta['type'] ?? '—') ?></strong></div>
        </div>
      </header>

      <div class="fr-print__title">
        <div class="fr-print__h"><?= h($title) ?></div>
        <div class="fr-print__desc"><?= h($desc) ?></div>
      </div>

      <section class="fr-print__kpis" aria-label="Resumo">
        <?php foreach ($kpis as $k): ?>
          <?php
            $kl = is_array($k) ? ($k['label'] ?? '') : '';
            $kv = is_array($k) ? ($k['value'] ?? '') : '';
            $kt = is_array($k) ? (string)($k['tone'] ?? '') : '';
            if ($kt === '') $kt = toneClass($kl, $kv, '');
          ?>
          <div class="fr-kpi <?= h($kt) ?>">
            <div class="fr-kpi__k"><?= h($kl) ?></div>
            <div class="fr-kpi__v"><?= h($kv) ?></div>
          </div>
        <?php endforeach; ?>
      </section>

      <?php if ($chartImg): ?>
        <section class="fr-print__chart" aria-label="Gráfico">
          <div class="fr-print__sectiontitle"><i class="fa-solid fa-chart-pie"></i> Gráfico</div>

          <div class="fr-print__chartgrid">
            <div class="fr-print__chartwrap">
              <img id="frPrintChartImg" src="<?= h($chartImg) ?>" alt="Gráfico" />
            </div>

            <aside class="fr-print__chartsum">
              <div class="fr-print__sumtitle">Valores do gráfico</div>

              <div class="fr-print__sumgrid">
                <?php if (!empty($chartSum)): ?>
                  <?php foreach ($chartSum as $r): ?>
                    <?php
                      $lb = is_array($r) ? ($r['label'] ?? '') : '';
                      $vv = is_array($r) ? ($r['value'] ?? '') : '';
                      $pp = is_array($r) ? ($r['pct'] ?? '') : '';
                      $tt = is_array($r) ? (string)($r['tone'] ?? '') : '';
                      if ($tt === '') $tt = toneClass($lb, $vv, '');
                    ?>
                    <div class="fr-print__sumrow <?= h($tt) ?>">
                      <div class="fr-print__sumcell fr-print__sumlabel"><?= h($lb) ?></div>
                      <div class="fr-print__sumcell fr-print__sumval"><?= h($vv) ?></div>
                      <div class="fr-print__sumcell fr-print__sumpct"><?= h($pp) ?></div>
                    </div>
                  <?php endforeach; ?>
                <?php else: ?>
                  <div class="fr-print__sumrow">
                    <div class="fr-print__sumcell fr-print__sumlabel">Sem dados</div>
                    <div class="fr-print__sumcell fr-print__sumval">—</div>
                    <div class="fr-print__sumcell fr-print__sumpct">—</div>
                  </div>
                <?php endif; ?>
              </div>
            </aside>
          </div>
        </section>
      <?php endif; ?>

      <section class="fr-print__table" aria-label="Tabela">
        <div class="fr-print__sectiontitle"><i class="fa-solid fa-table"></i> Dados</div>

        <?php if ($layout === 'cpcr_split' && is_array($splitData)): ?>
          <?php
            $cpRows = is_array($splitData['cpRows'] ?? null) ? $splitData['cpRows'] : [];
            $crRows = is_array($splitData['crRows'] ?? null) ? $splitData['crRows'] : [];
            $totalCP = (float)($splitData['totalCP'] ?? 0);
            $totalCR = (float)($splitData['totalCR'] ?? 0);
            $saldo = (float)($splitData['saldo'] ?? 0);
            $saldoClass = $saldo > 0 ? 'is-pos' : ($saldo < 0 ? 'is-neg' : 'is-neutral');

            $statusText = static function ($s) {
              return (strtolower((string)$s) === 'done') ? 'Concluído' : 'Aberto';
            };
          ?>
          <div class="fr-print__tablewrap">
            <div class="fr-resumo-split">
              <div class="fr-resumo-box is-cr">
                <div class="fr-resumo-box__head">Contas a Receber (CR)</div>
                <table class="fr-print__t fr-resumo-box__table">
                  <thead>
                    <tr>
                      <th>Descrição</th>
                      <th>Detalhe</th>
                      <th>Data</th>
                      <th>Status</th>
                      <th>Valor</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (!empty($crRows)): ?>
                      <?php foreach ($crRows as $r): ?>
                        <?php $r = is_array($r) ? $r : []; ?>
                        <tr>
                          <td><?= h($r['descricao'] ?? '—') ?></td>
                          <td><?= h($r['detalhe'] ?? '—') ?></td>
                          <td class="t-center"><?= h(isset($r['data']) ? date('d/m/Y', strtotime((string)$r['data'])) : '—') ?></td>
                          <td class="t-center"><?= h($statusText($r['status'] ?? 'open')) ?></td>
                          <td class="t-right"><?= h('R$ ' . number_format((float)($r['valor'] ?? 0), 2, ',', '.')) ?></td>
                        </tr>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <tr><td colspan="5">Nenhum item no período.</td></tr>
                    <?php endif; ?>
                  </tbody>
                </table>
                <div class="fr-resumo-box__total is-cr">Total CR: <?= h('R$ ' . number_format($totalCR, 2, ',', '.')) ?></div>
              </div>

              <div class="fr-resumo-box is-cp">
                <div class="fr-resumo-box__head">Contas a Pagar (CP)</div>
                <table class="fr-print__t fr-resumo-box__table">
                  <thead>
                    <tr>
                      <th>Descrição</th>
                      <th>Detalhe</th>
                      <th>Data</th>
                      <th>Status</th>
                      <th>Valor</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (!empty($cpRows)): ?>
                      <?php foreach ($cpRows as $r): ?>
                        <?php $r = is_array($r) ? $r : []; ?>
                        <tr>
                          <td><?= h($r['descricao'] ?? '—') ?></td>
                          <td><?= h($r['detalhe'] ?? '—') ?></td>
                          <td class="t-center"><?= h(isset($r['data']) ? date('d/m/Y', strtotime((string)$r['data'])) : '—') ?></td>
                          <td class="t-center"><?= h($statusText($r['status'] ?? 'open')) ?></td>
                          <td class="t-right"><?= h('R$ ' . number_format((float)($r['valor'] ?? 0), 2, ',', '.')) ?></td>
                        </tr>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <tr><td colspan="5">Nenhum item no período.</td></tr>
                    <?php endif; ?>
                  </tbody>
                </table>
                <div class="fr-resumo-box__total is-cp">Total CP: <?= h('R$ ' . number_format($totalCP, 2, ',', '.')) ?></div>
              </div>
            </div>

            <div class="fr-resumo-saldo <?= h($saldoClass) ?>">
              <span>Saldo do período</span>
              <strong><?= h('R$ ' . number_format($saldo, 2, ',', '.')) ?></strong>
            </div>
          </div>
        <?php else: ?>
          <div class="fr-print__tablewrap">
            <table class="fr-print__t">
              <?php if (!empty($thead)): ?>
                <thead>
                  <tr>
                    <?php foreach ($thead as $i => $th): ?>
                      <th class="<?= h(alignClass($align, $i)) ?>"><?= h($th) ?></th>
                    <?php endforeach; ?>
                  </tr>
                </thead>
              <?php endif; ?>

              <tbody>
                <?php if (!empty($rows)): ?>
                  <?php foreach ($rows as $r): ?>
                    <tr>
                      <?php
                        $cells = is_array($r) ? $r : [$r];
                        foreach ($cells as $i => $c):
                      ?>
                        <td class="<?= h(alignClass($align, $i)) ?>"><?= h($c) ?></td>
                      <?php endforeach; ?>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr><td colspan="<?= max(1, count($thead)) ?>"><?= h('Nenhum dado no período.') ?></td></tr>
                <?php endif; ?>

                <?php if (is_array($total) && !empty($total)): ?>
                  <tr class="is-total">
                    <?php foreach ($total as $i => $c): ?>
                      <?php
                        $cls = alignClass($align, $i);
                        $tone = (string)($totalTones[$i] ?? '');
                        if ($tone === '') $tone = toneClass($thead[$i] ?? '', $c, $total[0] ?? '');
                        $cls = trim($cls . ' ' . $tone);
                      ?>
                      <td class="<?= h($cls) ?>"><?= h($c) ?></td>
                    <?php endforeach; ?>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>

        <div class="fr-print__footnote"><?= h($footnote) ?></div>
      </section>

      <footer class="fr-print__footer">
        <div><?= h($corp['report_footer_note'] ?? 'Documento gerado automaticamente pelo Sistema Visa.') ?></div>
        <div class="fr-print__pagenum">Página <span class="fr-page"></span></div>
      </footer>

    </div>
  </div>

  <script>
  (function(){
    const payloadPrefs = <?= json_encode($prefs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const root = document.documentElement;

    function isHex(v){
      return /^#[0-9a-f]{6}$/i.test(String(v || '').trim());
    }

    function applyAccent(theme){
      if(!theme || typeof theme !== 'object') return;
      const preset = String(theme.accentPreset || theme.accent_preset || '').trim().toLowerCase();
      const primary = String(theme.primary || '').trim();
      const accent = String(theme.accent || '').trim();
      const danger = String(theme.danger || '').trim();
      const success = String(theme.success || '').trim();

      const customAccent = isHex(accent) ? accent : (isHex(primary) ? primary : '');
      if(customAccent){
        root.removeAttribute('data-accent');
        root.style.setProperty('--c-accent', customAccent);
        root.style.setProperty('--fin-blue', customAccent);
        root.style.setProperty('--accent', customAccent);
      }else if(preset){
        root.setAttribute('data-accent', preset);
        root.style.removeProperty('--c-accent');
        root.style.removeProperty('--fin-blue');
        root.style.removeProperty('--accent');
      }else{
        root.removeAttribute('data-accent');
        root.style.removeProperty('--c-accent');
        root.style.removeProperty('--fin-blue');
        root.style.removeProperty('--accent');
      }

      if(isHex(danger)) root.style.setProperty('--c-danger', danger);
      else root.style.removeProperty('--c-danger');

      if(isHex(success)) root.style.setProperty('--c-success', success);
      else root.style.removeProperty('--c-success');
    }

    function ensureFaviconLink(){
      let link = document.querySelector('link[rel="icon"]') || document.querySelector('link[rel="shortcut icon"]');
      if(!link){
        link = document.createElement('link');
        link.setAttribute('rel', 'icon');
        document.head.appendChild(link);
      }
      return link;
    }

    function applyBrand(brand){
      if(!brand || typeof brand !== 'object') return;
      const reportLogoSrc = String(
        brand.reportLogoDataUrl ||
        brand.logoDataUrl ||
        brand.faviconDataUrl ||
        ''
      ).trim();
      const fav = String(brand.faviconDataUrl || '').trim();
      const reportLogoEl = document.getElementById('frPreviewReportLogo');

      if(reportLogoEl && reportLogoSrc){
        try{ reportLogoEl.setAttribute('src', reportLogoSrc); }catch(_){}
      }

      if(fav){
        try{
          const link = ensureFaviconLink();
          link.setAttribute('href', fav);
        }catch(_){}

        document.querySelectorAll('img[data-brand="favicon"]').forEach((img) => {
          try{ img.setAttribute('src', fav); }catch(_){}
        });
      }
    }

    function init(){
      // fonte de verdade do preview: sempre light
      root.setAttribute('data-theme', 'light');
      root.classList.remove('theme-dark');
      root.classList.add('theme-light');

      const prefs = (payloadPrefs && typeof payloadPrefs === 'object') ? payloadPrefs : null;
      if(!prefs) return;
      applyAccent(prefs.theme || null);
      document.body.classList.toggle('fr-preview-compact', Boolean(prefs.preview?.compactMode));
      applyBrand(prefs.brand || null);
    }

    if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
  })();
  </script>
</body>
</html>
