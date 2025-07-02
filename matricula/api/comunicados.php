<?php
// api/comunicados.php - VERSÃO CORRIGIDA COM UTF-8 PARA BRASIL

// 🇧🇷 ===== CONFIGURAÇÕES PARA BRASIL - ADICIONAR NO INÍCIO =====
date_default_timezone_set('America/Sao_Paulo');

// Configurar charset UTF-8
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

// 🇧🇷 FUNÇÃO PARA OBTER DATA/HORA ATUAL DO BRASIL
function agora() {
    $dt = new DateTime('now', new DateTimeZone('America/Sao_Paulo'));
    return $dt->format('Y-m-d H:i:s');
}

// 🇧🇷 FUNÇÃO PARA FORMATAR DATA BRASILEIRA
function formatarDataBrasil($datetime) {
    if (empty($datetime)) return 'Data não informada';
    
    try {
        $dt = new DateTime($datetime);
        $dt->setTimezone(new DateTimeZone('America/Sao_Paulo'));
        return $dt->format('d/m/Y \à\s H:i');
    } catch (Exception $e) {
        return 'Data inválida';
    }
}

// ===== CONFIGURAÇÕES DE ERRO E SESSÃO =====
// Habilitar exibição de erros para debug (remover em produção)
ini_set('display_errors', 0); // 🔧 DESABILITAR para não quebrar JSON em produção
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// 🇧🇷 HEADERS UTF-8 - IMPORTANTE: antes de qualquer output
header('Content-Type: application/json; charset=UTF-8');
header('Accept-Charset: UTF-8');

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
    // 🇧🇷 CONEXÃO PDO COM UTF-8 COMPLETO
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ]);
    
    // 🇧🇷 CONFIGURAR TIMEZONE DO MYSQL PARA BRASIL
    $pdo->exec("SET time_zone = '-03:00'");
    
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
        
        // 🇧🇷 SANITIZAR E VALIDAR DADOS UTF-8
        $titulo = trim($input['titulo'] ?? '');
        $conteudo = trim($input['conteudo'] ?? '');
        $status = $input['status'] ?? 'ativo';
        
        // Normalizar caracteres UTF-8
        $titulo = mb_convert_encoding($titulo, 'UTF-8', 'UTF-8');
        $conteudo = mb_convert_encoding($conteudo, 'UTF-8', 'UTF-8');
        
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
            // 🇧🇷 Sanitizar nome do autor UTF-8
            $autor_nome = mb_convert_encoding($_SESSION['usuario_nome'] ?? 'Administrador', 'UTF-8', 'UTF-8');
            $params[] = $autor_nome;
        }
        
        // 🇧🇷 ADICIONAR DATA DE CRIAÇÃO BRASILEIRA SE A COLUNA EXISTIR
        if (in_array('data_criacao', $columns)) {
            $sql .= ", data_criacao";
            $values .= ", ?";
            $params[] = agora();
        }
        
        $sql .= ") VALUES ($values)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        $comunicadoId = $pdo->lastInsertId();
        
        // 🇧🇷 SE NÃO INSERIU data_criacao, ATUALIZAR COM HORÁRIO BRASILEIRO
        if (!in_array('data_criacao', $columns)) {
            // Tentar atualizar se a coluna existir mas não foi detectada
            try {
                $pdo->exec("UPDATE comunicados SET data_criacao = '" . agora() . "' WHERE id = " . $comunicadoId);
            } catch (Exception $e) {
                // Ignorar se a coluna não existir
            }
        }
        
        // Tentar registrar auditoria (se a tabela existir)
        try {
            registrarAuditoria($pdo, 'CRIAR_COMUNICADO', 'comunicados', $comunicadoId, null, [
                'titulo' => $titulo,
                'status' => $status,
                'autor' => $_SESSION['usuario_nome'] ?? 'Administrador',
                'data_criacao' => agora()
            ]);
        } catch (Exception $e) {
            error_log("Aviso: Não foi possível registrar auditoria: " . $e->getMessage());
        }
        
        echo json_encode([
            'success' => true, 
            'message' => '✅ Comunicado criado com sucesso!',
            'id' => $comunicadoId
        ]);
        
    } catch (Exception $e) {
        error_log("Erro ao criar comunicado: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => '❌ Erro interno do servidor: ' . $e->getMessage()]);
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
        
        // 🇧🇷 PROCESSAR DADOS PARA UTF-8 E FORMATO BRASILEIRO
        foreach ($comunicados as &$comunicado) {
            // Garantir UTF-8 nos textos
            $comunicado['titulo'] = mb_convert_encoding($comunicado['titulo'], 'UTF-8', 'UTF-8');
            $comunicado['conteudo'] = mb_convert_encoding($comunicado['conteudo'], 'UTF-8', 'UTF-8');
            
            if (isset($comunicado['autor_nome'])) {
                $comunicado['autor_nome'] = mb_convert_encoding($comunicado['autor_nome'], 'UTF-8', 'UTF-8');
            }
            
            // Formatar datas para formato brasileiro (se necessário no frontend)
            if (isset($comunicado['data_criacao'])) {
                $comunicado['data_criacao_formatada'] = formatarDataBrasil($comunicado['data_criacao']);
            }
            
            if (isset($comunicado['data_atualizacao'])) {
                $comunicado['data_atualizacao_formatada'] = formatarDataBrasil($comunicado['data_atualizacao']);
            }
        }
        
        echo json_encode(['success' => true, 'data' => $comunicados], JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        error_log("Erro ao listar comunicados: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => '❌ Erro interno do servidor: ' . $e->getMessage()]);
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
        
        // 🇧🇷 SANITIZAR E VALIDAR DADOS UTF-8
        $titulo = trim($input['titulo'] ?? '');
        $conteudo = trim($input['conteudo'] ?? '');
        $status = $input['status'] ?? 'ativo';
        
        // Normalizar caracteres UTF-8
        $titulo = mb_convert_encoding($titulo, 'UTF-8', 'UTF-8');
        $conteudo = mb_convert_encoding($conteudo, 'UTF-8', 'UTF-8');
        
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
        
        // 🇧🇷 ATUALIZAR COMUNICADO COM DATA BRASILEIRA
        if ($hasDataAtualizacao) {
            $sql = "UPDATE comunicados SET titulo = ?, conteudo = ?, status = ?, data_atualizacao = ? WHERE id = ?";
            $params = [$titulo, $conteudo, $status, agora(), $id];
        } else {
            $sql = "UPDATE comunicados SET titulo = ?, conteudo = ?, status = ? WHERE id = ?";
            $params = [$titulo, $conteudo, $status, $id];
        }
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute($params);
        
        if ($result) {
            // 🇧🇷 SE NÃO TEM COLUNA data_atualizacao ESPECÍFICA, TENTAR ATUALIZAR
            if (!$hasDataAtualizacao) {
                try {
                    $pdo->exec("UPDATE comunicados SET data_atualizacao = '" . agora() . "' WHERE id = " . $id);
                } catch (Exception $e) {
                    // Ignorar se a coluna não existir
                }
            }
            
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
                    'editado_por' => $_SESSION['usuario_nome'] ?? 'Administrador',
                    'data_edicao' => agora()
                ]);
            } catch (Exception $e) {
                error_log("Aviso: Não foi possível registrar auditoria: " . $e->getMessage());
            }
            
            echo json_encode(['success' => true, 'message' => '✅ Comunicado atualizado com sucesso!']);
        } else {
            echo json_encode(['success' => false, 'message' => '❌ Erro ao atualizar comunicado']);
        }
        
    } catch (Exception $e) {
        error_log("Erro ao editar comunicado: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => '❌ Erro interno do servidor: ' . $e->getMessage()]);
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
                    'tipo_exclusao' => 'soft_delete',
                    'data_exclusao' => agora()
                ]);
            } catch (Exception $e) {
                error_log("Aviso: Não foi possível registrar auditoria: " . $e->getMessage());
            }
            
            echo json_encode(['success' => true, 'message' => '✅ Comunicado excluído com sucesso!']);
        } else {
            echo json_encode(['success' => false, 'message' => '❌ Erro ao excluir comunicado']);
        }
        
    } catch (Exception $e) {
        error_log("Erro ao excluir comunicado: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => '❌ Erro interno do servidor: ' . $e->getMessage()]);
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
        
        // 🇧🇷 PREPARAR DADOS COM UTF-8 E DATA BRASILEIRA
        $usuario_nome = mb_convert_encoding($_SESSION['usuario_nome'] ?? 'Administrador', 'UTF-8', 'UTF-8');
        $dadosAnterioresJson = $dadosAnteriores ? json_encode($dadosAnteriores, JSON_UNESCAPED_UNICODE) : null;
        $dadosNovosJson = json_encode($dadosNovos, JSON_UNESCAPED_UNICODE);
        
        // Verificar se existe coluna data_acao
        $stmt = $pdo->prepare("SHOW COLUMNS FROM auditoria LIKE 'data_acao'");
        $stmt->execute();
        $hasDataAcao = $stmt->fetch();
        
        if ($hasDataAcao) {
            $sql = "INSERT INTO auditoria (usuario_id, usuario_nome, acao, tabela, registro_id, dados_anteriores, dados_novos, ip_address, data_acao) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $params = [
                $_SESSION['usuario_id'],
                $usuario_nome,
                $acao,
                $tabela,
                $registroId,
                $dadosAnterioresJson,
                $dadosNovosJson,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                agora()
            ];
        } else {
            $sql = "INSERT INTO auditoria (usuario_id, usuario_nome, acao, tabela, registro_id, dados_anteriores, dados_novos, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $params = [
                $_SESSION['usuario_id'],
                $usuario_nome,
                $acao,
                $tabela,
                $registroId,
                $dadosAnterioresJson,
                $dadosNovosJson,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ];
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        // 🇧🇷 SE NÃO TEM COLUNA data_acao, TENTAR ATUALIZAR COM HORÁRIO BRASILEIRO
        if (!$hasDataAcao) {
            try {
                $audit_id = $pdo->lastInsertId();
                $pdo->exec("UPDATE auditoria SET data_acao = '" . agora() . "' WHERE id = " . $audit_id);
            } catch (Exception $e) {
                // Ignorar se a coluna não existir
            }
        }
        
    } catch (Exception $e) {
        error_log("Erro ao registrar auditoria: " . $e->getMessage());
        throw $e; // Re-throw para que a função chamadora saiba do erro
    }
}
?>