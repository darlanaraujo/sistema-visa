<?php
// public_php/api/company_save.php
// Salva um PATCH de dados da empresa (override) e retorna o resultado final.
// Regras: precisa estar logado.

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['auth_user'])) {
  http_response_code(401);
  echo json_encode(['ok' => false, 'error' => 'UNAUTHENTICATED'], JSON_UNESCAPED_UNICODE);
  exit;
}

require_once __DIR__ . '/../../app/core/company.php';

// Lê JSON do body
$raw = file_get_contents('php://input');
$payload = json_decode($raw ?: '', true);

if (!is_array($payload)) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'INVALID_JSON'], JSON_UNESCAPED_UNICODE);
  exit;
}

try {
  // PATCH permitido: somente chaves que existem em company_defaults()
  $corp = company_save_patch($payload);

  // Se não estiver gravando por permissão, isso vai “parecer” OK,
  // mas não persistirá. Então fazemos uma checagem prática:
  // lê de novo e compara uma chave do patch.
  $after = company_get();

  $checkKey = null;
  foreach ($payload as $k => $v) { $checkKey = $k; break; }

  if ($checkKey && array_key_exists($checkKey, $after) && is_string($payload[$checkKey])) {
    $wanted = trim((string)$payload[$checkKey]);
    $got    = trim((string)$after[$checkKey]);

    // Se o usuário enviou algo diferente e não refletiu, indica falha de persistência.
    if ($wanted !== '' && $wanted !== $got) {
      http_response_code(500);
      echo json_encode([
        'ok' => false,
        'error' => 'PERSIST_FAILED',
        'hint' => 'Verifique permissões de escrita em /app/storage (company_override.json).'
      ], JSON_UNESCAPED_UNICODE);
      exit;
    }
  }

  echo json_encode(['ok' => true, 'data' => $corp], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'SERVER_ERROR'], JSON_UNESCAPED_UNICODE);
}