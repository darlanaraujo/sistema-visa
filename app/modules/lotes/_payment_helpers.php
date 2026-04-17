<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../public_php/src/Support/Database.php';

if (!function_exists('lot_purchase_payment_store_key')) {
  function lot_purchase_payment_store_key(int $loteId): string {
    return 'lote_pagamento_compra_v1:' . max(0, $loteId);
  }
}

if (!function_exists('lot_purchase_payment_normalize_status')) {
  function lot_purchase_payment_normalize_status(string $status, string $fallback = 'pendente'): string {
    $status = strtolower(trim($status));
    return in_array($status, ['pendente', 'pago'], true) ? $status : $fallback;
  }
}

if (!function_exists('lot_purchase_payment_fetch_config')) {
  function lot_purchase_payment_fetch_config(int $loteId, int $companyId = 1): array {
    if ($loteId <= 0) {
      return [
        'status' => 'pendente',
        'paidAt' => '',
        'updatedAt' => '',
      ];
    }

    $stmt = Database::connection()->prepare(
      'SELECT value_json, updated_at
         FROM store
        WHERE company_id = :company_id
          AND store_key = :store_key
        LIMIT 1'
    );
    $stmt->execute([
      ':company_id' => $companyId,
      ':store_key' => lot_purchase_payment_store_key($loteId),
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $decoded = [];
    if (is_array($row) && isset($row['value_json'])) {
      $candidate = json_decode((string)$row['value_json'], true);
      $decoded = is_array($candidate) ? $candidate : [];
    }

    return [
      'status' => lot_purchase_payment_normalize_status((string)($decoded['status'] ?? 'pendente')),
      'paidAt' => trim((string)($decoded['paidAt'] ?? '')),
      'updatedAt' => trim((string)($row['updated_at'] ?? '')),
    ];
  }
}

if (!function_exists('lot_purchase_payment_fetch_map')) {
  function lot_purchase_payment_fetch_map(array $loteIds, int $companyId = 1): array {
    $loteIds = array_values(array_unique(array_filter(array_map('intval', $loteIds), static fn (int $id): bool => $id > 0)));
    if ($loteIds === []) {
      return [];
    }

    $map = [];
    foreach ($loteIds as $loteId) {
      $map[$loteId] = [
        'status' => 'pendente',
        'paidAt' => '',
        'updatedAt' => '',
      ];
    }

    $placeholders = [];
    $params = [':company_id' => $companyId];
    foreach ($loteIds as $index => $loteId) {
      $keyParam = ':store_key_' . $index;
      $placeholders[] = $keyParam;
      $params[$keyParam] = lot_purchase_payment_store_key($loteId);
    }

    $stmt = Database::connection()->prepare(
      sprintf(
        'SELECT store_key, value_json, updated_at
           FROM store
          WHERE company_id = :company_id
            AND store_key IN (%s)',
        implode(', ', $placeholders)
      )
    );
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
      if (!is_array($row)) {
        continue;
      }
      $storeKey = (string)($row['store_key'] ?? '');
      if (!preg_match('/:(\d+)$/', $storeKey, $matches)) {
        continue;
      }
      $loteId = (int)($matches[1] ?? 0);
      if ($loteId <= 0) {
        continue;
      }
      $decoded = [];
      if (isset($row['value_json'])) {
        $candidate = json_decode((string)$row['value_json'], true);
        $decoded = is_array($candidate) ? $candidate : [];
      }
      $map[$loteId] = [
        'status' => lot_purchase_payment_normalize_status((string)($decoded['status'] ?? 'pendente')),
        'paidAt' => trim((string)($decoded['paidAt'] ?? '')),
        'updatedAt' => trim((string)($row['updated_at'] ?? '')),
      ];
    }

    return $map;
  }
}

if (!function_exists('lot_purchase_payment_save_config')) {
  function lot_purchase_payment_save_config(int $loteId, array $config, int $companyId = 1): array {
    if ($loteId <= 0) {
      throw new InvalidArgumentException('Lote inválido para atualizar pagamento.');
    }

    $current = lot_purchase_payment_fetch_config($loteId, $companyId);
    $status = lot_purchase_payment_normalize_status((string)($config['status'] ?? $current['status'] ?? 'pendente'));
    $paidAt = trim((string)($config['paidAt'] ?? $current['paidAt'] ?? ''));

    if ($status === 'pago' && $paidAt === '') {
      $paidAt = date('Y-m-d');
    }
    if ($status !== 'pago') {
      $paidAt = '';
    }

    $payload = [
      'status' => $status,
      'paidAt' => $paidAt,
    ];
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $stmt = Database::connection()->prepare(
      'INSERT INTO store (company_id, store_key, value_json)
       VALUES (:company_id, :store_key, :value_json)
       ON DUPLICATE KEY UPDATE value_json = :value_json_update, updated_at = CURRENT_TIMESTAMP'
    );
    $stmt->execute([
      ':company_id' => $companyId,
      ':store_key' => lot_purchase_payment_store_key($loteId),
      ':value_json' => $json,
      ':value_json_update' => $json,
    ]);

    return lot_purchase_payment_fetch_config($loteId, $companyId);
  }
}

if (!function_exists('lot_purchase_payment_label')) {
  function lot_purchase_payment_label(string $status): string {
    return lot_purchase_payment_normalize_status($status) === 'pago' ? 'Compra paga' : 'Pagamento pendente';
  }
}

if (!function_exists('lot_purchase_payment_open_amount')) {
  function lot_purchase_payment_open_amount(array $lote, array $config): float {
    if (lot_purchase_payment_normalize_status((string)($config['status'] ?? 'pendente')) === 'pago') {
      return 0.0;
    }
    return max(0.0, (float)($lote['valorPagoCompra'] ?? 0));
  }
}
