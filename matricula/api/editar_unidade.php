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
        echo json_encode(["status" => "erro", "mensagem" => "Dados JSON inválidos."]);
        exit;
    }
    
    if (!isset($data['id']) || !isset($data['nome'])) {
        echo json_encode(["status" => "erro", "mensagem" => "Campos obrigatórios não fornecidos."]);
        exit;
    }

    // AUDITORIA: Buscar dados anteriores ANTES da edição
    $stmt_anterior = $conn->prepare("SELECT * FROM unidade WHERE id = ?");
    $stmt_anterior->bind_param("i", $data['id']);
    $stmt_anterior->execute();
    $result_anterior = $stmt_anterior->get_result();
    $dados_anteriores = $result_anterior->fetch_assoc();
    
    if (!$dados_anteriores) {
        echo json_encode(["status" => "erro", "mensagem" => "Unidade não encontrada."]);
        exit;
    }

    // SQL UPDATE incluindo o campo unidade_crbm e cidade
    $sql = "UPDATE unidade SET
        nome = ?,
        unidade_crbm = ?,
        endereco = ?,
        telefone = ?,
        coordenador = ?,
        cidade = ?,
        ultima_atualizacao = NOW()
        WHERE id = ?";
        
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        echo json_encode(["status" => "erro", "mensagem" => "Erro ao preparar consulta: " . $conn->error]);
        exit;
    }

    // Preparar variáveis incluindo unidade_crbm e cidade
    $nome = $data['nome'];
    $unidade_crbm = $data['unidade-crbm'] ?? null; // Novo campo adicionado
    $endereco = $data['endereco'] ?? '';
    $telefone = $data['telefone'] ?? '';
    $coordenador = $data['coordenador'] ?? '';
    $cidade = $data['cidade'] ?? '';
    $id = $data['id'];
    
    // Bind parameters incluindo unidade_crbm e cidade (7 strings + 1 integer)
    $stmt->bind_param(
        "ssssssi",
        $nome,
        $unidade_crbm,
        $endereco,
        $telefone,
        $coordenador,
        $cidade,
        $id
    );
    
    // Preparar dados novos para auditoria incluindo unidade_crbm e cidade
    $dados_novos = [
        'id' => $id,
        'nome' => $nome,
        'unidade_crbm' => $unidade_crbm,
        'endereco' => $endereco,
        'telefone' => $telefone,
        'coordenador' => $coordenador,
        'cidade' => $cidade
    ];
    
    $success = $stmt->execute();
    $affected = $stmt->affected_rows;
    
    if ($success) {
        if ($affected > 0) {
            // AUDITORIA: Registrar a edição
            $audit->log('EDITAR_UNIDADE', 'unidade', $id, [
                'dados_anteriores' => $dados_anteriores,
                'dados_novos' => $dados_novos
            ]);
            
            echo json_encode(["status" => "sucesso", "mensagem" => "Unidade editada com sucesso!"]);
        } else {
            echo json_encode(["status" => "alerta", "mensagem" => "Nenhuma alteração foi feita. Os dados podem ser idênticos ou o ID da unidade pode estar incorreto."]);
        }
    } else {
        echo json_encode(["status" => "erro", "mensagem" => "Erro ao atualizar: " . $stmt->error]);
    }
} catch (Exception $e) {
    echo json_encode(["status" => "erro", "mensagem" => "Exceção: " . $e->getMessage()]);
}
?>