<?php
// app/email_template.php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/config.php';

function get_newsletter_data(int $newsletterId): array {
  $pdo = db();

  $stmt = $pdo->prepare("
    SELECT n.*, c.name AS company_name, c.header_image_path, c.site_url,
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

function render_news_block(array $item): string {
  $line = trim((format_ptbr_upper($item['news_date'] ?? null) ?: '') . ' - ' . strtoupper($item['portal'] ?? ''));

  $newsUrl = $item['link_url'] ? e($item['link_url']) : '#';
  $pdfUrl = $item['pdf_path'] ? e(APP_URL . $item['pdf_path']) : null;

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

function render_email_html(int $newsletterId): string {
  [$n, $items] = get_newsletter_data($newsletterId);

  $headerImg = $n['header_image_path'] ? (APP_URL . $n['header_image_path']) : (APP_URL . '/assets/email/engaja.png');

  $social1 = $n['social_1_url'] ?: '#';
  $social2 = $n['social_2_url'] ?: '#';
  $social3 = $n['social_3_url'] ?: '#';
  $social4 = $n['social_4_url'] ?: '#';
  $siteUrl = $n['site_url'] ?: 'https://www.engajacomunicacao.com.br';

  $newsBlocks = '';
  foreach ($items as $it) $newsBlocks .= render_news_block($it);

  $logoEngaja = APP_URL . '/assets/email/engaja.png';
  $rede1 = APP_URL . '/assets/email/rede1.png';
  $rede2 = APP_URL . '/assets/email/rede2.png';
  $rede3 = APP_URL . '/assets/email/rede3.png';
  $rede4 = APP_URL . '/assets/email/rede4.png';

  // Mantive o layout do seu index.html, só dinamizei
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
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td align="center" style="padding:20px;">
                    <a href="' . e($social1) . '"><img src="' . e($rede1) . '" width="28" style="margin:0 6px; display:inline-block;"></a>
                    <a href="' . e($social2) . '"><img src="' . e($rede2) . '" width="28" style="margin:0 6px; display:inline-block;"></a>
                    <a href="' . e($social3) . '"><img src="' . e($rede3) . '" width="28" style="margin:0 6px; display:inline-block;"></a>
                    <a href="' . e($social4) . '"><img src="' . e($rede4) . '" width="28" style="margin:0 6px; display:inline-block;"></a>
                  </td>
                </tr>
              </table>

              <table width="100%" cellpadding="0" cellspacing="0">
                <tr><td style="border-top:1px solid #cccccc;"></td></tr>
              </table>

              <table width="100%" cellpadding="0" cellspacing="0" style="padding:20px;">
                <tr>
                  <td align="left" valign="middle">
                    <img src="' . e($logoEngaja) . '" alt="Engaja" width="120" style="display:block;">
                  </td>
                  <td align="right" valign="middle" style="font-family:Arial, Helvetica, sans-serif;">
                    <a href="' . e($siteUrl) . '" style="font-size:14px; color:#000000; text-decoration:none; font-weight:bold;">
                      ' . e(preg_replace('#^https?://#', '', $siteUrl)) . '
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
