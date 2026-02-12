<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/helpers.php';
require_login();
$u = current_user();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Engaja</title>
  <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
<header>
  <h1 class="logo"><img src="assets/engaja.png" alt="Engaja" style="height:28px;"/></h1>
  <nav>
    <a href="empresas.php">Empresas</a>
    <a href="usuarios.php">Usuários</a>
    <a href="newsletters.php">Newsletter</a>
    <a href="logout.php" style="color:#ef4444;">Sair</a>
  </nav>
</header>
