<?php
declare(strict_types=1);

require_once __DIR__ . '/../../public_php/src/Repositories/LoteRepository.php';
require_once __DIR__ . '/../../public_php/src/Repositories/ArquivoRepository.php';
require_once __DIR__ . '/../../public_php/src/Support/helpers.php';
require_once __DIR__ . '/../modules/lotes/_public_helpers.php';

$loteId = (int)($_GET['lote'] ?? 0);
$arquivoId = (int)($_GET['id'] ?? 0);
$token = trim((string)($_GET['token'] ?? ''));
$download = trim((string)($_GET['download'] ?? '')) === '1';
$companyId = 1;

$config = lot_public_fetch_config($loteId, $companyId);
if (!lot_public_is_enabled($config, $loteId, $token)) {
  http_response_code(404);
  echo 'Arquivo indisponível.';
  exit;
}

$repo = new LoteRepository();
$arquivoRepo = new ArquivoRepository();
$lote = $repo->findById($loteId, $companyId, true);
$arquivo = $arquivoRepo->findById($arquivoId, $companyId);

if (!is_array($lote) || !is_array($arquivo) || empty($arquivo['isImage'])) {
  http_response_code(404);
  echo 'Arquivo indisponível.';
  exit;
}

$allowedIds = [];
foreach ($arquivoRepo->listByEntity('lotes_produtos', $loteId, $companyId) as $item) {
  if (is_array($item) && !empty($item['isImage'])) {
    $allowedIds[] = (int)($item['id'] ?? 0);
  }
}
foreach ((array)($lote['itens'] ?? []) as $item) {
  if (!is_array($item)) {
    continue;
  }
  foreach ((array)($item['imagensItem'] ?? []) as $imagem) {
    if (is_array($imagem) && !empty($imagem['isImage'])) {
      $allowedIds[] = (int)($imagem['id'] ?? 0);
    }
  }
}

if (!in_array($arquivoId, array_values(array_unique(array_filter($allowedIds))), true)) {
  http_response_code(404);
  echo 'Arquivo indisponível.';
  exit;
}

$absolutePath = dirname(__DIR__, 2) . '/app/storage/' . ltrim((string)($arquivo['caminho'] ?? ''), '/');
if (!is_file($absolutePath)) {
  http_response_code(404);
  echo 'Arquivo indisponível no armazenamento.';
  exit;
}

$fileName = (string)($arquivo['nomeOriginal'] ?? 'arquivo');
$mimeType = (string)($arquivo['mimeType'] ?? 'application/octet-stream');

header('Content-Type: ' . $mimeType);
header('Content-Length: ' . (string)filesize($absolutePath));
header('X-Content-Type-Options: nosniff');
header('Content-Disposition: ' . ($download ? 'attachment' : 'inline') . '; filename="' . rawurlencode($fileName) . '"');
readfile($absolutePath);
exit;
