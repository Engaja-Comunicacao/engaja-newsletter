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
  <link rel="stylesheet" href="assets/styles.css">
</head>
<body class="login-bg">
  <form class="login-box" method="POST">
    <h1 class="logo"><img src="assets/engaja.png" alt="Engaja" style="height:28px;"/></h1>

    <?= csrf_field(); ?>

    <?php if ($error): ?>
      <p style="color:#ef4444; font-weight:600;"><?= e($error) ?></p>
    <?php endif; ?>

    <input name="email" placeholder="Email" required>
    <input type="password" name="password" placeholder="Senha" required>
    <button>Entrar</button>
  </form>
</body>
</html>
