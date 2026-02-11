<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/helpers.php';
require_login();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id) {
  db()->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
}
redirect('usuarios.php');
