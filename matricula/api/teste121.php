<?php
// Arquivo: api/teste_conexao.php
session_start();
header('Content-Type: application/json; charset=utf-8');

echo "=== TESTE DE CONEXÃO E DADOS ===\n";

// Teste 1: Verificar sessão
echo "1. Testando sessão...\n";
if (!isset($_SESSION['usuario_id'])) {
    echo "❌ ERRO: Usuário não autenticado\n";
    echo "Sessão atual: " . print_r($_SESSION, true) . "\n";
} else {
    echo "✅ Usuário autenticado - ID: " . $_SESSION['usuario_id'] . "\n";
}

// Teste 2: Verificar arquivo de conexão
echo "\n2. Testando arquivo de conexão...\n";
$conexao_paths = [
    'conexao.php',
    '../../conexao.php',
    dirname(__DIR__) . '/conexao.php'
];

$conexao_encontrada = false;
foreach ($conexao_paths as $path) {
    if (file_exists($path)) {
        echo "✅ Arquivo encontrado: $path\n";
        require_once $path;
        $conexao_encontrada = true;
        break;
    } else {
        echo "❌ Não encontrado: $path\n";
    }
}

if (!$conexao_encontrada) {
    echo "❌ ERRO: Arquivo de conexão não encontrado!\n";
    exit;
}

// Teste 3: Verificar conexão com banco
echo "\n3. Testando conexão com banco...\n";
if (!isset($conn)) {
    echo "❌ ERRO: Variável \$conn não existe\n";
    exit;
}

if ($conn->connect_error) {
    echo "❌ ERRO na conexão: " . $conn->connect_error . "\n";
    exit;
} else {
    echo "✅ Conexão com banco estabelecida\n";
}

// Teste 4: Verificar tabelas
echo "\n4. Testando tabelas...\n";
$tabelas = ['alunos', 'matriculas', 'unidade', 'turma', 'responsaveis', 'enderecos'];

foreach ($tabelas as $tabela) {
    $result = $conn->query("SHOW TABLES LIKE '$tabela'");
    if ($result && $result->num_rows > 0) {
        $count_result = $conn->query("SELECT COUNT(*) as total FROM $tabela");
        $count = $count_result->fetch_assoc()['total'];
        echo "✅ Tabela '$tabela' existe - $count registros\n";
    } else {
        echo "❌ Tabela '$tabela' não encontrada\n";
    }
}

// Teste 5: Query simples de alunos
echo "\n5. Testando query de alunos...\n";
try {
    $sql = "SELECT COUNT(*) as total FROM alunos WHERE status != 'excluido'";
    $result = $conn->query($sql);
    
    if ($result) {
        $total = $result->fetch_assoc()['total'];
        echo "✅ Query executada - $total alunos encontrados\n";
        
        // Buscar alguns alunos para teste
        $sql_sample = "SELECT id, nome, numero_matricula, status FROM alunos WHERE status != 'excluido' LIMIT 3";
        $result_sample = $conn->query($sql_sample);
        
        if ($result_sample && $result_sample->num_rows > 0) {
            echo "✅ Amostras de alunos:\n";
            while ($row = $result_sample->fetch_assoc()) {
                echo "  - ID: {$row['id']}, Nome: {$row['nome']}, Matrícula: {$row['numero_matricula']}, Status: {$row['status']}\n";
            }
        }
    } else {
        echo "❌ ERRO na query: " . $conn->error . "\n";
    }
} catch (Exception $e) {
    echo "❌ ERRO na execução: " . $e->getMessage() . "\n";
}

// Teste 6: Verificar estrutura da tabela alunos
echo "\n6. Verificando estrutura da tabela alunos...\n";
$result = $conn->query("DESCRIBE alunos");
if ($result) {
    echo "✅ Colunas da tabela alunos:\n";
    while ($row = $result->fetch_assoc()) {
        echo "  - {$row['Field']} ({$row['Type']})\n";
    }
}

echo "\n=== FIM DO TESTE ===\n";
$conn->close();
?>