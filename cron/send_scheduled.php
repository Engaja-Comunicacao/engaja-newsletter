<?php
// cron/send_scheduled.php
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/email_template.php';
require_once __DIR__ . '/../app/mailer.php';

$pdo = db();

$st = $pdo->prepare("
  SELECT id
  FROM newsletters
  WHERE status='scheduled'
    AND send_at IS NOT NULL
    AND send_at <= NOW()
  ORDER BY send_at ASC
  LIMIT 10
");
$st->execute();
$rows = $st->fetchAll();

foreach ($rows as $r) {
  $id = (int)$r['id'];

  try {
    $pdo->prepare("UPDATE newsletters SET status='sending', error_message=NULL WHERE id=?")->execute([$id]);

    [$n, $items, $recipients] = get_newsletter_data($id);
    if (count($recipients) === 0) throw new RuntimeException('Sem destinatários.');
    if (count($items) === 0) throw new RuntimeException('Sem notícias.');

    $payload = render_email_send($id);
    $subject = "Radar de Notícias - " . ($n['company_name'] ?? 'Engaja');

    send_newsletter_email($subject, $payload['html'], $recipients, $payload['embeds']);

    $pdo->prepare("UPDATE newsletters SET status='sent', sent_at=NOW() WHERE id=?")->execute([$id]);
  } catch (Throwable $t) {
    $pdo->prepare("UPDATE newsletters SET status='failed', error_message=? WHERE id=?")->execute([$t->getMessage(), $id]);
  }
}
