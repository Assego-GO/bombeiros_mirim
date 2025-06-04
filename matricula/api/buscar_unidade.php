<?php
// Configuração para exibir erros
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
        
    // Verificar se o ID foi fornecido
    if (!isset($_GET['id'])) {
        throw new Exception("ID da unidade não fornecido");
    }
    
    $id = intval($_GET['id']);
    
    // Preparar consulta SQL
    $sql = "SELECT id, nome, endereco, telefone, coordenador, data_criacao, ultima_atualizacao 
            FROM unidade 
            WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Erro na preparação da consulta: " . $conn->error);
    }
    
    $stmt->bind_param("i", $id);
    
    // Executar a consulta
    if (!$stmt->execute()) {
        throw new Exception("Erro ao buscar a unidade: " . $stmt->error);
    }
    
    $resultado = $stmt->get_result();
    
    if ($resultado->num_rows === 0) {
        echo json_encode([
            'status' => 'info',
            'mensagem' => 'Nenhuma unidade encontrada com este ID'
        ]);
        exit;
    }
    
    $unidade = $resultado->fetch_assoc();
    
    // Formatar datas para exibição mais amigável, se necessário
    if (isset($unidade['data_criacao'])) {
        $data_criacao = new DateTime($unidade['data_criacao']);
        $unidade['data_criacao'] = $data_criacao->format('Y-m-d H:i:s');
    }
    
    if (isset($unidade['ultima_atualizacao'])) {
        $ultima_atualizacao = new DateTime($unidade['ultima_atualizacao']);
        $unidade['ultima_atualizacao'] = $ultima_atualizacao->format('Y-m-d H:i:s');
    }
    
    // Retornar dados no formato JSON
    echo json_encode([
        'status' => 'sucesso',
        'data' => $unidade
    ]);
    
} catch (Exception $e) {
    // Retornar mensagem de erro em formato JSON
    http_response_code(500);
    echo json_encode([
        'status' => 'erro',
        'mensagem' => $e->getMessage()
    ]);
} finally {
    // Fechar statement e conexão
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}
?>