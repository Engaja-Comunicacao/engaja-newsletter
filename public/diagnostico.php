<?php
require_once __DIR__ . '/_header.php';
require_once __DIR__ . '/../app/mailer.php';

$pdo = db();
ensure_sent_via_column($pdo);

$companies = $pdo->query("SELECT id, name, smtp_enabled, smtp_host, smtp_port, smtp_user, smtp_pass_enc, smtp_secure, smtp_from_email
  FROM companies ORDER BY name ASC")->fetchAll();

$sent = $pdo->query("SELECT n.id, c.name AS company_name, n.status, n.send_at, n.sent_at, n.sent_via, n.error_message
  FROM newsletters n JOIN companies c ON c.id = n.company_id
  WHERE n.status IN ('sent','failed','scheduled','sending')
  ORDER BY COALESCE(n.sent_at, n.send_at) DESC LIMIT 15")->fetchAll();

$hasKey = APP_CRYPT_KEY !== '';

// Lê a senha só para saber se a chave consegue descriptografar (nunca exibe)
function smtp_pass_status(?string $enc, bool $hasKey): string {
  if (empty($enc)) return '❌ sem senha salva';
  if (!$hasKey) return '❌ APP_CRYPT_KEY ausente';
  return decrypt_secret($enc) !== '' ? '✅ OK' : '❌ não descriptografa (chave diferente)';
}
?>
<main class="container px-20 py-10">
  <h2 class="text-5xl text-cinza font-extrabold">Diagnóstico</h2>

  <div class="bg-white p-4 rounded-lg mt-6 shadow-lg">
    <p><strong>Versão deste site (container web):</strong> <?= e(APP_VERSION) ?></p>
    <p><strong>APP_CRYPT_KEY no container web:</strong> <?= $hasKey ? '✅ configurada' : '❌ ausente' ?></p>
    <p><small class="muted">
      O agendador roda em outro container. A versão dele aparece em “Enviado via” de cada newsletter agendada.
      Se uma newsletter agendada foi enviada e “Enviado via” está vazio, o agendador do servidor ainda está com código antigo.
    </small></p>
  </div>

  <h3 class="text-2xl font-extrabold mt-8">SMTP por empresa</h3>
  <div class="bg-white p-4 rounded-lg mt-4 shadow-lg">
    <table>
      <tr><th>Empresa</th><th>SMTP próprio</th><th>Host</th><th>Usuário</th><th>Segurança</th><th>Senha</th></tr>
      <?php foreach ($companies as $c): ?>
        <tr>
          <td><?= e($c['name']) ?></td>
          <td><?= !empty($c['smtp_enabled']) ? '✅' : '—' ?></td>
          <td><?= e(($c['smtp_host'] ?? '') . (!empty($c['smtp_port']) ? ':' . $c['smtp_port'] : '')) ?></td>
          <td><?= e($c['smtp_user'] ?? '') ?></td>
          <td><?= e($c['smtp_secure'] ?? '') ?></td>
          <td><?= !empty($c['smtp_enabled']) ? e(smtp_pass_status($c['smtp_pass_enc'], $hasKey)) : '—' ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>

  <h3 class="text-2xl font-extrabold mt-8">Últimos envios</h3>
  <div class="bg-white p-4 rounded-lg mt-4 shadow-lg">
    <table>
      <tr><th>#</th><th>Empresa</th><th>Status</th><th>Agendado</th><th>Enviado</th><th>Enviado via / erro</th></tr>
      <?php foreach ($sent as $n): ?>
        <tr>
          <td><a class="underline" href="newsletter_preview.php?id=<?= (int)$n['id'] ?>"><?= (int)$n['id'] ?></a></td>
          <td><?= e($n['company_name']) ?></td>
          <td><?= e($n['status']) ?></td>
          <td><?= e($n['send_at'] ?? '') ?></td>
          <td><?= e($n['sent_at'] ?? '') ?></td>
          <td>
            <?php if ($n['status'] === 'failed'): ?>
              <?= e($n['error_message'] ?? '') ?>
            <?php elseif ($n['status'] === 'sent'): ?>
              <?= !empty($n['sent_via']) ? e($n['sent_via']) : 'não registrado (versão antiga)' ?>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>
</main>
<?php require_once __DIR__ . '/_footer.php'; ?>
