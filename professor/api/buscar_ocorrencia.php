<?php
// api/buscar_ocorrencia.php
session_start();

// Verificar se o usuário está logado (removendo verificação de tipo)
if (!isset($_SESSION['usuario_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

// Configuração do banco de dados - com fallback de caminhos
if (file_exists("../env_config.php")) {
    require "../env_config.php";
} else if (file_exists("../../env_config.php")) {
    require "../../env_config.php"; 
} else if (file_exists("env_config.php")) {
    require "env_config.php";
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Arquivo de configuração não encontrado']);
    exit;
}

try {
    $pdo = new PDO("mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8", $_ENV['DB_USER'], $_ENV['DB_PASS']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erro na conexão: ' . $e->getMessage()]);
    exit;
}

$professor_id = $_SESSION['usuario_id'];
$ocorrencia_id = $_GET['id'] ?? '';
$response = ['success' => false, 'message' => '', 'ocorrencia' => null];

if (empty($ocorrencia_id)) {
    $response['message'] = 'ID da ocorrência não informado';
} else {
    try {
        // Buscar ocorrência específica do professor - INCLUINDO CAMPOS DE FEEDBACK
        $stmt = $pdo->prepare("
            SELECT 
                o.*,
                a.nome as nome_aluno,
                t.nome_turma as nome_turma,
                u.nome as nome_unidade,
                o.feedback_admin,
                o.feedback_data,
                o.feedback_admin_id,
                o.feedback_admin_nome
            FROM ocorrencias o
            JOIN alunos a ON o.aluno_id = a.id
            JOIN turma t ON o.turma_id = t.id
            JOIN unidade u ON t.id_unidade = u.id
            WHERE o.id = ? AND o.professor_id = ?
        ");
        
        $stmt->execute([$ocorrencia_id, $professor_id]);
        $ocorrencia = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($ocorrencia) {
            $response['success'] = true;
            $response['ocorrencia'] = $ocorrencia;
        } else {
            $response['message'] = 'Ocorrência não encontrada';
        }
        
    } catch (PDOException $e) {
        $response['message'] = 'Erro ao buscar ocorrência: ' . $e->getMessage();
    }
}

header('Content-Type: application/json');
echo json_encode($response);