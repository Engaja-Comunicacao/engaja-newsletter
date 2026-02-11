<?php
require_once __DIR__ . '/../app/auth.php';
do_logout();
header('Location: login.php');
exit;
