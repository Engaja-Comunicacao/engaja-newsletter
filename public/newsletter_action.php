<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/email_template.php';
require_once __DIR__ . '/../app/mailer.php';

require_login();

$u = current_user();
$userId = (int)($u['id'] ?? 0);

$action = $_GET['action'] ?? '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) redirect('newsletters.php');

$pdo = db();

if ($action === 'duplicate') {
  try {
    $pdo->beginTransaction();

    // 1. Busca newsletter original
    $st = $pdo->prepare("SELECT company_id, mensagem FROM newsletters WHERE id=? LIMIT 1");
    $st->execute([$id]);
    $original = $st->fetch();
    if (!$original) throw new RuntimeException('Newsletter não encontrada.');

    // 2. Cria cópia (draft, sem data de envio)
    $pdo->prepare("INSERT INTO newsletters (company_id, send_at, mensagem, status, created_by_user_id) VALUES (?, NULL, ?, 'draft', ?)")
        ->execute([$original['company_id'], $original['mensagem'], $userId]);
    $newId = (int)$pdo->lastInsertId();

    // 3. Copia categorias e monta mapa id_antigo => id_novo
    $cats = $pdo->prepare("SELECT id, name, sort_order FROM newsletter_categories WHERE newsletter_id=? ORDER BY sort_order ASC, id ASC");
    $cats->execute([$id]);

    $catMap = [];
    $insCat = $pdo->prepare("INSERT INTO newsletter_categories (newsletter_id, name, sort_order) VALUES (?,?,?)");
    foreach ($cats->fetchAll() as $cat) {
      $insCat->execute([$newId, $cat['name'], $cat['sort_order']]);
      $catMap[(int)$cat['id']] = (int)$pdo->lastInsertId();
    }

    // 4. Copia itens remapeando category_id
    $items = $pdo->prepare("SELECT category_id, portal, news_date, title, description, link_url, pdf_path, sort_order FROM newsletter_items WHERE newsletter_id=? ORDER BY sort_order ASC, id ASC");
    $items->execute([$id]);

    $insItem = $pdo->prepare("INSERT INTO newsletter_items (newsletter_id, category_id, portal, news_date, title, description, link_url, pdf_path, sort_order) VALUES (?,?,?,?,?,?,?,?,?)");
    foreach ($items->fetchAll() as $item) {
      $newCatId = $item['category_id'] ? ($catMap[(int)$item['category_id']] ?? null) : null;
      $insItem->execute([
        $newId, $newCatId, $item['portal'], $item['news_date'],
        $item['title'], $item['description'], $item['link_url'],
        $item['pdf_path'], $item['sort_order']
      ]);
    }

    $pdo->commit();
    redirect('newsletter_edit.php?id=' . $newId);

  } catch (Throwable $t) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $_SESSION['flash_error'] = 'Falha ao duplicar: ' . $t->getMessage();
    redirect('newsletters.php');
  }
}

if ($action === 'delete') {
  $pdo->prepare("DELETE FROM newsletters WHERE id=?")->execute([$id]);
  $_SESSION['flash_success'] = 'Newsletter excluída.';
  redirect('newsletters.php');
}

if ($action === 'schedule') {
  $st = $pdo->prepare("SELECT send_at FROM newsletters WHERE id=?");
  $st->execute([$id]);
  $n = $st->fetch();

  if (!$n || empty($n['send_at'])) {
    $_SESSION['flash_error'] = 'Não dá pra agendar: preencha data/hora no cadastro.';
    redirect('newsletter_preview.php?id=' . $id);
  }

  $pdo->prepare("UPDATE newsletters SET status='scheduled', error_message=NULL WHERE id=?")->execute([$id]);
  $_SESSION['flash_success'] = 'Newsletter agendada com sucesso.';
  redirect('newsletters.php');
}

if ($action === 'send_now') {
  try {
    $pdo->prepare("UPDATE newsletters SET status='sending', error_message=NULL WHERE id=?")->execute([$id]);

    [$n, $items, $recipients] = get_newsletter_data($id);
    if (count($recipients) === 0) throw new RuntimeException('A empresa não tem destinatários cadastrados.');
    if (count($items) === 0) throw new RuntimeException('A newsletter não tem notícias.');

    $payload = render_email_send($id);

    $dateStr = date('d/m/Y');
    $subject = "Radar de Notícias - {$dateStr} - " . ($n['company_name'] ?? 'Engaja');

    // SMTP por empresa (se configurado)
    $smtp = null;
    if (!empty($n['smtp_enabled'])) {
      $smtpPass = decrypt_secret($n['smtp_pass_enc'] ?? '');

      $smtp = [
        'enabled'    => true,
        'host'       => (string)($n['smtp_host'] ?? ''),
        'port'       => (int)($n['smtp_port'] ?? 587),
        'user'       => (string)($n['smtp_user'] ?? ''),
        'pass'       => (string)$smtpPass,
        'secure'     => (string)($n['smtp_secure'] ?? 'tls'),
        'from_email' => (string)($n['smtp_from_email'] ?? ''),
        'from_name'  => (string)($n['smtp_from_name'] ?? ''),
      ];
    }

    // ENVIO (com embeds)
    send_newsletter_email($subject, $payload['html'], $recipients, $payload['embeds'], $smtp);

    $pdo->prepare("UPDATE newsletters
      SET status='sent', sent_at=NOW(), sent_by_user_id=?
      WHERE id=?")->execute([$userId, $id]);

    $_SESSION['flash_success'] = 'Newsletter enviada com sucesso!';
    redirect('newsletters.php');

  } catch (Throwable $t) {
    $pdo->prepare("UPDATE newsletters
      SET status='failed', error_message=?, sent_by_user_id=?
      WHERE id=?")->execute([$t->getMessage(), $userId, $id]);

    $_SESSION['flash_error'] = 'Falha ao enviar: ' . $t->getMessage();
    redirect('newsletters.php');
  }
}

redirect('newsletters.php');