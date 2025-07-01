<?php
header("Content-Type: application/json");
ini_set('display_errors', 0);
error_reporting(E_ALL);

include "conexao.php";

// AUDITORIA: Adicione essas 3 linhas
session_start();
require_once "auditoria.php";
$audit = new Auditoria($conn);

try {
    $data = json_decode(file_get_contents("php://input"), true);
    if (!$data) {
        echo json_encode(["status" => "erro", "mensagem" => "Dados inválidos."]);
        exit;
    }
  
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
        echo json_encode(["status" => "erro", "mensagem" => "Erro ao preparar consulta: " . $conn->error]);
        exit;
    }

    // Vincula os parâmetros
    $stmt->bind_param(
        "ssss",
        $data['nome'], 
        $email,        
        $telefone,     
        $senha        
    );
    
    $result = $stmt->execute();
    
    if ($result) {
        $professor_id = $conn->insert_id;
        
        // AUDITORIA: Registra a criação do professor (sem a senha!)
        $audit_params = [
            'nome' => $data['nome'],
            'email' => $email,
            'telefone' => $telefone,
            'senha' => isset($senha) ? '[SENHA PROTEGIDA]' : null
        ];
        $audit->log('CRIAR_PROFESSOR', 'professor', $professor_id, $audit_params);
        
        echo json_encode(["status" => "sucesso", "id" => $professor_id, "mensagem" => "Professor cadastrado com sucesso!"]);
    } else {
        echo json_encode(["status" => "erro", "mensagem" => $stmt->error]);
    }

} catch (Exception $e) {
    echo json_encode(["status" => "erro", "mensagem" => "Exceção: " . $e->getMessage()]);
}
?>