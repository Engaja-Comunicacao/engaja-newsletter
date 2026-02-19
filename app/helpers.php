<?php
// app/helpers.php

function e(string $v): string {
  return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void {
  header('Location: ' . $path);
  exit;
}

// CSRF
function csrf_token(): string {
  if (empty($_SESSION['_csrf'])) {
    $_SESSION['_csrf'] = bin2hex(random_bytes(16));
  }
  return $_SESSION['_csrf'];
}

function csrf_field(): string {
  return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_verify(): void {
  $ok = isset($_POST['_csrf'], $_SESSION['_csrf']) && hash_equals($_SESSION['_csrf'], $_POST['_csrf']);
  if (!$ok) {
    http_response_code(403);
    exit('CSRF inválido');
  }
}

function ensure_dir(string $dir): void {
  if (!is_dir($dir)) {
    mkdir($dir, 0775, true);
  }
}

function upload_image(string $field, string $destDir): ?string {
  if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) return null;
  if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Erro no upload da imagem.');

  $tmp = $_FILES[$field]['tmp_name'];
  $finfo = new finfo(FILEINFO_MIME_TYPE);
  $mime = $finfo->file($tmp);

  $allowed = [
    'image/png'  => 'png',
    'image/jpeg' => 'jpg',
    'image/webp' => 'webp'
  ];
  if (!isset($allowed[$mime])) throw new RuntimeException('Imagem inválida. Use PNG/JPG/WEBP.');

  ensure_dir($destDir);
  $name = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
  $full = rtrim($destDir, '/\\') . DIRECTORY_SEPARATOR . $name;

  if (!move_uploaded_file($tmp, $full)) throw new RuntimeException('Falha ao salvar imagem.');

  return '/uploads/headers/' . $name;
}

function upload_pdf_from_array(string $field, int $idx, string $destDir): ?string {
  if (empty($_FILES[$field]) || !isset($_FILES[$field]['name'][$idx])) return null;
  if ($_FILES[$field]['error'][$idx] === UPLOAD_ERR_NO_FILE) return null;
  if ($_FILES[$field]['error'][$idx] !== UPLOAD_ERR_OK) throw new RuntimeException('Erro no upload do PDF.');

  $tmp = $_FILES[$field]['tmp_name'][$idx];
  $finfo = new finfo(FILEINFO_MIME_TYPE);
  $mime = $finfo->file($tmp);

  if ($mime !== 'application/pdf') throw new RuntimeException('PDF inválido.');

  ensure_dir($destDir);
  $name = bin2hex(random_bytes(16)) . '.pdf';
  $full = rtrim($destDir, '/\\') . DIRECTORY_SEPARATOR . $name;

  if (!move_uploaded_file($tmp, $full)) throw new RuntimeException('Falha ao salvar PDF.');

  return '/uploads/pdfs/' . $name;
}

function format_ptbr_upper(?string $dateYmd): string {
  if (!$dateYmd) return '';
  $ts = strtotime($dateYmd);
  $day = date('d', $ts);

  $months = [
    '01'=>'JANEIRO','02'=>'FEVEREIRO','03'=>'MARÇO','04'=>'ABRIL','05'=>'MAIO','06'=>'JUNHO',
    '07'=>'JULHO','08'=>'AGOSTO','09'=>'SETEMBRO','10'=>'OUTUBRO','11'=>'NOVEMBRO','12'=>'DEZEMBRO'
  ];
  $m = $months[date('m', $ts)] ?? '';
  return $day . ' DE ' . $m;
}

/**
 * Converte um caminho público (ex: /uploads/headers/a.png) em caminho absoluto no FS.
 * Procura dentro de /public.
 */
function public_fs_path(string $publicPath): ?string {
  $publicPath = '/' . ltrim($publicPath, '/');

  $base = realpath(__DIR__ . '/../public');
  if (!$base) return null;

  // Caminho "cru"
  $raw = __DIR__ . '/../public' . $publicPath;

  // Se existir, ok. Se não, tenta realpath (ou vice-versa)
  $full = file_exists($raw) ? $raw : realpath($raw);
  if ($full === false || $full === null) return null;

  // Proteção simples contra traversal
  $baseN = str_replace('\\', '/', $base);
  $fullN = str_replace('\\', '/', $full);
  if (strpos($fullN, $baseN) !== 0) return null;

  return $full;
}

/**
 * Junta URLs sem duplicar barras.
 */
function url_join(string $base, string $path): string {
  return rtrim($base, '/') . '/' . ltrim($path, '/');
}
