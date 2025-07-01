<?php
header("Content-Type: application/json");
ini_set('display_errors', 0);
error_reporting(E_ALL);

include "conexao.php";

try {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!$data) {
        echo json_encode(["status" => "erro", "mensagem" => "Dados JSON inválidos."]);
        exit;
    }

    // Verificar campos obrigatórios
    if (!isset($data['id']) || !isset($data['unidade']) || !isset($data['turma']) || 
        !isset($data['data_matricula']) || !isset($data['status'])) {
        echo json_encode(["status" => "erro", "mensagem" => "Campos obrigatórios não fornecidos."]);
        exit;
    }

    // Incluir status_programa na atualização (com valor padrão se não fornecido)
    $status_programa = isset($data['status-programa']) ? $data['status-programa'] : 'novato';

    // Usar o ID correto da matrícula - AGORA COM STATUS_PROGRAMA
    $sql = "UPDATE matriculas SET 
        unidade = ?, 
        turma = ?, 
        data_matricula = ?, 
        status = ?,
        status_programa = ?
    WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(["status" => "erro", "mensagem" => "Erro ao preparar consulta: " . $conn->error]);
        exit;
    }

    // Data no formato correto para o banco
    $data_matricula = $data['data_matricula'];
    if (strpos($data_matricula, ' ') === false) {
        $data_matricula .= ' 00:00:00'; // Adiciona hora se não estiver presente
    }

    // ATENÇÃO: Adicionei mais um parâmetro 's' para o status_programa e mais um 'i' para o ID
    $stmt->bind_param(
        "sssssi",
        $data['unidade'],
        $data['turma'],
        $data_matricula,
        $data['status'],
        $status_programa,
        $data['id']
    );

    $success = $stmt->execute();
    $affected = $stmt->affected_rows;

    if ($success) {
        if ($affected > 0) {
            echo json_encode(["status" => "sucesso", "mensagem" => "Matrícula atualizada com sucesso!"]);
        } else {
            // Se nenhuma linha foi afetada, pode ser que os dados sejam idênticos ou o ID esteja errado
            echo json_encode(["status" => "alerta", "mensagem" => "Nenhuma alteração foi feita. Os dados podem ser idênticos ou o ID da matrícula pode estar incorreto."]);
        }
    } else {
        echo json_encode(["status" => "erro", "mensagem" => "Erro ao atualizar: " . $stmt->error]);
    }
} catch (Exception $e) {
    echo json_encode(["status" => "erro", "mensagem" => "Exceção: " . $e->getMessage()]);
}
?>