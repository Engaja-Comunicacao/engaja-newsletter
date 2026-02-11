<?php
// app/mailer.php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function send_newsletter_email(string $subject, string $html, array $recipients): void {
  $mail = new PHPMailer(true);

  $mail->isSMTP();
  $mail->Host = MAIL_HOST;
  $mail->SMTPAuth = true;
  $mail->Username = MAIL_USER;
  $mail->Password = MAIL_PASS;
  $mail->Port = MAIL_PORT;
  // Se quiser TLS:
  $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

  $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
  foreach ($recipients as $r) {
    $mail->addAddress($r);
  }

  $mail->isHTML(true);
  $mail->Subject = $subject;
  $mail->Body = $html;

  $mail->send();
}
