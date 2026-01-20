<?php
declare(strict_types=1);

// Recebe POST do form e usa a mesma lógica do módulo public_php

require_once __DIR__ . '/../../../public_php/src/Support/Session.php';
require_once __DIR__ . '/../../../public_php/src/Errors/AppError.php';
require_once __DIR__ . '/../../../public_php/src/Repositories/UserRepository.php';
require_once __DIR__ . '/../../../public_php/src/Services/AuthService.php';

try {
  Session::start();

  $email = (string)($_POST['email'] ?? '');
  $password = (string)($_POST['password'] ?? '');

  $repo = new UserRepository();
  $service = new AuthService($repo);

  // Cria a sessão auth_user internamente (Parte 7 já validada)
  $service->login($email, $password);

  // por enquanto: prova que autenticou (retorna JSON do usuário)
  header('Location: /sistema-visa/public_php/me');
  exit;

} catch (AppError $e) {
  $msg = urlencode($e->getMessage());
  header("Location: /sistema-visa/app/templates/login.php?error={$msg}");
  exit;
} catch (Throwable $e) {
  header("Location: /sistema-visa/app/templates/login.php?error=" . urlencode('Erro interno no servidor'));
  exit;
}
