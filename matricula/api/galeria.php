<?php
// api/galeria.php - ADAPTADA PARA ESTRUTURA REAL DO BANCO
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

session_start();

// Função para log detalhado
function logDebug($message) {
    $log_file = __DIR__ . '/galeria_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND | LOCK_EX);
}

logDebug("=== INÍCIO DA REQUISIÇÃO (Estrutura Real) ===");
logDebug("METHOD: " . $_SERVER['REQUEST_METHOD']);
logDebug("SESSION: " . json_encode($_SESSION));

// Incluir arquivo de conexão
if (!file_exists("conexao.php")) {
    logDebug("ERRO: arquivo conexao.php não encontrado");
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Arquivo de conexão não encontrado']);
    exit;
}

require_once "conexao.php";

// Verificar conexão
if ($conn->connect_error) {
    logDebug("ERRO: falha na conexão com o banco: " . $conn->connect_error);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro na conexão com o banco de dados']);
    exit;
}

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    logDebug("ERRO: usuário não autenticado");
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

// Função para verificar e obter dados do usuário (tabela usuarios E professor)
function obterDadosUsuario($conn, $usuario_id) {
    $dados_usuario = null;
    
    // Primeiro, tentar na tabela usuarios
    $stmt = $conn->prepare("SELECT id, nome, tipo, 'usuarios' as origem FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $dados_usuario = $result->fetch_assoc();
    } else {
        // Se não encontrou, tentar na tabela professor
        $stmt->close();
        $stmt = $conn->prepare("SELECT id, nome, 'professor' as tipo, 'professor' as origem FROM professor WHERE id = ?");
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $dados_usuario = $result->fetch_assoc();
        }
    }
    
    $stmt->close();
    return $dados_usuario;
}

$dados_usuario = obterDadosUsuario($conn, $usuario_id);

if (!$dados_usuario) {
    logDebug("ERRO: usuário ID $usuario_id não encontrado em nenhuma tabela");
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Usuário não encontrado no sistema']);
    exit;
}

logDebug("Usuário encontrado: " . json_encode($dados_usuario));

$action = $_GET['action'] ?? $_POST['action'] ?? '';
header('Content-Type: application/json');

try {
    switch ($action) {
        case 'listar':
            listarGalerias($conn, $usuario_id, $dados_usuario);
            break;
        
        case 'turmas':
            listarTurmas($conn, $usuario_id, $dados_usuario);
            break;
        
        case 'criar':
            criarGaleria($conn, $usuario_id, $dados_usuario);
            break;
        
        case 'detalhes':
            obterDetalhesGaleria($conn, $_GET['id'] ?? 0);
            break;
        
        case 'excluir':
            excluirGaleria($conn, $_GET['id'] ?? 0, $usuario_id, $dados_usuario);
            break;
        
        default:
            logDebug("ERRO: ação inválida: $action");
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Ação não especificada']);
    }
} catch (Exception $e) {
    logDebug("EXCEÇÃO: " . $e->getMessage());
    logDebug("STACK TRACE: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
}

function listarGalerias($conn, $usuario_id, $dados_usuario) {
    logDebug("Listando galerias para usuário: $usuario_id (origem: {$dados_usuario['origem']}, tipo: {$dados_usuario['tipo']})");
    
    if ($dados_usuario['tipo'] === 'admin') {
        // Admin vê todas as galerias
        $sql = "SELECT g.*, 
                       COALESCE(t.nome_turma, 'Sem turma específica') as nome_turma, 
                       COALESCE(u.nome, 'N/A') as unidade_nome,
                       COUNT(ga.id) as total_arquivos,
                       COALESCE(us.nome, pr.nome) as criado_por_nome,
                       CASE 
                           WHEN us.id IS NOT NULL THEN 'usuarios'
                           WHEN pr.id IS NOT NULL THEN 'professor'
                           ELSE 'desconhecido'
                       END as criador_origem
                FROM galerias g
                LEFT JOIN turma t ON g.turma_id = t.id
                LEFT JOIN unidade u ON t.id_unidade = u.id
                LEFT JOIN galeria_arquivos ga ON g.id = ga.galeria_id
                LEFT JOIN usuarios us ON g.criado_por = us.id
                LEFT JOIN professor pr ON g.criado_por = pr.id
                WHERE g.status = 'ativo'
                GROUP BY g.id
                ORDER BY g.data_criacao DESC";
        $stmt = $conn->prepare($sql);
    } else {
        // Professor vê apenas suas galerias
        $sql = "SELECT g.*, 
                       COALESCE(t.nome_turma, 'Sem turma específica') as nome_turma, 
                       COALESCE(u.nome, 'N/A') as unidade_nome,
                       COUNT(ga.id) as total_arquivos,
                       COALESCE(us.nome, pr.nome) as criado_por_nome,
                       CASE 
                           WHEN us.id IS NOT NULL THEN 'usuarios'
                           WHEN pr.id IS NOT NULL THEN 'professor'
                           ELSE 'desconhecido'
                       END as criador_origem
                FROM galerias g
                LEFT JOIN turma t ON g.turma_id = t.id
                LEFT JOIN unidade u ON t.id_unidade = u.id
                LEFT JOIN galeria_arquivos ga ON g.id = ga.galeria_id
                LEFT JOIN usuarios us ON g.criado_por = us.id
                LEFT JOIN professor pr ON g.criado_por = pr.id
                WHERE g.criado_por = ? AND g.status = 'ativo'
                GROUP BY g.id
                ORDER BY g.data_criacao DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $usuario_id);
    }
    
    if (!$stmt) {
        logDebug("ERRO na preparação da query: " . $conn->error);
        echo json_encode(['success' => false, 'message' => 'Erro na preparação da query: ' . $conn->error]);
        return;
    }
    
    if (!$stmt->execute()) {
        logDebug("ERRO na execução da query: " . $stmt->error);
        echo json_encode(['success' => false, 'message' => 'Erro na execução: ' . $stmt->error]);
        return;
    }
    
    $result = $stmt->get_result();
    $galerias = [];
    
    while ($row = $result->fetch_assoc()) {
        // Tratamento especial para galerias sem turma
        if (is_null($row['turma_id'])) {
            $row['nome_turma'] = '🎯 Sem turma específica';
            $row['unidade_nome'] = 'Galeria geral';
        }
        $galerias[] = $row;
    }
    
    logDebug("Encontradas " . count($galerias) . " galerias");
    $stmt->close();
    echo json_encode(['success' => true, 'galerias' => $galerias]);
}

function listarTurmas($conn, $usuario_id, $dados_usuario) {
    logDebug("Listando turmas para usuário: $usuario_id (origem: {$dados_usuario['origem']}, tipo: {$dados_usuario['tipo']})");
    
    if ($dados_usuario['tipo'] === 'admin') {
        // ✅ ADMIN VÊ TODAS AS TURMAS
        $sql = "SELECT t.id, t.nome_turma, u.nome as unidade_nome
                FROM turma t
                LEFT JOIN unidade u ON t.id_unidade = u.id
                WHERE t.status IN ('Em Andamento', 'Planejada')
                ORDER BY u.nome, t.nome_turma";
        $stmt = $conn->prepare($sql);
        logDebug("Admin: listando TODAS as turmas");
    } else {
        // ✅ PROFESSOR VÊ APENAS SUAS TURMAS
        $sql = "SELECT t.id, t.nome_turma, u.nome as unidade_nome
                FROM turma t
                LEFT JOIN unidade u ON t.id_unidade = u.id
                WHERE t.id_professor = ? AND t.status IN ('Em Andamento', 'Planejada')
                ORDER BY u.nome, t.nome_turma";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $usuario_id);
        }
        logDebug("Professor: listando apenas turmas do professor ID $usuario_id");
    }
    
    if (!$stmt) {
        logDebug("ERRO na preparação da query turmas: " . $conn->error);
        echo json_encode(['success' => false, 'message' => 'Erro na preparação da query: ' . $conn->error]);
        return;
    }
    
    if (!$stmt->execute()) {
        logDebug("ERRO na execução da query turmas: " . $stmt->error);
        echo json_encode(['success' => false, 'message' => 'Erro na execução: ' . $stmt->error]);
        return;
    }
    
    $result = $stmt->get_result();
    $turmas = [];
    
    while ($row = $result->fetch_assoc()) {
        $turmas[] = $row;
    }
    
    logDebug("Encontradas " . count($turmas) . " turmas para " . $dados_usuario['tipo']);
    $stmt->close();
    echo json_encode(['success' => true, 'turmas' => $turmas]);
}

function criarGaleria($conn, $usuario_id, $dados_usuario) {
    logDebug("Criando galeria para usuário: $usuario_id (origem: {$dados_usuario['origem']}, tipo: {$dados_usuario['tipo']})");
    
    $titulo = trim($_POST['titulo'] ?? '');
    $turma_id = (int)($_POST['turma_id'] ?? 0);
    $atividade_realizada = trim($_POST['atividade_realizada'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    
    logDebug("Dados da galeria: titulo='$titulo', turma_id=$turma_id, atividade='$atividade_realizada'");
    
    // Validações básicas
    if (empty($titulo)) {
        logDebug("ERRO: título vazio");
        echo json_encode(['success' => false, 'message' => 'Título é obrigatório']);
        return;
    }
    
    if (empty($atividade_realizada)) {
        logDebug("ERRO: atividade vazia");
        echo json_encode(['success' => false, 'message' => 'Atividade realizada é obrigatória']);
        return;
    }
    
    // ✅ VALIDAÇÃO DE TURMA - DIFERENTE PARA ADMIN E PROFESSOR
    if ($dados_usuario['tipo'] === 'admin') {
        // ✅ ADMIN: Turma é OPCIONAL
        if ($turma_id > 0) {
            // Se admin escolheu uma turma, verificar se existe
            $stmt = $conn->prepare("SELECT id FROM turma WHERE id = ? AND status IN ('Em Andamento', 'Planejada')");
            if ($stmt) {
                $stmt->bind_param("i", $turma_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 0) {
                    logDebug("ERRO: turma $turma_id não existe ou não está ativa");
                    echo json_encode(['success' => false, 'message' => 'Turma selecionada não encontrada ou inativa']);
                    $stmt->close();
                    return;
                }
                $stmt->close();
                logDebug("✅ Admin escolheu turma válida: $turma_id");
            }
        } else {
            // Admin não escolheu turma - OK!
            $turma_id = null;
            logDebug("✅ Admin criando galeria SEM turma associada");
        }
    } else {
        // ✅ PROFESSOR: Turma é OBRIGATÓRIA
        if ($turma_id <= 0) {
            logDebug("ERRO: professor deve selecionar uma turma");
            echo json_encode(['success' => false, 'message' => 'Selecione uma turma válida']);
            return;
        }
        
        // Verificar se professor tem acesso à turma
        logDebug("Verificando se professor tem acesso à turma $turma_id");
        $stmt = $conn->prepare("SELECT id FROM turma WHERE id = ? AND id_professor = ? AND status IN ('Em Andamento', 'Planejada')");
        if (!$stmt) {
            logDebug("ERRO na preparação da query de verificação de turma: " . $conn->error);
            echo json_encode(['success' => false, 'message' => 'Erro na verificação da turma']);
            return;
        }
        
        $stmt->bind_param("ii", $turma_id, $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            logDebug("ERRO: professor não tem acesso à turma $turma_id");
            echo json_encode(['success' => false, 'message' => 'Você não tem acesso a esta turma']);
            $stmt->close();
            return;
        }
        $stmt->close();
        logDebug("✅ Professor tem acesso à turma");
    }
    
    // Inserir galeria (turma_id pode ser NULL para admin)
    if ($turma_id === null) {
        $stmt = $conn->prepare("INSERT INTO galerias (titulo, turma_id, atividade_realizada, descricao, criado_por, status) VALUES (?, NULL, ?, ?, ?, 'ativo')");
        $stmt->bind_param("sssi", $titulo, $atividade_realizada, $descricao, $usuario_id);
    } else {
        $stmt = $conn->prepare("INSERT INTO galerias (titulo, turma_id, atividade_realizada, descricao, criado_por, status) VALUES (?, ?, ?, ?, ?, 'ativo')");
        $stmt->bind_param("sissi", $titulo, $turma_id, $atividade_realizada, $descricao, $usuario_id);
    }
    
    if (!$stmt) {
        logDebug("ERRO na preparação da query de inserção: " . $conn->error);
        echo json_encode(['success' => false, 'message' => 'Erro na preparação da query: ' . $conn->error]);
        return;
    }
    
    if (!$stmt->execute()) {
        logDebug("ERRO na execução da inserção: " . $stmt->error);
        echo json_encode(['success' => false, 'message' => 'Erro ao criar galeria: ' . $stmt->error]);
        $stmt->close();
        return;
    }
    
    $galeria_id = $conn->insert_id;
    logDebug("✅ Galeria criada com ID: $galeria_id" . ($turma_id ? " (turma: $turma_id)" : " (sem turma)"));
    $stmt->close();
    
    // Processar uploads de arquivos
    $arquivos_salvos = 0;
    $erros_upload = [];
    
    if (isset($_FILES['arquivos']) && !empty($_FILES['arquivos']['name'][0])) {
        logDebug("Processando " . count($_FILES['arquivos']['name']) . " arquivos");
        $arquivos_salvos = processarUploads($_FILES['arquivos'], $galeria_id, $conn, $erros_upload);
    } else {
        logDebug("Nenhum arquivo para upload");
    }
    
    $message = "Galeria criada com sucesso!";
    if ($arquivos_salvos > 0) {
        $message .= " {$arquivos_salvos} arquivo(s) enviado(s).";
    }
    if (!empty($erros_upload)) {
        $message .= " Alguns arquivos não puderam ser enviados: " . implode(', ', $erros_upload);
    }
    
    logDebug("✅ Resultado: $message");
    echo json_encode([
        'success' => true, 
        'message' => $message,
        'galeria_id' => $galeria_id,
        'arquivos_salvos' => $arquivos_salvos
    ]);
}

function processarUploads($files, $galeria_id, $conn, &$erros) {
    logDebug("Processando uploads para galeria: $galeria_id");
    
    // Definir estrutura de diretórios baseada na data
    $ano = date('Y');
    $mes = date('m');
    
    // Caminho absoluto da raiz do projeto
    $upload_base = __DIR__ . '/../../uploads/galeria/';
    $upload_dir = $upload_base . $ano . '/' . $mes . '/';
    
    logDebug("Caminho base: $upload_base");
    logDebug("Diretório upload: $upload_dir");
    
    // Criar diretórios se não existirem
    if (!file_exists($upload_dir)) {
        logDebug("Criando diretório: $upload_dir");
        if (!mkdir($upload_dir, 0755, true)) {
            $erro = "Erro ao criar diretório: $upload_dir";
            logDebug("ERRO: $erro");
            $erros[] = $erro;
            return 0;
        }
    }
    
    // Verificar se o diretório é gravável
    if (!is_writable($upload_dir)) {
        $erro = "Diretório não é gravável: $upload_dir";
        logDebug("ERRO: $erro");
        $erros[] = $erro;
        return 0;
    }
    
    $allowed_images = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $allowed_videos = ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm'];
    $max_size = 50 * 1024 * 1024; // 50MB
    
    $arquivos_salvos = 0;
    $total_files = count($files['name']);
    
    logDebug("Total de arquivos a processar: $total_files");
    
    for ($i = 0; $i < $total_files; $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            $erro = $files['name'][$i] . ' (erro no upload: ' . $files['error'][$i] . ')';
            logDebug("ERRO upload: $erro");
            $erros[] = $erro;
            continue;
        }
        
        $nome_original = $files['name'][$i];
        $tamanho = $files['size'][$i];
        $tmp_name = $files['tmp_name'][$i];
        
        logDebug("Processando arquivo $i: $nome_original ($tamanho bytes)");
        
        // Verificar tamanho
        if ($tamanho > $max_size) {
            $erro = $nome_original . ' (muito grande - ' . formatBytes($tamanho) . ')';
            logDebug("ERRO: $erro");
            $erros[] = $erro;
            continue;
        }
        
        // Obter extensão
        $extensao = strtolower(pathinfo($nome_original, PATHINFO_EXTENSION));
        
        // Verificar tipo de arquivo
        $tipo_arquivo = '';
        if (in_array($extensao, $allowed_images)) {
            $tipo_arquivo = 'imagem';
        } elseif (in_array($extensao, $allowed_videos)) {
            $tipo_arquivo = 'video';
        } else {
            $erro = $nome_original . ' (tipo não permitido: .' . $extensao . ')';
            logDebug("ERRO: $erro");
            $erros[] = $erro;
            continue;
        }
        
        // Gerar nome único
        $nome_arquivo = uniqid() . '_' . time() . '.' . $extensao;
        $caminho_completo = $upload_dir . $nome_arquivo;
        
        logDebug("Movendo para: $caminho_completo");
        
        // Mover arquivo
        if (move_uploaded_file($tmp_name, $caminho_completo)) {
            logDebug("Arquivo movido com sucesso");
            
            // Salvar no banco - caminho relativo da raiz do projeto
            $stmt = $conn->prepare("INSERT INTO galeria_arquivos (galeria_id, nome_arquivo, nome_original, tipo_arquivo, extensao, tamanho, caminho) VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            if ($stmt) {
                $caminho_relativo = '../uploads/galeria/' . $ano . '/' . $mes . '/' . $nome_arquivo;
                $stmt->bind_param("issssis", $galeria_id, $nome_arquivo, $nome_original, $tipo_arquivo, $extensao, $tamanho, $caminho_relativo);
                
                if ($stmt->execute()) {
                    $arquivos_salvos++;
                    logDebug("Arquivo salvo no banco: $caminho_relativo");
                } else {
                    unlink($caminho_completo); // Remover arquivo se falhar no banco
                    $erro = $nome_original . ' (erro no banco: ' . $stmt->error . ')';
                    logDebug("ERRO: $erro");
                    $erros[] = $erro;
                }
                $stmt->close();
            } else {
                unlink($caminho_completo);
                $erro = $nome_original . ' (erro na preparação: ' . $conn->error . ')';
                logDebug("ERRO: $erro");
                $erros[] = $erro;
            }
        } else {
            $erro = $nome_original . ' (erro ao mover arquivo)';
            logDebug("ERRO: $erro");
            $erros[] = $erro;
        }
    }
    
    logDebug("Upload concluído: $arquivos_salvos arquivos salvos");
    return $arquivos_salvos;
}

function obterDetalhesGaleria($conn, $galeria_id) {
    if ($galeria_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID da galeria inválido']);
        return;
    }
    
    // Buscar dados da galeria com suporte a turma NULL
    $sql = "SELECT g.*, 
                   COALESCE(t.nome_turma, 'Sem turma específica') as nome_turma, 
                   COALESCE(u.nome, 'Galeria geral') as unidade_nome, 
                   COALESCE(us.nome, pr.nome) as criado_por_nome,
                   CASE 
                       WHEN us.id IS NOT NULL THEN 'usuarios'
                       WHEN pr.id IS NOT NULL THEN 'professor'
                       ELSE 'desconhecido'
                   END as criador_origem
            FROM galerias g
            LEFT JOIN turma t ON g.turma_id = t.id
            LEFT JOIN unidade u ON t.id_unidade = u.id
            LEFT JOIN usuarios us ON g.criado_por = us.id
            LEFT JOIN professor pr ON g.criado_por = pr.id
            WHERE g.id = ? AND g.status = 'ativo'";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Erro na preparação da query: ' . $conn->error]);
        return;
    }
    
    $stmt->bind_param("i", $galeria_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Galeria não encontrada']);
        $stmt->close();
        return;
    }
    
    $galeria = $result->fetch_assoc();
    
    // Tratamento especial para galerias sem turma
    if (is_null($galeria['turma_id'])) {
        $galeria['nome_turma'] = '🎯 Sem turma específica';
        $galeria['unidade_nome'] = 'Galeria geral';
    }
    
    $stmt->close();
    
    // Buscar arquivos da galeria
    $stmt = $conn->prepare("SELECT * FROM galeria_arquivos WHERE galeria_id = ? ORDER BY data_upload ASC");
    $stmt->bind_param("i", $galeria_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $arquivos = [];
    while ($row = $result->fetch_assoc()) {
        $arquivos[] = $row;
    }
    
    $stmt->close();
    $galeria['arquivos'] = $arquivos;
    
    echo json_encode(['success' => true, 'galeria' => $galeria]);
}

function excluirGaleria($conn, $galeria_id, $usuario_id, $dados_usuario) {
    if ($galeria_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID da galeria inválido']);
        return;
    }
    
    // Verificar permissão: Admin pode excluir qualquer galeria, Professor só suas próprias
    if ($dados_usuario['tipo'] !== 'admin') {
        $stmt = $conn->prepare("SELECT id FROM galerias WHERE id = ? AND criado_por = ? AND status = 'ativo'");
        $stmt->bind_param("ii", $galeria_id, $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Você não tem permissão para excluir esta galeria']);
            $stmt->close();
            return;
        }
        $stmt->close();
    }
    
    // Buscar arquivos para excluir fisicamente
    $stmt = $conn->prepare("SELECT caminho FROM galeria_arquivos WHERE galeria_id = ?");
    $stmt->bind_param("i", $galeria_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $arquivos = [];
    while ($row = $result->fetch_assoc()) {
        $arquivos[] = $row['caminho'];
    }
    $stmt->close();
    
    // Marcar galeria como inativa (soft delete)
    $stmt = $conn->prepare("UPDATE galerias SET status = 'inativo' WHERE id = ?");
    $stmt->bind_param("i", $galeria_id);
    
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Erro ao excluir galeria: ' . $stmt->error]);
        $stmt->close();
        return;
    }
    $stmt->close();
    
    // Excluir arquivos físicos
    foreach ($arquivos as $arquivo) {
        $caminho_completo = __DIR__ . '/../../' . $arquivo;
        if (file_exists($caminho_completo)) {
            unlink($caminho_completo);
        }
    }
    
    // Excluir registros dos arquivos
    $stmt = $conn->prepare("DELETE FROM galeria_arquivos WHERE galeria_id = ?");
    $stmt->bind_param("i", $galeria_id);
    $stmt->execute();
    $stmt->close();
    
    echo json_encode(['success' => true, 'message' => 'Galeria excluída com sucesso']);
}

function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

// Fechar conexão
$conn->close();
logDebug("=== FIM DA REQUISIÇÃO (Estrutura Real) ===\n");
?>