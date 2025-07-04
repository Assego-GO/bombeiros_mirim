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

    // Campos obrigatórios de acordo com seu formulário HTML
    $campos_obrigatorios = ['nome_turma', 'unidade', 'professor_responsavel', 'data_inicio', 'data_fim'];
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
    $data_inicio = $data['data_inicio']; // Data de início do formulário
    $data_fim = $data['data_fim']; // Data de fim do formulário
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
        horario_fim, 
        data_inicio, 
        data_fim
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        echo json_encode(["status" => "erro", "mensagem" => "Erro ao preparar consulta: " . $conn->error]);
        exit;
    }

    // Vincula os parâmetros de acordo com os tipos corretos
    $stmt->bind_param(
        "siiisssssss",
        $data['nome_turma'],            // string: nome da turma
        $data['unidade'],               // int: id da unidade 
        $data['professor_responsavel'], // int: id do professor
        $capacidade,                    // int: capacidade
        $matriculados,                  // int: matriculados (0 inicialmente)
        $status,                        // string: status (Planejada ou Em Andamento)
        $dias_aula,                     // string: dias de aula
        $horario_inicio,                // string: horário de início
        $horario_fim,                 // string: horário de fim
        $data_inicio,
        $data_fim                       // string: data de início e fim
    );

    $result = $stmt->execute();
    
    if ($result) {
        $turma_id = $conn->insert_id;
        
        // AUDITORIA: Registra a criação da turma
        $audit_params = [
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
        $audit->log('CRIAR_TURMA', 'turma', $turma_id, $audit_params);
        
        echo json_encode(["status" => "sucesso", "id" => $turma_id]);
    } else {
        echo json_encode(["status" => "erro", "mensagem" => $stmt->error]);
    }

} catch (Exception $e) {
    echo json_encode(["status" => "erro", "mensagem" => "Exceção: " . $e->getMessage()]);
}
?>