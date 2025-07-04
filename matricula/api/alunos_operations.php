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

// Incluir arquivo de conexão (baseado no teste, está na pasta atual)
require_once 'conexao.php';

function buscarAlunos($conn, $filtros = []) {
    $where_conditions = ["a.status != 'excluido'"];
    $params = [];
    $types = '';

    // Aplicar filtros
    if (!empty($filtros['nome'])) {
        $where_conditions[] = 'a.nome LIKE ?';
        $params[] = '%' . $filtros['nome'] . '%';
        $types .= 's';
    }

    if (!empty($filtros['cpf'])) {
        $where_conditions[] = 'a.cpf LIKE ?';
        $params[] = '%' . $filtros['cpf'] . '%';
        $types .= 's';
    }

    if (!empty($filtros['numero_matricula'])) {
        $where_conditions[] = 'a.numero_matricula LIKE ?';
        $params[] = '%' . $filtros['numero_matricula'] . '%';
        $types .= 's';
    }

    if (!empty($filtros['escola'])) {
        $where_conditions[] = 'a.escola LIKE ?';
        $params[] = '%' . $filtros['escola'] . '%';
        $types .= 's';
    }

    if (!empty($filtros['status'])) {
        $where_conditions[] = 'a.status = ?';
        $params[] = $filtros['status'];
        $types .= 's';
    }

    if (!empty($filtros['unidade'])) {
        $where_conditions[] = 'm.unidade = ?';
        $params[] = $filtros['unidade'];
        $types .= 's';
    }

    if (!empty($filtros['turma'])) {
        $where_conditions[] = 'm.turma = ?';
        $params[] = $filtros['turma'];
        $types .= 's';
    }

    if (!empty($filtros['genero'])) {
        $where_conditions[] = 'a.genero = ?';
        $params[] = $filtros['genero'];
        $types .= 's';
    }

    if (!empty($filtros['status_programa'])) {
        $where_conditions[] = 'm.status_programa = ?';
        $params[] = $filtros['status_programa'];
        $types .= 's';
    }

    if (!empty($filtros['data_inicial'])) {
        $where_conditions[] = 'a.data_matricula >= ?';
        $params[] = $filtros['data_inicial'];
        $types .= 's';
    }

    if (!empty($filtros['data_final'])) {
        $where_conditions[] = 'a.data_matricula <= ?';
        $params[] = $filtros['data_final'] . ' 23:59:59';
        $types .= 's';
    }

    $where_clause = implode(' AND ', $where_conditions);

    // ✅ QUERY CORRIGIDA - Removido GROUP BY problemático
    $sql = "
        SELECT 
            a.id,
            a.nome,
            a.genero,
            a.data_nascimento,
            a.cpf,
            a.rg,
            a.numero_matricula,
            a.data_matricula,
            a.escola,
            a.serie,
            a.status,
            a.foto,
            a.telefone_escola,
            a.diretor_escola,
            a.tipo_sanguineo,
            a.crianca_atipica,
            a.tem_alergias_condicoes,
            a.detalhes_alergias_condicoes,
            a.medicacao_continua,
            a.detalhes_medicacao,
            a.tamanho_camisa,
            a.tamanho_calca,
            a.tamanho_calcado,
            COALESCE(m.status_programa, 'novato') as status_programa,
            COALESCE(m.status, 'pendente') as status_matricula,
            u.nome as unidade_nome,
            t.nome_turma,
            e.logradouro,
            e.numero as endereco_numero,
            e.complemento,
            e.bairro,
            e.cidade,
            e.cep
        FROM alunos a
        LEFT JOIN matriculas m ON a.id = m.aluno_id
        LEFT JOIN unidade u ON m.unidade = u.id
        LEFT JOIN turma t ON m.turma = t.id
        LEFT JOIN enderecos e ON a.id = e.aluno_id
        WHERE $where_clause
        ORDER BY a.nome ASC
    ";

    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Erro ao preparar consulta: " . $conn->error);
    }
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    if (!$stmt->execute()) {
        throw new Exception("Erro ao executar consulta: " . $stmt->error);
    }

    $result = $stmt->get_result();
    
    $alunos = [];
    $responsaveis_cache = [];
    
    while ($row = $result->fetch_assoc()) {
        // Calcular idade
        if ($row['data_nascimento']) {
            $nascimento = new DateTime($row['data_nascimento']);
            $hoje = new DateTime();
            $row['idade'] = $hoje->diff($nascimento)->y;
        } else {
            $row['idade'] = null;
        }

        // Formatar data de nascimento
        if ($row['data_nascimento']) {
            $row['data_nascimento_formatada'] = date('d/m/Y', strtotime($row['data_nascimento']));
        }

        // Formatar data de matrícula
        if ($row['data_matricula']) {
            $row['data_matricula_formatada'] = date('d/m/Y H:i', strtotime($row['data_matricula']));
        }

        // Formatar endereço completo
        $endereco_parts = array_filter([
            $row['logradouro'],
            $row['endereco_numero'],
            $row['complemento'],
            $row['bairro'],
            $row['cidade']
        ]);
        $row['endereco_completo'] = implode(', ', $endereco_parts);

        // ✅ Buscar responsáveis separadamente para evitar problema de GROUP BY
        if (!isset($responsaveis_cache[$row['id']])) {
            $responsaveis_cache[$row['id']] = buscarResponsaveisAluno($conn, $row['id']);
        }
        
        $row['responsaveis'] = $responsaveis_cache[$row['id']]['responsaveis'];
        $row['telefones_responsaveis'] = $responsaveis_cache[$row['id']]['telefones'];

        $alunos[] = $row;
    }

    return $alunos;
}

// ✅ Nova função para buscar responsáveis separadamente
function buscarResponsaveisAluno($conn, $aluno_id) {
    $sql = "
        SELECT 
            r.nome,
            r.parentesco,
            r.telefone
        FROM responsaveis r
        INNER JOIN aluno_responsavel ar ON r.id = ar.responsavel_id
        WHERE ar.aluno_id = ?
        ORDER BY r.parentesco
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $aluno_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $responsaveis_list = [];
    $telefones_list = [];
    
    while ($row = $result->fetch_assoc()) {
        $responsaveis_list[] = $row['nome'] . ' (' . $row['parentesco'] . ')';
        $telefones_list[] = $row['telefone'] . ' - ' . $row['nome'];
    }
    
    return [
        'responsaveis' => implode(', ', $responsaveis_list),
        'telefones' => implode(' | ', $telefones_list)
    ];
}

function buscarDetalhesAluno($conn, $id) {
    $sql = "
        SELECT 
            a.*,
            COALESCE(m.status_programa, 'novato') as status_programa,
            COALESCE(m.status, 'pendente') as status_matricula,
            m.data_matricula as data_matricula_programa,
            u.nome as unidade_nome,
            u.endereco as endereco_unidade,
            u.telefone as telefone_unidade,
            u.coordenador,
            t.nome_turma,
            t.horario_inicio,
            t.horario_fim,
            t.capacidade as capacidade_turma,
            e.logradouro,
            e.numero as endereco_numero,
            e.complemento,
            e.bairro,
            e.cidade,
            e.cep
        FROM alunos a
        LEFT JOIN matriculas m ON a.id = m.aluno_id
        LEFT JOIN unidade u ON m.unidade = u.id
        LEFT JOIN turma t ON m.turma = t.id
        LEFT JOIN enderecos e ON a.id = e.aluno_id
        WHERE a.id = ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $aluno = $stmt->get_result()->fetch_assoc();

    if (!$aluno) {
        return null;
    }

    // Buscar responsáveis
    $sql_resp = "
        SELECT r.* 
        FROM responsaveis r
        INNER JOIN aluno_responsavel ar ON r.id = ar.responsavel_id
        WHERE ar.aluno_id = ?
        ORDER BY r.parentesco
    ";
    
    $stmt_resp = $conn->prepare($sql_resp);
    $stmt_resp->bind_param('i', $id);
    $stmt_resp->execute();
    $responsaveis = $stmt_resp->get_result()->fetch_all(MYSQLI_ASSOC);

    $aluno['responsaveis'] = $responsaveis;

    // Calcular idade
    if ($aluno['data_nascimento']) {
        $nascimento = new DateTime($aluno['data_nascimento']);
        $hoje = new DateTime();
        $aluno['idade'] = $hoje->diff($nascimento)->y;
    }

    return $aluno;
}

function buscarEstatisticas($conn) {
    $stats = [];

    // Total de alunos por status
    $sql = "SELECT status, COUNT(*) as total FROM alunos WHERE status != 'excluido' GROUP BY status";
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $stats['por_status'][$row['status']] = $row['total'];
        }
    }

    // Total de alunos por gênero
    $sql = "SELECT genero, COUNT(*) as total FROM alunos WHERE status != 'excluido' GROUP BY genero";
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $stats['por_genero'][$row['genero']] = $row['total'];
        }
    }

    // Total de alunos por unidade
    $sql = "
        SELECT u.nome, COUNT(a.id) as total 
        FROM alunos a
        INNER JOIN matriculas m ON a.id = m.aluno_id
        INNER JOIN unidade u ON m.unidade = u.id
        WHERE a.status != 'excluido'
        GROUP BY u.id, u.nome
        ORDER BY total DESC
    ";
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $stats['por_unidade'][] = $row;
        }
    }

    // Total geral
    $sql = "SELECT COUNT(*) as total FROM alunos WHERE status != 'excluido'";
    $result = $conn->query($sql);
    if ($result) {
        $stats['total_geral'] = $result->fetch_assoc()['total'];
    }

    return $stats;
}

// Processar requisição
try {
    $action = $_GET['action'] ?? $_POST['action'] ?? '';

    switch ($action) {
        case 'listar':
            $filtros = [];
            
            // Capturar filtros da requisição
            $campos_filtro = ['nome', 'cpf', 'numero_matricula', 'escola', 'status', 'unidade', 'turma', 'genero', 'status_programa', 'data_inicial', 'data_final'];
            
            foreach ($campos_filtro as $campo) {
                if (!empty($_GET[$campo])) {
                    $filtros[$campo] = trim($_GET[$campo]);
                }
            }

            $alunos = buscarAlunos($conn, $filtros);
            
            echo json_encode([
                'success' => true,
                'data' => $alunos,
                'total' => count($alunos),
                'filtros_aplicados' => $filtros
            ]);
            break;

        case 'detalhes':
            $id = intval($_GET['id'] ?? 0);
            
            if ($id <= 0) {
                throw new Exception('ID do aluno inválido');
            }

            $aluno = buscarDetalhesAluno($conn, $id);
            
            if (!$aluno) {
                throw new Exception('Aluno não encontrado');
            }

            echo json_encode([
                'success' => true,
                'data' => $aluno
            ]);
            break;

        case 'estatisticas':
            $stats = buscarEstatisticas($conn);
            
            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);
            break;

        case 'exportar':
            $filtros = [];
            $campos_filtro = ['nome', 'cpf', 'numero_matricula', 'escola', 'status', 'unidade', 'turma', 'genero', 'status_programa', 'data_inicial', 'data_final'];
            
            foreach ($campos_filtro as $campo) {
                if (!empty($_GET[$campo])) {
                    $filtros[$campo] = trim($_GET[$campo]);
                }
            }

            $alunos = buscarAlunos($conn, $filtros);
            
            echo json_encode([
                'success' => true,
                'data' => $alunos,
                'tipo' => 'exportacao'
            ]);
            break;

        default:
            throw new Exception('Ação não encontrada');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>