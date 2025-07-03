<?php
// api/listar_ocorrencias_admin.php
session_start();

// Verificar se o usuário está logado e é administrador
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['usuario_tipo']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Acesso negado. Apenas administradores podem ver esta listagem.']);
    exit;
}

// Configuração do banco de dados
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

$response = ['success' => false, 'message' => '', 'ocorrencias' => []];

// Parâmetros de filtro opcionais
$filtros = [];
$params = [];
$where_conditions = [];

// Filtro por professor
if (!empty($_GET['professor_id'])) {
    $where_conditions[] = "o.professor_id = ?";
    $params[] = $_GET['professor_id'];
}

// Filtro por turma
if (!empty($_GET['turma_id'])) {
    $where_conditions[] = "o.turma_id = ?";
    $params[] = $_GET['turma_id'];
}

// Filtro por unidade
if (!empty($_GET['unidade_id'])) {
    $where_conditions[] = "t.id_unidade = ?";
    $params[] = $_GET['unidade_id'];
}

// Filtro por status de feedback
if (!empty($_GET['status_feedback'])) {
    if ($_GET['status_feedback'] === 'com_feedback') {
        $where_conditions[] = "o.feedback_admin IS NOT NULL AND o.feedback_admin != ''";
    } elseif ($_GET['status_feedback'] === 'sem_feedback') {
        $where_conditions[] = "(o.feedback_admin IS NULL OR o.feedback_admin = '')";
    }
}

// Filtro por período
if (!empty($_GET['data_inicio'])) {
    $where_conditions[] = "o.data_ocorrencia >= ?";
    $params[] = $_GET['data_inicio'];
}

if (!empty($_GET['data_fim'])) {
    $where_conditions[] = "o.data_ocorrencia <= ?";
    $params[] = $_GET['data_fim'];
}

// Construir WHERE clause
$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

try {
    // Buscar todas as ocorrências com informações completas
    $sql = "
        SELECT 
            o.*,
            a.nome as nome_aluno,
            a.numero_matricula,
            t.nome_turma as nome_turma,
            u.nome as nome_unidade,
            p.nome as nome_professor,
            p.email as email_professor,
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
        JOIN unidade u ON t.id_unidade = u.id
        JOIN professor p ON o.professor_id = p.id
        $where_clause
        ORDER BY o.data_ocorrencia DESC, o.data_criacao DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $ocorrencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($ocorrencias) > 0) {
        $response['success'] = true;
        $response['ocorrencias'] = $ocorrencias;
        
        // Estatísticas
        $total_ocorrencias = count($ocorrencias);
        $com_feedback = count(array_filter($ocorrencias, function($o) {
            return !empty($o['feedback_admin']);
        }));
        $pendentes_feedback = $total_ocorrencias - $com_feedback;
        
        // Estatísticas por unidade
        $por_unidade = [];
        foreach ($ocorrencias as $ocorrencia) {
            $unidade = $ocorrencia['nome_unidade'];
            if (!isset($por_unidade[$unidade])) {
                $por_unidade[$unidade] = ['total' => 0, 'com_feedback' => 0, 'sem_feedback' => 0];
            }
            $por_unidade[$unidade]['total']++;
            if (!empty($ocorrencia['feedback_admin'])) {
                $por_unidade[$unidade]['com_feedback']++;
            } else {
                $por_unidade[$unidade]['sem_feedback']++;
            }
        }
        
        $response['estatisticas'] = [
            'total' => $total_ocorrencias,
            'com_feedback' => $com_feedback,
            'sem_feedback' => $pendentes_feedback,
            'percentual_feedback' => $total_ocorrencias > 0 ? round(($com_feedback / $total_ocorrencias) * 100, 1) : 0,
            'por_unidade' => $por_unidade
        ];
    } else {
        $response['success'] = true;
        $response['message'] = 'Nenhuma ocorrência encontrada';
        $response['ocorrencias'] = [];
        $response['estatisticas'] = [
            'total' => 0,
            'com_feedback' => 0,
            'sem_feedback' => 0,
            'percentual_feedback' => 0,
            'por_unidade' => []
        ];
    }
    
} catch (PDOException $e) {
    $response['message'] = 'Erro ao buscar ocorrências: ' . $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);