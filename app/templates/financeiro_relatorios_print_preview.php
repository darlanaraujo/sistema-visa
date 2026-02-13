<?php
// app/templates/financeiro_relatorios_print_preview.php
// Preview de impressão do relatório (tela separada / janela nova).
// ⚠️ Esta página NÃO usa base_private.php, então precisa carregar helpers manualmente.

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Proteção (mesmo padrão do base_private.php)
if (!isset($_SESSION['auth_user'])) {
  header('Location: /sistema-visa/app/templates/login.php');
  exit;
}

// ---------------------------------------------------------
// Helpers globais (fonte única)
// - Necessário porque esta página usa h() e não passa pelo base_private.php
// ---------------------------------------------------------
require_once __DIR__ . '/../../public_php/src/Support/helpers.php';

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

$chart = is_array($data['chart'] ?? null) ? $data['chart'] : [];
$chartImg = (string)($chart['img'] ?? '');
$chartSum = is_array($chart['sum'] ?? null) ? $chart['sum'] : [];

$footnote = (string)($data['footnote'] ?? '');
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h($title) ?> • Preview</title>

  <!-- Favicon (usa fonte única; fallback mantém padrão) -->
  <link rel="icon" type="image/png" sizes="32x32" href="<?= h($corp['favicon'] ?? '/sistema-visa/app/static/img/favicon.png') ?>">
  <link rel="icon" type="image/png" sizes="16x16" href="<?= h($corp['favicon'] ?? '/sistema-visa/app/static/img/favicon.png') ?>">
  <link rel="apple-touch-icon" sizes="180x180" href="<?= h($corp['favicon'] ?? '/sistema-visa/app/static/img/favicon.png') ?>">

  <!-- Fonte -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <!-- CSS do módulo -->
  <link rel="stylesheet" href="/sistema-visa/app/static/css/theme.css">
  <link rel="stylesheet" href="/sistema-visa/app/static/css/global.css">

  <link rel="stylesheet" href="/sistema-visa/app/static/css/financeiro.css">
  <link rel="stylesheet" href="/sistema-visa/app/static/css/financeiro_relatorios.css">
  <link rel="stylesheet" href="/sistema-visa/app/static/css/financeiro_relatorios_print.css">
  <link rel="stylesheet" href="/sistema-visa/app/static/css/financeiro_relatorios_print_preview.css">
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
            class="fr-print__logo"
            src="<?= h($corp['report_logo'] ?? $corp['favicon'] ?? $corp['logo'] ?? '/sistema-visa/app/static/img/favicon.png') ?>"
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
          ?>
          <div class="fr-kpi">
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
                    ?>
                    <div class="fr-print__sumrow">
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
                    <?php $cls = alignClass($align, $i); ?>
                    <td class="<?= h($cls) ?>"><?= h($c) ?></td>
                  <?php endforeach; ?>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <div class="fr-print__footnote"><?= h($footnote) ?></div>
      </section>

      <footer class="fr-print__footer">
        <div><?= h($corp['report_footer_note'] ?? 'Documento gerado automaticamente pelo Sistema Visa.') ?></div>
        <div class="fr-print__pagenum">Página <span class="fr-page"></span></div>
      </footer>

    </div>
  </div>

  <!-- Personalização via localStorage (tema/cores/logo/favicon) -->
  <script src="/sistema-visa/app/static/js/system/sys_personalizacao.js"></script>
</body>
</html>