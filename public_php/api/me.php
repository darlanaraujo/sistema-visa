<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/Support/Response.php';
require_once __DIR__ . '/../src/Support/Request.php';
require_once __DIR__ . '/../src/Support/Session.php';
require_once __DIR__ . '/../src/Middlewares/AuthMiddleware.php';

AuthMiddleware::ensureAuthenticated();

$user = Session::get('auth_user');

Response::json(200, [
  'user' => $user
]);
