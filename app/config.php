<?php
// app/config.php

define('APP_URL', 'http://localhost/engaja');

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'engaja');
define('DB_USER', 'root');
define('DB_PASS', '');

// Uploads
define('UPLOAD_DIR_HEADERS', __DIR__ . '/../public/uploads/headers');
define('UPLOAD_DIR_PDFS', __DIR__ . '/../public/uploads/pdfs');

// Mail (SMTP)
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USER', 'monitoramento@engajacomunicacao.com.br');
define('MAIL_PASS', '@relations');
define('MAIL_FROM_EMAIL', 'monitoramento@engajacomunicacao.com.br');
define('MAIL_FROM_NAME', 'Engaja Comunicação');

// Segurança
define('SESSION_NAME', 'engaja_session');
