<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Teste de Upload de Arquivos</h1>";

echo "<h2>Configurações PHP:</h2>";
echo "<pre>";
echo "file_uploads: " . ini_get('file_uploads') . "\n";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "post_max_size: " . ini_get('post_max_size') . "\n";
echo "max_file_uploads: " . ini_get('max_file_uploads') . "\n";
echo "</pre>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h2>Dados Recebidos:</h2>";
    echo "<pre>";
    echo "FILES: ";
    print_r($_FILES);
    echo "\nPOST: ";
    print_r($_POST);
    echo "\nContent-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'não definido');
    echo "</pre>";
    
    if(isset($_FILES['teste_arquivo']) && $_FILES['teste_arquivo']['error'] === UPLOAD_ERR_OK) {
        echo "<p style='color:green'>Arquivo recebido com sucesso!</p>";
        
        $upload_dir = 'uploads/teste/';
        if(!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $dest = $upload_dir . basename($_FILES['teste_arquivo']['name']);
        if(move_uploaded_file($_FILES['teste_arquivo']['tmp_name'], $dest)) {
            echo "<p style='color:green'>Arquivo movido para $dest com sucesso!</p>";
        } else {
            echo "<p style='color:red'>Erro ao mover o arquivo.</p>";
            echo "<pre>Erro PHP: " . print_r(error_get_last(), true) . "</pre>";
        }
    }
}
?>

<form method="post" enctype="multipart/form-data">
    <input type="hidden" name="teste_campo" value="valor_teste">
    <input type="file" name="teste_arquivo">
    <button type="submit">Enviar</button>
</form>