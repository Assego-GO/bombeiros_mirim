<?php
// Configurações básicas
header("Content-Type: application/json");
ini_set('display_errors', 0); // Não exibe erros no navegador
error_reporting(E_ALL);

// Log para depuração
$logFile = 'debug_editar_turma.log';
file_put_contents($logFile, date('Y-m-d H:i:s') . " - Iniciando processamento\n", FILE_APPEND);

try {
    // Incluir conexão
    include "conexao.php";
    
    // Obter dados JSON
    $rawInput = file_get_contents('php://input');
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Dados recebidos: " . $rawInput . "\n", FILE_APPEND);
    
    // Decodificar JSON
    $data = json_decode($rawInput, true);
    if (!$data) {
        throw new Exception("JSON inválido: " . json_last_error_msg());
    }
    
    // Extrair campos do JSON
    $id = isset($data['id']) ? intval($data['id']) : 0;
    $nome_turma = isset($data['nome_turma']) ? $data['nome_turma'] : '';
    $id_unidade = isset($data['id_unidade']) ? intval($data['id_unidade']) : 0;
    $id_professor = isset($data['id_professor']) ? intval($data['id_professor']) : 0;
    $capacidade = isset($data['capacidade']) ? intval($data['capacidade']) : 0;
    $status = isset($data['status']) ? $data['status'] : 'Em Andamento';
    $dias_aula = isset($data['dias_aula']) ? $data['dias_aula'] : '';
    $horario_inicio = isset($data['horario_inicio']) ? $data['horario_inicio'] : '';
    $horario_fim = isset($data['horario_fim']) ? $data['horario_fim'] : '';
    
    // Validar campos obrigatórios
    if ($id <= 0 || empty($nome_turma)) {
        throw new Exception("Campos obrigatórios não fornecidos: ID e Nome da Turma");
    }
    
    // Registrar os valores no log
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Valores: id=$id, nome=$nome_turma, unidade=$id_unidade, professor=$id_professor\n", FILE_APPEND);
    
    // Atualizar turma - versão básica sem bind_param para minimizar possíveis erros
    $sql = "UPDATE turma SET 
            nome_turma = '$nome_turma', 
            id_unidade = $id_unidade, 
            id_professor = $id_professor, 
            capacidade = $capacidade, 
            status = '$status', 
            dias_aula = '$dias_aula', 
            horario_inicio = '$horario_inicio', 
            horario_fim = '$horario_fim' 
            WHERE id = $id";
    
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - SQL: $sql\n", FILE_APPEND);
    
    // Executar a query diretamente (não ideal para produção, mas bom para diagnóstico)
    $result = $conn->query($sql);
    
    if (!$result) {
        throw new Exception("Erro ao executar SQL: " . $conn->error);
    }
    
    // Verificar se a atualização teve efeito
    if ($conn->affected_rows > 0) {
        echo json_encode([
            "status" => "sucesso",
            "mensagem" => "Turma atualizada com sucesso!"
        ]);
    } else {
        echo json_encode([
            "status" => "alerta",
            "mensagem" => "Nenhuma alteração foi feita. Os dados podem ser idênticos ou o ID está incorreto."
        ]);
    }
    
} catch (Exception $e) {
    // Log do erro
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - ERRO: " . $e->getMessage() . "\n", FILE_APPEND);
    
    // Retorna erro em formato JSON
    echo json_encode([
        "status" => "erro",
        "mensagem" => "Erro: " . $e->getMessage()
    ]);
}
?>