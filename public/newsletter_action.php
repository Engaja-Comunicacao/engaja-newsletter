<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/email_template.php';
require_once __DIR__ . '/../app/mailer.php';

require_login();

$u = current_user();
$userId = (int)($u['id'] ?? 0);

$action = $_GET['action'] ?? '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) redirect('newsletters.php');

$pdo = db();

if ($action === 'delete') {
  $pdo->prepare("DELETE FROM newsletters WHERE id=?")->execute([$id]);
  $_SESSION['flash_success'] = 'Newsletter excluída.';
  redirect('newsletters.php');
}

if ($action === 'schedule') {
  $st = $pdo->prepare("SELECT send_at FROM newsletters WHERE id=?");
  $st->execute([$id]);
  $n = $st->fetch();

  if (!$n || empty($n['send_at'])) {
    $_SESSION['flash_error'] = 'Não dá pra agendar: preencha data/hora no cadastro.';
    redirect('newsletter_preview.php?id=' . $id);
  }

  $pdo->prepare("UPDATE newsletters SET status='scheduled', error_message=NULL WHERE id=?")->execute([$id]);
  $_SESSION['flash_success'] = 'Newsletter agendada com sucesso.';
  redirect('newsletters.php');
}

if ($action === 'send_now') {
  try {
    $pdo->prepare("UPDATE newsletters SET status='sending', error_message=NULL WHERE id=?")->execute([$id]);

    [$n, $items, $recipients] = get_newsletter_data($id);
    if (count($recipients) === 0) throw new RuntimeException('A empresa não tem destinatários cadastrados.');
    if (count($items) === 0) throw new RuntimeException('A newsletter não tem notícias.');

    $payload = render_email_send($id);

    $subject = "Radar de Notícias - " . ($n['company_name'] ?? 'Engaja');

    // ENVIO (com embeds)
    send_newsletter_email($subject, $payload['html'], $recipients, $payload['embeds']);

    $pdo->prepare("UPDATE newsletters
      SET status='sent', sent_at=NOW(), sent_by_user_id=?
      WHERE id=?")->execute([$userId, $id]);

    $_SESSION['flash_success'] = 'Newsletter enviada com sucesso!';
    redirect('newsletters.php');
  } catch (Throwable $t) {
    $pdo->prepare("UPDATE newsletters
      SET status='failed', error_message=?, sent_by_user_id=?
      WHERE id=?")->execute([$t->getMessage(), $userId, $id]);

    $_SESSION['flash_error'] = 'Falha ao enviar: ' . $t->getMessage();
    redirect('newsletters.php');
  }
}

redirect('newsletters.php');
