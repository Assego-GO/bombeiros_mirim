<?php
// buscar_comunicados.php - API para buscar comunicados para o aluno logado
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

    // Verificar se é uma requisição para marcar como lido
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (isset($input['action']) && $input['action'] === 'marcar_lido' && isset($input['comunicado_id'])) {
            $comunicado_id = $input['comunicado_id'];
            
            // Inserir ou atualizar o registro de leitura
            $stmt_marcar = $pdo->prepare("
                INSERT INTO comunicados_leitura (aluno_id, comunicado_id, data_leitura) 
                VALUES (?, ?, NOW()) 
                ON DUPLICATE KEY UPDATE data_leitura = NOW()
            ");
            
            $stmt_marcar->execute([$aluno_id, $comunicado_id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Comunicado marcado como lido'
            ]);
            exit();
        }
        
        if (isset($input['action']) && $input['action'] === 'marcar_todos_lidos') {
            // Marcar todos os comunicados ativos como lidos
            $stmt_todos = $pdo->prepare("
                INSERT INTO comunicados_leitura (aluno_id, comunicado_id, data_leitura)
                SELECT ?, c.id, NOW()
                FROM comunicados c
                WHERE c.status = 'ativo'
                ON DUPLICATE KEY UPDATE data_leitura = NOW()
            ");
            
            $stmt_todos->execute([$aluno_id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Todos os comunicados marcados como lidos'
            ]);
            exit();
        }
    }

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

    // Buscar os comunicados ativos com informação de leitura
    $stmt_comunicados = $pdo->prepare("
        SELECT 
            c.id,
            c.titulo,
            c.conteudo,
            c.data_criacao,
            c.data_atualizacao,
            c.autor_nome,
            c.status,
            cl.data_leitura,
            CASE WHEN cl.data_leitura IS NULL THEN 0 ELSE 1 END as lido
        FROM comunicados c
        LEFT JOIN comunicados_leitura cl ON (c.id = cl.comunicado_id AND cl.aluno_id = ?)
        WHERE c.status = 'ativo'
        ORDER BY c.data_criacao DESC
    ");
    
    $stmt_comunicados->execute([$aluno_id]);
    $comunicados = $stmt_comunicados->fetchAll(PDO::FETCH_ASSOC);

    // Contar comunicados não lidos
    $nao_lidos = 0;

    // Formatar as datas para exibição
    foreach ($comunicados as &$comunicado) {
        // Formatar data de criação
        if ($comunicado['data_criacao']) {
            $data_criacao = new DateTime($comunicado['data_criacao']);
            $comunicado['data_criacao_formatada'] = $data_criacao->format('d/m/Y H:i');
        }
        
        // Formatar data de atualização se existir
        if ($comunicado['data_atualizacao']) {
            $data_atualizacao = new DateTime($comunicado['data_atualizacao']);
            $comunicado['data_atualizacao_formatada'] = $data_atualizacao->format('d/m/Y H:i');
        }

        // Formatar data de leitura se existir
        if ($comunicado['data_leitura']) {
            $data_leitura = new DateTime($comunicado['data_leitura']);
            $comunicado['data_leitura_formatada'] = $data_leitura->format('d/m/Y H:i');
        }

        // Contar não lidos
        if (!$comunicado['lido']) {
            $nao_lidos++;
        }

        // Truncar conteúdo para preview (primeiros 150 caracteres)
        $comunicado['conteudo_preview'] = strlen($comunicado['conteudo']) > 150 
            ? substr($comunicado['conteudo'], 0, 150) . '...' 
            : $comunicado['conteudo'];
    }

    // Retornar os dados
    echo json_encode([
        'success' => true,
        'turma' => $turma,
        'comunicados' => $comunicados,
        'total_comunicados' => count($comunicados),
        'nao_lidos' => $nao_lidos
    ]);

} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao buscar comunicados: ' . $e->getMessage()
    ]);
} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno: ' . $e->getMessage()
    ]);
}
?>