<?php
session_start();
header('Content-Type: application/json');

// Verificar se o usuário está logado como aluno
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'aluno') {
    echo json_encode(['success' => false, 'message' => 'Usuário não autorizado']);
    exit;
}

// Incluir arquivo de conexão
require_once 'conexao.php';

try {
    // Verificar se a conexão PDO foi estabelecida
    if (!isset($pdo)) {
        throw new Exception('Conexão com banco de dados não estabelecida');
    }
    
    $aluno_id = $_SESSION['usuario_id'];
    
    // Buscar a turma do aluno através da matrícula ativa
    $stmt = $pdo->prepare("
        SELECT m.turma, t.nome_turma, u.nome as nome_unidade
        FROM matriculas m 
        INNER JOIN turma t ON m.turma = t.id 
        INNER JOIN unidade u ON m.unidade = u.id 
        WHERE m.aluno_id = ? AND m.status = 'ativo'
        LIMIT 1
    ");
    $stmt->execute([$aluno_id]);
    $matricula = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$matricula) {
        echo json_encode(['success' => false, 'message' => 'Matrícula ativa não encontrada']);
        exit;
    }
    
    $turma_id = $matricula['turma'];
    
    // Buscar todas as atividades da turma
    $stmt = $pdo->prepare("
        SELECT 
            a.*,
            p.nome as nome_professor
        FROM atividades a
        LEFT JOIN professor p ON a.professor_id = p.id
        WHERE a.turma_id = ?
        ORDER BY a.data_atividade DESC
    ");
    $stmt->execute([$turma_id]);
    $atividades = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Para cada atividade, buscar a participação do aluno
    $atividades_com_participacao = [];
    
    foreach ($atividades as $atividade) {
        // Buscar participação do aluno nesta atividade
        $stmt = $pdo->prepare("
            SELECT *
            FROM atividade_participacao 
            WHERE atividade_id = ? AND aluno_id = ?
        ");
        $stmt->execute([$atividade['id'], $aluno_id]);
        $participacao = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Adicionar dados da participação à atividade
        $atividade['participacao'] = $participacao;
        $atividades_com_participacao[] = $atividade;
    }
    
    echo json_encode([
        'success' => true,
        'turma' => $matricula,
        'atividades' => $atividades_com_participacao
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro no servidor: ' . $e->getMessage()]);
}
?>