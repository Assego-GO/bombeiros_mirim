<?php
// api/galeria.php
session_start();

// Incluir arquivo de conexão
require_once "conexao.php";

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

header('Content-Type: application/json');

try {
    switch ($action) {
        case 'listar':
            listarGalerias($conn, $usuario_id);
            break;
        
        case 'turmas':
            listarTurmas($conn, $usuario_id);
            break;
        
        case 'criar':
            criarGaleria($conn, $usuario_id);
            break;
        
        case 'detalhes':
            obterDetalhesGaleria($conn, $_GET['id'] ?? 0);
            break;
        
        case 'excluir':
            excluirGaleria($conn, $_GET['id'] ?? 0, $usuario_id);
            break;
        
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Ação não especificada']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
}

function listarGalerias($conn, $usuario_id) {
    $sql = "SELECT g.*, t.nome_turma, u.nome as unidade_nome,
                   COUNT(ga.id) as total_arquivos
            FROM galerias g
            LEFT JOIN turma t ON g.turma_id = t.id
            LEFT JOIN unidade u ON t.id_unidade = u.id
            LEFT JOIN galeria_arquivos ga ON g.id = ga.galeria_id
            WHERE g.criado_por = ? AND g.status = 'ativo'
            GROUP BY g.id
            ORDER BY g.data_criacao DESC";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Erro na preparação da query: ' . $conn->error]);
        return;
    }
    
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $galerias = [];
    
    while ($row = $result->fetch_assoc()) {
        $galerias[] = $row;
    }
    
    $stmt->close();
    echo json_encode(['success' => true, 'galerias' => $galerias]);
}

function listarTurmas($conn, $usuario_id) {
    // Verificar se é professor ou admin
    $tipo_usuario = $_SESSION['usuario_tipo'] ?? '';
    
    if ($tipo_usuario === 'admin') {
        // Admin vê todas as turmas
        $sql = "SELECT t.id, t.nome_turma, u.nome as unidade_nome
                FROM turma t
                LEFT JOIN unidade u ON t.id_unidade = u.id
                ORDER BY t.nome_turma";
        $stmt = $conn->prepare($sql);
    } else {
        // Professor vê apenas suas turmas
        $sql = "SELECT t.id, t.nome_turma, u.nome as unidade_nome
                FROM turma t
                LEFT JOIN unidade u ON t.id_unidade = u.id
                WHERE t.id_professor = ?
                ORDER BY t.nome_turma";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $usuario_id);
    }
    
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Erro na preparação da query: ' . $conn->error]);
        return;
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $turmas = [];
    
    while ($row = $result->fetch_assoc()) {
        $turmas[] = $row;
    }
    
    $stmt->close();
    echo json_encode(['success' => true, 'turmas' => $turmas]);
}

function criarGaleria($conn, $usuario_id) {
    $titulo = trim($_POST['titulo'] ?? '');
    $turma_id = (int)($_POST['turma_id'] ?? 0);
    $atividade_realizada = trim($_POST['atividade_realizada'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    
    // Validações
    if (empty($titulo)) {
        echo json_encode(['success' => false, 'message' => 'Título é obrigatório']);
        return;
    }
    
    if ($turma_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Selecione uma turma válida']);
        return;
    }
    
    if (empty($atividade_realizada)) {
        echo json_encode(['success' => false, 'message' => 'Atividade realizada é obrigatória']);
        return;
    }
    
    // Verificar se a turma existe e se o usuário tem acesso a ela
    $tipo_usuario = $_SESSION['usuario_tipo'] ?? '';
    if ($tipo_usuario !== 'admin') {
        $stmt = $conn->prepare("SELECT id FROM turma WHERE id = ? AND id_professor = ?");
        $stmt->bind_param("ii", $turma_id, $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Você não tem acesso a esta turma']);
            $stmt->close();
            return;
        }
        $stmt->close();
    }
    
    // Inserir galeria
    $stmt = $conn->prepare("INSERT INTO galerias (titulo, turma_id, atividade_realizada, descricao, criado_por) VALUES (?, ?, ?, ?, ?)");
    
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Erro na preparação da query: ' . $conn->error]);
        return;
    }
    
    $stmt->bind_param("sissi", $titulo, $turma_id, $atividade_realizada, $descricao, $usuario_id);
    
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Erro ao criar galeria: ' . $stmt->error]);
        $stmt->close();
        return;
    }
    
    $galeria_id = $conn->insert_id;
    $stmt->close();
    
    // Processar uploads de arquivos
    $arquivos_salvos = 0;
    $erros_upload = [];
    
    if (isset($_FILES['arquivos']) && !empty($_FILES['arquivos']['name'][0])) {
        $arquivos_salvos = processarUploads($_FILES['arquivos'], $galeria_id, $conn, $erros_upload);
    }
    
    $message = "Galeria criada com sucesso!";
    if ($arquivos_salvos > 0) {
        $message .= " {$arquivos_salvos} arquivo(s) enviado(s).";
    }
    if (!empty($erros_upload)) {
        $message .= " Alguns arquivos não puderam ser enviados: " . implode(', ', $erros_upload);
    }
    
    echo json_encode([
        'success' => true, 
        'message' => $message,
        'galeria_id' => $galeria_id,
        'arquivos_salvos' => $arquivos_salvos
    ]);
}

function processarUploads($files, $galeria_id, $conn, &$erros) {
    // Definir estrutura de diretórios baseada na data
    $ano = date('Y');
    $mes = date('m');
    
    // Caminho absoluto da raiz do projeto (já que uploads está na raiz)
    $upload_base = __DIR__ . '/../../uploads/galeria/';
    $upload_dir = $upload_base . $ano . '/' . $mes . '/';
    
    // Criar diretórios se não existirem
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $allowed_images = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $allowed_videos = ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm'];
    $max_size = 50 * 1024 * 1024; // 50MB
    
    $arquivos_salvos = 0;
    $total_files = count($files['name']);
    
    for ($i = 0; $i < $total_files; $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            $erros[] = $files['name'][$i] . ' (erro no upload)';
            continue;
        }
        
        $nome_original = $files['name'][$i];
        $tamanho = $files['size'][$i];
        $tmp_name = $files['tmp_name'][$i];
        
        // Verificar tamanho
        if ($tamanho > $max_size) {
            $erros[] = $nome_original . ' (muito grande)';
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
            $erros[] = $nome_original . ' (tipo não permitido)';
            continue;
        }
        
        // Gerar nome único
        $nome_arquivo = uniqid() . '_' . time() . '.' . $extensao;
        $caminho_completo = $upload_dir . $nome_arquivo;
        
        // Mover arquivo
        if (move_uploaded_file($tmp_name, $caminho_completo)) {
            // Salvar no banco - caminho relativo da raiz do projeto
            $stmt = $conn->prepare("INSERT INTO galeria_arquivos (galeria_id, nome_arquivo, nome_original, tipo_arquivo, extensao, tamanho, caminho) VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            if ($stmt) {
                $caminho_relativo = 'uploads/galeria/' . $ano . '/' . $mes . '/' . $nome_arquivo;
                $stmt->bind_param("issssls", $galeria_id, $nome_arquivo, $nome_original, $tipo_arquivo, $extensao, $tamanho, $caminho_relativo);
                
                if ($stmt->execute()) {
                    $arquivos_salvos++;
                } else {
                    unlink($caminho_completo); // Remover arquivo se falhar no banco
                    $erros[] = $nome_original . ' (erro no banco)';
                }
                $stmt->close();
            } else {
                unlink($caminho_completo);
                $erros[] = $nome_original . ' (erro na preparação)';
            }
        } else {
            $erros[] = $nome_original . ' (erro ao mover)';
        }
    }
    
    return $arquivos_salvos;
}

function obterDetalhesGaleria($conn, $galeria_id) {
    if ($galeria_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID da galeria inválido']);
        return;
    }
    
    // Buscar dados da galeria
    $sql = "SELECT g.*, t.nome_turma, u.nome as unidade_nome, us.nome as criado_por_nome
            FROM galerias g
            LEFT JOIN turma t ON g.turma_id = t.id
            LEFT JOIN unidade u ON t.id_unidade = u.id
            LEFT JOIN usuarios us ON g.criado_por = us.id
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

function excluirGaleria($conn, $galeria_id, $usuario_id) {
    if ($galeria_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID da galeria inválido']);
        return;
    }
    
    // Verificar se o usuário pode excluir (criador ou admin)
    $tipo_usuario = $_SESSION['usuario_tipo'] ?? '';
    
    if ($tipo_usuario !== 'admin') {
        $stmt = $conn->prepare("SELECT id FROM galerias WHERE id = ? AND criado_por = ?");
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

// Fechar conexão
$conn->close();
?>