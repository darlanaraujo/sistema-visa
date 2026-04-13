<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (!isset($_SESSION['auth_user'])) {
  require_once __DIR__ . '/../core/url.php';
  header('Location: ' . app_url('/app/templates/login.php'));
  exit;
}

require_once __DIR__ . '/../../public_php/src/Support/helpers.php';
require_once __DIR__ . '/../core/url.php';
require_once __DIR__ . '/../core/company.php';

const LOT_PRINT_SESSION_KEY = 'lot_print_preview_payloads';

function lot_print_text(mixed $value, string $fallback = 'Não informado'): string {
  $text = trim((string)($value ?? ''));
  return $text !== '' ? $text : $fallback;
}

$data = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $raw = $_POST['payload'] ?? '';
  if (is_string($raw) && $raw !== '') {
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
      $token = bin2hex(random_bytes(16));
      $stored = is_array($_SESSION[LOT_PRINT_SESSION_KEY] ?? null) ? $_SESSION[LOT_PRINT_SESSION_KEY] : [];
      $stored[$token] = [
        'payload' => $decoded,
        'stored_at' => time(),
      ];

      if (count($stored) > 12) {
        uasort($stored, static function (array $a, array $b): int {
          return (int)($a['stored_at'] ?? 0) <=> (int)($b['stored_at'] ?? 0);
        });
        while (count($stored) > 12) {
          array_shift($stored);
        }
      }

      $_SESSION[LOT_PRINT_SESSION_KEY] = $stored;

      header('Location: ' . app_url('/app/templates/lotes_print_preview.php?preview=' . urlencode($token)));
      exit;
    }
  }
}

$previewToken = trim((string)($_GET['preview'] ?? ''));
if ($previewToken !== '') {
  $stored = is_array($_SESSION[LOT_PRINT_SESSION_KEY] ?? null) ? $_SESSION[LOT_PRINT_SESSION_KEY] : [];
  $entry = $stored[$previewToken] ?? null;
  if (is_array($entry) && is_array($entry['payload'] ?? null)) {
    $data = $entry['payload'];
  }
}

if (!$data) {
  http_response_code(400);
  echo '<p style="font-family:Inter,system-ui; padding:16px;">Payload inválido.</p>';
  exit;
}

$corp = company_get();
$title = lot_print_text($data['title'] ?? '', 'Relatório');
$metaTitle = lot_print_text($data['metaTitle'] ?? '', $title);
$metaHint = lot_print_text($data['metaHint'] ?? '', 'Para salvar: Cmd+P (Mac) / Ctrl+P (Windows) → Destino: Salvar como PDF');
$brandSub = lot_print_text($data['brandSub'] ?? '', 'Documento gerado automaticamente pelo Sistema Visa Remoções.');
$reportTitle = lot_print_text($data['reportTitle'] ?? '', $title);
$chartTitle = lot_print_text($data['chartTitle'] ?? '', 'Gráfico');
$chartImage = trim((string)($data['chartImage'] ?? ''));
$chartType = lot_print_text($data['chartType'] ?? '', 'generic');
$chartSummaryRows = is_array($data['chartSummaryRows'] ?? null) ? $data['chartSummaryRows'] : [];
$metaRows = is_array($data['metaRows'] ?? null) ? $data['metaRows'] : [];
$summaryTitle = lot_print_text($data['summaryTitle'] ?? '', 'Resumo');
$summary = is_array($data['summary'] ?? null) ? $data['summary'] : [];
$sections = is_array($data['sections'] ?? null) ? $data['sections'] : [];
$sectionTitle = lot_print_text($data['sectionTitle'] ?? '', 'Dados');
$table = is_array($data['table'] ?? null) ? $data['table'] : [];
$tableHead = is_array($table['head'] ?? null) ? $table['head'] : [];
$tableRows = is_array($table['rows'] ?? null) ? $table['rows'] : [];
$tableTotal = is_array($table['total'] ?? null) ? $table['total'] : null;
$footnote = lot_print_text($data['footnote'] ?? '', 'Documento gerado automaticamente pelo Sistema Visa Remoções.');
?>
<!doctype html>
<html lang="pt-br" data-theme="light" class="theme-light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h($title) ?> • Preview</title>

  <link rel="icon" type="image/png" sizes="32x32" href="<?= h($corp['favicon'] ?? app_url('/app/static/img/favicon.png')) ?>">
  <link rel="icon" type="image/png" sizes="16x16" href="<?= h($corp['favicon'] ?? app_url('/app/static/img/favicon.png')) ?>">
  <link rel="apple-touch-icon" sizes="180x180" href="<?= h($corp['favicon'] ?? app_url('/app/static/img/favicon.png')) ?>">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="<?= h(app_url('/app/static/css/theme.css')) ?>">
  <link rel="stylesheet" href="<?= h(app_url('/app/static/css/global.css')) ?>">
  <link rel="stylesheet" href="<?= h(app_url('/app/static/css/financeiro.css')) ?>">
  <link rel="stylesheet" href="<?= h(app_url('/app/static/css/cadastros_print_preview.css')) ?>">
  <style>
    .lot-print-chartwrap{
      border:1px solid #d8dee9;
      border-radius:12px;
      overflow:hidden;
      background:#fff;
      padding:14px;
      display:flex;
      justify-content:center;
      align-items:center;
    }

    .lot-print-chartwrap img{
      width:100%;
      max-width:780px;
      height:auto;
      display:block;
    }

    .lot-print-chartwrap--donut img{
      max-width:420px;
    }

    .lot-print-chart-layout{
      display:grid;
      grid-template-columns:minmax(320px, .9fr) minmax(260px, 1.1fr);
      gap:16px;
      align-items:start;
    }

    .lot-print-chart-side{
      border:1px solid #d8dee9;
      border-radius:12px;
      overflow:hidden;
      background:#fff;
    }

    .lot-print-chart-side table{
      width:100%;
      border-collapse:collapse;
      table-layout:fixed;
      font-size:11px;
    }

    .lot-print-chart-side th,
    .lot-print-chart-side td{
      padding:8px 10px;
      border-bottom:1px solid #d5dbe5;
      border-right:1px solid #e1e6ee;
      text-align:left;
      vertical-align:top;
    }

    .lot-print-chart-side th:last-child,
    .lot-print-chart-side td:last-child{
      border-right:none;
      text-align:right;
    }

    .lot-print-chart-side th{
      background:#eef4ff;
      color:#28456b;
      font-size:10px;
      text-transform:uppercase;
      letter-spacing:.04em;
      font-weight:900;
    }

    .lot-print-chart-side tbody tr:last-child td{
      border-bottom:none;
    }

    .lot-print-chart-side td:first-child{
      font-weight:800;
      color:#0f172a;
    }

    .cad-print-table{
      table-layout:fixed;
    }

    .cad-print-tablewrap{
      overflow:hidden;
    }

    .cad-print-table th,
    .cad-print-table td{
      padding:9px 10px;
      border-bottom:1px solid #d5dbe5;
      border-right:1px solid #e1e6ee;
    }

    .cad-print-table th:last-child,
    .cad-print-table td:last-child{
      border-right:none;
    }

    .cad-print-table th{
      background:#eef4ff;
      color:#28456b;
      font-weight:900;
    }

    .cad-print-table td:last-child,
    .cad-print-table th:last-child{
      text-align:right;
    }

    .cad-print-table tfoot td{
      background:#e7eefb;
      color:#233b63;
      font-weight:900;
      border-top:2px solid #b8caea;
      border-bottom:none;
      border-right:none;
      width:auto;
      white-space:normal;
    }

    .cad-print-table tfoot td:first-child{
      text-align:left;
    }

    .cad-print-table tfoot td:last-child{
      text-align:right;
      white-space:nowrap;
    }

    .cad-print-table tbody tr td strong{
      font-weight:900;
      color:#0f172a;
    }

    .cad-print-table tbody tr.is-conclusion td{
      background:#e7eefb;
      color:#233b63;
      font-weight:900;
      border-top:2px solid #b8caea;
    }
  </style>
</head>
<body>
  <div class="cad-prevbar" role="region" aria-label="Ações do preview">
    <div class="cad-prevbar__left">
      <div class="cad-prevbar__title"><?= h($metaTitle) ?></div>
      <div class="cad-prevbar__hint"><?= h($metaHint) ?></div>
    </div>

    <div class="cad-prevbar__actions">
      <button type="button" class="fin-btn fin-btn--ghost" onclick="window.close()">
        <i class="fa-solid fa-xmark"></i><span>Fechar</span>
      </button>
      <button type="button" class="fin-btn" onclick="window.print()">
        <i class="fa-solid fa-print"></i><span>Imprimir / Salvar PDF</span>
      </button>
    </div>
  </div>

  <div class="cad-print-page">
    <article class="cad-print-doc">
      <header class="cad-print-doc__head">
        <div class="cad-print-head-main">
          <div class="cad-print-brand">
            <img
              class="cad-print-brand__logo"
              src="<?= h($corp['report_logo'] ?? $corp['favicon'] ?? app_url('/app/static/img/favicon.png')) ?>"
              alt="<?= h($corp['company'] ?? 'Visa Remoções') ?>"
            >
            <div>
              <div class="cad-print-brand__title"><?= h($corp['company'] ?? 'Visa Remoções') ?></div>
              <div class="cad-print-brand__sub"><?= h($brandSub) ?></div>
            </div>
          </div>

          <div class="cad-print-meta">
            <?php foreach ($metaRows as $row): ?>
              <?php if (!is_array($row)) { continue; } ?>
              <div><span><?= h(lot_print_text($row['label'] ?? '', 'Campo')) ?>:</span> <strong><?= h(lot_print_text($row['value'] ?? '')) ?></strong></div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="cad-print-report-title-row">
          <div class="cad-print-report-title"><?= h($reportTitle) ?></div>
        </div>
      </header>

      <?php if ($summary !== []): ?>
        <section class="cad-print-section">
          <h2><?= h($summaryTitle) ?></h2>
          <div class="cad-print-grid cad-print-grid--two">
            <?php foreach ($summary as $item): ?>
              <?php if (!is_array($item)) { continue; } ?>
              <div class="cad-print-kv">
                <span><?= h(lot_print_text($item['label'] ?? '', 'Resumo')) ?></span>
                <strong><?= h(lot_print_text($item['value'] ?? '')) ?></strong>
              </div>
            <?php endforeach; ?>
          </div>
        </section>
      <?php endif; ?>

      <?php if ($chartImage !== ''): ?>
        <section class="cad-print-section cad-print-section--compact">
          <h2><?= h($chartTitle) ?></h2>
          <div class="<?= $chartType === 'donut' && $chartSummaryRows !== [] ? 'lot-print-chart-layout' : '' ?>">
            <div class="lot-print-chartwrap <?= $chartType === 'donut' ? 'lot-print-chartwrap--donut' : '' ?>">
              <img src="<?= h($chartImage) ?>" alt="<?= h($chartTitle) ?>">
            </div>
            <?php if ($chartType === 'donut' && $chartSummaryRows !== []): ?>
              <div class="lot-print-chart-side">
                <table>
                  <thead>
                    <tr>
                      <th>Legenda</th>
                      <th>%</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($chartSummaryRows as $row): ?>
                      <?php if (!is_array($row)) { continue; } ?>
                      <tr>
                        <td><?= h(lot_print_text($row['label'] ?? '', 'Item')) ?></td>
                        <td><?= h(lot_print_text($row['percent'] ?? '')) ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>
        </section>
      <?php endif; ?>

      <?php if ($sections !== []): ?>
        <?php foreach ($sections as $section): ?>
          <?php if (!is_array($section)) { continue; } ?>
          <?php
            $items = is_array($section['items'] ?? null) ? $section['items'] : [];
            $sectionTable = is_array($section['table'] ?? null) ? $section['table'] : [];
            $sectionTableHead = is_array($sectionTable['head'] ?? null) ? $sectionTable['head'] : [];
            $sectionTableRows = is_array($sectionTable['rows'] ?? null) ? $sectionTable['rows'] : [];
            $sectionTableTotal = is_array($sectionTable['total'] ?? null) ? $sectionTable['total'] : null;
          ?>
          <section class="cad-print-section">
            <h2><?= h(lot_print_text($section['title'] ?? '', 'Seção')) ?></h2>
            <?php if ($sectionTable !== []): ?>
              <div class="cad-print-tablewrap">
                <table class="cad-print-table">
                  <?php if ($sectionTableHead !== []): ?>
                    <thead>
                      <tr>
                        <?php foreach ($sectionTableHead as $head): ?>
                          <th><?= h((string)$head) ?></th>
                        <?php endforeach; ?>
                      </tr>
                    </thead>
                  <?php endif; ?>
                  <tbody>
                    <?php if ($sectionTableRows === []): ?>
                      <tr>
                        <td colspan="<?= h((string)max(1, count($sectionTableHead))) ?>">Nenhum registro encontrado.</td>
                      </tr>
                    <?php else: ?>
                      <?php foreach ($sectionTableRows as $row): ?>
                        <?php if (!is_array($row)) { continue; } ?>
                        <tr>
                          <?php foreach ($row as $cell): ?>
                            <td><?= h((string)$cell) ?></td>
                          <?php endforeach; ?>
                        </tr>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </tbody>
                  <?php if ($sectionTableTotal !== null): ?>
                    <tfoot>
                      <tr>
                        <td colspan="<?= h((string)max(1, (int)($sectionTableTotal['colspan'] ?? max(1, count($sectionTableHead) - 1)))) ?>"><?= h(lot_print_text($sectionTableTotal['label'] ?? '', 'Total')) ?></td>
                        <td><?= h(lot_print_text($sectionTableTotal['value'] ?? '')) ?></td>
                      </tr>
                    </tfoot>
                  <?php endif; ?>
                </table>
              </div>
            <?php else: ?>
              <div class="cad-print-tablewrap">
                <table class="cad-print-table">
                  <thead>
                    <tr>
                      <th>Campo</th>
                      <th>Valor</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if ($items === []): ?>
                      <tr>
                        <td colspan="2">Nenhum registro encontrado.</td>
                      </tr>
                    <?php else: ?>
                      <?php foreach ($items as $item): ?>
                        <?php if (!is_array($item)) { continue; } ?>
                        <tr class="<?= !empty($item['highlight']) ? 'is-conclusion' : '' ?>">
                          <td><?= h(lot_print_text($item['label'] ?? '', 'Campo')) ?></td>
                          <td><?= h(lot_print_text($item['value'] ?? '')) ?></td>
                        </tr>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </section>
        <?php endforeach; ?>
      <?php else: ?>
        <section class="cad-print-section">
          <h2><?= h($sectionTitle) ?></h2>
          <div class="cad-print-tablewrap">
            <table class="cad-print-table">
              <?php if ($tableHead !== []): ?>
                <thead>
                  <tr>
                    <?php foreach ($tableHead as $head): ?>
                      <th><?= h((string)$head) ?></th>
                    <?php endforeach; ?>
                  </tr>
                </thead>
              <?php endif; ?>
              <tbody>
                <?php if ($tableRows === []): ?>
                  <tr>
                    <td colspan="<?= h((string)max(1, count($tableHead))) ?>">Nenhum registro encontrado.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($tableRows as $row): ?>
                    <?php if (!is_array($row)) { continue; } ?>
                    <tr>
                    <?php foreach ($row as $cell): ?>
                        <td><?= h((string)$cell) ?></td>
                      <?php endforeach; ?>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
              <?php if ($tableTotal !== null): ?>
                <tfoot>
                  <tr>
                    <td colspan="<?= h((string)max(1, (int)($tableTotal['colspan'] ?? max(1, count($tableHead) - 1)))) ?>"><?= h(lot_print_text($tableTotal['label'] ?? '', 'Total')) ?></td>
                    <td><?= h(lot_print_text($tableTotal['value'] ?? '')) ?></td>
                  </tr>
                </tfoot>
              <?php endif; ?>
            </table>
          </div>
        </section>
      <?php endif; ?>

      <div class="cad-print-foot"><?= h($footnote) ?></div>
    </article>
  </div>
</body>
</html>
