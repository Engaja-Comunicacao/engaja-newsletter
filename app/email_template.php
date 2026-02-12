<?php
// app/email_template.php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/config.php';

function public_path(string $relative): string {
  return realpath(__DIR__ . '/../public' . $relative) ?: (__DIR__ . '/../public' . $relative);
}

function get_newsletter_data(int $newsletterId): array {
  $pdo = db();

  $stmt = $pdo->prepare("
    SELECT n.*, c.name AS company_name, c.header_image_path,
           c.social_1_url, c.social_2_url, c.social_3_url, c.social_4_url
    FROM newsletters n
    JOIN companies c ON c.id = n.company_id
    WHERE n.id = ?
    LIMIT 1
  ");
  $stmt->execute([$newsletterId]);
  $n = $stmt->fetch();
  if (!$n) throw new RuntimeException("Newsletter não encontrada.");

  $stmt = $pdo->prepare("
    SELECT * FROM newsletter_items
    WHERE newsletter_id = ?
    ORDER BY sort_order ASC, id ASC
  ");
  $stmt->execute([$newsletterId]);
  $items = $stmt->fetchAll();

  $stmt = $pdo->prepare("SELECT email FROM company_recipients WHERE company_id = ? ORDER BY id ASC");
  $stmt->execute([$n['company_id']]);
  $recipients = array_map(fn($r) => $r['email'], $stmt->fetchAll());

  return [$n, $items, $recipients];
}

function render_news_block_web(array $item): string {
  $line = trim((format_ptbr_upper($item['news_date'] ?? null) ?: '') . ' - ' . strtoupper($item['portal'] ?? ''));

  $newsUrl = $item['link_url'] ? e($item['link_url']) : '#';
  $pdfUrl  = $item['pdf_path'] ? e(APP_URL . $item['pdf_path']) : null;

  $title = e($item['title'] ?? '');
  $desc  = e($item['description'] ?? '');

  $btnPdf = '';
  if ($pdfUrl) {
    $btnPdf = '
      <td width="10"></td>
      <td bgcolor="#e0e0e0" style="padding:10px 14px; font-size:12px;">
        <a href="' . $pdfUrl . '" style="text-decoration:none; color:#000000; font-weight:bold;">
          LINK DO PDF
        </a>
      </td>';
  }

  return '
    <tr>
      <td style="padding:24px 20px 10px 20px;">
        <span style="font-size:12px; color:#777777;">' . e($line) . '</span>
      </td>
    </tr>
    <tr>
      <td style="padding:0 20px;">
        <h2 style="margin:0; font-size:20px; color:#000000;">' . $title . '</h2>
      </td>
    </tr>
    <tr>
      <td style="padding:12px 20px 20px 20px;">
        <p style="margin:0; font-size:14px; line-height:20px; color:#333333;">' . $desc . '</p>
      </td>
    </tr>
    <tr>
      <td style="padding:0 20px 30px 20px;">
        <table cellpadding="0" cellspacing="0">
          <tr>
            <td bgcolor="#e0e0e0" style="padding:10px 14px; font-size:12px;">
              <a href="' . $newsUrl . '" style="text-decoration:none; color:#000000; font-weight:bold;">
                LINK DA NOTÍCIA
              </a>
            </td>
            ' . $btnPdf . '
          </tr>
        </table>
      </td>
    </tr>
    <tr><td style="border-top:1px solid #dddddd;"></td></tr>
  ';
}

function render_news_block_send(array $item): string {
  // conteúdo igual ao web, mas links ficam iguais (URL) pq são clique
  return render_news_block_web($item);
}

function social_icons_html_web(array $n): string {
  $icons = [
    ['url' => $n['social_1_url'] ?? '', 'img' => APP_URL . '/assets/rede1.png'],
    ['url' => $n['social_2_url'] ?? '', 'img' => APP_URL . '/assets/rede2.png'],
    ['url' => $n['social_3_url'] ?? '', 'img' => APP_URL . '/assets/rede3.png'],
    ['url' => $n['social_4_url'] ?? '', 'img' => APP_URL . '/assets/rede4.png'],
  ];

  $html = '';
  foreach ($icons as $it) {
    $u = trim((string)$it['url']);
    if ($u === '' || $u === '#') continue; // se não tem, some
    $html .= '<a href="' . e($u) . '"><img src="' . e($it['img']) . '" width="28" style="margin:0 6px; display:inline-block;"></a>';
  }
  return $html;
}

function social_icons_html_cid(array $n): array {
  // retorna [html, embeds]
  $map = [
    ['url' => $n['social_1_url'] ?? '', 'cid' => 'rede1', 'path' => public_path('/assets/rede1.png')],
    ['url' => $n['social_2_url'] ?? '', 'cid' => 'rede2', 'path' => public_path('/assets/rede2.png')],
    ['url' => $n['social_3_url'] ?? '', 'cid' => 'rede3', 'path' => public_path('/assets/rede3.png')],
    ['url' => $n['social_4_url'] ?? '', 'cid' => 'rede4', 'path' => public_path('/assets/rede4.png')],
  ];

  $html = '';
  $embeds = [];

  foreach ($map as $it) {
    $u = trim((string)$it['url']);
    if ($u === '' || $u === '#') continue;

    if (is_file($it['path'])) {
      $embeds[] = ['cid'=>$it['cid'], 'path'=>$it['path'], 'name'=>basename($it['path']), 'mime'=>'image/png'];
      $html .= '<a href="' . e($u) . '"><img src="cid:' . e($it['cid']) . '" width="28" style="margin:0 6px; display:inline-block;"></a>';
    }
  }

  return [$html, $embeds];
}

function render_email_web(int $newsletterId): string {
  [$n, $items] = get_newsletter_data($newsletterId);

  $headerImg = $n['header_image_path'] ? (APP_URL . $n['header_image_path']) : (APP_URL . '/assets/engaja.png');
  $logoEngaja = APP_URL . '/assets/engaja.png';

  $newsBlocks = '';
  foreach ($items as $it) $newsBlocks .= render_news_block_web($it);

  $socialHtml = social_icons_html_web($n);

  // FOOTER fixo Engaja
  $fixedSite = 'https://www.engajacomunicacao.com.br';
  $fixedSiteText = 'www.engajacomunicacao.com.br';

  return '<!DOCTYPE html>
<html lang="pt-br"><head><meta charset="UTF-8"><title>Radar de Notícias</title></head>
<body style="margin:0; padding:0; background-color:#f2f2f2;">
<table width="100%" cellpadding="0" cellspacing="0" bgcolor="#f2f2f2"><tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" bgcolor="#ffffff" style="font-family: Arial, Helvetica, sans-serif;">
<tr><td><img src="' . e($headerImg) . '" width="600" alt="Topo" style="display:block; width:100%; max-width:600px;"></td></tr>

<tr><td bgcolor="#CCC" align="center" style="padding:16px;">
<span style="color:#000; font-size:20px; font-weight:bold; letter-spacing:1px;">RADAR DE NOTÍCIAS</span>
</td></tr>

' . $newsBlocks . '

<tr><td bgcolor="#eeeeee" style="padding:0;">
  <table width="100%" cellpadding="0" cellspacing="0"><tr>
    <td align="center" style="padding:20px;">' . $socialHtml . '</td>
  </tr></table>

  <table width="100%" cellpadding="0" cellspacing="0"><tr><td style="border-top:1px solid #cccccc;"></td></tr></table>

  <table width="100%" cellpadding="0" cellspacing="0" style="padding:20px;"><tr>
    <td align="left" valign="middle">
      <img src="' . e($logoEngaja) . '" alt="Engaja" width="120" style="display:block;">
    </td>
    <td align="right" valign="middle" style="font-family:Arial, Helvetica, sans-serif;">
      <a href="' . e($fixedSite) . '" style="font-size:14px; color:#000000; text-decoration:none; font-weight:bold;">
        ' . e($fixedSiteText) . '
      </a>
    </td>
  </tr></table>
</td></tr>

</table></td></tr></table>
</body></html>';
}

function render_email_send(int $newsletterId): array {
  [$n, $items] = get_newsletter_data($newsletterId);

  // Header: tenta usar imagem da empresa, senão engaja.png
  $headerAbs = $n['header_image_path'] ? public_path($n['header_image_path']) : public_path('/assets/engaja.png');
  if (!is_file($headerAbs)) $headerAbs = public_path('/assets/engaja.png');

  $embeds = [];
  $embeds[] = ['cid'=>'header', 'path'=>$headerAbs, 'name'=>basename($headerAbs), 'mime'=>'image/png'];

  // Engaja fixo
  $logoAbs = public_path('/assets/engaja.png');
  if (is_file($logoAbs)) {
    $embeds[] = ['cid'=>'engaja_logo', 'path'=>$logoAbs, 'name'=>'engaja.png', 'mime'=>'image/png'];
  }

  $newsBlocks = '';
  foreach ($items as $it) $newsBlocks .= render_news_block_send($it);

  [$socialHtml, $socialEmbeds] = social_icons_html_cid($n);
  $embeds = array_merge($embeds, $socialEmbeds);

  $fixedSite = 'https://www.engajacomunicacao.com.br';
  $fixedSiteText = 'www.engajacomunicacao.com.br';

  $html = '<!DOCTYPE html>
<html lang="pt-br"><head><meta charset="UTF-8"><title>Radar de Notícias</title></head>
<body style="margin:0; padding:0; background-color:#f2f2f2;">
<table width="100%" cellpadding="0" cellspacing="0" bgcolor="#f2f2f2"><tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" bgcolor="#ffffff" style="font-family: Arial, Helvetica, sans-serif;">
<tr><td><img src="cid:header" width="600" alt="Topo" style="display:block; width:100%; max-width:600px;"></td></tr>

<tr><td bgcolor="#CCC" align="center" style="padding:16px;">
<span style="color:#000; font-size:20px; font-weight:bold; letter-spacing:1px;">RADAR DE NOTÍCIAS</span>
</td></tr>

' . $newsBlocks . '

<tr><td bgcolor="#eeeeee" style="padding:0;">
  <table width="100%" cellpadding="0" cellspacing="0"><tr>
    <td align="center" style="padding:20px;">' . $socialHtml . '</td>
  </tr></table>

  <table width="100%" cellpadding="0" cellspacing="0"><tr><td style="border-top:1px solid #cccccc;"></td></tr></table>

  <table width="100%" cellpadding="0" cellspacing="0" style="padding:20px;"><tr>
    <td align="left" valign="middle">
      <img src="cid:engaja_logo" alt="Engaja" width="120" style="display:block;">
    </td>
    <td align="right" valign="middle" style="font-family:Arial, Helvetica, sans-serif;">
      <a href="' . e($fixedSite) . '" style="font-size:14px; color:#000000; text-decoration:none; font-weight:bold;">
        ' . e($fixedSiteText) . '
      </a>
    </td>
  </tr></table>
</td></tr>

</table></td></tr></table>
</body></html>';

  return ['html' => $html, 'embeds' => $embeds];
}
