<?php
// buscar_atividades.php - API para buscar atividades do aluno logado
session_start();
require_once 'conexao.php';

// Definir o tipo de resposta como JSON
header('Content-Type: application/json');

try {
    // Verificar se o aluno está logado
    if (!isset($_SESSION['aluno_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Usuário não está logado'
        ]);
        exit();
    }

    $aluno_id = $_SESSION['aluno_id'];

    // Buscar a turma do aluno através da tabela matriculas
    $stmt_turma = $pdo->prepare("
        SELECT 
            t.id as turma_id,
            t.nome_turma,
            COALESCE(u.nome, 'Não informado') as nome_unidade
        FROM matriculas m
        INNER JOIN turma t ON t.id = m.turma
        LEFT JOIN unidade u ON t.id_unidade = u.id
        WHERE m.aluno_id = ? AND m.status = 'ativo'
        LIMIT 1
    ");
    
    $stmt_turma->execute([$aluno_id]);
    $turma = $stmt_turma->fetch(PDO::FETCH_ASSOC);

    if (!$turma) {
        echo json_encode([
            'success' => false,
            'message' => 'Aluno não possui matrícula ativa em nenhuma turma'
        ]);
        exit();
    }

    // Buscar as atividades da turma do aluno
    $stmt_atividades = $pdo->prepare("
        SELECT 
            at.id,
            at.nome_atividade,
            at.data_atividade,
            at.hora_inicio,
            at.hora_termino,
            at.local_atividade,
            at.instrutor_responsavel,
            at.objetivo_atividade,
            at.conteudo_abordado,
            COALESCE(p.nome, 'Não informado') as nome_professor
        FROM atividades at
        LEFT JOIN professor p ON at.professor_id = p.id
        WHERE at.turma_id = ?
        ORDER BY at.data_atividade DESC, at.hora_inicio DESC
    ");
    
    $stmt_atividades->execute([$turma['turma_id']]);
    $atividades = $stmt_atividades->fetchAll(PDO::FETCH_ASSOC);

    // Para cada atividade, buscar a participação do aluno
    foreach ($atividades as &$atividade) {
        $stmt_participacao = $pdo->prepare("
            SELECT 
                presenca,
                desempenho_nota,
                desempenho_conceito,
                comportamento,
                habilidades_desenvolvidas,
                observacoes
            FROM atividade_participacao
            WHERE atividade_id = ? AND aluno_id = ?
        ");
        
        $stmt_participacao->execute([$atividade['id'], $aluno_id]);
        $participacao = $stmt_participacao->fetch(PDO::FETCH_ASSOC);
        
        // Adicionar informações de participação à atividade
        $atividade['participacao'] = $participacao ?: null;
    }

    // Retornar os dados
    echo json_encode([
        'success' => true,
        'turma' => $turma,
        'atividades' => $atividades
    ]);

} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao buscar atividades: ' . $e->getMessage()
    ]);
} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno: ' . $e->getMessage()
    ]);
}
?>