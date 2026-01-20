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

    Session::set('auth_user', [
      'id' => $user['id'],
      'email' => $user['email'],
      'role' => $user['role'],
    ]);

    return [
      'message' => 'Login realizado com sucesso',
      'user' => [
        'id' => $user['id'],
        'email' => $user['email'],
        'role' => $user['role'],
      ],
    ];
  }
}