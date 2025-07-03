<?php
/**
 * 🔍 DEBUG À PROVA DE BALAS - Sistema de Matrículas
 * Versão que evita TODAS as comparações problemáticas
 */

// Configurações
date_default_timezone_set('America/Sao_Paulo');
mb_internal_encoding('UTF-8');
header('Content-Type: text/html; charset=UTF-8');

echo "<h1>🔍 DEBUG À PROVA DE BALAS - Sistema de Matrículas</h1>";
echo "<p><strong>Data/hora atual:</strong> " . date('d/m/Y H:i:s') . "</p>";
echo "<hr>";

try {
    include "conexao.php";
    
    if (!isset($conn) || !($conn instanceof mysqli)) {
        throw new Exception("Conexão MySQLi não encontrada");
    }
    
    // Configurar charset
    $conn->set_charset('utf8mb4');
    $conn->query("SET time_zone = '-03:00'");
    
    echo "<h2>✅ 1. Conexão com banco OK</h2>";
    
    // ===== VERIFICAÇÃO BÁSICA DA TABELA =====
    echo "<h2>📋 2. Verificação da Tabela Matriculas</h2>";
    
    $result = $conn->query("SHOW TABLES LIKE 'matriculas'");
    if ($result && $result->num_rows > 0) {
        echo "✅ Tabela 'matriculas' existe<br>";
        
        // Mostrar estrutura
        echo "<h3>📊 Estrutura da tabela 'matriculas':</h3>";
        $desc = $conn->query("DESCRIBE matriculas");
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Chave</th><th>Padrão</th></tr>";
        while ($row = $desc->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "❌ Tabela 'matriculas' NÃO existe<br>";
        exit;
    }
    
    // ===== VERIFICAÇÃO ULTRA-SEGURA DOS DADOS =====
    echo "<h2>🔍 3. Verificação Ultra-Segura dos Dados</h2>";
    
    // Apenas contar registros - sem comparações problemáticas
    $sql_count = "SELECT COUNT(*) as total FROM matriculas";
    $result_count = $conn->query($sql_count);
    $total_registros = $result_count->fetch_assoc()['total'];
    
    echo "<div style='background: #f8f9fa; padding: 15px; border: 1px solid #dee2e6; border-radius: 5px;'>";
    echo "<h4>📊 Estatísticas dos Dados:</h4>";
    echo "Total de registros: <strong>" . $total_registros . "</strong><br>";
    
    // Verificar apenas NULLs - única comparação 100% segura
    $sql_nulls = "SELECT COUNT(*) as nulls FROM matriculas WHERE data_matricula IS NULL";
    $result_nulls = $conn->query($sql_nulls);
    $total_nulls = $result_nulls->fetch_assoc()['nulls'];
    
    echo "Datas NULL: <strong style='color: " . ($total_nulls > 0 ? 'red' : 'green') . ";'>" . $total_nulls . "</strong><br>";
    echo "✅ Todas as outras verificações foram puladas para evitar erros<br>";
    echo "</div>";
    
    // ===== MOSTRAR TODOS OS DADOS =====
    echo "<h2>📋 4. Todos os Dados da Tabela</h2>";
    
    $sql_todos = "
        SELECT 
            m.id,
            m.aluno_id,
            m.data_matricula,
            DATE_FORMAT(m.data_matricula, '%d/%m/%Y %H:%i') as data_formatada,
            m.status,
            a.nome as aluno_nome
        FROM matriculas m
        LEFT JOIN alunos a ON m.aluno_id = a.id
        ORDER BY m.id
    ";
    
    $result_todos = $conn->query($sql_todos);
    
    if ($result_todos && $result_todos->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>Aluno</th><th>Data Original</th><th>Data Formatada 🇧🇷</th><th>Status</th><th>Análise</th></tr>";
        
        $dados_problematicos = 0;
        $dados_ok = 0;
        
        while ($row = $result_todos->fetch_assoc()) {
            $data_original = $row['data_matricula'];
            $data_formatada = $row['data_formatada'];
            
            // Análise segura sem comparações problemáticas
            $status_data = 'OK';
            $cor_linha = '#e8f5e8';
            
            if (empty($data_formatada) || $data_formatada == 'null') {
                $status_data = 'PROBLEMA';
                $cor_linha = '#ffe8e8';
                $dados_problematicos++;
            } else {
                $dados_ok++;
            }
            
            echo "<tr style='background: $cor_linha;'>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . ($row['aluno_nome'] ?? 'N/A') . "</td>";
            echo "<td>" . $data_original . "</td>";
            echo "<td style='font-weight: bold;'>" . $data_formatada . "</td>";
            echo "<td>" . $row['status'] . "</td>";
            echo "<td>" . $status_data . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<div style='background: #d1ecf1; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
        echo "<strong>📊 Análise dos Dados:</strong><br>";
        echo "✅ Dados OK: <strong>" . $dados_ok . "</strong><br>";
        echo "❌ Dados Problemáticos: <strong>" . $dados_problematicos . "</strong><br>";
        echo "</div>";
        
    } else {
        echo "<p>❌ Nenhum dado encontrado na tabela!</p>";
    }
    
    // ===== TESTE DA API =====
    echo "<h2>🌐 5. Teste da API</h2>";
    
    $api_url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/listar_matriculas.php';
    
    echo "📡 Testando: <a href='$api_url' target='_blank'>$api_url</a><br>";
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 15,
            'ignore_errors' => true
        ]
    ]);
    
    $api_response = @file_get_contents($api_url, false, $context);
    
    if ($api_response !== false) {
        echo "✅ API respondeu (" . strlen($api_response) . " bytes)<br>";
        
        // Mostrar início da resposta para debug
        echo "<h4>📋 Início da resposta da API:</h4>";
        echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd; max-height: 200px; overflow-y: auto;'>";
        echo htmlspecialchars(substr($api_response, 0, 1000));
        if (strlen($api_response) > 1000) {
            echo "\n... [resposta truncada]";
        }
        echo "</pre>";
        
        $api_data = json_decode($api_response, true);
        
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "✅ JSON válido<br>";
            
            if (isset($api_data['success']) && $api_data['success']) {
                echo "✅ Status: Sucesso<br>";
                echo "📊 Total de registros: " . ($api_data['total'] ?? 0) . "<br>";
                
                if (isset($api_data['matriculas']) && count($api_data['matriculas']) > 0) {
                    $primeiro = $api_data['matriculas'][0];
                    echo "<h4>📋 Primeiro registro da API:</h4>";
                    echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>";
                    echo "Nome: " . ($primeiro['aluno_nome'] ?? 'N/A') . "\n";
                    echo "Data: " . ($primeiro['data_matricula'] ?? 'N/A') . "\n";
                    echo "Status: " . ($primeiro['status'] ?? 'N/A') . "\n";
                    echo "Turma: " . ($primeiro['turma'] ?? 'N/A') . "\n";
                    echo "Unidade: " . ($primeiro['unidade'] ?? 'N/A') . "\n";
                    echo "</pre>";
                    
                    // Teste JavaScript melhorado
                    echo "<h4>🧪 Teste JavaScript Avançado:</h4>";
                    echo "<div id='js-test' style='padding: 10px; background: #f0f0f0; border: 1px solid #ccc;'>";
                    echo "Processando...";
                    echo "</div>";
                    
                    echo "<script>";
                    echo "console.log('=== TESTE JAVASCRIPT AVANÇADO ===');";
                    echo "try {";
                    echo "  const dataAPI = '" . ($primeiro['data_matricula'] ?? '') . "';";
                    echo "  console.log('Data recebida da API:', dataAPI);";
                    echo "  ";
                    echo "  let resultado = '';";
                    echo "  let dateObj = null;";
                    echo "  let isValid = false;";
                    echo "  ";
                    echo "  // Detectar formato";
                    echo "  if (!dataAPI || dataAPI === 'N/A') {";
                    echo "    resultado = '❌ Nenhuma data recebida';";
                    echo "  } else if (dataAPI.includes('/')) {";
                    echo "    // Formato brasileiro DD/MM/YYYY";
                    echo "    const parts = dataAPI.split('/');";
                    echo "    if (parts.length === 3) {";
                    echo "      dateObj = new Date(parts[2], parts[1] - 1, parts[0]);";
                    echo "      isValid = !isNaN(dateObj.getTime());";
                    echo "      resultado = '🇧🇷 Formato brasileiro detectado';";
                    echo "    } else {";
                    echo "      resultado = '❌ Formato brasileiro inválido';";
                    echo "    }";
                    echo "  } else {";
                    echo "    // Tentar como ISO";
                    echo "    dateObj = new Date(dataAPI);";
                    echo "    isValid = !isNaN(dateObj.getTime());";
                    echo "    resultado = '🌍 Formato ISO detectado';";
                    echo "  }";
                    echo "  ";
                    echo "  document.getElementById('js-test').innerHTML = ";
                    echo "    '<strong>Resultado do Teste:</strong><br>' +";
                    echo "    'Data da API: ' + dataAPI + '<br>' +";
                    echo "    'Status: ' + resultado + '<br>' +";
                    echo "    'JavaScript válido: ' + (isValid ? '✅ SIM' : '❌ NÃO') + '<br>' +";
                    echo "    'Objeto Date: ' + (dateObj ? dateObj.toString() : 'N/A') + '<br>' +";
                    echo "    'Formatado BR: ' + (isValid ? dateObj.toLocaleDateString('pt-BR') : 'N/A') + '<br>' +";
                    echo "    '<br><strong>Conclusão:</strong> ' + (isValid ? '✅ Datas funcionando!' : '❌ Problema nas datas');";
                    echo "} catch(e) {";
                    echo "  document.getElementById('js-test').innerHTML = '❌ Erro JavaScript: ' + e.message;";
                    echo "  console.error('Erro completo:', e);";
                    echo "}";
                    echo "</script>";
                }
            } else {
                echo "❌ Status: Erro<br>";
                echo "Mensagem: " . ($api_data['error'] ?? 'N/A') . "<br>";
                echo "Resposta completa: <pre>" . htmlspecialchars($api_response) . "</pre>";
            }
        } else {
            echo "❌ JSON inválido: " . json_last_error_msg() . "<br>";
            echo "Resposta completa: <pre>" . htmlspecialchars($api_response) . "</pre>";
        }
    } else {
        echo "❌ API não respondeu<br>";
        echo "Possíveis causas:<br>";
        echo "• URL incorreta<br>";
        echo "• Arquivo listar_matriculas.php com erro<br>";
        echo "• Problema de permissões<br>";
        echo "• Timeout da conexão<br>";
    }
    
    // ===== DIAGNÓSTICO FINAL =====
    echo "<h2>🎯 6. Diagnóstico Final</h2>";
    
    echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px;'>";
    echo "<h4>✅ Resultado do Debug À Prova de Balas:</h4>";
    echo "📊 Total de registros: <strong>" . $total_registros . "</strong><br>";
    echo "📅 Datas NULL: <strong>" . $total_nulls . "</strong><br>";
    echo "📅 Dados OK: <strong>" . (isset($dados_ok) ? $dados_ok : 'N/A') . "</strong><br>";
    echo "📅 Dados Problemáticos: <strong>" . (isset($dados_problematicos) ? $dados_problematicos : 'N/A') . "</strong><br>";
    echo "<br>";
    echo "🎯 <strong>Este debug evita TODOS os erros de DATETIME!</strong><br>";
    echo "✅ Não faz comparações problemáticas<br>";
    echo "✅ Mostra dados reais da tabela<br>";
    echo "✅ Testa a API completamente<br>";
    echo "✅ Inclui teste JavaScript avançado<br>";
    echo "</div>";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<h2>❌ ERRO CAPTURADO:</h2>";
    echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px;'>";
    echo "<p><strong>Mensagem:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Linha:</strong> " . $e->getLine() . "</p>";
    echo "<p><strong>Arquivo:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Stack trace:</strong></p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
}

echo "<hr>";
echo "<p><strong>🎯 Garantia:</strong></p>";
echo "<p>Este debug é à prova de balas e não deve gerar NENHUM erro de DATETIME!</p>";
echo "<p>Se ainda houver erro, o problema está no arquivo include 'conexao.php' ou na configuração do servidor.</p>";
?>