<?php
// Configuração para exibir erros (útil durante o desenvolvimento)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Cabeçalhos para JSON e CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Para desenvolvimento
header('Access-Control-Allow-Methods: GET');

try {
    // Incluir conexão com o banco de dados
    require_once "conexao.php";
    
    // Verificar se a conexão foi bem-sucedida
    if ($conn->connect_error) {
        throw new Exception("Falha na conexão com o banco de dados: " . $conn->connect_error);
    }
    
    // Consulta SQL para obter todos os campos da tabela unidade
    $sql = "SELECT id, nome, endereco, telefone, coordenador, data_criacao, ultima_atualizacao 
            FROM unidade 
            ORDER BY nome ASC";
    
    $resultado = $conn->query($sql);
    
    if (!$resultado) {
        throw new Exception("Erro na consulta: " . $conn->error);
    }
    
    $dados = [];
    
    // Obter todos os registros
    while ($row = $resultado->fetch_assoc()) {
        // Formatar datas para exibição mais amigável, se necessário
        if (isset($row['data_criacao'])) {
            $data_criacao = new DateTime($row['data_criacao']);
            $row['data_criacao'] = $data_criacao->format('Y-m-d H:i:s');
        }
        
        if (isset($row['ultima_atualizacao'])) {
            $ultima_atualizacao = new DateTime($row['ultima_atualizacao']);
            $row['ultima_atualizacao'] = $ultima_atualizacao->format('Y-m-d H:i:s');
        }
        
        $dados[] = $row;
    }
    
    // Retornar dados no formato JSON
    echo json_encode([
        'status' => 'sucesso',
        'data' => $dados,
        'total' => count($dados)
    ]);
    
} catch (Exception $e) {
    // Retornar mensagem de erro em formato JSON
    http_response_code(500);
    echo json_encode([
        'status' => 'erro',
        'mensagem' => $e->getMessage()
    ]);
} finally {
    // Fechar conexão
    if (isset($conn)) {
        $conn->close();
    }
}
?>