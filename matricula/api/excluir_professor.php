<?php
header("Content-Type: application/json");
include "conexao.php";

// AUDITORIA
session_start();
require_once "auditoria.php";
$audit = new Auditoria($conn);

try {
    $id = $_GET['id'] ?? 0;
    
    if ($id <= 0) {
        echo json_encode(["status" => "erro", "mensagem" => "ID inválido."]);
        exit;
    }

    // Buscar dados antes de excluir
    $stmt_buscar = $conn->prepare("SELECT * FROM professor WHERE id = ?");
    $stmt_buscar->bind_param("i", $id);
    $stmt_buscar->execute();
    $dados = $stmt_buscar->get_result()->fetch_assoc();
    
    if (!$dados) {
        echo json_encode(["status" => "erro", "mensagem" => "Professor não encontrado."]);
        exit;
    }

    // Remover senha dos dados para auditoria
    $dados_safe = $dados;
    $dados_safe['senha'] = '[PROTEGIDA]';

    // Excluir
    $sql = "DELETE FROM professor WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        // Registrar auditoria
        $audit->log('EXCLUIR_PROFESSOR', 'professor', $id, $dados_safe);
        
        echo json_encode(["status" => "sucesso", "mensagem" => "Professor excluído com sucesso!"]);
    } else {
        echo json_encode(["status" => "erro", "mensagem" => "Erro ao excluir professor."]);
    }

} catch (Exception $e) {
    echo json_encode(["status" => "erro", "mensagem" => "Erro: " . $e->getMessage()]);
}
?>