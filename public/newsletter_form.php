<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/helpers.php';
require_login();

$u = current_user();
$userId = (int)($u['id'] ?? 0);

$pdo = db();
$companies = $pdo->query("SELECT id,name FROM companies ORDER BY name ASC")->fetchAll();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_verify();

  try {
    $companyId = (int)($_POST['company_id'] ?? 0);
    $sendAt = trim($_POST['send_at'] ?? '');

    if (!$companyId) throw new RuntimeException('Selecione a empresa.');

    $sendAtDb = $sendAt ? date('Y-m-d H:i:s', strtotime($sendAt)) : null;

    $pdo->beginTransaction();

    // 1. Cria a Newsletter
    $pdo->prepare("INSERT INTO newsletters (company_id, send_at, status, created_by_user_id) VALUES (?,?, 'draft', ?)")
        ->execute([$companyId, $sendAtDb, $userId]);
    $newsletterId = (int)$pdo->lastInsertId();

    // 2. Processa Categorias
    $catNames = $_POST['category_name'] ?? [];
    $catRefs  = $_POST['category_ref'] ?? [];
    $catIdMap = []; // Vai guardar: 'ref_xyz' => ID_REAL_DO_BANCO

    $insCat = $pdo->prepare("INSERT INTO newsletter_categories (newsletter_id, name, sort_order) VALUES (?,?,?)");
    
    foreach ($catNames as $i => $cName) {
        $cName = trim($cName);
        if ($cName === '') continue;
        
        $ref = trim($catRefs[$i] ?? '');
        $insCat->execute([$newsletterId, $cName, $i]);
        $realId = (int)$pdo->lastInsertId();
        
        if ($ref) {
            $catIdMap[$ref] = $realId;
        }
    }

    // 3. Processa Notícias
    $titles  = $_POST['item_title'] ?? [];
    $descs   = $_POST['item_desc'] ?? [];
    $portals = $_POST['item_portal'] ?? [];
    $dates   = $_POST['item_date'] ?? [];
    $links   = $_POST['item_link'] ?? [];
    $itemRefs = $_POST['item_category_ref'] ?? [];

    $hasAny = false;
    foreach (($titles ?? []) as $t) {
      if (trim((string)$t) !== '') { $hasAny = true; break; }
    }
    if (!$hasAny) throw new RuntimeException('Adicione pelo menos 1 notícia.');

    $insItem = $pdo->prepare("
      INSERT INTO newsletter_items (newsletter_id, category_id, portal, news_date, title, description, link_url, pdf_path, sort_order)
      VALUES (?,?,?,?,?,?,?,?,?)
    ");

    for ($i=0; $i<count($titles); $i++) {
      $title = trim($titles[$i] ?? '');
      if ($title === '') continue;

      $portal = trim($portals[$i] ?? '');
      $date   = trim($dates[$i] ?? '');
      $desc   = trim($descs[$i] ?? '');
      $link   = trim($links[$i] ?? '');
      $itemRef = trim($itemRefs[$i] ?? '');

      // Pega o ID real da categoria mapeada ou NULL se não tiver categoria
      $categoryIdDb = $catIdMap[$itemRef] ?? null;

      $dateDb = $date ? date('Y-m-d', strtotime($date)) : null;
      $pdfPath = upload_pdf_from_array('item_pdf', $i, UPLOAD_DIR_PDFS);

      $insItem->execute([
        $newsletterId,
        $categoryIdDb,
        $portal ?: null,
        $dateDb,
        $title,
        $desc ?: null,
        $link ?: null,
        $pdfPath,
        $i
      ]);
    }

    $pdo->commit();
    redirect('newsletter_preview.php?id=' . $newsletterId);
  } catch (Throwable $t) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $error = $t->getMessage();
  }
}

require_once __DIR__ . '/_header.php';
?>
<main class="container card">
  <h2>Cadastro de Newsletter</h2>

  <?php if ($error): ?>
    <p style="color:#ef4444; font-weight:600;"><?= e($error) ?></p>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data">
    <?= csrf_field(); ?>

    <label><small class="muted">Empresa</small></label>
    <select name="company_id" required>
      <option value="">Selecione...</option>
      <?php foreach ($companies as $c): ?>
        <option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option>
      <?php endforeach; ?>
    </select>

    <label><small class="muted">Data e hora do envio (opcional, para agendar)</small></label>
    <input type="datetime-local" name="send_at">

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

    <div style="text-align:right; margin-top:10px;">
      <button type="button" class="secondary" onclick="addNewsItem()">
        + Adicionar notícia geral
      </button>
    </div>

    <button class="btn" style="margin-top:24px;">Ir para preview</button>
  </form>
</main>

<script src="assets/script.js"></script>
<script>addNewsItem();</script>

<?php require_once __DIR__ . '/_footer.php'; ?>
