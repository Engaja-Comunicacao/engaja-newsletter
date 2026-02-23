<?php
// app/email_template.php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/config.php';

function get_newsletter_data(int $newsletterId): array {
  $pdo = db();

  $st = $pdo->prepare("
    SELECT n.*, c.name AS company_name, c.header_image_path,
           c.social_1_url, c.social_2_url, c.social_3_url, c.social_4_url
    FROM newsletters n
    JOIN companies c ON c.id = n.company_id
    WHERE n.id = ?
    LIMIT 1
  ");
  $st->execute([$newsletterId]);
  $n = $st->fetch();
  if (!$n) throw new RuntimeException('Newsletter não encontrada.');

  $st = $pdo->prepare("
    SELECT * FROM newsletter_items
    WHERE newsletter_id = ?
    ORDER BY sort_order ASC, id ASC
  ");
  $st->execute([$newsletterId]);
  $items = $st->fetchAll();

  $st = $pdo->prepare("SELECT email FROM company_recipients WHERE company_id=? ORDER BY id ASC");
  $st->execute([(int)$n['company_id']]);
  $recipients = array_map(fn($r) => $r['email'], $st->fetchAll());

  return [$n, $items, $recipients];
}

function render_news_block_preview(array $item): string {
  $line = trim(
    (format_ptbr_upper($item['news_date'] ?? null) ?: '') . ' - ' . upper_ptbr($item['portal'] ?? '')
  );

  $newsUrl = $item['link_url'] ? e($item['link_url']) : '#';
  $pdfUrl  = $item['pdf_path'] ? e(url_join(APP_URL, $item['pdf_path'])) : null;

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
    <tr>
      <td style="border-top:1px solid #dddddd;"></td>
    </tr>
  ';
}

function render_email_preview_html(int $newsletterId): string {
  [$n, $items] = get_newsletter_data($newsletterId);

  $headerImg = $n['header_image_path']
    ? url_join(APP_URL, $n['header_image_path'])
    : url_join(APP_URL, '/assets/engaja.png');

  $logoEngajaUrl = url_join(APP_URL, '/assets/engaja.png');
  $rede1Url = url_join(APP_URL, '/assets/rede1.png');
  $rede2Url = url_join(APP_URL, '/assets/rede2.png');
  $rede3Url = url_join(APP_URL, '/assets/rede3.png');
  $rede4Url = url_join(APP_URL, '/assets/rede4.png');

  $socials = [
    ['url' => trim((string)($n['social_1_url'] ?? '')), 'icon' => $rede1Url],
    ['url' => trim((string)($n['social_2_url'] ?? '')), 'icon' => $rede2Url],
    ['url' => trim((string)($n['social_3_url'] ?? '')), 'icon' => $rede3Url],
    ['url' => trim((string)($n['social_4_url'] ?? '')), 'icon' => $rede4Url],
  ];

  $socialHtml = '';
  foreach ($socials as $s) {
    if ($s['url'] === '') continue;
    $socialHtml .= '<a href="' . e($s['url']) . '"><img src="' . e($s['icon']) . '" width="28" style="margin:0 6px; display:inline-block;"></a>';
  }

  $newsBlocks = '';
  foreach ($items as $it) $newsBlocks .= render_news_block_preview($it);

  return '<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Radar de Notícias</title>
</head>
<body style="margin:0; padding:0; background-color:#f2f2f2;">
  <table width="100%" cellpadding="0" cellspacing="0" bgcolor="#f2f2f2">
    <tr>
      <td align="center">
        <table width="600" cellpadding="0" cellspacing="0" bgcolor="#ffffff" style="font-family: Arial, Helvetica, sans-serif;">
          <tr>
            <td>
              <img src="' . e($headerImg) . '" width="600" alt="Topo" style="display:block; width:100%; max-width:600px;">
            </td>
          </tr>

          <tr>
            <td bgcolor="#CCC" align="center" style="padding:16px;">
              <span style="color:#000; font-size:20px; font-weight:bold; letter-spacing:1px;">
                RADAR DE NOTÍCIAS
              </span>
            </td>
          </tr>

          ' . $newsBlocks . '

          <tr>
            <td bgcolor="#eeeeee" style="padding:0;">

              ' . ($socialHtml ? '
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td align="center" style="padding:20px;">
                    ' . $socialHtml . '
                  </td>
                </tr>
              </table>' : '') . '

              <table width="100%" cellpadding="0" cellspacing="0">
                <tr><td style="border-top:1px solid #cccccc;"></td></tr>
              </table>

              <table width="100%" cellpadding="0" cellspacing="0" style="padding:20px;">
                <tr>
                  <td align="left" valign="middle">
                    <img src="' . e($logoEngajaUrl) . '" alt="Engaja" width="120" style="display:block;">
                  </td>
                  <td align="right" valign="middle" style="font-family:Arial, Helvetica, sans-serif;">
                    <a href="' . e(ENGAJA_SITE_URL) . '" style="font-size:14px; color:#000000; text-decoration:none; font-weight:bold;">
                      www.engajacomunicacao.com.br
                    </a>
                  </td>
                </tr>
              </table>

            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>';
}

/**
 * SEND: retorna payload com HTML + embeds (CID)
 */
function render_email_send(int $newsletterId): array {
  [$n, $items] = get_newsletter_data($newsletterId);

  $embeds = [];

  // Topo
  $headerPublic = $n['header_image_path'] ?: '/assets/engaja.png';
  $embeds['header_img'] = public_fs_path($headerPublic);

  // Logo Engaja
  $embeds['engaja_logo'] = public_fs_path('/assets/engaja.png');

  // Ícones redes
  $rede1Fs = public_fs_path('/assets/rede1.png');
  $rede2Fs = public_fs_path('/assets/rede2.png');
  $rede3Fs = public_fs_path('/assets/rede3.png');
  $rede4Fs = public_fs_path('/assets/rede4.png');

  $socials = [
    ['url' => trim((string)($n['social_1_url'] ?? '')), 'cid' => 'rede1', 'fs' => $rede1Fs],
    ['url' => trim((string)($n['social_2_url'] ?? '')), 'cid' => 'rede2', 'fs' => $rede2Fs],
    ['url' => trim((string)($n['social_3_url'] ?? '')), 'cid' => 'rede3', 'fs' => $rede3Fs],
    ['url' => trim((string)($n['social_4_url'] ?? '')), 'cid' => 'rede4', 'fs' => $rede4Fs],
  ];

  $socialHtml = '';
  foreach ($socials as $s) {
    if ($s['url'] === '') continue;
    if (!$s['fs']) continue;
    $embeds[$s['cid']] = $s['fs'];
    $socialHtml .= '<a href="' . e($s['url']) . '"><img src="cid:' . e($s['cid']) . '" width="28" style="margin:0 6px; display:inline-block;"></a>';
  }

  // Notícias
  $newsBlocks = '';
  foreach ($items as $it) {
    $line = trim(
      (format_ptbr_upper($it['news_date'] ?? null) ?: '') . ' - ' . upper_ptbr($it['portal'] ?? '')
    );

    $newsUrl = $it['link_url'] ? e($it['link_url']) : '#';
    $pdfUrl  = $it['pdf_path'] ? e(url_join(APP_URL, $it['pdf_path'])) : null;

    $title = e($it['title'] ?? '');
    $desc  = e($it['description'] ?? '');

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

    $newsBlocks .= '
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
      <tr>
        <td style="border-top:1px solid #dddddd;"></td>
      </tr>
    ';
  }

  $html = '<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Radar de Notícias</title>
</head>
<body style="margin:0; padding:0; background-color:#f2f2f2;">
  <table width="100%" cellpadding="0" cellspacing="0" bgcolor="#f2f2f2">
    <tr>
      <td align="center">
        <table width="600" cellpadding="0" cellspacing="0" bgcolor="#ffffff" style="font-family: Arial, Helvetica, sans-serif;">
          <tr>
            <td>
              <img src="cid:header_img" width="600" alt="Topo" style="display:block; width:100%; max-width:600px;">
            </td>
          </tr>

          <tr>
            <td bgcolor="#CCC" align="center" style="padding:16px;">
              <span style="color:#000; font-size:20px; font-weight:bold; letter-spacing:1px;">
                RADAR DE NOTÍCIAS
              </span>
            </td>
          </tr>

          ' . $newsBlocks . '

          <tr>
            <td bgcolor="#eeeeee" style="padding:0;">

              ' . ($socialHtml ? '
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td align="center" style="padding:20px;">
                    ' . $socialHtml . '
                  </td>
                </tr>
              </table>' : '') . '

              <table width="100%" cellpadding="0" cellspacing="0">
                <tr><td style="border-top:1px solid #cccccc;"></td></tr>
              </table>

              <table width="100%" cellpadding="0" cellspacing="0" style="padding:20px;">
                <tr>
                  <td align="left" valign="middle">
                    <img src="cid:engaja_logo" alt="Engaja" width="120" style="display:block;">
                  </td>
                  <td align="right" valign="middle" style="font-family:Arial, Helvetica, sans-serif;">
                    <a href="' . e(ENGAJA_SITE_URL) . '" style="font-size:14px; color:#000000; text-decoration:none; font-weight:bold;">
                      www.engajacomunicacao.com.br
                    </a>
                  </td>
                </tr>
              </table>

            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>';

  return [
    'html' => $html,
    'embeds' => $embeds
  ];
}
