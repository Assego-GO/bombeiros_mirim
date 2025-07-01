<?php
session_start();
header('Content-Type: application/json');

// Verificação de administrador
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

require "conexao.php";

$db_host = $_ENV['DB_HOST'];
$db_name = $_ENV['DB_NAME'];
$db_user = $_ENV['DB_USER'];
$db_pass = $_ENV['DB_PASS'];

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro de conexão com banco de dados']);
    exit;
}

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

switch ($action) {
    case 'criar':
        if ($method === 'POST') {
            criarComunicado($pdo);
        }
        break;
        
    case 'listar':
        if ($method === 'GET') {
            listarComunicados($pdo);
        }
        break;
        
    case 'editar':
        if ($method === 'POST') {
            editarComunicado($pdo);
        }
        break;
        
    case 'excluir':
        if ($method === 'POST') {
            excluirComunicado($pdo);
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
        
        $titulo = trim($input['titulo'] ?? '');
        $conteudo = trim($input['conteudo'] ?? '');
        $status = $input['status'] ?? 'ativo';
        
        if (empty($titulo) || empty($conteudo)) {
            echo json_encode(['success' => false, 'message' => 'Título e conteúdo são obrigatórios']);
            return;
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO comunicados (titulo, conteudo, status, criado_por, autor_nome) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $titulo,
            $conteudo,
            $status,
            $_SESSION['usuario_id'],
            $_SESSION['usuario_nome']
        ]);
        
        // Registrar auditoria
        registrarAuditoria($pdo, 'CRIAR_COMUNICADO', 'comunicados', $pdo->lastInsertId(), null, [
            'titulo' => $titulo,
            'status' => $status,
            'autor' => $_SESSION['usuario_nome']
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Comunicado criado com sucesso']);
        
    } catch (Exception $e) {
        error_log("Erro ao criar comunicado: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erro interno do servidor']);
    }
}

function listarComunicados($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT id, titulo, conteudo, status, data_criacao, data_atualizacao, autor_nome, criado_por
            FROM comunicados 
            ORDER BY data_criacao DESC
        ");
        
        $stmt->execute();
        $comunicados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $comunicados]);
        
    } catch (Exception $e) {
        error_log("Erro ao listar comunicados: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erro interno do servidor']);
    }
}

function editarComunicado($pdo) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $id = $input['id'] ?? 0;
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
        
        // Atualizar comunicado
        $stmt = $pdo->prepare("
            UPDATE comunicados 
            SET titulo = ?, conteudo = ?, status = ?
            WHERE id = ?
        ");
        
        $stmt->execute([$titulo, $conteudo, $status, $id]);
        
        // Registrar auditoria
        registrarAuditoria($pdo, 'EDITAR_COMUNICADO', 'comunicados', $id, $comunicadoAnterior, [
            'titulo' => $titulo,
            'status' => $status,
            'editado_por' => $_SESSION['usuario_nome']
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Comunicado atualizado com sucesso']);
        
    } catch (Exception $e) {
        error_log("Erro ao editar comunicado: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erro interno do servidor']);
    }
}

function excluirComunicado($pdo) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? 0;
        
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
        
        // Excluir comunicado
        $stmt = $pdo->prepare("DELETE FROM comunicados WHERE id = ?");
        $stmt->execute([$id]);
        
        // Registrar auditoria
        registrarAuditoria($pdo, 'EXCLUIR_COMUNICADO', 'comunicados', $id, null, $comunicado);
        
        echo json_encode(['success' => true, 'message' => 'Comunicado excluído com sucesso']);
        
    } catch (Exception $e) {
        error_log("Erro ao excluir comunicado: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erro interno do servidor']);
    }
}

function registrarAuditoria($pdo, $acao, $tabela, $registroId, $dadosAnteriores, $dadosNovos) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO auditoria (usuario_id, usuario_nome, acao, tabela, registro_id, dados_anteriores, dados_novos, ip_address) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $_SESSION['usuario_id'],
            $_SESSION['usuario_nome'],
            $acao,
            $tabela,
            $registroId,
            $dadosAnteriores ? json_encode($dadosAnteriores) : null,
            json_encode($dadosNovos),
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        
    } catch (Exception $e) {
        error_log("Erro ao registrar auditoria: " . $e->getMessage());
    }
}
?>