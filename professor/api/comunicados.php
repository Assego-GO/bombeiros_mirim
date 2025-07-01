<?php
// Habilitar exibição de erros para debug (remover em produção)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json');

// Verificação de administrador
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

// Carregar configurações do ambiente com múltiplos caminhos
$env_paths = [
    __DIR__ . "/../env_config.php",
    __DIR__ . "/../../env_config.php",
    dirname(__DIR__) . "/env_config.php",
    dirname(dirname(__DIR__)) . "/env_config.php"
];

$loaded = false;
foreach ($env_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $loaded = true;
        break;
    }
}

if (!$loaded) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Não foi possível carregar as configurações do ambiente']);
    exit;
}

// Verificar se as variáveis de ambiente existem
if (!isset($_ENV['DB_HOST']) || !isset($_ENV['DB_NAME']) || !isset($_ENV['DB_USER']) || !isset($_ENV['DB_PASS'])) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Configurações de banco de dados não encontradas']);
    exit;
}

$db_host = $_ENV['DB_HOST'];
$db_name = $_ENV['DB_NAME'];
$db_user = $_ENV['DB_USER'];
$db_pass = $_ENV['DB_PASS'];

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    error_log("Erro de conexão PDO: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro de conexão com banco de dados: ' . $e->getMessage()]);
    exit;
}

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

switch ($action) {
    case 'criar':
        if ($method === 'POST') {
            criarComunicado($pdo);
        } else {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
        }
        break;
        
    case 'listar':
        if ($method === 'GET') {
            listarComunicados($pdo);
        } else {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
        }
        break;
        
    case 'editar':
        if ($method === 'POST') {
            editarComunicado($pdo);
        } else {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
        }
        break;
        
    case 'excluir':
        if ($method === 'POST') {
            excluirComunicado($pdo);
        } else {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
        }
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Ação não válida']);
        break;
}

function criarComunicado($pdo) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
            return;
        }
        
        $titulo = trim($input['titulo'] ?? '');
        $conteudo = trim($input['conteudo'] ?? '');
        $status = $input['status'] ?? 'ativo';
        
        if (empty($titulo) || empty($conteudo)) {
            echo json_encode(['success' => false, 'message' => 'Título e conteúdo são obrigatórios']);
            return;
        }
        
        // Verificar se a tabela existe e quais colunas estão disponíveis
        $stmt = $pdo->prepare("SHOW COLUMNS FROM comunicados");
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Preparar SQL baseado nas colunas disponíveis
        $sql = "INSERT INTO comunicados (titulo, conteudo, status";
        $values = "?, ?, ?";
        $params = [$titulo, $conteudo, $status];
        
        if (in_array('criado_por', $columns)) {
            $sql .= ", criado_por";
            $values .= ", ?";
            $params[] = $_SESSION['usuario_id'];
        }
        
        if (in_array('autor_nome', $columns)) {
            $sql .= ", autor_nome";
            $values .= ", ?";
            $params[] = $_SESSION['usuario_nome'] ?? 'Administrador';
        }
        
        $sql .= ") VALUES ($values)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        $comunicadoId = $pdo->lastInsertId();
        
        // Tentar registrar auditoria (se a tabela existir)
        try {
            registrarAuditoria($pdo, 'CRIAR_COMUNICADO', 'comunicados', $comunicadoId, null, [
                'titulo' => $titulo,
                'status' => $status,
                'autor' => $_SESSION['usuario_nome'] ?? 'Administrador'
            ]);
        } catch (Exception $e) {
            error_log("Aviso: Não foi possível registrar auditoria: " . $e->getMessage());
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Comunicado criado com sucesso',
            'id' => $comunicadoId
        ]);
        
    } catch (Exception $e) {
        error_log("Erro ao criar comunicado: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erro interno do servidor: ' . $e->getMessage()]);
    }
}

function listarComunicados($pdo) {
    try {
        // Verificar quais colunas existem na tabela
        $stmt = $pdo->prepare("SHOW COLUMNS FROM comunicados");
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Construir SELECT baseado nas colunas disponíveis
        $selectColumns = ['id', 'titulo', 'conteudo', 'status'];
        
        $optionalColumns = ['data_criacao', 'data_atualizacao', 'autor_nome', 'criado_por'];
        foreach ($optionalColumns as $col) {
            if (in_array($col, $columns)) {
                $selectColumns[] = $col;
            }
        }
        
        $sql = "SELECT " . implode(', ', $selectColumns) . " FROM comunicados WHERE status = 'ativo'";
        
        if (in_array('data_criacao', $columns)) {
            $sql .= " ORDER BY data_criacao DESC";
        } else {
            $sql .= " ORDER BY id DESC";
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $comunicados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $comunicados]);
        
    } catch (Exception $e) {
        error_log("Erro ao listar comunicados: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erro interno do servidor: ' . $e->getMessage()]);
    }
}

function editarComunicado($pdo) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
            return;
        }
        
        $id = intval($input['id'] ?? 0);
        $titulo = trim($input['titulo'] ?? '');
        $conteudo = trim($input['conteudo'] ?? '');
        $status = $input['status'] ?? 'ativo';
        
        if (empty($id) || empty($titulo) || empty($conteudo)) {
            echo json_encode(['success' => false, 'message' => 'ID, título e conteúdo são obrigatórios']);
            return;
        }
        
        // Verificar se o comunicado existe
        $stmt = $pdo->prepare("SELECT * FROM comunicados WHERE id = ?");
        $stmt->execute([$id]);
        $comunicadoAnterior = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$comunicadoAnterior) {
            echo json_encode(['success' => false, 'message' => 'Comunicado não encontrado']);
            return;
        }
        
        // Verificar se a coluna data_atualizacao existe
        $stmt = $pdo->prepare("SHOW COLUMNS FROM comunicados LIKE 'data_atualizacao'");
        $stmt->execute();
        $hasDataAtualizacao = $stmt->fetch();
        
        // Atualizar comunicado
        if ($hasDataAtualizacao) {
            $sql = "UPDATE comunicados SET titulo = ?, conteudo = ?, status = ?, data_atualizacao = CURRENT_TIMESTAMP WHERE id = ?";
        } else {
            $sql = "UPDATE comunicados SET titulo = ?, conteudo = ?, status = ? WHERE id = ?";
        }
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([$titulo, $conteudo, $status, $id]);
        
        if ($result) {
            // Tentar registrar auditoria
            try {
                registrarAuditoria($pdo, 'EDITAR_COMUNICADO', 'comunicados', $id, [
                    'titulo_anterior' => $comunicadoAnterior['titulo'],
                    'conteudo_anterior' => $comunicadoAnterior['conteudo'],
                    'status_anterior' => $comunicadoAnterior['status']
                ], [
                    'titulo_novo' => $titulo,
                    'conteudo_novo' => $conteudo,
                    'status_novo' => $status,
                    'editado_por' => $_SESSION['usuario_nome'] ?? 'Administrador'
                ]);
            } catch (Exception $e) {
                error_log("Aviso: Não foi possível registrar auditoria: " . $e->getMessage());
            }
            
            echo json_encode(['success' => true, 'message' => 'Comunicado atualizado com sucesso']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao atualizar comunicado']);
        }
        
    } catch (Exception $e) {
        error_log("Erro ao editar comunicado: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erro interno do servidor: ' . $e->getMessage()]);
    }
}

function excluirComunicado($pdo) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
            return;
        }
        
        $id = intval($input['id'] ?? 0);
        
        if (empty($id)) {
            echo json_encode(['success' => false, 'message' => 'ID é obrigatório']);
            return;
        }
        
        // Buscar dados antes de excluir para auditoria
        $stmt = $pdo->prepare("SELECT * FROM comunicados WHERE id = ?");
        $stmt->execute([$id]);
        $comunicado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$comunicado) {
            echo json_encode(['success' => false, 'message' => 'Comunicado não encontrado']);
            return;
        }
        
        // Soft delete - marcar como inativo
        $stmt = $pdo->prepare("UPDATE comunicados SET status = 'inativo' WHERE id = ?");
        $result = $stmt->execute([$id]);
        
        if ($result) {
            // Tentar registrar auditoria
            try {
                registrarAuditoria($pdo, 'EXCLUIR_COMUNICADO', 'comunicados', $id, $comunicado, [
                    'excluido_por' => $_SESSION['usuario_nome'] ?? 'Administrador',
                    'tipo_exclusao' => 'soft_delete'
                ]);
            } catch (Exception $e) {
                error_log("Aviso: Não foi possível registrar auditoria: " . $e->getMessage());
            }
            
            echo json_encode(['success' => true, 'message' => 'Comunicado excluído com sucesso']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao excluir comunicado']);
        }
        
    } catch (Exception $e) {
        error_log("Erro ao excluir comunicado: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erro interno do servidor: ' . $e->getMessage()]);
    }
}

function registrarAuditoria($pdo, $acao, $tabela, $registroId, $dadosAnteriores, $dadosNovos) {
    try {
        // Verificar se a tabela de auditoria existe
        $stmt = $pdo->prepare("SHOW TABLES LIKE 'auditoria'");
        $stmt->execute();
        if (!$stmt->fetch()) {
            throw new Exception("Tabela de auditoria não encontrada");
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO auditoria (usuario_id, usuario_nome, acao, tabela, registro_id, dados_anteriores, dados_novos, ip_address) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $_SESSION['usuario_id'],
            $_SESSION['usuario_nome'] ?? 'Administrador',
            $acao,
            $tabela,
            $registroId,
            $dadosAnteriores ? json_encode($dadosAnteriores) : null,
            json_encode($dadosNovos),
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        
    } catch (Exception $e) {
        error_log("Erro ao registrar auditoria: " . $e->getMessage());
        throw $e; // Re-throw para que a função chamadora saiba do erro
    }
}
?>