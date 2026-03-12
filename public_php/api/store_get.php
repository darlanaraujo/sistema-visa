<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap_api.php';

try {
  if (Request::method() !== 'GET') {
    api_error('Método não permitido', 405);
  }

  $data = api_request_data();
  $storeKey = api_store_key($data);
  $companyId = api_company_id($data);

  if ($storeKey === '') {
    api_error('store_key é obrigatório', 422);
  }

  $stmt = api_pdo()->prepare(
    'SELECT value_json
       FROM store
      WHERE company_id = :company_id
        AND store_key = :store_key
      LIMIT 1'
  );

  $stmt->execute([
    ':company_id' => $companyId,
    ':store_key' => $storeKey,
  ]);

  $row = $stmt->fetch();
  if (!$row) {
    api_success(null);
  }

  $json = (string)($row['value_json'] ?? 'null');
  $decoded = json_decode($json, true);
  api_success(json_last_error() === JSON_ERROR_NONE ? $decoded : null);
} catch (Throwable $e) {
  api_error('Erro ao buscar registro na store', 500);
}
