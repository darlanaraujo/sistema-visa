<?php
declare(strict_types=1);

require_once __DIR__ . '/../Support/Session.php';
require_once __DIR__ . '/../Support/Response.php';

final class AuthMiddleware {
  public static function ensureAuthenticated(): void {
    // Start + expira por inatividade (server-side)
    // Se exceder o tempo, a sessão é destruída.
    Session::enforceIdleTimeout();

    if (!isset($_SESSION['auth_user'])) {
      Response::json(401, ['message' => 'Não autenticado']);
    }
  }
}