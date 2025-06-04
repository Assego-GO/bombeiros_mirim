<?php
// verificar_diretorio.php - Verifica e cria o diretório de uploads se necessário

// Caminho para o diretório de uploads
$diretorio_uploads = "../uploads/fotos/";

// Verifica se o diretório existe
if (!file_exists($diretorio_uploads)) {
    // Tenta criar o diretório com permissões 0777 (leitura, escrita e execução para todos)
    if (mkdir($diretorio_uploads, 0777, true)) {
        echo "Diretório de uploads criado com sucesso: " . $diretorio_uploads;
        
        // Cria um arquivo .htaccess para aumentar a segurança (opcional)
        $htaccess = $diretorio_uploads . ".htaccess";
        $conteudo = "# Permitir acesso a arquivos de imagem\n";
        $conteudo .= "Order Allow,Deny\n";
        $conteudo .= "Allow from all\n";
        $conteudo .= "Options -Indexes\n";
        $conteudo .= "<FilesMatch \"\\.(jpg|jpeg|png|gif)$\">\n";
        $conteudo .= "  Allow from all\n";
        $conteudo .= "</FilesMatch>\n";
        
        file_put_contents($htaccess, $conteudo);
        
        // Cria uma imagem padrão "sem_foto.png" se não existir
        $arquivo_sem_foto = $diretorio_uploads . "sem_foto.png";
        if (!file_exists($arquivo_sem_foto)) {
            // Você pode adicionar código aqui para criar uma imagem padrão
            // Ou copiar de algum lugar se necessário
            
            // Exemplo: cria um texto simples "Sem Foto" como imagem
            $imagem = imagecreate(150, 150);
            $cor_fundo = imagecolorallocate($imagem, 240, 240, 240);
            $cor_texto = imagecolorallocate($imagem, 50, 50, 50);
            imagestring($imagem, 5, 40, 70, "Sem Foto", $cor_texto);
            imagepng($imagem, $arquivo_sem_foto);
            imagedestroy($imagem);
            
            echo "<br>Arquivo 'sem_foto.png' criado com sucesso.";
        }
    } else {
        echo "Erro: Não foi possível criar o diretório de uploads. Verifique as permissões do servidor.";
    }
} else {
    echo "O diretório de uploads já existe: " . $diretorio_uploads;
    
    // Verifica se existe o arquivo sem_foto.png
    $arquivo_sem_foto = $diretorio_uploads . "sem_foto.png";
    if (!file_exists($arquivo_sem_foto)) {
        // Cria uma imagem padrão
        $imagem = imagecreate(150, 150);
        $cor_fundo = imagecolorallocate($imagem, 240, 240, 240);
        $cor_texto = imagecolorallocate($imagem, 50, 50, 50);
        imagestring($imagem, 5, 40, 70, "Sem Foto", $cor_texto);
        imagepng($imagem, $arquivo_sem_foto);
        imagedestroy($imagem);
        
        echo "<br>Arquivo 'sem_foto.png' criado com sucesso.";
    }
}

// Exibe informações sobre as permissões do diretório
if (file_exists($diretorio_uploads)) {
    echo "<br><br>Informações do diretório:<br>";
    echo "Permissões: " . substr(sprintf("%o", fileperms($diretorio_uploads)), -4);
    echo "<br>É gravável: " . (is_writable($diretorio_uploads) ? "Sim" : "Não");
}
?>

<p><a href="lista_alunos.php">Voltar para a lista de alunos</a></p>