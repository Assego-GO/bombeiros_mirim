<?php
// api/listar_ocorrencias.php
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
$response = ['success' => false, 'message' => '', 'ocorrencias' => []];

try {
    // Buscar ocorrências do professor com informações de aluno e turma - INCLUINDO CAMPOS DE FEEDBACK
    $stmt = $pdo->prepare("
        SELECT 
            o.*,
            a.nome as nome_aluno,
            t.nome_turma as nome_turma,
            o.feedback_admin,
            o.feedback_data,
            o.feedback_admin_id,
            o.feedback_admin_nome,
            CASE 
                WHEN o.feedback_admin IS NOT NULL AND o.feedback_admin != '' THEN 'com_feedback'
                ELSE 'sem_feedback'
            END as status_feedback
        FROM ocorrencias o
        JOIN alunos a ON o.aluno_id = a.id
        JOIN turma t ON o.turma_id = t.id
        WHERE o.professor_id = ?
        ORDER BY o.data_ocorrencia DESC, o.data_criacao DESC
    ");
    
    $stmt->execute([$professor_id]);
    $ocorrencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($ocorrencias) > 0) {
        $response['success'] = true;
        $response['ocorrencias'] = $ocorrencias;
        
        // Estatísticas adicionais
        $total_ocorrencias = count($ocorrencias);
        $com_feedback = count(array_filter($ocorrencias, function($o) {
            return !empty($o['feedback_admin']);
        }));
        
        $response['estatisticas'] = [
            'total' => $total_ocorrencias,
            'com_feedback' => $com_feedback,
            'sem_feedback' => $total_ocorrencias - $com_feedback
        ];
    } else {
        $response['success'] = true;
        $response['message'] = 'Nenhuma ocorrência encontrada';
        $response['ocorrencias'] = [];
    }
    
} catch (PDOException $e) {
    $response['message'] = 'Erro ao buscar ocorrências: ' . $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);