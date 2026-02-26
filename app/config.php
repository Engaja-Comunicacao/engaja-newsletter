<?php
// app/config.php

function env_str(string $key, string $default = ''): string {
  $v = getenv($key);
  return ($v === false || $v === null || $v === '') ? $default : (string)$v;
}

function env_int(string $key, int $default = 0): int {
  $v = getenv($key);
  if ($v === false || $v === null || $v === '') return $default;
  return (int)$v;
}

// ===== APP =====
define('APP_URL', rtrim(env_str('APP_URL', 'http://localhost:8080'), '/'));

// Timezone
$tz = env_str('TZ', 'America/Fortaleza');
date_default_timezone_set($tz);

// Chave para criptografar senhas SMTP por empresa (obrigatória se usar)
define('APP_CRYPT_KEY', env_str('APP_CRYPT_KEY', ''));

// ===== DB =====
define('DB_HOST', env_str('DB_HOST', 'db'));
define('DB_NAME', env_str('DB_NAME', 'engaja'));
define('DB_USER', env_str('DB_USER', 'engaja'));
define('DB_PASS', env_str('DB_PASS', 'engaja123'));

// Uploads (path interno do container)
define('UPLOAD_DIR_HEADERS', __DIR__ . '/../public/uploads/headers');
define('UPLOAD_DIR_PDFS', __DIR__ . '/../public/uploads/pdfs');

// ===== MAIL (SMTP PADRÃO - ENGAJA) =====
define('MAIL_HOST', env_str('MAIL_HOST', 'smtp.gmail.com'));
define('MAIL_PORT', env_int('MAIL_PORT', 587));
define('MAIL_USER', env_str('MAIL_USER', 'monitoramento@engajacomunicacao.com.br'));
define('MAIL_PASS', env_str('MAIL_PASS', 'prar uzta hizw ewgf'));
define('MAIL_FROM_EMAIL', env_str('MAIL_FROM_EMAIL', 'monitoramento@engajacomunicacao.com.br'));
define('MAIL_FROM_NAME', env_str('MAIL_FROM_NAME', 'Engaja Comunicação'));

// Segurança
define('SESSION_NAME', env_str('SESSION_NAME', 'engaja_session'));
define('ENGAJA_SITE_URL', 'https://www.engajacomunicacao.com.br');

/**
 * Criptografia simples (AES-256-CBC) para guardar senhas no banco.
 * Requer APP_CRYPT_KEY definido.
 */
function encrypt_secret(string $plain): string {
  if ($plain === '') return '';
  if (APP_CRYPT_KEY === '') throw new RuntimeException('APP_CRYPT_KEY não configurada.');

  $key = hash('sha256', APP_CRYPT_KEY, true); // 32 bytes
  $iv = random_bytes(16);

  $cipher = openssl_encrypt($plain, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
  if ($cipher === false) throw new RuntimeException('Falha ao criptografar segredo.');

  return base64_encode($iv . $cipher);
}

function decrypt_secret(?string $enc): string {
  $enc = (string)$enc;
  if ($enc === '') return '';
  if (APP_CRYPT_KEY === '') throw new RuntimeException('APP_CRYPT_KEY não configurada.');

  $raw = base64_decode($enc, true);
  if ($raw === false || strlen($raw) < 17) return '';

  $iv = substr($raw, 0, 16);
  $cipher = substr($raw, 16);

  $key = hash('sha256', APP_CRYPT_KEY, true);
  $plain = openssl_decrypt($cipher, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

  return ($plain === false) ? '' : (string)$plain;
}