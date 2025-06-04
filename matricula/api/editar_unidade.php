<?php
header("Content-Type: application/json");
ini_set('display_errors', 0);
error_reporting(E_ALL);
include "conexao.php";

file_put_contents('debug_editar_unidade.log', date('Y-m-d H:i:s') . " - POST Data: " . file_get_contents("php://input") . "\n", FILE_APPEND);
try {
    $data = json_decode(file_get_contents("php://input"), true);
    file_put_contents('debug_editar_unidade.log', date('Y-m-d H:i:s') . " - Decoded: " . print_r($data, true) . "\n", FILE_APPEND);
    
    if (!$data) {
        echo json_encode(["status" => "erro", "mensagem" => "Dados JSON inválidos."]);
        exit;
    }
    
    if (!isset($data['id']) || !isset($data['nome'])) {
        echo json_encode(["status" => "erro", "mensagem" => "Campos obrigatórios não fornecidos."]);
        exit;
    }
    

    $sql = "UPDATE unidade SET
        nome = ?,
        endereco = ?,
        telefone = ?,
        coordenador = ?,
        ultima_atualizacao = NOW()
        WHERE id = ?";
        
    file_put_contents('debug_editar_unidade.log', date('Y-m-d H:i:s') . " - SQL: $sql\n", FILE_APPEND);
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        file_put_contents('debug_editar_unidade.log', date('Y-m-d H:i:s') . " - Erro na preparação: " . $conn->error . "\n", FILE_APPEND);
        echo json_encode(["status" => "erro", "mensagem" => "Erro ao preparar consulta: " . $conn->error]);
        exit;
    }

    $nome = $data['nome'];
    $endereco = $data['endereco'] ?? '';
    $telefone = $data['telefone'] ?? '';
    $coordenador = $data['coordenador'] ?? '';
    $id = $data['id'];
    
    $stmt->bind_param(
        "ssssi",
        $nome,
        $endereco,
        $telefone,
        $coordenador,
        $id
    );
    
    $success = $stmt->execute();
    $affected = $stmt->affected_rows;
    
    file_put_contents('debug_editar_unidade.log', date('Y-m-d H:i:s') . " - Execute result: " . ($success ? "true" : "false") . "\n", FILE_APPEND);
    file_put_contents('debug_editar_unidade.log', date('Y-m-d H:i:s') . " - Affected rows: $affected\n", FILE_APPEND);
    file_put_contents('debug_editar_unidade.log', date('Y-m-d H:i:s') . " - Error (if any): " . $stmt->error . "\n", FILE_APPEND);
    
    if ($success) {
        if ($affected > 0) {
            echo json_encode(["status" => "sucesso", "mensagem" => "Unidade editada com sucesso!"]);
        } else {
           
            echo json_encode(["status" => "alerta", "mensagem" => "Nenhuma alteração foi feita. Os dados podem ser idênticos ou o ID da unidade pode estar incorreto."]);
        }
    } else {
        echo json_encode(["status" => "erro", "mensagem" => "Erro ao atualizar: " . $stmt->error]);
    }
} catch (Exception $e) {
    file_put_contents('debug_editar_unidade.log', date('Y-m-d H:i:s') . " - Exception: " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode(["status" => "erro", "mensagem" => "Exceção: " . $e->getMessage()]);
}
?>