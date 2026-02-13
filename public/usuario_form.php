<?php
require_once __DIR__ . '/../app/auth.php';
require_login();

$pdo = db();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$user = ['name'=>'','email'=>''];
if ($id) {
  $st = $pdo->prepare("SELECT id,name,email FROM users WHERE id=?");
  $st->execute([$id]);
  $user = $st->fetch() ?: $user;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_verify();
  try {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';

    if ($name === '' || $email === '') throw new RuntimeException('Nome e email são obrigatórios.');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Email inválido.');

    if ($id) {
      $sql = "UPDATE users SET name=?, email=?".($pass ? ", password_hash=?" : "")." WHERE id=?";
      $params = [$name, $email];
      if ($pass) $params[] = password_hash($pass, PASSWORD_BCRYPT);
      $params[] = $id;
      $pdo->prepare($sql)->execute($params);
    } else {
      if (!$pass) throw new RuntimeException('Senha é obrigatória no cadastro.');
      $pdo->prepare("INSERT INTO users (name,email,password_hash) VALUES (?,?,?)")
          ->execute([$name, $email, password_hash($pass, PASSWORD_BCRYPT)]);
    }

    redirect('usuarios.php');
  } catch (Throwable $t) {
    $error = $t->getMessage();
  }
}

require_once __DIR__ . '/_header.php';
?>
<main class="container card">
  <h2><?= $id ? 'Editar Usuário' : 'Cadastro de Usuário' ?></h2>

  <?php if ($error): ?>
    <p style="color:#ef4444; font-weight:600;"><?= e($error) ?></p>
  <?php endif; ?>

  <form method="POST">
    <?= csrf_field(); ?>
    <input name="name" value="<?= e($user['name'] ?? '') ?>" placeholder="Nome" required>
    <input name="email" value="<?= e($user['email'] ?? '') ?>" placeholder="Email" required>
    <input type="password" name="password" placeholder="<?= $id ? 'Senha (deixe vazio para manter)' : 'Senha' ?>" <?= $id ? '' : 'required' ?>>
    <button class="btn">Salvar</button>
  </form>
</main>
<?php require_once __DIR__ . '/_footer.php'; ?>
