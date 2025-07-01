<?php
header("Content-Type: application/json");
ini_set('display_errors', 0);
error_reporting(E_ALL);

include "conexao.php";

// AUDITORIA: Adicione essas 3 linhas
session_start();
require_once "auditoria.php";
$audit = new Auditoria($conn);

file_put_contents('debug_novo_professor.log', date('Y-m-d H:i:s') . " - Dados recebidos: " . file_get_contents("php://input") . "\n", FILE_APPEND);

try {
    $data = json_decode(file_get_contents("php://input"), true);
    if (!$data) {
        echo json_encode(["status" => "erro", "mensagem" => "Dados inválidos."]);
        exit;
    }
 
    file_put_contents('debug_novo_professor.log', date('Y-m-d H:i:s') . " - Dados processados: " . print_r($data, true) . "\n", FILE_APPEND);
  
    if (!isset($data['nome']) || empty($data['nome'])) {
        echo json_encode(["status" => "erro", "mensagem" => "O nome do professor é obrigatório."]);
        exit;
    }

    // Preparar valores opcionais
    $email = $data['email'] ?? null;
    $telefone = $data['telefone'] ?? null;
    
    // Tratamento da senha - aplicar hash se fornecida
    $senha = null;
    if (isset($data['senha']) && !empty($data['senha'])) {
        $senha = password_hash($data['senha'], PASSWORD_DEFAULT);
    }
    
    // Consulta SQL para tabela 'professor'
    $sql = "INSERT INTO professor (
        nome,
        email,
        telefone,
        senha
    ) VALUES (?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        file_put_contents('debug_novo_professor.log', date('Y-m-d H:i:s') . " - Erro na preparação: " . $conn->error . "\n", FILE_APPEND);
        echo json_encode(["status" => "erro", "mensagem" => "Erro ao preparar consulta: " . $conn->error]);
        exit;
    }
  
    file_put_contents('debug_novo_professor.log', date('Y-m-d H:i:s') . " - SQL: $sql\n", FILE_APPEND);

    // Vincula os parâmetros
    $stmt->bind_param(
        "ssss",
        $data['nome'], 
        $email,        
        $telefone,     
        $senha        
    );

    $log_params = [
        'nome' => $data['nome'],
        'email' => $email,
        'telefone' => $telefone,
        'senha' => isset($senha) ? '[SENHA PROTEGIDA]' : null
    ];
    file_put_contents('debug_novo_professor.log', date('Y-m-d H:i:s') . " - Parâmetros: " . print_r($log_params, true) . "\n", FILE_APPEND);
    
    $result = $stmt->execute();
  
    file_put_contents('debug_novo_professor.log', date('Y-m-d H:i:s') . " - Execução: " . ($result ? "Sucesso" : "Falha") . "\n", FILE_APPEND);
    
    if ($result) {
        $professor_id = $conn->insert_id;
        
        // AUDITORIA: Registra a criação do professor (sem a senha!)
        $audit->log('CRIAR_PROFESSOR', 'professor', $professor_id, $log_params);
        
        echo json_encode(["status" => "sucesso", "id" => $professor_id, "mensagem" => "Professor cadastrado com sucesso!"]);
    } else {
        file_put_contents('debug_novo_professor.log', date('Y-m-d H:i:s') . " - Erro: " . $stmt->error . "\n", FILE_APPEND);
        echo json_encode(["status" => "erro", "mensagem" => $stmt->error]);
    }

} catch (Exception $e) {
    file_put_contents('debug_novo_professor.log', date('Y-m-d H:i:s') . " - Exceção: " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode(["status" => "erro", "mensagem" => "Exceção: " . $e->getMessage()]);
}
?>