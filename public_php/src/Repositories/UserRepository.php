<?php
declare(strict_types=1);

require_once __DIR__ . '/../Support/Database.php';

final class UserRepository {
  public function findByEmail(string $email, int $companyId = 1): ?array {
    $email = trim($email);
    if ($email === '') {
      return null;
    }

    $pdo = Database::connection();
    $stmt = $pdo->prepare(
      'SELECT id, company_id, name, email, password_hash, role
         FROM users
        WHERE company_id = :company_id
          AND email = :email
        LIMIT 1'
    );
    $stmt->execute([
      ':company_id' => $companyId,
      ':email' => $email,
    ]);

    $user = $stmt->fetch();
    if (!is_array($user) || !$user) {
      return null;
    }

    return [
      'id' => (int)($user['id'] ?? 0),
      'companyId' => (int)($user['company_id'] ?? $companyId),
      'name' => (string)($user['name'] ?? ''),
      'email' => (string)($user['email'] ?? ''),
      'passwordHash' => (string)($user['password_hash'] ?? ''),
      'role' => (string)($user['role'] ?? ''),
      'active' => true,
    ];
  }
}
