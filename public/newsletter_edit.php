<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/helpers.php';
require_login();

$u = current_user();
$userId = (int)($u['id'] ?? 0);

$pdo = db();
$companies = $pdo->query("SELECT id,name FROM companies ORDER BY name ASC")->fetchAll();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) redirect('newsletters.php');

$error = '';

// carrega newsletter + status
$st = $pdo->prepare("SELECT id, company_id, send_at, status FROM newsletters WHERE id=? LIMIT 1");
$st->execute([$id]);
$n = $st->fetch();
if (!$n) redirect('newsletters.php');

// só edita se for draft/failed (ajuste se quiser permitir scheduled também)
if (!in_array($n['status'], ['draft','failed'], true)) {
  $_SESSION['flash_error'] = 'Não é possível editar uma newsletter com status: ' . $n['status'];
  redirect('newsletter_preview.php?id=' . $id);
}

// carrega itens
$st = $pdo->prepare("SELECT id, portal, news_date, title, description, link_url, pdf_path, sort_order
                     FROM newsletter_items
                     WHERE newsletter_id=?
                     ORDER BY sort_order ASC, id ASC");
$st->execute([$id]);
$items = $st->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_verify();

  try {
    $companyId = (int)($_POST['company_id'] ?? 0);
    $sendAt = trim($_POST['send_at'] ?? '');

    if (!$companyId) throw new RuntimeException('Selecione a empresa.');
    $sendAtDb = $sendAt ? date('Y-m-d H:i:s', strtotime($sendAt)) : null;

    $titles  = $_POST['item_title'] ?? [];
    $descs   = $_POST['item_desc'] ?? [];
    $portals = $_POST['item_portal'] ?? [];
    $dates   = $_POST['item_date'] ?? [];
    $links   = $_POST['item_link'] ?? [];
    $keepPdf = $_POST['item_keep_pdf'] ?? []; // hidden por item
    $itemIds = $_POST['item_id'] ?? [];       // hidden por item (id do item ou vazio)

    $hasAny = false;
    foreach (($titles ?? []) as $t) {
        if (trim((string)$t) !== '') { $hasAny = true; break; }
    }
    if (!$hasAny) throw new RuntimeException('Adicione pelo menos 1 notícia.');

    $pdo->beginTransaction();

    // Atualiza newsletter
    $pdo->prepare("UPDATE newsletters
                   SET company_id=?, send_at=?, error_message=NULL
                   WHERE id=?")
        ->execute([$companyId, $sendAtDb, $id]);

    // Estratégia simples e segura:
    // - para cada linha enviada no form:
    //   - se tem item_id: UPDATE
    //   - senão: INSERT
    // - itens que existiam e não vieram mais: DELETE
    $existingIds = array_map('intval', array_column($items, 'id'));
    $seenIds = [];

    $ins = $pdo->prepare("
      INSERT INTO newsletter_items (newsletter_id, portal, news_date, title, description, link_url, pdf_path, sort_order)
      VALUES (?,?,?,?,?,?,?,?)
    ");

    $upd = $pdo->prepare("
      UPDATE newsletter_items
      SET portal=?, news_date=?, title=?, description=?, link_url=?, pdf_path=?, sort_order=?
      WHERE id=? AND newsletter_id=?
    ");

    for ($i=0; $i<count($titles); $i++) {
      $itemId = (int)($itemIds[$i] ?? 0);
      $title = trim($titles[$i] ?? '');

      if ($title === '') {
        continue;
      }

      $portal = trim($portals[$i] ?? '');
      $date   = trim($dates[$i] ?? '');
      $desc   = trim($descs[$i] ?? '');
      $link   = trim($links[$i] ?? '');

      $dateDb = $date ? date('Y-m-d', strtotime($date)) : null;

      // mantém pdf antigo se não enviar novo
      $oldPdf = $keepPdf[$i] ?? null;
      $newPdf = upload_pdf_from_array('item_pdf', $i, UPLOAD_DIR_PDFS);
      $pdfPath = $newPdf ?: ($oldPdf ?: null);

      if ($itemId > 0) {
        $seenIds[] = $itemId;
        $upd->execute([
          $portal ?: null,
          $dateDb,
          $title,
          $desc ?: null,
          $link ?: null,
          $pdfPath,
          $i,
          $itemId,
          $id
        ]);
      } else {
        $ins->execute([
          $id,
          $portal ?: null,
          $dateDb,
          $title,
          $desc ?: null,
          $link ?: null,
          $pdfPath,
          $i
        ]);
      }
    }

    // delete dos removidos
    $toDelete = array_diff($existingIds, array_unique(array_map('intval', $seenIds)));
    if (!empty($toDelete)) {
      $placeholders = implode(',', array_fill(0, count($toDelete), '?'));
      $params = array_merge([$id], array_values($toDelete));
      $pdo->prepare("DELETE FROM newsletter_items WHERE newsletter_id=? AND id IN ($placeholders)")
          ->execute($params);
    }

    $pdo->commit();

    redirect('newsletter_preview.php?id=' . $id);

  } catch (Throwable $t) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $error = $t->getMessage();
  }
}

require_once __DIR__ . '/_header.php';
?>
<main class="container card">
  <h2>Editar Newsletter #<?= $id ?></h2>

  <?php if ($error): ?>
    <p style="color:#ef4444; font-weight:600;"><?= e($error) ?></p>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data">
    <?= csrf_field(); ?>

    <label><small class="muted">Empresa</small></label>
    <select name="company_id" required>
      <option value="">Selecione...</option>
      <?php foreach ($companies as $c): ?>
        <option value="<?= (int)$c['id'] ?>" <?= ((int)$n['company_id'] === (int)$c['id']) ? 'selected' : '' ?>>
          <?= e($c['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <label><small class="muted">Data e hora do envio (opcional, para agendar)</small></label>
    <input type="datetime-local" name="send_at"
      value="<?= $n['send_at'] ? e(date('Y-m-d\TH:i', strtotime($n['send_at']))) : '' ?>">

    <hr>

    <div class="row" style="align-items:center;">
      <div><h3 style="margin:0;">Notícias</h3></div>
      <div style="text-align:right;">
        <button type="button" class="secondary" onclick="addNewsItem()">+ Adicionar notícia</button>
      </div>
    </div>

    <div id="newsItems"></div>

    <button class="btn" style="margin-top:16px;">Salvar e voltar ao preview</button>
    <a class="btn secondary" style="margin-top:16px;" href="newsletter_preview.php?id=<?= $id ?>">Cancelar</a>
  </form>
</main>

<script src="assets/script.js"></script>
<script>
  window.__existingItems = <?= json_encode($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
  addNewsItem(window.__existingItems);
</script>

<?php require_once __DIR__ . '/_footer.php'; ?>