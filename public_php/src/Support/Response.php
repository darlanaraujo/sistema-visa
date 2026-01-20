<?php
declare(strict_types=1);

final class Response {
  public static function json(int $status, array $payload): void {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
  }
}