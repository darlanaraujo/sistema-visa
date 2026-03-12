<?php
declare(strict_types=1);

final class Database {
  private static ?PDO $connection = null;

  public static function connection(): PDO {
    if (self::$connection instanceof PDO) {
      return self::$connection;
    }

    // Em XAMPP local deste projeto, "localhost" é o fallback seguro
    // porque o login HTML fora de /public_php não herda o SetEnv do .htaccess.
    $host = self::env('SV_DB_HOST', 'localhost');
    $port = self::env('SV_DB_PORT', '3306');
    $name = self::env('SV_DB_NAME', 'sistema_visa');
    $user = self::env('SV_DB_USER', 'root');
    $pass = self::env('SV_DB_PASS', '');

    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $name);

    self::$connection = new PDO($dsn, $user, $pass, [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return self::$connection;
  }

  private static function env(string $key, string $default = ''): string {
    $value = getenv($key);
    if ($value !== false && $value !== '') {
      return (string)$value;
    }

    if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
      return (string)$_ENV[$key];
    }

    if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
      return (string)$_SERVER[$key];
    }

    return $default;
  }
}
