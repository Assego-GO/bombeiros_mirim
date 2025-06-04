<?php
// Arquivo temporário para download do PDF de teste
$file = "/tmp/teste_pdf_1744219590.pdf";
if (file_exists($file)) {
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="teste_pdf.pdf"');
    header('Content-Length: ' . filesize($file));
    readfile($file);
    exit;
} else {
    echo "Arquivo não encontrado";
}