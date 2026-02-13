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

  $st = db()->prepare("SELECT id,name,email FROM users WHERE id=? LIMIT 1");
  $st->execute([(int)$_SESSION['user_id']]);
  $u = $st->fetch();

  return $u ?: null;
}

function require_login(): void {
  if (!current_user()) redirect('login.php');
}

function do_login(string $email, string $password): bool {
  $st = db()->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
  $st->execute([$email]);
  $u = $st->fetch();
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
