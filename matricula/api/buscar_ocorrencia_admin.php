<?php
// api/buscar_ocorrencia_admin.php
session_start();

// Verificar se o usuário está logado e é administrador
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['usuario_tipo']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Acesso negado. Apenas administradores podem acessar.']);
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

$ocorrencia_id = $_GET['id'] ?? '';
$response = ['success' => false, 'message' => '', 'ocorrencia' => null];

if (empty($ocorrencia_id)) {
    $response['message'] = 'ID da ocorrência não informado';
} else {
    try {
        // Buscar ocorrência com todas as informações (administrador pode ver todas)
        $stmt = $pdo->prepare("
            SELECT 
                o.*,
                a.nome as nome_aluno,
                a.numero_matricula,
                a.cpf as cpf_aluno,
                t.nome_turma as nome_turma,
                u.nome as nome_unidade,
                p.nome as nome_professor,
                p.email as email_professor,
                p.telefone as telefone_professor,
                o.feedback_admin,
                o.feedback_data,
                o.feedback_admin_id,
                o.feedback_admin_nome
            FROM ocorrencias o
            JOIN alunos a ON o.aluno_id = a.id
            JOIN turma t ON o.turma_id = t.id
            JOIN unidade u ON t.id_unidade = u.id
            JOIN professor p ON o.professor_id = p.id
            WHERE o.id = ?
        ");
        
        $stmt->execute([$ocorrencia_id]);
        $ocorrencia = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($ocorrencia) {
            // Buscar informações adicionais dos responsáveis pelo aluno
            $stmt_resp = $pdo->prepare("
                SELECT r.nome, r.parentesco, r.telefone, r.email
                FROM responsaveis r
                JOIN aluno_responsavel ar ON r.id = ar.responsavel_id
                WHERE ar.aluno_id = ?
            ");
            $stmt_resp->execute([$ocorrencia['aluno_id']]);
            $responsaveis = $stmt_resp->fetchAll(PDO::FETCH_ASSOC);
            
            $ocorrencia['responsaveis'] = $responsaveis;
            
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