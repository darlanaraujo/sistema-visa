<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('America/Sao_Paulo');

if (!isset($_SESSION['auth_user'])) {
  http_response_code(401);
  echo json_encode(['ok' => false, 'error' => 'UNAUTHENTICATED'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}

require_once __DIR__ . '/../src/Support/Database.php';
require_once __DIR__ . '/../../app/core/url.php';
require_once __DIR__ . '/../src/Repositories/CadastroRepository.php';

function alerts_json(array $payload, int $status = 200): void {
  http_response_code($status);
  echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}

function alerts_company_id(): int {
  $raw = $_GET['company_id'] ?? 1;
  $id = (int)$raw;
  return $id > 0 ? $id : 1;
}

function alerts_store_array(PDO $pdo, string $key, int $companyId = 1): array {
  $stmt = $pdo->prepare(
    'SELECT value_json
       FROM store
      WHERE company_id = :company_id
        AND store_key = :store_key
      LIMIT 1'
  );
  $stmt->execute([
    ':company_id' => $companyId,
    ':store_key' => $key,
  ]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!is_array($row) || !isset($row['value_json'])) {
    return [];
  }
  $decoded = json_decode((string)$row['value_json'], true);
  return is_array($decoded) ? $decoded : [];
}

function alerts_dt(?string $iso): ?DateTimeImmutable {
  $value = trim((string)$iso);
  if ($value === '') {
    return null;
  }
  try {
    return (new DateTimeImmutable($value))->setTimezone(new DateTimeZone('America/Sao_Paulo'));
  } catch (Throwable $e) {
    return null;
  }
}

function alerts_money(float $value): string {
  return 'R$ ' . number_format($value, 2, ',', '.');
}

function alerts_title_from_name(array $row, string $primary = 'nome', string $secondary = 'razao_social', string $fallback = 'Registro'): string {
  $first = trim((string)($row[$primary] ?? ''));
  if ($first !== '') {
    return $first;
  }
  $second = trim((string)($row[$secondary] ?? ''));
  return $second !== '' ? $second : $fallback;
}

function alerts_blocks_to_flat(array $blocks, int $limit = 12): array {
  $items = [];
  foreach ($blocks as $block) {
    if (!is_array($block)) {
      continue;
    }
    foreach ((array)($block['items'] ?? []) as $item) {
      if (!is_array($item)) {
        continue;
      }
      $items[] = $item;
    }
  }
  return array_slice($items, 0, $limit);
}

function alerts_lot_stage_label(string $stage): string {
  return match ($stage) {
    'compra' => 'Compra',
    'autorizacao_coleta' => 'Autorização de coleta',
    'liberacao_coleta' => 'Liberação de coleta',
    'coleta' => 'Coleta',
    'entrega' => 'Entrega',
    'finalizado' => 'Finalizado',
    default => ucfirst(str_replace('_', ' ', trim($stage))),
  };
}

function alerts_lot_previous_stage(string $stage): ?string {
  return match ($stage) {
    'autorizacao_coleta' => 'compra',
    'liberacao_coleta' => 'autorizacao_coleta',
    'coleta' => 'liberacao_coleta',
    'entrega' => 'coleta',
    'finalizado' => 'entrega',
    default => null,
  };
}

function alerts_lot_stage_reference_ts(array $row, array $movementsByLot, string $stage): ?int {
  if ($stage === 'entrega') {
    $delivery = alerts_dt((string)($row['data_entrega'] ?? ''));
    if ($delivery instanceof DateTimeImmutable) {
      return $delivery->getTimestamp();
    }
  }

  $referenceStage = alerts_lot_previous_stage($stage);
  if ($referenceStage === null) {
    return null;
  }

  foreach (array_reverse($movementsByLot[(int)($row['id'] ?? 0)] ?? []) as $movement) {
    if (!is_array($movement)) {
      continue;
    }
    $payload = json_decode((string)($movement['payload_estrutural'] ?? '[]'), true);
    if (!is_array($payload)) {
      $payload = [];
    }
    if ((string)($payload['timeline_stage'] ?? '') !== $referenceStage) {
      continue;
    }
    if ((string)($payload['timeline_action'] ?? '') !== 'conclusao') {
      continue;
    }
    $movementDate = alerts_dt((string)($movement['data_evento'] ?? ''));
    if ($movementDate instanceof DateTimeImmutable) {
      return $movementDate->getTimestamp();
    }
  }

  if ($referenceStage === 'compra') {
    $purchase = alerts_dt((string)($row['data_compra'] ?? ''));
    if ($purchase instanceof DateTimeImmutable) {
      return $purchase->getTimestamp();
    }
  }

  return null;
}

function alerts_lot_delay_type(array $row, array $movementsByLot, string $stage): ?string {
  if ($stage === '' || $stage === 'finalizado') {
    return null;
  }
  $referenceTs = alerts_lot_stage_reference_ts($row, $movementsByLot, $stage);
  if ($referenceTs === null) {
    return null;
  }

  $today = new DateTimeImmutable('today', new DateTimeZone('America/Sao_Paulo'));
  $days = (int) floor(($today->getTimestamp() - $referenceTs) / 86400);
  if ($days >= 3) {
    return 'danger';
  }
  if ($days >= 1) {
    return 'warning';
  }

  return null;
}

function alerts_financeiro(PDO $pdo, int $companyId): array {
  $cpRows = array_values(array_filter(alerts_store_array($pdo, 'fin_cp_rows_v1', $companyId), 'is_array'));
  $crRows = array_values(array_filter(alerts_store_array($pdo, 'fin_cr_rows_v1', $companyId), 'is_array'));
  $today = new DateTimeImmutable('today', new DateTimeZone('America/Sao_Paulo'));
  $next7 = $today->modify('+7 days');

  $buildBlock = static function (array $rows, string $title, string $href, string $kind) use ($today, $next7): array {
    $danger = 0;
    $warning = 0;
    $info = 0;
    $success = 0;
    $items = [];

    foreach ($rows as $row) {
      $status = strtolower(trim((string)($row['status'] ?? 'open')));
      if ($status !== 'open') {
        $success++;
        continue;
      }

      $date = alerts_dt((string)($row['data'] ?? ''));
      if (!$date) {
        continue;
      }

      $baseTitle = $kind === 'cp'
        ? trim((string)($row['conta'] ?? 'Conta a pagar'))
        : trim((string)($row['cliente'] ?? 'Conta a receber'));
      $baseMeta = $kind === 'cp'
        ? trim((string)($row['imovel'] ?? ''))
        : trim((string)($row['forma'] ?? ''));
      $amount = (float)($row['valor'] ?? 0);
      $type = 'info';
      $message = '';

      if ($date < $today) {
        $danger++;
        $type = 'danger';
        $message = ($baseMeta !== '' ? $baseMeta . ' • ' : '') . 'Vencido em ' . $date->format('d/m/Y') . ' • ' . alerts_money($amount);
      } elseif ($date == $today) {
        $warning++;
        $type = 'warning';
        $message = ($baseMeta !== '' ? $baseMeta . ' • ' : '') . 'Vence hoje • ' . alerts_money($amount);
      } elseif ($date <= $next7) {
        $info++;
        $type = 'info';
        $message = ($baseMeta !== '' ? $baseMeta . ' • ' : '') . 'Vence em ' . $date->format('d/m/Y') . ' • ' . alerts_money($amount);
      } else {
        continue;
      }

      $items[] = [
        'type' => $type,
        'title' => $baseTitle !== '' ? $baseTitle : ($kind === 'cp' ? 'Conta a pagar' : 'Conta a receber'),
        'message' => $message,
        'href' => $href,
      ];
    }

    usort($items, static function (array $left, array $right): int {
      $priority = ['danger' => 0, 'warning' => 1, 'info' => 2, 'success' => 3];
      return ($priority[$left['type']] ?? 9) <=> ($priority[$right['type']] ?? 9);
    });

    return [
      'title' => $title,
      'href' => $href,
      'summary' => [
        'success' => $success,
        'info' => $info,
        'warning' => $warning,
        'danger' => $danger,
      ],
      'items' => array_slice($items, 0, 4),
    ];
  };

  $blocks = [
    $buildBlock($cpRows, 'Contas a pagar', app_url('/app/templates/financeiro_contas_pagar.php'), 'cp'),
    $buildBlock($crRows, 'Contas a receber', app_url('/app/templates/financeiro_contas_receber.php'), 'cr'),
  ];

  return [
    'ok' => true,
    'module' => 'financeiro',
    'blocks' => $blocks,
    'alerts' => alerts_blocks_to_flat($blocks),
  ];
}

function alerts_cadastros(PDO $pdo, int $companyId): array {
  $repo = new CadastroRepository();
  $recent = $repo->listRecentMovimentacoes($companyId, 6);

  $stmt = $pdo->prepare(
    'SELECT c.id, c.nome, c.razao_social, c.tipo_pessoa, c.documento, c.telefone, c.whatsapp, c.celular
       FROM cadastros c
      WHERE c.company_id = :company_id
      ORDER BY c.updated_at DESC, c.id DESC
      LIMIT 120'
  );
  $stmt->execute([':company_id' => $companyId]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $rows = is_array($rows) ? $rows : [];

  $warningItems = [];
  $warningCount = 0;
  foreach ($rows as $row) {
    if (!is_array($row)) {
      continue;
    }

    $name = alerts_title_from_name($row, 'razao_social', 'nome', 'Cadastro');
    $documento = trim((string)($row['documento'] ?? ''));
    $hasPhone = trim((string)($row['telefone'] ?? '')) !== ''
      || trim((string)($row['whatsapp'] ?? '')) !== ''
      || trim((string)($row['celular'] ?? '')) !== '';

    if (strtoupper(trim((string)($row['tipo_pessoa'] ?? 'PF'))) === 'PJ' && $documento === '') {
      $warningCount++;
      if (count($warningItems) < 4) {
        $warningItems[] = [
          'type' => 'warning',
          'title' => $name,
          'message' => 'Cadastro PJ sem CPF/CNPJ informado.',
          'href' => app_url('/app/templates/cadastros_ficha.php?id=' . (int)($row['id'] ?? 0)),
        ];
      }
    }

    if (!$hasPhone) {
      $warningCount++;
      if (count($warningItems) < 4) {
        $warningItems[] = [
          'type' => 'warning',
          'title' => $name,
          'message' => 'Cadastro sem telefone, celular ou WhatsApp.',
          'href' => app_url('/app/templates/cadastros_ficha.php?id=' . (int)($row['id'] ?? 0)),
        ];
      }
    }
  }

  $infoItems = [];
  foreach ($recent as $entry) {
    if (!is_array($entry) || count($infoItems) >= 2) {
      continue;
    }
    $cadastroNome = trim((string)($entry['cadastroNome'] ?? ''));
    $descricao = trim((string)($entry['descricaoEvento'] ?? '')) ?: 'Movimentação recente';
    $meta = alerts_dt((string)($entry['createdAt'] ?? ''));
    $infoItems[] = [
      'type' => 'info',
      'title' => $cadastroNome !== '' ? $cadastroNome : 'Cadastros',
      'message' => $descricao . ($meta ? ' • ' . $meta->format('d/m/Y H:i') : ''),
      'href' => app_url('/app/templates/cadastros.php'),
    ];
  }

  $blocks = [[
    'title' => 'Cadastros',
    'href' => app_url('/app/templates/cadastros.php'),
    'summary' => [
      'success' => 0,
      'info' => count($infoItems),
      'warning' => $warningCount,
      'danger' => 0,
    ],
    'items' => array_slice(array_merge($warningItems, $infoItems), 0, 4),
  ]];

  return [
    'ok' => true,
    'module' => 'cadastros',
    'blocks' => $blocks,
    'alerts' => alerts_blocks_to_flat($blocks),
  ];
}

function alerts_ferramentas(PDO $pdo, int $companyId): array {
  $movements = array_values(array_filter(alerts_store_array($pdo, 'tools_movements_v1', $companyId), 'is_array'));
  $stmt = $pdo->prepare(
    'SELECT store_key, value_json
       FROM store
      WHERE company_id = :company_id
        AND store_key LIKE :prefix'
  );
  $stmt->execute([
    ':company_id' => $companyId,
    ':prefix' => 'tools_ns_%',
  ]);
  $toolRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $toolRows = is_array($toolRows) ? $toolRows : [];

  $inactiveCount = 0;
  $inactiveItems = [];
  foreach ($toolRows as $row) {
    $storeKey = trim((string)($row['store_key'] ?? ''));
    $decoded = json_decode((string)($row['value_json'] ?? '[]'), true);
    if (!is_array($decoded)) {
      continue;
    }
    foreach ($decoded as $item) {
      if (!is_array($item) || !array_key_exists('active', $item) || !empty($item['active'])) {
        continue;
      }
      $inactiveCount++;
      if (count($inactiveItems) >= 4) {
        continue;
      }
      $inactiveItems[] = [
        'type' => 'warning',
        'title' => trim((string)($item['name'] ?? 'Item auxiliar')),
        'message' => 'Item inativo em ' . str_replace(['tools_ns_', '_v1', '.'], ['', '', ' • '], $storeKey) . '.',
        'href' => app_url('/app/templates/ferramentas.php'),
      ];
    }
  }

  $infoItems = [];
  foreach (array_slice($movements, 0, 2) as $entry) {
    if (!is_array($entry)) {
      continue;
    }
    $dt = alerts_dt((string)($entry['createdAt'] ?? ''));
    $scope = trim((string)($entry['scope'] ?? 'Ferramentas'));
    $descricao = trim((string)($entry['descricaoEvento'] ?? 'Movimentação recente'));
    $infoItems[] = [
      'type' => str_contains((string)($entry['tipoEvento'] ?? ''), 'personalizacao') ? 'success' : 'info',
      'title' => $scope !== '' ? $scope : 'Ferramentas',
      'message' => $descricao . ($dt ? ' • ' . $dt->format('d/m/Y H:i') : ''),
      'href' => app_url('/app/templates/ferramentas.php'),
    ];
  }

  $blocks = [[
    'title' => 'Ferramentas',
    'href' => app_url('/app/templates/ferramentas.php'),
    'summary' => [
      'success' => count(array_filter($infoItems, static fn (array $item): bool => ($item['type'] ?? '') === 'success')),
      'info' => count(array_filter($infoItems, static fn (array $item): bool => ($item['type'] ?? '') === 'info')),
      'warning' => $inactiveCount,
      'danger' => 0,
    ],
    'items' => array_slice(array_merge($inactiveItems, $infoItems), 0, 4),
  ]];

  return [
    'ok' => true,
    'module' => 'ferramentas',
    'blocks' => $blocks,
    'alerts' => alerts_blocks_to_flat($blocks),
  ];
}

function alerts_lotes(PDO $pdo, int $companyId): array {
  $stmt = $pdo->prepare(
    'SELECT l.id, l.numero_processo, l.titulo_lote, l.status_macro, l.etapa_timeline, l.tipo_transporte, l.data_compra, l.data_entrega
       FROM lotes l
      WHERE l.company_id = :company_id
      ORDER BY l.data_compra DESC, l.id DESC
      LIMIT 120'
  );
  $stmt->execute([':company_id' => $companyId]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $rows = is_array($rows) ? $rows : [];
  $ids = array_values(array_filter(array_map(static fn (array $row): int => (int)($row['id'] ?? 0), $rows)));
  $movementsByLot = [];
  if ($ids !== []) {
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmtMovements = $pdo->prepare(
      "SELECT lote_id, payload_estrutural, data_evento
         FROM lote_movimentacoes
        WHERE company_id = ?
          AND lote_id IN ({$placeholders})
        ORDER BY data_evento ASC, id ASC"
    );
    $stmtMovements->execute(array_merge([$companyId], $ids));
    $movementRows = $stmtMovements->fetchAll(PDO::FETCH_ASSOC);
    foreach ((array)$movementRows as $movementRow) {
      if (!is_array($movementRow)) {
        continue;
      }
      $movementsByLot[(int)($movementRow['lote_id'] ?? 0)][] = $movementRow;
    }
  }

  $infoCount = 0;
  $warningCount = 0;
  $dangerCount = 0;
  $successCount = 0;
  $items = [];

  foreach ($rows as $row) {
    if (!is_array($row)) {
      continue;
    }
    $id = (int)($row['id'] ?? 0);
    $title = trim((string)($row['titulo_lote'] ?? '')) ?: ('Lote #' . $id);
    $processo = trim((string)($row['numero_processo'] ?? ''));
    $status = trim((string)($row['status_macro'] ?? ''));
    $etapa = trim((string)($row['etapa_timeline'] ?? ''));
    if ($etapa === 'compra' && $status === 'em_transito') {
      $etapa = 'autorizacao_coleta';
    }
    $tipoTransporte = trim((string)($row['tipo_transporte'] ?? ''));

    if ($status === 'em_transito') {
      $infoCount++;
      if (count($items) < 4) {
        $items[] = [
          'type' => 'info',
          'title' => $title,
          'message' => 'Lote em trânsito na etapa ' . alerts_lot_stage_label($etapa) . '.',
          'href' => app_url('/app/templates/lotes.php?lote=' . $id),
        ];
      }
    } elseif ($status === 'em_estoque') {
      $successCount++;
    }

    if ($processo === '') {
      $warningCount++;
      if (count($items) < 4) {
        $items[] = [
          'type' => 'warning',
          'title' => $title,
          'message' => 'Lote sem número de processo informado.',
          'href' => app_url('/app/templates/lotes.php?lote=' . $id),
        ];
      }
    }

    if ($etapa === 'coleta' && ($tipoTransporte === '' || $tipoTransporte === 'sem_frete')) {
      $warningCount++;
      if (count($items) < 4) {
        $items[] = [
          'type' => 'warning',
          'title' => $title,
          'message' => 'Coleta sem frete definido ou marcada como sem frete.',
          'href' => app_url('/app/templates/lotes.php?lote=' . $id),
        ];
      }
    }

    $delayType = alerts_lot_delay_type($row, $movementsByLot, $etapa);
    if ($delayType !== null) {
      if ($delayType === 'danger') {
        $dangerCount++;
      } else {
        $warningCount++;
      }
      if (count($items) < 4) {
        $items[] = [
          'type' => $delayType,
          'title' => $title,
          'message' => alerts_lot_stage_label($etapa) . ' em atraso.',
          'href' => app_url('/app/templates/lotes.php?lote=' . $id),
        ];
      }
    }
  }

  $blocks = [[
    'title' => 'Lotes',
    'href' => app_url('/app/templates/lotes.php'),
    'summary' => [
      'success' => $successCount,
      'info' => $infoCount,
      'warning' => $warningCount,
      'danger' => $dangerCount,
    ],
    'items' => array_slice($items, 0, 4),
  ]];

  return [
    'ok' => true,
    'module' => 'lotes',
    'blocks' => $blocks,
    'alerts' => alerts_blocks_to_flat($blocks),
  ];
}

try {
  $module = strtolower(trim((string)($_GET['module'] ?? 'default')));
  $companyId = alerts_company_id();
  $pdo = Database::connection();

  $payload = match ($module) {
    'financeiro' => alerts_financeiro($pdo, $companyId),
    'cadastros' => alerts_cadastros($pdo, $companyId),
    'ferramentas' => alerts_ferramentas($pdo, $companyId),
    'lotes' => alerts_lotes($pdo, $companyId),
    default => ['ok' => true, 'module' => $module, 'blocks' => [], 'alerts' => []],
  };

  alerts_json($payload, 200);
} catch (Throwable $e) {
  alerts_json(['ok' => false, 'error' => 'SERVER_ERROR'], 500);
}
