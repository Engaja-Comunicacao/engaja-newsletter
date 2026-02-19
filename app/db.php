<?php
// app/db.php
require_once __DIR__ . '/config.php';

function db(): PDO {
  static $pdo = null;

  // Se já existe, valida rapidamente com ping (reconecta se tiver caído)
  if ($pdo instanceof PDO) {
    try {
      $pdo->query('SELECT 1');
      return $pdo;
    } catch (Throwable $e) {
      // conexão morreu (db reiniciou, etc.)
      $pdo = null;
    }
  }

  $host = DB_HOST;
  $port = (int)(defined('DB_PORT') ? DB_PORT : (getenv('DB_PORT') ?: 3306));
  $dsn = "mysql:host={$host};port={$port};dbname=" . DB_NAME . ";charset=utf8mb4";

  $attempts = 5;
  $sleepMs = 200; // começa com 200ms

  for ($i = 1; $i <= $attempts; $i++) {
    try {
      $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

        // Timeout de conexão
        PDO::ATTR_TIMEOUT => 3,

        // Em mysqlnd, ajuda a evitar “stale connection”
        PDO::ATTR_PERSISTENT => false,
      ]);

      // (Opcional) garante timezone do MySQL coerente
      // $pdo->exec("SET time_zone = '-03:00'");

      // ping de validação
      $pdo->query('SELECT 1');
      return $pdo;

    } catch (PDOException $e) {
      error_log("DB connect attempt {$i}/{$attempts} failed: " . $e->getMessage());

      // última tentativa: responde 503
      if ($i === $attempts) {
        http_response_code(503);
        exit('Sistema temporariamente indisponível. Tente novamente em instantes.');
      }

      // espera um pouco e tenta de novo (backoff)
      usleep($sleepMs * 1000);
      $sleepMs = min($sleepMs * 2, 2000); // até 2s
    }
  }

  // fallback (não deve chegar aqui)
  http_response_code(503);
  exit('Sistema temporariamente indisponível. Tente novamente em instantes.');
}
