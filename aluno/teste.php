<?php
// Arquivo temporário para testar a estrutura de fotos
// Salve como: bombeiros_mirim/aluno/teste_fotos.php

echo "<h1>Teste de Estrutura de Fotos</h1>";

// Verificar estrutura de pastas
echo "<h2>Verificação de Pastas:</h2>";
echo "Pasta atual: " . __DIR__ . "<br>";
echo "Pasta uploads existe: " . (is_dir("../uploads") ? "✅ SIM" : "❌ NÃO") . "<br>";
echo "Pasta uploads/fotos existe: " . (is_dir("../uploads/fotos") ? "✅ SIM" : "❌ NÃO") . "<br>";

// Listar arquivos na pasta de fotos
echo "<h2>Arquivos em uploads/fotos/:</h2>";
if (is_dir("../uploads/fotos")) {
    $files = scandir("../uploads/fotos");
    foreach ($files as $file) {
        if ($file != "." && $file != "..") {
            $fullPath = "../uploads/fotos/" . $file;
            echo "📄 " . $file . " (tamanho: " . filesize($fullPath) . " bytes)<br>";
        }
    }
} else {
    echo "❌ Pasta não encontrada";
}

// Verificar se default.png existe
echo "<h2>Verificação de Arquivos Específicos:</h2>";
echo "default.png existe: " . (file_exists("../uploads/fotos/default.png") ? "✅ SIM" : "❌ NÃO") . "<br>";

// Testar diferentes caminhos de imagem
echo "<h2>Teste de Caminhos de Imagem:</h2>";
$testPaths = [
    "../uploads/fotos/default.png",
    "./uploads/fotos/default.png", 
    "uploads/fotos/default.png",
    "../uploads/fotos/"
];

foreach ($testPaths as $path) {
    if (file_exists($path)) {
        echo "✅ $path - EXISTE<br>";
    } else {
        echo "❌ $path - NÃO EXISTE<br>";
    }
}

// Teste visual
echo "<h2>Teste Visual:</h2>";
if (file_exists("../uploads/fotos/default.png")) {
    echo '<img src="../uploads/fotos/default.png" alt="Teste" style="width: 100px; height: 100px; border: 2px solid red;">';
    echo "<br>Se você vê uma imagem acima, o caminho está correto!";
} else {
    echo "❌ Não foi possível carregar a imagem de teste";
}

// Conectar ao banco e verificar algumas fotos de alunos
echo "<h2>Fotos de Alunos no Banco:</h2>";
try {
    if (file_exists("../env_config.php")) {
        require "../env_config.php";
        
        $db = new PDO("mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8", 
                      $_ENV['DB_USER'], $_ENV['DB_PASS']);
        
        $query = "SELECT id, nome, foto FROM alunos WHERE foto IS NOT NULL AND foto != '' LIMIT 5";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($alunos as $aluno) {
            echo "<strong>Aluno:</strong> " . htmlspecialchars($aluno['nome']) . "<br>";
            echo "<strong>Foto no banco:</strong> " . htmlspecialchars($aluno['foto']) . "<br>";
            
            // Testar se o arquivo existe
            $testPaths = [
                "../uploads/fotos/" . basename($aluno['foto']),
                "../uploads/fotos/" . $aluno['foto'],
                "../" . $aluno['foto']
            ];
            
            foreach ($testPaths as $testPath) {
                if (file_exists($testPath)) {
                    echo "✅ Arquivo encontrado em: $testPath<br>";
                    break;
                }
            }
            echo "<hr>";
        }
    }
} catch (Exception $e) {
    echo "Erro ao conectar com banco: " . $e->getMessage();
}
?>