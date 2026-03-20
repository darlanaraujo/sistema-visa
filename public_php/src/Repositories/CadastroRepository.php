<?php
declare(strict_types=1);

require_once __DIR__ . '/../Support/Database.php';

final class CadastroRepository {
  public function findByDocumento(string $documento, int $companyId = 1): ?array {
    $normalizedDocumento = $this->normalizeDocumento($documento);
    if ($normalizedDocumento === '') {
      return null;
    }

    $stmt = Database::connection()->prepare(
      'SELECT c.*
         FROM cadastros c
        WHERE c.company_id = :company_id
          AND REPLACE(REPLACE(REPLACE(REPLACE(c.documento, ".", ""), "-", ""), "/", ""), " ", "") = :documento
        LIMIT 1'
    );
    $stmt->execute([
      ':company_id' => $companyId,
      ':documento' => $normalizedDocumento,
    ]);

    $row = $stmt->fetch();
    if (!is_array($row) || !$row) {
      return null;
    }

    return $this->hydrateCadastro($row, true);
  }

  public function searchByNome(string $term, int $companyId = 1, array $filters = []): array {
    $term = $this->normalizeText($term);
    return $this->list([
      'term' => $term,
      'status' => $filters['status'] ?? null,
      'limit' => $filters['limit'] ?? 20,
      'offset' => $filters['offset'] ?? 0,
    ], $companyId);
  }

  public function findById(int $id, int $companyId = 1): ?array {
    if ($id <= 0) {
      return null;
    }

    $stmt = Database::connection()->prepare(
      'SELECT c.*
         FROM cadastros c
        WHERE c.id = :id
          AND c.company_id = :company_id
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

    return $this->hydrateCadastro($row, true);
  }

  public function list(array $filters = [], int $companyId = 1): array {
    $params = [':company_id' => $companyId];
    $where = ['c.company_id = :company_id'];

    $status = $this->normalizeText((string)($filters['status'] ?? ''));
    if ($status !== '') {
      $where[] = 'c.status = :status';
      $params[':status'] = $status;
    }

    $term = $this->normalizeText((string)($filters['term'] ?? ''));
    if ($term !== '') {
      $normalizedDocumento = $this->normalizeDocumento($term);
      $where[] = '(
        c.nome LIKE :term
        OR REPLACE(REPLACE(REPLACE(REPLACE(c.documento, ".", ""), "-", ""), "/", ""), " ", "") LIKE :documento_term
      )';
      $params[':term'] = '%' . $term . '%';
      $params[':documento_term'] = '%' . $normalizedDocumento . '%';
    }

    $tipo = $this->normalizeText((string)($filters['tipo'] ?? ''));
    if ($tipo !== '') {
      $where[] = 'EXISTS (
        SELECT 1
          FROM cadastro_tipo_rel rel
          INNER JOIN cadastro_tipos t
                  ON t.id = rel.tipo_id
         WHERE rel.cadastro_id = c.id
           AND t.slug = :tipo
      )';
      $params[':tipo'] = $tipo;
    }

    $limit = $this->normalizeLimit($filters['limit'] ?? 20);
    $offset = $this->normalizeOffset($filters['offset'] ?? 0);

    $sql = sprintf(
      'SELECT c.*
         FROM cadastros c
        WHERE %s
        ORDER BY c.nome ASC, c.id DESC
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
      $items[] = $this->hydrateCadastro($row, false);
    }

    return $items;
  }

  public function create(array $payload, int $companyId = 1): array {
    $data = $this->normalizeCadastroPayload($payload);
    $pdo = Database::connection();
    $ownsTransaction = !$pdo->inTransaction();
    if ($ownsTransaction) {
      $pdo->beginTransaction();
    }

    try {
      $stmt = $pdo->prepare(
        'INSERT INTO cadastros (
           company_id,
           tipo_pessoa,
           nome,
           razao_social,
           nome_fantasia,
           documento,
           inscricao_estadual,
           telefone,
           telefone_secundario,
           contato,
           telefone_fixo,
           whatsapp,
           celular,
           email,
           cep,
           endereco,
           numero,
           complemento,
           bairro,
           cidade,
           estado,
           observacoes,
           status
         ) VALUES (
           :company_id,
           :tipo_pessoa,
           :nome,
           :razao_social,
           :nome_fantasia,
           :documento,
           :inscricao_estadual,
           :telefone,
           :telefone_secundario,
           :contato,
           :telefone_fixo,
           :whatsapp,
           :celular,
           :email,
           :cep,
           :endereco,
           :numero,
           :complemento,
           :bairro,
           :cidade,
           :estado,
           :observacoes,
           :status
         )'
      );

      $stmt->execute([
        ':company_id' => $companyId,
        ':tipo_pessoa' => $data['tipo_pessoa'],
        ':nome' => $data['nome'],
        ':razao_social' => $data['razao_social'],
        ':nome_fantasia' => $data['nome_fantasia'],
        ':documento' => $data['documento'],
        ':inscricao_estadual' => $data['inscricao_estadual'],
        ':telefone' => $data['telefone'],
        ':telefone_secundario' => $data['telefone_secundario'],
        ':contato' => $data['contato'],
        ':telefone_fixo' => $data['telefone_fixo'],
        ':whatsapp' => $data['whatsapp'],
        ':celular' => $data['celular'],
        ':email' => $data['email'],
        ':cep' => $data['cep'],
        ':endereco' => $data['endereco'],
        ':numero' => $data['numero'],
        ':complemento' => $data['complemento'],
        ':bairro' => $data['bairro'],
        ':cidade' => $data['cidade'],
        ':estado' => $data['estado'],
        ':observacoes' => $data['observacoes'],
        ':status' => $data['status'],
      ]);

      $cadastroId = (int)$pdo->lastInsertId();
      $this->syncExtendedStructures($cadastroId, $payload, $companyId);

      if ($ownsTransaction && $pdo->inTransaction()) {
        $pdo->commit();
      }

      return $this->findById($cadastroId, $companyId) ?? [];
    } catch (Throwable $e) {
      if ($ownsTransaction && $pdo->inTransaction()) {
        $pdo->rollBack();
      }
      throw $e;
    }
  }

  public function update(int $id, array $payload, int $companyId = 1): ?array {
    if ($id <= 0 || $this->findById($id, $companyId) === null) {
      return null;
    }

    $data = $this->normalizeCadastroPayload($payload);

    $pdo = Database::connection();
    $ownsTransaction = !$pdo->inTransaction();
    if ($ownsTransaction) {
      $pdo->beginTransaction();
    }

    try {
      $stmt = $pdo->prepare(
        'UPDATE cadastros
            SET tipo_pessoa = :tipo_pessoa,
                nome = :nome,
                razao_social = :razao_social,
                nome_fantasia = :nome_fantasia,
                documento = :documento,
                inscricao_estadual = :inscricao_estadual,
                telefone = :telefone,
                telefone_secundario = :telefone_secundario,
                contato = :contato,
                telefone_fixo = :telefone_fixo,
                whatsapp = :whatsapp,
                celular = :celular,
                email = :email,
                cep = :cep,
                endereco = :endereco,
                numero = :numero,
                complemento = :complemento,
                bairro = :bairro,
                cidade = :cidade,
                estado = :estado,
                observacoes = :observacoes,
                status = :status,
                updated_at = CURRENT_TIMESTAMP
          WHERE id = :id
            AND company_id = :company_id'
      );

      $stmt->execute([
        ':id' => $id,
        ':company_id' => $companyId,
        ':tipo_pessoa' => $data['tipo_pessoa'],
        ':nome' => $data['nome'],
        ':razao_social' => $data['razao_social'],
        ':nome_fantasia' => $data['nome_fantasia'],
        ':documento' => $data['documento'],
        ':inscricao_estadual' => $data['inscricao_estadual'],
        ':telefone' => $data['telefone'],
        ':telefone_secundario' => $data['telefone_secundario'],
        ':contato' => $data['contato'],
        ':telefone_fixo' => $data['telefone_fixo'],
        ':whatsapp' => $data['whatsapp'],
        ':celular' => $data['celular'],
        ':email' => $data['email'],
        ':cep' => $data['cep'],
        ':endereco' => $data['endereco'],
        ':numero' => $data['numero'],
        ':complemento' => $data['complemento'],
        ':bairro' => $data['bairro'],
        ':cidade' => $data['cidade'],
        ':estado' => $data['estado'],
        ':observacoes' => $data['observacoes'],
        ':status' => $data['status'],
      ]);

      $this->syncExtendedStructures($id, $payload, $companyId);

      if ($ownsTransaction && $pdo->inTransaction()) {
        $pdo->commit();
      }

      return $this->findById($id, $companyId);
    } catch (Throwable $e) {
      if ($ownsTransaction && $pdo->inTransaction()) {
        $pdo->rollBack();
      }
      throw $e;
    }
  }

  public function getMotoristaDetalhes(int $cadastroId, int $companyId = 1): ?array {
    if ($cadastroId <= 0 || !$this->existsCadastro($cadastroId, $companyId)) {
      return null;
    }

    $stmt = Database::connection()->prepare(
      'SELECT d.id, d.cadastro_id, d.cpf, d.cnh, d.created_at, d.updated_at
         FROM cadastro_motorista_detalhes d
         INNER JOIN cadastros c
                 ON c.id = d.cadastro_id
        WHERE d.cadastro_id = :cadastro_id
          AND c.company_id = :company_id
        LIMIT 1'
    );
    $stmt->execute([
      ':cadastro_id' => $cadastroId,
      ':company_id' => $companyId,
    ]);

    $row = $stmt->fetch();
    if (!is_array($row) || !$row) {
      return null;
    }

    return [
      'id' => (int)($row['id'] ?? 0),
      'cadastroId' => (int)($row['cadastro_id'] ?? 0),
      'cpf' => (string)($row['cpf'] ?? ''),
      'cnh' => (string)($row['cnh'] ?? ''),
      'createdAt' => (string)($row['created_at'] ?? ''),
      'updatedAt' => (string)($row['updated_at'] ?? ''),
    ];
  }

  public function saveMotoristaDetalhes(int $cadastroId, array $payload, int $companyId = 1): ?array {
    if ($cadastroId <= 0 || !$this->existsCadastro($cadastroId, $companyId)) {
      return null;
    }

    $data = $this->normalizeMotoristaDetalhesPayload($payload);
    if ($data['cnh'] === null && $data['cpf'] === null) {
      $stmt = Database::connection()->prepare(
        'DELETE d
           FROM cadastro_motorista_detalhes d
           INNER JOIN cadastros c
                   ON c.id = d.cadastro_id
          WHERE d.cadastro_id = :cadastro_id
            AND c.company_id = :company_id'
      );
      $stmt->execute([
        ':cadastro_id' => $cadastroId,
        ':company_id' => $companyId,
      ]);

      return null;
    }

    $stmt = Database::connection()->prepare(
      'INSERT INTO cadastro_motorista_detalhes (cadastro_id, cpf, cnh)
       VALUES (:cadastro_id, :cpf, :cnh)
       ON DUPLICATE KEY UPDATE
         cpf = VALUES(cpf),
         cnh = VALUES(cnh),
         updated_at = CURRENT_TIMESTAMP'
    );
    $stmt->execute([
      ':cadastro_id' => $cadastroId,
      ':cpf' => $data['cpf'],
      ':cnh' => $data['cnh'],
    ]);

    return $this->getMotoristaDetalhes($cadastroId, $companyId);
  }

  public function getMotoristasVinculados(int $cadastroId, int $companyId = 1): array {
    if ($cadastroId <= 0 || !$this->existsCadastro($cadastroId, $companyId)) {
      return [];
    }

    $stmt = Database::connection()->prepare(
      'SELECT v.*
         FROM cadastro_motoristas_vinculados v
         INNER JOIN cadastros c
                 ON c.id = v.cadastro_id
        WHERE v.cadastro_id = :cadastro_id
          AND c.company_id = :company_id
        ORDER BY v.principal DESC, v.id ASC'
    );
    $stmt->execute([
      ':cadastro_id' => $cadastroId,
      ':company_id' => $companyId,
    ]);

    $rows = $stmt->fetchAll();
    if (!is_array($rows) || !$rows) {
      return [];
    }

    return array_values(array_filter(array_map(
      fn ($row) => is_array($row) ? $this->hydrateMotoristaVinculado($row) : null,
      $rows
    )));
  }

  public function replaceMotoristasVinculados(int $cadastroId, array $items, int $companyId = 1): array {
    if ($cadastroId <= 0 || !$this->existsCadastro($cadastroId, $companyId)) {
      return [];
    }

    $pdo = Database::connection();
    $deleteStmt = $pdo->prepare(
      'DELETE v
         FROM cadastro_motoristas_vinculados v
         INNER JOIN cadastros c
                 ON c.id = v.cadastro_id
        WHERE v.cadastro_id = :cadastro_id
          AND c.company_id = :company_id'
    );
    $deleteStmt->execute([
      ':cadastro_id' => $cadastroId,
      ':company_id' => $companyId,
    ]);

    $insertStmt = $pdo->prepare(
      'INSERT INTO cadastro_motoristas_vinculados (
         cadastro_id,
         nome,
         cpf,
         cnh,
         contato,
         telefone_fixo,
         whatsapp,
         celular,
         email,
         principal
       ) VALUES (
         :cadastro_id,
         :nome,
         :cpf,
         :cnh,
         :contato,
         :telefone_fixo,
         :whatsapp,
         :celular,
         :email,
         :principal
       )'
    );

    foreach ($this->normalizeMotoristasVinculadosPayload($items) as $item) {
      $insertStmt->execute([
        ':cadastro_id' => $cadastroId,
        ':nome' => $item['nome'],
        ':cpf' => $item['cpf'],
        ':cnh' => $item['cnh'],
        ':contato' => $item['contato'],
        ':telefone_fixo' => $item['telefone_fixo'],
        ':whatsapp' => $item['whatsapp'],
        ':celular' => $item['celular'],
        ':email' => $item['email'],
        ':principal' => $item['principal'],
      ]);
    }

    return $this->getMotoristasVinculados($cadastroId, $companyId);
  }

  public function getVeiculos(int $cadastroId, int $companyId = 1): array {
    if ($cadastroId <= 0 || !$this->existsCadastro($cadastroId, $companyId)) {
      return [];
    }

    $stmt = Database::connection()->prepare(
      'SELECT v.*
         FROM cadastro_veiculos v
         INNER JOIN cadastros c
                 ON c.id = v.cadastro_id
        WHERE v.cadastro_id = :cadastro_id
          AND c.company_id = :company_id
        ORDER BY v.id ASC'
    );
    $stmt->execute([
      ':cadastro_id' => $cadastroId,
      ':company_id' => $companyId,
    ]);

    $rows = $stmt->fetchAll();
    if (!is_array($rows) || !$rows) {
      return [];
    }

    return array_values(array_filter(array_map(
      fn ($row) => is_array($row) ? $this->hydrateVeiculo($row) : null,
      $rows
    )));
  }

  public function replaceVeiculos(int $cadastroId, array $items, int $companyId = 1): array {
    if ($cadastroId <= 0 || !$this->existsCadastro($cadastroId, $companyId)) {
      return [];
    }

    $pdo = Database::connection();
    $deleteStmt = $pdo->prepare(
      'DELETE v
         FROM cadastro_veiculos v
         INNER JOIN cadastros c
                 ON c.id = v.cadastro_id
        WHERE v.cadastro_id = :cadastro_id
          AND c.company_id = :company_id'
    );
    $deleteStmt->execute([
      ':cadastro_id' => $cadastroId,
      ':company_id' => $companyId,
    ]);

    $insertStmt = $pdo->prepare(
      'INSERT INTO cadastro_veiculos (
         cadastro_id,
         modelo,
         placa,
         placa_adicional,
         tipo_carroceria,
         metragem,
         peso_carga
       ) VALUES (
         :cadastro_id,
         :modelo,
         :placa,
         :placa_adicional,
         :tipo_carroceria,
         :metragem,
         :peso_carga
       )'
    );

    foreach ($this->normalizeVeiculosPayload($items) as $item) {
      $insertStmt->execute([
        ':cadastro_id' => $cadastroId,
        ':modelo' => $item['modelo'],
        ':placa' => $item['placa'],
        ':placa_adicional' => $item['placa_adicional'],
        ':tipo_carroceria' => $item['tipo_carroceria'],
        ':metragem' => $item['metragem'],
        ':peso_carga' => $item['peso_carga'],
      ]);
    }

    return $this->getVeiculos($cadastroId, $companyId);
  }

  public function getTags(int $cadastroId, int $companyId = 1): array {
    if ($cadastroId <= 0 || !$this->existsCadastro($cadastroId, $companyId)) {
      return [];
    }

    $stmt = Database::connection()->prepare(
      'SELECT t.id, t.company_id, t.nome, t.slug, t.status, t.created_at, t.updated_at
         FROM cadastro_tag_rel rel
         INNER JOIN cadastro_tags t
                 ON t.id = rel.tag_id
         INNER JOIN cadastros c
                 ON c.id = rel.cadastro_id
        WHERE rel.cadastro_id = :cadastro_id
          AND c.company_id = :company_id
        ORDER BY t.nome ASC, t.id ASC'
    );
    $stmt->execute([
      ':cadastro_id' => $cadastroId,
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

  public function syncTags(int $cadastroId, array $tags, int $companyId = 1): array {
    if ($cadastroId <= 0 || !$this->existsCadastro($cadastroId, $companyId)) {
      return [];
    }

    $normalizedTags = $this->normalizeTagsPayload($tags);
    $pdo = Database::connection();

    $deleteStmt = $pdo->prepare(
      'DELETE rel
         FROM cadastro_tag_rel rel
         INNER JOIN cadastros c
                 ON c.id = rel.cadastro_id
        WHERE rel.cadastro_id = :cadastro_id
          AND c.company_id = :company_id'
    );
    $deleteStmt->execute([
      ':cadastro_id' => $cadastroId,
      ':company_id' => $companyId,
    ]);

    if ($normalizedTags === []) {
      return [];
    }

    $upsertTagStmt = $pdo->prepare(
      'INSERT INTO cadastro_tags (company_id, nome, slug, status)
       VALUES (:company_id, :nome, :slug, :status)
       ON DUPLICATE KEY UPDATE
         nome = VALUES(nome),
         status = VALUES(status),
         updated_at = CURRENT_TIMESTAMP'
    );

    $findTagStmt = $pdo->prepare(
      'SELECT id
         FROM cadastro_tags
        WHERE company_id = :company_id
          AND slug = :slug
        LIMIT 1'
    );

    $insertRelStmt = $pdo->prepare(
      'INSERT IGNORE INTO cadastro_tag_rel (cadastro_id, tag_id)
       VALUES (:cadastro_id, :tag_id)'
    );

    foreach ($normalizedTags as $tag) {
      $upsertTagStmt->execute([
        ':company_id' => $companyId,
        ':nome' => $tag['nome'],
        ':slug' => $tag['slug'],
        ':status' => 'ativo',
      ]);

      $findTagStmt->execute([
        ':company_id' => $companyId,
        ':slug' => $tag['slug'],
      ]);
      $tagId = (int)$findTagStmt->fetchColumn();
      if ($tagId <= 0) {
        continue;
      }

      $insertRelStmt->execute([
        ':cadastro_id' => $cadastroId,
        ':tag_id' => $tagId,
      ]);
    }

    return $this->getTags($cadastroId, $companyId);
  }

  public function tagsMatch(string $left, string $right, int $prefixLength = 4): bool {
    $leftTokens = $this->normalizeTagComparableTokens($left);
    $rightTokens = $this->normalizeTagComparableTokens($right);

    if ($leftTokens === [] || $rightTokens === []) {
      return false;
    }

    foreach ($leftTokens as $leftToken) {
      foreach ($rightTokens as $rightToken) {
        if ($leftToken === $rightToken) {
          return true;
        }

        $sharedPrefix = $this->sharedPrefixLength($leftToken, $rightToken);
        $requiredPrefix = $this->resolveComparablePrefixLength($leftToken, $rightToken, $prefixLength);
        if ($sharedPrefix >= $requiredPrefix) {
          return true;
        }
      }
    }

    return false;
  }

  public function findMatchingTags(array $sourceTags, array $candidateTags, int $prefixLength = 4): array {
    $matches = [];
    foreach ($sourceTags as $sourceTag) {
      $sourceName = is_array($sourceTag)
        ? (string)($sourceTag['nome'] ?? $sourceTag['label'] ?? $sourceTag['value'] ?? '')
        : (string)$sourceTag;
      $sourceName = $this->normalizeText($sourceName);
      if ($sourceName === '') {
        continue;
      }

      foreach ($candidateTags as $candidateTag) {
        $candidateName = is_array($candidateTag)
          ? (string)($candidateTag['nome'] ?? $candidateTag['label'] ?? $candidateTag['value'] ?? '')
          : (string)$candidateTag;
        $candidateName = $this->normalizeText($candidateName);
        if ($candidateName === '') {
          continue;
        }

        if (!$this->tagsMatch($sourceName, $candidateName, $prefixLength)) {
          continue;
        }

        $key = $this->slugify($sourceName) . '::' . $this->slugify($candidateName);
        $matches[$key] = [
          'source' => $sourceName,
          'candidate' => $candidateName,
        ];
      }
    }

    return array_values($matches);
  }

  public function getTipos(int $cadastroId, int $companyId = 1): array {
    if ($cadastroId <= 0) {
      return [];
    }

    $stmt = Database::connection()->prepare(
      'SELECT t.id, t.slug, t.nome, t.status
         FROM cadastro_tipo_rel rel
         INNER JOIN cadastro_tipos t
                 ON t.id = rel.tipo_id
         INNER JOIN cadastros c
                 ON c.id = rel.cadastro_id
        WHERE rel.cadastro_id = :cadastro_id
          AND c.company_id = :company_id
        ORDER BY t.nome ASC, t.id ASC'
    );
    $stmt->execute([
      ':cadastro_id' => $cadastroId,
      ':company_id' => $companyId,
    ]);

    $rows = $stmt->fetchAll();
    if (!is_array($rows) || !$rows) {
      return [];
    }

    $items = [];
    foreach ($rows as $row) {
      if (!is_array($row)) {
        continue;
      }
      $items[] = $this->hydrateTipo($row);
    }

    return $items;
  }

  public function listTipos(bool $onlyActive = true): array {
    $sql = 'SELECT t.id, t.slug, t.nome, t.status
              FROM cadastro_tipos t';

    if ($onlyActive) {
      $sql .= ' WHERE t.status = :status';
    }

    $sql .= ' ORDER BY t.nome ASC, t.id ASC';

    $stmt = Database::connection()->prepare($sql);
    $params = $onlyActive ? [':status' => 'ativo'] : [];
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
      $items[] = $this->hydrateTipo($row);
    }

    return $items;
  }

  public function attachTipos(int $cadastroId, array $tipoIds, int $companyId = 1): array {
    if ($cadastroId <= 0 || !$this->existsCadastro($cadastroId, $companyId)) {
      return [];
    }

    $ids = $this->normalizeTipoIds($tipoIds);
    if ($ids === []) {
      return $this->getTipos($cadastroId, $companyId);
    }

    $pdo = Database::connection();
    $stmt = $pdo->prepare(
      'INSERT IGNORE INTO cadastro_tipo_rel (cadastro_id, tipo_id)
       VALUES (:cadastro_id, :tipo_id)'
    );

    $ownsTransaction = !$pdo->inTransaction();
    if ($ownsTransaction) {
      $pdo->beginTransaction();
    }
    try {
      foreach ($ids as $tipoId) {
        if (!$this->existsTipo($tipoId)) {
          continue;
        }
        $stmt->execute([
          ':cadastro_id' => $cadastroId,
          ':tipo_id' => $tipoId,
        ]);
      }
      if ($ownsTransaction && $pdo->inTransaction()) {
        $pdo->commit();
      }
    } catch (Throwable $e) {
      if ($ownsTransaction && $pdo->inTransaction()) {
        $pdo->rollBack();
      }
      throw $e;
    }

    return $this->getTipos($cadastroId, $companyId);
  }

  public function detachTipos(int $cadastroId, array $tipoIds, int $companyId = 1): bool {
    if ($cadastroId <= 0 || !$this->existsCadastro($cadastroId, $companyId)) {
      return false;
    }

    $ids = $this->normalizeTipoIds($tipoIds);
    if ($ids === []) {
      return false;
    }

    $placeholders = implode(', ', array_fill(0, count($ids), '?'));
    $sql = sprintf(
      'DELETE rel
         FROM cadastro_tipo_rel rel
         INNER JOIN cadastros c
                 ON c.id = rel.cadastro_id
        WHERE rel.cadastro_id = ?
          AND c.company_id = ?
          AND rel.tipo_id IN (%s)',
      $placeholders
    );

    $params = array_merge([$cadastroId, $companyId], $ids);
    $stmt = Database::connection()->prepare($sql);
    $stmt->execute($params);

    return $stmt->rowCount() > 0;
  }

  public function replaceTipos(int $cadastroId, array $tipoIds, int $companyId = 1): array {
    if ($cadastroId <= 0 || !$this->existsCadastro($cadastroId, $companyId)) {
      return [];
    }

    $ids = $this->normalizeTipoIds($tipoIds);
    $pdo = Database::connection();

    $ownsTransaction = !$pdo->inTransaction();
    if ($ownsTransaction) {
      $pdo->beginTransaction();
    }
    try {
      $deleteStmt = $pdo->prepare(
        'DELETE rel
           FROM cadastro_tipo_rel rel
           INNER JOIN cadastros c
                   ON c.id = rel.cadastro_id
          WHERE rel.cadastro_id = :cadastro_id
            AND c.company_id = :company_id'
      );
      $deleteStmt->execute([
        ':cadastro_id' => $cadastroId,
        ':company_id' => $companyId,
      ]);

      if ($ids !== []) {
        $insertStmt = $pdo->prepare(
          'INSERT IGNORE INTO cadastro_tipo_rel (cadastro_id, tipo_id)
           VALUES (:cadastro_id, :tipo_id)'
        );

        foreach ($ids as $tipoId) {
          if (!$this->existsTipo($tipoId)) {
            continue;
          }
          $insertStmt->execute([
            ':cadastro_id' => $cadastroId,
            ':tipo_id' => $tipoId,
          ]);
        }
      }

      if ($ownsTransaction && $pdo->inTransaction()) {
        $pdo->commit();
      }
    } catch (Throwable $e) {
      if ($ownsTransaction && $pdo->inTransaction()) {
        $pdo->rollBack();
      }
      throw $e;
    }

    return $this->getTipos($cadastroId, $companyId);
  }

  private function hydrateCadastro(array $row, bool $includeRelations = false): array {
    $id = (int)($row['id'] ?? 0);
    $companyId = (int)($row['company_id'] ?? 1);
    $legacyTelefone = (string)($row['telefone'] ?? '');
    $legacyTelefoneSecundario = (string)($row['telefone_secundario'] ?? '');
    $telefoneFixo = (string)($row['telefone_fixo'] ?? '');
    $whatsapp = (string)($row['whatsapp'] ?? '');
    $celular = (string)($row['celular'] ?? '');

    $payload = [
      'id' => $id,
      'companyId' => $companyId,
      'tipoPessoa' => (string)($row['tipo_pessoa'] ?? ''),
      'nome' => (string)($row['nome'] ?? ''),
      'razaoSocial' => (string)($row['razao_social'] ?? ''),
      'nomeFantasia' => (string)($row['nome_fantasia'] ?? ''),
      'documento' => (string)($row['documento'] ?? ''),
      'inscricaoEstadual' => (string)($row['inscricao_estadual'] ?? ''),
      'contato' => (string)($row['contato'] ?? ''),
      'telefone' => $legacyTelefone !== '' ? $legacyTelefone : ($celular !== '' ? $celular : ($whatsapp !== '' ? $whatsapp : $telefoneFixo)),
      'telefoneSecundario' => $legacyTelefoneSecundario !== '' ? $legacyTelefoneSecundario : ($whatsapp !== '' ? $whatsapp : $telefoneFixo),
      'telefoneFixo' => $telefoneFixo,
      'whatsapp' => $whatsapp,
      'celular' => $celular !== '' ? $celular : $legacyTelefone,
      'email' => (string)($row['email'] ?? ''),
      'cep' => (string)($row['cep'] ?? ''),
      'endereco' => (string)($row['endereco'] ?? ''),
      'numero' => (string)($row['numero'] ?? ''),
      'complemento' => (string)($row['complemento'] ?? ''),
      'bairro' => (string)($row['bairro'] ?? ''),
      'cidade' => (string)($row['cidade'] ?? ''),
      'estado' => (string)($row['estado'] ?? ''),
      'observacoes' => (string)($row['observacoes'] ?? ''),
      'status' => (string)($row['status'] ?? ''),
      'createdAt' => (string)($row['created_at'] ?? ''),
      'updatedAt' => (string)($row['updated_at'] ?? ''),
      'tipos' => $id > 0 ? $this->getTipos($id, $companyId) : [],
    ];

    if ($includeRelations && $id > 0) {
      $payload['motoristaDetalhes'] = $this->getMotoristaDetalhes($id, $companyId);
      $payload['motoristasVinculados'] = $this->getMotoristasVinculados($id, $companyId);
      $payload['veiculos'] = $this->getVeiculos($id, $companyId);
      $payload['tags'] = $this->getTags($id, $companyId);
    }

    return $payload;
  }

  private function hydrateTipo(array $row): array {
    return [
      'id' => (int)($row['id'] ?? 0),
      'slug' => (string)($row['slug'] ?? ''),
      'nome' => (string)($row['nome'] ?? ''),
      'status' => (string)($row['status'] ?? ''),
    ];
  }

  private function normalizeCadastroPayload(array $payload): array {
    $tipoPessoa = strtoupper($this->normalizeText((string)($payload['tipo_pessoa'] ?? $payload['tipoPessoa'] ?? '')));
    if ($tipoPessoa !== 'PJ') {
      $tipoPessoa = 'PF';
    }

    $nomeInformado = $this->normalizeText((string)($payload['nome'] ?? ''));
    $razaoSocialInformada = $this->normalizeText((string)($payload['razao_social'] ?? $payload['razaoSocial'] ?? ''));
    $nome = $tipoPessoa === 'PJ'
      ? ($nomeInformado !== '' ? $nomeInformado : $razaoSocialInformada)
      : ($nomeInformado !== '' ? $nomeInformado : $razaoSocialInformada);
    $razaoSocial = $tipoPessoa === 'PJ'
      ? ($razaoSocialInformada !== '' ? $razaoSocialInformada : ($nome !== '' ? $nome : null))
      : ($razaoSocialInformada !== '' ? $razaoSocialInformada : null);
    $documento = $this->normalizeText((string)($payload['documento'] ?? ''));
    $telefone = $this->normalizeNullableText($payload['telefone'] ?? null);
    $telefoneSecundario = $this->normalizeNullableText($payload['telefone_secundario'] ?? $payload['telefoneSecundario'] ?? null);
    $telefoneFixo = $this->normalizeNullableText($payload['telefone_fixo'] ?? $payload['telefoneFixo'] ?? null);
    $whatsapp = $this->normalizeNullableText($payload['whatsapp'] ?? null);
    $celular = $this->normalizeNullableText($payload['celular'] ?? null);

    return [
      'tipo_pessoa' => $tipoPessoa,
      'nome' => $nome,
      'razao_social' => $razaoSocial,
      'nome_fantasia' => $this->normalizeNullableText($payload['nome_fantasia'] ?? $payload['nomeFantasia'] ?? null),
      'documento' => $documento,
      'inscricao_estadual' => $this->normalizeNullableText($payload['inscricao_estadual'] ?? $payload['inscricaoEstadual'] ?? null, true),
      'telefone' => $telefone !== null ? $telefone : ($celular ?? $whatsapp ?? $telefoneFixo),
      'telefone_secundario' => $telefoneSecundario !== null ? $telefoneSecundario : ($whatsapp ?? $telefoneFixo),
      'contato' => $this->normalizeNullableText($payload['contato'] ?? null),
      'telefone_fixo' => $telefoneFixo,
      'whatsapp' => $whatsapp,
      'celular' => $celular !== null ? $celular : $telefone,
      'email' => $this->normalizeNullableText($payload['email'] ?? null),
      'cep' => $this->normalizeNullableText($payload['cep'] ?? null),
      'endereco' => $this->normalizeNullableText($payload['endereco'] ?? null),
      'numero' => $this->normalizeNullableText($payload['numero'] ?? null),
      'complemento' => $this->normalizeNullableText($payload['complemento'] ?? null),
      'bairro' => $this->normalizeNullableText($payload['bairro'] ?? null),
      'cidade' => $this->normalizeNullableText($payload['cidade'] ?? null),
      'estado' => $this->normalizeNullableText($payload['estado'] ?? null, true),
      'observacoes' => $this->normalizeNullableText($payload['observacoes'] ?? $payload['observacao'] ?? null),
      'status' => $this->normalizeStatus((string)($payload['status'] ?? 'ativo')),
    ];
  }

  private function syncExtendedStructures(int $cadastroId, array $payload, int $companyId): void {
    if (array_key_exists('motorista_detalhes', $payload) || array_key_exists('motoristaDetalhes', $payload)) {
      $this->saveMotoristaDetalhes(
        $cadastroId,
        (array)($payload['motorista_detalhes'] ?? $payload['motoristaDetalhes'] ?? []),
        $companyId
      );
    }

    if (array_key_exists('motoristas_vinculados', $payload) || array_key_exists('motoristasVinculados', $payload)) {
      $this->replaceMotoristasVinculados(
        $cadastroId,
        (array)($payload['motoristas_vinculados'] ?? $payload['motoristasVinculados'] ?? []),
        $companyId
      );
    }

    if (array_key_exists('veiculos', $payload)) {
      $this->replaceVeiculos($cadastroId, (array)$payload['veiculos'], $companyId);
    }

    if (array_key_exists('tags', $payload)) {
      $this->syncTags($cadastroId, (array)$payload['tags'], $companyId);
    }
  }

  private function normalizeMotoristaDetalhesPayload(array $payload): array {
    return [
      'cpf' => $this->normalizeNullableText($payload['cpf'] ?? null),
      'cnh' => $this->normalizeNullableText($payload['cnh'] ?? null, true),
    ];
  }

  private function normalizeMotoristasVinculadosPayload(array $items): array {
    $normalized = [];
    foreach ($items as $item) {
      if (!is_array($item)) {
        continue;
      }

      $nome = $this->normalizeText((string)($item['nome'] ?? ''));
      if ($nome === '') {
        continue;
      }

      $normalized[] = [
        'nome' => $nome,
        'cpf' => $this->normalizeNullableText($item['cpf'] ?? null),
        'cnh' => $this->normalizeNullableText($item['cnh'] ?? null, true),
        'contato' => $this->normalizeNullableText($item['contato'] ?? null),
        'telefone_fixo' => $this->normalizeNullableText($item['telefone_fixo'] ?? $item['telefoneFixo'] ?? null),
        'whatsapp' => $this->normalizeNullableText($item['whatsapp'] ?? null),
        'celular' => $this->normalizeNullableText($item['celular'] ?? null),
        'email' => $this->normalizeNullableText($item['email'] ?? null),
        'principal' => !empty($item['principal']) ? 1 : 0,
      ];
    }

    return $normalized;
  }

  private function normalizeVeiculosPayload(array $items): array {
    $normalized = [];
    foreach ($items as $item) {
      if (!is_array($item)) {
        continue;
      }

      $modelo = $this->normalizeText((string)($item['modelo'] ?? ''));
      $placa = $this->normalizeText((string)($item['placa'] ?? ''));
      if ($modelo === '' || $placa === '') {
        continue;
      }

      $normalized[] = [
        'modelo' => $modelo,
        'placa' => strtoupper($placa),
        'placa_adicional' => $this->normalizeNullableText($item['placa_adicional'] ?? $item['placaAdicional'] ?? null, true),
        'tipo_carroceria' => $this->normalizeNullableText($item['tipo_carroceria'] ?? $item['tipoCarroceria'] ?? null),
        'metragem' => $this->normalizeNullableText($item['metragem'] ?? null),
        'peso_carga' => $this->normalizeNullableText($item['peso_carga'] ?? $item['pesoCarga'] ?? null),
      ];
    }

    return $normalized;
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

  private function normalizeTagComparableTokens(string $value): array {
    $slug = $this->slugify($value);
    if ($slug === '') {
      return [];
    }

    $tokens = array_values(array_filter(explode('-', $slug), static fn ($token) => $token !== ''));
    if ($tokens === []) {
      return [];
    }

    $compact = implode('', $tokens);
    if ($compact !== '') {
      $tokens[] = $compact;
    }

    return array_values(array_unique($tokens));
  }

  private function sharedPrefixLength(string $left, string $right): int {
    $max = min(strlen($left), strlen($right));
    $count = 0;

    for ($i = 0; $i < $max; $i++) {
      if ($left[$i] !== $right[$i]) {
        break;
      }
      $count++;
    }

    return $count;
  }

  private function resolveComparablePrefixLength(string $left, string $right, int $default): int {
    $normalizedDefault = max(3, $default);
    if (strlen($left) <= 4 || strlen($right) <= 4) {
      return 3;
    }

    return $normalizedDefault;
  }

  private function hydrateMotoristaVinculado(array $row): array {
    return [
      'id' => (int)($row['id'] ?? 0),
      'cadastroId' => (int)($row['cadastro_id'] ?? 0),
      'nome' => (string)($row['nome'] ?? ''),
      'cpf' => (string)($row['cpf'] ?? ''),
      'cnh' => (string)($row['cnh'] ?? ''),
      'contato' => (string)($row['contato'] ?? ''),
      'telefoneFixo' => (string)($row['telefone_fixo'] ?? ''),
      'whatsapp' => (string)($row['whatsapp'] ?? ''),
      'celular' => (string)($row['celular'] ?? ''),
      'email' => (string)($row['email'] ?? ''),
      'principal' => (bool)($row['principal'] ?? false),
      'createdAt' => (string)($row['created_at'] ?? ''),
      'updatedAt' => (string)($row['updated_at'] ?? ''),
    ];
  }

  private function hydrateVeiculo(array $row): array {
    return [
      'id' => (int)($row['id'] ?? 0),
      'cadastroId' => (int)($row['cadastro_id'] ?? 0),
      'modelo' => (string)($row['modelo'] ?? ''),
      'placa' => (string)($row['placa'] ?? ''),
      'placaAdicional' => (string)($row['placa_adicional'] ?? ''),
      'tipoCarroceria' => (string)($row['tipo_carroceria'] ?? ''),
      'metragem' => (string)($row['metragem'] ?? ''),
      'pesoCarga' => (string)($row['peso_carga'] ?? ''),
      'createdAt' => (string)($row['created_at'] ?? ''),
      'updatedAt' => (string)($row['updated_at'] ?? ''),
    ];
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

  private function normalizeText(string $value): string {
    return trim(preg_replace('/\s+/', ' ', $value) ?? '');
  }

  private function normalizeNullableText(mixed $value, bool $uppercase = false): ?string {
    $text = $this->normalizeText((string)($value ?? ''));
    if ($text === '') {
      return null;
    }

    return $uppercase ? strtoupper($text) : $text;
  }

  private function normalizeDocumento(string $value): string {
    return preg_replace('/\D+/', '', $value) ?? '';
  }

  private function normalizeStatus(string $status): string {
    $status = strtolower($this->normalizeText($status));
    return $status !== '' ? $status : 'ativo';
  }

  private function slugify(string $value): string {
    $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    if ($text === false) {
      $text = $value;
    }

    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    $text = trim($text, '-');

    return $text;
  }

  private function normalizeTipoIds(array $tipoIds): array {
    $items = [];
    foreach ($tipoIds as $tipoId) {
      $id = (int)$tipoId;
      if ($id <= 0) {
        continue;
      }
      $items[$id] = $id;
    }

    return array_values($items);
  }

  private function normalizeLimit(mixed $value): int {
    $limit = (int)$value;
    if ($limit <= 0) {
      return 20;
    }

    return min($limit, 100);
  }

  private function normalizeOffset(mixed $value): int {
    $offset = (int)$value;
    return max(0, $offset);
  }

  private function existsCadastro(int $cadastroId, int $companyId): bool {
    $stmt = Database::connection()->prepare(
      'SELECT id
         FROM cadastros
        WHERE id = :id
          AND company_id = :company_id
        LIMIT 1'
    );
    $stmt->execute([
      ':id' => $cadastroId,
      ':company_id' => $companyId,
    ]);

    return (bool)$stmt->fetchColumn();
  }

  private function existsTipo(int $tipoId): bool {
    $stmt = Database::connection()->prepare(
      'SELECT id
         FROM cadastro_tipos
        WHERE id = :id
        LIMIT 1'
    );
    $stmt->execute([':id' => $tipoId]);

    return (bool)$stmt->fetchColumn();
  }
}
