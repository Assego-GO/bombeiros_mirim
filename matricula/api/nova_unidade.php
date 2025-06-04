<?php
header("Content-Type: application/json");
ini_set('display_errors', 0);
error_reporting(E_ALL);

include "conexao.php";

// Log para debug
file_put_contents('debug_nova_unidade.log', date('Y-m-d H:i:s') . " - Dados recebidos: " . file_get_contents("php://input") . "\n", FILE_APPEND);

try {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!$data) {
        echo json_encode(["status" => "erro", "mensagem" => "Dados inválidos."]);
        exit;
    }

    // Log dos dados decodificados
    file_put_contents('debug_nova_unidade.log', date('Y-m-d H:i:s') . " - Dados processados: " . print_r($data, true) . "\n", FILE_APPEND);

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
        file_put_contents('debug_nova_unidade.log', date('Y-m-d H:i:s') . " - Erro na preparação: " . $conn->error . "\n", FILE_APPEND);
        echo json_encode(["status" => "erro", "mensagem" => "Erro ao preparar consulta: " . $conn->error]);
        exit;
    }

    // Log da consulta SQL
    file_put_contents('debug_nova_unidade.log', date('Y-m-d H:i:s') . " - SQL: $sql\n", FILE_APPEND);

    // Vincula os parâmetros
    $stmt->bind_param(
        "ssss",
        $data['nome'],         // string: nome da unidade
        $data['endereco'],     // string: endereço
        $telefone,             // string: telefone (pode ser null)
        $coordenador           // string: coordenador (pode ser null)
    );

    // Log dos parâmetros
    $log_params = [
        'nome' => $data['nome'],
        'endereco' => $data['endereco'],
        'telefone' => $telefone,
        'coordenador' => $coordenador
    ];
    file_put_contents('debug_nova_unidade.log', date('Y-m-d H:i:s') . " - Parâmetros: " . print_r($log_params, true) . "\n", FILE_APPEND);

    $result = $stmt->execute();
    
    // Log do resultado da execução
    file_put_contents('debug_nova_unidade.log', date('Y-m-d H:i:s') . " - Execução: " . ($result ? "Sucesso" : "Falha") . "\n", FILE_APPEND);
    
    if ($result) {
        echo json_encode(["status" => "sucesso", "id" => $conn->insert_id, "mensagem" => "Unidade criada com sucesso!"]);
    } else {
        file_put_contents('debug_nova_unidade.log', date('Y-m-d H:i:s') . " - Erro: " . $stmt->error . "\n", FILE_APPEND);
        echo json_encode(["status" => "erro", "mensagem" => $stmt->error]);
    }

} catch (Exception $e) {
    file_put_contents('debug_nova_unidade.log', date('Y-m-d H:i:s') . " - Exceção: " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode(["status" => "erro", "mensagem" => "Exceção: " . $e->getMessage()]);
}
?>