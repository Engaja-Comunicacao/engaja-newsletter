<?php

class DbSessionHandler implements SessionHandlerInterface {
  private int $lifetime;

  public function __construct(int $lifetime = 7200) {
    $this->lifetime = $lifetime;
  }

  public function open(string $path, string $name): bool {
    return true;
  }

  public function close(): bool {
    return true;
  }

  public function read(string $id): string|false {
    try {
      $st = db()->prepare("SELECT data FROM sessions WHERE id = ? AND last_activity > ?");
      $st->execute([$id, time() - $this->lifetime]);
      $row = $st->fetch(PDO::FETCH_ASSOC);
      return $row ? $row['data'] : '';
    } catch (Throwable $e) {
      return '';
    }
  }

  public function write(string $id, string $data): bool {
    try {
      $st = db()->prepare("
        INSERT INTO sessions (id, data, last_activity) VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE data = VALUES(data), last_activity = VALUES(last_activity)
      ");
      return $st->execute([$id, $data, time()]);
    } catch (Throwable $e) {
      return false;
    }
  }

  public function destroy(string $id): bool {
    try {
      $st = db()->prepare("DELETE FROM sessions WHERE id = ?");
      return $st->execute([$id]);
    } catch (Throwable $e) {
      return false;
    }
  }

  public function gc(int $max_lifetime): int|false {
    try {
      $st = db()->prepare("DELETE FROM sessions WHERE last_activity < ?");
      $st->execute([time() - $max_lifetime]);
      return $st->rowCount();
    } catch (Throwable $e) {
      return false;
    }
  }
}
