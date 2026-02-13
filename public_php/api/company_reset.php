<?php
// public_php/api/company_reset.php
// Remove override e volta aos defaults.
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

try {
  $ok = company_reset_override();
  $corp = company_get();

  echo json_encode([
    'ok' => (bool)$ok,
    'data' => $corp
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'SERVER_ERROR'], JSON_UNESCAPED_UNICODE);
}