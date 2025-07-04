<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('America/Sao_Paulo');

// Verificação de autenticação
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Usuário não autenticado']);
    exit;
}

// Incluir arquivo de conexão
require_once 'conexao.php';

try {
    $type = $_GET['type'] ?? '';

    switch ($type) {
        case 'unidades':
            $sql = "SELECT id, nome, endereco, telefone, coordenador FROM unidade ORDER BY nome";
            $result = $conn->query($sql);
            
            if (!$result) {
                throw new Exception("Erro na query de unidades: " . $conn->error);
            }
            
            $data = $result->fetch_all(MYSQLI_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $data,
                'total' => count($data)
            ]);
            break;

        case 'turmas': // ✅ Corrigido para 'turmas' (plural)
            $sql = "
                SELECT 
                    t.id, 
                    t.nome_turma, 
                    t.horario_inicio,
                    t.horario_fim,
                    t.capacidade,
                    t.id_unidade,
                    u.nome as unidade_nome 
                FROM turma t 
                LEFT JOIN unidade u ON t.id_unidade = u.id
                ORDER BY u.nome, t.nome_turma
            ";
            $result = $conn->query($sql);
            
            if (!$result) {
                throw new Exception("Erro na query de turmas: " . $conn->error);
            }
            
            $data = $result->fetch_all(MYSQLI_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $data,
                'total' => count($data)
            ]);
            break;

        case 'professores':
            $sql = "
                SELECT 
                    id, 
                    nome, 
                    email, 
                    telefone,
                    especialidade,
                    status
                FROM professor 
                WHERE status = 'ativo'
                ORDER BY nome
            ";
            $result = $conn->query($sql);
            
            if (!$result) {
                throw new Exception("Erro na query de professores: " . $conn->error);
            }
            
            $data = $result->fetch_all(MYSQLI_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $data,
                'total' => count($data)
            ]);
            break;

        case 'alunos_basico': // ✅ Novo endpoint para lista básica de alunos
            $sql = "
                SELECT 
                    a.id, 
                    a.nome, 
                    a.numero_matricula,
                    a.status,
                    u.nome as unidade_nome,
                    t.nome_turma
                FROM alunos a
                LEFT JOIN matriculas m ON a.id = m.aluno_id
                LEFT JOIN unidade u ON m.unidade = u.id
                LEFT JOIN turma t ON m.turma = t.id
                WHERE a.status != 'excluido'
                ORDER BY a.nome
            ";
            $result = $conn->query($sql);
            
            if (!$result) {
                throw new Exception("Erro na query de alunos: " . $conn->error);
            }
            
            $data = $result->fetch_all(MYSQLI_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $data,
                'total' => count($data)
            ]);
            break;

        case 'status_options': // ✅ Novo endpoint para opções de status
            $data = [
                'status_aluno' => [
                    ['value' => 'ativo', 'label' => 'Ativo'],
                    ['value' => 'inativo', 'label' => 'Inativo'],
                    ['value' => 'pendente', 'label' => 'Pendente'],
                    ['value' => 'transferido', 'label' => 'Transferido']
                ],
                'status_programa' => [
                    ['value' => 'novato', 'label' => 'Novato'],
                    ['value' => 'monitor', 'label' => 'Monitor'],
                    ['value' => 'aspirante', 'label' => 'Aspirante']
                ],
                'generos' => [
                    ['value' => 'masculino', 'label' => 'Masculino'],
                    ['value' => 'feminino', 'label' => 'Feminino'],
                    ['value' => 'outro', 'label' => 'Outro']
                ]
            ];
            
            echo json_encode([
                'success' => true,
                'data' => $data
            ]);
            break;

        case 'dashboard_stats': // ✅ Novo endpoint para estatísticas do dashboard
            $stats = [];
            
            // Total de alunos ativos
            $sql = "SELECT COUNT(*) as total FROM alunos WHERE status = 'ativo'";
            $result = $conn->query($sql);
            $stats['alunos_ativos'] = $result ? $result->fetch_assoc()['total'] : 0;
            
            // Total de turmas
            $sql = "SELECT COUNT(*) as total FROM turma";
            $result = $conn->query($sql);
            $stats['total_turmas'] = $result ? $result->fetch_assoc()['total'] : 0;
            
            // Total de unidades
            $sql = "SELECT COUNT(*) as total FROM unidade";
            $result = $conn->query($sql);
            $stats['total_unidades'] = $result ? $result->fetch_assoc()['total'] : 0;
            
            // Total de professores ativos
            $sql = "SELECT COUNT(*) as total FROM professor WHERE status = 'ativo'";
            $result = $conn->query($sql);
            $stats['professores_ativos'] = $result ? $result->fetch_assoc()['total'] : 0;
            
            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);
            break;

        default:
            throw new Exception('Tipo de dados não encontrado: ' . $type . '. Tipos disponíveis: unidades, turmas, professores, alunos_basico, status_options, dashboard_stats');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug_info' => [
            'type_requested' => $_GET['type'] ?? 'nenhum',
            'mysql_error' => isset($conn) ? $conn->error : 'Conexão não estabelecida'
        ]
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>