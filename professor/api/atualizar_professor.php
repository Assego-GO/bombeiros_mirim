<?php
// Iniciar sessão
session_start();
// Headers para JSON
header('Content-Type: application/json');

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Acesso não autorizado'
    ]);
    exit;
}

// Verificar se é um método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Método não permitido'
    ]);
    exit;
}

// Configuração do banco de dados
require "../../env_config.php";

$db_host =  $_ENV['DB_HOST'];
$db_name =  $_ENV['DB_NAME'];
$db_user = $_ENV['DB_USER'];
$db_pass =  $_ENV['DB_PASS'];

// Conexão com o banco de dados
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro na conexão com o banco de dados: ' . $e->getMessage()
    ]);
    exit;
}

// Obter dados do formulário
$id = $_SESSION['usuario_id']; // Usar o ID da sessão para segurança
$nome = trim($_POST['nome'] ?? '');
$email = trim($_POST['email'] ?? '');
$telefone = trim($_POST['telefone'] ?? '');
$senha = $_POST['senha'] ?? '';

// Validação básica
if (empty($nome)) {
    echo json_encode([
        'success' => false,
        'message' => 'O nome é obrigatório'
    ]);
    exit;
}

if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Email inválido'
    ]);
    exit;
}

// Preparar a consulta SQL base
$sql = "UPDATE professor SET nome = :nome, email = :email, telefone = :telefone";
$params = [
    ':nome' => $nome,
    ':email' => $email,
    ':telefone' => $telefone
];

// Adicionar senha à consulta se foi fornecida
if (!empty($senha)) {
    $sql .= ", senha = :senha";
    $params[':senha'] = password_hash($senha, PASSWORD_DEFAULT);
}

// Processar foto se foi enviada
$foto_url = null;
if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = '../../uploads/fotos/';
    
    // Criar o diretório se não existir
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Gerar um nome único para o arquivo
    $filename = uniqid() . '_' . basename($_FILES['foto']['name']);
    $upload_file = $upload_dir . $filename;
    
    // Verificar tipo de arquivo (apenas imagens)
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $file_type = $_FILES['foto']['type'];
    
    if (!in_array($file_type, $allowed_types)) {
        echo json_encode([
            'success' => false,
            'message' => 'Tipo de arquivo não permitido. Envie apenas imagens (JPG, PNG, GIF).'
        ]);
        exit;
    }
    
    // Mover o arquivo para o diretório de uploads
    if (move_uploaded_file($_FILES['foto']['tmp_name'], $upload_file)) {
        // Salvar caminho relativo consistente
        $foto_url = 'uploads/fotos/' . $filename;
        
        // Adicionar foto à consulta SQL
        $sql .= ", foto = :foto";
        $params[':foto'] = $foto_url;
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao fazer upload da foto'
        ]);
        exit;
    }
}

// Adicionar cláusula WHERE
$sql .= " WHERE id = :id";
$params[':id'] = $id;

// Executar a consulta
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    if ($stmt->rowCount() > 0) {
        // Atualizar informações da sessão
        $_SESSION['usuario_nome'] = $nome;
        
        if ($foto_url) {
            $_SESSION['usuario_foto'] = $foto_url;
        }
        
        // Buscar professor atualizado
        $stmt = $pdo->prepare("SELECT * FROM professor WHERE id = ?");
        $stmt->execute([$id]);
        $professor = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Para debug - adicionar verificação de caminhos
        $debug_info = [];
        
        // Verificar e ajustar o caminho da foto para exibição
        if (!empty($professor['foto'])) {
            // Obter apenas o nome do arquivo, independente do que esteja no banco
            $filename = basename($professor['foto']);
            
            // Definir diretamente o URL correto
            $usuario_foto = "http://172.16.253.44/luis/superacao/uploads/fotos/" . $filename;
            
            // Atualizar na sessão
            $_SESSION['usuario_foto'] = $usuario_foto;
            
            // Atualizar o valor na resposta
            $professor['foto_url'] = $usuario_foto;
            
            // Adicionar ao debug
            $debug_info[] = "Nome do arquivo: " . $filename;
            $debug_info[] = "Caminho final da foto: " . $usuario_foto;
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Perfil atualizado com sucesso',
            'professor' => $professor,
            'debug' => $debug_info  // Remover isto após o teste
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'Nenhuma alteração realizada'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao atualizar perfil: ' . $e->getMessage()
    ]);
    exit;
}