<?php
/**
 * API para consultar o ranking dos alunos
 * Usa MySQLi (compatível com seu sistema de conexão)
 */

session_start();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Verificação de admin
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

require_once "conexao.php";

try {
    // Parâmetros da consulta
    $turma_id = isset($_GET['turma_id']) && !empty($_GET['turma_id']) ? intval($_GET['turma_id']) : null;
    $periodo = isset($_GET['periodo']) && !empty($_GET['periodo']) ? $_GET['periodo'] : null;
    
    // Query para buscar avaliações - CORRIGIDO: nome da coluna t.nome para t.nome_turma
    $sql_avaliacoes = "
        SELECT 
            a.aluno_id,
            a.turma_id,
            al.nome as aluno_nome,
            CONCAT(t.nome_turma, ' - ', u.nome) as turma_nome,
            
            -- Nota Física (média das 5 habilidades físicas, convertida para 0-10)
            ROUND(
                COALESCE(
                    (AVG(COALESCE(a.velocidade, 0)) + AVG(COALESCE(a.resistencia, 0)) + 
                     AVG(COALESCE(a.coordenacao, 0)) + AVG(COALESCE(a.agilidade, 0)) + 
                     AVG(COALESCE(a.forca, 0))) / 5 * 2, 0
                ), 2
            ) AS nota_fisica,
            
            -- Nota Comportamento (média dos 4 aspectos comportamentais, convertida para 0-10) 
            ROUND(
                COALESCE(
                    (AVG(COALESCE(a.participacao, 0)) + AVG(COALESCE(a.trabalho_equipe, 0)) + 
                     AVG(COALESCE(a.disciplina, 0)) + AVG(COALESCE(a.respeito_regras, 0))) / 4 * 2, 0
                ), 2
            ) AS nota_comportamento,
            
            COUNT(a.id) as total_avaliacoes
            
        FROM avaliacoes a
        LEFT JOIN alunos al ON a.aluno_id = al.id
        LEFT JOIN turma t ON a.turma_id = t.id
        LEFT JOIN unidade u ON t.id_unidade = u.id
        WHERE 1=1
    ";
    
    if ($turma_id) {
        $sql_avaliacoes .= " AND a.turma_id = " . intval($turma_id);
    }
    
    $sql_avaliacoes .= " GROUP BY a.aluno_id, a.turma_id, al.nome, t.nome_turma, u.nome";
    
    $result_avaliacoes = $conn->query($sql_avaliacoes);
    
    if (!$result_avaliacoes) {
        throw new Exception("Erro na consulta de avaliações: " . $conn->error);
    }
    
    $avaliacoes_base = [];
    while ($row = $result_avaliacoes->fetch_assoc()) {
        $avaliacoes_base[] = $row;
    }
    
    // Query para buscar dados das atividades
    $sql_atividades = "
        SELECT 
            ap.aluno_id,
            ROUND(AVG(
                CASE 
                    WHEN ap.desempenho_conceito = 'excelente' THEN 10
                    WHEN ap.desempenho_conceito = 'bom' THEN 8
                    WHEN ap.desempenho_conceito = 'regular' THEN 6
                    WHEN ap.desempenho_conceito = 'insuficiente' THEN 4
                    WHEN ap.desempenho_nota IS NOT NULL THEN ap.desempenho_nota
                    ELSE 5
                END
            ), 2) AS nota_atividades,
            
            ROUND(AVG(
                CASE 
                    WHEN ap.comportamento = 'excelente' THEN 10
                    WHEN ap.comportamento = 'bom' THEN 8
                    WHEN ap.comportamento = 'regular' THEN 6
                    WHEN ap.comportamento = 'precisa_melhorar' THEN 4
                    ELSE 7
                END
            ), 2) AS nota_comportamento_atividades,
            
            ROUND(
                (SUM(CASE WHEN ap.presenca = 'sim' THEN 1 ELSE 0 END) * 100.0 / COUNT(*)), 2
            ) AS taxa_presenca,
            
            COUNT(*) as total_atividades
            
        FROM atividade_participacao ap
        WHERE ap.presenca IN ('sim', 'falta_justificada')
        GROUP BY ap.aluno_id
    ";
    
    $result_atividades = $conn->query($sql_atividades);
    
    if (!$result_atividades) {
        throw new Exception("Erro na consulta de atividades: " . $conn->error);
    }
    
    // Criar array indexado por aluno_id
    $atividades_por_aluno = [];
    while ($row = $result_atividades->fetch_assoc()) {
        $atividades_por_aluno[$row['aluno_id']] = $row;
    }
    
    // Combinar dados e calcular ranking
    $ranking_final = [];
    
    foreach ($avaliacoes_base as $avaliacao) {
        $aluno_id = $avaliacao['aluno_id'];
        $atividade = isset($atividades_por_aluno[$aluno_id]) ? $atividades_por_aluno[$aluno_id] : null;
        
        $nota_fisica = floatval($avaliacao['nota_fisica'] ?? 0);
        $nota_comportamento = floatval($avaliacao['nota_comportamento'] ?? 0);
        $nota_atividades = floatval($atividade['nota_atividades'] ?? 5);
        $nota_comportamento_atividades = floatval($atividade['nota_comportamento_atividades'] ?? 7);
        $taxa_presenca = floatval($atividade['taxa_presenca'] ?? 80);
        
        // Calcular média final ponderada
        $media_final = round(
            ($nota_fisica * 0.25) +                    // 25% física
            ($nota_comportamento * 0.15) +             // 15% comportamento avaliações
            ($nota_atividades * 0.30) +                // 30% atividades
            ($nota_comportamento_atividades * 0.15) +  // 15% comportamento atividades
            (($taxa_presenca / 10) * 0.15),            // 15% presença (convertida para escala 0-10)
            2
        );
        
        $ranking_final[] = [
            'aluno_id' => intval($aluno_id),
            'turma_id' => intval($avaliacao['turma_id']),
            'aluno_nome' => $avaliacao['aluno_nome'] ?? 'Nome não informado',
            'turma_nome' => $avaliacao['turma_nome'] ?? 'Turma não informada',
            'nota_fisica' => $nota_fisica,
            'nota_comportamento' => $nota_comportamento,
            'nota_atividades' => $nota_atividades,
            'nota_comportamento_atividades' => $nota_comportamento_atividades,
            'taxa_presenca' => $taxa_presenca,
            'media_final' => $media_final,
            'total_avaliacoes' => intval($avaliacao['total_avaliacoes'] ?? 0),
            'total_atividades' => intval($atividade['total_atividades'] ?? 0),
            'data_calculo' => date('Y-m-d')
        ];
    }
    
    // NOVO: Se não há avaliações, buscar alunos ativos sem ranking
    if (empty($ranking_final)) {
        $sql_alunos_sem_avaliacao = "
            SELECT 
                a.id as aluno_id,
                m.turma as turma_id,
                a.nome as aluno_nome,
                CONCAT(t.nome_turma, ' - ', u.nome) as turma_nome
            FROM alunos a
            INNER JOIN matriculas m ON a.id = m.aluno_id
            LEFT JOIN turma t ON m.turma = t.id
            LEFT JOIN unidade u ON t.id_unidade = u.id
            WHERE a.status = 'ativo' AND m.status = 'ativo'
        ";
        
        if ($turma_id) {
            $sql_alunos_sem_avaliacao .= " AND m.turma = " . intval($turma_id);
        }
        
        $result_alunos = $conn->query($sql_alunos_sem_avaliacao);
        
        if ($result_alunos) {
            while ($row = $result_alunos->fetch_assoc()) {
                $ranking_final[] = [
                    'aluno_id' => intval($row['aluno_id']),
                    'turma_id' => intval($row['turma_id']),
                    'aluno_nome' => $row['aluno_nome'],
                    'turma_nome' => $row['turma_nome'],
                    'nota_fisica' => 0.0,
                    'nota_comportamento' => 0.0,
                    'nota_atividades' => 0.0,
                    'nota_comportamento_atividades' => 0.0,
                    'taxa_presenca' => 0.0,
                    'media_final' => 0.0,
                    'total_avaliacoes' => 0,
                    'total_atividades' => 0,
                    'data_calculo' => date('Y-m-d')
                ];
            }
        }
    }
    
    // Ordenar por turma e depois por média final (decrescente)
    usort($ranking_final, function($a, $b) {
        if ($a['turma_id'] == $b['turma_id']) {
            return $b['media_final'] <=> $a['media_final']; // Maior média primeiro
        }
        return $a['turma_id'] <=> $b['turma_id']; // Ordem crescente por turma
    });
    
    // Adicionar posições por turma e premiação
    $turma_atual = null;
    $posicao_na_turma = 0;
    
    for ($i = 0; $i < count($ranking_final); $i++) {
        if ($ranking_final[$i]['turma_id'] != $turma_atual) {
            $turma_atual = $ranking_final[$i]['turma_id'];
            $posicao_na_turma = 1;
        } else {
            $posicao_na_turma++;
        }
        
        $ranking_final[$i]['posicao'] = $posicao_na_turma;
        $ranking_final[$i]['premiado'] = $posicao_na_turma <= 3 ? 'SIM' : 'NAO';
        
        switch ($posicao_na_turma) {
            case 1:
                $ranking_final[$i]['premio'] = '1º LUGAR - OURO';
                break;
            case 2:
                $ranking_final[$i]['premio'] = '2º LUGAR - PRATA';
                break;
            case 3:
                $ranking_final[$i]['premio'] = '3º LUGAR - BRONZE';
                break;
            default:
                $ranking_final[$i]['premio'] = 'PARTICIPANTE';
        }
    }
    
    // Calcular estatísticas resumidas
    $total_alunos = count($ranking_final);
    $total_premiados = count(array_filter($ranking_final, function($aluno) {
        return $aluno['premiado'] === 'SIM';
    }));
    
    $media_geral = $total_alunos > 0 ? round(array_sum(array_column($ranking_final, 'media_final')) / $total_alunos, 2) : 0;
    
    // Fechar conexão
    $conn->close();
    
    // Retornar resposta JSON
    echo json_encode([
        'success' => true,
        'ranking' => $ranking_final,
        'estatisticas' => [], // Será implementado em arquivo separado
        'resumo' => [
            'total_alunos' => $total_alunos,
            'total_premiados' => $total_premiados,
            'media_geral' => $media_geral,
            'filtros' => [
                'turma_id' => $turma_id,
                'periodo' => $periodo
            ]
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // Log do erro
    error_log("Erro no ranking: " . $e->getMessage());
    
    // Fechar conexão se estiver aberta
    if (isset($conn) && $conn->ping()) {
        $conn->close();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor',
        'error' => $e->getMessage(),
        'debug' => [
            'file' => basename(__FILE__),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]
    ], JSON_UNESCAPED_UNICODE);
}
?>