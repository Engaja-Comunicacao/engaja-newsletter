<?php
require_once __DIR__ . '/../app/auth.php';
require_login();

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

    $pdo->prepare("INSERT INTO newsletters (company_id, send_at, status) VALUES (?,?, 'draft')")
        ->execute([$companyId, $sendAtDb]);
    $newsletterId = (int)$pdo->lastInsertId();

    $titles  = $_POST['item_title'] ?? [];
    $descs   = $_POST['item_desc'] ?? [];
    $portals = $_POST['item_portal'] ?? [];
    $dates   = $_POST['item_date'] ?? [];
    $links   = $_POST['item_link'] ?? [];

    if (count($titles) === 0) throw new RuntimeException('Adicione pelo menos 1 notícia.');

    $ins = $pdo->prepare("
      INSERT INTO newsletter_items (newsletter_id, portal, news_date, title, description, link_url, pdf_path, sort_order)
      VALUES (?,?,?,?,?,?,?,?)
    ");

    for ($i=0; $i<count($titles); $i++) {
      $title = trim($titles[$i] ?? '');
      if ($title === '') continue;

      $portal = trim($portals[$i] ?? '');
      $date   = trim($dates[$i] ?? '');
      $desc   = trim($descs[$i] ?? '');
      $link   = trim($links[$i] ?? '');

      $dateDb = $date ? date('Y-m-d', strtotime($date)) : null;
      $pdfPath = upload_pdf_from_array('item_pdf', $i, UPLOAD_DIR_PDFS);

      $ins->execute([
        $newsletterId,
        $portal ?: null,
        $dateDb,
        $title,
        $desc ?: null,
        $link ?: null,
        $pdfPath,
        $i
      ]);
    }

    redirect('newsletter_preview.php?id=' . $newsletterId);
  } catch (Throwable $t) {
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
      <div><h3 style="margin:0;">Notícias</h3></div>
      <div style="text-align:right;">
        <button type="button" class="secondary" onclick="addNewsItem()">+ Adicionar notícia</button>
      </div>
    </div>

    <div id="newsItems"></div>

    <button class="btn" style="margin-top:16px;">Ir para preview</button>
  </form>
</main>

<script src="assets/script.js"></script>
<script>addNewsItem();</script>

<?php require_once __DIR__ . '/_footer.php'; ?>
