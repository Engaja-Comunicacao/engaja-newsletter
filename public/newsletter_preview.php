<?php
require_once __DIR__ . '/_header.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) redirect('newsletters.php');

$st = db()->prepare("SELECT status, error_message, send_at, sent_at FROM newsletters WHERE id=?");
$st->execute([$id]);
$meta = $st->fetch();

?>
<main class="container card">
  <h2>Preview da Newsletter #<?= $id ?></h2>

  <div class="row">
    <div>
      <a class="btn secondary" href="newsletters.php">Voltar</a>
    </div>
    <div style="text-align:right;">
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
      <?php if (!empty($meta['send_at'])): ?> | <strong>Agendado:</strong> <?= e($meta['send_at']) ?> <?php endif; ?>
      <?php if (!empty($meta['sent_at'])): ?> | <strong>Enviado:</strong> <?= e($meta['sent_at']) ?> <?php endif; ?>
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
