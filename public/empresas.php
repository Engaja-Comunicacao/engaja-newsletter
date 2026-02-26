<?php
require_once __DIR__ . '/_header.php';
$rows = db()->query("SELECT * FROM companies ORDER BY id DESC")->fetchAll();
?>
<main class="container">
  <h2>Empresas</h2>
  <a class="btn" href="empresa_form.php">+ Nova Empresa</a>

  <table>
    <tr><th>Nome</th><th>Topo</th><th>SMTP Cliente</th><th>Ações</th></tr>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= e($r['name']) ?></td>
        <td><?= $r['header_image_path'] ? '✅' : '—' ?></td>
        <td><?= !empty($r['smtp_enabled']) ? '✅' : '—' ?></td>
        <td>
          <a class="btn secondary" href="empresa_form.php?id=<?= (int)$r['id'] ?>">Editar</a>
          <a class="btn" href="empresa_delete.php?id=<?= (int)$r['id'] ?>" onclick="return confirm('Excluir empresa?')">Excluir</a>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
</main>
<?php require_once __DIR__ . '/_footer.php'; ?>