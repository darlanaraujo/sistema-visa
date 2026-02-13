<?php
// app/api/company_get.php
// Retorna dados corporativos finais (defaults + override).

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['auth_user'])) {
  http_response_code(401);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['ok' => false, 'error' => 'unauthorized']);
  exit;
}

require_once __DIR__ . '/../core/company.php';

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['ok' => true, 'corp' => company_get()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);