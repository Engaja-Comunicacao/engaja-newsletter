<?php
require_once __DIR__ . '/_header.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) redirect('newsletters.php');
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

  <p><small class="muted">O botão “Agendar” só funciona se você preencheu data e hora no cadastro.</small></p>
</main>
<?php require_once __DIR__ . '/_footer.php'; ?>
