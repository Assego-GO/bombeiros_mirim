<?php
// Arquivo teste-conexao.php
// Coloque este arquivo no mesmo diretório do seu script principal
header('Content-Type: text/plain');
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "=== DIAGNÓSTICO DO SISTEMA ===\n\n";
echo "Data/Hora: " . date('Y-m-d H:i:s') . "\n";
echo "PHP Version: " . phpversion() . "\n\n";

// 1. Testar conexão com o banco de dados
echo "## TESTE DE CONEXÃO COM O BANCO ##\n";
try {
    require_once 'conexao.php';
    echo "✅ Conexão estabelecida com sucesso\n\n";
} catch (Exception $e) {
    echo "❌ ERRO DE CONEXÃO: " . $e->getMessage() . "\n\n";
    die("O diagnóstico não pode continuar sem a conexão com o banco.\n");
}

// 2. Verificar existência das tabelas
echo "## VERIFICAÇÃO DE TABELAS ##\n";
$tabelas = [
    'alunos', 
    'responsaveis', 
    'aluno_responsavel', 
    'enderecos', 
    'matriculas'
];

foreach ($tabelas as $tabela) {
    try {
        $stmt = $conexao->query("SHOW TABLES LIKE '$tabela'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Tabela '$tabela': EXISTE\n";
        } else {
            echo "❌ Tabela '$tabela': NÃO EXISTE\n";
        }
    } catch (Exception $e) {
        echo "❌ Erro ao verificar tabela '$tabela': " . $e->getMessage() . "\n";
    }
}
echo "\n";

// 3. Verificar estrutura da tabela aluno_responsavel
echo "## ESTRUTURA DA TABELA ALUNO_RESPONSAVEL ##\n";
try {
    $stmt = $conexao->query("DESCRIBE aluno_responsavel");
    if ($stmt->rowCount() > 0) {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
        }
    } else {
        echo "❌ Não foi possível obter a estrutura da tabela.\n";
    }
} catch (Exception $e) {
    echo "❌ Erro ao verificar estrutura: " . $e->getMessage() . "\n";
}
echo "\n";

// 4. Verificar chaves estrangeiras
echo "## CHAVES ESTRANGEIRAS ##\n";
try {
    $stmt = $conexao->query("
        SELECT 
            TABLE_NAME, 
            COLUMN_NAME, 
            CONSTRAINT_NAME, 
            REFERENCED_TABLE_NAME, 
            REFERENCED_COLUMN_NAME
        FROM
            INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE
            REFERENCED_TABLE_NAME IS NOT NULL
            AND TABLE_SCHEMA = DATABASE()
    ");
    
    if ($stmt->rowCount() > 0) {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "- " . $row['TABLE_NAME'] . "." . $row['COLUMN_NAME'] . 
                 " -> " . $row['REFERENCED_TABLE_NAME'] . "." . $row['REFERENCED_COLUMN_NAME'] . 
                 " (" . $row['CONSTRAINT_NAME'] . ")\n";
        }
    } else {
        echo "❌ Não foram encontradas chaves estrangeiras.\n";
    }
} catch (Exception $e) {
    echo "❌ Erro ao verificar chaves estrangeiras: " . $e->getMessage() . "\n";
}
echo "\n";

// 5. Testar consultas simples
echo "## TESTE DE CONSULTAS ##\n";

// Testar SELECT em alunos
try {
    $stmt = $conexao->query("SELECT COUNT(*) FROM alunos");
    $count = $stmt->fetchColumn();
    echo "✅ SELECT em alunos: $count registros\n";
} catch (Exception $e) {
    echo "❌ Erro em SELECT alunos: " . $e->getMessage() . "\n";
}

// Testar SELECT em responsaveis
try {
    $stmt = $conexao->query("SELECT COUNT(*) FROM responsaveis");
    $count = $stmt->fetchColumn();
    echo "✅ SELECT em responsaveis: $count registros\n";
} catch (Exception $e) {
    echo "❌ Erro em SELECT responsaveis: " . $e->getMessage() . "\n";
}

// Testar SELECT em aluno_responsavel
try {
    $stmt = $conexao->query("SELECT COUNT(*) FROM aluno_responsavel");
    $count = $stmt->fetchColumn();
    echo "✅ SELECT em aluno_responsavel: $count registros\n";
} catch (Exception $e) {
    echo "❌ Erro em SELECT aluno_responsavel: " . $e->getMessage() . "\n";
}

echo "\nDiagnóstico concluído.";