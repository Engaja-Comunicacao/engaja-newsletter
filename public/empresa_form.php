<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/config.php';
require_login();

$pdo = db();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$company = [
  'name'=>'','header_image_path'=>null,
  'social_1_url'=>null,'social_2_url'=>null,'social_3_url'=>null,'social_4_url'=>null,

  'smtp_enabled'=>0,
  'smtp_host'=>null,
  'smtp_port'=>null,
  'smtp_user'=>null,
  'smtp_pass_enc'=>null,
  'smtp_secure'=>'tls',
  'smtp_from_email'=>null,
  'smtp_from_name'=>null
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

    // SMTP fields
    $smtpEnabled = isset($_POST['smtp_enabled']) ? 1 : 0;
    $smtpHost = trim($_POST['smtp_host'] ?? '');
    $smtpPort = (int)($_POST['smtp_port'] ?? 0);
    $smtpUser = trim($_POST['smtp_user'] ?? '');
    $smtpPass = (string)($_POST['smtp_pass'] ?? ''); // vem “crua”
    $smtpSecure = trim($_POST['smtp_secure'] ?? 'tls');
    if (!in_array($smtpSecure, ['tls','ssl','none'], true)) $smtpSecure = 'tls';

    $smtpFromEmail = trim($_POST['smtp_from_email'] ?? '');
    $smtpFromName  = trim($_POST['smtp_from_name'] ?? '');

    // validações básicas se habilitado
    $smtpPassEnc = null;
    if ($smtpEnabled) {
      if ($smtpHost === '') throw new RuntimeException('SMTP habilitado: informe o Host.');
      if ($smtpPort <= 0) $smtpPort = 587;
      if ($smtpUser === '') throw new RuntimeException('SMTP habilitado: informe o Usuário (email).');

      // senha só é obrigatória se não existir ainda OU se usuário quer trocar
      $currentEnc = $company['smtp_pass_enc'] ?? null;
      $hasCurrent = is_string($currentEnc) && $currentEnc !== '';

      if (!$hasCurrent && trim($smtpPass) === '') {
        throw new RuntimeException('SMTP habilitado: informe o App Password (primeira vez).');
      }

      if (trim($smtpPass) !== '') {
        $smtpPassEnc = encrypt_secret($smtpPass);
      }
    } else {
      // se desabilitar, opcionalmente limpa tudo (aqui vamos manter salvo, só desliga)
      if ($smtpPort <= 0) $smtpPort = 587;
    }

    if ($id) {
      $sql = "UPDATE companies SET
                name=?,
                social_1_url=?, social_2_url=?, social_3_url=?, social_4_url=?,
                smtp_enabled=?, smtp_host=?, smtp_port=?, smtp_user=?, smtp_secure=?, smtp_from_email=?, smtp_from_name=?"
           . ($smtpPassEnc ? ", smtp_pass_enc=?" : "")
           . ($newHeader ? ", header_image_path=?" : "")
           . " WHERE id=?";

      $params = [
        $name,
        $s1 ?: null, $s2 ?: null, $s3 ?: null, $s4 ?: null,
        $smtpEnabled,
        $smtpHost ?: null,
        $smtpPort ?: null,
        $smtpUser ?: null,
        $smtpSecure ?: null,
        $smtpFromEmail ?: null,
        $smtpFromName ?: null
      ];

      if ($smtpPassEnc) $params[] = $smtpPassEnc;
      if ($newHeader) $params[] = $newHeader;
      $params[] = $id;

      $pdo->prepare($sql)->execute($params);

    } else {
      $pdo->prepare("INSERT INTO companies (
          name, header_image_path,
          social_1_url, social_2_url, social_3_url, social_4_url,
          smtp_enabled, smtp_host, smtp_port, smtp_user, smtp_pass_enc, smtp_secure, smtp_from_email, smtp_from_name
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
      )->execute([
        $name, $newHeader,
        $s1 ?: null, $s2 ?: null, $s3 ?: null, $s4 ?: null,
        $smtpEnabled,
        $smtpHost ?: null,
        $smtpPort ?: null,
        $smtpUser ?: null,
        $smtpPassEnc ?: null,
        $smtpSecure ?: 'tls',
        $smtpFromEmail ?: null,
        $smtpFromName ?: null
      ]);

      $id = (int)$pdo->lastInsertId();
    }

    // recipients
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
    <h3 style="margin:0 0 12px 0;">Envio por email do cliente (SMTP)</h3>

    <label style="display:flex; align-items:center; gap:10px;">
      <input type="checkbox" name="smtp_enabled" value="1" <?= !empty($company['smtp_enabled']) ? 'checked' : '' ?>>
      <small class="muted">Habilitar envio pelo SMTP desta empresa</small>
    </label>

    <div class="row" style="margin-top:10px;">
      <div>
        <label><small class="muted">SMTP Host</small></label>
        <input name="smtp_host" value="<?= e($company['smtp_host'] ?? '') ?>" placeholder="smtp.gmail.com">
      </div>
      <div>
        <label><small class="muted">SMTP Port</small></label>
        <input name="smtp_port" value="<?= e((string)($company['smtp_port'] ?? '587')) ?>" placeholder="587">
      </div>
    </div>

    <div class="row">
      <div>
        <label><small class="muted">SMTP Usuário (email)</small></label>
        <input name="smtp_user" value="<?= e($company['smtp_user'] ?? '') ?>" placeholder="ex: newsletter@cliente.com.br">
      </div>
      <div>
        <label><small class="muted">Segurança</small></label>
        <select name="smtp_secure">
          <option value="tls" <?= (($company['smtp_secure'] ?? 'tls') === 'tls') ? 'selected' : '' ?>>TLS (STARTTLS)</option>
          <option value="ssl" <?= (($company['smtp_secure'] ?? '') === 'ssl') ? 'selected' : '' ?>>SSL</option>
          <option value="none" <?= (($company['smtp_secure'] ?? '') === 'none') ? 'selected' : '' ?>>Nenhuma</option>
        </select>
      </div>
    </div>

    <label><small class="muted">App Password (somente se quiser cadastrar/trocar)</small></label>
    <input type="password" name="smtp_pass" placeholder="<?= !empty($company['smtp_pass_enc']) ? 'Deixe vazio para manter' : 'Obrigatório na primeira configuração' ?>">

    <div class="row" style="margin-top:10px;">
      <div>
        <label><small class="muted">From Email (opcional)</small></label>
        <input name="smtp_from_email" value="<?= e($company['smtp_from_email'] ?? '') ?>" placeholder="ex: newsletter@cliente.com.br">
      </div>
      <div>
        <label><small class="muted">From Nome (opcional)</small></label>
        <input name="smtp_from_name" value="<?= e($company['smtp_from_name'] ?? '') ?>" placeholder="ex: ABIHV">
      </div>
    </div>

    <p><small class="muted">
      Se habilitar SMTP e preencher credenciais, o envio dessa empresa será autenticado no email do cliente (melhor para “aparecer como eles”).
      Se desabilitar, continua enviando pelo SMTP padrão da Engaja.
    </small></p>

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