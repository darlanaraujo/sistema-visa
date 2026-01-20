<?php
declare(strict_types=1);

final class AuthMiddleware {
  public static function ensureAuthenticated(): void {
    Session::start();

    if (!isset($_SESSION['auth_user'])) {
      Response::json(401, ['message' => 'Não autenticado']);
    }
  }
}
