<?php
// teste_fpdf.php
// Coloque este arquivo no diretório raiz e acesse-o diretamente pelo navegador

// Habilitar exibição de erros
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Teste FPDF</h1>";

// Tentar encontrar o FPDF
$fpdf_paths = [
    'vendor/fpdf/fpdf.php',
    'vendor/setasign/fpdf/fpdf.php',
    'lib/fpdf/fpdf.php',
    'fpdf/fpdf.php',
    __DIR__ . '/vendor/fpdf/fpdf.php',
    __DIR__ . '/vendor/setasign/fpdf/fpdf.php',
    __DIR__ . '/lib/fpdf/fpdf.php',
    __DIR__ . '/fpdf/fpdf.php'
];

$fpdf_loaded = false;
$fpdf_path = '';

foreach ($fpdf_paths as $path) {
    echo "Verificando: $path... ";
    if (file_exists($path)) {
        echo "<span style='color:green'>ENCONTRADO!</span><br>";
        $fpdf_path = $path;
        $fpdf_loaded = true;
        break;
    } else {
        echo "<span style='color:red'>não encontrado</span><br>";
    }
}

if (!$fpdf_loaded) {
    echo "<h2 style='color:red'>FPDF não encontrado!</h2>";
    echo "<p>Você precisa instalar o FPDF. Execute este comando no terminal:</p>";
    echo "<pre>composer require setasign/fpdf</pre>";
    echo "<p>Ou baixe manualmente em: <a href='http://www.fpdf.org/'>http://www.fpdf.org/</a></p>";
    die();
}

// Tentar carregar o FPDF
try {
    echo "<p>Carregando FPDF de: $fpdf_path</p>";
    require($fpdf_path);
    echo "<p style='color:green'>FPDF carregado com sucesso!</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>Erro ao carregar FPDF: " . $e->getMessage() . "</p>";
    die();
}

// Testar a criação de um PDF simples
try {
    echo "<h2>Tentando criar um PDF de teste...</h2>";
    
    // Verificar permissões
    $temp_dir = sys_get_temp_dir();
    echo "<p>Diretório temporário: $temp_dir</p>";
    
    if (is_writable($temp_dir)) {
        echo "<p style='color:green'>Diretório temporário é gravável!</p>";
    } else {
        echo "<p style='color:red'>Diretório temporário NÃO é gravável!</p>";
    }
    
    // Criar PDF simples
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(40, 10, 'Teste FPDF funcionando!');
    
    // Salvar em arquivo
    $test_file = $temp_dir . '/teste_fpdf_' . time() . '.pdf';
    echo "<p>Tentando salvar PDF em: $test_file</p>";
    
    $pdf->Output('F', $test_file);
    
    if (file_exists($test_file)) {
        echo "<p style='color:green'>PDF criado com sucesso!</p>";
        echo "<p>Tamanho do arquivo: " . filesize($test_file) . " bytes</p>";
        
        // Criar link para download
        $download_script = <<<PHP
<?php
\$file = "$test_file";
if (file_exists(\$file)) {
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="teste_fpdf.pdf"');
    header('Content-Length: ' . filesize(\$file));
    readfile(\$file);
    exit;
} else {
    echo "Arquivo não encontrado";
}
PHP;
        
        file_put_contents('download_test_pdf.php', $download_script);
        echo "<p><a href='download_test_pdf.php' target='_blank'>➡️ Clique aqui para baixar o PDF de teste</a></p>";
        
        // Tentar exibir diretamente
        echo "<h2>Teste de saída direta para o navegador:</h2>";
        echo "<p>Clique no botão abaixo para testar a saída direta (Output 'I'):</p>";
        echo "<form method='post'>";
        echo "<input type='submit' name='test_direct' value='Testar saída direta' style='padding:10px;'>";
        echo "</form>";
        
        if (isset($_POST['test_direct'])) {
            // Criar PDF para saída direta
            $pdf = new FPDF();
            $pdf->AddPage();
            $pdf->SetFont('Arial', 'B', 16);
            $pdf->Cell(40, 10, 'Teste de saída direta!');
            
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="teste_direto.pdf"');
            $pdf->Output('I', 'teste_direto.pdf');
            exit;
        }
    } else {
        echo "<p style='color:red'>Falha ao criar o arquivo PDF!</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>Erro ao criar PDF: " . $e->getMessage() . "</p>";
}

// Informações do PHP
echo "<h2>Informações do PHP:</h2>";
echo "<ul>";
echo "<li>Versão do PHP: " . phpversion() . "</li>";
echo "<li>Memória limite: " . ini_get('memory_limit') . "</li>";
echo "<li>Tempo máximo de execução: " . ini_get('max_execution_time') . " segundos</li>";
echo "<li>Post max size: " . ini_get('post_max_size') . "</li>";
echo "<li>Upload max size: " . ini_get('upload_max_filesize') . "</li>";
echo "</ul>";

echo "<h2>Extensões do PHP:</h2>";
echo "<ul>";
$extensoes = ['gd', 'mbstring', 'mysqli', 'json', 'zlib'];
foreach ($extensoes as $ext) {
    if (extension_loaded($ext)) {
        echo "<li style='color:green'>$ext: ✓ Carregada</li>";
    } else {
        echo "<li style='color:red'>$ext: ✗ NÃO carregada</li>";
    }
}
echo "</ul>";

echo "<h2>Instruções para correção:</h2>";
echo "<ol>";
echo "<li>Instale o FPDF via Composer (recomendado):<br><code>composer require setasign/fpdf</code></li>";
echo "<li>Se o teste acima falhar, verifique os logs de erro do servidor (geralmente em /var/log/apache2/error.log ou similar)</li>";
echo "<li>Certifique-se de que a pasta 'vendor' ou onde o FPDF está instalado tem permissões corretas de leitura</li>";
echo "<li>Verifique que o PHP tem permissão para gravar no diretório temporário</li>";
echo "<li>Substitua o arquivo original 'api/gerar_carteirinha.php' pelo código simplificado abaixo</li>";
echo "</ol>";

echo "<h2>Código simplificado para gerar_carteirinha.php:</h2>";
echo "<pre style='background-color:#f4f4f4; padding:10px; overflow:auto;'>";
echo htmlspecialchars('<?php
// api/gerar_carteirinha.php - Versão mínima
// Habilitar exibição de erros para debug
ini_set("display_errors", 1);
error_reporting(E_ALL);

// Iniciar sessão
session_start();

// Descomente para testes se necessário
// $_SESSION["usuario_id"] = 1;

// Verificar se foi enviado alunos_ids
if (!isset($_POST["alunos_ids"]) || empty($_POST["alunos_ids"])) {
    die("Erro: Nenhum aluno selecionado");
}

// Carregar FPDF - múltiplas opções
$fpdf_loaded = false;
$fpdf_paths = [
    "../vendor/fpdf/fpdf.php",
    "../vendor/setasign/fpdf/fpdf.php",
    "../lib/fpdf/fpdf.php",
    "fpdf/fpdf.php"
];

foreach ($fpdf_paths as $path) {
    if (file_exists($path)) {
        require($path);
        $fpdf_loaded = true;
        break;
    }
}

if (!$fpdf_loaded) {
    die("Erro: FPDF não encontrado. Execute: composer require setasign/fpdf");
}

// Conexão com o banco
require_once("../config/database.php");

// Obter alunos
$alunos_ids = explode(",", $_POST["alunos_ids"]);
$alunos_ids = array_filter($alunos_ids, "is_numeric");

if (empty($alunos_ids)) {
    die("Erro: IDs de alunos inválidos");
}

// Consulta simples
$ids_string = implode(",", array_map("intval", $alunos_ids));
$query = "SELECT id, nome FROM alunos WHERE id IN (" . $ids_string . ")";
$result = $conn->query($query);

if (!$result || $result->num_rows === 0) {
    die("Erro: Nenhum aluno encontrado");
}

// Criar PDF mínimo
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont("Arial", "B", 16);
$pdf->Cell(0, 10, "Carteirinhas", 0, 1, "C");

while ($aluno = $result->fetch_assoc()) {
    $pdf->SetFont("Arial", "", 12);
    $pdf->Cell(0, 10, "Aluno: " . $aluno["nome"], 0, 1);
    $pdf->Cell(0, 10, "ID: " . $aluno["id"], 0, 1);
    $pdf->Ln(5);
}

// Saída
$filename = "carteirinhas_" . time() . ".pdf";
$pdf->Output("D", $filename);
?>');
echo "</pre>";

?>