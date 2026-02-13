<?php
// app/api/company_save.php
// POST JSON: { action: "save", patch: {...} }  ou  { action: "reset" }

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['auth_user'])) {
  http_response_code(401);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['ok' => false, 'error' => 'unauthorized']);
  exit;
}

require_once __DIR__ . '/../core/company.php';

$raw = file_get_contents('php://input');
$payload = json_decode((string)$raw, true);
$payload = is_array($payload) ? $payload : [];

$action = (string)($payload['action'] ?? 'save');

header('Content-Type: application/json; charset=utf-8');

if ($action === 'reset') {
  $ok = company_reset_override();
  echo json_encode(['ok' => (bool)$ok, 'corp' => company_get()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}

// default: save
$patch = $payload['patch'] ?? [];
$patch = is_array($patch) ? $patch : [];

$corp = company_save_patch($patch);
echo json_encode(['ok' => true, 'corp' => $corp], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);