<?php
// api/teste_conexao.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔧 Teste de Conexão com Banco de Dados</h2>";
echo "<hr>";

// 1. Verificar diretórios e arquivos
echo "<h3>📁 Verificação de Arquivos:</h3>";
echo "Diretório atual: " . __DIR__ . "<br>";
echo "Diretório de trabalho: " . getcwd() . "<br>";

// Caminho baseado na estrutura real do projeto
$env_file = "/var/www/html/luis/bombeiros_mirim/env_config.php";
// Alternativa usando caminho relativo: $env_file = __DIR__ . "/../env_config.php";
echo "Caminho do env_config.php: " . $env_file . "<br>";
echo "Arquivo env_config.php existe? " . (file_exists($env_file) ? "✅ SIM" : "❌ NÃO") . "<br>";

if (!file_exists($env_file)) {
    echo "<div style='color: red; font-weight: bold;'>❌ ERRO: Arquivo env_config.php não encontrado!</div>";
    echo "<p>Verifique se o arquivo está na pasta correta:</p>";
    echo "<ul>";
    echo "<li>Conteúdo da pasta pai: " . implode(", ", scandir(__DIR__ . "/..")) . "</li>";
    echo "</ul>";
    exit;
}

echo "<hr>";

// 2. Carregar configurações
echo "<h3>⚙️ Carregando Configurações:</h3>";
try {
    require $env_file;
    echo "✅ Arquivo env_config.php carregado com sucesso<br>";
} catch (Exception $e) {
    echo "❌ ERRO ao carregar env_config.php: " . $e->getMessage() . "<br>";
    exit;
}

// 3. Verificar variáveis de ambiente
echo "<h3>🔑 Verificação das Variáveis de Ambiente:</h3>";
$required_vars = ['DB_HOST', 'DB_USER', 'DB_PASS', 'DB_NAME'];
$missing_vars = [];

foreach ($required_vars as $var) {
    if (isset($_ENV[$var]) && !empty($_ENV[$var])) {
        // Mascarar senha para exibição
        $value = ($var === 'DB_PASS') ? str_repeat('*', strlen($_ENV[$var])) : $_ENV[$var];
        echo "✅ $var: " . $value . "<br>";
    } else {
        echo "❌ $var: NÃO DEFINIDO<br>";
        $missing_vars[] = $var;
    }
}

if (!empty($missing_vars)) {
    echo "<div style='color: red; font-weight: bold;'>";
    echo "❌ ERRO: Variáveis obrigatórias não definidas: " . implode(", ", $missing_vars);
    echo "</div>";
    exit;
}

echo "<hr>";

// 4. Tentar conexão
echo "<h3>🔌 Testando Conexão com Banco:</h3>";

$host = $_ENV['DB_HOST'];
$usuario = $_ENV['DB_USER'];
$senha = $_ENV['DB_PASS'];
$db_name = $_ENV['DB_NAME'];

echo "Tentando conectar em: $host<br>";
echo "Banco de dados: $db_name<br>";
echo "Usuário: $usuario<br>";

try {
    $conn = new mysqli($host, $usuario, $senha, $db_name);
    
    if ($conn->connect_error) {
        throw new Exception("Erro de conexão: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8");
    echo "✅ <strong style='color: green;'>CONEXÃO ESTABELECIDA COM SUCESSO!</strong><br>";
    
    // 5. Informações da conexão
    echo "<hr>";
    echo "<h3>ℹ️ Informações da Conexão:</h3>";
    echo "Versão do MySQL: " . $conn->server_info . "<br>";
    echo "Charset atual: " . $conn->character_set_name() . "<br>";
    echo "Host info: " . $conn->host_info . "<br>";
    
    // 6. Teste simples de query
    echo "<hr>";
    echo "<h3>🧪 Teste de Query:</h3>";
    
    $result = $conn->query("SELECT 1 as teste, NOW() as agora");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "✅ Query de teste executada com sucesso<br>";
        echo "Resultado: " . $row['teste'] . "<br>";
        echo "Data/Hora do servidor: " . $row['agora'] . "<br>";
    } else {
        echo "❌ Erro na query de teste: " . $conn->error . "<br>";
    }
    
    // 7. Verificar se tabelas do sistema existem
    echo "<hr>";
    echo "<h3>📋 Verificação de Tabelas do Sistema:</h3>";
    
    $tabelas_sistema = ['usuarios', 'atividades', 'turma', 'unidade', 'alunos', 'atividade_participacao'];
    
    foreach ($tabelas_sistema as $tabela) {
        $result = $conn->query("SHOW TABLES LIKE '$tabela'");
        if ($result && $result->num_rows > 0) {
            echo "✅ Tabela '$tabela' existe<br>";
        } else {
            echo "⚠️ Tabela '$tabela' não encontrada<br>";
        }
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo "❌ <strong style='color: red;'>ERRO DE CONEXÃO:</strong><br>";
    echo $e->getMessage() . "<br>";
    
    // Diagnósticos adicionais
    echo "<hr>";
    echo "<h3>🔍 Diagnósticos:</h3>";
    
    // Verificar se é erro de host
    if (strpos($e->getMessage(), "Unknown host") !== false) {
        echo "• Verifique se o host '$host' está correto<br>";
    }
    
    // Verificar se é erro de credenciais
    if (strpos($e->getMessage(), "Access denied") !== false) {
        echo "• Verifique usuário e senha<br>";
        echo "• Certifique-se de que o usuário '$usuario' tem permissão para acessar '$db_name'<br>";
    }
    
    // Verificar se é erro de banco
    if (strpos($e->getMessage(), "Unknown database") !== false) {
        echo "• O banco de dados '$db_name' não existe<br>";
        echo "• Verifique se o nome está correto<br>";
    }
}

echo "<hr>";
echo "<p><em>Teste concluído em " . date('Y-m-d H:i:s') . "</em></p>";
?>