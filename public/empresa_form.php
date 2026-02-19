<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/helpers.php';
require_login();

$pdo = db();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$company = [
  'name'=>'','header_image_path'=>null,
  'social_1_url'=>null,'social_2_url'=>null,'social_3_url'=>null,'social_4_url'=>null
];
$recipients = [];
$error = '';

if ($id) {
  $st = $pdo->prepare("SELECT * FROM companies WHERE id=?");
  $st->execute([$id]);
  $company = $st->fetch() ?: $company;

  $st = $pdo->prepare("SELECT email FROM company_recipients WHERE company_id=? ORDER BY id ASC");
  $st->execute([$id]);
  $recipients = $st->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_verify();

  try {
    $name = trim($_POST['name'] ?? '');
    if ($name === '') throw new RuntimeException('Nome é obrigatório.');

    $newHeader = upload_image('header_image', UPLOAD_DIR_HEADERS);

    $s1 = trim($_POST['social_1_url'] ?? '');
    $s2 = trim($_POST['social_2_url'] ?? '');
    $s3 = trim($_POST['social_3_url'] ?? '');
    $s4 = trim($_POST['social_4_url'] ?? '');

    if ($id) {
      $sql = "UPDATE companies SET name=?, social_1_url=?, social_2_url=?, social_3_url=?, social_4_url=?"
           . ($newHeader ? ", header_image_path=?" : "")
           . " WHERE id=?";
      $params = [$name, $s1 ?: null, $s2 ?: null, $s3 ?: null, $s4 ?: null];
      if ($newHeader) $params[] = $newHeader;
      $params[] = $id;
      $pdo->prepare($sql)->execute($params);
    } else {
      $pdo->prepare("INSERT INTO companies (name, header_image_path, social_1_url, social_2_url, social_3_url, social_4_url)
                     VALUES (?,?,?,?,?,?)")
          ->execute([$name, $newHeader, $s1 ?: null, $s2 ?: null, $s3 ?: null, $s4 ?: null]);
      $id = (int)$pdo->lastInsertId();
    }

    $emails = $_POST['recipient_emails'] ?? [];
    $emails = array_values(array_unique(array_filter(array_map('trim', $emails))));

    $pdo->prepare("DELETE FROM company_recipients WHERE company_id=?")->execute([$id]);
    $ins = $pdo->prepare("INSERT INTO company_recipients (company_id, email) VALUES (?,?)");
    foreach ($emails as $em) {
      if (!filter_var($em, FILTER_VALIDATE_EMAIL)) continue;
      $ins->execute([$id, $em]);
    }

    redirect('empresas.php');
  } catch (Throwable $t) {
    $error = $t->getMessage();
  }
}

require_once __DIR__ . '/_header.php';
?>
<main class="container card">
  <h2><?= $id ? 'Editar Empresa' : 'Cadastro de Empresa' ?></h2>

  <?php if ($error): ?>
    <p style="color:#ef4444; font-weight:600;"><?= e($error) ?></p>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data">
    <?= csrf_field(); ?>

    <label><small class="muted">Nome da Empresa</small></label>
    <input name="name" value="<?= e($company['name'] ?? '') ?>" placeholder="Nome da Empresa" required>

    <label>
      <small class="muted">
        Imagem topo do email (PNG/JPG/WEBP) — <strong>600x300px</strong>
      </small>
    </label>
    <input type="file" name="header_image" accept="image/png,image/jpeg,image/webp">
    <?php if (!empty($company['header_image_path'])): ?>
      <p><small class="muted">Atual: <?= e($company['header_image_path']) ?></small></p>
    <?php endif; ?>

    <hr>
    <h3 style="margin:0 0 12px 0;">Redes</h3>

    <div class="row">
      <div>
        <label><small class="muted">Instagram</small></label>
        <input name="social_1_url" value="<?= e($company['social_1_url'] ?? '') ?>" placeholder="https://instagram.com/...">
      </div>
      <div>
        <label><small class="muted">Facebook</small></label>
        <input name="social_2_url" value="<?= e($company['social_2_url'] ?? '') ?>" placeholder="https://facebook.com/...">
      </div>
    </div>

    <div class="row">
      <div>
        <label><small class="muted">Linkedin</small></label>
        <input name="social_3_url" value="<?= e($company['social_3_url'] ?? '') ?>" placeholder="https://linkedin.com/company/...">
      </div>
      <div>
        <label><small class="muted">Site</small></label>
        <input name="social_4_url" value="<?= e($company['social_4_url'] ?? '') ?>" placeholder="https://...">
      </div>
    </div>

    <hr>

    <label><small class="muted">Destinatários (clique na tag para remover)</small></label>
    <input id="emailInput" placeholder="E-mail">
    <button type="button" class="secondary" onclick="addEmailTag()">Adicionar e-mail</button>

    <div id="emailsList" class="tags" style="margin-top:8px;">
      <?php foreach ($recipients as $r): ?>
        <span title="Clique para remover" style="cursor:pointer;" onclick="this.remove()">
          <?= e($r['email']) ?>
          <input type="hidden" name="recipient_emails[]" value="<?= e($r['email']) ?>">
        </span>
      <?php endforeach; ?>
    </div>

    <button class="btn" style="margin-top:16px;">Salvar</button>
  </form>
</main>

<script src="assets/script.js"></script>
<?php require_once __DIR__ . '/_footer.php'; ?>
