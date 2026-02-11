<?php
// app/config.php

// Ajuste conforme seu ambiente
define('APP_URL', 'http://localhost/engaja'); // IMPORTANTE: URL pública p/ links e imagens no email

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'engaja');
define('DB_USER', 'root');
define('DB_PASS', '');

// Uploads
define('UPLOAD_DIR_HEADERS', __DIR__ . '/../public/uploads/headers');
define('UPLOAD_DIR_PDFS', __DIR__ . '/../public/uploads/pdfs');

// Mail (SMTP) - ajuste com suas credenciais
define('MAIL_HOST', 'smtp.seudominio.com');
define('MAIL_PORT', 587);
define('MAIL_USER', 'seu_email@seudominio.com');
define('MAIL_PASS', 'SUA_SENHA');
define('MAIL_FROM_EMAIL', 'seu_email@seudominio.com');
define('MAIL_FROM_NAME', 'Engaja Comunicação');

// Segurança
define('SESSION_NAME', 'engaja_session');
