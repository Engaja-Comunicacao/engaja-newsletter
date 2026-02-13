<?php
// app/mailer.php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function send_newsletter_email(string $subject, string $html, array $recipients, array $embeds): void {
  $mail = new PHPMailer(true);

  $mail->CharSet = 'UTF-8';

  $mail->isSMTP();
  $mail->Host = MAIL_HOST;
  $mail->SMTPAuth = true;
  $mail->Username = MAIL_USER;
  $mail->Password = MAIL_PASS;
  $mail->Port = MAIL_PORT;
  $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

  $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);

  foreach ($recipients as $r) {
    if (!$r) continue;
    $mail->addAddress($r);
  }

  foreach ($embeds as $cid => $path) {
    if (!is_string($cid) || $cid === '') continue;
    if (!is_string($path) || $path === '') continue;

    if (!file_exists($path)) {
      throw new RuntimeException("Embed não encontrado: $cid => $path");
    }

    $mail->addEmbeddedImage($path, $cid);
  }

  $mail->isHTML(true);
  $mail->Subject = $subject;
  $mail->Body = $html;

  $mail->send();
}
