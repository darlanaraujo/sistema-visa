<?php
declare(strict_types=1);

final class AuthController {
  public function __construct(private AuthService $service) {}

  public function login(): void {
    if (Request::method() !== 'POST') {
      Response::json(405, ['message' => 'Método não permitido']);
    }

    $data = Request::jsonBody();

    $email = (string)($data['email'] ?? '');
    $password = (string)($data['password'] ?? '');

    $result = $this->service->login($email, $password);

    Response::json(200, $result);
  }

  public function logout(): void {
    if (Request::method() !== 'POST') {
      Response::json(405, ['message' => 'Método não permitido']);
    }

    Session::destroy();

    Response::json(200, ['message' => 'Logout realizado com sucesso']);
  }

}