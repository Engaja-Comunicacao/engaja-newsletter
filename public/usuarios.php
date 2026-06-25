<?php
require_once __DIR__ . '/_header.php';
$rows = db()->query("SELECT id,name,email FROM users ORDER BY id DESC")->fetchAll();
?>
<main class="container px-20 py-10">
  <div class="flex justify-between items-center">
    <h2 class="text-5xl text-cinza font-extrabold">Usuários</h2>
    <a class="btn rounded-lg leading-10 px-8 font-bold bg-cinza text-white text-sm duration-150 hover:bg-yellow-500 hover:text-cinza" href="usuario_form.php">Novo Usuário</a>
  </div>
  <div class="bg-white p-4 rounded-lg mt-6 shadow-lg">
    <table>
      <tr><th>Nome</th><th>Email</th><th>Ações</th></tr>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= e($r['name']) ?></td>
          <td><?= e($r['email']) ?></td>
          <td class="w-26">
              <div class="flex justify-between">
                <a class="btn block leading-5 duration-150 text-xs rounded-sm border px-1.5 font-semibold border-blue-500 text-blue-500 hover:bg-blue-500 hover:text-white" href="usuario_form.php?id=<?= (int)$r['id'] ?>">Editar</a>
                <a class="btn block leading-5 duration-150 text-xs rounded-sm border px-1.5 font-semibold border-red-500 text-red-500 hover:bg-red-500 hover:text-white" href="usuario_delete.php?id=<?= (int)$r['id'] ?>" onclick="return confirm('Excluir empresa?')">Excluir</a>
              </div>
            </td>
          <!-- <td>
            <a class="btn secondary" href="usuario_form.php?id=<?= (int)$r['id'] ?>">Editar</a>
            <a class="btn" href="usuario_delete.php?id=<?= (int)$r['id'] ?>" onclick="return confirm('Excluir usuário?')">Excluir</a>
          </td> -->
        </tr>
      <?php endforeach; ?>
    </table>
  </div>
</main>
<?php require_once __DIR__ . '/_footer.php'; ?>
