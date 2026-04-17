<?php
declare(strict_types=1);

require_once __DIR__ . '/../../public_php/src/Repositories/LoteRepository.php';
require_once __DIR__ . '/../modules/lotes/_public_helpers.php';
require_once __DIR__ . '/../core/company.php';

function lot_public_print_text(mixed $value, string $fallback = 'Não informado'): string {
  $text = trim((string)($value ?? ''));
  return $text !== '' ? $text : $fallback;
}

function lot_public_print_money(float $value): string {
  return 'R$ ' . number_format($value, 2, ',', '.');
}

function lot_public_print_qty(float $value): string {
  return number_format($value, 3, ',', '.');
}

function lot_public_print_control_label(string $tipo): string {
  return match ($tipo) {
    'kg' => 'Kg',
    'metros' => 'Metros',
    default => 'Und',
  };
}

function lot_public_print_sinistro(array $lote): string {
  $raw = trim((string)($lote['numeroSinistro'] ?? ''));
  if ($raw !== '') {
    return $raw;
  }
  $text = trim((string)($lote['observacoesGerais'] ?? ''));
  if ($text !== '' && preg_match('/(?:^|\R)\s*Sinistro:\s*(.+)$/imu', $text, $matches)) {
    return trim((string)($matches[1] ?? ''));
  }
  return '';
}

$loteId = (int)($_GET['lote'] ?? 0);
$token = trim((string)($_GET['token'] ?? ''));
$companyId = 1;
$repo = new LoteRepository();
$corp = company_get();
$config = lot_public_fetch_config($loteId, $companyId);

if (!lot_public_is_enabled($config, $loteId, $token)) {
  http_response_code(404);
  echo 'Lote indisponível para impressão pública.';
  exit;
}

$lote = $repo->findById($loteId, $companyId, true);
if (!is_array($lote)) {
  http_response_code(404);
  echo 'Lote não encontrado.';
  exit;
}

$itensDisponiveis = array_values(array_filter((array)($lote['itens'] ?? []), static function ($item): bool {
  return is_array($item) && (float)($item['quantidadeDisponivel'] ?? 0) > 0;
}));
$sinistro = lot_public_print_sinistro($lote);
?><!doctype html>
<html lang="pt-br" data-theme="light" class="theme-light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars(lot_public_print_text($lote['tituloLote'] ?? '', 'Lote disponível')) ?> | Lista pública</title>
  <link rel="icon" type="image/png" href="<?= htmlspecialchars($corp['favicon'] ?? app_url('/app/static/img/favicon.png')) ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="<?= htmlspecialchars(app_url('/app/static/css/theme.css')) ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(app_url('/app/static/css/global.css')) ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(app_url('/app/static/css/financeiro.css')) ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(app_url('/app/static/css/cadastros_print_preview.css')) ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(app_url('/app/static/css/lotes_publico.css')) ?>">
</head>
<body class="lot-public-print">
  <div class="cad-prevbar">
    <div>
      <div class="cad-prevbar__title">Impressão pública da lista do lote</div>
      <div class="cad-prevbar__hint">Documento comercial enxuto para compartilhamento dos itens disponíveis.</div>
    </div>
    <div class="cad-prevbar__actions">
      <button class="fin-btn" type="button" onclick="window.print()">
        <i class="fa-solid fa-print" aria-hidden="true"></i><span>Imprimir</span>
      </button>
    </div>
  </div>

  <main class="cad-print-page lot-public-print__sheet">
    <div class="lot-public-print__page">
      <article class="cad-print-doc">
        <header class="cad-print-doc__head">
          <div class="cad-print-head-main">
            <div class="cad-print-brand">
              <img
                class="cad-print-brand__logo"
                src="<?= htmlspecialchars($corp['report_logo'] ?? $corp['favicon'] ?? app_url('/app/static/img/favicon.png')) ?>"
                alt="<?= htmlspecialchars($corp['company'] ?? 'Visa Remoções') ?>"
              >
              <div>
                <div class="cad-print-brand__title"><?= htmlspecialchars($corp['company'] ?? 'Visa Remoções') ?></div>
                <div class="cad-print-brand__sub">Documento comercial público gerado automaticamente pelo Sistema Visa Remoções.</div>
              </div>
            </div>

            <div class="cad-print-meta">
              <div><span>Lote:</span> <strong><?= htmlspecialchars(lot_public_print_text($lote['tituloLote'] ?? '', 'Lote disponível')) ?></strong></div>
              <div><span>Processo:</span> <strong><?= htmlspecialchars(lot_public_print_text($lote['numeroProcesso'] ?? '', '-')) ?></strong></div>
              <div><span>Sinistro:</span> <strong><?= htmlspecialchars($sinistro !== '' ? $sinistro : 'Não informado') ?></strong></div>
              <div><span>Emitido em:</span> <strong><?= htmlspecialchars(date('d/m/Y H:i')) ?></strong></div>
            </div>
          </div>

          <div class="cad-print-report-title-row">
            <div class="cad-print-report-title">Lista pública de itens do lote</div>
          </div>
        </header>

        <section class="cad-print-section">
          <h2>Resumo</h2>
          <div class="cad-print-grid cad-print-grid--two">
            <div class="cad-print-kv">
              <span>Itens disponíveis</span>
              <strong><?= htmlspecialchars((string)count($itensDisponiveis)) ?></strong>
            </div>
            <div class="cad-print-kv">
              <span>Localidade</span>
              <strong><?= htmlspecialchars(trim((string)($lote['cidade'] ?? '') . (((string)($lote['estado'] ?? '')) !== '' ? ' / ' . (string)($lote['estado'] ?? '') : '')) !== '' ? trim((string)($lote['cidade'] ?? '') . (((string)($lote['estado'] ?? '')) !== '' ? ' / ' . (string)($lote['estado'] ?? '') : '')) : 'Não informado') ?></strong>
            </div>
          </div>
        </section>

        <section class="cad-print-section">
          <h2>Itens disponíveis</h2>
          <div class="cad-print-tablewrap">
            <table class="cad-print-table">
              <thead>
                <tr>
                  <th>Produto</th>
                  <th>Tipo</th>
                  <th>Quantidade disponível</th>
                  <th>Valor unitário</th>
                  <th>Total sugerido</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($itensDisponiveis === []): ?>
                  <tr>
                    <td colspan="5">Nenhum item disponível para venda neste lote no momento.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($itensDisponiveis as $item): ?>
                    <tr>
                      <td><?= htmlspecialchars(lot_public_print_text($item['descricaoItem'] ?? '', 'Item')) ?></td>
                      <td><?= htmlspecialchars(lot_public_print_control_label((string)($item['tipoControleItem'] ?? ''))) ?></td>
                      <td><?= htmlspecialchars(lot_public_print_qty((float)($item['quantidadeDisponivel'] ?? 0))) ?></td>
                      <td><?= htmlspecialchars(lot_public_print_money((float)($item['valorVendaUnitarioSugerido'] ?? 0))) ?></td>
                      <td><?= htmlspecialchars(lot_public_print_money((float)($item['valorVendaTotalSugerido'] ?? 0))) ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>

        <div class="cad-print-foot">Documento comercial público gerado pelo Sistema Visa Remoções. As disponibilidades desta lista refletem o estado atual do lote no momento da emissão.</div>
      </article>
    </div>
  </main>
</body>
</html>
