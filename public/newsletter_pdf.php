<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/email_template.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

require_login();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { http_response_code(400); exit('ID inválido'); }

$html = render_email_preview_html($id);

// Extrai apenas a tabela interna de 600px, descartando o wrapper externo 100%
// que causa problemas de centralização no dompdf
preg_match('/<table width="600"[\s\S]*<\/table>/U', $html, $m);
$innerTable = isset($m[0]) ? $m[0] : $html;

// Busca a última ocorrência de </table> para pegar a tabela completa
$lastClose = strrpos($html, '</table>');
$firstOpen = strpos($html, '<table width="600"');
if ($firstOpen !== false && $lastClose !== false) {
    $innerTable = substr($html, $firstOpen, $lastClose - $firstOpen + strlen('</table>'));
}

// Remove a largura fixa para a tabela se adaptar ao tamanho da página
$innerTable = preg_replace('/<table width="600"/i', '<table width="100%"', $innerTable, 1);

$pdfHtml = '<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <style>
    @page { margin: 0; }
    body {
      margin: 0;
      padding: 30px;
      background: #ffffff;
      font-family: Arial, Helvetica, sans-serif;
    }
  </style>
</head>
<body>' . $innerTable . '</body>
</html>';

$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Arial');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($pdfHtml, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$dompdf->stream('newsletter-' . $id . '.pdf', ['Attachment' => true]);
