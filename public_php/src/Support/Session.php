<?php
declare(strict_types=1);

final class Session {
  public static function start(): void {
    if (session_status() === PHP_SESSION_NONE) {
      if (!headers_sent()) {
        $params = session_get_cookie_params();
        session_set_cookie_params([
          'lifetime' => $params['lifetime'],
          'path' => '/', // ESSENCIAL para compartilhar entre /public_php e /app
          'domain' => $params['domain'],
          'secure' => $params['secure'],
          'httponly' => $params['httponly'],
          'samesite' => 'Lax',
        ]);
      }
      session_start();
    }
  }

  public static function set(string $key, mixed $value): void {
    self::start();
    $_SESSION[$key] = $value;
  }

  public static function get(string $key): mixed {
    self::start();
    return $_SESSION[$key] ?? null;
  }

  public static function destroy(): void {
    self::start();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
      $params = session_get_cookie_params();
      setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
  }
}
