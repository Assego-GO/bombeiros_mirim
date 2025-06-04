<?php
header("Content-Type: application/json");
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include "conexao.php";


file_put_contents('debug_editar_professor.log', date('Y-m-d H:i:s') . " - POST Data: " . file_get_contents("php://input") . "\n", FILE_APPEND);

try {
    $data = json_decode(file_get_contents("php://input"), true);
    file_put_contents('debug_editar_professor.log', date('Y-m-d H:i:s') . " - Decoded: " . print_r($data, true) . "\n", FILE_APPEND);
    
    if (!$data) {
        echo json_encode(["status" => "erro", "mensagem" => "Dados JSON inválidos."]);
        exit;
    }
    
    if (!isset($data['id']) || !isset($data['nome'])) {
        echo json_encode(["status" => "erro", "mensagem" => "Campos obrigatórios não fornecidos."]);
        exit;
    }
    
    // Ve se a senha foi inserida 
    if (!empty($data['senha'])) {
       
        $sql = "UPDATE professor SET 
                nome = ?, 
                email = ?, 
                senha = ?, 
                telefone = ? 
                WHERE id = ?";
                
        file_put_contents('debug_editar_professor.log', date('Y-m-d H:i:s') . " - SQL with password: $sql\n", FILE_APPEND);
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            file_put_contents('debug_editar_professor.log', date('Y-m-d H:i:s') . " - Erro na preparação: " . $conn->error . "\n", FILE_APPEND);
            echo json_encode(["status" => "erro", "mensagem" => "Erro ao preparar consulta: " . $conn->error]);
            exit;
        }
        
        $nome = $data['nome'];
        $email = $data['email'] ?? '';
        $senha = password_hash($data['senha'], PASSWORD_DEFAULT); //hash 
        $telefone = $data['telefone'] ?? '';
        $id = $data['id'];
        
        $stmt->bind_param(
            "ssssi",
            $nome,
            $email,
            $senha,
            $telefone,
            $id
        );
        // caso ela não tenha sido inserida 
    } else {
     
        $sql = "UPDATE professor SET 
                nome = ?, 
                email = ?, 
                telefone = ? 
                WHERE id = ?";
                
        file_put_contents('debug_editar_professor.log', date('Y-m-d H:i:s') . " - SQL without password: $sql\n", FILE_APPEND);
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            file_put_contents('debug_editar_professor.log', date('Y-m-d H:i:s') . " - Erro na preparação: " . $conn->error . "\n", FILE_APPEND);
            echo json_encode(["status" => "erro", "mensagem" => "Erro ao preparar consulta: " . $conn->error]);
            exit;
        }
        
        $nome = $data['nome'];
        $email = $data['email'] ?? '';
        $telefone = $data['telefone'] ?? '';
        $id = $data['id'];
        
        $stmt->bind_param(
            "sssi",
            $nome,
            $email,
            $telefone,
            $id
        );
    }
    
    $success = $stmt->execute();
    $affected = $stmt->affected_rows;
    
    file_put_contents('debug_editar_professor.log', date('Y-m-d H:i:s') . " - Execute result: " . ($success ? "true" : "false") . "\n", FILE_APPEND);
    file_put_contents('debug_editar_professor.log', date('Y-m-d H:i:s') . " - Affected rows: $affected\n", FILE_APPEND);
    file_put_contents('debug_editar_professor.log', date('Y-m-d H:i:s') . " - Error (if any): " . $stmt->error . "\n", FILE_APPEND);
    
    if ($success) {
        if ($affected > 0) {
            echo json_encode(["status" => "sucesso", "mensagem" => "Professor atualizado com sucesso!"]);
        } else {
            echo json_encode(["status" => "alerta", "mensagem" => "Nenhuma alteração foi feita. Os dados podem ser idênticos ou o ID do professor pode estar incorreto."]);
        }
    } else {
        echo json_encode(["status" => "erro", "mensagem" => "Erro ao atualizar: " . $stmt->error]);
    }
} catch (Exception $e) {
    file_put_contents('debug_editar_professor.log', date('Y-m-d H:i:s') . " - Exception: " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode(["status" => "erro", "mensagem" => "Exceção: " . $e->getMessage()]);
}
?>