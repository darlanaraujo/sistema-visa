<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/Support/Request.php';
require_once __DIR__ . '/../src/Support/Response.php';
require_once __DIR__ . '/../src/Support/Session.php';
require_once __DIR__ . '/../src/Support/Database.php';
require_once __DIR__ . '/../src/Middlewares/AuthMiddleware.php';

AuthMiddleware::ensureAuthenticated();

function api_success(mixed $data = null, int $status = 200): void {
  Response::json($status, [
    'success' => true,
    'data' => $data,
    'error' => null,
  ]);
}

function api_error(string $message, int $status = 400): void {
  Response::json($status, [
    'success' => false,
    'data' => null,
    'error' => $message,
  ]);
}

function api_request_data(): array {
  $body = Request::jsonBody();
  return is_array($body) ? $body : [];
}

function api_company_id(array $data, int $default = 1): int {
  $raw = $data['company_id'] ?? $_GET['company_id'] ?? $default;
  $companyId = (int)$raw;
  return $companyId > 0 ? $companyId : $default;
}

function api_store_key(array $data): string {
  $raw = $data['store_key'] ?? $data['key'] ?? $_GET['store_key'] ?? $_GET['key'] ?? '';
  return trim((string)$raw);
}

function api_store_value_json(array $data): string {
  $value = array_key_exists('value_json', $data) ? $data['value_json'] : ($data['value'] ?? null);

  if (is_string($value)) {
    $decoded = json_decode($value, true);
    if (json_last_error() === JSON_ERROR_NONE) {
      return json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: 'null';
    }
  }

  return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: 'null';
}

function api_pdo(): PDO {
  return Database::connection();
}
