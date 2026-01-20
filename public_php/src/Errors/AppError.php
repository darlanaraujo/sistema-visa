<?php
declare(strict_types=1);

final class AppError extends Exception {
  public function __construct(string $message, int $statusCode = 500) {
    parent::__construct($message, $statusCode);
  }

  public function statusCode(): int {
    return $this->getCode() > 0 ? $this->getCode() : 500;
  }
}