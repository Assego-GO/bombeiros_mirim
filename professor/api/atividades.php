<?php
// api/atividades.php - Versão corrigida com suporte a voluntário e status
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

function logDebug($message, $data = null) {
    $timestamp = date('Y-m-d H:i:s');
    $log = "[$timestamp] $message";
    if ($data !== null) {
        $log .= " | " . json_encode($data);
    }
    error_log($log);
}

try {
    logDebug("=== INICIO API ATIVIDADES ===");
    
    // Verificar se sessão já está ativa
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
        logDebug("Sessão iniciada");
    }
    
    logDebug("Verificando autenticação", [
        'usuario_id' => $_SESSION['usuario_id'] ?? 'não definido',
        'usuario_tipo' => $_SESSION['usuario_tipo'] ?? 'não definido'
    ]);
    
    // Verificar autenticação
    if (!isset($_SESSION['usuario_id'])) {
        logDebug("ERRO: Usuário não logado");
        http_response_code(401);
        echo json_encode([
            'success' => false, 
            'message' => 'Acesso negado - usuário não logado'
        ]);
        exit;
    }
    
    if (!isset($_SESSION['usuario_tipo']) || $_SESSION['usuario_tipo'] !== 'professor') {
        logDebug("ERRO: Usuário não é professor");
        http_response_code(403);
        echo json_encode([
            'success' => false, 
            'message' => 'Acesso negado - usuário não é professor'
        ]);
        exit;
    }
    
    // CAMINHO CORRETO baseado na estrutura mostrada
    $configPath = "conexao.php";
    
    logDebug("Verificando arquivo de configuração", [
        'caminho' => $configPath,
        'diretorio_atual' => getcwd(),
        'arquivo_existe' => file_exists($configPath)
    ]);
    
    if (!file_exists($configPath)) {
        logDebug("ERRO: Arquivo de configuração não encontrado");
        echo json_encode([
            'success' => false,
            'message' => 'Arquivo de configuração não encontrado',
            'debug' => [
                'caminho_tentado' => $configPath,
                'caminho_absoluto' => realpath('.') . '/' . $configPath,
                'diretorio_atual' => getcwd()
            ]
        ]);
        exit;
    }
    
    logDebug("Carregando configuração...");
    require $configPath;
    
    // Verificar variáveis de ambiente
    $env_required = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'];
    $env_missing = [];
    
    foreach ($env_required as $var) {
        if (!isset($_ENV[$var])) {
            $env_missing[] = $var;
        }
    }
    
    if (!empty($env_missing)) {
        logDebug("ERRO: Variáveis de ambiente faltando", $env_missing);
        echo json_encode([
            'success' => false,
            'message' => 'Configuração incompleta',
            'debug' => 'Variáveis faltando: ' . implode(', ', $env_missing)
        ]);
        exit;
    }
    
    logDebug("Conectando ao banco de dados...");
    
    // Conectar ao banco
    try {
        $pdo = new PDO(
            "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8mb4", 
            $_ENV['DB_USER'], 
            $_ENV['DB_PASS']
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        logDebug("Conexão com banco estabelecida");
    } catch (PDOException $e) {
        logDebug("ERRO: Falha na conexão com banco", ['error' => $e->getMessage()]);
        echo json_encode([
            'success' => false,
            'message' => 'Erro de conexão com banco de dados',
            'debug' => $e->getMessage()
        ]);
        exit;
    }
    
    // Processar requisição
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';
    $professor_id = $_SESSION['usuario_id'];
    
    logDebug("Processando requisição", [
        'method' => $method,
        'action' => $action,
        'professor_id' => $professor_id
    ]);
    
    switch ($method) {
        case 'GET':
            switch ($action) {
                case 'listar':
                    listarAtividades($pdo, $professor_id);
                    break;
                    
                case 'detalhes':
                    if (isset($_GET['id'])) {
                        detalhesAtividade($pdo, $_GET['id'], $professor_id);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'ID não fornecido']);
                    }
                    break;
                    
                case 'turmas':
                    listarTurmasProfessor($pdo, $professor_id);
                    break;
                    
                default:
                    echo json_encode(['success' => false, 'message' => 'Ação não reconhecida']);
            }
            break;
            
        case 'POST':
            if (isset($_POST['action'])) {
                switch ($_POST['action']) {
                    case 'cadastrar':
                        cadastrarAtividade($pdo, $_POST, $professor_id);
                        break;
                    case 'editar':
                        editarAtividade($pdo, $_POST, $professor_id);
                        break;
                    default:
                        echo json_encode(['success' => false, 'message' => 'Ação POST não reconhecida']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Ação não especificada']);
            }
            break;
            
        case 'DELETE':
            if (isset($_GET['id'])) {
                deletarAtividade($pdo, $_GET['id'], $professor_id);
            } else {
                echo json_encode(['success' => false, 'message' => 'ID não fornecido']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Método não suportado']);
    }
    
} catch (Exception $e) {
    logDebug("ERRO GERAL", [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor',
        'debug' => $e->getMessage()
    ]);
}

function listarAtividades($pdo, $professor_id) {
    try {
        logDebug("Executando listarAtividades", ['professor_id' => $professor_id]);
        
        // Incluir campos de voluntário na consulta
        $sql = "SELECT a.*, t.nome_turma, u.nome as unidade_nome 
                FROM atividades a 
                LEFT JOIN turma t ON a.turma_id = t.id 
                LEFT JOIN unidade u ON t.id_unidade = u.id 
                WHERE a.professor_id = ? 
                ORDER BY a.data_atividade DESC, a.hora_inicio ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$professor_id]);
        $atividades = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        logDebug("Atividades encontradas", ['total' => count($atividades)]);
        
        // Adicionar contagem de participações
        foreach ($atividades as &$atividade) {
            try {
                $stmt_count = $pdo->prepare("SELECT COUNT(*) as total FROM atividade_participacao WHERE atividade_id = ?");
                $stmt_count->execute([$atividade['id']]);
                $count = $stmt_count->fetch(PDO::FETCH_ASSOC);
                $atividade['total_participacoes'] = $count['total'];
            } catch (PDOException $e) {
                logDebug("Aviso: Erro ao contar participações", ['atividade_id' => $atividade['id']]);
                $atividade['total_participacoes'] = 0;
            }
            
            // Garantir que campos de voluntário sejam retornados corretamente
            $atividade['eh_voluntario'] = $atividade['eh_voluntario'] ?? 0;
            $atividade['nome_voluntario'] = $atividade['nome_voluntario'] ?? null;
            $atividade['telefone_voluntario'] = $atividade['telefone_voluntario'] ?? null;
            $atividade['especialidade_voluntario'] = $atividade['especialidade_voluntario'] ?? null;
        }
        
        echo json_encode([
            'success' => true, 
            'atividades' => $atividades
        ]);
        
    } catch (PDOException $e) {
        logDebug("ERRO SQL listarAtividades", ['error' => $e->getMessage()]);
        echo json_encode([
            'success' => false, 
            'message' => 'Erro ao listar atividades: ' . $e->getMessage()
        ]);
    }
}

function listarTurmasProfessor($pdo, $professor_id) {
    try {
        logDebug("Executando listarTurmasProfessor", ['professor_id' => $professor_id]);
        
        $sql = "SELECT t.id, t.nome_turma, u.nome as unidade_nome 
                FROM turma t 
                LEFT JOIN unidade u ON t.id_unidade = u.id 
                WHERE t.id_professor = ? AND t.status = 'Em Andamento'";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$professor_id]);
        $turmas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        logDebug("Turmas encontradas", ['total' => count($turmas)]);
        
        echo json_encode([
            'success' => true, 
            'turmas' => $turmas
        ]);
        
    } catch (PDOException $e) {
        logDebug("ERRO SQL listarTurmasProfessor", ['error' => $e->getMessage()]);
        echo json_encode([
            'success' => false, 
            'message' => 'Erro ao listar turmas: ' . $e->getMessage()
        ]);
    }
}

function detalhesAtividade($pdo, $atividade_id, $professor_id) {
    try {
        // Incluir todos os campos na consulta de detalhes
        $sql = "SELECT a.*, t.nome_turma, u.nome as unidade_nome 
                FROM atividades a 
                LEFT JOIN turma t ON a.turma_id = t.id 
                LEFT JOIN unidade u ON t.id_unidade = u.id 
                WHERE a.id = ? AND a.professor_id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$atividade_id, $professor_id]);
        $atividade = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$atividade) {
            echo json_encode(['success' => false, 'message' => 'Atividade não encontrada']);
            return;
        }
        
        // Garantir que campos de voluntário sejam retornados
        $atividade['eh_voluntario'] = $atividade['eh_voluntario'] ?? 0;
        $atividade['nome_voluntario'] = $atividade['nome_voluntario'] ?? null;
        $atividade['telefone_voluntario'] = $atividade['telefone_voluntario'] ?? null;
        $atividade['especialidade_voluntario'] = $atividade['especialidade_voluntario'] ?? null;
        
        // Buscar participações
        $sql_participacoes = "SELECT ap.*, al.nome as aluno_nome 
                              FROM atividade_participacao ap 
                              LEFT JOIN alunos al ON ap.aluno_id = al.id 
                              WHERE ap.atividade_id = ?";
        
        $stmt_part = $pdo->prepare($sql_participacoes);
        $stmt_part->execute([$atividade_id]);
        $participacoes = $stmt_part->fetchAll(PDO::FETCH_ASSOC);
        
        $atividade['participacoes'] = $participacoes;
        
        logDebug("Detalhes da atividade carregados", [
            'atividade_id' => $atividade_id,
            'eh_voluntario' => $atividade['eh_voluntario'],
            'nome_voluntario' => $atividade['nome_voluntario'],
            'status' => $atividade['status']
        ]);
        
        echo json_encode(['success' => true, 'atividade' => $atividade]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro ao buscar detalhes: ' . $e->getMessage()]);
    }
}

function cadastrarAtividade($pdo, $dados, $professor_id) {
    try {
        logDebug("Iniciando cadastro de atividade", [
            'professor_id' => $professor_id,
            'eh_voluntario' => $dados['eh_voluntario'] ?? 0,
            'nome_voluntario' => $dados['nome_voluntario'] ?? null
        ]);
        
        // Validar campos obrigatórios básicos
        $campos_obrigatorios = [
            'nome_atividade', 'turma_id', 'data_atividade', 
            'hora_inicio', 'hora_termino', 'local_atividade', 
            'instrutor_responsavel', 'objetivo_atividade', 'conteudo_abordado'
        ];
        
        foreach ($campos_obrigatorios as $campo) {
            if (empty($dados[$campo])) {
                echo json_encode(['success' => false, 'message' => "Campo '$campo' é obrigatório"]);
                return;
            }
        }
        
        // Validação: Se é voluntário, nome do voluntário é obrigatório
        $eh_voluntario = isset($dados['eh_voluntario']) && $dados['eh_voluntario'] == '1' ? 1 : 0;
        
        if ($eh_voluntario && empty($dados['nome_voluntario'])) {
            echo json_encode(['success' => false, 'message' => 'Nome do voluntário é obrigatório quando a atividade é ministrada por voluntário']);
            return;
        }
        
        // SQL com campos de voluntário - nova atividade sempre começa como 'planejada'
        $sql = "INSERT INTO atividades (
                    nome_atividade, turma_id, professor_id, data_atividade, 
                    hora_inicio, hora_termino, local_atividade, instrutor_responsavel, 
                    objetivo_atividade, conteudo_abordado, eh_voluntario, nome_voluntario, 
                    telefone_voluntario, especialidade_voluntario, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'planejada')";
        
        $stmt = $pdo->prepare($sql);
        $resultado = $stmt->execute([
            $dados['nome_atividade'],
            $dados['turma_id'],
            $professor_id,
            $dados['data_atividade'],
            $dados['hora_inicio'],
            $dados['hora_termino'],
            $dados['local_atividade'],
            $dados['instrutor_responsavel'],
            $dados['objetivo_atividade'],
            $dados['conteudo_abordado'],
            $eh_voluntario,
            $eh_voluntario ? ($dados['nome_voluntario'] ?? null) : null,
            $eh_voluntario ? ($dados['telefone_voluntario'] ?? null) : null,
            $eh_voluntario ? ($dados['especialidade_voluntario'] ?? null) : null
        ]);
        
        if ($resultado) {
            $atividade_id = $pdo->lastInsertId();
            
            logDebug("Atividade cadastrada com sucesso", [
                'atividade_id' => $atividade_id,
                'eh_voluntario' => $eh_voluntario,
                'nome_voluntario' => $eh_voluntario ? $dados['nome_voluntario'] : null
            ]);
            
            echo json_encode([
                'success' => true, 
                'message' => $eh_voluntario ? 
                    'Atividade com voluntário cadastrada com sucesso!' : 
                    'Atividade cadastrada com sucesso!',
                'atividade_id' => $atividade_id
            ]);
        } else {
            throw new Exception('Falha ao inserir atividade no banco de dados');
        }
        
    } catch (PDOException $e) {
        logDebug("ERRO SQL cadastrarAtividade", ['error' => $e->getMessage()]);
        echo json_encode(['success' => false, 'message' => 'Erro ao cadastrar: ' . $e->getMessage()]);
    }
}

function editarAtividade($pdo, $dados, $professor_id) {
    try {
        if (empty($dados['atividade_id'])) {
            echo json_encode(['success' => false, 'message' => 'ID da atividade não fornecido']);
            return;
        }
        
        logDebug("Iniciando edição de atividade", [
            'atividade_id' => $dados['atividade_id'],
            'professor_id' => $professor_id,
            'eh_voluntario' => $dados['eh_voluntario'] ?? 0,
            'status' => $dados['status'] ?? 'planejada'
        ]);
        
        // Validação: Se é voluntário, nome do voluntário é obrigatório
        $eh_voluntario = isset($dados['eh_voluntario']) && $dados['eh_voluntario'] == '1' ? 1 : 0;
        
        if ($eh_voluntario && empty($dados['nome_voluntario'])) {
            echo json_encode(['success' => false, 'message' => 'Nome do voluntário é obrigatório quando a atividade é ministrada por voluntário']);
            return;
        }
        
        // Validar status
        $status_validos = ['planejada', 'em_andamento', 'concluida', 'cancelada'];
        $status = $dados['status'] ?? 'planejada';
        
        if (!in_array($status, $status_validos)) {
            echo json_encode(['success' => false, 'message' => 'Status inválido']);
            return;
        }
        
        // ATUALIZADO: SQL com campos de voluntário E status
        $sql = "UPDATE atividades SET 
                    nome_atividade = ?, data_atividade = ?, hora_inicio = ?, 
                    hora_termino = ?, local_atividade = ?, instrutor_responsavel = ?, 
                    objetivo_atividade = ?, conteudo_abordado = ?, eh_voluntario = ?, 
                    nome_voluntario = ?, telefone_voluntario = ?, especialidade_voluntario = ?,
                    status = ?
                WHERE id = ? AND professor_id = ?";
        
        $stmt = $pdo->prepare($sql);
        $resultado = $stmt->execute([
            $dados['nome_atividade'],
            $dados['data_atividade'],
            $dados['hora_inicio'],
            $dados['hora_termino'],
            $dados['local_atividade'],
            $dados['instrutor_responsavel'],
            $dados['objetivo_atividade'],
            $dados['conteudo_abordado'],
            $eh_voluntario,
            $eh_voluntario ? ($dados['nome_voluntario'] ?? null) : null,
            $eh_voluntario ? ($dados['telefone_voluntario'] ?? null) : null,
            $eh_voluntario ? ($dados['especialidade_voluntario'] ?? null) : null,
            $status,
            $dados['atividade_id'],
            $professor_id
        ]);
        
        if ($stmt->rowCount() > 0) {
            logDebug("Atividade editada com sucesso", [
                'atividade_id' => $dados['atividade_id'],
                'eh_voluntario' => $eh_voluntario,
                'status' => $status
            ]);
            
            $message = 'Atividade atualizada com sucesso!';
            if ($eh_voluntario) {
                $message = 'Atividade com voluntário atualizada com sucesso!';
            }
            if ($status === 'concluida') {
                $message .= ' Status alterado para CONCLUÍDA.';
            } elseif ($status === 'cancelada') {
                $message .= ' Status alterado para CANCELADA.';
            }
            
            echo json_encode([
                'success' => true, 
                'message' => $message
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Atividade não encontrada ou sem alterações']);
        }
        
    } catch (PDOException $e) {
        logDebug("ERRO SQL editarAtividade", ['error' => $e->getMessage()]);
        echo json_encode(['success' => false, 'message' => 'Erro ao editar: ' . $e->getMessage()]);
    }
}

function deletarAtividade($pdo, $atividade_id, $professor_id) {
    try {
        logDebug("Iniciando exclusão de atividade", [
            'atividade_id' => $atividade_id,
            'professor_id' => $professor_id
        ]);
        
        // Verificar se a atividade existe e pertence ao professor
        $stmt_check = $pdo->prepare("SELECT eh_voluntario, nome_voluntario, status FROM atividades WHERE id = ? AND professor_id = ?");
        $stmt_check->execute([$atividade_id, $professor_id]);
        $atividade = $stmt_check->fetch(PDO::FETCH_ASSOC);
        
        if (!$atividade) {
            echo json_encode(['success' => false, 'message' => 'Atividade não encontrada']);
            return;
        }
        
        // Deletar participações primeiro (se existir a tabela)
        try {
            $pdo->prepare("DELETE FROM atividade_participacao WHERE atividade_id = ?")->execute([$atividade_id]);
        } catch (PDOException $e) {
            logDebug("Aviso: Erro ao deletar participações (tabela pode não existir)", ['error' => $e->getMessage()]);
        }
        
        // Deletar atividade
        $sql = "DELETE FROM atividades WHERE id = ? AND professor_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$atividade_id, $professor_id]);
        
        if ($stmt->rowCount() > 0) {
            logDebug("Atividade excluída com sucesso", [
                'atividade_id' => $atividade_id,
                'era_voluntario' => $atividade['eh_voluntario'],
                'status_anterior' => $atividade['status']
            ]);
            
            $message = 'Atividade excluída com sucesso!';
            if ($atividade['eh_voluntario']) {
                $message = 'Atividade com voluntário excluída com sucesso!';
            }
                
            echo json_encode(['success' => true, 'message' => $message]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Atividade não encontrada']);
        }
        
    } catch (PDOException $e) {
        logDebug("ERRO SQL deletarAtividade", ['error' => $e->getMessage()]);
        echo json_encode(['success' => false, 'message' => 'Erro ao excluir: ' . $e->getMessage()]);
    }
}

logDebug("=== FIM API ATIVIDADES ===");
?>