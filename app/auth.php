<?php
// app/auth.php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

function start_session(): void {
  if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
  }
}

function current_user(): ?array {
  start_session();
  if (empty($_SESSION['user_id'])) return null;
  $stmt = db()->prepare("SELECT id, name, email FROM users WHERE id = ?");
  $stmt->execute([$_SESSION['user_id']]);
  $u = $stmt->fetch();
  return $u ?: null;
}

function require_login(): void {
  if (!current_user()) redirect('login.php');
}

function do_login(string $email, string $password): bool {
  $stmt = db()->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
  $stmt->execute([$email]);
  $u = $stmt->fetch();
  if (!$u) return false;
  if (!password_verify($password, $u['password_hash'])) return false;

  start_session();
  $_SESSION['user_id'] = (int)$u['id'];
  return true;
}

function do_logout(): void {
  start_session();
  session_destroy();
}
