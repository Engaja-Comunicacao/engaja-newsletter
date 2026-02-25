<?php
require_once __DIR__ . '/_header.php';

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$rows = db()->query("
  SELECT
    n.id,
    n.status,
    n.send_at,
    n.sent_at,
    n.created_at,
    c.name AS company_name,
    u_send.name AS sent_by_name,
    u_create.name AS created_by_name
  FROM newsletters n
  JOIN companies c ON c.id = n.company_id
  LEFT JOIN users u_send ON u_send.id = n.sent_by_user_id
  LEFT JOIN users u_create ON u_create.id = n.created_by_user_id
  ORDER BY n.id DESC
")->fetchAll();
?>
<main class="container">
  <h2>Newsletters</h2>
  <a class="btn" href="newsletter_form.php">+ Nova Newsletter</a>

  <?php if ($flashSuccess): ?>
    <div style="margin-top:14px; background:#dcfce7; padding:12px; border-radius:10px; color:#166534; font-weight:600;">
      <?= e($flashSuccess) ?>
    </div>
  <?php endif; ?>

  <?php if ($flashError): ?>
    <div style="margin-top:14px; background:#fee2e2; padding:12px; border-radius:10px; color:#991b1b; font-weight:600;">
      <?= e($flashError) ?>
    </div>
  <?php endif; ?>

  <table style="margin-top:14px;">
    <tr>
      <th>ID</th>
      <th>Empresa</th>
      <th>Status</th>
      <th>Envio</th>
      <th>Usuário</th>
      <th>Ações</th>
    </tr>

    <?php foreach ($rows as $r): ?>
      <?php
        $envio = $r['sent_at'] ?? $r['send_at'] ?? null;
        $userName = $r['sent_by_name'] ?? $r['created_by_name'] ?? null;
      ?>
      <tr>
        <td>#<?= (int)$r['id'] ?></td>
        <td><?= e($r['company_name']) ?></td>
        <td><?= e($r['status']) ?></td>
        <td><?= e($envio ?: '—') ?></td>
        <td><?= e($userName ?: '—') ?></td>
        <td style="white-space:nowrap; display:flex; gap:8px; align-items:center;">
          <a class="btn secondary" href="newsletter_preview.php?id=<?= (int)$r['id'] ?>">Preview</a>

          <?php if (in_array($r['status'], ['draft','failed'], true)): ?>
            <a class="btn secondary" href="newsletter_edit.php?id=<?= (int)$r['id'] ?>">Editar</a>
          <?php endif; ?>

          <a class="btn" href="newsletter_action.php?action=delete&id=<?= (int)$r['id'] ?>"
            onclick="return confirm('Excluir newsletter?')">Excluir</a>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
</main>
<?php require_once __DIR__ . '/_footer.php'; ?>
