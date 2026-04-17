<?php
declare(strict_types=1);

require_once __DIR__ . '/../../public_php/src/Repositories/LoteRepository.php';
require_once __DIR__ . '/../../public_php/src/Repositories/ArquivoRepository.php';
require_once __DIR__ . '/../../public_php/src/Support/helpers.php';
require_once __DIR__ . '/../modules/lotes/_public_helpers.php';

function lot_public_extract_sinistro(array $lote): string {
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

function lot_public_collect_gallery(array $itens, array $anexosProdutos, int $loteId, string $token): array {
  $gallery = [];
  foreach ($anexosProdutos as $arquivo) {
    if (!is_array($arquivo) || empty($arquivo['isImage'])) {
      continue;
    }
    $arquivoId = (int)($arquivo['id'] ?? 0);
    if ($arquivoId <= 0) {
      continue;
    }
    $gallery[$arquivoId] = [
      'id' => $arquivoId,
      'name' => trim((string)($arquivo['nomeOriginal'] ?? '')) !== '' ? (string)$arquivo['nomeOriginal'] : 'Imagem do lote',
      'url' => lot_public_asset_url($loteId, $token, $arquivoId),
    ];
  }

  foreach ($itens as $item) {
    if (!is_array($item)) {
      continue;
    }
    foreach ((array)($item['imagensItem'] ?? []) as $arquivo) {
      if (!is_array($arquivo) || empty($arquivo['isImage'])) {
        continue;
      }
      $arquivoId = (int)($arquivo['id'] ?? 0);
      if ($arquivoId <= 0 || isset($gallery[$arquivoId])) {
        continue;
      }
      $gallery[$arquivoId] = [
        'id' => $arquivoId,
        'name' => trim((string)($arquivo['nomeOriginal'] ?? '')) !== '' ? (string)$arquivo['nomeOriginal'] : 'Imagem do item',
        'url' => lot_public_asset_url($loteId, $token, $arquivoId),
      ];
    }
  }

  return array_values($gallery);
}

$loteId = (int)($_GET['lote'] ?? 0);
$token = trim((string)($_GET['token'] ?? ''));
$companyId = 1;

$repo = new LoteRepository();
$arquivoRepo = new ArquivoRepository();
$config = lot_public_fetch_config($loteId, $companyId);

if (!lot_public_is_enabled($config, $loteId, $token)) {
  http_response_code(404);
  echo 'Lote indisponível para visualização pública.';
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
$anexosProdutos = $arquivoRepo->listByEntity('lotes_produtos', $loteId, $companyId);
$gallery = lot_public_collect_gallery($itensDisponiveis, $anexosProdutos, $loteId, $token);
$cover = $gallery[0] ?? null;
$sinistro = lot_public_extract_sinistro($lote);
$descricao = trim((string)($lote['descricaoResumida'] ?? '')) !== '' ? trim((string)$lote['descricaoResumida']) : trim((string)($lote['descricaoOperacional'] ?? ''));
$tags = array_values(array_filter(array_map(static fn ($tag): string => trim((string)($tag['nome'] ?? $tag['slug'] ?? '')), (array)($lote['tags'] ?? []))));

$title = lot_public_text($lote['tituloLote'] ?? '', 'Lote disponível') . ' | Visa Remoções';
$bodyClass = 'lot-public-body';
$extra_css = [
  app_url('/app/static/css/financeiro.css'),
  app_url('/app/static/css/lotes.css'),
  app_url('/app/static/css/toast.css'),
  app_url('/app/static/css/ui_attachments.css'),
  app_url('/app/static/css/lotes_publico.css'),
];
$extra_js = [
  app_url('/app/static/js/ui_attachments.js'),
  app_url('/app/static/js/lotes_publico.js'),
];
$contentFile = __DIR__ . '/lote_publico_content.php';

require __DIR__ . '/base_public.php';
