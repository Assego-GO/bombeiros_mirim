<?php
// Configurações básicas
header("Content-Type: application/json");
ini_set('display_errors', 0); // Não exibe erros no navegador
error_reporting(E_ALL);

// AUDITORIA: Adicione essas 3 linhas
session_start();
require_once "auditoria.php";

// Log para depuração
$logFile = 'debug_editar_turma.log';
file_put_contents($logFile, date('Y-m-d H:i:s') . " - Iniciando processamento\n", FILE_APPEND);

try {
    // Incluir conexão
    include "conexao.php";
    
    // Inicializar auditoria após conexão
    $audit = new Auditoria($conn);
    
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
    
    // AUDITORIA: Buscar dados anteriores ANTES da edição
    $stmt_anterior = $conn->prepare("SELECT * FROM turma WHERE id = ?");
    $stmt_anterior->bind_param("i", $id);
    $stmt_anterior->execute();
    $result_anterior = $stmt_anterior->get_result();
    $dados_anteriores = $result_anterior->fetch_assoc();
    
    if (!$dados_anteriores) {
        throw new Exception("Turma não encontrada com ID: $id");
    }
    
    // Registrar os valores no log
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Valores: id=$id, nome=$nome_turma, unidade=$id_unidade, professor=$id_professor\n", FILE_APPEND);
    
    // Atualizar turma usando prepared statement (SEGURO)
    $sql = "UPDATE turma SET 
            nome_turma = ?, 
            id_unidade = ?, 
            id_professor = ?, 
            capacidade = ?, 
            status = ?, 
            dias_aula = ?, 
            horario_inicio = ?, 
            horario_fim = ? 
            WHERE id = ?";
    
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - SQL: $sql\n", FILE_APPEND);
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Erro ao preparar consulta: " . $conn->error);
    }
    
    // Bind dos parâmetros
    $stmt->bind_param(
        "siisssssi",
        $nome_turma,      // s - string
        $id_unidade,      // i - integer
        $id_professor,    // i - integer
        $capacidade,      // i - integer
        $status,          // s - string
        $dias_aula,       // s - string
        $horario_inicio,  // s - string
        $horario_fim,     // s - string
        $id               // i - integer
    );
    
    // Executar a query
    $result = $stmt->execute();
    
    if (!$result) {
        throw new Exception("Erro ao executar SQL: " . $stmt->error);
    }
    
    // Verificar se a atualização teve efeito
    if ($stmt->affected_rows > 0) {
        // AUDITORIA: Preparar dados novos
        $dados_novos = [
            'id' => $id,
            'nome_turma' => $nome_turma,
            'id_unidade' => $id_unidade,
            'id_professor' => $id_professor,
            'capacidade' => $capacidade,
            'status' => $status,
            'dias_aula' => $dias_aula,
            'horario_inicio' => $horario_inicio,
            'horario_fim' => $horario_fim
        ];
        
        // AUDITORIA: Registrar a edição
        $audit->log('EDITAR_TURMA', 'turma', $id, [
            'dados_anteriores' => $dados_anteriores,
            'dados_novos' => $dados_novos
        ]);
        
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