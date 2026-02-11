<?php
require_once __DIR__ . '/_header.php';

$rows = db()->query("
  SELECT n.id, n.status, n.send_at, n.created_at, c.name AS company_name
  FROM newsletters n
  JOIN companies c ON c.id = n.company_id
  ORDER BY n.id DESC
")->fetchAll();
?>
<main class="container">
  <h2>Newsletters</h2>
  <a class="btn" href="newsletter_form.php">+ Nova Newsletter</a>

  <table>
    <tr><th>ID</th><th>Empresa</th><th>Status</th><th>Envio</th><th>Ações</th></tr>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td>#<?= (int)$r['id'] ?></td>
        <td><?= e($r['company_name']) ?></td>
        <td><?= e($r['status']) ?></td>
        <td><?= e($r['send_at'] ?? '—') ?></td>
        <td>
          <a class="btn secondary" href="newsletter_preview.php?id=<?= (int)$r['id'] ?>">Preview</a>
          <a class="btn" href="newsletter_action.php?action=delete&id=<?= (int)$r['id'] ?>" onclick="return confirm('Excluir newsletter?')">Excluir</a>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
</main>
<?php require_once __DIR__ . '/_footer.php'; ?>
