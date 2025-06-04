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
        throw new Exception("ID do professor não fornecido");
    }

    $id = intval($_GET['id']);

    $sql = "SELECT id, nome, email, senha,telefone 
            FROM professor
            WHERE id = ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Erro na preparação da consulta: " . $conn->error);
    }

    $stmt->bind_param("i", $id);

    // Executar a consulta
    if (!$stmt->execute()) {
        throw new Exception("Erro ao buscar o professor: " . $stmt->error);
    }

    $resultado = $stmt->get_result();
    if ($resultado->num_rows === 0) {
        echo json_encode([
            'status' => 'info',
            'mensagem' => 'Nenhum professor encontrado com este ID'
        ]);
        exit;
    }

    $professor = $resultado->fetch_assoc();

    // Retornar dados no formato JSON
    echo json_encode([
        'status' => 'sucesso',
        'data' => $professor
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