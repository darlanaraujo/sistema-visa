<?php
declare(strict_types=1);

require_once __DIR__ . '/../Support/Database.php';
require_once __DIR__ . '/../Support/FileStorage.php';

final class ArquivoRepository {
  private const ALLOWED_MIME_TYPES = [
    'image/jpeg',
    'image/png',
    'image/webp',
    'image/gif',
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'text/plain',
  ];

  private const MAX_FILE_SIZE = 15728640;

  public function listByEntity(string $entidade, int $entidadeId, int $companyId = 1): array {
    if ($entidadeId <= 0) {
      return [];
    }

    $stmt = Database::connection()->prepare(
      'SELECT rel.id AS relacao_id,
              rel.entidade,
              rel.entidade_id,
              rel.ordem,
              a.*
         FROM arquivo_relacao rel
         INNER JOIN arquivos a
                 ON a.id = rel.arquivo_id
        WHERE rel.company_id = :company_id
          AND rel.entidade = :entidade
          AND rel.entidade_id = :entidade_id
          AND a.status = :status
        ORDER BY rel.ordem ASC, rel.id ASC'
    );
    $stmt->execute([
      ':company_id' => $companyId,
      ':entidade' => $this->normalizeEntidade($entidade),
      ':entidade_id' => $entidadeId,
      ':status' => 'ativo',
    ]);

    $rows = $stmt->fetchAll();
    if (!is_array($rows) || !$rows) {
      return [];
    }

    return array_values(array_filter(array_map(
      fn ($row) => is_array($row) ? $this->hydrateArquivo($row) : null,
      $rows
    )));
  }

  public function findById(int $arquivoId, int $companyId = 1): ?array {
    if ($arquivoId <= 0) {
      return null;
    }

    $stmt = Database::connection()->prepare(
      'SELECT a.*
         FROM arquivos a
        WHERE a.id = :id
          AND a.company_id = :company_id
        LIMIT 1'
    );
    $stmt->execute([
      ':id' => $arquivoId,
      ':company_id' => $companyId,
    ]);

    $row = $stmt->fetch();
    if (!is_array($row) || !$row) {
      return null;
    }

    return $this->hydrateArquivo($row);
  }

  public function attachUploadedFiles(string $entidade, int $entidadeId, array $files, int $companyId = 1): array {
    $entidade = $this->normalizeEntidade($entidade);
    if ($entidadeId <= 0) {
      return [];
    }

    $normalizedFiles = $this->normalizeUploadedFilesArray($files);
    if ($normalizedFiles === []) {
      return [];
    }

    $pdo = Database::connection();
    $ownsTransaction = !$pdo->inTransaction();
    if ($ownsTransaction) {
      $pdo->beginTransaction();
    }

    $createdFiles = [];

    try {
      $nextOrder = $this->nextOrder($entidade, $entidadeId, $companyId);
      $insertFile = $pdo->prepare(
        'INSERT INTO arquivos (
           company_id,
           nome_original,
           nome_armazenado,
           caminho,
           mime_type,
           extensao,
           tamanho_bytes,
           hash_arquivo,
           status
         ) VALUES (
           :company_id,
           :nome_original,
           :nome_armazenado,
           :caminho,
           :mime_type,
           :extensao,
           :tamanho_bytes,
           :hash_arquivo,
           :status
         )'
      );

      $insertRel = $pdo->prepare(
        'INSERT INTO arquivo_relacao (
           company_id,
           entidade,
           entidade_id,
           arquivo_id,
           ordem
         ) VALUES (
           :company_id,
           :entidade,
           :entidade_id,
           :arquivo_id,
           :ordem
         )'
      );

      foreach ($normalizedFiles as $file) {
        $mimeType = $this->validateSingleUpload($file);

        $extension = $this->resolveExtension($file['name'], $mimeType);
        $storedName = bin2hex(random_bytes(16)) . ($extension !== '' ? '.' . $extension : '');
        $targetDir = FileStorage::entityDirectory($companyId, $entidade);
        $absolutePath = $targetDir . '/' . $storedName;
        FileStorage::moveUploadedFile($file['tmp_name'], $absolutePath);

        $relativePath = ltrim(str_replace(dirname(__DIR__, 3) . '/app/storage/', '', $absolutePath), '/');
        $hash = sha1_file($absolutePath) ?: null;

        $insertFile->execute([
          ':company_id' => $companyId,
          ':nome_original' => $this->sanitizeOriginalName($file['name']),
          ':nome_armazenado' => $storedName,
          ':caminho' => $relativePath,
          ':mime_type' => $mimeType,
          ':extensao' => $extension !== '' ? $extension : null,
          ':tamanho_bytes' => (int)$file['size'],
          ':hash_arquivo' => $hash,
          ':status' => 'ativo',
        ]);

        $arquivoId = (int)$pdo->lastInsertId();

        $insertRel->execute([
          ':company_id' => $companyId,
          ':entidade' => $entidade,
          ':entidade_id' => $entidadeId,
          ':arquivo_id' => $arquivoId,
          ':ordem' => $nextOrder++,
        ]);

        $created = $this->findById($arquivoId, $companyId);
        if (is_array($created)) {
          $createdFiles[] = $created;
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

    return $createdFiles;
  }

  public function removeRelations(string $entidade, int $entidadeId, array $relationIds, int $companyId = 1): void {
    $entidade = $this->normalizeEntidade($entidade);
    $ids = array_values(array_unique(array_filter(array_map('intval', $relationIds), static fn (int $id) => $id > 0)));
    if ($entidadeId <= 0 || $ids === []) {
      return;
    }

    $pdo = Database::connection();
    $ownsTransaction = !$pdo->inTransaction();
    if ($ownsTransaction) {
      $pdo->beginTransaction();
    }

    try {
      $selectStmt = $pdo->prepare(
        'SELECT rel.id, rel.arquivo_id, a.caminho
           FROM arquivo_relacao rel
           INNER JOIN arquivos a
                   ON a.id = rel.arquivo_id
          WHERE rel.company_id = :company_id
            AND rel.entidade = :entidade
            AND rel.entidade_id = :entidade_id
            AND rel.id = :id
          LIMIT 1'
      );
      $deleteRelStmt = $pdo->prepare(
        'DELETE FROM arquivo_relacao
          WHERE id = :id
            AND company_id = :company_id
            AND entidade = :entidade
            AND entidade_id = :entidade_id'
      );
      $countRelStmt = $pdo->prepare(
        'SELECT COUNT(*) FROM arquivo_relacao WHERE arquivo_id = :arquivo_id'
      );
      $markRemovedStmt = $pdo->prepare(
        'UPDATE arquivos
            SET status = :status,
                updated_at = CURRENT_TIMESTAMP
          WHERE id = :id
            AND company_id = :company_id'
      );

      foreach ($ids as $relationId) {
        $selectStmt->execute([
          ':company_id' => $companyId,
          ':entidade' => $entidade,
          ':entidade_id' => $entidadeId,
          ':id' => $relationId,
        ]);
        $row = $selectStmt->fetch();
        if (!is_array($row) || !$row) {
          continue;
        }

        $arquivoId = (int)($row['arquivo_id'] ?? 0);
        $caminho = (string)($row['caminho'] ?? '');

        $deleteRelStmt->execute([
          ':id' => $relationId,
          ':company_id' => $companyId,
          ':entidade' => $entidade,
          ':entidade_id' => $entidadeId,
        ]);

        if ($arquivoId <= 0) {
          continue;
        }

        $countRelStmt->execute([':arquivo_id' => $arquivoId]);
        $usage = (int)$countRelStmt->fetchColumn();
        if ($usage > 0) {
          continue;
        }

        $markRemovedStmt->execute([
          ':status' => 'removido',
          ':id' => $arquivoId,
          ':company_id' => $companyId,
        ]);

        if ($caminho !== '') {
          FileStorage::deleteIfExists($this->absoluteStoragePath($caminho));
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
  }

  public function validateUploads(array $files): void {
    foreach ($this->normalizeUploadedFilesArray($files) as $file) {
      $this->validateSingleUpload($file);
    }
  }

  private function hydrateArquivo(array $row): array {
    $mimeType = (string)($row['mime_type'] ?? '');
    $extensao = strtolower((string)($row['extensao'] ?? ''));
    $arquivoId = (int)($row['id'] ?? 0);

    return [
      'id' => $arquivoId,
      'companyId' => (int)($row['company_id'] ?? 0),
      'relacaoId' => (int)($row['relacao_id'] ?? 0),
      'entidade' => (string)($row['entidade'] ?? ''),
      'entidadeId' => (int)($row['entidade_id'] ?? 0),
      'ordem' => (int)($row['ordem'] ?? 0),
      'nomeOriginal' => (string)($row['nome_original'] ?? ''),
      'nomeArmazenado' => (string)($row['nome_armazenado'] ?? ''),
      'caminho' => (string)($row['caminho'] ?? ''),
      'mimeType' => $mimeType,
      'extensao' => $extensao,
      'tamanhoBytes' => (int)($row['tamanho_bytes'] ?? 0),
      'status' => (string)($row['status'] ?? ''),
      'isImage' => str_starts_with($mimeType, 'image/'),
      'isPdf' => $mimeType === 'application/pdf',
      'isPreviewable' => str_starts_with($mimeType, 'image/') || $mimeType === 'application/pdf',
    ];
  }

  private function normalizeUploadedFilesArray(array $files): array {
    if ($files === [] || !isset($files['name'])) {
      return [];
    }

    $items = [];
    $names = (array)$files['name'];
    $types = (array)($files['type'] ?? []);
    $tmpNames = (array)($files['tmp_name'] ?? []);
    $errors = (array)($files['error'] ?? []);
    $sizes = (array)($files['size'] ?? []);

    foreach ($names as $index => $name) {
      $items[] = [
        'name' => (string)$name,
        'type' => (string)($types[$index] ?? ''),
        'tmp_name' => (string)($tmpNames[$index] ?? ''),
        'error' => (int)($errors[$index] ?? UPLOAD_ERR_NO_FILE),
        'size' => (int)($sizes[$index] ?? 0),
      ];
    }

    return array_values(array_filter($items, static fn (array $item): bool => $item['error'] !== UPLOAD_ERR_NO_FILE));
  }

  private function assertUploadIsValid(array $file): void {
    $error = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error !== UPLOAD_ERR_OK) {
      throw new RuntimeException('Falha no envio do arquivo.');
    }

    $size = (int)($file['size'] ?? 0);
    if ($size <= 0) {
      throw new RuntimeException('O arquivo enviado está vazio.');
    }

    if ($size > self::MAX_FILE_SIZE) {
      throw new RuntimeException('O arquivo excede o limite permitido de 15 MB.');
    }
  }

  private function assertMimeTypeIsAllowed(string $mimeType): void {
    if (!in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
      throw new RuntimeException('Tipo de arquivo não suportado para anexos.');
    }
  }

  private function detectMimeType(string $tmpName): string {
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = (string)$finfo->file($tmpName);
    return $mimeType !== '' ? $mimeType : 'application/octet-stream';
  }

  private function validateSingleUpload(array $file): string {
    $this->assertUploadIsValid($file);
    $mimeType = $this->detectMimeType($file['tmp_name']);
    $this->assertMimeTypeIsAllowed($mimeType);
    return $mimeType;
  }

  private function sanitizeOriginalName(string $name): string {
    $clean = preg_replace('/[^\pL\pN\._ -]+/u', '', $name);
    $clean = trim((string)$clean);
    return $clean !== '' ? $clean : 'arquivo';
  }

  private function resolveExtension(string $name, string $mimeType): string {
    $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if ($extension !== '') {
      return preg_replace('/[^a-z0-9]+/i', '', $extension) ?: '';
    }

    return match ($mimeType) {
      'image/jpeg' => 'jpg',
      'image/png' => 'png',
      'image/webp' => 'webp',
      'image/gif' => 'gif',
      'application/pdf' => 'pdf',
      'application/msword' => 'doc',
      'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
      'application/vnd.ms-excel' => 'xls',
      'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
      'text/plain' => 'txt',
      default => '',
    };
  }

  private function normalizeEntidade(string $entidade): string {
    $normalized = trim(strtolower($entidade));
    if ($normalized === '') {
      throw new InvalidArgumentException('Entidade de anexo inválida.');
    }

    return preg_replace('/[^a-z0-9_]+/', '_', $normalized) ?: 'entidade';
  }

  private function absoluteStoragePath(string $relativePath): string {
    return dirname(__DIR__, 3) . '/app/storage/' . ltrim($relativePath, '/');
  }

  private function nextOrder(string $entidade, int $entidadeId, int $companyId): int {
    $stmt = Database::connection()->prepare(
      'SELECT COALESCE(MAX(ordem), 0) + 1
         FROM arquivo_relacao
        WHERE company_id = :company_id
          AND entidade = :entidade
          AND entidade_id = :entidade_id'
    );
    $stmt->execute([
      ':company_id' => $companyId,
      ':entidade' => $entidade,
      ':entidade_id' => $entidadeId,
    ]);

    return max(1, (int)$stmt->fetchColumn());
  }
}
