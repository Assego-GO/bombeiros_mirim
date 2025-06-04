<?php
header("Content-Type: application/json");
ini_set('display_errors', 0);
error_reporting(E_ALL);

include "conexao.php";

// Log para debug
file_put_contents('debug_nova_turma.log', date('Y-m-d H:i:s') . " - Dados recebidos: " . file_get_contents("php://input") . "\n", FILE_APPEND);

try {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!$data) {
        echo json_encode(["status" => "erro", "mensagem" => "Dados inválidos."]);
        exit;
    }

    // Log dos dados decodificados
    file_put_contents('debug_nova_turma.log', date('Y-m-d H:i:s') . " - Dados processados: " . print_r($data, true) . "\n", FILE_APPEND);

    // Campos obrigatórios de acordo com seu formulário HTML
    $campos_obrigatorios = ['nome_turma', 'unidade', 'professor_responsavel', 'data_inicio'];
    foreach ($campos_obrigatorios as $campo) {
        if (!isset($data[$campo]) || empty($data[$campo])) {
            echo json_encode(["status" => "erro", "mensagem" => "Campo '$campo' está faltando ou vazio."]);
            exit;
        }
    }

    // Valores padrão para campos que não estão no seu formulário
    $capacidade = 25; // Valor padrão
    $matriculados = 0; // Começa com zero matriculados
    $status = isset($data['status']) && $data['status'] == 1 ? 'Em Andamento' : 'Planejada';
    $dias_aula = "Não definido"; // Valor padrão
    $horario_inicio = "08:00:00"; // Valor padrão
    $horario_fim = "10:00:00"; // Valor padrão

    // Consulta SQL para tabela 'turma'
    $sql = "INSERT INTO turma (
        nome_turma, 
        id_unidade, 
        id_professor, 
        capacidade, 
        matriculados, 
        status, 
        dias_aula, 
        horario_inicio, 
        horario_fim
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        echo json_encode(["status" => "erro", "mensagem" => "Erro ao preparar consulta: " . $conn->error]);
        exit;
    }

    // Log da consulta SQL
    file_put_contents('debug_nova_turma.log', date('Y-m-d H:i:s') . " - SQL: $sql\n", FILE_APPEND);

    // Vincula os parâmetros de acordo com os tipos corretos
    $stmt->bind_param(
        "siiisssss",
        $data['nome_turma'],            // string: nome da turma
        $data['unidade'],               // int: id da unidade 
        $data['professor_responsavel'], // int: id do professor
        $capacidade,                    // int: capacidade
        $matriculados,                  // int: matriculados (0 inicialmente)
        $status,                        // string: status (Planejada ou Em Andamento)
        $dias_aula,                     // string: dias de aula
        $horario_inicio,                // string: horário de início
        $horario_fim                    // string: horário de fim
    );

    // Log dos parâmetros
    $log_params = [
        'nome_turma' => $data['nome_turma'],
        'unidade' => $data['unidade'],
        'professor_responsavel' => $data['professor_responsavel'],
        'capacidade' => $capacidade,
        'matriculados' => $matriculados,
        'status' => $status,
        'dias_aula' => $dias_aula,
        'horario_inicio' => $horario_inicio,
        'horario_fim' => $horario_fim
    ];
    file_put_contents('debug_nova_turma.log', date('Y-m-d H:i:s') . " - Parâmetros: " . print_r($log_params, true) . "\n", FILE_APPEND);

    $result = $stmt->execute();
    
    // Log do resultado da execução
    file_put_contents('debug_nova_turma.log', date('Y-m-d H:i:s') . " - Execução: " . ($result ? "Sucesso" : "Falha") . "\n", FILE_APPEND);
    
    if ($result) {
        echo json_encode(["status" => "sucesso", "id" => $conn->insert_id]);
    } else {
        file_put_contents('debug_nova_turma.log', date('Y-m-d H:i:s') . " - Erro: " . $stmt->error . "\n", FILE_APPEND);
        echo json_encode(["status" => "erro", "mensagem" => $stmt->error]);
    }

} catch (Exception $e) {
    file_put_contents('debug_nova_turma.log', date('Y-m-d H:i:s') . " - Exceção: " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode(["status" => "erro", "mensagem" => "Exceção: " . $e->getMessage()]);
}
?>