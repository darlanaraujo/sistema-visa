<?php
declare(strict_types=1);


final class AuthService {
  public function __construct(private UserRepository $repo) {}

  public function login(string $email, string $password): array {
    $email = trim($email);

    if ($email === '' || $password === '') {
      throw new AppError('Email e senha são obrigatórios', 400);
    }

    $user = $this->repo->findByEmail($email);

    if ($user === null) {
      throw new AppError('Credenciais inválidas', 401);
    }

    if (!$user['active']) {
      throw new AppError('Usuário inativo', 403);
    }

    $hash = $user['passwordHash'] ?? '';

    if (!is_string($hash) || $hash === '') {
      throw new AppError('Credenciais inválidas', 401);
    }

    if (!password_verify($password, $hash)) {
      throw new AppError('Credenciais inválidas', 401);
    }

    Session::regenerate();

    $authUser = [
      'id' => $user['id'],
      'company_id' => $user['companyId'] ?? 1,
      'name' => $user['name'] ?? '',
      'email' => $user['email'],
      'role' => $user['role'],
      'active' => (bool)($user['active'] ?? true),
    ];

    Session::set('auth_user', [
      'id' => $authUser['id'],
      'company_id' => $authUser['company_id'],
      'name' => $authUser['name'],
      'email' => $authUser['email'],
      'role' => $authUser['role'],
      'active' => $authUser['active'],
    ]);

    return [
      'message' => 'Login realizado com sucesso',
      'user' => $authUser,
    ];
  }
}
