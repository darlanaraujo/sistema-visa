<?php
declare(strict_types=1);

// Recebe POST do form e usa a mesma lógica do módulo public_php

require_once __DIR__ . '/../../../public_php/src/Support/Session.php';
require_once __DIR__ . '/../../../public_php/src/Errors/AppError.php';
require_once __DIR__ . '/../../../public_php/src/Repositories/UserRepository.php';
require_once __DIR__ . '/../../../public_php/src/Services/AuthService.php';

try {
  Session::start();

  $email = trim((string)($_POST['email'] ?? ''));
  $password = (string)($_POST['password'] ?? '');

  // Validação mínima (sem BD)
  if ($email === '' || $password === '') {
    throw new AppError('Informe e-mail e senha.');
  }

  $repo = new UserRepository();
  $service = new AuthService($repo);

  // Cria a sessão auth_user internamente (Parte 7 já validada)
  $service->login($email, $password);

  // Login OK → redireciona para o Dashboard (HTML)
  header('Location: /sistema-visa/app/templates/dashboard.php');
  exit;

} catch (AppError $e) {
  $msg = urlencode($e->getMessage());
  header("Location: /sistema-visa/app/templates/login.php?error={$msg}");
  exit;
} catch (Throwable $e) {
  header("Location: /sistema-visa/app/templates/login.php?error=" . urlencode('Erro interno no servidor'));
  exit;
}
