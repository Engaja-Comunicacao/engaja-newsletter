<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/email_template.php';

require_login();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { http_response_code(400); exit('ID inválido'); }

header('Content-Type: text/html; charset=UTF-8');
echo render_email_html($id);
