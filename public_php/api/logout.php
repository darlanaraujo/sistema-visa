<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/Support/Response.php';
require_once __DIR__ . '/../src/Support/Request.php';
require_once __DIR__ . '/../src/Support/Session.php';
require_once __DIR__ . '/../src/Errors/AppError.php';
require_once __DIR__ . '/../src/Repositories/UserRepository.php';
require_once __DIR__ . '/../src/Services/AuthService.php';
require_once __DIR__ . '/../src/Controllers/AuthController.php';

try {
  $repo = new UserRepository();
  $service = new AuthService($repo);
  $controller = new AuthController($service);

  $controller->logout();
} catch (AppError $e) {
  Response::json($e->statusCode(), ['message' => $e->getMessage()]);
} catch (Throwable $e) {
  Response::json(500, ['message' => 'Erro interno no servidor']);
}
