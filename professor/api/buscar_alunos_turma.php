<?php
session_start();

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

// Verificar se turma_id foi enviado
if (!isset($_GET['turma_id']) || empty($_GET['turma_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID da turma não informado']);
    exit;
}

// Configuração do banco de dados - CORRIGINDO O CAMINHO
// Se o dashboard está na raiz e a API está em /api/, então:
if (file_exists("../env_config.php")) {
    require "../env_config.php";
} else if (file_exists("../../env_config.php")) {
    require "../../env_config.php"; 
} else if (file_exists("env_config.php")) {
    require "env_config.php";
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Arquivo de configuração não encontrado']);
    exit;
}

$db_host =  $_ENV['DB_HOST'];
$db_name =  $_ENV['DB_NAME'];
$db_user = $_ENV['DB_USER'];
$db_pass =  $_ENV['DB_PASS'];

// Conexão com o banco de dados
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erro na conexão: ' . $e->getMessage()]);
    exit;
}

$usuario_id = $_SESSION["usuario_id"];
$turma_id = $_GET['turma_id'];

// Verificar se a turma pertence ao professor
try {
    $stmt = $pdo->prepare("SELECT t.*, u.nome as nome_unidade FROM turma t JOIN unidade u ON t.id_unidade = u.id WHERE t.id = ? AND t.id_professor = ?");
    $stmt->execute([$turma_id, $usuario_id]);
    $turma = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$turma) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Turma não encontrada ou não pertence a você']);
        exit;
    }
    
    // Buscar alunos da turma
    $stmt = $pdo->prepare("SELECT a.id, a.nome, a.numero_matricula FROM alunos a JOIN matriculas m ON a.id = m.aluno_id WHERE m.turma = ? AND m.status = 'ativo' ORDER BY a.nome");
    $stmt->execute([$turma_id]);
    $alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'alunos' => $alunos,
        'message' => count($alunos) . ' aluno(s) encontrado(s)'
    ]);
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
}
?>