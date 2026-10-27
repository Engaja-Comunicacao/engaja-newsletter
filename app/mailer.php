<?php
// app/mailer.php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;

/**
 * Monta a config SMTP da empresa a partir da linha da newsletter (get_newsletter_data).
 * Retorna null se a empresa não usa SMTP próprio.
 */
function company_smtp_config(array $n): ?array {
  if (empty($n['smtp_enabled'])) return null;

  return [
    'enabled'    => true,
    'host'       => (string)($n['smtp_host'] ?? ''),
    'port'       => (int)($n['smtp_port'] ?? 587),
    'user'       => (string)($n['smtp_user'] ?? ''),
    'pass'       => decrypt_secret($n['smtp_pass_enc'] ?? ''),
    'secure'     => (string)($n['smtp_secure'] ?? 'tls'),
    'from_email' => (string)($n['smtp_from_email'] ?? ''),
    'from_name'  => (string)($n['smtp_from_name'] ?? ''),
  ];
}

/**
 * Garante a coluna newsletters.sent_via (bancos criados antes dela existir).
 */
function ensure_sent_via_column(PDO $pdo): void {
  static $done = false;
  if ($done) return;

  $st = $pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'newsletters' AND COLUMN_NAME = 'sent_via'");
  if ((int)$st->fetchColumn() === 0) {
    $pdo->exec("ALTER TABLE newsletters ADD COLUMN sent_via VARCHAR(255) NULL AFTER sent_at");
  }
  $done = true;
}

/**
 * Envia a newsletter e retorna por qual SMTP saiu (para registrar em newsletters.sent_via).
 */
function send_newsletter_email(
  string $subject,
  string $html,
  array $recipients,
  array $embeds,
  ?array $smtp = null
): string {

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
    if ($enabled) {
      // Empresa marcou SMTP próprio: não cair silenciosamente no SMTP da Engaja
      if ($host === '' || $user === '') {
        throw new RuntimeException('SMTP da empresa habilitado, mas Host/Usuário não preenchidos.');
      }
      if ($pass === '') {
        throw new RuntimeException('SMTP da empresa habilitado, mas a senha não pôde ser lida (APP_CRYPT_KEY ausente/diferente). Salve a senha novamente no cadastro da empresa.');
      }
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

  return ($useCompany ? 'SMTP da empresa' : 'SMTP Engaja') . " ({$user} / {$host})";
}