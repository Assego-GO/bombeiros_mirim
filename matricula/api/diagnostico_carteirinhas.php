<?php
// diagnostico_carteirinhas.php
// Coloque este arquivo no mesmo diretório da sua API e execute-o diretamente no navegador

// Habilitar exibição de todos os erros
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Diagnóstico de Carteirinhas</h1>";

// 1. Verificar estrutura de diretórios
echo "<h2>Verificando estrutura de diretórios:</h2>";
$caminhos_api = [
    'API/gerar_carteirinha.php',
    'api/gerar_carteirinha.php',
    './API/gerar_carteirinha.php',
    './api/gerar_carteirinha.php'
];

foreach ($caminhos_api as $caminho) {
    if (file_exists($caminho)) {
        echo "<p style='color:green'>✓ Arquivo encontrado: {$caminho}</p>";
    } else {
        echo "<p style='color:red'>✗ Arquivo não encontrado: {$caminho}</p>";
    }
}

// 2. Verificar biblioteca FPDF
echo "<h2>Verificando biblioteca FPDF:</h2>";
$fpdf_paths = [
    '../vendor/fpdf/fpdf.php',
    '../vendor/setasign/fpdf/fpdf.php',
    '../lib/fpdf/fpdf.php',
    'fpdf/fpdf.php',
    __DIR__ . '/../vendor/fpdf/fpdf.php',
    __DIR__ . '/../lib/fpdf/fpdf.php'
];

$fpdf_encontrado = false;
foreach ($fpdf_paths as $path) {
    if (file_exists($path)) {
        echo "<p style='color:green'>✓ FPDF encontrado em: {$path}</p>";
        $fpdf_encontrado = true;
        break;
    }
}

if (!$fpdf_encontrado) {
    echo "<p style='color:red'>✗ FPDF não encontrado em nenhum dos caminhos verificados!</p>";
    echo "<p>Você precisa instalar o FPDF. Recomendação: <code>composer require setasign/fpdf</code></p>";
}

// 3. Verificar conexão com o banco de dados
echo "<h2>Verificando conexão com o banco de dados:</h2>";
try {
    // Tentativa de incluir o arquivo de configuração do banco de dados
    if (file_exists('../config/database.php')) {
        echo "<p style='color:green'>✓ Arquivo de configuração do banco de dados encontrado</p>";
        
        // Capturar saída para evitar que credenciais apareçam na tela
        ob_start();
        require_once('../config/database.php');
        ob_end_clean();
        
        if (isset($conn) && $conn instanceof mysqli) {
            if ($conn->connect_error) {
                echo "<p style='color:red'>✗ Erro de conexão com o banco de dados: {$conn->connect_error}</p>";
            } else {
                echo "<p style='color:green'>✓ Conexão com o banco de dados estabelecida com sucesso</p>";
                
                // Verificar tabela alunos
                $result = $conn->query("SHOW TABLES LIKE 'alunos'");
                if ($result && $result->num_rows > 0) {
                    echo "<p style='color:green'>✓ Tabela 'alunos' encontrada</p>";
                    
                    // Verificar estrutura da tabela
                    $result = $conn->query("DESCRIBE alunos");
                    if ($result) {
                        echo "<p style='color:green'>✓ Estrutura da tabela 'alunos' verificada</p>";
                        echo "<ul>";
                        while ($row = $result->fetch_assoc()) {
                            echo "<li>{$row['Field']} - {$row['Type']}</li>";
                        }
                        echo "</ul>";
                    } else {
                        echo "<p style='color:red'>✗ Não foi possível verificar a estrutura da tabela 'alunos'</p>";
                    }
                } else {
                    echo "<p style='color:red'>✗ Tabela 'alunos' não encontrada!</p>";
                }
            }
        } else {
            echo "<p style='color:red'>✗ Não foi possível acessar a conexão com o banco de dados</p>";
        }
    } else {
        echo "<p style='color:red'>✗ Arquivo de configuração do banco de dados não encontrado!</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Erro ao verificar banco de dados: {$e->getMessage()}</p>";
}

// 4. Testar geração de PDF simples
echo "<h2>Testando geração de PDF simples:</h2>";
if ($fpdf_encontrado) {
    try {
        // Carregar biblioteca FPDF
        foreach ($fpdf_paths as $path) {
            if (file_exists($path)) {
                require($path);
                break;
            }
        }
        
        // Verificar permissões de diretórios
        $temp_dir = sys_get_temp_dir();
        if (is_writable($temp_dir)) {
            echo "<p style='color:green'>✓ Diretório temporário gravável: {$temp_dir}</p>";
        } else {
            echo "<p style='color:red'>✗ Diretório temporário NÃO é gravável: {$temp_dir}</p>";
        }
        
        // Tentar criar um PDF simples
        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(40, 10, 'Teste de PDF');
        
        $test_file = $temp_dir . '/teste_pdf_' . time() . '.pdf';
        $pdf->Output('F', $test_file);
        
        if (file_exists($test_file)) {
            echo "<p style='color:green'>✓ PDF de teste criado com sucesso em: {$test_file}</p>";
            echo "<p><a href='download_test_pdf.php?file=" . basename($test_file) . "' target='_blank'>Baixar PDF de teste</a></p>";
            
            // Criar um arquivo auxiliar para download
            $download_file = <<<PHP
<?php
// Arquivo temporário para download do PDF de teste
\$file = "{$test_file}";
if (file_exists(\$file)) {
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="teste_pdf.pdf"');
    header('Content-Length: ' . filesize(\$file));
    readfile(\$file);
    exit;
} else {
    echo "Arquivo não encontrado";
}
PHP;
            file_put_contents('download_test_pdf.php', $download_file);
            
        } else {
            echo "<p style='color:red'>✗ Não foi possível criar o PDF de teste!</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color:red'>✗ Erro ao testar geração de PDF: {$e->getMessage()}</p>";
    }
} else {
    echo "<p style='color:orange'>⚠ Teste de geração de PDF ignorado - FPDF não encontrado</p>";
}

// 5. Verificar extensões do PHP necessárias
echo "<h2>Verificando extensões do PHP:</h2>";
$extensoes_necessarias = ['gd', 'mbstring', 'mysqli', 'json'];
foreach ($extensoes_necessarias as $ext) {
    if (extension_loaded($ext)) {
        echo "<p style='color:green'>✓ Extensão '{$ext}' está carregada</p>";
    } else {
        echo "<p style='color:red'>✗ Extensão '{$ext}' NÃO está carregada!</p>";
    }
}

echo "<h2>Informações do PHP:</h2>";
echo "<p>Versão do PHP: " . phpversion() . "</p>";
echo "<p>Memória limite: " . ini_get('memory_limit') . "</p>";
echo "<p>Tempo máximo de execução: " . ini_get('max_execution_time') . " segundos</p>";
echo "<p>Upload máximo: " . ini_get('upload_max_filesize') . "</p>";

// 6. Recomendações
echo "<h2>Recomendações com base nos resultados:</h2>";
echo "<ol>";
echo "<li>Verifique o log de erros do PHP em seu servidor para detalhes mais específicos sobre o erro 500.</li>";
echo "<li>Certifique-se de que o FPDF está instalado corretamente (via composer ou manualmente).</li>";
echo "<li>Confira se os caminhos da API estão corretos (maiúsculas/minúsculas) no frontend e backend.</li>";
echo "<li>Verifique permissões do diretório temporário no servidor.</li>";
echo "<li>Confirme que a conexão com o banco de dados está funcionando corretamente.</li>";
echo "</ol>";

// Fornecer uma versão simplificada do script de geração de carteirinhas
echo "<h2>Versão simplificada de gerar_carteirinha.php:</h2>";
echo "<p>Tente a versão simplificada abaixo para depurar o problema:</p>";
echo "<pre style='background-color: #f0f0f0; padding: 10px; overflow: auto;'>";
echo htmlspecialchars('<?php
// api/gerar_carteirinha_simplificado.php
// Versão simplificada para diagnóstico

// Habilitar exibição de erros
ini_set("display_errors", 1);
error_reporting(E_ALL);

// Iniciar sessão
session_start();

// Bypass de autenticação para teste
// $_SESSION["usuario_id"] = 1; // Descomente para testes se necessário

// 1. Verificar autenticação
if (!isset($_SESSION["usuario_id"])) {
    die("Erro: Usuário não autenticado");
}

// 2. Verificar alunos_ids
if (!isset($_POST["alunos_ids"]) || empty($_POST["alunos_ids"])) {
    die("Erro: Nenhum aluno selecionado");
}

// 3. Carregar FPDF
if (file_exists("../vendor/fpdf/fpdf.php")) {
    require("../vendor/fpdf/fpdf.php");
} elseif (file_exists("../vendor/setasign/fpdf/fpdf.php")) {
    require("../vendor/setasign/fpdf/fpdf.php");
} else {
    die("Erro: FPDF não encontrado. Instale via composer: composer require setasign/fpdf");
}

// 4. Conexão com o banco
require_once("../config/database.php");
if (!isset($conn) || $conn->connect_error) {
    die("Erro de conexão: " . ($conn->connect_error ?? "Variável de conexão não disponível"));
}

// 5. Obter IDs de alunos
$alunos_ids = explode(",", $_POST["alunos_ids"]);
$alunos_ids = array_filter($alunos_ids, "is_numeric");

if (empty($alunos_ids)) {
    die("Erro: IDs de alunos inválidos");
}

// 6. Consulta simplificada
$ids_string = implode(",", array_map("intval", $alunos_ids));
$query = "SELECT id, nome, numero_matricula, escola, serie FROM alunos WHERE id IN (" . $ids_string . ") LIMIT 10";
$result = $conn->query($query);

if (!$result || $result->num_rows === 0) {
    die("Erro: Nenhum aluno encontrado. Query: " . $query);
}

// 7. Criar PDF simples
try {
    $pdf = new FPDF("L", "mm", "A4");
    $pdf->SetMargins(10, 10, 10);
    $pdf->SetAutoPageBreak(true, 10);
    
    while ($aluno = $result->fetch_assoc()) {
        $pdf->AddPage();
        $pdf->SetFont("Arial", "B", 16);
        $pdf->Cell(0, 10, "Carteirinha do Aluno", 0, 1, "C");
        $pdf->Cell(0, 10, "ID: " . $aluno["id"], 0, 1);
        $pdf->Cell(0, 10, "Nome: " . $aluno["nome"], 0, 1);
        if (isset($aluno["numero_matricula"])) {
            $pdf->Cell(0, 10, "Matrícula: " . $aluno["numero_matricula"], 0, 1);
        }
        if (isset($aluno["escola"])) {
            $pdf->Cell(0, 10, "Escola: " . $aluno["escola"], 0, 1);
        }
        if (isset($aluno["serie"])) {
            $pdf->Cell(0, 10, "Série: " . $aluno["serie"], 0, 1);
        }
    }
    
    // 8. Saída do PDF
    $filename = "carteirinhas_teste_" . time() . ".pdf";
    $pdf->Output("D", $filename);
    
} catch (Exception $e) {
    die("Erro ao gerar PDF: " . $e->getMessage());
}
');
echo "</pre>";

echo "<p>Data do diagnóstico: " . date('Y-m-d H:i:s') . "</p>";
?>