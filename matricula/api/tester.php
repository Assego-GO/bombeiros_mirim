<?php
/**
 * Arquivo de teste para debug do ranking - MySQLi
 */

// Habilitar todos os erros para debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Simular login de admin para teste
$_SESSION['usuario_id'] = 6; // Coloque um ID de admin válido

echo "<h1>Teste do Sistema de Ranking (MySQLi)</h1>";

try {
    echo "<h2>1. Testando Conexão com Banco</h2>";
    
    require_once "conexao.php";
    
    if ($conn->connect_error) {
        throw new Exception("Falha na conexão: " . $conn->connect_error);
    }
    
    echo "✅ Conexão com banco OK<br>";
    echo "🏠 Host: " . $conn->host_info . "<br>";
    echo "🗄️ Charset: " . $conn->character_set_name() . "<br><br>";
    
    echo "<h2>2. Testando Tabelas</h2>";
    
    // Verificar tabela avaliacoes
    $result = $conn->query("SHOW TABLES LIKE 'avaliacoes'");
    if ($result && $result->num_rows > 0) {
        echo "✅ Tabela 'avaliacoes' existe<br>";
        
        // Contar registros
        $result = $conn->query("SELECT COUNT(*) as total FROM avaliacoes");
        if ($result) {
            $row = $result->fetch_assoc();
            echo "📊 Total de avaliações: " . $row['total'] . "<br>";
        }
        
        // Mostrar estrutura
        $result = $conn->query("DESCRIBE avaliacoes");
        if ($result) {
            echo "📋 Estrutura da tabela avaliacoes:<br>";
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Chave</th></tr>";
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row['Field'] . "</td>";
                echo "<td>" . $row['Type'] . "</td>";
                echo "<td>" . $row['Null'] . "</td>";
                echo "<td>" . $row['Key'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        // Mostrar exemplo
        $result = $conn->query("SELECT * FROM avaliacoes LIMIT 1");
        if ($result && $result->num_rows > 0) {
            $exemplo = $result->fetch_assoc();
            echo "📝 Exemplo de avaliação:<br>";
            echo "<pre>" . print_r($exemplo, true) . "</pre>";
        }
    } else {
        echo "❌ Tabela 'avaliacoes' não existe<br>";
    }
    
    // Verificar tabela atividade_participacao
    $result = $conn->query("SHOW TABLES LIKE 'atividade_participacao'");
    if ($result && $result->num_rows > 0) {
        echo "✅ Tabela 'atividade_participacao' existe<br>";
        
        $result = $conn->query("SELECT COUNT(*) as total FROM atividade_participacao");
        if ($result) {
            $row = $result->fetch_assoc();
            echo "📊 Total de participações: " . $row['total'] . "<br>";
        }
        
        // Mostrar exemplo
        $result = $conn->query("SELECT * FROM atividade_participacao LIMIT 1");
        if ($result && $result->num_rows > 0) {
            $exemplo = $result->fetch_assoc();
            echo "📝 Exemplo de participação:<br>";
            echo "<pre>" . print_r($exemplo, true) . "</pre>";
        }
    } else {
        echo "❌ Tabela 'atividade_participacao' não existe<br>";
    }
    
    // Verificar tabela alunos
    $result = $conn->query("SHOW TABLES LIKE 'alunos'");
    if ($result && $result->num_rows > 0) {
        echo "✅ Tabela 'alunos' existe<br>";
        
        $result = $conn->query("SELECT COUNT(*) as total FROM alunos");
        if ($result) {
            $row = $result->fetch_assoc();
            echo "📊 Total de alunos: " . $row['total'] . "<br>";
        }
    } else {
        echo "❌ Tabela 'alunos' não existe<br>";
    }
    
    // Verificar tabelas turma e unidades
    $result = $conn->query("SHOW TABLES LIKE 'turma'");
    if ($result && $result->num_rows > 0) {
        echo "✅ Tabela 'turma' existe<br>";
    } else {
        echo "❌ Tabela 'turma' não existe<br>";
    }
    
    $result = $conn->query("SHOW TABLES LIKE 'unidades'");
    if ($result && $result->num_rows > 0) {
        echo "✅ Tabela 'unidades' existe<br>";
    } else {
        echo "❌ Tabela 'unidades' não existe<br>";
    }
    
    echo "<br><h2>3. Testando Query Básica de Ranking</h2>";
    
    $sql = "
        SELECT 
            a.aluno_id,
            a.turma_id,
            al.nome as aluno_nome,
            AVG(COALESCE(a.velocidade, 0)) as velocidade_avg,
            AVG(COALESCE(a.resistencia, 0)) as resistencia_avg,
            COUNT(a.id) as total_avaliacoes
        FROM avaliacoes a
        LEFT JOIN alunos al ON a.aluno_id = al.id
        GROUP BY a.aluno_id, a.turma_id, al.nome
        LIMIT 3
    ";
    
    $result = $conn->query($sql);
    
    if ($result) {
        echo "📊 Resultados da query básica:<br>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID Aluno</th><th>ID Turma</th><th>Nome</th><th>Velocidade</th><th>Resistência</th><th>Avaliações</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['aluno_id'] . "</td>";
            echo "<td>" . $row['turma_id'] . "</td>";
            echo "<td>" . $row['aluno_nome'] . "</td>";
            echo "<td>" . $row['velocidade_avg'] . "</td>";
            echo "<td>" . $row['resistencia_avg'] . "</td>";
            echo "<td>" . $row['total_avaliacoes'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "❌ Erro na query: " . $conn->error . "<br>";
    }
    
    echo "<h2>4. Teste da API Completa</h2>";
    echo "<a href='ranking_alunos.php' target='_blank' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔗 Testar API ranking_alunos.php</a><br><br>";
    echo "<a href='ranking_estatisticas.php' target='_blank' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>📊 Testar API ranking_estatisticas.php</a><br><br>";
    
    echo "<h2>5. Dados para Debug</h2>";
    echo "<strong>URL para testar:</strong><br>";
    echo "• ranking_alunos.php<br>";
    echo "• ranking_alunos.php?turma_id=20<br>";
    echo "• ranking_estatisticas.php<br>";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "<br>";
    echo "📍 Arquivo: " . $e->getFile() . "<br>";
    echo "📍 Linha: " . $e->getLine() . "<br>";
    echo "<pre style='background: #f8f9fa; padding: 10px; border-left: 4px solid #dc3545;'>" . $e->getTraceAsString() . "</pre>";
    
    if (isset($conn)) {
        $conn->close();
    }
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    background: #f8f9fa;
}

h1 {
    color: #333;
    border-bottom: 3px solid #007bff;
    padding-bottom: 10px;
}

h2 {
    color: #495057;
    margin-top: 30px;
    border-left: 4px solid #007bff;
    padding-left: 15px;
}

table {
    background: white;
    margin: 10px 0;
}

th {
    background: #007bff;
    color: white;
    padding: 8px;
}

td {
    padding: 6px 8px;
}

pre {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    border-left: 4px solid #6c757d;
    overflow-x: auto;
}

.success {
    color: #28a745;
}

.error {
    color: #dc3545;
}
</style>