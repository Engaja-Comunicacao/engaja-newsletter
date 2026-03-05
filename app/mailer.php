<?php
// app/mailer.php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;

function send_newsletter_email(
  string $subject,
  string $html,
  array $recipients,
  array $embeds,
  ?array $smtp = null
): void {

  $MAX_BCC_PER_EMAIL = 90;

  $clean = [];
  foreach ($recipients as $r) {
    $r = trim((string)$r);
    if ($r === '') continue;
    $clean[strtolower($r)] = $r;
  }
  $recipients = array_values($clean);

  if (count($recipients) === 0) {
    throw new RuntimeException('Sem destinatários.');
  }

  // Decide SMTP (empresa ou Engaja)
  $useCompany = false;
  if (is_array($smtp)) {
    $enabled = !empty($smtp['enabled']);
    $host = trim((string)($smtp['host'] ?? ''));
    $user = trim((string)($smtp['user'] ?? ''));
    $pass = (string)($smtp['pass'] ?? '');
    if ($enabled && $host !== '' && $user !== '' && $pass !== '') {
      $useCompany = true;
    }
  }

  $host = $useCompany ? (string)$smtp['host'] : MAIL_HOST;
  $port = $useCompany ? (int)($smtp['port'] ?? 587) : (int)MAIL_PORT;
  $user = $useCompany ? (string)$smtp['user'] : MAIL_USER;
  $pass = $useCompany ? (string)$smtp['pass'] : MAIL_PASS;

  $secure = $useCompany ? (string)($smtp['secure'] ?? 'tls') : 'tls';
  $fromEmail = $useCompany ? (string)($smtp['from_email'] ?? $user) : MAIL_FROM_EMAIL;
  $fromName  = $useCompany ? (string)($smtp['from_name'] ?? '') : MAIL_FROM_NAME;

  $fromEmail = trim($fromEmail);
  $fromName  = trim($fromName);

  if ($fromEmail === '') $fromEmail = $user;
  if ($fromName === '')  $fromName  = ($useCompany ? $user : MAIL_FROM_NAME);

  $batches = array_chunk($recipients, $MAX_BCC_PER_EMAIL);

  foreach ($batches as $idx => $batch) {
    $mail = new PHPMailer(true);
    $mail->CharSet = 'UTF-8';

    $mail->isSMTP();
    $mail->Host = $host;
    $mail->SMTPAuth = true;
    $mail->Username = $user;
    $mail->Password = $pass;
    $mail->Port = $port;

    if ($secure === 'ssl') {
      $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    } elseif ($secure === 'none') {
      $mail->SMTPSecure = false;
      $mail->SMTPAutoTLS = false;
    } else {
      $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    }

    // Remetente + “To” (manda pra si mesmo)
    $mail->setFrom($fromEmail, $fromName);
    $mail->addAddress($fromEmail, $fromName);

    // Destinatários reais em BCC (lote)
    foreach ($batch as $r) {
      $mail->addBCC($r);
    }

    // Embeds
    foreach ($embeds as $cid => $path) {
      $cid = trim((string)$cid);
      $path = (string)$path;
      if ($cid === '' || $path === '') continue;

      if (!file_exists($path)) {
        throw new RuntimeException("Embed não encontrado: $cid => $path");
      }
      $mail->addEmbeddedImage($path, $cid, basename($path));
    }

    $mail->isHTML(true);

    $mail->Subject = $subject;

    $mail->Body = $html;
    $mail->AltBody = 'Radar de Notícias';

    $mail->send();
  }
}