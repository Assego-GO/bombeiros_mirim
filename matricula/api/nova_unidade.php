<?php
header("Content-Type: application/json");
ini_set('display_errors', 1);
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

    // Campos obrigatórios
    $campos_obrigatorios = ['nome', 'endereco'];
    foreach ($campos_obrigatorios as $campo) {
        if (!isset($data[$campo]) || empty($data[$campo])) {
            echo json_encode(["status" => "erro", "mensagem" => "Campo '$campo' está faltando ou vazio."]);
            exit;
        }
    }

    // Preparar valores opcionais
    $telefone = $data['telefone'] ?? null;
    $coordenador = $data['coordenador'] ?? null;

    // Consulta SQL para tabela 'unidade'
    $sql = "INSERT INTO unidade (
        nome, 
        endereco, 
        telefone, 
        coordenador
    ) VALUES (?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        echo json_encode(["status" => "erro", "mensagem" => "Erro ao preparar consulta: " . $conn->error]);
        exit;
    }

    // Vincula os parâmetros
    $stmt->bind_param(
        "ssss",
        $data['nome'],         // string: nome da unidade
        $data['endereco'],     // string: endereço
        $telefone,             // string: telefone (pode ser null)
        $coordenador           // string: coordenador (pode ser null)
    );

    $result = $stmt->execute();
    
    if ($result) {
        $unidade_id = $conn->insert_id;
        
        // AUDITORIA: Registra a criação da unidade
        $audit_params = [
            'nome' => $data['nome'],
            'endereco' => $data['endereco'],
            'telefone' => $telefone,
            'coordenador' => $coordenador
        ];
        $audit->log('CRIAR_UNIDADE', 'unidade', $unidade_id, $audit_params);
        
        echo json_encode(["status" => "sucesso", "id" => $unidade_id, "mensagem" => "Unidade criada com sucesso!"]);
    } else {
        echo json_encode(["status" => "erro", "mensagem" => $stmt->error]);
    }

} catch (Exception $e) {
    echo json_encode(["status" => "erro", "mensagem" => "Exceção: " . $e->getMessage()]);
}
?>