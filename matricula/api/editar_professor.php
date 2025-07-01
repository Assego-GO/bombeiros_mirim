<?php
header("Content-Type: application/json");
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include "conexao.php";

// AUDITORIA: Adicione essas 3 linhas
session_start();
require_once "auditoria.php";
$audit = new Auditoria($conn);

try {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!$data) {
        echo json_encode(["status" => "erro", "mensagem" => "Dados JSON inválidos."]);
        exit;
    }
    
    if (!isset($data['id']) || !isset($data['nome'])) {
        echo json_encode(["status" => "erro", "mensagem" => "Campos obrigatórios não fornecidos."]);
        exit;
    }

    // AUDITORIA: Buscar dados anteriores ANTES da edição
    $stmt_anterior = $conn->prepare("SELECT * FROM professor WHERE id = ?");
    $stmt_anterior->bind_param("i", $data['id']);
    $stmt_anterior->execute();
    $result_anterior = $stmt_anterior->get_result();
    $dados_anteriores = $result_anterior->fetch_assoc();
    
    if (!$dados_anteriores) {
        echo json_encode(["status" => "erro", "mensagem" => "Professor não encontrado."]);
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
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
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
        
        // Dados novos para auditoria (com senha)
        $dados_novos = [
            'id' => $id,
            'nome' => $nome,
            'email' => $email,
            'senha' => '[ALTERADA]',
            'telefone' => $telefone
        ];
        
    } else {
     
        $sql = "UPDATE professor SET 
                nome = ?, 
                email = ?, 
                telefone = ? 
                WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
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
        
        // Dados novos para auditoria (sem senha)
        $dados_novos = [
            'id' => $id,
            'nome' => $nome,
            'email' => $email,
            'telefone' => $telefone
        ];
    }
    
    $success = $stmt->execute();
    $affected = $stmt->affected_rows;
    
    if ($success) {
        if ($affected > 0) {
            // AUDITORIA: Registrar a edição (removendo senha dos dados anteriores)
            $dados_anteriores_safe = $dados_anteriores;
            $dados_anteriores_safe['senha'] = '[PROTEGIDA]';
            
            $audit->log('EDITAR_PROFESSOR', 'professor', $id, [
                'dados_anteriores' => $dados_anteriores_safe,
                'dados_novos' => $dados_novos
            ]);
            
            echo json_encode(["status" => "sucesso", "mensagem" => "Professor atualizado com sucesso!"]);
        } else {
            echo json_encode(["status" => "alerta", "mensagem" => "Nenhuma alteração foi feita. Os dados podem ser idênticos ou o ID do professor pode estar incorreto."]);
        }
    } else {
        echo json_encode(["status" => "erro", "mensagem" => "Erro ao atualizar: " . $stmt->error]);
    }
} catch (Exception $e) {
    echo json_encode(["status" => "erro", "mensagem" => "Exceção: " . $e->getMessage()]);
}
?>