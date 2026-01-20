<?php
declare(strict_types=1);

final class Request {
  public static function method(): string {
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
  }

  public static function jsonBody(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '', true);
    return is_array($data) ? $data : [];
  }
}