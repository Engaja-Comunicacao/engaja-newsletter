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
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Mulish:ital,wght@0,200..1000;1,200..1000&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <style>
    *{
      font-family: "Mulish", sans-serif;
    }
  </style>
  <style type="text/tailwindcss">
    @theme {
      --color-cinza: #1E1E1F;
    }
  </style>
  <link rel="stylesheet" href="assets/styles.css">

</head>
<body>
<header class="bg-cinza flex gap-10 items-center px-10 py-4">
  <h1 class="logo">
    <img src="assets/LOGO.svg" alt="Engaja"/>
  </h1>
  <nav class="flex gap-10 *:text-white *:hover:text-yellow-500 *:font-semibold *:duration-150">
    <a href="empresas.php">Empresas</a>
    <a href="usuarios.php">Usuários</a>
    <a href="newsletters.php">Newsletter</a>
    <a href="logout.php">Sair</a>
  </nav>
</header>
