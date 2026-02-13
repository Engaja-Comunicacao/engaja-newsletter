<?php
// app/config.php

// Helpers simples pra ler ENV com fallback
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

// Timezone (Docker já passa TZ, mas deixo por garantia)
$tz = env_str('TZ', 'America/Fortaleza');
date_default_timezone_set($tz);

// ===== DB =====
// Dentro do Docker o host é "db" (nome do serviço)
define('DB_HOST', env_str('DB_HOST', 'db'));
define('DB_NAME', env_str('DB_NAME', 'engaja'));
define('DB_USER', env_str('DB_USER', 'engaja'));
define('DB_PASS', env_str('DB_PASS', 'engaja123'));

// Uploads (path interno do container)
define('UPLOAD_DIR_HEADERS', __DIR__ . '/../public/uploads/headers');
define('UPLOAD_DIR_PDFS', __DIR__ . '/../public/uploads/pdfs');

// ===== MAIL (SMTP) =====
// (Se quiser, também dá pra jogar isso em ENV depois)
define('MAIL_HOST', env_str('MAIL_HOST', 'smtp.gmail.com'));
define('MAIL_PORT', env_int('MAIL_PORT', 587));
define('MAIL_USER', env_str('MAIL_USER', 'monitoramento@engajacomunicacao.com.br'));
define('MAIL_PASS', env_str('MAIL_PASS', 'prar uzta hizw ewgf'));
define('MAIL_FROM_EMAIL', env_str('MAIL_FROM_EMAIL', 'monitoramento@engajacomunicacao.com.br'));
define('MAIL_FROM_NAME', env_str('MAIL_FROM_NAME', 'Engaja Comunicação'));

// Segurança
define('SESSION_NAME', env_str('SESSION_NAME', 'engaja_session'));
