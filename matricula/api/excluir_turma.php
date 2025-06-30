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
    $stmt_buscar = $conn->prepare("SELECT * FROM turma WHERE id = ?");
    $stmt_buscar->bind_param("i", $id);
    $stmt_buscar->execute();
    $dados = $stmt_buscar->get_result()->fetch_assoc();
    
    if (!$dados) {
        echo json_encode(["status" => "erro", "mensagem" => "Turma não encontrada."]);
        exit;
    }

    // Excluir
    $sql = "DELETE FROM turma WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        // Registrar auditoria
        $audit->log('EXCLUIR_TURMA', 'turma', $id, $dados);
        
        echo json_encode(["status" => "sucesso", "mensagem" => "Turma excluída com sucesso!"]);
    } else {
        echo json_encode(["status" => "erro", "mensagem" => "Erro ao excluir turma."]);
    }

} catch (Exception $e) {
    echo json_encode(["status" => "erro", "mensagem" => "Erro: " . $e->getMessage()]);
}
?>