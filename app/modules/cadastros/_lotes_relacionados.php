<?php

require_once __DIR__ . '/../../../public_php/src/Support/Database.php';

function cad_load_lot_relationships(int $cadastroId, int $companyId = 1): array {
  if ($cadastroId <= 0) {
    return [
      'compras' => [],
      'vendas' => [],
      'fretes' => [],
    ];
  }

  $compras = [];
  $vendas = [];
  $fretes = [];

  try {
    $pdo = Database::connection();

    $purchaseStmt = $pdo->prepare(
      'SELECT l.id,
              l.numero_processo,
              l.titulo_lote,
              l.data_compra,
              l.valor_pago_compra,
              l.custo_total,
              l.status_macro
         FROM lotes l
        WHERE l.company_id = :company_id
          AND l.fornecedor_id = :cadastro_id
        ORDER BY COALESCE(l.data_compra, l.created_at) DESC, l.id DESC'
    );
    $purchaseStmt->execute([
      ':company_id' => $companyId,
      ':cadastro_id' => $cadastroId,
    ]);
    foreach ($purchaseStmt->fetchAll() as $row) {
      if (!is_array($row)) {
        continue;
      }
      $compras[] = [
        'loteId' => (int)($row['id'] ?? 0),
        'processo' => (string)($row['numero_processo'] ?? ''),
        'titulo' => (string)($row['titulo_lote'] ?? ''),
        'data' => (string)($row['data_compra'] ?? ''),
        'compra' => (float)($row['valor_pago_compra'] ?? 0),
        'custoTotal' => (float)($row['custo_total'] ?? 0),
        'status' => (string)($row['status_macro'] ?? ''),
      ];
    }

    $freightStmt = $pdo->prepare(
      'SELECT l.id,
              l.numero_processo,
              l.titulo_lote,
              l.data_compra,
              l.cidade,
              l.estado,
              l.tipo_transporte,
              l.valor_frete,
              l.valor_documento_transporte,
              l.status_macro,
              l.motorista_id,
              l.transportadora_id
         FROM lotes l
        WHERE l.company_id = :company_id
          AND (l.motorista_id = :motorista_id OR l.transportadora_id = :transportadora_id)
        ORDER BY COALESCE(l.data_compra, l.created_at) DESC, l.id DESC'
    );
    $freightStmt->execute([
      ':company_id' => $companyId,
      ':motorista_id' => $cadastroId,
      ':transportadora_id' => $cadastroId,
    ]);
    foreach ($freightStmt->fetchAll() as $row) {
      if (!is_array($row)) {
        continue;
      }
      $fretes[] = [
        'loteId' => (int)($row['id'] ?? 0),
        'processo' => (string)($row['numero_processo'] ?? ''),
        'titulo' => (string)($row['titulo_lote'] ?? ''),
        'data' => (string)($row['data_compra'] ?? ''),
        'cidade' => (string)($row['cidade'] ?? ''),
        'estado' => (string)($row['estado'] ?? ''),
        'tipo' => (int)($row['transportadora_id'] ?? 0) === $cadastroId ? 'Transportadora' : 'Motorista',
        'modalidade' => (string)($row['tipo_transporte'] ?? ''),
        'totalFrete' => (float)($row['valor_frete'] ?? 0) + (float)($row['valor_documento_transporte'] ?? 0),
        'status' => (string)($row['status_macro'] ?? ''),
      ];
    }

    $salesStmt = $pdo->prepare(
      'SELECT m.id,
              m.lote_id,
              m.tipo_evento,
              m.data_evento,
              m.payload_estrutural,
              l.numero_processo,
              l.titulo_lote,
              l.status_macro
         FROM lote_movimentacoes m
         INNER JOIN lotes l
                 ON l.id = m.lote_id
        WHERE l.company_id = :company_id
          AND m.tipo_evento IN (\'item_venda\', \'item_venda_devolucao\')
        ORDER BY m.data_evento DESC, m.id DESC'
    );
    $salesStmt->execute([':company_id' => $companyId]);
    $salesMap = [];
    foreach ($salesStmt->fetchAll() as $row) {
      if (!is_array($row)) {
        continue;
      }
      $payloadRaw = $row['payload_estrutural'] ?? null;
      $payload = [];
      if (is_string($payloadRaw) && $payloadRaw !== '') {
        $decoded = json_decode($payloadRaw, true);
        $payload = is_array($decoded) ? $decoded : [];
      } elseif (is_array($payloadRaw)) {
        $payload = $payloadRaw;
      }
      if ((int)($payload['cliente_id'] ?? 0) !== $cadastroId) {
        continue;
      }

      $saleRef = trim((string)($payload['sale_id'] ?? $payload['sale_ref'] ?? ''));
      if ($saleRef === '') {
        $movementId = (int)($row['id'] ?? 0);
        $saleRef = $movementId > 0 ? 'mov:' . $movementId : '';
      }
      if ($saleRef === '') {
        continue;
      }

      if (!isset($salesMap[$saleRef])) {
        $salesMap[$saleRef] = [
          'loteId' => (int)($row['lote_id'] ?? 0),
          'processo' => (string)($row['numero_processo'] ?? ''),
          'titulo' => (string)($row['titulo_lote'] ?? ''),
          'data' => (string)($row['data_evento'] ?? ''),
          'produto' => (string)($payload['descricao_item'] ?? ''),
          'forma' => (string)($payload['forma_pagamento'] ?? ''),
          'quantidadeVendida' => 0.0,
          'quantidadeDevolvida' => 0.0,
          'valorBruto' => 0.0,
          'valorDevolvido' => 0.0,
          'status' => (string)($row['status_macro'] ?? ''),
        ];
      }

      if ((string)($row['tipo_evento'] ?? '') === 'item_venda') {
        $salesMap[$saleRef]['quantidadeVendida'] += (float)($payload['quantidade_vendida'] ?? 0);
        $salesMap[$saleRef]['valorBruto'] += (float)($payload['valor_total_vendido'] ?? 0);
        $salesMap[$saleRef]['forma'] = (string)($payload['forma_pagamento'] ?? $salesMap[$saleRef]['forma']);
      } else {
        $salesMap[$saleRef]['quantidadeDevolvida'] += (float)($payload['quantidade_devolvida'] ?? 0);
        $salesMap[$saleRef]['valorDevolvido'] += (float)($payload['valor_total_devolvido'] ?? 0);
      }
    }
    $vendas = array_values($salesMap);
    usort($vendas, static function (array $a, array $b): int {
      return strcmp((string)($b['data'] ?? ''), (string)($a['data'] ?? ''));
    });
  } catch (Throwable $e) {
    return [
      'compras' => [],
      'vendas' => [],
      'fretes' => [],
    ];
  }

  return [
    'compras' => $compras,
    'vendas' => $vendas,
    'fretes' => $fretes,
  ];
}
