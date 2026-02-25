<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/helpers.php';
require_login();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) redirect('newsletters.php');

$st = db()->prepare("
  SELECT
    n.status, n.error_message, n.send_at, n.sent_at,
    u_send.name AS sent_by_name,
    u_create.name AS created_by_name
  FROM newsletters n
  LEFT JOIN users u_send ON u_send.id = n.sent_by_user_id
  LEFT JOIN users u_create ON u_create.id = n.created_by_user_id
  WHERE n.id=?
  LIMIT 1
");
$st->execute([$id]);
$meta = $st->fetch();

require_once __DIR__ . '/_header.php';
?>
<main class="container card">
  <h2>Preview da Newsletter #<?= $id ?></h2>

  <div class="row">
    <div>
      <a class="btn secondary" href="newsletters.php">Voltar</a>
    </div>
    <div style="text-align:right;">
      <?php if ($meta && in_array($meta['status'], ['draft','failed'], true)): ?>
        <a class="btn secondary" href="newsletter_edit.php?id=<?= $id ?>">Editar</a>
      <?php endif; ?>
      <a class="btn secondary" href="newsletter_action.php?action=schedule&id=<?= $id ?>" onclick="return confirm('Confirmar agendamento?')">Agendar</a>
      <a class="btn" href="newsletter_action.php?action=send_now&id=<?= $id ?>" onclick="return confirm('Enviar agora?')">Enviar agora</a>
    </div>
  </div>

  <hr>

  <iframe
    src="newsletter_email.php?id=<?= $id ?>"
    style="width:100%; height:720px; border:1px solid #e5e7eb; border-radius:12px; background:#fff;"
  ></iframe>

  <?php if ($meta): ?>
    <p>
      <strong>Status:</strong> <?= e($meta['status']) ?>

      <?php if (!empty($meta['send_at'])): ?>
        | <strong>Agendado:</strong> <?= e($meta['send_at']) ?>
      <?php endif; ?>

      <?php if (!empty($meta['sent_at'])): ?>
        | <strong>Enviado:</strong> <?= e($meta['sent_at']) ?>
      <?php endif; ?>

      <?php
        $who = $meta['sent_by_name'] ?? $meta['created_by_name'] ?? null;
      ?>
      <?php if ($who): ?>
        | <strong>Usuário:</strong> <?= e($who) ?>
      <?php endif; ?>
    </p>

    <?php if (!empty($meta['error_message'])): ?>
      <div style="background:#fee2e2; padding:12px; border-radius:10px; color:#991b1b; font-weight:600;">
        Erro: <?= e($meta['error_message']) ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>

  <p><small class="muted">O botão “Agendar” só funciona se você preencheu data e hora no cadastro.</small></p>
</main>
<?php require_once __DIR__ . '/_footer.php'; ?>
