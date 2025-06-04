<?php
// Configuração para exibir erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Cabeçalhos para JSON e CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Para desenvolvimento
header('Access-Control-Allow-Methods: GET');
try {
    // Incluir conexão com o banco de dados
    require_once "conexao.php";
    // Verificar se a conexão foi bem-sucedida
    if ($conn->connect_error) {
        throw new Exception("Falha na conexão com o banco de dados: " . $conn->connect_error);
    }
    // Verificar se o ID foi fornecido
    if (!isset($_GET['id'])) {
        throw new Exception("ID da turma não fornecido");
    }
    $id = intval($_GET['id']);
    // Preparar consulta SQL com JOIN para unidade e professor
    $sql = "SELECT t.id, t.nome_turma, t.id_unidade, t.id_professor, 
                 t.capacidade, t.matriculados, t.status, t.dias_aula,
                 t.horario_inicio, t.horario_fim, t.data_criacao, t.ultima_atualizacao,
                 u.nome AS unidade_nome, 
                 p.nome AS professor_nome
            FROM turma t
            LEFT JOIN unidade u ON t.id_unidade = u.id
            LEFT JOIN professor p ON t.id_professor = p.id
            WHERE t.id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Erro na preparação da consulta: " . $conn->error);
    }
    $stmt->bind_param("i", $id);
    // Executar a consulta
    if (!$stmt->execute()) {
        throw new Exception("Erro ao buscar a turma: " . $stmt->error);
    }
    $resultado = $stmt->get_result();
    if ($resultado->num_rows === 0) {
        echo json_encode([
            'status' => 'info',
            'mensagem' => 'Nenhuma turma encontrada com este ID'
        ]);
        exit;
    }
    $turma = $resultado->fetch_assoc();
    // Formatar datas para exibição mais amigável, se necessário
    if (isset($turma['data_criacao'])) {
        $data_criacao = new DateTime($turma['data_criacao']);
        $turma['data_criacao'] = $data_criacao->format('Y-m-d H:i:s');
    }
    if (isset($turma['ultima_atualizacao'])) {
        $ultima_atualizacao = new DateTime($turma['ultima_atualizacao']);
        $turma['ultima_atualizacao'] = $ultima_atualizacao->format('Y-m-d H:i:s');
    }
    // Retornar dados no formato JSON
    echo json_encode([
        'status' => 'sucesso',
        'data' => $turma
    ]);
} catch (Exception $e) {
    // Retornar mensagem de erro em formato JSON
    http_response_code(500);
    echo json_encode([
        'status' => 'erro',
        'mensagem' => $e->getMessage()
    ]);
} finally {
    // Fechar statement e conexão
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}
?>