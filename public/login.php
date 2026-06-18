<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/helpers.php';

start_session();
if (current_user()) redirect('empresas.php');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_verify();
  $email = trim($_POST['email'] ?? '');
  $pass  = $_POST['password'] ?? '';

  if (do_login($email, $pass)) {
    redirect('empresas.php');
  } else {
    $error = 'Login ou senha inválidos.';
  }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Engaja | Login</title>
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
<body class="bg-[#E7E7E7] h-screen flex justify-center items-center relative">
  <img src="assets/bg.png" alt="" class="absolute bottom-0 left-0 opacity-50 z-0">
  <form class="relative z-10 border border-cinza rounded-2xl w-75 overflow-hidden bg-[#E7E7E7]" method="POST">
    <h1 class="bg-cinza p-6">
      <img src="assets/LOGO.svg" alt="Engaja" />
    </h1>

    <?= csrf_field(); ?>

    <div class="p-6">
      <?php if ($error): ?>
        <p style="color:#ef4444; font-weight:600;"><?= e($error) ?></p>
      <?php endif; ?>
  
      <input class="border! border-cinza!" name="email" placeholder="Email" required>
      <input class="border! border-cinza!" type="password" name="password" placeholder="Senha" required>
      <button class="w-full cursor-pointer rounded-lg leading-10 px-8 font-bold bg-cinza text-white text-sm duration-150 hover:bg-yellow-500 hover:text-cinza">Entrar</button>
    </div>
  </form>
  <h6 class="text-cinza absolute bottom-4 right-4 text-xs font-bold">v1.44.3</h6>
</body>
</html>
