<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../public_php/src/Support/Database.php';
require_once __DIR__ . '/../../core/url.php';

if (!function_exists('lot_public_text')) {
  function lot_public_text(mixed $value, string $fallback = 'Não informado'): string {
    $text = trim((string)($value ?? ''));
    return $text !== '' ? $text : $fallback;
  }
}

if (!function_exists('lot_public_money')) {
  function lot_public_money(float $value): string {
    return 'R$ ' . number_format($value, 2, ',', '.');
  }
}

if (!function_exists('lot_public_qty')) {
  function lot_public_qty(float $value): string {
    return number_format($value, 3, ',', '.');
  }
}

if (!function_exists('lot_public_control_label')) {
  function lot_public_control_label(string $tipo): string {
    return match ($tipo) {
      'kg' => 'Kg',
      'metros' => 'Metros',
      default => 'Und',
    };
  }
}

if (!function_exists('lot_public_available_items_label')) {
  function lot_public_available_items_label(int $count): string {
    return $count === 1 ? '1 item disponível' : number_format($count, 0, ',', '.') . ' itens disponíveis';
  }
}

if (!function_exists('lot_public_store_key')) {
  function lot_public_store_key(int $loteId): string {
    return 'lote_publico_v1:' . max(0, $loteId);
  }
}

if (!function_exists('lot_public_generate_token')) {
  function lot_public_generate_token(): string {
    return bin2hex(random_bytes(16));
  }
}

if (!function_exists('lot_public_fetch_config')) {
  function lot_public_fetch_config(int $loteId, int $companyId = 1): array {
    if ($loteId <= 0) {
      return [
        'published' => false,
        'token' => '',
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
      ':store_key' => lot_public_store_key($loteId),
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $decoded = [];
    if (is_array($row) && isset($row['value_json'])) {
      $candidate = json_decode((string)$row['value_json'], true);
      $decoded = is_array($candidate) ? $candidate : [];
    }

    return [
      'published' => !empty($decoded['published']),
      'token' => trim((string)($decoded['token'] ?? '')),
      'updatedAt' => trim((string)($row['updated_at'] ?? ($decoded['updatedAt'] ?? ''))),
    ];
  }
}

if (!function_exists('lot_public_save_config')) {
  function lot_public_save_config(int $loteId, array $config, int $companyId = 1): array {
    if ($loteId <= 0) {
      throw new InvalidArgumentException('Lote inválido para publicação.');
    }

    $current = lot_public_fetch_config($loteId, $companyId);
    $published = array_key_exists('published', $config) ? !empty($config['published']) : (bool)$current['published'];
    $token = trim((string)($config['token'] ?? $current['token'] ?? ''));
    if ($published && $token === '') {
      $token = lot_public_generate_token();
    }

    $payload = [
      'published' => $published,
      'token' => $token,
    ];
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $stmt = Database::connection()->prepare(
      'INSERT INTO store (company_id, store_key, value_json)
       VALUES (:company_id, :store_key, :value_json)
       ON DUPLICATE KEY UPDATE value_json = :value_json_update, updated_at = CURRENT_TIMESTAMP'
    );
    $stmt->execute([
      ':company_id' => $companyId,
      ':store_key' => lot_public_store_key($loteId),
      ':value_json' => $json,
      ':value_json_update' => $json,
    ]);

    return lot_public_fetch_config($loteId, $companyId);
  }
}

if (!function_exists('lot_public_url')) {
  function lot_public_url(int $loteId, string $token): string {
    return app_url('/app/templates/lote_publico.php?' . http_build_query([
      'lote' => $loteId,
      'token' => $token,
    ]));
  }
}

if (!function_exists('lot_public_print_url')) {
  function lot_public_print_url(int $loteId, string $token): string {
    return app_url('/app/templates/lote_publico_lista_print.php?' . http_build_query([
      'lote' => $loteId,
      'token' => $token,
    ]));
  }
}

if (!function_exists('lot_public_sheet_print_url')) {
  function lot_public_sheet_print_url(int $loteId, string $token): string {
    return app_url('/app/templates/lote_publico_ficha_print.php?' . http_build_query([
      'lote' => $loteId,
      'token' => $token,
    ]));
  }
}

if (!function_exists('lot_public_asset_url')) {
  function lot_public_asset_url(int $loteId, string $token, int $arquivoId, bool $download = false): string {
    return app_url('/app/templates/arquivo_publico.php?' . http_build_query([
      'lote' => $loteId,
      'token' => $token,
      'id' => $arquivoId,
      'download' => $download ? '1' : '0',
    ]));
  }
}

if (!function_exists('lot_public_is_enabled')) {
  function lot_public_is_enabled(array $config, int $loteId, string $token): bool {
    return !empty($config['published']) && $loteId > 0 && trim((string)($config['token'] ?? '')) !== '' && hash_equals((string)$config['token'], $token);
  }
}
