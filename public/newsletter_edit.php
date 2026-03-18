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

if (!in_array($n['status'], ['draft','failed'], true)) {
  $_SESSION['flash_error'] = 'Não é possível editar uma newsletter com status: ' . $n['status'];
  redirect('newsletter_preview.php?id=' . $id);
}

// carrega categorias
$st = $pdo->prepare("SELECT id, name FROM newsletter_categories WHERE newsletter_id=? ORDER BY sort_order ASC, id ASC");
$st->execute([$id]);
$categories = $st->fetchAll();

// carrega itens
$st = $pdo->prepare("SELECT id, category_id, portal, news_date, title, description, link_url, pdf_path, sort_order
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

    $pdo->beginTransaction();

    // Atualiza newsletter
    $pdo->prepare("UPDATE newsletters SET company_id=?, send_at=?, error_message=NULL WHERE id=?")
        ->execute([$companyId, $sendAtDb, $id]);

    // ===================================
    // 1. Processa Categorias
    // ===================================
    $catIds   = $_POST['category_id'] ?? [];
    $catNames = $_POST['category_name'] ?? [];
    $catRefs  = $_POST['category_ref'] ?? [];
    
    $seenCatIds = [];
    $catIdMap = []; // 'ref_xyz' ou 'ID_ANTIGO' => ID_REAL_NO_BANCO

    $insCat = $pdo->prepare("INSERT INTO newsletter_categories (newsletter_id, name, sort_order) VALUES (?,?,?)");
    $updCat = $pdo->prepare("UPDATE newsletter_categories SET name=?, sort_order=? WHERE id=? AND newsletter_id=?");

    foreach ($catNames as $i => $cName) {
        $cName = trim($cName);
        if ($cName === '') continue;

        $cId = (int)($catIds[$i] ?? 0);
        $ref = trim($catRefs[$i] ?? '');

        if ($cId > 0) {
            $updCat->execute([$cName, $i, $cId, $id]);
            $seenCatIds[] = $cId;
            $catIdMap[$cId] = $cId; // ID que já existia mapeia pra ele mesmo
            if ($ref) $catIdMap[$ref] = $cId;
        } else {
            $insCat->execute([$id, $cName, $i]);
            $newCid = (int)$pdo->lastInsertId();
            if ($ref) $catIdMap[$ref] = $newCid; // Ref nova mapeia pro ID novo inserido
        }
    }

    // Delete das categorias removidas
    $existingCatIds = array_map('intval', array_column($categories, 'id'));
    $catsToDelete = array_diff($existingCatIds, $seenCatIds);
    if (!empty($catsToDelete)) {
        $placeholders = implode(',', array_fill(0, count($catsToDelete), '?'));
        $params = array_merge([$id], array_values($catsToDelete));
        $pdo->prepare("DELETE FROM newsletter_categories WHERE newsletter_id=? AND id IN ($placeholders)")->execute($params);
    }

    // ===================================
    // 2. Processa Notícias
    // ===================================
    $titles   = $_POST['item_title'] ?? [];
    $descs    = $_POST['item_desc'] ?? [];
    $portals  = $_POST['item_portal'] ?? [];
    $dates    = $_POST['item_date'] ?? [];
    $links    = $_POST['item_link'] ?? [];
    $keepPdf  = $_POST['item_keep_pdf'] ?? [];
    $itemIds  = $_POST['item_id'] ?? [];
    $itemRefs = $_POST['item_category_ref'] ?? [];

    $hasAny = false;
    foreach (($titles ?? []) as $t) {
        if (trim((string)$t) !== '') { $hasAny = true; break; }
    }
    if (!$hasAny) throw new RuntimeException('Adicione pelo menos 1 notícia.');

    $seenItemIds = [];
    $existingItemIds = array_map('intval', array_column($items, 'id'));

    $insItem = $pdo->prepare("
      INSERT INTO newsletter_items (newsletter_id, category_id, portal, news_date, title, description, link_url, pdf_path, sort_order)
      VALUES (?,?,?,?,?,?,?,?,?)
    ");

    $updItem = $pdo->prepare("
      UPDATE newsletter_items
      SET category_id=?, portal=?, news_date=?, title=?, description=?, link_url=?, pdf_path=?, sort_order=?
      WHERE id=? AND newsletter_id=?
    ");

    for ($i=0; $i<count($titles); $i++) {
      $itemId = (int)($itemIds[$i] ?? 0);
      $title = trim($titles[$i] ?? '');
      if ($title === '') continue;

      $portal = trim($portals[$i] ?? '');
      $date   = trim($dates[$i] ?? '');
      $desc   = trim($descs[$i] ?? '');
      $link   = trim($links[$i] ?? '');
      $itemRef = trim($itemRefs[$i] ?? '');

      $categoryIdDb = $catIdMap[$itemRef] ?? null;
      $dateDb = $date ? date('Y-m-d', strtotime($date)) : null;

      $oldPdf = $keepPdf[$i] ?? null;
      $newPdf = upload_pdf_from_array('item_pdf', $i, UPLOAD_DIR_PDFS);
      $pdfPath = $newPdf ?: ($oldPdf ?: null);

      if ($itemId > 0) {
        $seenItemIds[] = $itemId;
        $updItem->execute([
          $categoryIdDb, $portal ?: null, $dateDb, $title, $desc ?: null, $link ?: null, $pdfPath, $i, $itemId, $id
        ]);
      } else {
        $insItem->execute([
          $id, $categoryIdDb, $portal ?: null, $dateDb, $title, $desc ?: null, $link ?: null, $pdfPath, $i
        ]);
      }
    }

    // Delete dos itens removidos
    $toDeleteItem = array_diff($existingItemIds, $seenItemIds);
    if (!empty($toDeleteItem)) {
      $placeholders = implode(',', array_fill(0, count($toDeleteItem), '?'));
      $params = array_merge([$id], array_values($toDeleteItem));
      $pdo->prepare("DELETE FROM newsletter_items WHERE newsletter_id=? AND id IN ($placeholders)")->execute($params);
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
      <div><h3 style="margin:0;">Categorias (Opcional)</h3></div>
      <div style="text-align:right;">
        <button type="button" class="secondary" onclick="addCategory()">
          + Criar categoria
        </button>
      </div>
    </div>
    
    <div id="newsItems"></div> 

    <hr style="margin-top: 24px; border-top: 2px dashed #ddd;">

    <div class="row" style="align-items:center;">
      <div><h3 style="margin:0;">Notícias Gerais (Sem Categoria)</h3></div>
      <div style="text-align:right;">
        <button type="button" class="secondary" onclick="addNewsItem()">
          + Adicionar notícia geral
        </button>
      </div>
    </div>
    
    <div id="generalNewsItems"></div>

    <button class="btn" style="margin-top:24px;">Salvar e voltar ao preview</button>
    <a class="btn secondary" style="margin-top:24px;" href="newsletter_preview.php?id=<?= $id ?>">Cancelar</a>
  </form>
</main>

<script src="assets/script.js"></script>
<script>
  window.__existingCats = <?= json_encode($categories, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
  window.__existingItems = <?= json_encode($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
  renderEdit(window.__existingCats, window.__existingItems);
</script>

<?php require_once __DIR__ . '/_footer.php'; ?>