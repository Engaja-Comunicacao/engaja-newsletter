<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/email_template.php';
require_once __DIR__ . '/../app/mailer.php';

require_login();

$action = $_GET['action'] ?? '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) redirect('newsletters.php');

$pdo = db();

if ($action === 'delete') {
  $pdo->prepare("DELETE FROM newsletters WHERE id=?")->execute([$id]);
  redirect('newsletters.php');
}

if ($action === 'schedule') {
  // precisa ter send_at
  $st = $pdo->prepare("SELECT send_at FROM newsletters WHERE id=?");
  $st->execute([$id]);
  $n = $st->fetch();
  if (!$n || empty($n['send_at'])) {
    exit('Não dá pra agendar: preencha data/hora no cadastro.');
  }
  $pdo->prepare("UPDATE newsletters SET status='scheduled', error_message=NULL WHERE id=?")->execute([$id]);
  redirect('newsletter_preview.php?id=' . $id);
}

if ($action === 'send_now') {
  try {
    // trava pra evitar duplo envio
    $pdo->prepare("UPDATE newsletters SET status='sending', error_message=NULL WHERE id=?")->execute([$id]);

    [$n, $items, $recipients] = get_newsletter_data($id);
    if (count($recipients) === 0) throw new RuntimeException('A empresa não tem destinatários cadastrados.');
    if (count($items) === 0) throw new RuntimeException('A newsletter não tem notícias.');

    $html = render_email_html($id);
    $subject = "Radar de Notícias - " . ($n['company_name'] ?? 'Engaja');

    send_newsletter_email($subject, $html, $recipients);

    $pdo->prepare("UPDATE newsletters SET status='sent', sent_at=NOW() WHERE id=?")->execute([$id]);
  } catch (Throwable $t) {
    $pdo->prepare("UPDATE newsletters SET status='failed', error_message=? WHERE id=?")->execute([$t->getMessage(), $id]);
  }

  redirect('newsletter_preview.php?id=' . $id);
}

redirect('newsletters.php');
