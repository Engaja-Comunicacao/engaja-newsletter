<?php
require_once __DIR__ . '/_header.php';
$rows = db()->query("SELECT id,name,email,created_at FROM users ORDER BY id DESC")->fetchAll();
?>
<main class="container">
  <h2>Usuários</h2>
  <a class="btn" href="usuario_form.php">+ Novo Usuário</a>

  <table>
    <tr><th>Nome</th><th>Email</th><th>Ações</th></tr>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= e($r['name']) ?></td>
        <td><?= e($r['email']) ?></td>
        <td>
          <a class="btn secondary" href="usuario_form.php?id=<?= (int)$r['id'] ?>">Editar</a>
          <a class="btn" href="usuario_delete.php?id=<?= (int)$r['id'] ?>" onclick="return confirm('Excluir usuário?')">Excluir</a>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
</main>
<?php require_once __DIR__ . '/_footer.php'; ?>
