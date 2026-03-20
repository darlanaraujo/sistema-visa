<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/url.php';
require_once __DIR__ . '/../../public_php/src/Support/helpers.php';
require_once __DIR__ . '/../../public_php/src/Repositories/ArquivoRepository.php';

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (!isset($_SESSION['auth_user'])) {
  http_response_code(401);
  header('Content-Type: text/plain; charset=utf-8');
  echo 'Não autorizado.';
  exit;
}

$arquivoId = (int)($_GET['id'] ?? 0);
$download = trim((string)($_GET['download'] ?? '')) === '1';
$companyId = 1;

$repo = new ArquivoRepository();
$arquivo = $repo->findById($arquivoId, $companyId);

if (!is_array($arquivo) || $arquivo === []) {
  http_response_code(404);
  header('Content-Type: text/plain; charset=utf-8');
  echo 'Arquivo não encontrado.';
  exit;
}

$absolutePath = dirname(__DIR__, 2) . '/app/storage/' . ltrim((string)($arquivo['caminho'] ?? ''), '/');
if (!is_file($absolutePath)) {
  http_response_code(404);
  header('Content-Type: text/plain; charset=utf-8');
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
