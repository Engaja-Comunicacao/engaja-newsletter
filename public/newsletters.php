<?php
require_once __DIR__ . '/_header.php';

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$rows = db()->query('
  SELECT
    n.id,
    n.status,
    n.send_at,
    n.sent_at,
    n.created_at,
    c.name AS company_name,
    u_send.name AS sent_by_name,
    u_create.name AS created_by_name
  FROM newsletters n
  JOIN companies c ON c.id = n.company_id
  LEFT JOIN users u_send ON u_send.id = n.sent_by_user_id
  LEFT JOIN users u_create ON u_create.id = n.created_by_user_id
  ORDER BY n.id DESC
')->fetchAll();
?>
<main class="container px-20 py-10">
  <div class="flex justify-between items-center">
    <h2 class="text-5xl text-cinza font-extrabold">Newsletters</h2>
    <a class="btn rounded-lg leading-10 px-8 font-bold bg-cinza text-white text-sm duration-150 hover:bg-yellow-500 hover:text-cinza" href="newsletter_form.php">Nova Newsletter</a>
  </div>

  <div class="bg-white p-4 rounded-lg mt-6 shadow-lg">
    <?php if ($flashSuccess): ?>
      <div style="margin-top:14px; background:#dcfce7; padding:12px; border-radius:10px; color:#166534; font-weight:600;">
        <?= e($flashSuccess) ?>
      </div>
    <?php endif; ?>
  
    <?php if ($flashError): ?>
      <div style="margin-top:14px; background:#fee2e2; padding:12px; border-radius:10px; color:#991b1b; font-weight:600;">
        <?= e($flashError) ?>
      </div>
    <?php endif; ?>
  
    <table style="margin-top:14px;">
      <tr>
        <th>ID</th>
        <th>Empresa</th>
        <th>Status</th>
        <th>Envio</th>
        <th>Usuário</th>
        <th>Ações</th>
      </tr>
  
      <?php foreach ($rows as $r): ?>
        <?php
        $envio = $r['sent_at'] ?? $r['send_at'] ?? null;
        $userName = $r['sent_by_name'] ?? $r['created_by_name'] ?? null;
        ?>
        <tr>
          <td class="text-sm">#<?= (int) $r['id'] ?></td>
          <td class="text-sm"><?= e($r['company_name']) ?></td>
          <td class="text-center">
            <?php 
              if($r['status'] == "sent"){
            ?>
                <div class="inline-block uppercase leading-5 duration-150 text-xs rounded-sm border px-1.5 font-bold bg-green-500 text-white">
                Enviado
              </div>
            <?php
              }
            ?>
            <?php 
              if($r['status'] == "draft"){
            ?>
                <div class="inline-block uppercase leading-5 duration-150 text-xs rounded-sm border px-1.5 font-bold bg-blue-500 text-white">
                Rascunho
              </div>
            <?php
              }
            ?>
            <?php 
              if($r['status'] == "failed"){
            ?>
                <div class="inline-block uppercase leading-5 duration-150 text-xs rounded-sm border px-1.5 font-bold bg-red-500 text-white">
                Falhou
              </div>
            <?php
              }
            ?>
          </td>
          <td class="text-sm"><?= e($envio ? date("d/m/Y H:i:s", strtotime($envio)) : 'Não enviado') ?></td>
          <td class="text-sm"><?= e($userName ?: '—') ?></td>
          <td class="w-84">
            <div class="flex justify-between items-center">
              <a class="btn block leading-5 duration-150 text-xs rounded-sm border px-1.5 font-semibold border-cinza text-cinza hover:bg-cinza hover:text-white" href="newsletter_preview.php?id=<?= (int) $r['id'] ?>">Preview</a>
              <a class="btn block leading-5 duration-150 text-xs rounded-sm border px-1.5 font-semibold border-cinza text-cinza hover:bg-cinza hover:text-white" href="newsletter_action.php?action=duplicate&id=<?= (int) $r['id'] ?>" onclick="return confirm('Duplicar esta newsletter?')">Duplicar</a>
              <a class="btn block leading-5 duration-150 text-xs rounded-sm border px-1.5 font-semibold border-cinza text-cinza hover:bg-cinza hover:text-white" href="newsletter_pdf.php?id=<?= (int) $r['id'] ?>" target="_blank">Exportar PDF</a>
              <?php if (in_array($r['status'], ['draft', 'failed'], true)): ?>
                <a class="btn block leading-5 duration-150 text-xs rounded-sm border px-1.5 font-semibold border-blue-500 text-blue-500 hover:bg-blue-500 hover:text-white" href="newsletter_edit.php?id=<?= (int) $r['id'] ?>">Editar</a>
              <?php endif; ?>
              <a class="btn block leading-5 duration-150 text-xs rounded-sm border px-1.5 font-semibold border-red-500 text-red-500 hover:bg-red-500 hover:text-white" href="newsletter_action.php?action=delete&id=<?= (int) $r['id'] ?>" onclick="return confirm('Excluir newsletter?')">Excluir</a>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>
</main>
<?php require_once __DIR__ . '/_footer.php'; ?>
