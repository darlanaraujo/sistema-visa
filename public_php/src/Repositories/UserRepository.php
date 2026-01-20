<?php
declare(strict_types=1);

final class UserRepository {
  public function findByEmail(string $email): ?array {
    // hash fixo gerado com password_hash('123456', PASSWORD_BCRYPT)
    // vamos gerar e colar uma vez (abaixo explico)
    $users = [
      [
        'id' => 1,
        'email' => 'admin@visa.com',
        'passwordHash' => '$2y$10$LHixaJFunG/65xJvCY9H/.QoNnit0dOCGv/46GIiwP5.doO86lNji',
        'role' => 'ADMIN',
        'active' => true,
      ]
    ];

    foreach ($users as $u) {
      if (strtolower($u['email']) === strtolower($email)) {
        return $u;
      }
    }

    return null;
  }
}