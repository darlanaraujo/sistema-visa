<?php
declare(strict_types=1);

require_once __DIR__ . '/../Support/Database.php';
require_once __DIR__ . '/ArquivoRepository.php';

final class LoteRepository {
  private const STATUS_MACRO = ['em_transito', 'em_estoque', 'finalizado', 'cancelado'];
  private const ETAPAS_TIMELINE = ['compra', 'autorizacao_coleta', 'liberacao_coleta', 'coleta', 'entrega', 'finalizado'];
  private const ETAPAS_TIMELINE_DB = ['compra_confirmada', 'liberacao', 'coleta', 'transporte', 'recebido', 'venda', 'encerrado'];
  private const TIPOS_CONTROLE = ['unidade', 'kg', 'metros'];
  private const TIPOS_TRANSPORTE = ['motorista_autonomo', 'transportadora', 'transporte_proprio', 'sem_frete', 'retirada_cliente'];
  private const ETAPA_APP_TO_DB = [
    'compra' => 'compra_confirmada',
    'autorizacao_coleta' => 'liberacao',
    'liberacao_coleta' => 'liberacao',
    'coleta' => 'coleta',
    'entrega' => 'recebido',
    'finalizado' => 'encerrado',
  ];
  private const ETAPA_DB_TO_APP = [
    'compra_confirmada' => 'compra',
    'liberacao' => 'liberacao_coleta',
    'coleta' => 'coleta',
    'transporte' => 'entrega',
    'recebido' => 'entrega',
    'venda' => 'finalizado',
    'encerrado' => 'finalizado',
  ];

  public function findById(int $id, int $companyId = 1, bool $includeRelations = true): ?array {
    if ($id <= 0) {
      return null;
    }

    $stmt = Database::connection()->prepare(
      'SELECT l.*
         FROM lotes l
        WHERE l.id = :id
          AND l.company_id = :company_id
        LIMIT 1'
    );
    $stmt->execute([
      ':id' => $id,
      ':company_id' => $companyId,
    ]);

    $row = $stmt->fetch();
    if (!is_array($row) || !$row) {
      return null;
    }

    return $this->hydrateLote($row, $includeRelations);
  }

  public function findByNumeroProcesso(string $numeroProcesso, int $companyId = 1, bool $includeRelations = true): ?array {
    $numeroProcesso = $this->normalizeText($numeroProcesso);
    if ($numeroProcesso === '') {
      return null;
    }

    $stmt = Database::connection()->prepare(
      'SELECT l.*
         FROM lotes l
        WHERE l.company_id = :company_id
          AND l.numero_processo = :numero_processo
        LIMIT 1'
    );
    $stmt->execute([
      ':company_id' => $companyId,
      ':numero_processo' => $numeroProcesso,
    ]);

    $row = $stmt->fetch();
    if (!is_array($row) || !$row) {
      return null;
    }

    return $this->hydrateLote($row, $includeRelations);
  }

  public function list(array $filters = [], int $companyId = 1, bool $includeRelations = false): array {
    $params = [':company_id' => $companyId];
    $where = ['l.company_id = :company_id'];

    $statusMacro = $this->normalizeEnum((string)($filters['status_macro'] ?? $filters['statusMacro'] ?? ''), self::STATUS_MACRO, '');
    if ($statusMacro !== '') {
      $where[] = 'l.status_macro = :status_macro';
      $params[':status_macro'] = $statusMacro;
    }

    $etapaTimeline = $this->normalizeEnum((string)($filters['etapa_timeline'] ?? $filters['etapaTimeline'] ?? ''), self::ETAPAS_TIMELINE, '');
    if ($etapaTimeline !== '') {
      $where[] = 'l.etapa_timeline = :etapa_timeline';
      $params[':etapa_timeline'] = $this->mapTimelineStageToDb($etapaTimeline);
    }

    $fornecedorId = (int)($filters['fornecedor_id'] ?? $filters['fornecedorId'] ?? 0);
    if ($fornecedorId > 0) {
      $where[] = 'l.fornecedor_id = :fornecedor_id';
      $params[':fornecedor_id'] = $fornecedorId;
    }

    $term = $this->normalizeText((string)($filters['term'] ?? $filters['busca'] ?? ''));
    if ($term !== '') {
      $where[] = '(l.numero_processo LIKE :term OR l.titulo_lote LIKE :term)';
      $params[':term'] = '%' . $term . '%';
    }

    $limit = $this->normalizeLimit($filters['limit'] ?? 50);
    $offset = $this->normalizeOffset($filters['offset'] ?? 0);

    $sql = sprintf(
      'SELECT l.*
         FROM lotes l
        WHERE %s
        ORDER BY l.data_compra DESC, l.id DESC
        LIMIT %d OFFSET %d',
      implode(' AND ', $where),
      $limit,
      $offset
    );

    $stmt = Database::connection()->prepare($sql);
    $stmt->execute($params);

    $rows = $stmt->fetchAll();
    if (!is_array($rows) || !$rows) {
      return [];
    }

    $items = [];
    foreach ($rows as $row) {
      if (!is_array($row)) {
        continue;
      }
      $items[] = $this->hydrateLote($row, $includeRelations);
    }

    return $items;
  }

  public function create(array $payload, int $companyId = 1): array {
    $data = $this->normalizeLotePayload($payload);
    $this->assertNoDuplicateReference(
      (string)$data['numero_processo'],
      $this->extractNumeroSinistro($data['observacoes_gerais']),
      $companyId
    );

    $pdo = Database::connection();
    $ownsTransaction = !$pdo->inTransaction();
    if ($ownsTransaction) {
      $pdo->beginTransaction();
    }

    try {
      $stmt = $pdo->prepare(
        'INSERT INTO lotes (
           company_id,
           fornecedor_id,
           numero_processo,
           titulo_lote,
           descricao_resumida,
           descricao_operacional,
           tipo_macro_lote,
           data_compra,
           status_macro,
           etapa_timeline,
           observacoes_gerais,
           valor_original_lote,
           valor_depreciado,
           valor_pago_compra,
           despesas_local,
           valor_frete,
           valor_documento_transporte,
           outros_custos,
           custo_total,
           nome_local,
           nome_contato,
           telefone,
           email,
           endereco,
           cidade,
           estado,
           observacoes_local,
           tipo_transporte,
           motorista_id,
           transportadora_id,
           veiculo_referencia,
           agenciador,
           documento_transporte,
           data_contratacao,
           data_agendamento,
           data_coleta,
           data_entrega,
           observacoes_logisticas
         ) VALUES (
           :company_id,
           :fornecedor_id,
           :numero_processo,
           :titulo_lote,
           :descricao_resumida,
           :descricao_operacional,
           :tipo_macro_lote,
           :data_compra,
           :status_macro,
           :etapa_timeline,
           :observacoes_gerais,
           :valor_original_lote,
           :valor_depreciado,
           :valor_pago_compra,
           :despesas_local,
           :valor_frete,
           :valor_documento_transporte,
           :outros_custos,
           :custo_total,
           :nome_local,
           :nome_contato,
           :telefone,
           :email,
           :endereco,
           :cidade,
           :estado,
           :observacoes_local,
           :tipo_transporte,
           :motorista_id,
           :transportadora_id,
           :veiculo_referencia,
           :agenciador,
           :documento_transporte,
           :data_contratacao,
           :data_agendamento,
           :data_coleta,
           :data_entrega,
           :observacoes_logisticas
         )'
      );

      $stmt->execute($this->buildLoteStatementParams($data, $companyId));

      $loteId = (int)$pdo->lastInsertId();
      $this->syncExtendedStructures($loteId, $payload, $companyId);

      if ($ownsTransaction && $pdo->inTransaction()) {
        $pdo->commit();
      }

      return $this->findById($loteId, $companyId, true) ?? [];
    } catch (Throwable $e) {
      if ($ownsTransaction && $pdo->inTransaction()) {
        $pdo->rollBack();
      }
      throw $e;
    }
  }

  public function update(int $id, array $payload, int $companyId = 1): ?array {
    if ($id <= 0 || $this->findById($id, $companyId, false) === null) {
      return null;
    }

    $data = $this->normalizeLotePayload($payload);
    $this->assertNoDuplicateReference(
      (string)$data['numero_processo'],
      $this->extractNumeroSinistro($data['observacoes_gerais']),
      $companyId,
      $id
    );

    $pdo = Database::connection();
    $ownsTransaction = !$pdo->inTransaction();
    if ($ownsTransaction) {
      $pdo->beginTransaction();
    }

    try {
      $stmt = $pdo->prepare(
        'UPDATE lotes
            SET fornecedor_id = :fornecedor_id,
                numero_processo = :numero_processo,
                titulo_lote = :titulo_lote,
                descricao_resumida = :descricao_resumida,
                descricao_operacional = :descricao_operacional,
                tipo_macro_lote = :tipo_macro_lote,
                data_compra = :data_compra,
                status_macro = :status_macro,
                etapa_timeline = :etapa_timeline,
                observacoes_gerais = :observacoes_gerais,
                valor_original_lote = :valor_original_lote,
                valor_depreciado = :valor_depreciado,
                valor_pago_compra = :valor_pago_compra,
                despesas_local = :despesas_local,
                valor_frete = :valor_frete,
                valor_documento_transporte = :valor_documento_transporte,
                outros_custos = :outros_custos,
                custo_total = :custo_total,
                nome_local = :nome_local,
                nome_contato = :nome_contato,
                telefone = :telefone,
                email = :email,
                endereco = :endereco,
                cidade = :cidade,
                estado = :estado,
                observacoes_local = :observacoes_local,
                tipo_transporte = :tipo_transporte,
                motorista_id = :motorista_id,
                transportadora_id = :transportadora_id,
                veiculo_referencia = :veiculo_referencia,
                agenciador = :agenciador,
                documento_transporte = :documento_transporte,
                data_contratacao = :data_contratacao,
                data_agendamento = :data_agendamento,
                data_coleta = :data_coleta,
                data_entrega = :data_entrega,
                observacoes_logisticas = :observacoes_logisticas,
                updated_at = CURRENT_TIMESTAMP
          WHERE id = :id
            AND company_id = :company_id'
      );

      $params = $this->buildLoteStatementParams($data, $companyId);
      $params[':id'] = $id;
      $stmt->execute($params);

      $this->syncExtendedStructures($id, $payload, $companyId);

      if ($ownsTransaction && $pdo->inTransaction()) {
        $pdo->commit();
      }

      return $this->findById($id, $companyId, true);
    } catch (Throwable $e) {
      if ($ownsTransaction && $pdo->inTransaction()) {
        $pdo->rollBack();
      }
      throw $e;
    }
  }

  public function getItens(int $loteId, int $companyId = 1): array {
    if ($loteId <= 0 || !$this->existsLote($loteId, $companyId)) {
      return [];
    }

    $stmt = Database::connection()->prepare(
      'SELECT i.*
         FROM lote_itens i
         INNER JOIN lotes l
                 ON l.id = i.lote_id
        WHERE i.lote_id = :lote_id
          AND l.company_id = :company_id
        ORDER BY i.id ASC'
    );
    $stmt->execute([
      ':lote_id' => $loteId,
      ':company_id' => $companyId,
    ]);

    $rows = $stmt->fetchAll();
    if (!is_array($rows) || !$rows) {
      return [];
    }

    return array_values(array_filter(array_map(
      fn ($row) => is_array($row) ? $this->hydrateItem($row, $companyId) : null,
      $rows
    )));
  }

  public function replaceItens(int $loteId, array $items, int $companyId = 1): array {
    if ($loteId <= 0 || !$this->existsLote($loteId, $companyId)) {
      return [];
    }

    $pdo = Database::connection();
    $normalizedItems = $this->normalizeItensPayload($items);
    $itemRelationMap = [];
    $deleteTagRel = $pdo->prepare(
      'DELETE rel
         FROM lote_item_tag_rel rel
         INNER JOIN lote_itens i
                 ON i.id = rel.lote_item_id
         INNER JOIN lotes l
                 ON l.id = i.lote_id
        WHERE i.lote_id = :lote_id
          AND l.company_id = :company_id'
    );
    $deleteItem = $pdo->prepare(
      'DELETE i
         FROM lote_itens i
         INNER JOIN lotes l
                 ON l.id = i.lote_id
        WHERE i.lote_id = :lote_id
          AND l.company_id = :company_id'
    );
    $insertItem = $pdo->prepare(
      'INSERT INTO lote_itens (
         company_id,
         lote_id,
         descricao_item,
         tipo_controle_item,
         quantidade_total,
         quantidade_disponivel,
         quantidade_baixada,
         quantidade_vendida,
         custo_unitario_referencia,
         custo_total_referencia,
         valor_venda_unitario_sugerido,
         valor_venda_total_sugerido,
         observacoes_item,
         status_item
       ) VALUES (
         :company_id,
         :lote_id,
         :descricao_item,
         :tipo_controle_item,
         :quantidade_total,
         :quantidade_disponivel,
         :quantidade_baixada,
         :quantidade_vendida,
         :custo_unitario_referencia,
         :custo_total_referencia,
         :valor_venda_unitario_sugerido,
         :valor_venda_total_sugerido,
         :observacoes_item,
         :status_item
       )'
    );

    $deleteTagRel->execute([
      ':lote_id' => $loteId,
      ':company_id' => $companyId,
    ]);
    $deleteItem->execute([
      ':lote_id' => $loteId,
      ':company_id' => $companyId,
    ]);

    foreach ($normalizedItems as $item) {
      $insertItem->execute([
        ':company_id' => $companyId,
        ':lote_id' => $loteId,
        ':descricao_item' => $item['descricao_item'],
        ':tipo_controle_item' => $item['tipo_controle_item'],
        ':quantidade_total' => $item['quantidade_total'],
        ':quantidade_disponivel' => $item['quantidade_disponivel'],
        ':quantidade_baixada' => $item['quantidade_baixada'],
        ':quantidade_vendida' => $item['quantidade_vendida'],
        ':custo_unitario_referencia' => $item['custo_unitario_referencia'],
        ':custo_total_referencia' => $item['custo_total_referencia'],
        ':valor_venda_unitario_sugerido' => $item['valor_venda_unitario_sugerido'],
        ':valor_venda_total_sugerido' => $item['valor_venda_total_sugerido'],
        ':observacoes_item' => $item['observacoes_item'],
        ':status_item' => $item['status_item'],
      ]);

      $itemId = (int)$pdo->lastInsertId();
      $sourceItemId = (int)($item['source_item_id'] ?? 0);
      if ($sourceItemId > 0) {
        $itemRelationMap[$sourceItemId] = $itemId;
      }
      $this->syncItemTags($itemId, (array)($item['tags'] ?? []), $companyId);
    }

    if ($itemRelationMap !== []) {
      $this->migrateItemArquivoRelations($itemRelationMap, $companyId);
    }

    return $this->getItens($loteId, $companyId);
  }

  public function getMovimentacoes(int $loteId, int $companyId = 1): array {
    if ($loteId <= 0 || !$this->existsLote($loteId, $companyId)) {
      return [];
    }

    $stmt = Database::connection()->prepare(
      'SELECT m.*
         FROM lote_movimentacoes m
         INNER JOIN lotes l
                 ON l.id = m.lote_id
        WHERE m.lote_id = :lote_id
          AND l.company_id = :company_id
        ORDER BY m.data_evento ASC, m.id ASC'
    );
    $stmt->execute([
      ':lote_id' => $loteId,
      ':company_id' => $companyId,
    ]);

    $rows = $stmt->fetchAll();
    if (!is_array($rows) || !$rows) {
      return [];
    }

    return array_values(array_filter(array_map(
      fn ($row) => is_array($row) ? $this->hydrateMovimentacao($row) : null,
      $rows
    )));
  }

  public function addMovimentacao(int $loteId, array $payload, int $companyId = 1): ?array {
    if ($loteId <= 0 || !$this->existsLote($loteId, $companyId)) {
      return null;
    }

    $data = $this->normalizeMovimentacaoPayload($payload);
    $stmt = Database::connection()->prepare(
      'INSERT INTO lote_movimentacoes (
         company_id,
         lote_id,
         tipo_evento,
         descricao_evento,
         payload_estrutural,
         data_evento,
         responsavel
       ) VALUES (
         :company_id,
         :lote_id,
         :tipo_evento,
         :descricao_evento,
         :payload_estrutural,
         :data_evento,
         :responsavel
       )'
    );
    $stmt->execute([
      ':company_id' => $companyId,
      ':lote_id' => $loteId,
      ':tipo_evento' => $data['tipo_evento'],
      ':descricao_evento' => $data['descricao_evento'],
      ':payload_estrutural' => $data['payload_estrutural'],
      ':data_evento' => $data['data_evento'],
      ':responsavel' => $data['responsavel'],
    ]);

    $id = (int)Database::connection()->lastInsertId();
    return $this->findMovimentacaoById($id, $companyId);
  }

  public function updateMovimentacao(int $id, array $payload, int $companyId = 1): ?array {
    $existing = $this->findMovimentacaoById($id, $companyId);
    if (!is_array($existing)) {
      return null;
    }

    $data = $this->normalizeMovimentacaoPayload(array_merge($existing, $payload));
    $stmt = Database::connection()->prepare(
      'UPDATE lote_movimentacoes
          SET tipo_evento = :tipo_evento,
              descricao_evento = :descricao_evento,
              payload_estrutural = :payload_estrutural,
              data_evento = :data_evento,
              responsavel = :responsavel
        WHERE id = :id
          AND company_id = :company_id'
    );
    $stmt->execute([
      ':id' => $id,
      ':company_id' => $companyId,
      ':tipo_evento' => $data['tipo_evento'],
      ':descricao_evento' => $data['descricao_evento'],
      ':payload_estrutural' => $data['payload_estrutural'],
      ':data_evento' => $data['data_evento'],
      ':responsavel' => $data['responsavel'],
    ]);

    return $this->findMovimentacaoById($id, $companyId);
  }

  public function deleteMovimentacao(int $id, int $companyId = 1): bool {
    if ($id <= 0) {
      return false;
    }

    $stmt = Database::connection()->prepare(
      'DELETE FROM lote_movimentacoes
        WHERE id = :id
          AND company_id = :company_id'
    );
    $stmt->execute([
      ':id' => $id,
      ':company_id' => $companyId,
    ]);

    return $stmt->rowCount() > 0;
  }

  private function findMovimentacaoById(int $id, int $companyId): ?array {
    if ($id <= 0) {
      return null;
    }

    $stmt = Database::connection()->prepare(
      'SELECT m.*
         FROM lote_movimentacoes m
        WHERE m.id = :id
          AND m.company_id = :company_id
        LIMIT 1'
    );
    $stmt->execute([
      ':id' => $id,
      ':company_id' => $companyId,
    ]);

    $row = $stmt->fetch();
    if (!is_array($row) || !$row) {
      return null;
    }

    return $this->hydrateMovimentacao($row);
  }

  private function hydrateLote(array $row, bool $includeRelations): array {
    $id = (int)($row['id'] ?? 0);
    $companyId = (int)($row['company_id'] ?? 1);

    $payload = [
      'id' => $id,
      'companyId' => $companyId,
      'fornecedorId' => (int)($row['fornecedor_id'] ?? 0),
      'numeroProcesso' => (string)($row['numero_processo'] ?? ''),
      'tituloLote' => (string)($row['titulo_lote'] ?? ''),
      'descricaoResumida' => (string)($row['descricao_resumida'] ?? ''),
      'descricaoOperacional' => (string)($row['descricao_operacional'] ?? ''),
      'tipoMacroLote' => (string)($row['tipo_macro_lote'] ?? ''),
      'dataCompra' => (string)($row['data_compra'] ?? ''),
      'statusMacro' => (string)($row['status_macro'] ?? ''),
      'etapaTimeline' => $this->mapTimelineStageFromDb((string)($row['etapa_timeline'] ?? '')),
      'observacoesGerais' => (string)($row['observacoes_gerais'] ?? ''),
      'valorOriginalLote' => (float)($row['valor_original_lote'] ?? 0),
      'valorDepreciado' => (float)($row['valor_depreciado'] ?? 0),
      'valorPagoCompra' => (float)($row['valor_pago_compra'] ?? 0),
      'despesasLocal' => (float)($row['despesas_local'] ?? 0),
      'valorFrete' => (float)($row['valor_frete'] ?? 0),
      'valorDocumentoTransporte' => (float)($row['valor_documento_transporte'] ?? 0),
      'outrosCustos' => (float)($row['outros_custos'] ?? 0),
      'custoTotal' => (float)($row['custo_total'] ?? 0),
      'nomeLocal' => (string)($row['nome_local'] ?? ''),
      'nomeContato' => (string)($row['nome_contato'] ?? ''),
      'telefone' => (string)($row['telefone'] ?? ''),
      'email' => (string)($row['email'] ?? ''),
      'endereco' => (string)($row['endereco'] ?? ''),
      'cidade' => (string)($row['cidade'] ?? ''),
      'estado' => (string)($row['estado'] ?? ''),
      'observacoesLocal' => (string)($row['observacoes_local'] ?? ''),
      'tipoTransporte' => (string)($row['tipo_transporte'] ?? ''),
      'motoristaId' => (int)($row['motorista_id'] ?? 0),
      'transportadoraId' => (int)($row['transportadora_id'] ?? 0),
      'veiculoReferencia' => (string)($row['veiculo_referencia'] ?? ''),
      'agenciador' => (string)($row['agenciador'] ?? ''),
      'documentoTransporte' => (string)($row['documento_transporte'] ?? ''),
      'dataContratacao' => (string)($row['data_contratacao'] ?? ''),
      'dataAgendamento' => (string)($row['data_agendamento'] ?? ''),
      'dataColeta' => (string)($row['data_coleta'] ?? ''),
      'dataEntrega' => (string)($row['data_entrega'] ?? ''),
      'observacoesLogisticas' => (string)($row['observacoes_logisticas'] ?? ''),
      'createdAt' => (string)($row['created_at'] ?? ''),
      'updatedAt' => (string)($row['updated_at'] ?? ''),
    ];

    if ($includeRelations && $id > 0) {
      $payload['itens'] = $this->getItens($id, $companyId);
      $payload['tags'] = $this->getTags($id, $companyId);
      $payload['movimentacoes'] = $this->getMovimentacoes($id, $companyId);
      $payload['etapaTimeline'] = $this->resolveTimelineStageFromHistory(
        (string)$payload['etapaTimeline'],
        (string)$payload['statusMacro'],
        (array)$payload['movimentacoes']
      );
    }

    return $payload;
  }

  private function hydrateItem(array $row, int $companyId): array {
    $itemId = (int)($row['id'] ?? 0);

    return [
      'id' => $itemId,
      'companyId' => (int)($row['company_id'] ?? 0),
      'loteId' => (int)($row['lote_id'] ?? 0),
      'descricaoItem' => (string)($row['descricao_item'] ?? ''),
      'tipoControleItem' => (string)($row['tipo_controle_item'] ?? ''),
      'quantidadeTotal' => (float)($row['quantidade_total'] ?? 0),
      'quantidadeDisponivel' => (float)($row['quantidade_disponivel'] ?? 0),
      'quantidadeBaixada' => (float)($row['quantidade_baixada'] ?? 0),
      'quantidadeVendida' => (float)($row['quantidade_vendida'] ?? 0),
      'custoUnitarioReferencia' => (float)($row['custo_unitario_referencia'] ?? 0),
      'custoTotalReferencia' => (float)($row['custo_total_referencia'] ?? 0),
      'valorVendaUnitarioSugerido' => (float)($row['valor_venda_unitario_sugerido'] ?? 0),
      'valorVendaTotalSugerido' => (float)($row['valor_venda_total_sugerido'] ?? 0),
      'observacoesItem' => (string)($row['observacoes_item'] ?? ''),
      'statusItem' => (string)($row['status_item'] ?? ''),
      'createdAt' => (string)($row['created_at'] ?? ''),
      'updatedAt' => (string)($row['updated_at'] ?? ''),
      'tags' => $itemId > 0 ? $this->getItemTags($itemId, $companyId) : [],
      'imagensItem' => $itemId > 0 ? $this->getItemArquivos($itemId, $companyId) : [],
    ];
  }

  private function hydrateMovimentacao(array $row): array {
    $payloadRaw = $row['payload_estrutural'] ?? null;
    $payload = null;
    if (is_string($payloadRaw) && trim($payloadRaw) !== '') {
      $decoded = json_decode($payloadRaw, true);
      $payload = is_array($decoded) ? $decoded : null;
    } elseif (is_array($payloadRaw)) {
      $payload = $payloadRaw;
    }

    return [
      'id' => (int)($row['id'] ?? 0),
      'companyId' => (int)($row['company_id'] ?? 0),
      'loteId' => (int)($row['lote_id'] ?? 0),
      'tipoEvento' => (string)($row['tipo_evento'] ?? ''),
      'descricaoEvento' => (string)($row['descricao_evento'] ?? ''),
      'payloadEstrutural' => $payload,
      'dataEvento' => (string)($row['data_evento'] ?? ''),
      'responsavel' => (string)($row['responsavel'] ?? ''),
      'createdAt' => (string)($row['created_at'] ?? ''),
    ];
  }

  private function getItemTags(int $itemId, int $companyId): array {
    if ($itemId <= 0) {
      return [];
    }

    $stmt = Database::connection()->prepare(
      'SELECT t.*
         FROM lote_item_tag_rel rel
         INNER JOIN cadastro_tags t
                 ON t.id = rel.tag_id
        WHERE rel.lote_item_id = :lote_item_id
          AND t.company_id = :company_id
        ORDER BY t.nome ASC, t.id ASC'
    );
    $stmt->execute([
      ':lote_item_id' => $itemId,
      ':company_id' => $companyId,
    ]);

    $rows = $stmt->fetchAll();
    if (!is_array($rows) || !$rows) {
      return [];
    }

    return array_values(array_filter(array_map(
      fn ($row) => is_array($row) ? $this->hydrateTag($row) : null,
      $rows
    )));
  }

  public function getTags(int $loteId, int $companyId = 1): array {
    if ($loteId <= 0 || !$this->existsLote($loteId, $companyId)) {
      return [];
    }

    $stmt = Database::connection()->prepare(
      'SELECT t.*
         FROM lote_tag_rel rel
         INNER JOIN cadastro_tags t
                 ON t.id = rel.tag_id
        WHERE rel.lote_id = :lote_id
          AND t.company_id = :company_id
        ORDER BY t.nome ASC, t.id ASC'
    );
    $stmt->execute([
      ':lote_id' => $loteId,
      ':company_id' => $companyId,
    ]);

    $rows = $stmt->fetchAll();
    if (!is_array($rows) || !$rows) {
      return [];
    }

    return array_values(array_filter(array_map(
      fn ($row) => is_array($row) ? $this->hydrateTag($row) : null,
      $rows
    )));
  }

  private function syncExtendedStructures(int $loteId, array $payload, int $companyId): void {
    if (array_key_exists('itens', $payload)) {
      $this->replaceItens($loteId, (array)$payload['itens'], $companyId);
    }

    if (array_key_exists('tags', $payload)) {
      $this->replaceTags($loteId, (array)$payload['tags'], $companyId);
    }

    if (array_key_exists('movimentacoes', $payload)) {
      $this->replaceMovimentacoes($loteId, (array)$payload['movimentacoes'], $companyId);
    }
  }

  public function replaceTags(int $loteId, array $tags, int $companyId = 1): array {
    if ($loteId <= 0 || !$this->existsLote($loteId, $companyId)) {
      return [];
    }

    $pdo = Database::connection();
    $deleteStmt = $pdo->prepare(
      'DELETE rel
         FROM lote_tag_rel rel
         INNER JOIN lotes l
                 ON l.id = rel.lote_id
        WHERE rel.lote_id = :lote_id
          AND l.company_id = :company_id'
    );
    $deleteStmt->execute([
      ':lote_id' => $loteId,
      ':company_id' => $companyId,
    ]);

    $normalizedTags = $this->normalizeTagsPayload($tags);
    if ($normalizedTags === []) {
      return [];
    }

    $findTagStmt = $pdo->prepare(
      'SELECT id
         FROM cadastro_tags
        WHERE company_id = :company_id
          AND slug = :slug
        LIMIT 1'
    );
    $insertTagStmt = $pdo->prepare(
      'INSERT INTO cadastro_tags (company_id, nome, slug, status)
       VALUES (:company_id, :nome, :slug, :status)'
    );
    $insertRelStmt = $pdo->prepare(
      'INSERT INTO lote_tag_rel (lote_id, tag_id)
       VALUES (:lote_id, :tag_id)'
    );

    foreach ($normalizedTags as $tag) {
      $findTagStmt->execute([
        ':company_id' => $companyId,
        ':slug' => $tag['slug'],
      ]);
      $tagId = (int)($findTagStmt->fetchColumn() ?: 0);

      if ($tagId <= 0) {
        $insertTagStmt->execute([
          ':company_id' => $companyId,
          ':nome' => $tag['nome'],
          ':slug' => $tag['slug'],
          ':status' => 'ativo',
        ]);
        $tagId = (int)$pdo->lastInsertId();
      }

      $insertRelStmt->execute([
        ':lote_id' => $loteId,
        ':tag_id' => $tagId,
      ]);
    }

    return $this->getTags($loteId, $companyId);
  }

  private function replaceMovimentacoes(int $loteId, array $items, int $companyId): array {
    if ($loteId <= 0 || !$this->existsLote($loteId, $companyId)) {
      return [];
    }

    $pdo = Database::connection();
    $deleteStmt = $pdo->prepare(
      'DELETE m
         FROM lote_movimentacoes m
         INNER JOIN lotes l
                 ON l.id = m.lote_id
        WHERE m.lote_id = :lote_id
          AND l.company_id = :company_id'
    );
    $deleteStmt->execute([
      ':lote_id' => $loteId,
      ':company_id' => $companyId,
    ]);

    $insertStmt = $pdo->prepare(
      'INSERT INTO lote_movimentacoes (
         company_id,
         lote_id,
         tipo_evento,
         descricao_evento,
         payload_estrutural,
         data_evento,
         responsavel
       ) VALUES (
         :company_id,
         :lote_id,
         :tipo_evento,
         :descricao_evento,
         :payload_estrutural,
         :data_evento,
         :responsavel
       )'
    );

    foreach ($this->normalizeMovimentacoesPayload($items) as $item) {
      $insertStmt->execute([
        ':company_id' => $companyId,
        ':lote_id' => $loteId,
        ':tipo_evento' => $item['tipo_evento'],
        ':descricao_evento' => $item['descricao_evento'],
        ':payload_estrutural' => $item['payload_estrutural'],
        ':data_evento' => $item['data_evento'],
        ':responsavel' => $item['responsavel'],
      ]);
    }

    return $this->getMovimentacoes($loteId, $companyId);
  }

  private function normalizeLotePayload(array $payload): array {
    $fornecedorId = (int)($payload['fornecedor_id'] ?? $payload['fornecedorId'] ?? 0);
    if ($fornecedorId <= 0) {
      throw new InvalidArgumentException('Fornecedor do lote é obrigatório.');
    }

    $numeroProcesso = $this->normalizeText((string)($payload['numero_processo'] ?? $payload['numeroProcesso'] ?? ''));
    if ($numeroProcesso === '') {
      throw new InvalidArgumentException('Número do processo do lote é obrigatório.');
    }

    $tituloLote = $this->normalizeText((string)($payload['titulo_lote'] ?? $payload['tituloLote'] ?? ''));
    if ($tituloLote === '') {
      throw new InvalidArgumentException('Título do lote é obrigatório.');
    }

    $tipoTransporte = $this->normalizeNullableEnum($payload['tipo_transporte'] ?? $payload['tipoTransporte'] ?? null, self::TIPOS_TRANSPORTE);
    $valorPagoCompra = $this->normalizeDecimal($payload['valor_pago_compra'] ?? $payload['valorPagoCompra'] ?? null);
    $despesasLocal = $this->normalizeDecimal($payload['despesas_local'] ?? $payload['despesasLocal'] ?? null);
    $valorFrete = $this->normalizeDecimal($payload['valor_frete'] ?? $payload['valorFrete'] ?? null);
    $valorDocumentoTransporte = $this->normalizeDecimal($payload['valor_documento_transporte'] ?? $payload['valorDocumentoTransporte'] ?? null);
    $outrosCustos = $this->normalizeDecimal($payload['outros_custos'] ?? $payload['outrosCustos'] ?? null);
    $custoTotal = $this->sumDecimals([
      $valorPagoCompra,
      $despesasLocal,
      $valorFrete,
      $valorDocumentoTransporte,
      $outrosCustos,
    ]);

    return [
      'fornecedor_id' => $fornecedorId,
      'numero_processo' => $numeroProcesso,
      'titulo_lote' => $tituloLote,
      'descricao_resumida' => $this->normalizeNullableText($payload['descricao_resumida'] ?? $payload['descricaoResumida'] ?? null),
      'descricao_operacional' => $this->normalizeNullableText($payload['descricao_operacional'] ?? $payload['descricaoOperacional'] ?? null),
      'tipo_macro_lote' => $this->normalizeNullableText($payload['tipo_macro_lote'] ?? $payload['tipoMacroLote'] ?? null),
      'data_compra' => $this->normalizeNullableDate($payload['data_compra'] ?? $payload['dataCompra'] ?? null),
      'status_macro' => $this->normalizeEnum((string)($payload['status_macro'] ?? $payload['statusMacro'] ?? 'em_transito'), self::STATUS_MACRO, 'em_transito'),
      'etapa_timeline' => $this->mapTimelineStageToDb(
        $this->normalizeEnum((string)($payload['etapa_timeline'] ?? $payload['etapaTimeline'] ?? 'compra'), self::ETAPAS_TIMELINE, 'compra')
      ),
      'observacoes_gerais' => $this->normalizeNullableText($payload['observacoes_gerais'] ?? $payload['observacoesGerais'] ?? null, false, true),
      'valor_original_lote' => $this->normalizeDecimal($payload['valor_original_lote'] ?? $payload['valorOriginalLote'] ?? null),
      'valor_depreciado' => $this->normalizeDecimal($payload['valor_depreciado'] ?? $payload['valorDepreciado'] ?? null),
      'valor_pago_compra' => $valorPagoCompra,
      'despesas_local' => $despesasLocal,
      'valor_frete' => $valorFrete,
      'valor_documento_transporte' => $valorDocumentoTransporte,
      'outros_custos' => $outrosCustos,
      'custo_total' => $custoTotal,
      'nome_local' => $this->normalizeNullableText($payload['nome_local'] ?? $payload['nomeLocal'] ?? null),
      'nome_contato' => $this->normalizeNullableText($payload['nome_contato'] ?? $payload['nomeContato'] ?? null),
      'telefone' => $this->normalizeNullableText($payload['telefone'] ?? null),
      'email' => $this->normalizeNullableText($payload['email'] ?? null),
      'endereco' => $this->normalizeNullableText($payload['endereco'] ?? null),
      'cidade' => $this->normalizeNullableText($payload['cidade'] ?? null),
      'estado' => $this->normalizeNullableText($payload['estado'] ?? null, true),
      'observacoes_local' => $this->normalizeNullableText($payload['observacoes_local'] ?? $payload['observacoesLocal'] ?? null, false, true),
      'tipo_transporte' => $tipoTransporte,
      'motorista_id' => $this->normalizeNullableInt($payload['motorista_id'] ?? $payload['motoristaId'] ?? null),
      'transportadora_id' => $this->normalizeNullableInt($payload['transportadora_id'] ?? $payload['transportadoraId'] ?? null),
      'veiculo_referencia' => $this->normalizeNullableText($payload['veiculo_referencia'] ?? $payload['veiculoReferencia'] ?? null),
      'agenciador' => $this->normalizeNullableText($payload['agenciador'] ?? null),
      'documento_transporte' => $this->normalizeNullableText($payload['documento_transporte'] ?? $payload['documentoTransporte'] ?? null),
      'data_contratacao' => $this->normalizeNullableDate($payload['data_contratacao'] ?? $payload['dataContratacao'] ?? null),
      'data_agendamento' => $this->normalizeNullableDate($payload['data_agendamento'] ?? $payload['dataAgendamento'] ?? null),
      'data_coleta' => $this->normalizeNullableDate($payload['data_coleta'] ?? $payload['dataColeta'] ?? null),
      'data_entrega' => $this->normalizeNullableDate($payload['data_entrega'] ?? $payload['dataEntrega'] ?? null),
      'observacoes_logisticas' => $this->normalizeNullableText($payload['observacoes_logisticas'] ?? $payload['observacoesLogisticas'] ?? null, false, true),
    ];
  }

  private function buildLoteStatementParams(array $data, int $companyId): array {
    return [
      ':company_id' => $companyId,
      ':fornecedor_id' => $data['fornecedor_id'],
      ':numero_processo' => $data['numero_processo'],
      ':titulo_lote' => $data['titulo_lote'],
      ':descricao_resumida' => $data['descricao_resumida'],
      ':descricao_operacional' => $data['descricao_operacional'],
      ':tipo_macro_lote' => $data['tipo_macro_lote'],
      ':data_compra' => $data['data_compra'],
      ':status_macro' => $data['status_macro'],
      ':etapa_timeline' => $data['etapa_timeline'],
      ':observacoes_gerais' => $data['observacoes_gerais'],
      ':valor_original_lote' => $data['valor_original_lote'],
      ':valor_depreciado' => $data['valor_depreciado'],
      ':valor_pago_compra' => $data['valor_pago_compra'],
      ':despesas_local' => $data['despesas_local'],
      ':valor_frete' => $data['valor_frete'],
      ':valor_documento_transporte' => $data['valor_documento_transporte'],
      ':outros_custos' => $data['outros_custos'],
      ':custo_total' => $data['custo_total'],
      ':nome_local' => $data['nome_local'],
      ':nome_contato' => $data['nome_contato'],
      ':telefone' => $data['telefone'],
      ':email' => $data['email'],
      ':endereco' => $data['endereco'],
      ':cidade' => $data['cidade'],
      ':estado' => $data['estado'],
      ':observacoes_local' => $data['observacoes_local'],
      ':tipo_transporte' => $data['tipo_transporte'],
      ':motorista_id' => $data['motorista_id'],
      ':transportadora_id' => $data['transportadora_id'],
      ':veiculo_referencia' => $data['veiculo_referencia'],
      ':agenciador' => $data['agenciador'],
      ':documento_transporte' => $data['documento_transporte'],
      ':data_contratacao' => $data['data_contratacao'],
      ':data_agendamento' => $data['data_agendamento'],
      ':data_coleta' => $data['data_coleta'],
      ':data_entrega' => $data['data_entrega'],
      ':observacoes_logisticas' => $data['observacoes_logisticas'],
    ];
  }

  private function normalizeItensPayload(array $items): array {
    $normalized = [];
    foreach ($items as $item) {
      if (!is_array($item)) {
        continue;
      }

      $descricao = $this->normalizeText((string)($item['descricao_item'] ?? $item['descricaoItem'] ?? ''));
      if ($descricao === '') {
        continue;
      }

      $quantidadeTotal = $this->normalizeDecimal($item['quantidade_total'] ?? $item['quantidadeTotal'] ?? null, 3);
      $quantidadeBaixada = $this->normalizeDecimal($item['quantidade_baixada'] ?? $item['quantidadeBaixada'] ?? null, 3);
      $quantidadeVendida = $this->normalizeDecimal($item['quantidade_vendida'] ?? $item['quantidadeVendida'] ?? null, 3);
      $quantidadeDisponivel = $this->resolveQuantidadeDisponivel(
        $item['quantidade_disponivel'] ?? $item['quantidadeDisponivel'] ?? null,
        $quantidadeTotal,
        $quantidadeBaixada,
        $quantidadeVendida
      );
      $statusItem = $this->normalizeStatusItem((string)($item['status_item'] ?? $item['statusItem'] ?? ''));
      if ((float)$quantidadeDisponivel <= 0) {
        $statusItem = 'encerrado';
      }

      $normalized[] = [
        'source_item_id' => (int)($item['source_item_id'] ?? $item['sourceItemId'] ?? $item['item_id'] ?? $item['itemId'] ?? $item['id'] ?? 0),
        'descricao_item' => $descricao,
        'tipo_controle_item' => $this->normalizeEnum((string)($item['tipo_controle_item'] ?? $item['tipoControleItem'] ?? 'unidade'), self::TIPOS_CONTROLE, 'unidade'),
        'quantidade_total' => $quantidadeTotal,
        'quantidade_disponivel' => $quantidadeDisponivel,
        'quantidade_baixada' => $quantidadeBaixada,
        'quantidade_vendida' => $quantidadeVendida,
        'custo_unitario_referencia' => $this->normalizeDecimal($item['custo_unitario_referencia'] ?? $item['custoUnitarioReferencia'] ?? null),
        'custo_total_referencia' => $this->normalizeDecimal($item['custo_total_referencia'] ?? $item['custoTotalReferencia'] ?? null),
        'valor_venda_unitario_sugerido' => $this->normalizeDecimal($item['valor_venda_unitario_sugerido'] ?? $item['valorVendaUnitarioSugerido'] ?? null),
        'valor_venda_total_sugerido' => $this->normalizeDecimal($item['valor_venda_total_sugerido'] ?? $item['valorVendaTotalSugerido'] ?? null),
        'observacoes_item' => $this->normalizeNullableText($item['observacoes_item'] ?? $item['observacoesItem'] ?? null, false, true),
        'status_item' => $statusItem,
        'tags' => (array)($item['tags'] ?? []),
      ];
    }

    return $normalized;
  }

  private function getItemArquivos(int $itemId, int $companyId): array {
    if ($itemId <= 0) {
      return [];
    }

    $arquivoRepo = new ArquivoRepository();
    return $arquivoRepo->listByEntity('lote_item', $itemId, $companyId);
  }

  private function migrateItemArquivoRelations(array $itemRelationMap, int $companyId): void {
    $stmt = Database::connection()->prepare(
      'UPDATE arquivo_relacao
          SET entidade_id = :new_item_id
        WHERE company_id = :company_id
          AND entidade = :entidade
          AND entidade_id = :old_item_id'
    );

    foreach ($itemRelationMap as $oldItemId => $newItemId) {
      $oldItemId = (int)$oldItemId;
      $newItemId = (int)$newItemId;
      if ($oldItemId <= 0 || $newItemId <= 0 || $oldItemId === $newItemId) {
        continue;
      }

      $stmt->execute([
        ':new_item_id' => $newItemId,
        ':company_id' => $companyId,
        ':entidade' => 'lote_item',
        ':old_item_id' => $oldItemId,
      ]);
    }
  }

  private function normalizeMovimentacaoPayload(array $payload): array {
    $tipoEvento = $this->normalizeText((string)($payload['tipo_evento'] ?? $payload['tipoEvento'] ?? ''));
    if ($tipoEvento === '') {
      throw new InvalidArgumentException('Tipo de evento da movimentação é obrigatório.');
    }

    $descricaoEvento = $this->normalizeText((string)($payload['descricao_evento'] ?? $payload['descricaoEvento'] ?? ''));
    if ($descricaoEvento === '') {
      throw new InvalidArgumentException('Descrição da movimentação é obrigatória.');
    }

    return [
      'tipo_evento' => $tipoEvento,
      'descricao_evento' => $descricaoEvento,
      'payload_estrutural' => $this->normalizePayloadEstrutural($payload['payload_estrutural'] ?? $payload['payloadEstrutural'] ?? null),
      'data_evento' => $this->normalizeNullableDateTime($payload['data_evento'] ?? $payload['dataEvento'] ?? null) ?? date('Y-m-d H:i:s'),
      'responsavel' => $this->normalizeNullableText($payload['responsavel'] ?? null),
    ];
  }

  private function normalizeMovimentacoesPayload(array $items): array {
    $normalized = [];
    foreach ($items as $item) {
      if (!is_array($item)) {
        continue;
      }
      $normalized[] = $this->normalizeMovimentacaoPayload($item);
    }

    return $normalized;
  }

  private function normalizePayloadEstrutural(mixed $value): ?string {
    if ($value === null || $value === '' || $value === []) {
      return null;
    }

    if (is_string($value)) {
      $decoded = json_decode($value, true);
      if (json_last_error() === JSON_ERROR_NONE) {
        return json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
      }
      return null;
    }

    if (is_array($value)) {
      return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    return null;
  }

  private function syncItemTags(int $itemId, array $tags, int $companyId): void {
    if ($itemId <= 0) {
      return;
    }

    $pdo = Database::connection();
    $deleteStmt = $pdo->prepare('DELETE FROM lote_item_tag_rel WHERE lote_item_id = :lote_item_id');
    $deleteStmt->execute([':lote_item_id' => $itemId]);

    $normalizedTags = $this->normalizeTagsPayload($tags);
    if ($normalizedTags === []) {
      return;
    }

    $findTagStmt = $pdo->prepare(
      'SELECT id
         FROM cadastro_tags
        WHERE company_id = :company_id
          AND slug = :slug
        LIMIT 1'
    );
    $insertTagStmt = $pdo->prepare(
      'INSERT INTO cadastro_tags (company_id, nome, slug, status)
       VALUES (:company_id, :nome, :slug, :status)'
    );
    $insertRelStmt = $pdo->prepare(
      'INSERT INTO lote_item_tag_rel (lote_item_id, tag_id)
       VALUES (:lote_item_id, :tag_id)'
    );

    foreach ($normalizedTags as $tag) {
      $findTagStmt->execute([
        ':company_id' => $companyId,
        ':slug' => $tag['slug'],
      ]);
      $tagId = (int)($findTagStmt->fetchColumn() ?: 0);

      if ($tagId <= 0) {
        $insertTagStmt->execute([
          ':company_id' => $companyId,
          ':nome' => $tag['nome'],
          ':slug' => $tag['slug'],
          ':status' => 'ativo',
        ]);
        $tagId = (int)$pdo->lastInsertId();
      }

      $insertRelStmt->execute([
        ':lote_item_id' => $itemId,
        ':tag_id' => $tagId,
      ]);
    }
  }

  private function hydrateTag(array $row): array {
    return [
      'id' => (int)($row['id'] ?? 0),
      'companyId' => (int)($row['company_id'] ?? 0),
      'nome' => (string)($row['nome'] ?? ''),
      'slug' => (string)($row['slug'] ?? ''),
      'status' => (string)($row['status'] ?? ''),
      'createdAt' => (string)($row['created_at'] ?? ''),
      'updatedAt' => (string)($row['updated_at'] ?? ''),
    ];
  }

  private function normalizeTagsPayload(array $tags): array {
    $items = [];
    foreach ($tags as $tag) {
      $nome = '';
      if (is_array($tag)) {
        $nome = $this->normalizeText((string)($tag['nome'] ?? $tag['label'] ?? $tag['value'] ?? ''));
      } else {
        $nome = $this->normalizeText((string)$tag);
      }

      if ($nome === '') {
        continue;
      }

      $slug = $this->slugify($nome);
      if ($slug === '') {
        continue;
      }

      $items[$slug] = [
        'nome' => $nome,
        'slug' => $slug,
      ];
    }

    return array_values($items);
  }

  private function existsLote(int $loteId, int $companyId): bool {
    $stmt = Database::connection()->prepare(
      'SELECT 1
         FROM lotes
        WHERE id = :id
          AND company_id = :company_id
        LIMIT 1'
    );
    $stmt->execute([
      ':id' => $loteId,
      ':company_id' => $companyId,
    ]);

    return (bool)$stmt->fetchColumn();
  }

  private function normalizeText(string $value): string {
    return trim(preg_replace('/\s+/', ' ', $value) ?? '');
  }

  private function mapTimelineStageToDb(string $value): string {
    $stage = $this->normalizeEnum($value, self::ETAPAS_TIMELINE, 'compra');
    return self::ETAPA_APP_TO_DB[$stage] ?? 'compra_confirmada';
  }

  private function mapTimelineStageFromDb(string $value): string {
    $stage = $this->normalizeText(strtolower($value));
    return self::ETAPA_DB_TO_APP[$stage] ?? 'compra';
  }

  private function resolveTimelineStageFromHistory(string $fallbackStage, string $statusMacro, array $movimentacoes): string {
    $currentStage = $fallbackStage !== '' ? $fallbackStage : 'compra';
    $stageKeys = self::ETAPAS_TIMELINE;

    foreach ($movimentacoes as $movimentacao) {
      if (!is_array($movimentacao)) {
        continue;
      }
      $payload = is_array($movimentacao['payloadEstrutural'] ?? null) ? (array)$movimentacao['payloadEstrutural'] : [];
      $stage = $this->normalizeEnum((string)($payload['timeline_stage'] ?? ''), self::ETAPAS_TIMELINE, '');
      $action = $this->normalizeText((string)($payload['timeline_action'] ?? ''));
      if ($stage === '' || $action === '') {
        continue;
      }

      if ($action === 'reabertura') {
        $currentStage = $stage;
        continue;
      }

      if ($action !== 'conclusao') {
        continue;
      }

      $nextStage = $this->normalizeEnum((string)($payload['next_stage'] ?? ''), self::ETAPAS_TIMELINE, '');
      if ($nextStage !== '') {
        $currentStage = $nextStage;
        continue;
      }

      $currentIndex = array_search($stage, $stageKeys, true);
      if ($currentIndex === false) {
        continue;
      }
      $currentStage = $stageKeys[$currentIndex + 1] ?? $stage;
    }

    if ($statusMacro === 'finalizado') {
      return 'finalizado';
    }

    if ($statusMacro === 'cancelado') {
      return $currentStage;
    }

    if ($statusMacro === 'em_estoque' && $currentStage === 'finalizado') {
      return 'entrega';
    }

    return $currentStage;
  }

  private function extractNumeroSinistro(?string $observacoesGerais): string {
    $text = $this->normalizeText((string)($observacoesGerais ?? ''));
    if ($text === '') {
      return '';
    }

    if (preg_match('/(?:^|\R)\s*Sinistro:\s*(.+)$/imu', $text, $matches)) {
      return $this->normalizeText((string)($matches[1] ?? ''));
    }

    return '';
  }

  private function findDuplicateReference(string $numeroProcesso, string $numeroSinistro, int $companyId, ?int $ignoreId = null): ?array {
    $numeroProcesso = $this->normalizeText($numeroProcesso);
    $numeroSinistro = $this->normalizeText($numeroSinistro);
    if ($numeroProcesso === '' && $numeroSinistro === '') {
      return null;
    }

    $where = ['company_id = :company_id'];
    $params = [':company_id' => $companyId];

    if ($ignoreId !== null && $ignoreId > 0) {
      $where[] = 'id <> :ignore_id';
      $params[':ignore_id'] = $ignoreId;
    }

    $referenceWhere = [];
    if ($numeroProcesso !== '') {
      $referenceWhere[] = 'numero_processo = :numero_processo';
      $params[':numero_processo'] = $numeroProcesso;
    }
    if ($numeroSinistro !== '') {
      $referenceWhere[] = 'observacoes_gerais LIKE :numero_sinistro';
      $params[':numero_sinistro'] = '%Sinistro: ' . $numeroSinistro . '%';
    }

    if ($referenceWhere === []) {
      return null;
    }

    $sql = sprintf(
      'SELECT id, numero_processo, observacoes_gerais
         FROM lotes
        WHERE %s
          AND (%s)
        ORDER BY id DESC
        LIMIT 1',
      implode(' AND ', $where),
      implode(' OR ', $referenceWhere)
    );

    $stmt = Database::connection()->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return is_array($row) && $row ? $row : null;
  }

  private function assertNoDuplicateReference(string $numeroProcesso, string $numeroSinistro, int $companyId, ?int $ignoreId = null): void {
    $duplicate = $this->findDuplicateReference($numeroProcesso, $numeroSinistro, $companyId, $ignoreId);
    if (!is_array($duplicate)) {
      return;
    }

    $duplicateProcesso = $this->normalizeText((string)($duplicate['numero_processo'] ?? ''));
    $duplicateSinistro = $this->extractNumeroSinistro((string)($duplicate['observacoes_gerais'] ?? ''));
    $processo = $this->normalizeText($numeroProcesso);
    $sinistro = $this->normalizeText($numeroSinistro);

    if ($processo !== '' && $duplicateProcesso !== '' && strcasecmp($processo, $duplicateProcesso) === 0) {
      throw new InvalidArgumentException('Já existe um lote cadastrado com este número de processo.');
    }

    if ($sinistro !== '' && $duplicateSinistro !== '' && strcasecmp($sinistro, $duplicateSinistro) === 0) {
      throw new InvalidArgumentException('Já existe um lote cadastrado com este número de sinistro.');
    }

    throw new InvalidArgumentException('Já existe um lote cadastrado com esta referência.');
  }

  private function normalizeNullableText(mixed $value, bool $uppercase = false, bool $preserveLineBreaks = false): ?string {
    $raw = (string)($value ?? '');
    if ($preserveLineBreaks) {
      $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];
      $kept = [];
      foreach ($lines as $line) {
        $line = $this->normalizeText((string)$line);
        if ($line === '') {
          continue;
        }
        $kept[] = $uppercase ? mb_strtoupper($line, 'UTF-8') : $line;
      }
      $text = implode("\n", $kept);
    } else {
      $text = $this->normalizeText($raw);
    }

    if ($text === '') {
      return null;
    }

    return $uppercase ? mb_strtoupper($text, 'UTF-8') : $text;
  }

  private function normalizeDecimal(mixed $value, int $scale = 2): string {
    if ($value === null || $value === '') {
      return number_format(0, $scale, '.', '');
    }

    $normalized = str_replace(',', '.', preg_replace('/[^0-9,.\-]/', '', (string)$value) ?? '');
    $number = is_numeric($normalized) ? (float)$normalized : 0.0;
    return number_format($number, $scale, '.', '');
  }

  private function sumDecimals(array $values, int $scale = 2): string {
    $total = 0.0;
    foreach ($values as $value) {
      $total += (float)$value;
    }

    return number_format($total, $scale, '.', '');
  }

  private function resolveQuantidadeDisponivel(mixed $value, string $quantidadeTotal, string $quantidadeBaixada, string $quantidadeVendida): string {
    $raw = $this->normalizeText((string)($value ?? ''));
    if ($raw !== '') {
      return $this->normalizeDecimal($raw, 3);
    }

    $disponivel = (float)$quantidadeTotal - (float)$quantidadeBaixada - (float)$quantidadeVendida;
    return number_format(max(0, $disponivel), 3, '.', '');
  }

  private function normalizeNullableInt(mixed $value): ?int {
    $int = (int)($value ?? 0);
    return $int > 0 ? $int : null;
  }

  private function normalizeNullableDate(mixed $value): ?string {
    $text = $this->normalizeText((string)($value ?? ''));
    if ($text === '') {
      return null;
    }

    $ts = strtotime($text);
    return $ts !== false ? date('Y-m-d', $ts) : null;
  }

  private function normalizeNullableDateTime(mixed $value): ?string {
    $text = $this->normalizeText((string)($value ?? ''));
    if ($text === '') {
      return null;
    }

    $ts = strtotime($text);
    return $ts !== false ? date('Y-m-d H:i:s', $ts) : null;
  }

  private function normalizeEnum(string $value, array $allowed, string $default): string {
    $value = $this->normalizeText(strtolower($value));
    return in_array($value, $allowed, true) ? $value : $default;
  }

  private function normalizeNullableEnum(mixed $value, array $allowed): ?string {
    $text = $this->normalizeText(strtolower((string)($value ?? '')));
    if ($text === '') {
      return null;
    }

    return in_array($text, $allowed, true) ? $text : null;
  }

  private function normalizeStatusItem(string $value): string {
    $status = strtolower($this->normalizeText($value));
    return $status !== '' ? $status : 'ativo';
  }

  private function normalizeLimit(mixed $value): int {
    $limit = (int)$value;
    if ($limit <= 0) {
      return 50;
    }

    return min($limit, 200);
  }

  private function normalizeOffset(mixed $value): int {
    $offset = (int)$value;
    return max(0, $offset);
  }

  private function slugify(string $value): string {
    $text = strtolower($this->normalizeText($value));
    if ($text === '') {
      return '';
    }

    $map = [
      'a' => '/[áàãâä]/u',
      'e' => '/[éèêë]/u',
      'i' => '/[íìîï]/u',
      'o' => '/[óòõôö]/u',
      'u' => '/[úùûü]/u',
      'c' => '/[ç]/u',
      'n' => '/[ñ]/u',
    ];

    foreach ($map as $replace => $pattern) {
      $text = preg_replace($pattern, $replace, $text) ?? $text;
    }

    $text = preg_replace('/[^a-z0-9]+/u', '-', $text) ?? '';
    return trim($text, '-');
  }
}
