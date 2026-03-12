<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap_api.php';

try {
  if (Request::method() !== 'POST') {
    api_error('Método não permitido', 405);
  }

  $data = api_request_data();
  $storeKey = api_store_key($data);
  $companyId = api_company_id($data);

  if ($storeKey === '') {
    api_error('store_key é obrigatório', 422);
  }

  if (!array_key_exists('value_json', $data) && !array_key_exists('value', $data)) {
    api_error('value_json é obrigatório', 422);
  }

  $valueJson = api_store_value_json($data);

  $stmt = api_pdo()->prepare(
    'INSERT INTO store (company_id, store_key, value_json)
     VALUES (:company_id, :store_key, :value_json)
     ON DUPLICATE KEY UPDATE
       value_json = :value_json_update,
       updated_at = CURRENT_TIMESTAMP'
  );

  $stmt->execute([
    ':company_id' => $companyId,
    ':store_key' => $storeKey,
    ':value_json' => $valueJson,
    ':value_json_update' => $valueJson,
  ]);

  api_success([
    'company_id' => $companyId,
    'store_key' => $storeKey,
  ]);
} catch (Throwable $e) {
  api_error('Erro ao salvar registro na store', 500);
}
