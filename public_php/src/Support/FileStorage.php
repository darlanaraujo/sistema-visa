<?php
declare(strict_types=1);

final class FileStorage {
  public static function storageRoot(): string {
    return dirname(__DIR__, 3) . '/app/storage';
  }

  public static function uploadsRoot(): string {
    return self::storageRoot() . '/anexos';
  }

  public static function ensureDirectory(string $path): void {
    if (is_dir($path)) {
      if (!is_writable($path)) {
        throw new RuntimeException('O diretório de anexos não possui permissão de escrita: ' . $path);
      }
      return;
    }

    if (file_exists($path)) {
      throw new RuntimeException('O caminho de armazenamento já existe, mas não é um diretório válido: ' . $path);
    }

    if (!mkdir($path, 0775, true) && !is_dir($path)) {
      throw new RuntimeException('Não foi possível preparar o diretório de anexos.');
    }

    @chmod($path, 0775);

    if (!is_writable($path)) {
      throw new RuntimeException('O diretório de anexos foi criado, mas permanece sem permissão de escrita: ' . $path);
    }
  }

  public static function entityDirectory(int $companyId, string $entidade): string {
    $companyPart = (string)max(1, $companyId);
    $entityPart = trim(preg_replace('/[^a-z0-9_-]+/i', '-', strtolower($entidade)), '-');
    $datePart = date('Y/m');
    $dir = self::uploadsRoot() . '/' . $companyPart . '/' . $entityPart . '/' . $datePart;
    self::ensureDirectory($dir);
    return $dir;
  }

  public static function moveUploadedFile(string $tmpName, string $destination): void {
    if ($tmpName === '' || !is_file($tmpName) || !is_readable($tmpName)) {
      throw new RuntimeException('O arquivo temporário do anexo não está disponível para armazenamento.');
    }

    $targetDirectory = dirname($destination);
    self::ensureDirectory($targetDirectory);

    if (is_uploaded_file($tmpName)) {
      if (!move_uploaded_file($tmpName, $destination)) {
        throw new RuntimeException('Não foi possível mover o arquivo enviado.');
      }
      return;
    }

    if (@rename($tmpName, $destination)) {
      return;
    }

    if (@copy($tmpName, $destination)) {
      @unlink($tmpName);
      return;
    }

    throw new RuntimeException('Não foi possível mover o arquivo para o armazenamento.');
  }

  public static function deleteIfExists(string $absolutePath): void {
    if (is_file($absolutePath)) {
      @unlink($absolutePath);
    }
  }
}
