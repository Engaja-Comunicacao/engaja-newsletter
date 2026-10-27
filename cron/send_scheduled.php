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

    $dateStr = !empty($n['send_at'])
      ? date('d/m/Y', strtotime($n['send_at']))
      : date('d/m/Y');

    $subject = "Radar de Notícias - {$dateStr} - " . ($n['company_name'] ?? 'Engaja');

    // SMTP por empresa (se configurado)
    $smtp = company_smtp_config($n);

    $via = send_newsletter_email($subject, $payload['html'], $recipients, $payload['embeds'], $smtp);
    $via .= ' | agendador v' . APP_VERSION;

    ensure_sent_via_column($pdo);
    $pdo->prepare("UPDATE newsletters SET status='sent', sent_at=NOW(), sent_via=? WHERE id=?")->execute([$via, $id]);
    echo date('Y-m-d H:i:s') . " newsletter #{$id} enviada: {$via}\n";
  } catch (Throwable $t) {
    $pdo->prepare("UPDATE newsletters SET status='failed', error_message=? WHERE id=?")->execute([$t->getMessage(), $id]);
    echo date('Y-m-d H:i:s') . " newsletter #{$id} falhou: " . $t->getMessage() . "\n";
  }
}
