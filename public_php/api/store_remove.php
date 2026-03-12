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

  $stmt = api_pdo()->prepare(
    'DELETE FROM store
      WHERE company_id = :company_id
        AND store_key = :store_key'
  );

  $stmt->execute([
    ':company_id' => $companyId,
    ':store_key' => $storeKey,
  ]);

  api_success([
    'company_id' => $companyId,
    'store_key' => $storeKey,
    'removed' => $stmt->rowCount() > 0,
  ]);
} catch (Throwable $e) {
  api_error('Erro ao remover registro da store', 500);
}
